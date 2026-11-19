<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../../app/Core/DB.php';
$db=DB::getInstance();$db->begin();
try{
 $rows=$db->query('SELECT * FROM kontrak_neo WHERE uraian_kontrak=? AND tahun=2026 AND is_deleted=0 FOR UPDATE',['Normalisasi Sungai/Saluran Desa Saptanajaya'])->fetchAll();
 if(count($rows)!==1)throw new RuntimeException('Expected one package');$c=$rows[0];$id=$c['id'];
 $docs=$db->query('SELECT * FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0 FOR UPDATE',[$id])->fetchAll();
 $settings=$db->query('SELECT * FROM kontrak_print_settings_neo WHERE kontrak_id=? FOR UPDATE',[$id])->fetch();
 foreach($docs as $d)if($d['status']!=='DRAFT')throw new RuntimeException('Document no longer draft');
 $backup=['contract'=>$c,'documents'=>$docs,'settings'=>$settings];
 if(file_put_contents(__DIR__.'/before-'.date('Ymd-His').'.json',json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
 $dateFor=fn($code)=>$code==='UNDANGAN_PL'?'2026-11-16':'2026-11-17';
 $audit=['username_update'=>'SAPTANAJAYA_DATES_20261119','tgl_update'=>date('Y-m-d H:i:s')];
 $p=json_decode($c['proses_pengadaan'],true,512,JSON_THROW_ON_ERROR);
 foreach($p as $code=>&$v)$v['tanggal']=$dateFor($code);unset($v);
 $p['UNDANGAN_PL']['tanggal']='2026-11-16';$p['UPLOAD_PENAWARAN']['tanggal']='2026-11-17';
 $duration=(new DateTimeImmutable('2026-11-17'))->diff(new DateTimeImmutable($c['tanggal_selesai']))->days+1;
 $changes=$audit+['tanggal_kontrak'=>'2026-11-17','tanggal_spmk'=>'2026-11-17','tanggal_mulai'=>'2026-11-17','waktu_pelaksanaan'=>$duration,'proses_pengadaan'=>json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
 $db->update('kontrak_neo',$changes,'WHERE id=?',[$id]);
 $after=$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$id])->fetch();
 foreach($after as $k=>$v)if($v!=($changes[$k]??$c[$k]))throw new RuntimeException('Contract verification failed: '.$k);
 foreach($docs as $d){
  $data=json_decode($d['data_isian'],true,512,JSON_THROW_ON_ERROR);
  if(isset($data['tanggal_proses']))$data['tanggal_proses']=$dateFor($d['kode_dokumen']);
  $delta=$audit+['tanggal_dokumen'=>$dateFor($d['kode_dokumen']),'data_isian'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
  $db->update('dokumen_pengadaan_neo',$delta,'WHERE id=?',[$d['id']]);
  $a=$db->query('SELECT * FROM dokumen_pengadaan_neo WHERE id=?',[$d['id']])->fetch();
  foreach($a as $k=>$v)if($v!==($delta[$k]??$d[$k]))throw new RuntimeException('Document verification failed: '.$k);
 }
 if($settings){
  $s=json_decode($settings['settings_json'],true,512,JSON_THROW_ON_ERROR);
  if(isset($s['offer_print'])){
   $s['offer_print']['tanggal_surat']='2026-11-17';$json=json_encode($s,JSON_UNESCAPED_UNICODE);
   $db->query('UPDATE kontrak_print_settings_neo SET settings_json=?,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE kontrak_id=?',[$json,$audit['username_update'],$id]);
   if($db->query('SELECT settings_json FROM kontrak_print_settings_neo WHERE kontrak_id=?',[$id])->fetchColumn()!==$json)throw new RuntimeException('Offer settings verification failed');
  }
 }
 $db->commit();
 $report=['id'=>$id,'paket'=>$after['uraian_kontrak'],'penyedia'=>$after['nama_penyedia'],'undangan'=>$p['UNDANGAN_PL']['tanggal'],'pemasukan'=>$p['UPLOAD_PENAWARAN']['tanggal'],'kontrak'=>$after['tanggal_kontrak'],'mulai'=>$after['tanggal_mulai'],'selesai'=>$after['tanggal_selesai'],'durasi'=>$after['waktu_pelaksanaan'],'dokumen_diperbarui'=>count($docs)];
 file_put_contents(__DIR__.'/result.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
