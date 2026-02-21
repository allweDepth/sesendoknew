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
        /* ==============================
           LOAD DATA
        ============================== */

        $naskah = $this->db->query(
            "SELECT * FROM trx_naskah_dinas WHERE id = ?",
            [$naskahId]
        )->fetch();

        $struktur = $this->db->query(
            "SELECT * FROM trx_naskah_struktur WHERE naskah_id = ?",
            [$naskahId]
        )->fetch();

        $jenis = $this->db->query(
            "SELECT * FROM ref_jenis_naskah WHERE id = ?",
            [$naskah['jenis_id']]
        )->fetch();

        if (!$naskah || !$struktur || !$jenis) {
            throw new Exception("Data tidak lengkap");
        }

        $schema = json_decode($jenis['schema_json'], true);
        $data   = json_decode($struktur['struktur_json'], true);

        /* ==============================
           INIT PDF
        ============================== */

        $pdf = new TCPDF();
        $pdf->SetMargins(25, 30, 25);
        $pdf->AddPage();
        $pdf->SetFont('times', '', 12);

        /* ==============================
           RENDER SECTION GENERIK
        ============================== */

        foreach ($schema['sections'] as $section) {

            $key = $section['key'];
            $type = $section['type'];

            if (!isset($data[$key])) continue;

            $pdf->Ln(4);

            if (!empty($section['label'])) {
                $pdf->SetFont('times', 'B', 12);
                $pdf->MultiCell(0, 6, strtoupper($section['label']));
                $pdf->SetFont('times', '', 12);
                $pdf->Ln(2);
            }

            $this->renderSection($pdf, $type, $data[$key]);
        }

        /* ==============================
           OUTPUT FILE
        ============================== */

        $filePath = __DIR__ . "/../../public/uploads/naskah_$naskahId.pdf";
        $pdf->Output($filePath, 'F');

        return "/uploads/naskah_$naskahId.pdf";
    }


    private function renderSection($pdf, string $type, $data)
    {
        switch ($type) {

            case 'list_huruf':
                foreach ($data as $item) {
                    $pdf->MultiCell(0, 6, $item['huruf'] . ". " . $item['uraian']);
                }
            break;

            case 'list_nomor':
                foreach ($data as $item) {
                    $pdf->MultiCell(0, 6, $item['nomor'] . ". " . $item['uraian']);
                }
            break;

            case 'textarea':
                $pdf->MultiCell(0, 6, $data);
            break;

            case 'free_editor':
                $pdf->writeHTML($data, true, false, true, false, '');
            break;

            default:
                $pdf->MultiCell(0, 6, print_r($data, true));
        }
    }
}