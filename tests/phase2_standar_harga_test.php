<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/DynamicTableService.php';
require_once __DIR__ . '/../app/Services/StandarHargaService.php';

$_SESSION['user'] = [
    'username' => 'phase2_test',
    'type_user' => 'super_admin',
    'kd_wilayah' => '76.01',
    'tahun' => 2026
];

ob_start();

$db = DB::getInstance();
$ids = [];
$codes = [];
$testSettingId = 0;

function phase2Call(DynamicTableService $service, array $request): array
{
    return json_decode($service->handle($request), true) ?: [];
}

function phase2Assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo "PASS: {$message}\n";
}

try {
    $satuan = $db->query(
        "SELECT id, uraian FROM satuan_neo WHERE peraturan_id = 4 AND is_deleted = 0 LIMIT 1"
    )->fetch();
    phase2Assert((bool)$satuan, 'referensi satuan aktual tersedia');
    $aset = $db->query("SELECT kode FROM aset_neo WHERE disable = 0 AND is_deleted = 0 ORDER BY kode LIMIT 1")->fetch();
    phase2Assert((bool)$aset, 'referensi kode aset aktual tersedia');

    foreach (['ssh', 'hspk', 'asb', 'sbu'] as $type) {
        $service = new DynamicTableService();
        $code = 'TRACE_TEST_PHASE2_' . strtoupper($type);
        $codes[] = $code;
        $db->query("DELETE FROM master_biaya WHERE kode = ?", [$code]);

        $invalid = phase2Call($service, ['action' => 'add', 'tbl' => $type]);
        phase2Assert(($invalid['success'] ?? true) === false, "validasi required {$type} bekerja");

        $add = phase2Call($service, [
            'action' => 'add', 'tbl' => $type, 'kode' => $code,
            'kode_aset' => $aset['kode'], 'kelompok_barang' => 'TRACE_TEST',
            'uraian' => "TRACE TEST {$type}", 'spesifikasi' => 'Spesifikasi uji',
            'satuan_id' => $satuan['id'], 'harga' => 125000.50, 'tkdn' => 40
        ]);
        phase2Assert(($add['success'] ?? false) === true, "ADD {$type} berhasil");
        $id = (int)($add['meta']['insert_id'] ?? 0);
        $ids[] = $id;

        $stored = $db->query("SELECT * FROM master_biaya WHERE id = ?", [$id])->fetch();
        phase2Assert(($stored['tipe'] ?? '') === $type, "discriminator {$type} canonical");
        phase2Assert(($stored['kd_wilayah'] ?? '') === '76.01' && (int)$stored['tahun'] === 2026, "scope {$type} benar");
        phase2Assert((int)$stored['peraturan_id'] === 4, "peraturan {$type} benar");

        $list = phase2Call($service, [
            'action' => 'list', 'tbl' => $type, 'cari' => $code, 'halaman' => 1, 'rows' => 5
        ]);
        phase2Assert(($list['success'] ?? false) && count($list['data'] ?? []) === 1, "READ/SEARCH/FILTER {$type} berhasil");
        phase2Assert(($list['data'][0]['satuan'] ?? '') !== '', "JOIN satuan {$type} berhasil");

        $edit = phase2Call($service, [
            'action' => 'edit', 'mode' => 'update', 'id_row' => $id,
            'tbl' => $type, 'harga' => 150000.75
        ]);
        phase2Assert(($edit['success'] ?? false) === true, "EDIT {$type} berhasil");

        $export = phase2Call($service, ['action' => 'export', 'tbl' => $type]);
        phase2Assert(($export['success'] ?? false) === true, "EXPORT XLSX data {$type} berhasil");
    }

    $account = $db->query("SELECT kode FROM akun_neo WHERE is_deleted = 0 LIMIT 1")->fetch();
    $mappingService = new DynamicTableService();
    $map = phase2Call($mappingService, [
        'action' => 'add', 'tbl' => 'mapping', 'req' => 'ssh',
        'master_biaya_id' => $ids[0], 'kd_akun' => $account['kode']
    ]);
    phase2Assert(($map['success'] ?? false) === true, 'mapping akun master_biaya berhasil');

    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $spreadsheet->getActiveSheet()->fromArray([
        ['kode', 'kode_aset', 'uraian', 'spesifikasi', 'satuan', 'harga', 'tkdn'],
        ['TRACE_TEST_PHASE2_IMPORT', $aset['kode'], 'TRACE TEST IMPORT', 'Import XLSX', $satuan['uraian'], 99000, 25]
    ]);
    $importFile = tempnam(sys_get_temp_dir(), 'phase2_') . '.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($importFile);
    $importService = new DynamicTableService();
    $import = json_decode($importService->importStrict('ssh', $importFile, 1), true);
    unlink($importFile);
    phase2Assert(($import['success'] ?? false) === true && ($import['meta']['berhasil'] ?? 0) === 1, 'IMPORT XLSX SSH berhasil');
    $codes[] = 'TRACE_TEST_PHASE2_IMPORT';

    $standardService = new StandarHargaService($_SESSION['user']);
    $pdf = $standardService->exportPdf('ssh');
    phase2Assert(str_starts_with($pdf, '%PDF-'), 'EXPORT PDF SSH valid');

    $targetSetting = $db->query("SELECT * FROM pengaturan_neo WHERE id = 3")->fetch();
    unset($targetSetting['id']);
    $targetSetting['tahun'] = 2027;
    $targetSetting['keterangan'] = 'TRACE_TEST_PHASE2_COPY';
    $targetSetting['tgl_insert'] = date('Y-m-d H:i:s');
    $targetSetting['username_insert'] = 'phase2_test';
    $testSettingId = (int)$db->insert('pengaturan_neo', $targetSetting);

    $copy = $standardService->copyYear('ssh', 2027, [$ids[0]]);
    phase2Assert(($copy['copied'] ?? 0) >= 1, 'COPY tahun SSH berhasil dalam transaksi');

    foreach ($ids as $id) {
        $deleteType = $db->query("SELECT tipe FROM master_biaya WHERE id = ?", [$id])->fetch()['tipe'];
        $delete = phase2Call(new DynamicTableService(), ['action' => 'delete', 'tbl' => $deleteType, 'id_row' => $id]);
        phase2Assert(($delete['success'] ?? false) === true, "SOFT DELETE {$deleteType} berhasil");
    }

    echo "RESULT: PASS\n";
} finally {
    if ($codes) {
        $marks = implode(',', array_fill(0, count($codes), '?'));
        $rows = $db->query("SELECT id FROM master_biaya WHERE kode IN ($marks)", $codes)->fetchAll();
        foreach ($rows as $row) {
            $db->query("DELETE FROM master_biaya_akun WHERE master_biaya_id = ?", [$row['id']]);
        }
        $db->query("DELETE FROM master_biaya WHERE kode IN ($marks)", $codes);
    }
    if ($testSettingId > 0) {
        $db->query("DELETE FROM pengaturan_neo WHERE id = ? AND keterangan = ?", [$testSettingId, 'TRACE_TEST_PHASE2_COPY']);
    }
    ob_end_flush();
}
