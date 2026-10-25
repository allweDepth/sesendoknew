<?php
/** Attach the verified Excel offers to their own scoped contracts; safe to rerun. */
chdir(dirname(__DIR__));
require 'app/Services/KontrakRealisasiService.php';
$user=['kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'type_user'=>'admin_opd','username'=>'IMPORT_PENAWARAN_EXCEL_2026'];
$_SESSION['user']=$user;$service=new KontrakRealisasiService($user);$db=DB::getInstance();
$manifest=json_decode(file_get_contents('outputs/konstruksi-perubahan-2026/manifest.json'),true,512,JSON_THROW_ON_ERROR);
$verified=json_decode(file_get_contents($argv[1]??''),true,512,JSON_THROW_ON_ERROR);$models=array_column($verified['contracts'],null,'id');
if(count($manifest)!==19||count($models)!==19)throw new RuntimeException('Expected exactly 19 verified packages');
$prepared=[];
foreach($manifest as $entry){
 $id=(int)$entry['id'];$delivery=$service->delivery($id);$header=$delivery['contract'];$model=$models[$id];$file=$entry['folder'].'/'.$entry['offerFile'];
 if(!is_file($file)||hash_file('sha256',$file)!==$model['offer_sha256'])throw new RuntimeException('Excel changed after validation: '.$id);
 $rows=array_values(array_filter($delivery['rab'],fn($r)=>$r['type']!=='KETERANGAN'));
 if(count($rows)!==count($model['rab']))throw new RuntimeException('RAB item count differs: '.$id);
 foreach($rows as $i=>$row){$expected=$model['rab'][$i];if(trim($row['uraian'])!==$expected['uraian']||trim($row['satuan'])!==$expected['satuan'])throw new RuntimeException('RAB description differs: '.$id);
  foreach(['vol_penawaran','harga_sat_penawaran','jumlah_penawaran','vol_negoisasi','harga_sat_negoisasi','jumlah_negoisasi','pajak'] as $key)if(abs((float)$row[$key]-(float)$expected[$key])>.001)throw new RuntimeException('RAB differs: '.$id.' '.$key);
 }
 $total=round(array_sum(array_column($rows,'jumlah_negoisasi')),2);
 if(abs($total-$model['nilai_kontrak'])>.001||abs($total-(float)$header['nilai_kontrak'])>.001)throw new RuntimeException('Contract total differs: '.$id);
 $prepared[]=[$entry,$header,$file,$total];
}
$created=[];$report=[];$db->begin();
try{foreach($prepared as [$e,$h,$source,$total]){
 $id=(int)$e['id'];$hash=hash_file('sha256',$source);$existing=$db->query('SELECT * FROM kontrak_dokumen_neo WHERE kontrak_id=? AND is_deleted=0 AND nama_file_asli=? AND username_insert=? ORDER BY id DESC',[$id,$e['offerFile'],$user['username']])->fetch();
 if($existing&&is_file($existing['path_file'])&&hash_file('sha256',$existing['path_file'])===$hash){$docId=(int)$existing['id'];$path=$existing['path_file'];}
 else{
  $scope=preg_replace('/[^A-Za-z0-9._-]/','_',$h['kd_wilayah'].'-'.$h['kd_opd']);$dir='storage/uploads/'.$scope.'/2026/kontrak/'.$id.'/rab';
  if(!is_dir($dir)&&!mkdir($dir,0775,true))throw new RuntimeException('Cannot create upload folder');
  $path=$dir.'/penawaran-'.substr($hash,0,20).'.'.strtolower(pathinfo($source,PATHINFO_EXTENSION));
  if(!file_exists($path)){$created[]=$path;if(!copy($source,$path))throw new RuntimeException('Cannot copy offer');}
  if(hash_file('sha256',$path)!==$hash)throw new RuntimeException('Uploaded file checksum differs');
  $version=(int)($db->query("SELECT COALESCE(MAX(versi),0)+1 versi FROM kontrak_dokumen_neo WHERE kontrak_id=? AND jenis_dokumen='RAB'",[$id])->fetch()['versi']);
  $docId=$db->insert('kontrak_dokumen_neo',['kontrak_id'=>$id,'jenis_dokumen'=>'RAB','nomor_dokumen'=>null,'tanggal_dokumen'=>'2026-10-26','judul'=>'Penawaran Excel — '.$e['package'],'nama_file_asli'=>$e['offerFile'],'path_file'=>$path,'mime_type'=>str_ends_with($source,'.xlsm')?'application/vnd.ms-excel.sheet.macroEnabled.12':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','ukuran'=>filesize($source),'versi'=>$version,'keterangan'=>'Penawaran perusahaan: '.$e['company'].'. Total rincian dan pembulatan diperiksa terhadap RAB aplikasi.','kd_wilayah'=>$h['kd_wilayah'],'kd_opd'=>$h['kd_opd'],'tahun'=>2026,'username_insert'=>$user['username']]);
 }
 $d=$service->document((int)$docId);if(hash_file('sha256',$d['path_file'])!==$hash)throw new RuntimeException('Download checksum mismatch');
 if(!in_array((int)$docId,array_map(fn($x)=>(int)$x['id'],$service->delivery($id)['documents']),true))throw new RuntimeException('Attachment missing from delivery');
 $report[]=['kontrak_id'=>$id,'document_id'=>$docId,'filename'=>$e['offerFile'],'sha256'=>$hash,'total_rab'=>$total,'display_rupiah'=>round($total,0),'path'=>$path,'status'=>'PASS'];
}$db->commit();}catch(Throwable $e){$db->rollback();foreach($created as $f)if(is_file($f))unlink($f);throw $e;}
file_put_contents('outputs/konstruksi-perubahan-2026/verifikasi-dokumen-terkait.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL);
echo json_encode(['packages'=>count($report),'attachments_verified'=>count($report),'items_verified'=>array_sum(array_map(fn($x)=>count($x['rab']),$models)),'totals_match'=>true,'download_bytes_match'=>true]).PHP_EOL;
