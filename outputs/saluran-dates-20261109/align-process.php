<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../../app/Core/DB.php';
$db=DB::getInstance();
$early=['JADWAL_DOKUMEN','BA_SURVEY_HARGA','PAKTA_INTEGRITAS','UNDANGAN_PL'];
$dateFor=fn($code)=>in_array($code,$early,true)?'2026-11-05':'2026-11-06';
$expected=[820=>'CV. SURYA NUSANTARA',821=>'CV. ALIANSI',823=>'CV. SURYA NUSANTARA',825=>'CV. ALIANSI'];
$db->begin();
try{
 $cs=$db->query('SELECT * FROM kontrak_neo WHERE id IN(820,821,823,825) ORDER BY id FOR UPDATE')->fetchAll();
 $ds=$db->query('SELECT * FROM dokumen_pengadaan_neo WHERE kontrak_id IN(820,821,823,825) AND is_deleted=0 ORDER BY id FOR UPDATE')->fetchAll();
 if(count($cs)!==4)throw new RuntimeException('Missing contracts');
 foreach($cs as $c)if($c['nama_penyedia']!==$expected[$c['id']]||$c['tahun']!=2026||$c['is_deleted']!=0)throw new RuntimeException('Contract mismatch');
 foreach($ds as $d)if($d['status']!=='DRAFT')throw new RuntimeException('Document not draft');
 if(file_put_contents(__DIR__.'/process-before-'.date('Ymd-His').'.json',json_encode(['contracts'=>$cs,'documents'=>$ds],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
 $audit=['username_update'=>'SALURAN_PROCESS_20261109','tgl_update'=>date('Y-m-d H:i:s')];
 foreach($cs as $c){
  $p=json_decode($c['proses_pengadaan'],true,512,JSON_THROW_ON_ERROR);
  foreach($p as $code=>&$v)$v['tanggal']=$dateFor($code);unset($v);
  $duration=(new DateTimeImmutable('2026-11-06'))->diff(new DateTimeImmutable($c['tanggal_selesai']))->days+1;
  $changes=$audit+['proses_pengadaan'=>json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'tanggal_kontrak'=>'2026-11-06','tanggal_spmk'=>'2026-11-06','tanggal_mulai'=>'2026-11-06','waktu_pelaksanaan'=>$duration];
  $db->update('kontrak_neo',$changes,'WHERE id=?',[$c['id']]);
  $after=$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$c['id']])->fetch();
  foreach($after as $k=>$v)if($v!=($changes[$k]??$c[$k]))throw new RuntimeException('Contract verification '.$k);
 }
 foreach($ds as $d){
  $data=json_decode($d['data_isian'],true,512,JSON_THROW_ON_ERROR);
  if(isset($data['tanggal_proses']))$data['tanggal_proses']=$dateFor($d['kode_dokumen']);
  $changes=$audit+['tanggal_dokumen'=>$dateFor($d['kode_dokumen']),'data_isian'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
  $db->update('dokumen_pengadaan_neo',$changes,'WHERE id=?',[$d['id']]);
  $after=$db->query('SELECT * FROM dokumen_pengadaan_neo WHERE id=?',[$d['id']])->fetch();
  foreach($after as $k=>$v)if($v!==($changes[$k]??$d[$k]))throw new RuntimeException('Document verification '.$k);
 }
 $db->commit();
 $export=[];
 foreach(['kontrak_neo'=>'id IN(820,821,823,825)','dokumen_pengadaan_neo'=>'kontrak_id IN(820,821,823,825) AND is_deleted=0'] as $table=>$where){
  $export[$table]=['columns'=>array_column($db->query('DESCRIBE '.$table)->fetchAll(),'Field'),'rows'=>$db->query('SELECT * FROM '.$table.' WHERE '.$where.' ORDER BY id')->fetchAll()];
 }
 file_put_contents(__DIR__.'/process-after.json',json_encode($export,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 echo 'Verified '.count($cs).' contracts and '.count($ds).' documents. Export saved.'.PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
