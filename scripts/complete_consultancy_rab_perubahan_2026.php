<?php
/** Add/update source-backed draft RAB; never relax contract/provider validation. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Core/DB.php';$db=DB::getInstance();$plan=json_decode(file_get_contents($argv[1]??''),true,512,JSON_THROW_ON_ERROR);$apply=in_array('--apply',$argv,true);$actor='IMPORT_PERUBAHAN_PUPR_2026';$mapped=[];$pending=[];$rows=0;
foreach($plan['contracts'] as $c){
 if(!str_starts_with($c['jenis_pengadaan'],'KONSULTANSI')||(float)$c['budget']>100000000)continue;
 $h=$db->query("SELECT * FROM kontrak_neo WHERE username_insert=? AND keterangan LIKE ? AND tahun=2026 AND kd_wilayah='76.01' AND kd_opd='1.03.0.00.0.00.01.0000' AND is_deleted=0",[$actor,'Excel row '.$c['excel_row'].';%'])->fetch();
 if(!$h||$h['status_kontrak']!=='draft'||$h['setujui']||$h['kunci']||abs((float)$h['total_anggaran']-(float)$c['budget'])>.01)throw new RuntimeException('Draft berubah atau di luar scope');
 if(!in_array($h['username_update'],[null,$actor,'PUPR_OFFICIALS_2026'],true))throw new RuntimeException('Kontrak telah diedit pengguna; tidak ditimpa');
 $existing=$db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY CAST(nomor AS UNSIGNED),id',[$h['id']])->fetchAll();
 foreach($existing as $r)if($r['username_insert']!==$actor||!in_array($r['username_update'],[null,$actor],true))throw new RuntimeException('RAB telah diedit pengguna; tidak ditimpa');
 if($existing&&count($existing)!==count($c['rab']))throw new RuntimeException('Susunan RAB berubah');
 $gross=array_sum(array_column($c['rab'],'jumlah_negoisasi'));if(abs($gross-(float)$c['nilai_kontrak'])>.01||$gross>(float)$h['total_anggaran'])throw new RuntimeException('Nilai RAB tidak cocok');
 $items=$db->query('SELECT * FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0',[$h['id']])->fetchAll();if(count($items)!==1)throw new RuntimeException('Tautan anggaran bukan satu uraian');
 if(!$h['rekanan_id'])$pending[]=$h['id'];$mapped[]=[$c,$h,$existing,$items[0]];$rows+=count($c['rab']);
}
if(count($mapped)!==51)throw new RuntimeException('Jumlah paket konsultansi tidak cocok');
if(!$apply){echo json_encode(['status'=>'dry_run','contracts'=>count($mapped),'rows'=>$rows,'pending_provider'=>$pending]).PHP_EOL;exit;}
$db->begin();try{
 foreach($mapped as [$c,$h,$existing,$item]){
  foreach($c['rab'] as $i=>$r){
   $r+=['kontrak_id'=>$h['id'],'kontrak_item_id'=>$item['id'],'tahun'=>2026,'kd_wilayah'=>$h['kd_wilayah'],'kd_opd'=>$h['kd_opd'],'id_renja_p'=>0,'id_dpa'=>0,'id_dppa'=>$item['anggaran_id'],'type'=>str_contains(strtolower($c['package']),'pengawasan')?'DRAFT RAB pengawasan':'DRAFT RAB perencanaan','bobot'=>round((float)$r['jumlah_negoisasi']/(float)$c['nilai_kontrak']*100,4),'keterangan'=>mb_substr($c['rab_template'].'; '.$c['rab_note'],0,255),'is_deleted'=>0];
   if(isset($existing[$i])){$r['username_update']=$actor;$r['tgl_update']=date('Y-m-d H:i:s');$db->update('rab_paket_neo',$r,'WHERE id=?',[$existing[$i]['id']]);}
   else{$r['username_insert']=$actor;$r['tgl_insert']=date('Y-m-d H:i:s');$db->insert('rab_paket_neo',$r);}
  }
  // Incomplete-provider drafts retain their unapproved header; their RAB remains
  // visible as an estimate. No trigger bypass or fabricated provider is used.
  if($h['rekanan_id']){
   $db->query('UPDATE kontrak_item_neo SET nilai_kontrak=?,username_update=?,tgl_update=NOW() WHERE id=?',[$c['nilai_kontrak'],$actor,$item['id']]);
   $db->query('UPDATE kontrak_neo SET nilai_hps=?,nilai_kontrak=?,username_update=?,tgl_update=NOW() WHERE id=?',[$c['nilai_hps'],$c['nilai_kontrak'],$actor,$h['id']]);
  }
 }
 $db->commit();echo json_encode(['status'=>'applied','contracts'=>count($mapped),'rows'=>$rows,'headers_waiting_provider'=>$pending]).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
