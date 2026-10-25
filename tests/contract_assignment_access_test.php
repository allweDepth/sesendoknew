<?php
require_once __DIR__.'/../app/Services/ContractOfferPrintService.php';
$db=DB::getInstance();$c=$db->query('SELECT * FROM kontrak_neo WHERE is_deleted=0 AND tahun=2026 AND kd_sub_keg IS NOT NULL ORDER BY id DESC LIMIT 1')->fetch();if(!$c)throw new RuntimeException('Contract fixture required');$uid=(int)$db->query('SELECT id FROM user_sesendok_biila ORDER BY id LIMIT 1')->fetch()['id'];$session=$_SESSION??[];$u=['id'=>$uid,'type_user'=>'ppk','username'=>'QA_ASSIGNMENT','kd_wilayah'=>$c['kd_wilayah'],'kd_opd'=>$c['kd_opd'],'tahun'=>$c['tahun']];$_SESSION['user']=$u;$db->begin();
function blockedAssignment(callable $call):void{$blocked=false;try{$call();}catch(RuntimeException$e){$blocked=true;}if(!$blocked)throw new RuntimeException('Unassigned access accepted');}
try{
 $db->query('DELETE FROM user_subkegiatan_neo WHERE user_id=?',[$uid]);$s=new KontrakRealisasiService($u);$offer=new ContractOfferPrintService($u);blockedAssignment(fn()=>$s->delivery((int)$c['id']));blockedAssignment(fn()=>(new ProcurementDocumentService($u))->templates((int)$c['id']));
 if((int)$s->summary()['totals']['jumlah_kontrak']!==0||$s->reportRows()||$s->financialRows()||$s->realizationContracts()||$s->availableSubActivities())throw new RuntimeException('Unassigned totals/list leaked');
 $id=$db->insert('user_subkegiatan_neo',['user_id'=>$uid,'kd_wilayah'=>$c['kd_wilayah'],'kd_opd'=>$c['kd_opd'],'tahun'=>$c['tahun'],'kd_sub_keg'=>$c['kd_sub_keg'],'peran'=>'PPK','dapat_lihat'=>1,'dapat_input'=>0,'berlaku_mulai'=>'2026-01-01','berlaku_sampai'=>'2026-12-31','is_deleted'=>0]);
 $s->delivery((int)$c['id']);$offer->get((int)$c['id']);blockedAssignment(fn()=>$s->saveRab((int)$c['id'],[]));blockedAssignment(fn()=>$offer->save((int)$c['id'],['nomor_surat'=>'QA','tanggal_surat'=>'2026-10-26','masa_berlaku'=>20]));
 foreach($s->reportRows()as$r)if($r['kd_sub_keg']!==$c['kd_sub_keg'])throw new RuntimeException('Unassigned contract in report');
 $db->update('user_subkegiatan_neo',['dapat_input'=>1],'WHERE id=?',[$id]);$offer->save((int)$c['id'],['nomor_surat'=>'QA','tanggal_surat'=>'2026-10-26','masa_berlaku'=>20]);
 echo "PASS: Unassigned detail/exports denied; read-only assignment cannot write; input assignment works\n";
}finally{$db->rollback();$_SESSION=$session;}
