<?php
require_once __DIR__.'/../app/Core/DB.php';
$_SESSION['user']=['username'=>'AUDIT_PHASE50','type_user'=>'super_admin','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026];
require_once __DIR__.'/../app/Services/KontrakRealisasiService.php';
$db=DB::getInstance();
$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
$rows=$db->query("SELECT k.id kontrak_id,k.setujui,ci.id item_id,ci.anggaran_id,ci.tahap,ci.nilai_kontrak,ci.pagu FROM kontrak_neo k JOIN kontrak_item_neo ci ON ci.kontrak_id=k.id AND ci.is_deleted=0 WHERE k.kd_wilayah='76.01' AND k.tahun=2026 AND k.is_deleted=0 AND k.setujui=0 GROUP BY k.id HAVING COUNT(ci.id)=1 ORDER BY k.id LIMIT 2")->fetchAll();
$ok(count($rows)===2,'tersedia dua kontrak draft satu uraian untuk uji rollback');
$a=$rows[0];$b=$rows[1];$table=$a['tahap']==='dppa'?'dppa_neo':'dpa_neo';
$budget=$db->query("SELECT jumlah,kd_sub_keg,kd_akun,uraian FROM `$table` WHERE id=?",[$a['anggaran_id']])->fetch();
$db->begin();
try{
    $db->query('UPDATE kontrak_neo SET setujui=1 WHERE id=?',[$a['kontrak_id']]);
    $available=(new KontrakRealisasiService($_SESSION['user']))->availableItems('',(int)$b['kontrak_id'],(string)$budget['kd_sub_keg'],100);
    $partial=array_values(array_filter($available,fn($row)=>$row['tahap']===$a['tahap']&&(int)$row['anggaran_id']===(int)$a['anggaran_id']));
    $ok(count($partial)===1&&abs((float)$partial[0]['pagu_tersedia']-((float)$budget['jumlah']-(float)$a['nilai_kontrak']))<0.01,'uraian terpakai sebagian tetap tersedia bagi kontrak berikutnya');
    $floorBlocked=false;
    try{$db->query("UPDATE `$table` SET jumlah=? WHERE id=?",[(float)$a['nilai_kontrak']-1,$a['anggaran_id']]);}catch(Throwable $e){$floorBlocked=str_contains($e->getMessage(),'tidak boleh lebih kecil');}
    $ok($floorBlocked,'pagu tidak dapat diturunkan di bawah kontrak yang disetujui');

    $db->query('UPDATE kontrak_item_neo SET tahap=?,anggaran_id=?,kd_sub_keg=?,kd_akun=?,uraian=?,pagu=?,nilai_kontrak=? WHERE id=?',[$a['tahap'],$a['anggaran_id'],$budget['kd_sub_keg'],$budget['kd_akun'],$budget['uraian'],$budget['jumlah'],$budget['jumlah'],$b['item_id']]);
    $db->query('UPDATE kontrak_neo SET tahap=?,anggaran_id=?,nilai_kontrak=?,total_anggaran=? WHERE id=?',[$a['tahap'],$a['anggaran_id'],$budget['jumlah'],$budget['jumlah'],$b['kontrak_id']]);
    $approvalBlocked=false;
    try{$db->query('UPDATE kontrak_neo SET setujui=1 WHERE id=?',[$b['kontrak_id']]);}catch(Throwable $e){$approvalBlocked=str_contains($e->getMessage(),'akumulasi kontrak');}
    $ok($approvalBlocked,'persetujuan kontrak ditolak bila akumulasi melampaui pagu uraian');
}finally{$db->rollback();}
echo "PHASE 50 APPROVED CONTRACT BUDGET GUARDS COMPLETE\n";
