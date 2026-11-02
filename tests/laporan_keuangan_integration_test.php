<?php
require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/LaporanKeuanganService.php';

$db = DB::getInstance();
$scope = 'LK-TEST-' . bin2hex(random_bytes(6));
$opd = 'OPD-TEST';
$year = 2099;
$user = ['kd_wilayah' => $scope, 'kd_opd' => $opd, 'tahun' => $year, 'type_user' => 'admin_opd', 'username' => 'TEST_LAPORAN_KEUANGAN'];
$_SESSION['user'] = $user;
$files = [];
$assert = static function (bool $condition, string $message): void {
  if (!$condition) throw new RuntimeException('FAIL: ' . $message);
  echo "PASS: $message\n";
};

try {
  $service = new LaporanKeuanganService($user);
  $saved = $service->saveBalances([
    ['kode_akun' => '1.1.1.01', 'uraian' => 'Kas', 'kelompok' => 'ASET', 'saldo_awal' => 100, 'saldo_akhir' => 120],
    ['kode_akun' => '2.1.1.01', 'uraian' => 'Utang', 'kelompok' => 'KEWAJIBAN', 'saldo_awal' => 40, 'saldo_akhir' => 50],
    ['kode_akun' => '3.1.1.01', 'uraian' => 'Ekuitas', 'kelompok' => 'EKUITAS', 'saldo_awal' => 60, 'saldo_akhir' => 70],
  ]);
  $assert(count($saved['rows']) === 3 && $saved['summary']['seimbang'], 'saldo Neraca tersimpan terisolasi per wilayah/OPD/tahun dan seimbang');

  $budget = $db->query(
    "SELECT b.id,b.kd_wilayah,b.kd_opd,b.tahun,b.kd_sub_keg,b.kd_akun,b.uraian,b.jumlah
     FROM dpa_neo b
     WHERE b.setujui=1 AND b.kunci=1 AND b.is_deleted=0 AND b.jumlah>=500000
       AND NOT EXISTS (
         SELECT 1 FROM kontrak_item_neo ci
         WHERE ci.tahap='dpa' AND ci.anggaran_id=b.id AND ci.is_deleted=0
       )
     ORDER BY b.id LIMIT 1"
  )->fetch();
  if (!$budget) throw new RuntimeException('Tidak ada DPA disetujui dan terkunci dengan pagu tersedia untuk simulasi.');
  $simulationUser = [
    'kd_wilayah' => $budget['kd_wilayah'],
    'kd_opd' => $budget['kd_opd'],
    'tahun' => (int)$budget['tahun'],
    'type_user' => 'admin_opd',
    'username' => 'TEST_LAPORAN_KEUANGAN',
  ];
  $_SESSION['user'] = $simulationUser;
  $simulationService = new LaporanKeuanganService($simulationUser);
  $baseline = $simulationService->lraData();
  $budgetAmount = (float)$budget['jumlah'];
  $realizationAmount = 125000.0;
  $baseRow = array_values(array_filter($baseline['rows'], static fn($row) => $row['kd_sub_keg'] === $budget['kd_sub_keg'] && $row['kd_akun'] === $budget['kd_akun']))[0]
    ?? ['pagu' => 0.0, 'realisasi' => 0.0];
  $db->begin();
  try {
    $contractId = (int)$db->insert('kontrak_neo', [
      'kd_wilayah' => $budget['kd_wilayah'],
      'kd_opd' => $budget['kd_opd'],
      'tahun' => $budget['tahun'],
      'kd_sub_keg' => $budget['kd_sub_keg'],
      'anggaran_id' => $budget['id'],
      'tahap' => 'dpa',
      'total_anggaran' => $budgetAmount,
      'nilai_kontrak' => 200000,
      'nomor_kontrak' => 'SIM-LRA-' . bin2hex(random_bytes(4)),
      'tanggal_kontrak' => $budget['tahun'] . '-01-01',
      'uraian_kontrak' => 'Simulasi pekerjaan pemeliharaan',
      'status_kontrak' => 'aktif',
      'setujui' => 1,
      'is_deleted' => 0,
    ]);
    $db->insert('kontrak_item_neo', [
      'kontrak_id' => $contractId,
      'tahap' => 'dpa',
      'anggaran_id' => $budget['id'],
      'kd_wilayah' => $budget['kd_wilayah'],
      'kd_opd' => $budget['kd_opd'],
      'tahun' => $budget['tahun'],
      'kd_sub_keg' => $budget['kd_sub_keg'],
      'kd_akun' => $budget['kd_akun'],
      'uraian' => $budget['uraian'],
      'pagu' => $budgetAmount,
      'nilai_kontrak' => 200000,
      'username_insert' => 'TEST_LAPORAN_KEUANGAN',
      'is_deleted' => 0,
    ]);
    $contractService = new KontrakRealisasiService($simulationUser);
    $contractService->saveRealization([
      'contract_id' => $contractId,
      'tanggal' => $budget['tahun'] . '-03-15',
      'uraian_transaksi' => 'Pembayaran termin pertama (simulasi)',
      'keterangan' => 'Data simulasi, bukan transaksi sebenarnya.',
    ], [['tahap' => 'dpa', 'anggaran_id' => (int)$budget['id'], 'jumlah' => $realizationAmount, 'progress_fisik' => 40]]);
    $lra = $simulationService->lraData();
    $simulatedRow = array_values(array_filter($lra['rows'], static fn($row) => $row['kd_sub_keg'] === $budget['kd_sub_keg'] && $row['kd_akun'] === $budget['kd_akun']))[0] ?? null;
    $assert($simulatedRow !== null, 'input realisasi kontrak muncul pada baris LRA yang cocok');
    $assert($simulatedRow['pagu'] === $baseRow['pagu'] + $budgetAmount, 'pagu LRA bertambah sesuai pagu DPA dari item kontrak');
    $assert($simulatedRow['realisasi'] === $baseRow['realisasi'] + $realizationAmount, 'realisasi LRA bertambah persis sesuai transaksi input');
    $assert($simulatedRow['sisa'] === $simulatedRow['pagu'] - $simulatedRow['realisasi'] && $simulatedRow['persen'] === $simulatedRow['realisasi'] / $simulatedRow['pagu'] * 100, 'sisa dan persentase LRA terhitung sesuai pagu dan realisasi');
    echo 'SIMULASI LRA: ' . json_encode($simulatedRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  } finally {
    $db->rollback();
    $_SESSION['user'] = $user;
  }

  $template = $service->template();
  $files[] = $template;
  $book = PhpOffice\PhpSpreadsheet\IOFactory::load($template);
  $book->getSheetByName('Saldo Neraca')->fromArray([
    ['1.1.1.01', 'Kas', 'ASET', 100, 120, 'Buku besar'],
    ['2.1.1.01', 'Utang', 'KEWAJIBAN', 40, 50, 'Buku besar'],
    ['3.1.1.01', 'Ekuitas', 'EKUITAS', 60, 70, 'Buku besar'],
  ], null, 'A2', true);
  $importFile = tempnam(sys_get_temp_dir(), 'lkn-import-');
  if ($importFile === false) throw new RuntimeException('Tidak dapat menyiapkan file uji impor.');
  $files[] = $importFile;
  (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($importFile);
  $imported = $service->importBalances($importFile);
  $assert(count($imported['rows']) === 3 && $imported['summary']['seimbang'], 'impor template Excel mengganti saldo dengan validasi dan perhitungan Neraca');

  $notes = $service->saveNotes([['bagian' => 'umum', 'judul' => 'Informasi umum', 'uraian' => 'Narasi uji yang bersumber dari dokumen.']]);
  $assert(count($notes['rows']) === 1 && $notes['neraca']['seimbang'], 'catatan CaLK tersimpan dan menampilkan status Neraca');
  $xlsx = $service->export('neraca', 'xlsx');
  $files[] = $xlsx;
  $assert(PhpOffice\PhpSpreadsheet\IOFactory::load($xlsx)->getActiveSheet()->getCell('A1')->getValue() === 'KERTAS KERJA NERACA - TAHUN 2099', 'ekspor Neraca membaca data tersimpan');
  $assert(str_starts_with($service->export('calk', 'pdf'), '%PDF'), 'ekspor CaLK PDF berhasil');
} finally {
  $db->query('DELETE FROM laporan_neraca_saldo_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=?', [$scope, $opd, $year]);
  $db->query('DELETE FROM laporan_calk_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=?', [$scope, $opd, $year]);
  foreach ($files as $file) if (is_file($file)) unlink($file);
}

echo "LAPORAN KEUANGAN INTEGRATION TESTS COMPLETE\n";
