<?php
require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/LaporanKeuanganService.php';
require_once __DIR__ . '/../app/Services/RealisasiKeuanganService.php';

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
  $baselineMatches = array_values(array_filter($baseline['rows'], static fn($row) => $row['kd_sub_keg'] === $budget['kd_sub_keg'] && $row['kd_akun'] === $budget['kd_akun']));
  $baseRow = $baselineMatches[0] ?? null;
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
    $progressResult = $contractService->saveRealization([
      'contract_id' => $contractId,
      'tanggal' => $budget['tahun'] . '-03-15',
      'uraian_transaksi' => 'Progres pekerjaan belum dibayar (simulasi)',
      'keterangan' => 'Data simulasi, bukan transaksi sebenarnya.',
    ], [['tahap' => 'dpa', 'anggaran_id' => (int)$budget['id'], 'jumlah' => $realizationAmount, 'progress_fisik' => 40]]);
    $assert(isset($contractService->summary()['totals']['realisasi']), 'ringkasan kontrak tetap dapat dimuat dengan metadata transaksi keuangan');
    $assert(count(array_filter($contractService->realizationContracts(), static fn($row) => (int)$row['id'] === $contractId)) === 1, 'pemilihan kontrak untuk input realisasi tetap tersedia');
    $lra = $simulationService->lraData();
    $simulatedRow = array_values(array_filter($lra['rows'], static fn($row) => $row['kd_sub_keg'] === $budget['kd_sub_keg'] && $row['kd_akun'] === $budget['kd_akun']))[0] ?? null;
    $assert(($simulatedRow === null && $baseRow === null) || ($simulatedRow !== null && $baseRow !== null && $simulatedRow['pagu'] === $baseRow['pagu']), 'pagu LRA tidak dibuat dari nilai kontrak atau baris DPA mentah');
    $assert(($simulatedRow === null && $baseRow === null) || ($simulatedRow !== null && $baseRow !== null && $simulatedRow['realisasi'] === $baseRow['realisasi']), 'progres tanpa pembayaran dan verifikasi tidak menambah belanja LRA');
    $assert($lra['full_lkpd'] === false && $lra['status'] === 'BELUM_LENGKAP', 'LRA menampilkan batas cakupan dan status sumber yang belum lengkap');
    $assert(!RealisasiKeuanganService::lraEligible(['jenis_transaksi' => 'PROGRES', 'status_verifikasi' => 'TERVERIFIKASI']), 'progres kontrak tetap tidak memenuhi syarat belanja LRA');
    $assert(RealisasiKeuanganService::lraEligible(['jenis_transaksi' => 'LS', 'status_verifikasi' => 'TERVERIFIKASI', 'nomor_sp2d' => 'SP2D-1', 'tanggal_sp2d' => $budget['tahun'] . '-03-15']), 'LS terverifikasi dengan bukti SP2D memenuhi syarat sumber LRA');
    $assert(RealisasiKeuanganService::lraEligible(['jenis_transaksi' => 'UP', 'status_verifikasi' => 'TERVERIFIKASI', 'nomor_spj' => 'SPJ-1', 'tanggal_pengesahan_spj' => $budget['tahun'] . '-03-15', 'nomor_sp2d_sumber' => 'SP2D-UP-1', 'nomor_up_tu' => 'UP-1']), 'UP/TU hanya memenuhi syarat LRA jika SPJ disahkan dan terhubung ke sumber dana');
    $assert(!RealisasiKeuanganService::lraEligible(['jenis_transaksi' => 'GU', 'status_verifikasi' => 'TERVERIFIKASI', 'nomor_sp2d' => 'SP2D-GU-1']), 'SP2D GU tidak dihitung sebagai belanja LRA kedua');
    $transactionUuid = $progressResult['transaksi_uuid'];
    $realizationId = (int)$db->query('SELECT MIN(id) id FROM daftar_realisasi_neo WHERE transaksi_uuid=? AND kontrak_id=? AND is_deleted=0', [$transactionUuid, $contractId])->fetch()['id'];
    $db->insert('realisasi_dokumen_neo', [
      'transaksi_uuid' => $transactionUuid,
      'kontrak_id' => $contractId,
      'nama_file_asli' => 'simulasi-sp2d.pdf',
      'path_file' => 'storage/simulasi-sp2d.pdf',
      'mime_type' => 'application/pdf',
      'ukuran' => 1,
      'kd_wilayah' => $budget['kd_wilayah'],
      'kd_opd' => $budget['kd_opd'],
      'tahun' => $budget['tahun'],
      'username_insert' => 'TEST_LAPORAN_KEUANGAN',
      'is_deleted' => 0,
    ]);
    $db->insert('realisasi_dokumen_neo', [
      'transaksi_uuid' => $transactionUuid,
      'kontrak_id' => $contractId,
      'nama_file_asli' => 'simulasi-bukti-tambahan.pdf',
      'path_file' => 'storage/simulasi-bukti-tambahan.pdf',
      'mime_type' => 'application/pdf',
      'ukuran' => 1,
      'kd_wilayah' => $budget['kd_wilayah'],
      'kd_opd' => $budget['kd_opd'],
      'tahun' => $budget['tahun'],
      'username_insert' => 'TEST_LAPORAN_KEUANGAN',
      'is_deleted' => 0,
    ]);
    $contractService->saveRealization([
      'realization_id' => $realizationId,
      'tanggal' => $budget['tahun'] . '-03-15',
      'uraian_transaksi' => 'Pembayaran LS (simulasi)',
      'keterangan' => 'Data simulasi, bukan transaksi sebenarnya.',
      'keuangan' => [
        'jenis_transaksi' => 'LS',
        'nomor_bukti' => 'SIM-BUKTI-LS',
        'nomor_sp2d' => 'SIM-SP2D-LS',
        'tanggal_sp2d' => $budget['tahun'] . '-03-15',
        'nilai_potongan_uang_muka' => 0,
      ],
    ], [['tahap' => 'dpa', 'anggaran_id' => (int)$budget['id'], 'jumlah' => $realizationAmount, 'nilai_lra' => $realizationAmount, 'progress_fisik' => 40]]);
    $selfVerificationRejected = false;
    try {
      (new RealisasiKeuanganService($simulationUser))->verify($transactionUuid);
    } catch (RuntimeException $error) {
      $selfVerificationRejected = str_contains($error->getMessage(), 'pengguna lain');
    }
    $assert($selfVerificationRejected, 'penginput transaksi tidak dapat memverifikasi transaksinya sendiri');
    $reviewer = $simulationUser;
    $reviewer['username'] = 'TEST_LAPORAN_KEUANGAN_REVIEWER';
    $_SESSION['user'] = $reviewer;
    (new RealisasiKeuanganService($reviewer))->verify($transactionUuid);
    $_SESSION['user'] = $simulationUser;
    $verifiedLra = $simulationService->lraData();
    $verifiedRow = array_values(array_filter($verifiedLra['rows'], static fn($row) => $row['kd_sub_keg'] === $budget['kd_sub_keg'] && $row['kd_akun'] === $budget['kd_akun']))[0] ?? null;
    $beforeRealization = (float)($baseRow['realisasi'] ?? 0);
    $trackedTransaction = array_values(array_filter($verifiedLra['transactions'], static fn($row) => $row['transaksi_uuid'] === $transactionUuid))[0] ?? null;
    $assert($trackedTransaction !== null && (float)$trackedTransaction['jumlah'] === $realizationAmount && (int)$trackedTransaction['jumlah_dokumen'] === 2, 'banyak dokumen sumber tidak menggandakan nilai transaksi dalam antrean');
    if ($baseline['budget_published']) {
      $assert($verifiedRow !== null && $verifiedRow['realisasi'] === $beforeRealization + $realizationAmount, 'nilai LS per rekening muncul di LRA setelah SP2D, dokumen, verifikasi, dan snapshot pagu disahkan lengkap');
    } else {
      $assert($verifiedRow === null && $trackedTransaction !== null && $trackedTransaction['status_verifikasi'] === 'TERVERIFIKASI', 'transaksi LS terverifikasi tetap dapat ditelusuri tetapi tidak diterbitkan sebagai LRA tanpa snapshot pagu disahkan');
    }
    $snapshotDate = min(date('Y-m-d'), $budget['tahun'] . '-12-31');
    $approvalId = (int)$db->insert('anggaran_pengesahan_neo', [
      'kd_wilayah' => $budget['kd_wilayah'],
      'kd_opd' => $budget['kd_opd'],
      'tahun' => $budget['tahun'],
      'jenis' => 'DPA',
      'urutan' => 999,
      'nomor' => 'SIM-LRA-' . bin2hex(random_bytes(4)),
      'tanggal' => $snapshotDate,
      'dasar_hukum' => 'Snapshot pengujian yang akan di-rollback.',
      'status' => 'DISAHKAN',
      'username_insert' => 'TEST_LAPORAN_KEUANGAN',
    ]);
    $db->insert('anggaran_pengesahan_rincian_neo', [
      'pengesahan_id' => $approvalId,
      'tahap' => 'dpa',
      'anggaran_id' => $budget['id'],
      'kd_sub_keg' => $budget['kd_sub_keg'],
      'kd_akun' => $budget['kd_akun'],
      'uraian' => $budget['uraian'],
      'pagu' => $budgetAmount,
    ]);
    $publishedLra = $simulationService->lraData();
    $publishedRow = array_values(array_filter($publishedLra['rows'], static fn($row) => $row['kd_sub_keg'] === $budget['kd_sub_keg'] && $row['kd_akun'] === $budget['kd_akun']))[0] ?? null;
    $assert($publishedLra['budget_published'] && $publishedRow !== null && $publishedRow['pagu'] === $budgetAmount && $publishedRow['realisasi'] === $beforeRealization + $realizationAmount, 'snapshot DPA yang disahkan dan transaksi LS terverifikasi membentuk pagu serta realisasi LRA tanpa menambah pagu dari kontrak');
    echo 'SIMULASI TRANSAKSI: ' . json_encode($trackedTransaction, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
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
  $lraExport = $service->export('lra', 'xlsx');
  $files[] = $lraExport;
  $assert(PhpOffice\PhpSpreadsheet\IOFactory::load($lraExport)->getActiveSheet()->getCell('A1')->getValue() === 'KERTAS KERJA LRA - TAHUN 2099', 'ekspor LRA memakai kertas kerja terverifikasi yang sama dengan layar');
  $assert(str_starts_with($service->export('calk', 'pdf'), '%PDF'), 'ekspor CaLK PDF berhasil');
} finally {
  $db->query('DELETE FROM laporan_neraca_saldo_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=?', [$scope, $opd, $year]);
  $db->query('DELETE FROM laporan_calk_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=?', [$scope, $opd, $year]);
  foreach ($files as $file) if (is_file($file)) unlink($file);
}

echo "LAPORAN KEUANGAN INTEGRATION TESTS COMPLETE\n";
