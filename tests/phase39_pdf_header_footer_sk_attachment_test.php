<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/PageSetupService.php';
require_once __DIR__.'/../app/Services/PdfTemplateService.php';
require_once __DIR__.'/../vendor/tecnickcom/tcpdf/tcpdf.php';

$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
class SignatureBoundsProbe extends TCPDF {
 public array $signatureCells=[];
 public function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=false,$ln=1,$x=null,$y=null,$reseth=true,$stretch=0,$ishtml=false,$autopadding=true,$maxh=0,$valign='T',$fitcell=false){
  if(is_string($txt)&&str_starts_with($txt,'Ditetapkan di'))$this->signatureCells[]=['x'=>$x??$this->GetX(),'width'=>$w,'align'=>$align];
  return parent::MultiCell($w,$h,$txt,$border,$align,$fill,$ln,$x,$y,$reseth,$stretch,$ishtml,$autopadding,$maxh,$valign,$fitcell);
 }
}
$view=file_get_contents(__DIR__.'/../app/Views/pengaturan/form.php');$js=file_get_contents(__DIR__.'/../public/assets/js/modules/pengaturan.js');$service=file_get_contents(__DIR__.'/../app/Services/PageSetupService.php');$budget=file_get_contents(__DIR__.'/../app/Services/AnggaranDocumentService.php');$template=file_get_contents(__DIR__.'/../app/Services/PdfTemplateService.php');
$ok(str_contains($view,'name="<?= $section ?>_pdf_aktif"')&&str_contains($view,'name="tinggi_<?= $section ?>_mm"')&&str_contains($view,'name="<?= $section ?>_pdf_json"'),'field Page Setup header/footer tersedia');
foreach(['bold','italic','underline','divider'] as $flag)$ok(str_contains($view,'data-section-flag="'.$flag.'"'),'style '.$flag.' tersedia');
$ok(str_contains($view,"['Kiri',25,'L'],['Tengah',50,'C'],['Kanan',25,'R']")&&str_contains($view,'data-column-key="width"'),'lebar tiga kolom header dan footer dapat diatur terpisah');
$ok(str_contains($js,'syncPageSection')&&str_contains($js,'header_pdf_aktif'),'konfigurasi header/footer dikirim saat disimpan');
$ok(str_contains($service,'header_enabled')&&str_contains($service,'PageSetupPdf'),'Page Setup diterapkan pada renderer PDF global');
$ok(str_contains($budget,'$cells[]')&&str_contains($budget,'width="'),'baris hierarki DPPA memakai lebar kolom eksplisit');
$ok(str_contains($template,'renderAssignmentAttachment')&&str_contains($template,"'nama_ditugaskan'"),'nama yang ditugaskan dipindahkan ke lampiran SK');
$ok(str_contains($template,'Pangkat/Gol')&&str_contains($template,'<th width="7%">NO.'),'dua bentuk lampiran SK tersedia');
$pageSetupForm=file_get_contents(__DIR__.'/../app/Views/pengaturan/form.php');$pageSetupService=file_get_contents(__DIR__.'/../app/Services/PageSetupService.php');$controller=file_get_contents(__DIR__.'/../app/Controllers/PengaturanController.php');
$ok(str_contains($pageSetupForm,'name="alignment_tanda_tangan"')&&str_contains($pageSetupForm,'value="J">Rata kiri-kanan'),'dropdown alignment tanda tangan tersedia');
$ok(str_contains($pageSetupService,"'signature_alignment'=>\$row['alignment_tanda_tangan']??'C'"),'alignment tanda tangan dibaca dari Page Setup');
$ok(str_contains($controller,"'alignment_tanda_tangan'")&&str_contains($controller,"['L','C','R','J']"),'alignment tanda tangan divalidasi dan disimpan');

$_SESSION['user']=['kd_wilayah'=>'1','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_opd'=>'DINAS PUPR','nama_pemda'=>'KABUPATEN PASANGKAYU','nama_wilayah'=>'Pasangkayu'];
$setup=['paper'=>'A4','orientation'=>'P','font'=>'helvetica','font_size'=>10,'margin_left'=>18,'margin_top'=>15,'margin_right'=>16,'margin_bottom'=>18,'margin_header'=>5,'margin_footer'=>6,'header_enabled'=>true,'footer_enabled'=>true,'header_height'=>11,'footer_height'=>9,
 'header'=>['font'=>'times','size'=>9,'color'=>'#123456','bold'=>true,'italic'=>false,'underline'=>true,'divider'=>true,'columns'=>[['text'=>'KIRI HEADER','width'=>22,'align'=>'L'],['text'=>'TENGAH HEADER','width'=>53,'align'=>'C'],['text'=>'KANAN HEADER','width'=>25,'align'=>'R']]],
 'footer'=>['font'=>'helvetica','size'=>8,'color'=>'#222222','bold'=>false,'italic'=>true,'underline'=>false,'divider'=>true,'columns'=>[['text'=>'FOOTER KIRI','width'=>30,'align'=>'L'],['text'=>'{opd}','width'=>40,'align'=>'C'],['text'=>'{page} / {pages}','width'=>30,'align'=>'R']]]];
$pdf=PageSetupService::createPdf($setup,'P');PageSetupService::applyPdf($pdf,$setup);$pdf->AddPage();$pdf->Cell(0,10,'ISI UJI PAGE SETUP',0,1);$headerFile=__DIR__.'/../tmp/pdfs/phase39-header-footer.pdf';$pdf->Output($headerFile,'F');
$ok(is_file($headerFile)&&filesize($headerFile)>1000,'PDF uji header/footer terbentuk');

$header=['jenis_naskah'=>'Naskah Dinas Penetapan','nomor'=>'10 TAHUN 2026','tanggal_surat'=>'5 Januari 2026','perihal'=>'PENGANGKATAN TIM TEKNIS'];
$base=['nama_penandatangan'=>'SYAMSUNAR, SP.,M.M','jabatan_penandatangan'=>'Kepala Dinas','pangkat_penandatangan'=>'Pembina, IV/a','nip_penandatangan'=>'197503102009031001','nama_ditugaskan'=>[['nama'=>'Patahuddin, ST.M.AP','pangkat'=>'Pembina, IV/a','nip'=>'197110122009031001','jabatan'=>'Teknik Jalan dan Jembatan','jabatan_sk'=>'Pembantu Tim Teknis'],['nama'=>'Vilma Erhid Salla, ST','pangkat'=>'Penata Muda Tk.I, III/b','nip'=>'198504172019032010','jabatan'=>'Penelaah Teknis Kebijakan','jabatan_sk'=>'Tim Asistensi']]];
foreach([0=>'vertical',1=>'table'] as $mode=>$label){$doc=PageSetupService::createPdf(array_merge($setup,['header_enabled'=>false,'footer_enabled'=>false]),'P');PageSetupService::applyPdf($doc,array_merge($setup,['header_enabled'=>false,'footer_enabled'=>false]));$doc->AddPage();(new PdfTemplateService($setup))->renderOfficial($doc,$header,[],array_merge($base,['bentuk_lampiran'=>$mode]),null);$file=__DIR__.'/../tmp/pdfs/phase39-sk-'.$label.'.pdf';$doc->Output($file,'F');$ok(is_file($file)&&filesize($file)>1500,'PDF lampiran SK '.$label.' terbentuk');}
$narrowSetup=array_merge($setup,['paper'=>'CUSTOM','paper_width'=>89,'paper_height'=>127,'margin_left'=>12,'margin_right'=>12,'header_enabled'=>false,'footer_enabled'=>false]);
$narrowPdf=PageSetupService::createPdf($narrowSetup,'P');PageSetupService::applyPdf($narrowPdf,$narrowSetup);$narrowPdf->AddPage();
$pageBoxMethod=new ReflectionMethod(PdfTemplateService::class,'pageBox');$signatureBoxMethod=new ReflectionMethod(PdfTemplateService::class,'signatureBoxFromPageBox');
$narrowRenderer=new PdfTemplateService($narrowSetup);$pageBox=$pageBoxMethod->invoke($narrowRenderer,$narrowPdf);
$ok(abs($pageBox['width']-65)<0.01,'lebar cetak dihitung dari ukuran kertas khusus dan margin');
foreach(['kiri','tengah','kanan'] as $position){$signatureBox=$signatureBoxMethod->invoke($narrowRenderer,$pageBox,$position);$ok($signatureBox['x']>=$pageBox['left']&&$signatureBox['x']+$signatureBox['width']<=$pageBox['right']+0.01,'tanda tangan '.$position.' tetap di dalam batas cetak kertas kecil');}
$signaturePdf=new SignatureBoundsProbe('P','mm',[89,127],true,'UTF-8',false);$signaturePdf->setPrintHeader(false);$signaturePdf->setPrintFooter(false);$signaturePdf->SetMargins(12,12,12);$signaturePdf->AddPage();
foreach(['kanan','kiri'] as $position){
 $signaturePdf=new SignatureBoundsProbe('P','mm',[89,127],true,'UTF-8',false);$signaturePdf->setPrintHeader(false);$signaturePdf->setPrintFooter(false);$signaturePdf->SetMargins(12,12,12);$signaturePdf->AddPage();
 foreach(['L','C','R','J'] as $alignment){
  $signatureRenderer=new PdfTemplateService(array_merge($narrowSetup,['signature_position'=>$position,'signature_alignment'=>$alignment]));$signatureMethod=new ReflectionMethod(PdfTemplateService::class,'renderAttachmentSignature');
  $signatureMethod->invoke($signatureRenderer,$signaturePdf,array_merge($base,['tempat_ditetapkan'=>'Pasangkayu']),$header);
  $signatureCell=$signaturePdf->signatureCells[array_key_last($signaturePdf->signatureCells)]??[];$ok(($signatureCell['x']??0)>=12&&($signatureCell['x']??0)+($signatureCell['width']??0)<=77.01,'sel tanda tangan SK posisi '.$position.' tetap di dalam margin');
  $ok(($signatureCell['align']??'')===$alignment,'alignment '.$alignment.' diterapkan pada blok '.$position.' tanpa mengubah posisinya');
 }
}
$narrowRenderer->renderOfficial($narrowPdf,['jenis_naskah'=>'Surat Biasa','nomor'=>'UJI/1','tanggal_surat'=>'2026-01-05'],[],['nama_penandatangan'=>'SYAMSUNAR','jabatan_penandatangan'=>'Kepala Dinas','nip_penandatangan'=>'197503102009031001']);
$narrowOutput=$narrowPdf->Output('','S');$ok(str_starts_with($narrowOutput,'%PDF-'),'PDF naskah berhasil dirender pada kertas khusus sempit');
echo "PHASE 39 PDF HEADER FOOTER AND SK ATTACHMENT TESTS COMPLETE\n";
