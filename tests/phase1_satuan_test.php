<?php

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/DynamicTableService.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

ob_start();
$_SESSION['user'] = [
    'username' => 'TRACE_TEST', 'type_user' => 'super_admin',
    'kd_wilayah' => '76.01', 'kd_opd' => '1.03.0.00.0.00.01.0000', 'tahun' => 2026,
];

$service = new DynamicTableService();
$db = DB::getInstance();
$value = 'trace_unit';
$importValue = 'trace_import_unit';
$id = null;
$importFile = tempnam(sys_get_temp_dir(), 'phase1-satuan-') . '.xlsx';
$exitCode = 0;

function satuanCall(DynamicTableService $service, array $request): array
{
    unset($_SESSION['_last_request']);
    $response = json_decode($service->handle($request), true);
    if (!is_array($response)) throw new RuntimeException('Response bukan JSON valid');
    return $response;
}

function satuanAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}

try {
    $db->query("DELETE FROM satuan_neo WHERE value IN (?, ?)", [$value, $importValue]);

    $spreadsheet = IOFactory::load(__DIR__ . '/../public/assets/template_import/9. Satuan 1 Header.xlsx');
    $sheet = $spreadsheet->getSheetByName('Satuan');
    $sheet->setCellValue('A2', $importValue);
    $sheet->setCellValue('B2', 'TRACE IMPORT SATUAN');
    $sheet->setCellValue('C2', 'TRACE ALIAS');
    $sheet->setCellValue('D2', 'TRACE IMPORT');
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($importFile);
    $spreadsheet->disconnectWorksheets();

    $import = json_decode($service->importStrict('satuan', $importFile, 1), true);
    satuanAssert($import['success'] === true, 'IMPORT XLSX Satuan berhasil');
    satuanAssert(
        (int)($import['meta']['berhasil'] ?? 0) === 1,
        'IMPORT Satuan memproses tepat satu baris: ' . json_encode($import, JSON_UNESCAPED_UNICODE)
    );
    $imported = $db->query(
        "SELECT peraturan_id,is_deleted FROM satuan_neo WHERE value = ?",
        [$importValue]
    )->fetch();
    satuanAssert((int)($imported['peraturan_id'] ?? 0) === 4, 'IMPORT Satuan memakai peraturan aktif');
    satuanAssert((int)($imported['is_deleted'] ?? 1) === 0, 'hasil IMPORT Satuan aktif');

    $invalid = satuanCall($service, ['action' => 'add', 'tbl' => 'satuan', 'value' => $value]);
    satuanAssert($invalid['success'] === false, 'validasi required Satuan bekerja');

    $add = satuanCall($service, [
        'action' => 'add', 'tbl' => 'satuan',
        'value' => $value, 'uraian' => 'TRACE SATUAN', 'sebutan_lain' => 'TRACE',
        'keterangan' => 'TRACE TEST',
    ]);
    satuanAssert($add['success'] === true, 'ADD Satuan berhasil: ' . ($add['message'] ?? ''));
    $id = (int)($add['meta']['insert_id'] ?? 0);
    $stored = $db->query("SELECT peraturan_id,is_deleted FROM satuan_neo WHERE id = ?", [$id])->fetch();
    satuanAssert((int)($stored['peraturan_id'] ?? 0) === 4, 'ADD Satuan memakai peraturan aktif');
    satuanAssert((int)($stored['is_deleted'] ?? 1) === 0, 'default soft-delete Satuan aktif');

    $list = satuanCall($service, [
        'action' => 'list', 'tbl' => 'satuan', 'halaman' => 1, 'rows' => 5, 'cari' => $value,
    ]);
    satuanAssert($list['success'] === true && count($list['data']) === 1, 'READ/SEARCH/FILTER/PAGINATION Satuan berhasil');

    $edit = satuanCall($service, [
        'action' => 'edit', 'mode' => 'update', 'id_row' => $id, 'tbl' => 'satuan',
        'value' => $value, 'uraian' => 'TRACE SATUAN EDIT', 'sebutan_lain' => 'TRACE',
        'keterangan' => 'TRACE TEST EDIT',
    ]);
    satuanAssert($edit['success'] === true, 'EDIT Satuan berhasil');

    $export = satuanCall($service, ['action' => 'export', 'tbl' => 'satuan']);
    satuanAssert($export['success'] === true, 'EXPORT data Satuan berhasil');

    $delete = satuanCall($service, ['action' => 'delete', 'tbl' => 'satuan', 'id_row' => $id]);
    satuanAssert($delete['success'] === true, 'DELETE Satuan berhasil');
    $deleted = $db->query("SELECT is_deleted FROM satuan_neo WHERE id = ?", [$id])->fetch();
    satuanAssert($deleted !== false && (int)$deleted['is_deleted'] === 1, 'DELETE Satuan memakai soft delete');

    $hidden = satuanCall($service, [
        'action' => 'list', 'tbl' => 'satuan', 'halaman' => 1, 'rows' => 5, 'cari' => $value,
    ]);
    satuanAssert(count($hidden['data']) === 0, 'Satuan terhapus tidak muncul di listing');
    echo "RESULT: PASS\n";
} catch (Throwable $e) {
    fwrite(STDERR, "RESULT: FAIL - {$e->getMessage()}\n");
    $exitCode = 1;
} finally {
    $db->query("DELETE FROM satuan_neo WHERE value IN (?, ?)", [$value, $importValue]);
    if (is_file($importFile)) unlink($importFile);
}

exit($exitCode);
