<?php
/** Add source Excel headings and row order; preserve every existing financial row and ID. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Core/DB.php';$db=DB::getInstance();$plan=json_decode(file_get_contents($argv[1]??''),true,512,JSON_THROW_ON_ERROR);$apply=in_array('--apply',$argv,true);$actor='IMPORT_RAB_STRUCTURE_2026';$mapped=[];$notes=0;$items=0;
foreach($plan['contracts'] as $c){
 if(!hash_equals($c['sha256'],hash_file('sha256',$c['path'])))throw new RuntimeException('Excel changed');
 $h=$db->query("SELECT * FROM kontrak_neo WHERE id=? AND kd_wilayah='76.01' AND kd_opd='1.03.0.00.0.00.01.0000' AND tahun=2026 AND is_deleted=0",[$c['id']])->fetch();
 if(!$h||$h['status_kontrak']!=='draft'||$h['setujui']||$h['kunci'])throw new RuntimeException('Contract no longer draft');
 $existing=$db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0',[$c['id']])->fetchAll();$used=[];$rows=[];
 foreach($c['rows'] as $row){
  $found=null;foreach($existing as $r)if(!isset($used[$r['id']])&&$r['nomor']===$row['nomor']&&$r['uraian']===$row['uraian']&&(($r['type']==='KETERANGAN')===($row['type']==='KETERANGAN'))){$found=$r;$used[$r['id']]=true;break;}
  if($row['type']==='ITEM'){
   if(!$found||$found['satuan']!==$row['satuan'])throw new RuntimeException('Source item mismatch: '.$c['id'].' '.$row['nomor']);
   foreach(['jumlah_hps','jumlah_penawaran','jumlah_negoisasi'] as $key)if(abs((float)$found[$key]-(float)$row[$key])>.011)throw new RuntimeException('User/source amount differs; not overwritten');$items++;
  }else $notes++;
  $rows[]=[$row,$found];
 }
 if(count($used)!==count($existing))throw new RuntimeException('Unmapped existing row; not overwritten');
 $mapped[]=[$c,$h,$rows,$existing];
}
if(!$apply){echo json_encode(['status'=>'dry_run','packages'=>count($mapped),'items_preserved'=>$items,'source_notes'=>$notes]).PHP_EOL;exit;}
$db->begin();try{
 foreach($mapped as [$c,$h,$rows,$before]){
  foreach($rows as [$r,$old]){
   if($old){$db->update('rab_paket_neo',['urutan'=>$r['urutan']],'WHERE id=?',[$old['id']]);continue;}
   $db->insert('rab_paket_neo',['kontrak_id'=>$h['id'],'kontrak_item_id'=>$before[0]['kontrak_item_id'],'tahun'=>2026,'kd_wilayah'=>$h['kd_wilayah'],'kd_opd'=>$h['kd_opd'],'id_renja_p'=>0,'id_dpa'=>0,'id_dppa'=>$h['anggaran_id'],'nomor'=>$r['nomor'],'uraian'=>$r['uraian'],'satuan'=>'','type'=>'KETERANGAN','urutan'=>$r['urutan'],'bobot'=>0,'keterangan'=>mb_substr('Excel '.$c['sheet'].' baris '.$r['source_row'],0,255),'username_insert'=>$actor,'is_deleted'=>0]);
  }
  foreach($before as $old){$now=$db->query('SELECT * FROM rab_paket_neo WHERE id=?',[$old['id']])->fetch();foreach(['kontrak_id','nomor','uraian','satuan','vol_hps','vol_penawaran','vol_negoisasi','harga_sat_hps','harga_sat_penawaran','harga_sat_negoisasi','pajak','jumlah_hps','jumlah_penawaran','jumlah_negoisasi','bobot'] as $field)if($now[$field]!==$old[$field])throw new RuntimeException('Existing RAB data changed: '.$field);}
 }
 $db->commit();echo json_encode(['status'=>'applied','packages'=>count($mapped),'financial_items_preserved'=>$items,'source_notes'=>$notes,'financial_values_unchanged'=>true]).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
