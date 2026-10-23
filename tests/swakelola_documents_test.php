<?php
require_once __DIR__.'/../app/Services/ProcurementDocumentService.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';
require_once __DIR__.'/../app/Services/KontrakRealisasiService.php';
$db=DB::getInstance();
$ok=function(bool $v,string $m){if(!$v)throw new RuntimeException($m);echo "PASS: $m\n";};
$catalog=SwakelolaDocumentService::catalog();
$ok(count($catalog['sources'])===16 && count($catalog['templates'])===163,'16 sumber dan 163 master lengkap');
foreach($catalog['templates'] as $t){
    SwakelolaDocumentService::validate($t['blocks']);
    $m=$db->query('SELECT id FROM master_dokumen_pengadaan_neo WHERE kode=? AND versi=? AND aktif=1',[$t['code'],$catalog['version']])->fetch();
    if(!$m)throw new RuntimeException('Master hilang: '.$t['code']);
    $part=$db->query("SELECT id,isi_template FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND kode_bagian='ISI'",[$m['id']])->fetch();
    $roundtrip=SwakelolaDocumentService::loadBlocks($db,'master_bagian_id',(int)$part['id']);
    if($roundtrip!=$t['blocks'])throw new RuntimeException('Blok berubah: '.$t['code']);
}
$ok(true,'seluruh paragraf, tabel, dan sel pada 163 master utuh setelah dibaca dari database');
$ok(!str_contains(SwakelolaDocumentService::html([['type'=>'paragraph','text'=>'<script>evil()</script>']]),'<script>'),'HTML dari isi dokumen di-escape');
$user=['type_user'=>'admin_opd','username'=>'QA_SW_TEST','kd_wilayah'=>'QA.SW','kd_opd'=>'QA.SW','tahun'=>2026,'nama_opd'=>'OPD Uji Swakelola'];
$_SESSION['user']=$user;
$partner=0;$budget=0;$contracts=[];$documents=[];
try {
    $partner=(int)$db->insert('rekanan_neo',['kd_wilayah'=>'QA.SW','nama_perusahaan'=>'Organisasi Uji Swakelola','alamat'=>'Alamat Uji','npwp'=>'NPWP-UJI','direktur'=>'Pimpinan Uji','username_insert'=>'QA_SW_TEST','is_deleted'=>0]);
    $normalizer=new DynamicTableService();$method=(new ReflectionClass($normalizer))->getMethod('normalizeContractProcurement');
    $value=$method->invoke($normalizer,'kontrak_neo',['cara_pengadaan'=>'SWAKELOLA','tipe_swakelola'=>'III','rekanan_id'=>$partner]);
    $ok((int)$value['rekanan_id']===$partner,'simpan kontrak swakelola mempertahankan hubungan pelaksana');
    $budget=(int)$db->insert('dpa_neo',['kd_wilayah'=>'QA.SW','kd_opd'=>'QA.SW','tahun'=>2026,'kd_sub_keg'=>'QA.SW.01','uraian'=>'Anggaran Uji Swakelola','jumlah'=>100000000,'setujui'=>1,'kunci'=>1,'is_deleted'=>0]);
    $service=new ProcurementDocumentService($user);
    foreach(['I','II','III','IV'] as $type){
        $contract=(int)$db->insert('kontrak_neo',['kd_wilayah'=>'QA.SW','kd_opd'=>'QA.SW','tahun'=>2026,'cara_pengadaan'=>'SWAKELOLA','tipe_swakelola'=>$type,'jenis_pengadaan'=>'BARANG','uraian_kontrak'=>'Paket Uji Swakelola '.$type,'rekanan_id'=>$partner,'nilai_kontrak'=>15000000,'tahap'=>'dpa','anggaran_id'=>$budget,'nomor_kontrak'=>'QA/SW/'.$type,'tanggal_kontrak'=>'2026-10-23','status_kontrak'=>'aktif','is_deleted'=>0]);
        $contracts[]=$contract;
        $db->insert('rab_paket_neo',['kontrak_id'=>$contract,'tahun'=>2026,'kd_wilayah'=>'QA.SW','kd_opd'=>'QA.SW','id_renja_p'=>0,'id_dpa'=>$budget,'id_dppa'=>0,'nomor'=>'1','uraian'=>'Pekerjaan nyata dari RAB paket','satuan'=>'LS','vol_negoisasi'=>1,'harga_sat_negoisasi'=>15000000,'jumlah_negoisasi'=>15000000,'username_insert'=>'QA_SW_TEST']);
        $templates=$service->templates($contract)['templates'];
        $expected=count(array_filter($catalog['templates'],fn($t)=>$t['type']===$type));
        $sw=array_filter($templates,fn($t)=>SwakelolaDocumentService::supports($t['kode']));
        $ok(count($sw)===$expected && count(array_filter($sw,fn($t)=>$t['tipe_swakelola']!==$type))===0,"daftar master Tipe $type sesuai ($expected)");
        foreach($sw as $t){$autoDraft=$service->draft($contract,(int)$t['id']);foreach($autoDraft['sections'] as $part){if(preg_match('/[<(](?:diisi|isi|ditulis)\b/iu',$part['isi'])){preg_match('/.{0,100}[<(](?:diisi|isi|ditulis).{0,150}/iu',$part['isi'],$left);throw new RuntimeException('Petunjuk isian tersisa: '.$t['kode'].' '.($left[0]??''));}}}
        $ok(true,"seluruh master Tipe $type terisi otomatis tanpa petunjuk (isi...)");
        if($type==='II'){$rabMaster=$db->query("SELECT id FROM master_dokumen_pengadaan_neo WHERE kode='SW_II_02_08' AND aktif=1")->fetch();$rabDraft=$service->draft($contract,(int)$rabMaster['id']);file_put_contents(sys_get_temp_dir().'/swakelola-draft-qa.json',json_encode($rabDraft,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));$ok(str_contains($rabDraft['sections'][0]['isi'],'Pekerjaan nyata dari RAB paket'),'tabel RAB terisi uraian paket secara otomatis');}
        $master=$db->query("SELECT id FROM master_dokumen_pengadaan_neo WHERE kode=? AND aktif=1",['SW_'.$type.'_02_01'])->fetch();
        $draft=$service->draft($contract,(int)$master['id']);
        $ok(str_contains($draft['contract']['nama_penyedia_resmi'],'Organisasi Uji Swakelola'),"identitas pelaksana terisi otomatis Tipe $type");
        $blocks=json_decode($draft['sections'][0]['isi'],true);
        $blocks[]=['type'=>'paragraph','text'=>'Paragraf suntingan '.$type,'align'=>'right','bold'=>true,'style'=>['bold','italic','underline']];
        $blocks[]=['type'=>'table','rows'=>[[['colspan'=>2,'blocks'=>[['type'=>'paragraph','text'=>'Sel suntingan '.$type]]]]]];
        $draft['sections'][0]['isi']=json_encode($blocks);
        $payload=['contract_id'=>$contract,'master_id'=>$master['id'],'judul'=>'KAK Swakelola Tipe '.$type,'nomor_dokumen'=>'QA/KAK/'.$type,'tanggal_dokumen'=>'2026-10-23','sections'=>$draft['sections']];
        $saved=$service->save($payload);$documents[]=(int)$saved['id'];
        $ok(json_decode($saved['sections'][0]['isi'],true)==$blocks,"sunting paragraf dan sel tersimpan Tipe $type");
        $payload['id']=$saved['id'];$payload['sections'][0]['isi']=json_encode(array_merge($blocks,[['type'=>'paragraph','text'=>'Perubahan kedua']]));
        $updated=$service->save($payload);
        $ok(str_contains($updated['sections'][0]['isi'],'Perubahan kedua'),"dokumen dapat dibuka dan disunting ulang Tipe $type");
        $ok(!str_contains($service->draft($contract,(int)$master['id'])['sections'][0]['isi'],'Perubahan kedua'),"master tidak berubah ketika hasil disunting Tipe $type");
        $pdf=$service->pdf((int)$saved['id']);
        $ok(str_starts_with($pdf,'%PDF-')&&strlen($pdf)>3000,"PDF Tipe $type terbentuk");
        file_put_contents(sys_get_temp_dir().'/swakelola-qa-'.$type.'.pdf',$pdf);
        if($type==='II'){$batch=$service->generateSwakelola($contract);$ok($batch['created']===25,'satu aksi menyusun semua 26 dokumen Tipe II termasuk yang sudah ada');$again=$service->generateSwakelola($contract);$ok($again['created']===0&&$again['existing']===26,'susun ulang tidak menggandakan atau menimpa dokumen');$rabDoc=$db->query("SELECT d.id FROM dokumen_pengadaan_neo d JOIN master_dokumen_pengadaan_neo m ON m.id=d.master_id WHERE d.kontrak_id=? AND m.kode='SW_II_02_08' AND d.is_deleted=0",[$contract])->fetch();file_put_contents(sys_get_temp_dir().'/swakelola-qa-rab.pdf',$service->pdf((int)$rabDoc['id']));}
        $ok(str_starts_with((new KontrakRealisasiService($user))->contractPdf($contract),'%PDF-'),"tombol PDF kontrak memakai pokok perjanjian swakelola Tipe $type");
        $wrong=$db->query("SELECT id FROM master_dokumen_pengadaan_neo WHERE kode=?",['SW_'.($type==='I'?'II':'I').'_02_01'])->fetch();
        try{$service->draft($contract,(int)$wrong['id']);throw new LogicException('Master tipe lain diterima');}catch(InvalidArgumentException $e){}
    }
    $other=$user;$other['kd_opd']='OPD.LAIN';$_SESSION['user']=$other;
    try{(new ProcurementDocumentService($other))->detail($documents[0]);throw new LogicException('Scope OPD bocor');}catch(RuntimeException $e){if($e instanceof LogicException)throw $e;}
    $ok(true,'master tipe berbeda dan akses dokumen OPD lain ditolak');
} finally {
    $_SESSION['user']=$user;
    foreach($contracts as $contract){foreach($db->query('SELECT id FROM dokumen_pengadaan_neo WHERE kontrak_id=?',[$contract])->fetchAll() as $row)$documents[]=(int)$row['id'];}
    foreach(array_unique($documents) as $id){
        $db->query('DELETE p FROM dokumen_pengadaan_paragraf_neo p JOIN dokumen_pengadaan_bagian_neo b ON b.id=p.dokumen_bagian_id WHERE b.dokumen_id=?',[$id]);
        $db->query('DELETE FROM dokumen_pengadaan_lampiran_neo WHERE dokumen_id=?',[$id]);
        $db->query('DELETE FROM dokumen_pengadaan_bagian_neo WHERE dokumen_id=?',[$id]);
        $db->query('DELETE FROM dokumen_pengadaan_neo WHERE id=?',[$id]);
    }
    foreach($contracts as $id){$db->query('DELETE FROM rab_paket_neo WHERE kontrak_id=?',[$id]);$db->query('DELETE FROM kontrak_neo WHERE id=?',[$id]);}
    if($budget)$db->query('DELETE FROM dpa_neo WHERE id=?',[$budget]);
    if($partner)$db->query('DELETE FROM rekanan_neo WHERE id=?',[$partner]);
}
echo "SWAKELOLA TESTS COMPLETE\n";
