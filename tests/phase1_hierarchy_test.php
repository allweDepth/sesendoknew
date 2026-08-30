<?php

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/DynamicTableService.php';

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
$levels = [
    'urusan' => ['kode' => 'TRACEH', 'parent' => null],
    'bidang' => ['kode' => 'TRACEH.01', 'parent' => 'TRACEH'],
    'program' => ['kode' => 'TRACEH.01.01', 'parent' => 'TRACEH.01'],
    'kegiatan' => ['kode' => 'TRACEH.01.01.01.01', 'parent' => 'TRACEH.01.01'],
    'sub_kegiatan' => ['kode' => 'TRACEH.01.01.01.01.01', 'parent' => 'TRACEH.01.01.01.01'],
];
$ids = [];

function hierarchyCall(DynamicTableService $service, array $request): array
{
    unset($_SESSION['_last_request']);
    $response = json_decode($service->handle($request), true);
    if (!is_array($response)) throw new RuntimeException('Response bukan JSON valid');
    return $response;
}

function hierarchyAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}

function hierarchyDropdownCall(DynamicTableService $service, string $source, array $params): array
{
    $request = array_merge(['action' => 'dropdown', 'tbl' => $source], $params);
    $_POST = $request;
    $response = json_decode($service->handle($request), true);
    $_POST = [];
    if (!is_array($response)) throw new RuntimeException('Response dropdown bukan JSON valid');
    return $response;
}

function hierarchyDropdownHasValue(array $response, string $value): bool
{
    foreach ($response['data'] ?? [] as $option) {
        if ((string)($option['value'] ?? '') === $value) return true;
    }
    return false;
}

try {
    $db->query("DELETE FROM rekening_kegiatan WHERE kode LIKE 'TRACEH%' ORDER BY LENGTH(kode) DESC");

    foreach ($levels as $level => $config) {
        $request = [
            'action' => 'add',
            'tbl' => 'rekening_kegiatan',
            'req' => $level,
            'kode' => $config['kode'],
            'uraian' => 'TRACE_TEST ' . strtoupper($level),
        ];
        if ($config['parent'] !== null) $request['parent_kode'] = $config['parent'];
        if ($level === 'sub_kegiatan') $request['satuan'] = 'Dokumen';

        $add = hierarchyCall($service, $request);
        hierarchyAssert($add['success'] === true, "ADD {$level} berhasil: " . ($add['message'] ?? ''));
        $ids[$level] = (int)($add['meta']['insert_id'] ?? 0);

        $stored = $db->query("SELECT kode,parent_kode,level,status FROM rekening_kegiatan WHERE id = ?", [$ids[$level]])->fetch();
        hierarchyAssert(($stored['level'] ?? null) === $level, "{$level} menyimpan level canonical");
        hierarchyAssert(($stored['parent_kode'] ?? null) === $config['parent'], "{$level} menyimpan parent canonical");

        $list = hierarchyCall($service, [
            'action' => 'list', 'tbl' => 'rekening_kegiatan', 'req' => $level,
            'halaman' => 1, 'rows' => 10, 'cari' => $config['kode'],
        ]);
        hierarchyAssert($list['success'] === true && count($list['data']) === 1, "READ/SEARCH {$level} terisolasi");

        $editRequest = $request;
        $editRequest['action'] = 'edit';
        $editRequest['mode'] = 'update';
        $editRequest['id_row'] = $ids[$level];
        $editRequest['uraian'] = 'TRACE_TEST ' . strtoupper($level) . ' EDIT';
        $edit = hierarchyCall($service, $editRequest);
        hierarchyAssert($edit['success'] === true, "EDIT {$level} berhasil");
    }

    $parentLevels = [
        'bidang' => 'urusan',
        'program' => 'bidang',
        'kegiatan' => 'program',
        'sub_kegiatan' => 'kegiatan',
    ];
    foreach ($parentLevels as $childLevel => $parentLevel) {
        $parentCode = $levels[$childLevel]['parent'];
        $dropdown = hierarchyDropdownCall($service, 'rekening_kegiatan', [
            'mode' => 'edit',
            'value' => $parentCode,
            'filters' => json_encode(['level' => $parentLevel]),
            'limit' => 20,
        ]);
        hierarchyAssert(
            $dropdown['success'] === true && hierarchyDropdownHasValue($dropdown, $parentCode),
            "dropdown parent terpilih tampil saat EDIT {$childLevel}"
        );
    }

    $satuanDropdown = hierarchyDropdownCall($service, 'satuan_teks', [
        'mode' => 'edit', 'value' => 'Dokumen', 'limit' => 20,
    ]);
    hierarchyAssert(
        $satuanDropdown['success'] === true && hierarchyDropdownHasValue($satuanDropdown, 'Dokumen'),
        'dropdown Satuan terpilih tampil saat EDIT sub_kegiatan'
    );

    $blocked = hierarchyCall($service, [
        'action' => 'delete', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'id_row' => $ids['urusan'],
    ]);
    hierarchyAssert($blocked['success'] === false, 'DELETE parent dengan child aktif ditolak');

    $_SESSION['user']['type_user'] = 'viewer';
    $viewerService = new DynamicTableService();
    $denied = hierarchyCall($viewerService, [
        'action' => 'add', 'tbl' => 'rekening_kegiatan', 'req' => 'urusan',
        'kode' => 'TRACEH_DENIED', 'uraian' => 'TRACE_TEST DENIED',
    ]);
    hierarchyAssert($denied['success'] === false, 'permission viewer menolak ADD');
    $_SESSION['user']['type_user'] = 'super_admin';

    foreach (array_reverse(array_keys($levels)) as $level) {
        $delete = hierarchyCall($service, [
            'action' => 'delete', 'tbl' => 'rekening_kegiatan', 'req' => $level,
            'id_row' => $ids[$level],
        ]);
        hierarchyAssert($delete['success'] === true, "DELETE {$level} berhasil dari child ke parent");
    }

    echo "RESULT: PASS\n";
} catch (Throwable $e) {
    fwrite(STDERR, "RESULT: FAIL - {$e->getMessage()}\n");
    exit(1);
} finally {
    $db->query("DELETE FROM rekening_kegiatan WHERE kode LIKE 'TRACEH%' ORDER BY LENGTH(kode) DESC");
}
