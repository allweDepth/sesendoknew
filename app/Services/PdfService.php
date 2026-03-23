<?php

require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/PdfTemplateService.php';
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

    $pdf = new TCPDF(); // init
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
      "SELECT * FROM trx_naskah_dinas WHERE id = ?",
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
    $pdf = new TCPDF();
    $pdf->AddPage();

    // =====================================================
    // RENDER TEMPLATE
    // =====================================================
    $tpl = new PdfTemplateService();
    $tpl->render($pdf, $schemaData, $strukturData);

    return $pdf->Output('', 'S');
  }
}