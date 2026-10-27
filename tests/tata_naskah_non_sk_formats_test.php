<?php
require_once __DIR__.'/../app/Services/PdfService.php';
$assert=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);echo "PASS: $message\n";};
$assert(TataNaskahFormatService::rank('iva')==='Pembina, IV/a','rank shorthand resolved');
$assert(TataNaskahFormatService::rank('','3','c')==='Penata, III/c','empty rank uses employee master');
$assert(TataNaskahFormatService::rank('Pembina, IV/a')==='Pembina, IV/a','explicit rank retained');
$skSchema=[['name'=>'menimbang','type'=>'editable_table']];
$assert(TataNaskahFormatService::schema(5,$skSchema)===$skSchema,'SK form is unchanged');
$s=TataNaskahFormatService::withStoredFields(TataNaskahFormatService::schema(24),['isi'=>[['text'=>'uraian 1']],'tentang'=>'tentang','isi_lama'=>'Old saved text']);
$names=array_column($s,'name');$assert(in_array('isi',$names,true)&&in_array('tentang',$names,true)&&in_array('isi_lama',$names,true),'training includes both current and legacy stored body fields');

$_SESSION['user']=['kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_pemda'=>'PEMERINTAH KABUPATEN PASANGKAYU','nama_opd'=>'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG'];
$root=__DIR__.'/../outputs/tata-naskah-audit-20261027/fixtures';if(!is_dir($root))mkdir($root,0775,true);
$report=[];
for($id=1;$id<=25;$id++){
    if($id===5)continue;$profile=TataNaskahFormatService::profile($id);$schema=TataNaskahFormatService::schema($id);
    $data=['nama_penandatangan'=>'SYAMSUNAR, SP.,M.M','jabatan_penandatangan'=>'Kepala Dinas','pangkat_penandatangan'=>'Pembina, IV/a','nip_penandatangan'=>'197503102009031001','tempat_ditetapkan'=>'Pasangkayu'];$tokens=[];
    $fill=static function($items)use(&$fill,&$data,&$tokens,$id){foreach($items as $f){if(isset($f['fields'])){$fill($f['fields']);continue;}$name=$f['name']??'';if(!$name||in_array($name,['nomor','tanggal_surat','file','perihal','penandatangan','jbt_pemberi_tgs','pangkat_pemberi_tgs','asn'],true))continue;$token='T'.$id.'_'.strtoupper($name);$tokens[]=$token;
        if(($f['type']??'')==='editable_table')$data[$name]=[['type'=>'numbered','text'=>$token.' - Paragraf pertama untuk pemeriksaan isi naskah.','align'=>'justify'],['type'=>'numbered','text'=>'Paragraf kedua untuk memastikan nomor sejajar dan baris terakhir rata kiri.','align'=>'justify']];
        elseif($name==='barang'){$data[$name]=[['uraian'=>$token,'banyaknya'=>'2 berkas','keterangan'=>'Lengkap']];}
        elseif($name==='petugas'){$data[$name]=[['nama'=>$token,'pangkat'=>'Penata, III/c','nip'=>'198007112006041010','jabatan'=>'Verifikator Keuangan']];}
        else $data[$name]=$token;
    }};$fill($schema);
    $header=['id'=>0,'jenis_id'=>$id,'jenis_naskah'=>$profile['title'],'nomor'=>'TEST/'.$id.'/2026','perihal'=>'Pemeriksaan Tata Naskah Tahun 2026','tanggal_surat'=>'2026-10-27','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026];
    $setup=PageSetupService::current();$setup=array_replace($setup,['paper'=>'A4','orientation'=>$id===24?'L':'P','font'=>'arial_tata_naskah','font_size'=>12,'margin_left'=>30,'margin_right'=>20,'margin_top'=>20,'margin_bottom'=>25,'signature_height'=>25]);
    $pdf=PageSetupService::createPdf($setup);PageSetupService::applyPdf($pdf,$setup);$pdf->AddPage();
    (new TataNaskahPdfRenderer())->render($pdf,$header,$schema,$data,$setup,__DIR__.'/../public/assets/img/umum/logo.png');
    $bytes=$pdf->Output('','S');$assert(str_starts_with($bytes,'%PDF-'),"native PDF generated for type $id");file_put_contents($root.'/type-'.$id.'.pdf',$bytes);$report[]=['type'=>$id,'tokens'=>$tokens];
}
file_put_contents($root.'/expected.json',json_encode($report,JSON_PRETTY_PRINT));
echo "All non-SK layouts generated; verify expected.json tokens against extracted PDF text.\n";
