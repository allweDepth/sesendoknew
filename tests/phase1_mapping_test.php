<?php

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/DynamicTableService.php';

ob_start();
$_SESSION['user'] = [
    'username' => 'TRACE_TEST', 'type_user' => 'super_admin',
    'kd_wilayah' => '76.01', 'kd_opd' => '1.03.0.00.0.00.01.0000', 'tahun' => 2026,
];

$service = new DynamicTableService();
$db = DB::getInstance();
$sshCode = 'TRACE-MAP-SSH';
$sbuCode = 'TRACE-MAP-SBU';
$sshId = null;
$sbuId = null;
$mappingId = null;
$exitCode = 0;

function mappingCall(DynamicTableService $service, array $request): array
{
    unset($_SESSION['_last_request']);
    $response = json_decode($service->handle($request), true);
    if (!is_array($response)) throw new RuntimeException('Response bukan JSON valid');
    return $response;
}

function mappingAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}

try {
    $oldIds = $db->query("SELECT id FROM master_biaya WHERE kode IN (?, ?)", [$sshCode, $sbuCode])->fetchAll();
    foreach ($oldIds as $old) {
        $db->query("DELETE FROM master_biaya_akun WHERE master_biaya_id = ?", [$old['id']]);
    }
    $db->query("DELETE FROM master_biaya WHERE kode IN (?, ?)", [$sshCode, $sbuCode]);

    $accounts = $db->query(
        "SELECT kode FROM akun_neo WHERE is_deleted = 0 ORDER BY id LIMIT 2"
    )->fetchAll();
    if (count($accounts) < 2) throw new RuntimeException('Dua akun aktif tidak tersedia untuk fixture Mapping');
    $accountA = $accounts[0]['kode'];
    $accountB = $accounts[1]['kode'];

    $db->insert('master_biaya', [
        'tipe' => 'ssh', 'kode' => $sshCode, 'uraian' => 'TRACE MAPPING SSH',
        'kd_wilayah' => '76.01', 'tahun' => 2026, 'peraturan_id' => 4,
        'username_insert' => 'TRACE_TEST',
    ]);
    $sshId = $db->lastInsertId();
    $db->insert('master_biaya', [
        'tipe' => 'sbu', 'kode' => $sbuCode, 'uraian' => 'TRACE MAPPING SBU',
        'kd_wilayah' => '76.01', 'tahun' => 2026, 'peraturan_id' => 4,
        'username_insert' => 'TRACE_TEST',
    ]);
    $sbuId = $db->lastInsertId();

    $invalid = mappingCall($service, ['action' => 'add', 'tbl' => 'mapping']);
    mappingAssert($invalid['success'] === false, 'validasi required Mapping bekerja');

    $add = mappingCall($service, [
        'action' => 'add', 'tbl' => 'mapping',
        'master_biaya_id' => $sshId, 'kd_akun' => $accountA,
    ]);
    mappingAssert($add['success'] === true, 'ADD Mapping berhasil: ' . ($add['message'] ?? ''));
    $mappingId = (int)($add['meta']['insert_id'] ?? 0);
    $stored = $db->query(
        "SELECT kd_wilayah,peraturan_id,is_deleted FROM master_biaya_akun WHERE id = ?",
        [$mappingId]
    )->fetch();
    mappingAssert(($stored['kd_wilayah'] ?? null) === '76.01', 'scope wilayah Mapping tersimpan');
    mappingAssert((int)($stored['peraturan_id'] ?? 0) === 4, 'Mapping memakai peraturan aktif');
    mappingAssert((int)($stored['is_deleted'] ?? 1) === 0, 'default soft-delete Mapping aktif');

    $db->insert('master_biaya_akun', [
        'master_biaya_id' => $sbuId, 'kd_akun' => $accountA,
        'kd_wilayah' => '76.01', 'peraturan_id' => 4,
        'username_insert' => 'TRACE_TEST',
    ]);

    $sshList = mappingCall($service, [
        'action' => 'list', 'tbl' => 'mapping', 'req' => 'ssh',
        'halaman' => 1, 'rows' => 5, 'cari' => $sshCode,
    ]);
    mappingAssert(
        $sshList['success'] === true && count($sshList['data']) === 1,
        'READ/SEARCH/PAGINATION Mapping SSH berhasil: ' . json_encode($sshList, JSON_UNESCAPED_UNICODE)
    );
    mappingAssert(($sshList['data'][0]['tipe'] ?? null) === 'ssh', 'FILTER submenu Mapping SSH bekerja');

    $sbuList = mappingCall($service, [
        'action' => 'list', 'tbl' => 'mapping', 'req' => 'sbu',
        'halaman' => 1, 'rows' => 5, 'cari' => $sbuCode,
    ]);
    mappingAssert($sbuList['success'] === true && count($sbuList['data']) === 1, 'FILTER submenu Mapping SBU bekerja');

    $edit = mappingCall($service, [
        'action' => 'edit', 'mode' => 'update', 'id_row' => $mappingId, 'tbl' => 'mapping',
        'master_biaya_id' => $sshId, 'kd_akun' => $accountB,
    ]);
    mappingAssert($edit['success'] === true, 'EDIT Mapping berhasil');

    $export = mappingCall($service, ['action' => 'export', 'tbl' => 'mapping', 'req' => 'ssh']);
    mappingAssert($export['success'] === true, 'EXPORT Mapping berhasil');

    $delete = mappingCall($service, ['action' => 'delete', 'tbl' => 'mapping', 'id_row' => $mappingId]);
    mappingAssert($delete['success'] === true, 'DELETE Mapping berhasil');
    $deleted = $db->query("SELECT is_deleted FROM master_biaya_akun WHERE id = ?", [$mappingId])->fetch();
    mappingAssert($deleted !== false && (int)$deleted['is_deleted'] === 1, 'DELETE Mapping memakai soft delete');

    $hidden = mappingCall($service, [
        'action' => 'list', 'tbl' => 'mapping', 'req' => 'ssh',
        'halaman' => 1, 'rows' => 5, 'cari' => $sshCode,
    ]);
    mappingAssert(count($hidden['data']) === 0, 'Mapping terhapus tidak muncul di listing');
    echo "RESULT: PASS\n";
} catch (Throwable $e) {
    fwrite(STDERR, "RESULT: FAIL - {$e->getMessage()}\n");
    $exitCode = 1;
} finally {
    foreach (array_filter([$sshId, $sbuId]) as $masterId) {
        $db->query("DELETE FROM master_biaya_akun WHERE master_biaya_id = ?", [$masterId]);
    }
    $db->query("DELETE FROM master_biaya WHERE kode IN (?, ?)", [$sshCode, $sbuCode]);
}

exit($exitCode);
