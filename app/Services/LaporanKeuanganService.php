<?php

require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/KontrakRealisasiService.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class LaporanKeuanganService
{
  private DB $db;
  private array $user;

  private const BALANCE_TABLE = 'laporan_neraca_saldo_neo';
  private const NOTE_TABLE = 'laporan_calk_neo';
  private const READ_ROLES = ['super_admin', 'admin_wilayah', 'tapd', 'admin_opd', 'kepala_opd', 'pa_kpa', 'bendahara'];
  private const WRITE_ROLES = ['super_admin', 'admin_wilayah', 'admin_opd', 'kepala_opd', 'pa_kpa', 'bendahara'];
  private const NOTE_SECTIONS = [
    'umum' => 'Informasi Umum',
    'kebijakan' => 'Kebijakan Akuntansi',
    'lra' => 'Penjelasan LRA',
    'aset' => 'Aset',
    'kewajiban' => 'Kewajiban',
    'ekuitas' => 'Ekuitas',
    'lainnya' => 'Pengungkapan Lainnya',
  ];

  public function __construct(array $user)
  {
    $this->db = DB::getInstance();
    $this->user = Auth::scopedUser() ?: $user;
    $this->scope();
  }

  private function scope(): array
  {
    $region = trim((string)($this->user['kd_wilayah'] ?? ''));
    $opd = trim((string)($this->user['kd_opd'] ?? ''));
    $year = (int)($this->user['tahun'] ?? 0);
    if ($region === '' || $year < 2000 || $year > 2200) {
      throw new RuntimeException('Scope wilayah dan tahun pengguna tidak lengkap.');
    }
    if ($opd === '' || $opd === '0') $opd = '0';
    return [$region, $opd, $year];
  }

  private function assertCanRead(): void
  {
    if (!in_array((string)($this->user['type_user'] ?? 'viewer'), self::READ_ROLES, true)) {
      throw new RuntimeException('Role ini tidak memiliki akses ke saldo Neraca dan pengungkapan CaLK.');
    }
  }

  private function assertCanWrite(): void
  {
    if (!in_array((string)($this->user['type_user'] ?? 'viewer'), self::WRITE_ROLES, true)) {
      throw new RuntimeException('Role ini hanya dapat membaca dan mengekspor laporan keuangan.');
    }
  }

  public static function groupForAccount(string $code): string
  {
    $code = trim($code);
    if (!preg_match('/^[1-3](?:\.[0-9]+)*$/D', $code)) {
      throw new InvalidArgumentException('Kode rekening Neraca harus mengikuti struktur akun 1 (Aset), 2 (Kewajiban), atau 3 (Ekuitas).');
    }
    return match ($code[0]) {
      '1' => 'ASET',
      '2' => 'KEWAJIBAN',
      '3' => 'EKUITAS',
    };
  }

  private function normalizeBalances(array $rows): array
  {
    if (count($rows) > 10000) throw new InvalidArgumentException('Maksimal 10.000 rekening per impor.');
    if (!$rows) throw new InvalidArgumentException('Masukkan minimal satu rekening saldo sebelum menyimpan.');
    $normalized = [];
    $seen = [];
    foreach ($rows as $index => $row) {
      if (!is_array($row)) throw new InvalidArgumentException('Baris saldo ke-' . ($index + 1) . ' tidak valid.');
      $code = trim((string)($row['kode_akun'] ?? ''));
      $name = trim((string)($row['uraian'] ?? ''));
      $group = strtoupper(trim((string)($row['kelompok'] ?? '')));
      $derivedGroup = self::groupForAccount($code);
      if ($name === '' || mb_strlen($name) > 500) throw new InvalidArgumentException('Uraian rekening ke-' . ($index + 1) . ' wajib diisi (maksimal 500 karakter).');
      if ($group !== $derivedGroup) throw new InvalidArgumentException("Kelompok rekening $code harus $derivedGroup sesuai kode akun.");
      if (isset($seen[$code])) throw new InvalidArgumentException("Kode rekening $code tercantum lebih dari satu kali.");
      $opening = $row['saldo_awal'] ?? null;
      $closing = $row['saldo_akhir'] ?? null;
      if (!is_numeric($opening) || !is_numeric($closing) || !is_finite((float)$opening) || !is_finite((float)$closing) || abs((float)$opening) >= 1.0e18 || abs((float)$closing) >= 1.0e18) {
        throw new InvalidArgumentException("Saldo awal/akhir rekening $code harus berupa angka.");
      }
      $seen[$code] = true;
      $normalized[] = [
        'kode_akun' => $code,
        'uraian' => $name,
        'kelompok' => $group,
        'saldo_awal' => (float)$opening,
        'saldo_akhir' => (float)$closing,
        'catatan' => trim((string)($row['catatan'] ?? '')),
      ];
    }
    $codes = array_keys($seen);
    sort($codes, SORT_STRING);
    for ($index = 0, $count = count($codes) - 1; $index < $count; $index++) {
      if (str_starts_with($codes[$index + 1], $codes[$index] . '.')) {
        throw new InvalidArgumentException("Rekening {$codes[$index]} dan {$codes[$index + 1]} berisi induk serta rincian sekaligus; pilih satu level agar saldo tidak dihitung ganda.");
      }
    }
    return $normalized;
  }

  public function balanceData(): array
  {
    $this->assertCanRead();
    [$region, $opd, $year] = $this->scope();
    $rows = $this->db->query(
      'SELECT id,kode_akun,uraian,kelompok,saldo_awal,saldo_akhir,catatan FROM ' . self::BALANCE_TABLE .
      ' WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY kode_akun',
      [$region, $opd, $year]
    )->fetchAll();
    return ['tahun' => $year, 'rows' => $rows, 'summary' => self::balanceSummary($rows)];
  }

  public static function balanceSummary(array $rows): array
  {
    $totals = ['saldo_awal' => ['ASET' => 0.0, 'KEWAJIBAN' => 0.0, 'EKUITAS' => 0.0], 'saldo_akhir' => ['ASET' => 0.0, 'KEWAJIBAN' => 0.0, 'EKUITAS' => 0.0]];
    foreach ($rows as $row) {
      $group = (string)$row['kelompok'];
      if (!isset($totals['saldo_awal'][$group])) continue;
      $totals['saldo_awal'][$group] += (float)$row['saldo_awal'];
      $totals['saldo_akhir'][$group] += (float)$row['saldo_akhir'];
    }
    $openingDifference = $totals['saldo_awal']['ASET'] - $totals['saldo_awal']['KEWAJIBAN'] - $totals['saldo_awal']['EKUITAS'];
    $closingDifference = $totals['saldo_akhir']['ASET'] - $totals['saldo_akhir']['KEWAJIBAN'] - $totals['saldo_akhir']['EKUITAS'];
    return [
      'totals' => $totals,
      'selisih_awal' => $openingDifference,
      'selisih_akhir' => $closingDifference,
      'seimbang' => abs($openingDifference) < 0.01 && abs($closingDifference) < 0.01 && count($rows) > 0,
    ];
  }

  public function saveBalances(array $rows, string $source = 'manual'): array
  {
    $this->assertCanWrite();
    $rows = $this->normalizeBalances($rows);
    if (!in_array($source, ['manual', 'excel'], true)) throw new InvalidArgumentException('Sumber data saldo tidak dikenal.');
    [$region, $opd, $year] = $this->scope();
    $this->db->begin();
    try {
      $this->db->update(self::BALANCE_TABLE, ['is_deleted' => 1], 'WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0', [$region, $opd, $year]);
      foreach ($rows as $row) {
        $this->db->insert(self::BALANCE_TABLE, $row + [
          'kd_wilayah' => $region,
          'kd_opd' => $opd,
          'tahun' => $year,
          'sumber' => $source,
          'username_insert' => (string)($this->user['username'] ?? 'system'),
        ]);
      }
      $this->db->commit();
    } catch (Throwable $e) {
      $this->db->rollback();
      throw $e;
    }
    return $this->balanceData();
  }

  public function noteData(): array
  {
    $this->assertCanRead();
    [$region, $opd, $year] = $this->scope();
    $rows = $this->db->query(
      'SELECT id,bagian,judul,uraian,urutan FROM ' . self::NOTE_TABLE .
      ' WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY urutan,id',
      [$region, $opd, $year]
    )->fetchAll();
    return ['tahun' => $year, 'sections' => self::NOTE_SECTIONS, 'rows' => $rows, 'neraca' => $this->balanceData()['summary']];
  }

  public function saveNotes(array $rows): array
  {
    $this->assertCanWrite();
    if (count($rows) > 200) throw new InvalidArgumentException('Maksimal 200 catatan CaLK.');
    [$region, $opd, $year] = $this->scope();
    $normalized = [];
    foreach ($rows as $index => $row) {
      if (!is_array($row)) throw new InvalidArgumentException('Catatan CaLK ke-' . ($index + 1) . ' tidak valid.');
      $section = trim((string)($row['bagian'] ?? ''));
      $title = trim((string)($row['judul'] ?? ''));
      $body = trim((string)($row['uraian'] ?? ''));
      if (!isset(self::NOTE_SECTIONS[$section])) throw new InvalidArgumentException('Bagian CaLK ke-' . ($index + 1) . ' tidak dikenal.');
      if ($title === '' || mb_strlen($title) > 255 || $body === '' || mb_strlen($body) > 65000) throw new InvalidArgumentException('Judul dan uraian setiap catatan CaLK wajib diisi (uraian maksimal 65.000 karakter).');
      $normalized[] = ['bagian' => $section, 'judul' => $title, 'uraian' => $body, 'urutan' => $index + 1];
    }
    $this->db->begin();
    try {
      $this->db->update(self::NOTE_TABLE, ['is_deleted' => 1], 'WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0', [$region, $opd, $year]);
      foreach ($normalized as $row) {
        $this->db->insert(self::NOTE_TABLE, $row + [
          'kd_wilayah' => $region,
          'kd_opd' => $opd,
          'tahun' => $year,
          'username_insert' => (string)($this->user['username'] ?? 'system'),
        ]);
      }
      $this->db->commit();
    } catch (Throwable $e) {
      $this->db->rollback();
      throw $e;
    }
    return $this->noteData();
  }

  public function importBalances(string $path): array
  {
    $this->assertCanWrite();
    $book = IOFactory::load($path);
    $sheet = $book->getSheetByName('Saldo Neraca');
    if (!$sheet) throw new InvalidArgumentException('Sheet "Saldo Neraca" tidak ditemukan. Gunakan template dari menu laporan keuangan.');
    $expected = ['KODE_REKENING', 'URAIAN_REKENING', 'KELOMPOK', 'SALDO_AWAL', 'SALDO_AKHIR', 'CATATAN'];
    $headers = array_map(static fn($v) => strtoupper(trim((string)$v)), $sheet->rangeToArray('A1:F1', null, true, false)[0] ?? []);
    if ($headers !== $expected) throw new InvalidArgumentException('Kolom template tidak sesuai. Unduh template terbaru sebelum mengimpor.');
    $rows = [];
    foreach ($sheet->getRowIterator(2) as $row) {
      $values = $sheet->rangeToArray('A' . $row->getRowIndex() . ':F' . $row->getRowIndex(), null, true, false)[0];
      if (count(array_filter($values, static fn($value) => trim((string)$value) !== '')) === 0) continue;
      $rows[] = ['kode_akun' => $values[0], 'uraian' => $values[1], 'kelompok' => $values[2], 'saldo_awal' => $values[3], 'saldo_akhir' => $values[4], 'catatan' => $values[5] ?? ''];
    }
    if (!$rows) throw new InvalidArgumentException('Template tidak berisi rekening saldo.');
    return $this->saveBalances($rows, 'excel');
  }

  public function template(): string
  {
    $book = new Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Saldo Neraca');
    $sheet->fromArray(['KODE_REKENING', 'URAIAN_REKENING', 'KELOMPOK', 'SALDO_AWAL', 'SALDO_AKHIR', 'CATATAN'], null, 'A1', true);
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()->setFillType('solid')->getStartColor()->setRGB('DCEBFA');
    $validation = $sheet->getCell('C2')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(false);
    $validation->setShowErrorMessage(true);
    $validation->setFormula1('"ASET,KEWAJIBAN,EKUITAS"');
    for ($row = 2; $row <= 1000; $row++) $sheet->setDataValidation('C' . $row, clone $validation);
    foreach (range('A', 'F') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
    $sheet->freezePane('A2');
    $writer = new Xlsx($book);
    $path = tempnam(sys_get_temp_dir(), 'lkn-template-');
    if ($path === false) throw new RuntimeException('Tidak dapat menyiapkan file template.');
    $writer->save($path);
    return $path;
  }

  private function excelText(string $value): string
  {
    return preg_match('/^\s*[=+\-@]/', $value) ? "'" . $value : $value;
  }

  public function lraData(): array
  {
    $user = $this->user;
    $service = new KontrakRealisasiService($user);
    $rows = $service->lraRows();
    return [
      'tahun' => (int)($user['tahun'] ?? 0),
      'rows' => $rows,
      'summary' => [
        'pagu' => array_sum(array_column($rows, 'pagu')),
        'realisasi' => array_sum(array_column($rows, 'realisasi')),
        'sisa' => array_sum(array_column($rows, 'sisa')),
      ],
      'source' => 'Realisasi transaksi kontrak yang tercatat pada aplikasi.',
    ];
  }

  public function export(string $report, string $format): string
  {
    $this->assertCanRead();
    if (!in_array($report, ['neraca', 'calk'], true) || !in_array($format, ['xlsx', 'pdf'], true)) {
      throw new InvalidArgumentException('Jenis ekspor laporan tidak valid.');
    }
    $data = $report === 'neraca' ? $this->balanceData() : $this->noteData();
    return $format === 'xlsx' ? $this->exportExcel($report, $data) : $this->exportPdf($report, $data);
  }

  private function exportExcel(string $report, array $data): string
  {
    $book = new Spreadsheet();
    $sheet = $book->getActiveSheet();
    if ($report === 'neraca') {
      $sheet->setTitle('Neraca');
      $sheet->fromArray(['KERTAS KERJA NERACA - TAHUN ' . $data['tahun']], null, 'A1');
      $sheet->fromArray([$data['summary']['seimbang'] ? 'STATUS: SEIMBANG' : 'STATUS: BELUM SEIMBANG - PERLU VERIFIKASI'], null, 'A2');
      $sheet->fromArray(['KODE REKENING', 'URAIAN', 'KELOMPOK', 'SALDO AWAL', 'SALDO AKHIR', 'CATATAN'], null, 'A3', true);
      $rowNo = 4;
      foreach ($data['rows'] as $row) {
        $sheet->fromArray([$this->excelText($row['kode_akun']), $this->excelText($row['uraian']), $this->excelText($row['kelompok']), (float)$row['saldo_awal'], (float)$row['saldo_akhir'], $this->excelText((string)($row['catatan'] ?? ''))], null, 'A' . $rowNo++, true);
      }
      $sheet->fromArray(['TOTAL ASET', 'ASET', '', $data['summary']['totals']['saldo_awal']['ASET'], $data['summary']['totals']['saldo_akhir']['ASET']], null, 'A' . $rowNo++, true);
      $sheet->fromArray(['TOTAL KEWAJIBAN', 'KEWAJIBAN', '', $data['summary']['totals']['saldo_awal']['KEWAJIBAN'], $data['summary']['totals']['saldo_akhir']['KEWAJIBAN']], null, 'A' . $rowNo++, true);
      $sheet->fromArray(['TOTAL EKUITAS', 'EKUITAS', '', $data['summary']['totals']['saldo_awal']['EKUITAS'], $data['summary']['totals']['saldo_akhir']['EKUITAS']], null, 'A' . $rowNo++, true);
      $sheet->fromArray(['SELISIH ASET - KEWAJIBAN - EKUITAS', '', '', $data['summary']['selisih_awal'], $data['summary']['selisih_akhir']], null, 'A' . $rowNo, true);
      $sheet->getStyle('D4:E' . $rowNo)->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00);-');
      $sheet->getStyle('A3:F3')->getFont()->setBold(true);
    } else {
      $sheet->setTitle('CaLK');
      $sheet->fromArray(['KERTAS KERJA CATATAN ATAS LAPORAN KEUANGAN - TAHUN ' . $data['tahun']], null, 'A1');
      $sheet->fromArray(['BAGIAN', 'JUDUL', 'URAIAN'], null, 'A3', true);
      $rowNo = 4;
      foreach ($data['rows'] as $row) {
        $sheet->fromArray([$this->excelText(self::NOTE_SECTIONS[$row['bagian']] ?? $row['bagian']), $this->excelText($row['judul']), $this->excelText($row['uraian'])], null, 'A' . $rowNo++, true);
      }
      $sheet->getColumnDimension('C')->setWidth(100);
      $sheet->getStyle('C4:C' . max(4, $rowNo))->getAlignment()->setWrapText(true);
      $sheet->getStyle('A3:C3')->getFont()->setBold(true);
    }
    foreach (range('A', $report === 'neraca' ? 'F' : 'B') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
    $path = tempnam(sys_get_temp_dir(), 'lkn-export-');
    if ($path === false) throw new RuntimeException('Tidak dapat menyiapkan file ekspor.');
    (new Xlsx($book))->save($path);
    return $path;
  }

  private function exportPdf(string $report, array $data): string
  {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('seSendok');
    $pdf->SetTitle($report === 'neraca' ? 'Kertas Kerja Neraca' : 'Kertas Kerja CaLK');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();
    $title = $report === 'neraca' ? 'NERACA' : 'CATATAN ATAS LAPORAN KEUANGAN';
    $html = '<h2 style="text-align:center">' . $title . ' TAHUN ' . (int)$data['tahun'] . '</h2><p style="text-align:center"><b>KERTAS KERJA - perlu verifikasi dan pengesahan pejabat berwenang</b></p>';
    if ($report === 'neraca') {
      $html .= '<table border="1" cellpadding="4"><thead><tr style="font-weight:bold;background-color:#dcebf7"><th>Kode</th><th>Uraian</th><th>Kelompok</th><th>Saldo Awal</th><th>Saldo Akhir</th></tr></thead><tbody>';
      foreach ($data['rows'] as $row) {
        $html .= '<tr><td>' . htmlspecialchars((string)$row['kode_akun'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars((string)$row['uraian'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars((string)$row['kelompok'], ENT_QUOTES, 'UTF-8') . '</td><td align="right">' . number_format((float)$row['saldo_awal'], 2, ',', '.') . '</td><td align="right">' . number_format((float)$row['saldo_akhir'], 2, ',', '.') . '</td></tr>';
      }
      $summary = $data['summary'];
      foreach (['ASET' => 'Aset', 'KEWAJIBAN' => 'Kewajiban', 'EKUITAS' => 'Ekuitas'] as $group => $label) {
        $html .= '<tr style="font-weight:bold;background-color:#f1f5f9"><td colspan="3">TOTAL ' . $label . '</td><td align="right">' . number_format($summary['totals']['saldo_awal'][$group], 2, ',', '.') . '</td><td align="right">' . number_format($summary['totals']['saldo_akhir'][$group], 2, ',', '.') . '</td></tr>';
      }
      $html .= '</tbody></table><br><b>Selisih awal (Aset - Kewajiban - Ekuitas):</b> ' . number_format($summary['selisih_awal'], 2, ',', '.') .
        '<br><b>Selisih akhir (Aset - Kewajiban - Ekuitas):</b> ' . number_format($summary['selisih_akhir'], 2, ',', '.') .
        '<br><b>Status keseimbangan:</b> ' . ($summary['seimbang'] ? 'Seimbang' : 'Belum seimbang');
    } else {
      if (!$data['rows']) $html .= '<p>Belum ada pengungkapan CaLK.</p>';
      foreach ($data['rows'] as $row) {
        $section = self::NOTE_SECTIONS[$row['bagian']] ?? $row['bagian'];
        $html .= '<h3>' . htmlspecialchars($section, ENT_QUOTES, 'UTF-8') . '</h3><h4>' . htmlspecialchars((string)$row['judul'], ENT_QUOTES, 'UTF-8') . '</h4><p>' . nl2br(htmlspecialchars((string)$row['uraian'], ENT_QUOTES, 'UTF-8')) . '</p>';
      }
    }
    $pdf->writeHTML($html, true, false, true, false, '');
    return $pdf->Output('', 'S');
  }
}
