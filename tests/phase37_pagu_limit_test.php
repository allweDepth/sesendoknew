<?php

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/PaguLimitService.php';

$db = DB::getInstance();
$scope = $db->query("SELECT kd_wilayah,kd_opd,tahun,COALESCE(SUM(jumlah),0) total FROM renja_neo WHERE is_deleted=0 AND kd_opd IS NOT NULL AND kd_opd<>'' AND kd_opd<>'0' GROUP BY kd_wilayah,kd_opd,tahun LIMIT 1")->fetch();
if (!$scope) throw new RuntimeException('Data Renja untuk pengujian tidak tersedia.');

$db->begin();
try {
    $limit = (float)$scope['total'] + 100;
    $db->query('INSERT INTO batas_pagu_opd_neo(kd_wilayah,kd_opd,tahun,dokumen,pagu_maksimal,username_insert,is_deleted) VALUES(?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE pagu_maksimal=VALUES(pagu_maksimal),is_deleted=0',[$scope['kd_wilayah'],$scope['kd_opd'],$scope['tahun'],'renja',$limit,'PHASE37_TEST']);
    $service = new PaguLimitService($scope);
    $service->validate('renja_neo', ['jumlah'=>100] + $scope);
    try {
        $service->validate('renja_neo', ['jumlah'=>101] + $scope);
        throw new RuntimeException('Validasi seharusnya menolak pagu berlebih.');
    } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'melebihi batas OPD')) throw $e;
    }
    $db->rollback();
    echo "PHASE 37 PAGU LIMIT TEST PASSED\n";
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}
