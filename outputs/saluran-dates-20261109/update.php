<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../../app/Core/DB.php';
$db=DB::getInstance();
$names=[820=>'Normalisasi Sungai/Saluran Bulumario',821=>'Normalisasi Sungai/Saluran Desa Saptanajaya',823=>'Normalisasi Sungai/Saluran Desa Tirtabuana',825=>'Normalisasi Sungai/Saluran Desa Polewali'];
$actor='SALURAN_DATES_20261109';
$db->begin();
try {
 $before=[]; $docs=[];
 foreach($names as $id=>$name){
  $c=$db->query('SELECT * FROM kontrak_neo WHERE id=? AND tahun=2026 AND is_deleted=0 FOR UPDATE',[$id])->fetch();
  if(!$c || $c['uraian_kontrak']!==$name)throw new RuntimeException('Package mismatch');
  $before[$id]=$c;
  $docs[$id]=$db->query('SELECT * FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0 FOR UPDATE',[$id])->fetchAll();
 }
 $backup=__DIR__.'/before-'.date('Ymd-His').'.json';
 if(file_put_contents($backup,json_encode(['contracts'=>$before,'documents'=>$docs],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
 $result=[];
 foreach($before as $id=>$c){
  $process=json_decode($c['proses_pengadaan'],true,512,JSON_THROW_ON_ERROR);
  $process['UNDANGAN_PL']['tanggal']='2026-11-05';
  $process['UPLOAD_PENAWARAN']['tanggal']='2026-11-06';
  $changes=['tanggal_kontrak'=>'2026-11-06','proses_pengadaan'=>json_encode($process,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')];
  $db->update('kontrak_neo',$changes,'WHERE id=?',[$id]);
  foreach($docs[$id] as $d){
   $code=$d['kode_dokumen'];
   $date=$code==='UNDANGAN_PL'?'2026-11-05':(($code==='UPLOAD_PENAWARAN'||$code==='KONTRAK'||$code==='SURAT_PERJANJIAN'||str_starts_with($code,'SPK_'))?'2026-11-06':null);
   if($date===null)continue;
   if($d['status']!=='DRAFT')throw new RuntimeException('Document is not a draft');
   $db->update('dokumen_pengadaan_neo',['tanggal_dokumen'=>$date,'username_update'=>$actor,'tgl_update'=>$changes['tgl_update']],'WHERE id=?',[$d['id']]);
  }
  $after=$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$id])->fetch();
  foreach($c as $key=>$value)if(!array_key_exists($key,$changes)&&$after[$key]!==$value)throw new RuntimeException('Unexpected change: '.$key);
  foreach($changes as $key=>$value)if($after[$key]!==$value)throw new RuntimeException('Verification failed');
  foreach($docs[$id] as $d){
   $a=$db->query('SELECT * FROM dokumen_pengadaan_neo WHERE id=?',[$d['id']])->fetch();
   foreach($d as $key=>$value)if(!in_array($key,['tanggal_dokumen','username_update','tgl_update'],true)&&$a[$key]!==$value)throw new RuntimeException('Unexpected document change');
   $code=$d['kode_dokumen'];
   $expected=$code==='UNDANGAN_PL'?'2026-11-05':(($code==='UPLOAD_PENAWARAN'||$code==='KONTRAK'||$code==='SURAT_PERJANJIAN'||str_starts_with($code,'SPK_'))?'2026-11-06':$d['tanggal_dokumen']);
   if($a['tanggal_dokumen']!==$expected)throw new RuntimeException('Document date verification failed');
  }
  $result[]=['id'=>$id,'paket'=>$c['uraian_kontrak'],'perusahaan'=>$after['nama_penyedia'],'undangan'=>$process['UNDANGAN_PL']['tanggal'],'pemasukan'=>$process['UPLOAD_PENAWARAN']['tanggal'],'kontrak'=>$after['tanggal_kontrak']];
 }
 $db->commit();
 file_put_contents(__DIR__.'/result.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch(Throwable $e){$db->rollback();throw $e;}
