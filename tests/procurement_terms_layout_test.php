<?php
require_once __DIR__.'/../app/Services/ProcurementTermsLayoutService.php';
require_once __DIR__.'/../app/Services/ProcurementFixedPdfService.php';
function termsLayoutCheck(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$rows=ProcurementTermsLayoutService::rows([
 ['format'=>'clause-heading','text'=>'28. Masa Pelaksanaan'],
 ['format'=>'hanging-list','text'=>'28.1 Masa Pelaksanaan dimulai sejak'],
 ['format'=>'body','text'=>'tanggal yang ditetapkan dalam SPMK.'],
 ['format'=>'hanging-list','text'=>'a. Ketentuan pertama.'],
 ['format'=>'hanging-list','text'=>'ii. Ketentuan berikutnya.'],
]);
termsLayoutCheck($rows[0]['kind']==='heading','Judul pasal berubah menjadi daftar');
termsLayoutCheck($rows[1]['marker']==='28.1'&&$rows[1]['text']==='Masa Pelaksanaan dimulai sejak tanggal yang ditetapkan dalam SPMK.','Nomor bertingkat atau kalimat lanjutan rusak');
termsLayoutCheck($rows[2]['indent']===8.0&&$rows[3]['indent']===16.0,'Jenjang inden daftar tidak sesuai');
$recovered=ProcurementTermsLayoutService::rows([
 ['format'=>'clause-heading','text'=>'56. Perlindungan Tenaga Kerja'],
 ['format'=>'clause-heading','text'=>'57. Pemeliharaan Lingkungan'],
 ['format'=>'hanging-list','text'=>'55.4 Lanjutan penanggungan risiko.'],
 ['format'=>'hanging-list','text'=>'56.1 Perlindungan tenaga kerja.'],
 ['format'=>'hanging-list','text'=>'57.1 Pemeliharaan lingkungan.'],
]);
termsLayoutCheck($recovered[0]['marker']==='55.4'&&$recovered[1]['text']==='56. Perlindungan Tenaga Kerja'&&$recovered[3]['text']==='57. Pemeliharaan Lingkungan','Judul pasal menimpa judul lain atau menyela lanjutan pasal sebelumnya');
$c=['id'=>0,'jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','metode_pemilihan'=>'Mini Kompetisi','cara_pengadaan'=>'PENYEDIA','nilai_kontrak'=>2000000000,'uraian_kontrak'=>'Paket Uji Tata Letak','print_settings'=>['terms_variant'=>'MINI_KOMPETISI','line_spacing'=>1.15,'paragraph_after'=>4]];
$setup=['paper'=>'A4','font'=>'helvetica','font_size'=>9,'margin_left'=>18,'margin_right'=>18,'margin_top'=>18,'margin_bottom'=>18];
$renderer=new ProcurementFixedPdfService([]);$pdf=PageSetupService::createPdf($setup,'P');$pdf->AddPage();$pdf->SetFont('helvetica','',12);$pdf->MultiCell(0,8,'HALAMAN PENGANTAR UJI');
foreach(['SSUK','SSKK'] as $code){
 $document=['kode_dokumen'=>$code,'sections'=>[]];
 $standalone=(new ProcurementFixedPdfService([]))->render($document,$c,$setup);
 termsLayoutCheck(str_starts_with($standalone,'%PDF-'),'PDF terpisah gagal');
 $before=$pdf->getNumPages();$renderer->renderInto($pdf,$document,$c,$setup);
 termsLayoutCheck($pdf->getNumPages()>$before,'Dokumen tidak ditambahkan ke bundel');
}
$out=$pdf->Output('','S');termsLayoutCheck(str_starts_with($out,'%PDF-'),'PDF bundel gagal');
file_put_contents(sys_get_temp_dir().'/procurement-terms-layout-test.pdf',$out);
echo "PASS: nomor bertingkat, kalimat lanjutan, jenjang inden, PDF terpisah dan bundel.\n";
