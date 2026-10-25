<?php
/** Apply the user's subsequent supervision template to imported amendment drafts only. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Core/DB.php';
$plan=json_decode(file_get_contents($argv[1]??''),true,512,JSON_THROW_ON_ERROR);$apply=in_array('--apply',$argv,true);$db=DB::getInstance();$actor='IMPORT_PERUBAHAN_PUPR_2026';
if(hash_file('sha256',$plan['supervision_template'])!==$plan['supervision_template_sha256'])throw new RuntimeException('Contoh pengawasan berubah');
$mapped=[];$rowCount=0;
foreach($plan['contracts'] as $c){
 if(empty($c['rab_template']))continue;
 $header=$db->query('SELECT * FROM kontrak_neo WHERE username_insert=? AND keterangan LIKE ? AND is_deleted=0',[$actor,'Excel row '.$c['excel_row'].';%'])->fetch();
 if(!$header||$header['status_kontrak']!=='draft'||$header['setujui']||$header['kunci']||$header['nomor_kontrak']!==$c['nomor_kontrak'])throw new RuntimeException('Draft telah berubah; tidak ditimpa');
 $edited=$db->query('SELECT COUNT(*) n FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 AND COALESCE(username_update,?)<>?',[$header['id'],$actor,$actor])->fetch();if($edited['n'])throw new RuntimeException('RAB telah disunting pengguna');
 $items=$db->query('SELECT * FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY id',[$header['id']])->fetchAll();
 if(count($items)!==1)throw new RuntimeException('Tautan anggaran tidak sesuai manifest');
 $sum=0.;foreach($c['rab'] as $r){
  foreach(['hps','penawaran','negoisasi'] as $key)if(abs(round((float)$r['vol_'.$key]*(float)$r['harga_sat_'.$key]*(1+(float)$r['pajak']/100),2)-(float)$r['jumlah_'.$key])>.01)throw new RuntimeException('Perhitungan baris RAB tidak cocok');
  $sum+=(float)$r['jumlah_negoisasi'];
 }
 if(abs($sum-(float)$c['nilai_kontrak'])>.01||(float)$c['nilai_hps']>(float)$header['total_anggaran']||$sum>=(float)$c['nilai_hps'])throw new RuntimeException('Nilai RAB melebihi pagu atau selisih tidak valid');
 $mapped[]=[$c,$header,$items[0]];$rowCount+=count($c['rab']);
}
if(count($mapped)!==11)throw new RuntimeException('Jumlah paket pengawasan tidak cocok');
if(!$apply){echo json_encode(['status'=>'dry_run','contracts'=>count($mapped),'rab_rows'=>$rowCount]).PHP_EOL;exit;}
$db->begin();try{
 foreach($mapped as [$c,$header,$item]){
  $id=$header['id'];
  $db->query('UPDATE rab_paket_neo SET is_deleted=1,username_update=?,tgl_update=NOW() WHERE kontrak_id=? AND is_deleted=0',[$actor,$id]);
  $db->query('UPDATE kontrak_item_neo SET nilai_kontrak=?,username_update=?,tgl_update=NOW() WHERE id=?',[$c['nilai_kontrak'],$actor,$item['id']]);
  $db->query('UPDATE kontrak_neo SET nilai_hps=?,nilai_kontrak=?,keterangan=?,username_update=?,tgl_update=NOW() WHERE id=?',[$c['nilai_hps'],$c['nilai_kontrak'],mb_substr('Excel row '.$c['excel_row'].'; DRAFT; RUP acak sementara, bukan RUP resmi; HPS/penawaran estimasi proporsional sample pengawasan / awas; kuantitas contoh, durasi final belum ditetapkan.',0,400),$actor,$id]);
  foreach($c['rab'] as $r){
   $r+=['kontrak_id'=>$id,'kontrak_item_id'=>$item['id'],'tahun'=>$header['tahun'],'kd_wilayah'=>$header['kd_wilayah'],'kd_opd'=>$header['kd_opd'],'id_renja_p'=>0,'id_dpa'=>0,'id_dppa'=>$item['anggaran_id'],'type'=>'DRAFT RAB pengawasan','bobot'=>round((float)$r['jumlah_negoisasi']/(float)$c['nilai_kontrak']*100,4),'keterangan'=>mb_substr('Draft proporsional '.$c['rab_template'].'; '.$c['rab_note'],0,255),'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0];
   $db->insert('rab_paket_neo',$r);
  }
  $docs=$db->query('SELECT id,data_isian FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND username_insert=? AND status=? AND is_deleted=0',[$id,$actor,'DRAFT'])->fetchAll();
  foreach($docs as $doc){$meta=json_decode($doc['data_isian'],true);$meta['nilai_status']='ESTIMASI_PROPORSIONAL';$meta['sumber_hps']=$plan['supervision_template'];$meta['sumber_hps_sha256']=$plan['supervision_template_sha256'];$db->query('UPDATE dokumen_pengadaan_neo SET data_isian=?,username_update=?,tgl_update=NOW() WHERE id=?',[json_encode($meta,JSON_UNESCAPED_UNICODE),$actor,$doc['id']]);}
 }
 $db->commit();echo json_encode(['status'=>'applied','contracts'=>count($mapped),'rab_rows'=>$rowCount]).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
