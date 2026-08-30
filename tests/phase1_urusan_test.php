<?php

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/DynamicTableService.php';
require_once __DIR__ . '/../vendor/autoload.php';

ob_start();

$_SESSION['user'] = [
    'username' => 'TRACE_TEST',
    'type_user' => 'super_admin',
    'kd_wilayah' => '76.01',
    'kd_opd' => '1.03.0.00.0.00.01.0000',
    'tahun' => 2026,
];

$service = new DynamicTableService();
$db = DB::getInstance();
$code = 'TRACE_TEST_URUSAN';
$importCode = 'TRACE_TEST_IMPORT';
$id = null;
$importFile = sys_get_temp_dir() . '/sesendok_trace_urusan_import.xlsx';

function callService(DynamicTableService $service, array $request): array
{
    $response = json_decode($service->handle($request), true);
    if (!is_array($response)) {
        throw new RuntimeException('Response bukan JSON valid');
    }
    return $response;
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

try {
    $db->query("DELETE FROM rekening_kegiatan WHERE kode = ?", [$code]);
    $db->query("DELETE FROM rekening_kegiatan WHERE kode = ?", [$importCode]);

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
        __DIR__ . '/../public/assets/template_import/11. Referensi Hierarki.xlsx'
    );
    $importSheet = $spreadsheet->getSheetByName('Import Referensi');
    $importSheet->setCellValue('A2', $importCode);
    $importSheet->setCellValue('B2', 'TRACE_TEST URUSAN IMPORT');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($importFile);

    $import = json_decode($service->importStrict('rekening_kegiatan', $importFile, 1), true);
    assertTrue($import['success'] === true, 'IMPORT XLSX Urusan berhasil');
    assertTrue((int)($import['meta']['berhasil'] ?? 0) === 1, 'IMPORT mencatat satu baris berhasil');
    $imported = $db->query("SELECT level, kd_wilayah, peraturan_id FROM rekening_kegiatan WHERE kode = ?", [$importCode])->fetch();
    assertTrue(($imported['level'] ?? null) === 'urusan', 'IMPORT mendeteksi level Urusan');
    assertTrue(($imported['kd_wilayah'] ?? null) === '76.01', 'IMPORT menerapkan scope wilayah');
    assertTrue((int)($imported['peraturan_id'] ?? 0) === 4, 'IMPORT menerapkan peraturan aktif');

    $list = callService($service, [
        'action' => 'list', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'halaman' => 1, 'rows' => 5,
    ]);
    assertTrue($list['success'] === true, 'READ Urusan berhasil');
    assertTrue(($list['meta']['limit'] ?? null) === 5, 'pagination menghormati rows=5');
    assertTrue(count($list['data']) <= 5, 'jumlah row tidak melewati page size');
    assertTrue(array_reduce($list['data'], fn($ok, $row) => $ok && !isset($row['level']), true), 'select mode hanya mengekspos kolom Urusan');

    unset($_SESSION['_last_request']);
    $invalid = callService($service, [
        'action' => 'add', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'kode' => $code,
    ]);
    assertTrue($invalid['success'] === false, 'validasi required menolak uraian kosong');

    unset($_SESSION['_last_request']);
    $add = callService($service, [
        'action' => 'add', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'kode' => $code, 'uraian' => 'TRACE_TEST URUSAN AWAL',
    ]);
    assertTrue($add['success'] === true, 'ADD Urusan berhasil: ' . ($add['message'] ?? 'tanpa pesan'));
    $id = (int)($add['meta']['insert_id'] ?? 0);
    assertTrue($id > 0, 'ADD mengembalikan insert_id');

    $row = $db->query("SELECT * FROM rekening_kegiatan WHERE id = ?", [$id])->fetch();
    assertTrue(($row['level'] ?? null) === 'urusan', 'level otomatis canonical urusan');
    assertTrue(($row['parent_kode'] ?? null) === null, 'Urusan tidak mempunyai parent');
    assertTrue(($row['kd_wilayah'] ?? null) === '76.01', 'scope kd_wilayah terisi dari session');
    assertTrue((int)($row['peraturan_id'] ?? 0) === 4, 'peraturan aktif terisi dari pengaturan');

    $search = callService($service, [
        'action' => 'list', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'halaman' => 1, 'rows' => 10, 'cari' => $code,
    ]);
    assertTrue(
        $search['success'] === true && count($search['data']) === 1,
        'SEARCH menemukan TRACE_TEST Urusan (rows=' . count($search['data']) . ', total=' . ($search['meta']['total'] ?? 'n/a') . ')'
    );

    unset($_SESSION['_last_request']);
    $edit = callService($service, [
        'action' => 'edit', 'mode' => 'update', 'id_row' => $id,
        'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'kode' => $code, 'uraian' => 'TRACE_TEST URUSAN DIUBAH',
    ]);
    assertTrue($edit['success'] === true, 'EDIT Urusan berhasil');
    $updated = $db->query("SELECT uraian FROM rekening_kegiatan WHERE id = ?", [$id])->fetch();
    assertTrue(($updated['uraian'] ?? null) === 'TRACE_TEST URUSAN DIUBAH', 'EDIT tersimpan di database');

    $export = callService($service, [
        'action' => 'export', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
    ]);
    assertTrue($export['success'] === true, 'EXPORT data Urusan berhasil');

    $delete = callService($service, [
        'action' => 'delete', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'id_row' => $id,
    ]);
    assertTrue($delete['success'] === true, 'DELETE Urusan berhasil');
    $deleted = $db->query("SELECT status FROM rekening_kegiatan WHERE id = ?", [$id])->fetch();
    assertTrue($deleted !== false && (int)$deleted['status'] === 0, 'DELETE menggunakan soft delete status=0');

    $afterDelete = callService($service, [
        'action' => 'list', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'halaman' => 1, 'rows' => 10, 'cari' => $code,
    ]);
    assertTrue(count($afterDelete['data']) === 0, 'soft-deleted Urusan tidak muncul pada listing');

    echo "RESULT: PASS\n";
} catch (Throwable $e) {
    fwrite(STDERR, "RESULT: FAIL - {$e->getMessage()}\n");
    exit(1);
} finally {
    $db->query("DELETE FROM rekening_kegiatan WHERE kode = ?", [$code]);
    $db->query("DELETE FROM rekening_kegiatan WHERE kode = ?", [$importCode]);
    if (is_file($importFile)) unlink($importFile);
}
