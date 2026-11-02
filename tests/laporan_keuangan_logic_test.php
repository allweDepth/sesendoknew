<?php
require_once __DIR__ . '/../app/Services/LaporanKeuanganService.php';
require_once __DIR__ . '/../app/Core/RequestGuard.php';

$assert = static function (bool $condition, string $message): void {
  if (!$condition) throw new RuntimeException('FAIL: ' . $message);
  echo "PASS: $message\n";
};

$assert(LaporanKeuanganService::groupForAccount('1.1.1.01') === 'ASET', 'kode rekening kelas 1 dikelompokkan sebagai Aset');
$assert(LaporanKeuanganService::groupForAccount('2.1.01') === 'KEWAJIBAN', 'kode rekening kelas 2 dikelompokkan sebagai Kewajiban');
$assert(LaporanKeuanganService::groupForAccount('3.1.01') === 'EKUITAS', 'kode rekening kelas 3 dikelompokkan sebagai Ekuitas');
$invalidCodeRejected = false;
try {
  LaporanKeuanganService::groupForAccount('5.1.02');
} catch (InvalidArgumentException) {
  $invalidCodeRejected = true;
}
$assert($invalidCodeRejected, 'rekening di luar kelas Neraca ditolak');

$summary = LaporanKeuanganService::balanceSummary([
  ['kelompok' => 'ASET', 'saldo_awal' => 100, 'saldo_akhir' => 120],
  ['kelompok' => 'KEWAJIBAN', 'saldo_awal' => 40, 'saldo_akhir' => 50],
  ['kelompok' => 'EKUITAS', 'saldo_awal' => 60, 'saldo_akhir' => 70],
]);
$assert($summary['seimbang'] === true && $summary['selisih_awal'] === 0.0 && $summary['selisih_akhir'] === 0.0, 'Neraca seimbang dihitung dari Aset = Kewajiban + Ekuitas');
$unbalanced = LaporanKeuanganService::balanceSummary([
  ['kelompok' => 'ASET', 'saldo_awal' => 100, 'saldo_akhir' => 100],
  ['kelompok' => 'EKUITAS', 'saldo_awal' => 90, 'saldo_akhir' => 90],
]);
$assert($unbalanced['seimbang'] === false && $unbalanced['selisih_akhir'] === 10.0, 'Neraca tidak seimbang terdeteksi dan tidak dilabeli siap');

$service = (new ReflectionClass(LaporanKeuanganService::class))->newInstanceWithoutConstructor();
$template = $service->template();
$templateBook = PhpOffice\PhpSpreadsheet\IOFactory::load($template);
$assert($templateBook->getSheetByName('Saldo Neraca')?->getCell('A1')->getValue() === 'KODE_REKENING', 'template Excel memiliki sheet dan header impor yang ditetapkan');
unlink($template);

$summary = LaporanKeuanganService::balanceSummary([
  ['kelompok' => 'ASET', 'saldo_awal' => 100, 'saldo_akhir' => 120],
  ['kelompok' => 'KEWAJIBAN', 'saldo_awal' => 40, 'saldo_akhir' => 50],
  ['kelompok' => 'EKUITAS', 'saldo_awal' => 60, 'saldo_akhir' => 70],
]);
$neracaExport = new ReflectionMethod(LaporanKeuanganService::class, 'exportExcel');
$neracaFile = $neracaExport->invoke($service, 'neraca', ['tahun' => 2026, 'rows' => [
  ['kode_akun' => '1.1.1.01', 'uraian' => '=1+1', 'kelompok' => 'ASET', 'saldo_awal' => 100, 'saldo_akhir' => 120, 'catatan' => ''],
], 'summary' => $summary]);
$neracaSheet = PhpOffice\PhpSpreadsheet\IOFactory::load($neracaFile)->getActiveSheet();
$assert($neracaSheet->getCell('A1')->getValue() === 'KERTAS KERJA NERACA - TAHUN 2026', 'ekspor Neraca memakai label kertas kerja dan tahun laporan');
$assert($neracaSheet->getCell('A2')->getValue() === 'STATUS: SEIMBANG', 'ekspor Neraca menampilkan status validasi keseimbangan');
$assert($neracaSheet->getCell('B4')->getValue() === "'=1+1", 'teks CaLK/uraian tidak diekspor sebagai formula Excel');
unlink($neracaFile);
$pdfExport = new ReflectionMethod(LaporanKeuanganService::class, 'exportPdf');
$pdf = $pdfExport->invoke($service, 'neraca', ['tahun' => 2026, 'rows' => [], 'summary' => $summary]);
$assert(str_starts_with($pdf, '%PDF'), 'ekspor PDF Neraca valid');

$assert(RequestGuard::requiresPost(['LaporanKeuanganController', 'saveBalances']), 'perubahan saldo wajib melalui POST dan CSRF');
$assert(RequestGuard::requiresPost(['LaporanKeuanganController', 'saveNotes']), 'perubahan CaLK wajib melalui POST dan CSRF');
$assert(RequestGuard::requiresPost(['LaporanKeuanganController', 'importBalances']), 'impor saldo wajib melalui POST dan CSRF');
$assert(!RequestGuard::requiresPost(['LaporanKeuanganController', 'data']), 'pembacaan laporan tetap GET');

$sidebar = file_get_contents(__DIR__ . '/../app/Views/partials/sidebar.php');
$assert(str_contains($sidebar, "['LRA', '/laporan-keuangan?laporan=lra'") && str_contains($sidebar, "['Neraca', '/laporan-keuangan?laporan=neraca'") && str_contains($sidebar, "['CaLK', '/laporan-keuangan?laporan=calk'"), 'menu memuat LRA, Neraca, dan CaLK');
echo "LAPORAN KEUANGAN LOGIC TESTS COMPLETE\n";
