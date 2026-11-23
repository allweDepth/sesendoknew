<?php
require_once __DIR__.'/../app/Services/ProcurementFixedPdfService.php';
$files=[];
foreach(['STANDARD','MINI_KOMPETISI'] as $variant){
    $c=['id'=>0,'jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','metode_pemilihan'=>'Mini Kompetisi','cara_pengadaan'=>'PENYEDIA','nilai_kontrak'=>2000000000,'uraian_kontrak'=>'Uji batas sel tabel','print_settings'=>['terms_variant'=>$variant,'line_spacing'=>1.5,'paragraph_after'=>6]];
    $setup=['paper'=>'A4','font'=>'helvetica','font_size'=>9,'margin_left'=>18,'margin_right'=>18,'margin_top'=>18,'margin_bottom'=>18,'header_enabled'=>false,'footer_enabled'=>false];
    $path=tempnam(sys_get_temp_dir(),'sskk-page-break-').'.pdf';
    file_put_contents($path,(new ProcurementFixedPdfService([]))->render(['kode_dokumen'=>'SSKK','sections'=>[]],$c,$setup));
    $files[$variant]=$path;
}
echo json_encode($files);
