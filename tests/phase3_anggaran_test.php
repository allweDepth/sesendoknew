<?php
require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Services/AnggaranCopyService.php';

$db = DB::getInstance();
$assert = static function (bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
    echo "PASS: $message\n";
};

foreach (['rkpd_neo','renja_neo','rka_neo','dpa_neo','rkpd_p_neo','renja_p_neo','rka_p_neo','dppa_neo'] as $table) {
    $count = (int)$db->query("SELECT COUNT(*) total FROM `$table` WHERE keterangan='TRACE_TEST Phase 3' AND is_deleted=0")->fetch()['total'];
    $assert($count > 0, "$table memiliki data TRACE_TEST");
}

$renja = $db->query("SELECT source_id FROM renja_neo WHERE keterangan='TRACE_TEST Phase 3' LIMIT 1")->fetch();
$assert(!empty($renja['source_id']), 'Renja memiliki provenance RKPD');
$assert((int)$db->query("SELECT COUNT(*) total FROM master_biaya_akun mba JOIN master_biaya mb ON mb.id=mba.master_biaya_id WHERE mb.kode='TRACE_TEST.P3.001' AND mba.is_deleted=0")->fetch()['total'] > 0, 'Standar harga Phase 2 terhubung ke akun');

$user = ['type_user'=>'super_admin','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'username'=>'phase3_test'];
$result = (new AnggaranCopyService($user))->copy('rkpd', 'renja', 2026);
$assert($result['skipped'] > 0 && $result['copied'] === 0, 'Workflow idempoten dan tidak menduplikasi dokumen');

$rejected = false;
try { (new AnggaranCopyService($user))->copy('rkpd', 'dpa', 2026); } catch (InvalidArgumentException $e) { $rejected = true; }
$assert($rejected, 'Workflow menolak lompatan tahap');
echo "PHASE 3 TESTS COMPLETE\n";
