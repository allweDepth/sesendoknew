<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/PageSetupService.php';
require_once __DIR__.'/../app/Services/PdfTemplateService.php';

$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
$view=file_get_contents(__DIR__.'/../app/Views/pengaturan/form.php');$js=file_get_contents(__DIR__.'/../public/assets/js/modules/pengaturan.js');$service=file_get_contents(__DIR__.'/../app/Services/PageSetupService.php');$budget=file_get_contents(__DIR__.'/../app/Services/AnggaranDocumentService.php');$template=file_get_contents(__DIR__.'/../app/Services/PdfTemplateService.php');
$ok(str_contains($view,'name="<?= $section ?>_pdf_aktif"')&&str_contains($view,'name="tinggi_<?= $section ?>_mm"')&&str_contains($view,'name="<?= $section ?>_pdf_json"'),'field Page Setup header/footer tersedia');
foreach(['bold','italic','underline','divider'] as $flag)$ok(str_contains($view,'data-section-flag="'.$flag.'"'),'style '.$flag.' tersedia');
$ok(str_contains($view,"['Kiri',25,'L'],['Tengah',50,'C'],['Kanan',25,'R']")&&str_contains($view,'data-column-key="width"'),'lebar tiga kolom header dan footer dapat diatur terpisah');
$ok(str_contains($js,'syncPageSection')&&str_contains($js,'header_pdf_aktif'),'konfigurasi header/footer dikirim saat disimpan');
$ok(str_contains($service,'header_enabled')&&str_contains($service,'PageSetupPdf'),'Page Setup diterapkan pada renderer PDF global');
$ok(str_contains($budget,'$cells[]')&&str_contains($budget,'width="'),'baris hierarki DPPA memakai lebar kolom eksplisit');
$ok(str_contains($template,'renderAssignmentAttachment')&&str_contains($template,"'nama_ditugaskan'"),'nama yang ditugaskan dipindahkan ke lampiran SK');
$ok(str_contains($template,'Pangkat/Gol')&&str_contains($template,'<th width="7%">NO.'),'dua bentuk lampiran SK tersedia');

$_SESSION['user']=['kd_wilayah'=>'1','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_opd'=>'DINAS PUPR','nama_pemda'=>'KABUPATEN PASANGKAYU','nama_wilayah'=>'Pasangkayu'];
$setup=['paper'=>'A4','orientation'=>'P','font'=>'helvetica','font_size'=>10,'margin_left'=>18,'margin_top'=>15,'margin_right'=>16,'margin_bottom'=>18,'margin_header'=>5,'margin_footer'=>6,'header_enabled'=>true,'footer_enabled'=>true,'header_height'=>11,'footer_height'=>9,
 'header'=>['font'=>'times','size'=>9,'color'=>'#123456','bold'=>true,'italic'=>false,'underline'=>true,'divider'=>true,'columns'=>[['text'=>'KIRI HEADER','width'=>22,'align'=>'L'],['text'=>'TENGAH HEADER','width'=>53,'align'=>'C'],['text'=>'KANAN HEADER','width'=>25,'align'=>'R']]],
 'footer'=>['font'=>'helvetica','size'=>8,'color'=>'#222222','bold'=>false,'italic'=>true,'underline'=>false,'divider'=>true,'columns'=>[['text'=>'FOOTER KIRI','width'=>30,'align'=>'L'],['text'=>'{opd}','width'=>40,'align'=>'C'],['text'=>'{page} / {pages}','width'=>30,'align'=>'R']]]];
$pdf=PageSetupService::createPdf($setup,'P');PageSetupService::applyPdf($pdf,$setup);$pdf->AddPage();$pdf->Cell(0,10,'ISI UJI PAGE SETUP',0,1);$headerFile=__DIR__.'/../tmp/pdfs/phase39-header-footer.pdf';$pdf->Output($headerFile,'F');
$ok(is_file($headerFile)&&filesize($headerFile)>1000,'PDF uji header/footer terbentuk');

$header=['jenis_naskah'=>'Naskah Dinas Penetapan','nomor'=>'10 TAHUN 2026','tanggal_surat'=>'5 Januari 2026','perihal'=>'PENGANGKATAN TIM TEKNIS'];
$base=['nama_penandatangan'=>'SYAMSUNAR, SP.,M.M','jabatan_penandatangan'=>'Kepala Dinas','pangkat_penandatangan'=>'Pembina, IV/a','nip_penandatangan'=>'197503102009031001','nama_ditugaskan'=>[['nama'=>'Patahuddin, ST.M.AP','pangkat'=>'Pembina, IV/a','nip'=>'197110122009031001','jabatan'=>'Teknik Jalan dan Jembatan','jabatan_sk'=>'Pembantu Tim Teknis'],['nama'=>'Vilma Erhid Salla, ST','pangkat'=>'Penata Muda Tk.I, III/b','nip'=>'198504172019032010','jabatan'=>'Penelaah Teknis Kebijakan','jabatan_sk'=>'Tim Asistensi']]];
foreach([0=>'vertical',1=>'table'] as $mode=>$label){$doc=PageSetupService::createPdf(array_merge($setup,['header_enabled'=>false,'footer_enabled'=>false]),'P');PageSetupService::applyPdf($doc,array_merge($setup,['header_enabled'=>false,'footer_enabled'=>false]));$doc->AddPage();(new PdfTemplateService($setup))->renderOfficial($doc,$header,[],array_merge($base,['bentuk_lampiran'=>$mode]),null);$file=__DIR__.'/../tmp/pdfs/phase39-sk-'.$label.'.pdf';$doc->Output($file,'F');$ok(is_file($file)&&filesize($file)>1500,'PDF lampiran SK '.$label.' terbentuk');}
echo "PHASE 39 PDF HEADER FOOTER AND SK ATTACHMENT TESTS COMPLETE\n";
