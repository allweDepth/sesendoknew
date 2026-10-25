<?php
/** Read-only source audit, with optional correction of imported draft labels. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Core/DB.php';$db=DB::getInstance();
$root=dirname(__DIR__);$sourcePath=$root.'/storage/imports/kontrak_perubahan_2026/source.json';
$budget=json_decode(file_get_contents($root.'/storage/imports/dppa_2026_pupr/source.json'),true,512,JSON_THROW_ON_ERROR);
$plan=json_decode(file_get_contents($sourcePath),true,512,JSON_THROW_ON_ERROR);$scope=['76.01','1.03.0.00.0.00.01.0000',2026];$apply=in_array('--apply',$argv,true);
$cents=static fn($v)=>(int)round((float)$v*100);
$signature=static fn($r)=>json_encode([$r['kd_akun'],$r['kelompok'],$r['uraian'],$r['komponen'],$r['spesifikasi'],$r['koefisien_keterangan'],round((float)$r['volume'],4),$cents($r['harga_satuan']),$cents($r['jumlah']),$cents($r['jumlah_awal']),$cents($r['harga_satuan_awal']),round((float)$r['volume_awal'],4)]);
$audited=[];
foreach($budget['documents'] as $doc){
    $pdf='/Users/nabiila_inayah/Downloads/DPPA 2026/'.$doc['source_file'];
    if(!is_file($pdf)||hash_file('sha256',$pdf)!==$doc['sha256'])throw new RuntimeException('PDF sumber berubah atau hilang: '.$doc['source_file']);
    $expected=[];
    foreach($doc['items'] as $item)$expected[]=$signature(['kd_akun'=>$item['kd_akun'],'kelompok'=>$item['kelompok'],'uraian'=>$item['uraian_kelompok'],'komponen'=>$item['komponen'],'spesifikasi'=>$item['spesifikasi'],'koefisien_keterangan'=>$item['after']['koefisien_keterangan'],'volume'=>$item['after']['volume'],'harga_satuan'=>$item['after']['harga_satuan'],'jumlah'=>$item['after']['jumlah'],'volume_awal'=>$item['before']['volume'],'harga_satuan_awal'=>$item['before']['harga_satuan'],'jumlah_awal'=>$item['before']['jumlah']]);
    sort($expected);
    foreach(['renja_p_neo','rka_p_neo','dppa_neo'] as $table){
        $rows=$db->query("SELECT * FROM `$table` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND kd_sub_keg=? AND is_deleted=0",[...$scope,$doc['sub_kegiatan']])->fetchAll();$actual=array_map($signature,$rows);sort($actual);
        if($actual!==$expected)throw new RuntimeException('Isi rincian berbeda dari PDF: '.$table.' '.$doc['sub_kegiatan']);
        $audited[$table]=($audited[$table]??0)+count($rows);
    }
}
$fixes=[];$shared=[];$metadata=[];$reservations=[];
foreach($plan['contracts'] as &$c){
    $h=$db->query('SELECT * FROM kontrak_neo WHERE nomor_rup=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$c['nomor_rup'],...$scope])->fetch();
    if(!$h||$h['username_insert']!=='IMPORT_PERUBAHAN_PUPR_2026'||$h['status_kontrak']!=='draft'||$h['setujui']||$h['kunci'])throw new RuntimeException('Kontrak telah berubah: '.$c['nomor_rup']);
    $items=$db->query('SELECT * FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY id',[$h['id']])->fetchAll();
    if(count($items)!==count($c['rows']))throw new RuntimeException('Jumlah tautan DPPA berubah');
    foreach($items as $item){
        $d=$db->query('SELECT * FROM dppa_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0',[$item['anggaran_id'],...$scope])->fetch();
        if(!$d||$d['kd_sub_keg']!==$h['kd_sub_keg']||$item['uraian']!==$d['uraian'])throw new RuntimeException('Tautan/nama rincian kontrak tidak cocok DPPA');
        $reservations[$d['id']]=($reservations[$d['id']]??0)+(float)$item['pagu'];
        if($reservations[$d['id']]>(float)$d['jumlah']+.01)throw new RuntimeException('Alokasi paket melebihi rincian DPPA');
    }
    $canonical=$c['uraian_anggaran'];$label=$budget['recap']['rows'][$h['kd_sub_keg']]['name'];
    if($c['shared'])$shared[]=['id'=>$h['id'],'package_excel'=>$c['package_excel']??$c['package'],'uraian_dppa'=>$canonical,'pagu_kontrak'=>$h['total_anggaran'],'note'=>'Paket berbagi rincian DPPA; alokasi total telah diperiksa; nama kontrak tetap mengikuti DPPA.'];
    if($h['uraian_kontrak']!==$canonical){
        if($h['uraian_kontrak']!==$c['package'])throw new RuntimeException('Nama telah diedit pengguna');
        $fixes[]=['id'=>$h['id'],'old'=>$h['uraian_kontrak'],'new'=>$canonical,'rup'=>$h['nomor_rup']];
        $c['package_excel']=$c['package_excel']??$c['package'];$c['package']=$canonical;$c['name_source']='Rincian DPPA final, bukan ejaan Excel daftar paket';
    }
    if($h['nama_sub_keg']!==$label)$metadata[]=['id'=>$h['id'],'old'=>$h['nama_sub_keg'],'new'=>$label];
    $c['subactivity']=$label;
}
unset($c);
$report=['pdfs_verified'=>count($budget['documents']),'rows_verified'=>$audited,'contracts_budget_links_verified'=>count($plan['contracts']),'name_corrections'=>$fixes,'subactivity_label_corrections'=>$metadata,'shared_budget_packages'=>$shared];
if(!$apply){echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;exit;}
$db->begin();try{
    foreach($fixes as $f){
        $db->query('UPDATE kontrak_neo SET uraian_kontrak=?,tgl_update=NOW() WHERE id=?',[$f['new'],$f['id']]);
        $db->query("UPDATE dokumen_pengadaan_neo SET judul=REPLACE(judul,?,?),tgl_update=NOW() WHERE kontrak_id=? AND is_deleted=0 AND status='DRAFT' AND username_insert='IMPORT_PERUBAHAN_PUPR_2026' AND judul LIKE ?",[$f['old'],$f['new'],$f['id'],'%'.$f['old'].'%']);
    }
    foreach($metadata as $f)$db->query('UPDATE kontrak_neo SET nama_sub_keg=?,tgl_update=NOW() WHERE id=?',[$f['new'],$f['id']]);
    $db->commit();
}catch(Throwable $e){$db->rollback();throw $e;}
$encode=static fn($data)=>preg_replace_callback('/^( +)/m',static fn($m)=>str_repeat(' ',strlen($m[1])/2),json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))."\n";
file_put_contents($sourcePath,$encode($plan));
$reportPath=$root.'/storage/imports/kontrak_perubahan_2026/name_audit.json';
if(is_file($reportPath)){
    $prior=json_decode(file_get_contents($reportPath),true);
    foreach(['name_corrections','subactivity_label_corrections'] as $key){$history=[];foreach(array_merge($prior[$key]??[],$report[$key]) as $entry)$history[$entry['id']]=$entry;$report[$key]=array_values($history);}
}
file_put_contents($reportPath,$encode($report));
echo json_encode(['status'=>'corrected','pdfs_verified'=>$report['pdfs_verified'],'rows_verified'=>$audited,'contracts_budget_links_verified'=>count($plan['contracts']),'names_corrected'=>count($fixes),'subactivity_labels_corrected'=>count($metadata),'shared_budget_packages'=>count($shared)]).PHP_EOL;
