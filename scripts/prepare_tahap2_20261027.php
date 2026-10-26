<?php
/** Apply the explicitly marked TAHAP 2 rows after PDF/DPPA reconciliation. */
if(PHP_SAPI!=='cli')exit;
chdir(dirname(__DIR__));require 'app/Core/DB.php';require 'app/Services/ProcurementProcessService.php';
$db=DB::getInstance();$plan=json_decode(file_get_contents('outputs/tahap2-20261027/rencana.json'),true,512,JSON_THROW_ON_ERROR);$scope=['76.01','1.03.0.00.0.00.01.0000',2026];$actor='TAHAP2_20261027';if(in_array('--existing-only',$argv,true))$plan['ready']=array_values(array_filter($plan['ready'],fn($p)=>!empty($p['contract_id'])));
$selected=[];foreach($argv as$arg)if(str_starts_with($arg,'--contracts='))$selected=array_map('intval',explode(',',substr($arg,12)));if($selected)$plan['ready']=array_values(array_filter($plan['ready'],fn($p)=>in_array((int)$p['contract_id'],$selected,true)));
$base=$db->query('SELECT * FROM kontrak_neo WHERE id=859')->fetch();$baseRab=$db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=859 AND is_deleted=0 ORDER BY urutan,id')->fetchAll();
$strip=function($r)use($actor){unset($r['id']);$r['username_insert']=$actor;$r['tgl_insert']=date('Y-m-d H:i:s');$r['username_update']=null;$r['tgl_update']=null;return$r;};
$report=[];$counts=[];$db->begin();try{foreach($plan['ready']as$p){
 $d=$db->query('SELECT * FROM dppa_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$p['dppa_id'],...$scope])->fetch();if(!$d||$d['uraian']!==$p['package']||abs((float)$d['jumlah']-$p['budget'])>.001)throw new RuntimeException('DPPA changed');
 $v=$db->query('SELECT * FROM rekanan_neo WHERE id=? AND kd_wilayah=? AND is_deleted=0',[$p['vendor_id'],$scope[0]])->fetch();if(!$v||!trim($v['direktur']))throw new RuntimeException('Company identity incomplete');
 $id=$p['contract_id'];$new=false;
 if(!$id){$prior=$db->query('SELECT id FROM kontrak_neo WHERE anggaran_id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$d['id'],...$scope])->fetch();if($prior)$id=$prior['id'];else{
  if((float)$db->query("SELECT COALESCE(SUM(pagu),0) n FROM kontrak_item_neo WHERE tahap='dppa' AND anggaran_id=? AND is_deleted=0",[$d['id']])->fetch()['n']>0)throw new RuntimeException('DPPA already allocated');
  $new=true;$rup=(string)random_int(60000000,69999999);while($db->query('SELECT id FROM kontrak_neo WHERE nomor_rup=? AND is_deleted=0',[$rup])->fetch())$rup=(string)random_int(60000000,69999999);
  $c=$strip($base);$c=array_replace($c,['uraian_kontrak'=>$d['uraian'],'anggaran_id'=>$d['id'],'kd_sub_keg'=>$d['kd_sub_keg'],'total_anggaran'=>$p['budget'],'rekanan_id'=>$v['id'],'nama_penyedia'=>$v['nama_perusahaan'],'nomor_rup'=>$rup,'nomor_kontrak'=>'600/'.$rup.'/SPK-Konsultansi/PPK/DPUPR','nomor_spmk'=>'600/'.$rup.'/SPMK/PPK/DPUPR','proses_pengadaan'=>str_replace($base['nomor_rup'],$rup,$base['proses_pengadaan']),'keterangan'=>'Tahap 2 Excel '.$p['sheet'].' baris '.$p['row'].'; DPPA '.$d['id'].'; RAB proporsional contoh Excel; RUP sementara acak sesuai instruksi terdahulu.']);
  $db->query('UPDATE dppa_neo SET setujui=1,kunci=1 WHERE id=?',[$d['id']]);$id=(int)$db->insert('kontrak_neo',$c);$item=(int)$db->insert('kontrak_item_neo',['kontrak_id'=>$id,'tahap'=>'dppa','anggaran_id'=>$d['id'],'kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>2026,'kd_sub_keg'=>$d['kd_sub_keg'],'kd_akun'=>$d['kd_akun'],'uraian'=>$d['uraian'],'pagu'=>$p['budget'],'nilai_kontrak'=>$c['nilai_kontrak'],'username_insert'=>$actor,'is_deleted'=>0]);
  foreach($baseRab as$r){$r=$strip($r);$r=array_replace($r,['kontrak_id'=>$id,'kontrak_item_id'=>$item,'id_dppa'=>$d['id'],'keterangan'=>'Rincian contoh HPS dan penawaran konsultan 2026.xlsx, proporsional; tanpa item pembulatan.']);$db->insert('rab_paket_neo',$r);}
 }}
 $c=$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$id])->fetch();if(!$c||$c['uraian_kontrak']!==$d['uraian'])throw new RuntimeException('Contract mismatch');
 $rows=$db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY urutan,id',[$id])->fetchAll();if(!$rows)throw new RuntimeException('RAB missing');
 $hps=array_sum(array_map(fn($r)=>(float)$r['jumlah_hps'],$rows));$target=(float)$c['nilai_kontrak'];
 if($new||((int)$c['rekanan_id']!==(int)$v['id']&&$c['jenis_pengadaan']!=='PEKERJAAN_KONSTRUKSI')){
  $n=$counts[$v['id']]=($counts[$v['id']]??0)+1;
  $discount=match((int)$v['id']){475=>150000+$n*1000,479=>144000+$n*1000,468=>79000+$n*1000,436=>85000+$n*1000,589=>90000+$n*1000,590=>130000+$n*1000,default=>110000+$n*1000};
  $target=floor(($hps-$discount)/1000)*1000;$old=array_sum(array_map(fn($r)=>(float)$r['jumlah_penawaran'],$rows));if($old<=0)throw new RuntimeException('Empty offer');$sum=0;
  $last=count($rows)-1;foreach($rows as$i=>$r){if($r['type']==='KETERANGAN')continue;$total=$i===$last?round($target-$sum,2):round((float)$r['jumlah_penawaran']*$target/$old,2);$price=$total/((float)$r['vol_penawaran']*(1+(float)$r['pajak']/100));$sum+=$total;$db->update('rab_paket_neo',['harga_sat_penawaran'=>sprintf('%.12F',$price),'harga_sat_negoisasi'=>sprintf('%.12F',$price),'jumlah_penawaran'=>$total,'jumlah_negoisasi'=>$total,'bobot'=>round($total/$target*100,4),'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')],'WHERE id=?',[$r['id']]);}
  if(abs($sum-$target)>.001)throw new RuntimeException('Offer mismatch');
 }
 $process=ProcurementProcessService::decode($c['proses_pengadaan']);foreach($process as$code=>&$r)$r['tanggal']=in_array($code,['UNDANGAN_PL','JADWAL_DOKUMEN','BA_SURVEY_HARGA','PAKTA_INTEGRITAS'],true)?'2026-10-26':'2026-10-27';unset($r);
 $physical=$c['jenis_pengadaan']==='PEKERJAAN_KONSTRUKSI';$end=$physical?'2026-12-31':'2026-11-25';$days=$physical?66:30;
 $update=['rekanan_id'=>$v['id'],'nama_penyedia'=>$v['nama_perusahaan'],'total_anggaran'=>$p['budget'],'nilai_kontrak'=>$target,'jenis_kontrak'=>$physical?'HARGA_SATUAN':'LUMSUM','tanggal_kontrak'=>'2026-10-27','tanggal_spmk'=>'2026-10-27','tanggal_mulai'=>'2026-10-27','tanggal_selesai'=>$end,'waktu_pelaksanaan'=>$days,'proses_pengadaan'=>json_encode($process,JSON_UNESCAPED_SLASHES),'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')];$db->update('kontrak_neo',$update,'WHERE id=?',[$id]);$c=array_replace($c,$update);
 $db->query('UPDATE kontrak_item_neo SET nilai_kontrak=?,username_update=?,tgl_update=NOW() WHERE kontrak_id=? AND is_deleted=0',[$target,$actor,$id]);
 ProcurementProcessService::syncDocuments($db,(int)$id,$c,$actor);
 // An explicit offer-print date must follow the new process date as well.
 $setting=$db->query('SELECT settings_json FROM kontrak_print_settings_neo WHERE kontrak_id=?',[$id])->fetch();if($setting){$settings=json_decode($setting['settings_json'],true)?:[];if(isset($settings['offer_print'])){$settings['offer_print']['tanggal_surat']='2026-10-27';$db->query('UPDATE kontrak_print_settings_neo SET settings_json=?,updated_by=? WHERE kontrak_id=?',[json_encode($settings),$actor,$id]);}}
 $sum=$db->query('SELECT SUM(jumlah_penawaran) n FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0',[$id])->fetch()['n'];if(abs((float)$sum-$target)>.011||$target>$p['budget']||$target<=0)throw new RuntimeException('Offer exceeds DPPA or RAB mismatch');
 $report[]=array_replace($p,['contract_id'=>(int)$id,'new'=>$new,'offer'=>$target,'hps_rab'=>$hps,'hps_header'=>(float)$c['nilai_hps'],'discount'=>$hps-$target,'invitation_date'=>'2026-10-26','process_date'=>'2026-10-27','end'=>$end]);
}if(!in_array('--apply',$argv,true)){ $db->rollback();echo 'Dry run ready: '.count($report);exit;}$db->commit();}catch(Throwable$e){$db->rollback();throw$e;}
if($selected){$prior=json_decode(file_get_contents('outputs/tahap2-20261027/hasil-data.json'),true);$kept=array_values(array_filter($prior,fn($r)=>!in_array((int)$r['contract_id'],$selected,true)));$report=array_merge($kept,$report);}
file_put_contents('outputs/tahap2-20261027/hasil-data.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));echo 'Applied '.count($report).' packages';
