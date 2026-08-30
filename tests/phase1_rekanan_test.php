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
$name = 'TRACE_TEST REKANAN';
$importName = 'TRACE_IMPORT REKANAN';
$id = null;
$importFile = tempnam(sys_get_temp_dir(), 'phase1-rekanan-') . '.xlsx';
$exitCode = 0;

function rekananCall(DynamicTableService $service, array $request): array
{
    unset($_SESSION['_last_request']);
    $response = json_decode($service->handle($request), true);
    if (!is_array($response)) throw new RuntimeException('Response bukan JSON valid');
    return $response;
}

function rekananAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}

try {
    $db->query(
        "DELETE FROM rekanan_neo WHERE nama_perusahaan IN (?, ?, ?)",
        [$name, $importName, 'TRACE_TEST REKANAN IMPORT']
    );

    $template = __DIR__ . '/../public/assets/template_import/10. rekanan.xlsx';
    $spreadsheet = IOFactory::load($template);
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A2', $importName);
    $sheet->setCellValue('B2', 'TRACE_TEST ALAMAT IMPORT');
    $sheet->setCellValue('D2', 'TRACE-IMPORT');
    $sheet->setCellValue('H2', 'TRACE_TEST DIREKTUR IMPORT');
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($importFile);
    $spreadsheet->disconnectWorksheets();

    $import = json_decode($service->importStrict('rekanan', $importFile, 1), true);
    rekananAssert($import['success'] === true, 'IMPORT XLSX Rekanan berhasil');
    rekananAssert(
        (int)($import['meta']['berhasil'] ?? 0) === 1,
        'IMPORT Rekanan memproses tepat satu baris: ' . json_encode($import, JSON_UNESCAPED_UNICODE)
    );
    $imported = $db->query(
        "SELECT kd_wilayah,is_deleted FROM rekanan_neo WHERE nama_perusahaan = ?",
        [$importName]
    )->fetch();
    rekananAssert(($imported['kd_wilayah'] ?? null) === '76.01', 'scope wilayah hasil IMPORT Rekanan benar');
    rekananAssert((int)($imported['is_deleted'] ?? 1) === 0, 'hasil IMPORT Rekanan aktif');

    $invalid = rekananCall($service, ['action' => 'add', 'tbl' => 'rekanan', 'nama_perusahaan' => $name]);
    rekananAssert($invalid['success'] === false, 'validasi required Rekanan bekerja');

    $add = rekananCall($service, [
        'action' => 'add', 'tbl' => 'rekanan',
        'nama_perusahaan' => $name, 'alamat' => 'TRACE_TEST ALAMAT',
        'npwp' => 'TRACE.TEST.NPWP', 'direktur' => 'TRACE_TEST DIREKTUR',
    ]);
    rekananAssert($add['success'] === true, 'ADD Rekanan berhasil: ' . ($add['message'] ?? ''));
    $id = (int)($add['meta']['insert_id'] ?? 0);
    $stored = $db->query("SELECT kd_wilayah,is_deleted FROM rekanan_neo WHERE id = ?", [$id])->fetch();
    rekananAssert(($stored['kd_wilayah'] ?? null) === '76.01', 'scope wilayah Rekanan tersimpan');
    rekananAssert((int)($stored['is_deleted'] ?? 1) === 0, 'default soft-delete Rekanan aktif');

    $list = rekananCall($service, [
        'action' => 'list', 'tbl' => 'rekanan', 'halaman' => 1, 'rows' => 5, 'cari' => $name,
    ]);
    rekananAssert($list['success'] === true && count($list['data']) === 1, 'READ/SEARCH/PAGINATION Rekanan berhasil');

    $edit = rekananCall($service, [
        'action' => 'edit', 'mode' => 'update', 'id_row' => $id, 'tbl' => 'rekanan',
        'nama_perusahaan' => $name, 'alamat' => 'TRACE_TEST ALAMAT EDIT',
        'npwp' => 'TRACE.TEST.NPWP', 'direktur' => 'TRACE_TEST DIREKTUR',
    ]);
    rekananAssert($edit['success'] === true, 'EDIT Rekanan berhasil');

    $export = rekananCall($service, ['action' => 'export', 'tbl' => 'rekanan']);
    rekananAssert($export['success'] === true, 'EXPORT data Rekanan berhasil');

    $delete = rekananCall($service, ['action' => 'delete', 'tbl' => 'rekanan', 'id_row' => $id]);
    rekananAssert($delete['success'] === true, 'DELETE Rekanan berhasil');
    $deleted = $db->query("SELECT is_deleted FROM rekanan_neo WHERE id = ?", [$id])->fetch();
    rekananAssert($deleted !== false && (int)$deleted['is_deleted'] === 1, 'DELETE Rekanan memakai soft delete');

    $hidden = rekananCall($service, [
        'action' => 'list', 'tbl' => 'rekanan', 'halaman' => 1, 'rows' => 5, 'cari' => $name,
    ]);
    rekananAssert(count($hidden['data']) === 0, 'Rekanan terhapus tidak muncul di listing');
    echo "RESULT: PASS\n";
} catch (Throwable $e) {
    fwrite(STDERR, "RESULT: FAIL - {$e->getMessage()}\n");
    $exitCode = 1;
} finally {
    $db->query(
        "DELETE FROM rekanan_neo WHERE nama_perusahaan IN (?, ?, ?)",
        [$name, $importName, 'TRACE_TEST REKANAN IMPORT']
    );
    if (is_file($importFile)) unlink($importFile);
}

exit($exitCode);
