<?php

require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/PdfTemplateService.php';
require_once __DIR__ . '/PageSetupService.php';
require_once __DIR__ . '/../Core/DB.php';

class PdfService
{
  private DB $db;

  public function __construct()
  {
    $this->db = DB::getInstance(); // koneksi DB
  }

  public function generate(string $tbl, int $id): string
  {
    // =====================================================
    // ROUTING PER TABEL
    // =====================================================
    switch ($tbl) {

      case 'trx_naskah_dinas':
        return $this->generateTataNaskah($id); // khusus

      default:
        return $this->generateGeneric($tbl, $id); // default
    }
  }

  // =====================================================
  // GENERIC (SEMUA TABEL)
  // =====================================================
  private function generateGeneric(string $tbl, int $id): string
  {
    $data = $this->db->query(
      "SELECT * FROM {$tbl} WHERE id = ?",
      [$id]
    )->fetch(); // ambil data

    if (!$data) {
      throw new Exception("Data tidak ditemukan");
    }

    $setup=PageSetupService::current();
    $pdf = new TCPDF(PageSetupService::orientation($setup,'P'),'mm',PageSetupService::tcpdfFormat($setup),true,'UTF-8',false);
    $pdf->AddPage();
    $pdf->SetFont('times', '', 11);

    foreach ($data as $key => $val) {

      $pdf->SetFont('times', 'B', 11);
      $pdf->Cell(50, 6, strtoupper($key), 0, 0);

      $pdf->SetFont('times', '', 11);
      $pdf->MultiCell(0, 6, ': ' . $val, 0, 1);
    }

    return $pdf->Output('', 'S'); // return string
  }

  // =====================================================
  // KHUSUS TATA NASKAH (TEMPLATE JSON)
  // =====================================================
  private function generateTataNaskah(int $id): string
  {
    // =====================================================
    // ambil data utama
    // =====================================================
    $naskah = $this->db->query(
      "SELECT t.*,j.nama jenis_naskah,k.kode kode_keamanan,k.nama klasifikasi_keamanan FROM trx_naskah_dinas t LEFT JOIN ref_jenis_naskah j ON j.id=t.jenis_id LEFT JOIN ref_klasifikasi_keamanan k ON k.id=t.klasifikasi_id WHERE t.id = ?",
      [$id]
    )->fetch();

    // =====================================================
    // struktur_json
    // =====================================================
    $struktur = $this->db->query(
      "SELECT struktur_json FROM trx_naskah_struktur WHERE naskah_id = ?",
      [$id]
    )->fetch();

    // =====================================================
    // schema_json
    // =====================================================
    $jenis = $this->db->query(
      "SELECT r.schema_json 
             FROM ref_jenis_naskah r
             JOIN trx_naskah_dinas t ON t.jenis_id = r.id
             WHERE t.id = ?",
      [$id]
    )->fetch();

    if (!$naskah || !$struktur || !$jenis) {
      throw new Exception("Data tidak lengkap");
    }

    $strukturData = json_decode($struktur['struktur_json'], true); // isi
    $schemaData   = json_decode($jenis['schema_json'], true); // template
    if(!empty($strukturData['penandatangan']) && ctype_digit((string)$strukturData['penandatangan'])){
      $pegawai=$this->db->query('SELECT CONCAT_WS(" ",gelar_depan,nama) nama FROM db_asn_pemda_neo WHERE id=?',[(int)$strukturData['penandatangan']])->fetch();
      if($pegawai)$strukturData['nama_penandatangan']=$pegawai['nama'];
    }

    // =====================================================
    // FALLBACK TEMPLATE
    // =====================================================
    if (!$schemaData) {

      $schemaData = [];

      foreach ($strukturData as $key => $val) {
        $schemaData[] = [
          'field' => $key,
          'label' => strtoupper($key),
          'type'  => 'text'
        ];
      }
    }

    // =====================================================
    // INIT PDF
    // =====================================================
    $setup=PageSetupService::current();
    $pdf = new TCPDF(PageSetupService::orientation($setup,'P'), 'mm', PageSetupService::tcpdfFormat($setup), true, 'UTF-8', false);
    $pdf->SetCreator('seSendok Tata Naskah');
    $pdf->SetTitle(($naskah['jenis_naskah'] ?? 'Naskah Dinas').' '.$naskah['nomor']);
    $pdf->SetMargins(25, 18, 20);
    $pdf->SetAutoPageBreak(true, 22);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    // =====================================================
    // RENDER TEMPLATE
    // =====================================================
    $tpl = new PdfTemplateService();
    $logo = __DIR__.'/../../public/assets/img/umum/logo.png';
    $tpl->renderOfficial($pdf, $naskah, $schemaData, $strukturData, is_file($logo)?$logo:null);

    return $pdf->Output('', 'S');
  }
}
