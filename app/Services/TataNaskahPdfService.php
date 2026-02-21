<?php

require_once __DIR__ . '/../../vendor/tcpdf/tcpdf.php';

class TataNaskahPdfService
{
    private DB $db;

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    public function generate(int $naskahId): string
{
    $naskah = $this->db->query(
        "SELECT * FROM trx_naskah_dinas WHERE id = ?",
        [$naskahId]
    )->fetch();

    if (!$naskah) {
        throw new Exception("Naskah tidak ditemukan");
    }

    $struktur = $this->db->query(
        "SELECT * FROM trx_naskah_struktur WHERE naskah_id = ?",
        [$naskahId]
    )->fetch();

    if (!$struktur) {
        throw new Exception("Struktur tidak ditemukan");
    }

    $data = json_decode($struktur['struktur_json'], true);

    $pdf = new TCPDF();
    $pdf->SetMargins(30, 30, 25);
    $pdf->AddPage();
    $pdf->SetFont('times', '', 12);

    /* =========================
       KOP
    ========================= */
    $pdf->SetFont('times','B',14);
    $pdf->Cell(0,6,'PEMERINTAH KABUPATEN PASANGKAYU',0,1,'C');

    $pdf->SetFont('times','B',12);
    $pdf->Cell(0,6,'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG',0,1,'C');

    $pdf->Ln(3);
    $pdf->Line(30, $pdf->GetY(), 185, $pdf->GetY());
    $pdf->Ln(8);

    /* =========================
       JUDUL
    ========================= */
    $pdf->SetFont('times','B',14);
    $pdf->Cell(0,6,'KEPUTUSAN KEPALA DINAS',0,1,'C');
    $pdf->Ln(3);

    $pdf->SetFont('times','',12);
    $pdf->Cell(0,6,'NOMOR : '.$data['nomor'],0,1,'C');
    $pdf->Ln(6);

    /* =========================
       TENTANG
    ========================= */
    $pdf->SetFont('times','B',12);
    $pdf->Cell(0,6,'TENTANG',0,1,'C');
    $pdf->Ln(2);

    $pdf->MultiCell(0,6,strtoupper($data['tentang']),0,'C');
    $pdf->Ln(6);

    /* =========================
       PEMBUKA
    ========================= */
    $pdf->Cell(0,6,'KEPALA DINAS PEKERJAAN UMUM DAN PENATAAN RUANG,',0,1,'L');
    $pdf->Ln(4);

    /* =========================
       MENIMBANG
    ========================= */
    $pdf->Cell(30,6,'Menimbang',0,0);
    $pdf->Cell(5,6,':',0,1);

    foreach ($data['menimbang'] as $item) {
        $pdf->MultiCell(0,6,"   ".$item['huruf'].". ".$item['uraian']);
    }

    $pdf->Ln(4);

    /* =========================
       MENGINGAT
    ========================= */
    $pdf->Cell(30,6,'Mengingat',0,0);
    $pdf->Cell(5,6,':',0,1);

    foreach ($data['mengingat'] as $item) {
        $pdf->MultiCell(0,6,"   ".$item['nomor'].". ".$item['uraian']);
    }

    $pdf->Ln(6);

    /* =========================
       MEMUTUSKAN
    ========================= */
    $pdf->SetFont('times','B',12);
    $pdf->Cell(0,6,'MEMUTUSKAN:',0,1);
    $pdf->Ln(4);

    $pdf->SetFont('times','',12);

    foreach ($data['menetapkan'] as $item) {

        $pdf->SetFont('times','B',12);
        $pdf->Cell(30,6,$item['judul'],0,0);

        $pdf->SetFont('times','',12);
        $pdf->MultiCell(0,6,": ".$item['isi']);
        $pdf->Ln(2);
    }

    /* =========================
       TANDA TANGAN
    ========================= */
    $pdf->Ln(10);
    $pdf->Cell(0,6,'Ditetapkan di Pasangkayu',0,1,'R');
    $pdf->Cell(0,6,'Pada tanggal '.$data['tanggal'],0,1,'R');
    $pdf->Ln(6);

    $pdf->Cell(0,6,'KEPALA DINAS,',0,1,'R');
    $pdf->Ln(20);

    $pdf->SetFont('times','B',12);
    $pdf->Cell(0,6,$_SESSION['user']['nama'],0,1,'R');

    $pdf->SetFont('times','',12);
    $pdf->Cell(0,6,'NIP. '.$_SESSION['user']['nip'],0,1,'R');

    $filePath = __DIR__ . "/../../public/uploads/naskah_$naskahId.pdf";
    $pdf->Output($filePath,'F');

    return "/uploads/naskah_$naskahId.pdf";
}

    /* =========================================================
       RENDER SECTION
    ========================================================= */
    private function renderSection($pdf, string $type, $data)
    {
        switch ($type) {

            case 'list_huruf':
                foreach ($data as $item) {
                    $pdf->MultiCell(0,6,$item['huruf'].'. '.$item['uraian']);
                }
            break;

            case 'list_nomor':
                foreach ($data as $item) {
                    $pdf->MultiCell(0,6,$item['nomor'].'. '.$item['uraian']);
                }
            break;

            case 'textarea':
                $pdf->MultiCell(0,6,$data);
            break;

            case 'free_editor':
                $pdf->writeHTML($data, true, false, true, false, '');
            break;
        }
    }

    /* =========================================================
       KOP OPD
    ========================================================= */
    private function renderKop($pdf)
    {
        $pdf->SetFont('times','B',14);
        $pdf->Cell(0,6,'PEMERINTAH KABUPATEN PASANGKAYU',0,1,'C');

        $pdf->SetFont('times','B',12);
        $pdf->Cell(0,6,'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG',0,1,'C');

        $pdf->SetFont('times','',10);
        $pdf->Cell(0,6,'Alamat OPD di sini',0,1,'C');

        $pdf->Ln(4);
        $pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
        $pdf->Ln(6);
    }

    /* =========================================================
       TANDA TANGAN
    ========================================================= */
    private function renderSignature($pdf, $signedBy)
    {
        if (!$signedBy) return;

        $user = $this->db->query(
            "SELECT * FROM user_sesendok_biila WHERE username = ?",
            [$signedBy]
        )->fetch();

        if (!$user) return;

        if (!$user['signature_image'] || !$user['signature_verified']) {
            return;
        }

        $imagePath = __DIR__ . "/../../public/uploads/signature/" . $user['signature_image'];

        if (!file_exists($imagePath)) return;

        $pdf->Ln(10);
        $pdf->Cell(0,6,'Ditetapkan Oleh:',0,1,'R');

        $pdf->Image($imagePath, 130, $pdf->GetY(), 40);

        $pdf->Ln(25);
        $pdf->Cell(0,6,$user['nama'],0,1,'R');
        $pdf->Cell(0,6,'NIP. '.$user['nip'],0,1,'R');
    }
}