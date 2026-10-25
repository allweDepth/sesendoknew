<?php
/** Additive, transactionally verified import of the supplied PUPR amendment package list.
 * Usage: php scripts/import_contracts_perubahan_2026.php plan.json vendors.json [--apply]
 * Contract numbers/RUP and process dates are user-authorized draft values, not procurement evidence.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../app/Core/DB.php';
$plan=json_decode(file_get_contents($argv[1]??''),true,512,JSON_THROW_ON_ERROR);
// The finalized budget owns the package label. Keep the Excel spelling only
// as provenance. Split allocations retain distinct IDs/RUP, with the DPPA name.
foreach($plan['contracts'] as &$entry)if(!empty($entry['uraian_anggaran'])){
 $entry['package_excel']=$entry['package_excel']??$entry['package'];
 $entry['package']=$entry['uraian_anggaran'];
}
unset($entry);
$vendors=json_decode(file_get_contents($argv[2]??''),true,512,JSON_THROW_ON_ERROR);
$apply=in_array('--apply',$argv,true);$db=DB::getInstance();
$w='76.01';$o='1.03.0.00.0.00.01.0000';$y=2026;$actor='IMPORT_PERUBAHAN_PUPR_2026';
if(($plan['scope']??[])!==['kd_wilayah'=>$w,'kd_opd'=>$o,'year'=>$y])throw new RuntimeException('Scope tidak sesuai');
foreach(['source'=>'sha256','template'=>'template_sha256'] as $file=>$hash)
 if(!is_file($plan[$file])||hash_file('sha256',$plan[$file])!==$plan[$hash])throw new RuntimeException('Sumber berubah: '.$file);
if(isset($plan['supervision_template'])&&hash_file('sha256',$plan['supervision_template'])!==$plan['supervision_template_sha256'])throw new RuntimeException('Contoh pengawasan berubah');
$norm=static fn($s)=>preg_replace('/[^A-Z0-9]/','',strtoupper((string)$s));
$tax=static fn($s)=>str_pad(preg_replace('/\D/','',(string)$s),16,'0',STR_PAD_LEFT);
$active=$db->query('SELECT * FROM rekanan_neo WHERE kd_wilayah=? AND is_deleted=0',[$w])->fetchAll();
$vendorRows=[];$aliasToKey=[];$newVendors=0;
foreach($vendors as $v){
 $found=null;
 foreach($active as $r){
  if(isset($v['existing_id'])?(int)$r['id']===(int)$v['existing_id']:
   ($norm($r['nama_perusahaan'])===$norm($v['fields']['nama_perusahaan'])||($tax($r['npwp'])!=='0000000000000000'&&$tax($r['npwp'])===$tax($v['fields']['npwp'])))){$found=$r;break;}
 }
 if(isset($v['existing_id'])&&!$found)throw new RuntimeException('Referensi penyedia hilang');
 $vendorRows[$v['key']]=['source'=>$v,'existing'=>$found];if(!$found)$newVendors++;
 foreach($v['aliases'] as $a)$aliasToKey[$a]=$v['key'];
}
$prior=$db->query('SELECT * FROM kontrak_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY id',[$w,$o,$y])->fetchAll();
$already=array_values(array_filter($prior,static fn($r)=>$r['username_insert']===$actor));
if($already){
 if(count($already)!==count($plan['contracts']))throw new RuntimeException('Impor sebelumnya belum lengkap; perlu diperiksa');
 foreach($plan['contracts'] as $c){$matches=array_values(array_filter($already,static fn($r)=>str_contains((string)$r['keterangan'],'Excel row '.$c['excel_row'].';')));if(count($matches)!==1||$matches[0]['nomor_kontrak']!==$c['nomor_kontrak']||$matches[0]['tanggal_kontrak']!==$c['date'])throw new RuntimeException('Manifest berbeda dari impor sebelumnya');}
 echo json_encode(['status'=>'already_imported','contracts'=>count($already)]).PHP_EOL;exit;
}
$paguTotal=0.;$reservations=[];$rups=[];$budgetIds=[];$rabCount=0;$pending=0;
foreach($plan['contracts'] as $c){
 if($c['date']!=='2026-10-26'||isset($rups[$c['nomor_rup']])||!preg_match('/^\d{8}$/',$c['nomor_rup']))throw new RuntimeException('Tanggal/nomor RUP draft tidak valid');
 $rups[$c['nomor_rup']]=true;
 if($c['vendor_alias']&&!isset($aliasToKey[$c['vendor_alias']]))throw new RuntimeException('Alias penyedia tidak dikenali');
 if(!$c['vendor_alias'])$pending++;
 $sum=0.;foreach($c['rows'] as $r){
  $live=$db->query('SELECT * FROM dppa_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$r['id'],$w,$o,$y])->fetch();
  if(!$live||$live['kd_sub_keg']!==$r['kd_sub_keg']||$live['uraian']!==$r['uraian']||abs((float)$live['jumlah']-(float)$r['jumlah'])>.001)throw new RuntimeException('Rincian DPPA berubah');
  $allocated=(float)$r['jumlah']/($c['shared']?2:1);$sum+=$allocated;
  $reservations[$r['id']]=($reservations[$r['id']]??0)+$allocated;$budgetIds[$r['id']]=$live;
 }
 if(abs($sum-(float)$c['budget'])>.01)throw new RuntimeException('Pagu paket tidak cocok');
 if($c['rab']){
  $hps=array_sum(array_column($c['rab'],'jumlah_hps'));$offer=array_sum(array_column($c['rab'],'jumlah_penawaran'));$nego=array_sum(array_column($c['rab'],'jumlah_negoisasi'));
  if($hps>(float)$c['budget']||$offer>=$hps||$nego>$offer||abs($nego-(float)$c['nilai_kontrak'])>.01||($hps-$offer)/$hps>.002)throw new RuntimeException('Rekonsiliasi RAB gagal');
 }
 $paguTotal+=$sum;$rabCount+=count($c['rab']);
}
foreach($reservations as $id=>$sum)if($sum>(float)$budgetIds[$id]['jumlah']+.01)throw new RuntimeException('Pagu terpakai ganda');
$summary=['contracts'=>count($plan['contracts']),'pagu'=>$paguTotal,'new_companies'=>$newVendors,'pending_company'=>$pending,'rab_contracts'=>count(array_filter($plan['contracts'],static fn($c)=>!!$c['rab'])),'rab_rows'=>$rabCount,'budget_rows'=>count($budgetIds),'date'=>'2026-10-26'];
if(!$apply){echo json_encode(['status'=>'dry_run']+$summary,JSON_PRETTY_PRINT).PHP_EOL;exit;}
$db->begin();$result=[];$documents=0;$items=0;
try{
 $vendorIds=[];
 foreach($vendorRows as $key=>$entry){
  if($entry['existing']){$vendorIds[$key]=(int)$entry['existing']['id'];continue;}
  $v=$entry['source'];$fields=$v['fields'];$fields+=['kd_wilayah'=>$w,'alamat'=>'','npwp'=>'','direktur'=>'','disable'=>0,'is_deleted'=>0,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'keterangan'=>mb_substr('Profil perubahan 2026: '.$v['source'].(!empty($v['note'])?'; '.$v['note']:''),0,255)];
  $vendorIds[$key]=(int)$db->insert('rekanan_neo',$fields);
 }
 // These are the verified, finalized APBD amendment rows supplied as the contract budget.
 // Mark only referenced rows final; never alter their financial content or original DPA contracts.
 foreach($budgetIds as $id=>$r)$db->query('UPDATE dppa_neo SET setujui=1,kunci=1,username_update=?,tgl_update=NOW() WHERE id=?',[$actor,$id]);
 $masters=$db->query("SELECT * FROM master_dokumen_pengadaan_neo WHERE cara_pengadaan='PENYEDIA' AND aktif=1 AND is_deleted=0 AND berlaku_mulai<=? AND (berlaku_sampai IS NULL OR berlaku_sampai>=?) ORDER BY id",['2026-10-26','2026-10-26'])->fetchAll();
 foreach($plan['contracts'] as $c){
  $vid=$c['vendor_alias']?$vendorIds[$aliasToKey[$c['vendor_alias']]]:null;$first=$c['rows'][0];
  $provider=$vid?$db->query('SELECT nama_perusahaan FROM rekanan_neo WHERE id=?',[$vid])->fetch()['nama_perusahaan']:null;
  $note='Excel row '.$c['excel_row'].'; DRAFT; RUP acak sementara, bukan RUP resmi; '.($c['rab']?'HPS/penawaran estimasi proporsional template; ':'Nilai final belum tersedia; alokasi item adalah pagu sementara; ').implode('; ',$c['warnings']);
  $header=['kd_wilayah'=>$w,'kd_opd'=>$o,'tahun'=>$y,'kd_sub_keg'=>$c['kd_sub_keg'],'anggaran_id'=>$first['id'],'rekanan_id'=>$vid,'nama_sub_keg'=>$c['subactivity'],'tahap'=>'dppa','cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>$c['jenis_pengadaan'],'bentuk_kontrak'=>$c['bentuk_kontrak'],'metode_pemilihan'=>$c['bentuk_kontrak']==='SPK'?'Pengadaan Langsung':null,'nomor_rup'=>$c['nomor_rup'],'total_anggaran'=>$c['budget'],'nilai_hps'=>$c['nilai_hps'],'nilai_kontrak'=>$c['nilai_kontrak'],'nomor_kontrak'=>$c['nomor_kontrak'],'tanggal_kontrak'=>$c['date'],'uraian_kontrak'=>$c['package'],'nomor_spmk'=>$c['nomor_spmk'],'tanggal_spmk'=>$c['date'],'tanggal_mulai'=>$c['date'],'nama_penyedia'=>$provider,'status_kontrak'=>'draft','setujui'=>0,'kunci'=>0,'disable'=>0,'is_deleted'=>0,'keterangan'=>mb_substr($note,0,400),'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s')];
  $id=(int)$db->insert('kontrak_neo',$header);$left=$c['nilai_kontrak']!==null?(float)$c['nilai_kontrak']:(float)$c['budget'];$itemIds=[];
  foreach($c['rows'] as $i=>$r){
   $allocation=(float)$r['jumlah']/($c['shared']?2:1);
   $value=$i===array_key_last($c['rows'])?$left:round(($c['nilai_kontrak']!==null?(float)$c['nilai_kontrak']:(float)$c['budget'])*$allocation/(float)$c['budget'],2);$left=round($left-$value,2);
   $itemIds[]=(int)$db->insert('kontrak_item_neo',['kontrak_id'=>$id,'tahap'=>'dppa','anggaran_id'=>$r['id'],'kd_wilayah'=>$w,'kd_opd'=>$o,'tahun'=>$y,'kd_sub_keg'=>$r['kd_sub_keg'],'kd_akun'=>$r['kd_akun'],'uraian'=>$r['uraian'],'pagu'=>$allocation,'nilai_kontrak'=>$value,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);$items++;
  }
  foreach($c['rab'] as $rab){
   $row=$rab+['kontrak_id'=>$id,'kontrak_item_id'=>$itemIds[0],'tahun'=>$y,'kd_wilayah'=>$w,'kd_opd'=>$o,'id_renja_p'=>0,'id_dpa'=>0,'id_dppa'=>$first['id'],'type'=>'DRAFT HPS proporsional','vol_hps'=>1,'vol_penawaran'=>1,'vol_negoisasi'=>1,'bobot'=>round((float)$rab['jumlah_negoisasi']/(float)$c['nilai_kontrak']*100,4),'keterangan'=>mb_substr('Draft proporsional '.($c['rab_template']??'HPS dan penawaran konsultan 2026.xlsx / 64449217').'; bukan penawaran aktual; 26-10-2026; '.($c['rab_note']??''),0,255),'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0];
   $db->insert('rab_paket_neo',$row);
  }
  $documentIds=[];
  foreach($masters as $m){
   if(!in_array($m['kelompok'],['PERSIAPAN','PEMILIHAN','KONTRAK'],true)&&$m['kode']!=='SPMK')continue;
   if(!in_array($m['jenis_pengadaan'],['SEMUA',$c['jenis_pengadaan']],true))continue;
   if($m['bentuk_kontrak']!=='SEMUA'&&$m['bentuk_kontrak']!==$c['bentuk_kontrak'])continue;
   if($m['metode_pemilihan']&&$m['metode_pemilihan']!==$header['metode_pemilihan'])continue;
   $estimate=(float)($c['nilai_kontrak']??$c['budget']);
   if($estimate<(float)$m['nilai_min']||($m['nilai_max']!==null&&$estimate>(float)$m['nilai_max']))continue;
   $docNumber=$m['kode']==='SPMK'?$c['nomor_spmk']:($m['bentuk_kontrak']===$c['bentuk_kontrak']?$c['nomor_kontrak']:'600/'.$c['nomor_rup'].'/'.str_replace('_','-',$m['kode']).'/PPK/DPUPR');
   $meta=['source_sha256'=>$plan['sha256'],'excel_row'=>$c['excel_row'],'draft'=>true,'rup_status'=>'ACAK_SEMENTARA','tanggal_proses'=>$c['date'],'nilai_status'=>$c['rab']?'ESTIMASI_PROPORSIONAL':'BELUM_TERSEDIA','penyedia_status'=>$vid?'SUMBER_WORKBOOK':'BELUM_PASTI'];
   $docId=(int)$db->insert('dokumen_pengadaan_neo',['kontrak_id'=>$id,'master_id'=>$m['id'],'kode_dokumen'=>$m['kode'],'nomor_dokumen'=>$docNumber,'tanggal_dokumen'=>$c['date'],'judul'=>mb_substr($m['nama'].' — '.$c['package'],0,255),'status'=>'DRAFT','data_isian'=>json_encode($meta,JSON_UNESCAPED_UNICODE),'dasar_hukum_snapshot'=>$m['dasar_hukum'],'versi_master'=>$m['versi'],'pembuat_role'=>'ppk','kd_wilayah'=>$w,'kd_opd'=>$o,'tahun'=>$y,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);
   $sections=$db->query('SELECT * FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND is_deleted=0 ORDER BY urutan,id',[$m['id']])->fetchAll();
   foreach($sections as $section){
    $body=$section['isi_template'];
    // Generic HTML masters use the application's variable replacement at rendering time.
    if(!str_starts_with(ltrim($body),'['))$body='<p><b>DRAFT — RUP sementara; nilai dan hasil proses belum disahkan.</b></p>'.$body;
    $db->insert('dokumen_pengadaan_bagian_neo',['dokumen_id'=>$docId,'master_bagian_id'=>$section['id'],'kode_bagian'=>$section['kode_bagian'],'judul'=>$section['judul'],'urutan'=>$section['urutan'],'isi'=>$body,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);
   }
   $documents++;$documentIds[]=$docId;
  }
  $result[]=['excel_row'=>$c['excel_row'],'contract_id'=>$id,'nomor_kontrak'=>$c['nomor_kontrak'],'nomor_rup'=>$c['nomor_rup'],'rekanan_id'=>$vid,'item_ids'=>$itemIds,'document_ids'=>$documentIds,'warnings'=>$c['warnings']];
 }
 // Exact checks within the transaction: existing contracts must be byte-for-byte unchanged.
 foreach($prior as $r){$live=$db->query('SELECT * FROM kontrak_neo WHERE id=?',[$r['id']])->fetch();if($live!==$r)throw new RuntimeException('Kontrak lama berubah');}
 $check=$db->query('SELECT COUNT(*) n,SUM(total_anggaran) pagu,COUNT(DISTINCT nomor_rup) rups,MIN(tanggal_kontrak) awal,MAX(tanggal_kontrak) akhir FROM kontrak_neo WHERE username_insert=? AND is_deleted=0',[$actor])->fetch();
 if((int)$check['n']!==83||(int)$check['rups']!==83||abs((float)$check['pagu']-$paguTotal)>.01||$check['awal']!=='2026-10-26'||$check['akhir']!=='2026-10-26')throw new RuntimeException('Rekonsiliasi hasil gagal');
 $db->commit();
 echo json_encode(['status'=>'applied']+$summary+['items'=>$items,'process_documents'=>$documents,'results'=>$result],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
