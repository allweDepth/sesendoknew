<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../../app/Core/DB.php';
$db=DB::getInstance();
$db->begin();
try {
 $rows=$db->query('SELECT * FROM kontrak_neo WHERE id IN (820,821,823,825) ORDER BY id FOR UPDATE')->fetchAll();
 $contracts=array_column($rows,null,'id');
 $expected=[820=>['Normalisasi Sungai/Saluran Bulumario','CV. SURYA NUSANTARA',820],821=>['Normalisasi Sungai/Saluran Desa Saptanajaya','CV. ALIANSI',821],823=>['Normalisasi Sungai/Saluran Desa Tirtabuana','CV. SURYA NUSANTARA',820],825=>['Normalisasi Sungai/Saluran Desa Polewali','CV. ALIANSI',821]];
 if(count($contracts)!==4)throw new RuntimeException('Missing contracts');
 foreach($expected as $id=>[$name,$company,$source]){
  $c=$contracts[$id];
  if($c['uraian_kontrak']!==$name||(int)$c['tahun']!==2026||(int)$c['is_deleted']!==0)throw new RuntimeException('Contract mismatch');
  $r=$db->query('SELECT id,nama_perusahaan FROM rekanan_neo WHERE id=? AND is_deleted=0',[$contracts[$source]['rekanan_id']])->fetch();
  if(!$r||$r['nama_perusahaan']!==$company||$contracts[$source]['nama_penyedia']!==$company)throw new RuntimeException('Vendor mismatch');
 }
 if(file_put_contents(__DIR__.'/vendors-before-'.date('Ymd-His').'.json',json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
 foreach([823,825] as $id){
  [$name,$company,$source]=$expected[$id];
  $db->update('kontrak_neo',['rekanan_id'=>$contracts[$source]['rekanan_id'],'nama_penyedia'=>$company,'username_update'=>'SALURAN_VENDOR_20261109','tgl_update'=>date('Y-m-d H:i:s')],'WHERE id=?',[$id]);
 }
 $result=[];
 foreach($expected as $id=>[$name,$company,$source]){
  $after=$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$id])->fetch();
  if($after['nama_penyedia']!==$company||$after['rekanan_id']!==$contracts[$source]['rekanan_id'])throw new RuntimeException('Vendor verification failed');
  foreach($contracts[$id] as $k=>$v)if(!in_array($k,['rekanan_id','nama_penyedia','username_update','tgl_update'],true)&&$after[$k]!==$v)throw new RuntimeException('Unexpected change '.$k);
  $p=json_decode($after['proses_pengadaan'],true,512,JSON_THROW_ON_ERROR);
  if($after['tanggal_kontrak']!=='2026-11-06'||$p['UNDANGAN_PL']['tanggal']!=='2026-11-05'||$p['UPLOAD_PENAWARAN']['tanggal']!=='2026-11-06')throw new RuntimeException('Date verification failed');
  $result[]=['id'=>$id,'paket'=>$name,'perusahaan'=>$company,'undangan'=>$p['UNDANGAN_PL']['tanggal'],'pemasukan'=>$p['UPLOAD_PENAWARAN']['tanggal'],'kontrak'=>$after['tanggal_kontrak']];
 }
 $db->commit();
 file_put_contents(__DIR__.'/vendors-result.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
