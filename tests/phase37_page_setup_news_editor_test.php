<?php

$assert=static function(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";};
$sidebar=file_get_contents(__DIR__.'/../app/Views/partials/sidebar.php');
$settings=file_get_contents(__DIR__.'/../app/Views/pengaturan/form.php');
$service=file_get_contents(__DIR__.'/../app/Services/PageSetupService.php');
$editor=file_get_contents(__DIR__.'/../public/assets/js/ui/rich-document-editor.js');
$news=file_get_contents(__DIR__.'/../public/assets/js/modules/halaman_berita.js');
$layout=file_get_contents(__DIR__.'/../app/Views/layouts/app.php');

$assert(!str_contains($sidebar,'href="/pengaturan#page-setup"'),'menu Page Setup duplikat di sidebar dihapus');
$assert(str_contains($settings,'Page Setup PDF')&&!str_contains($settings,'Simpan Page Setup</button>'),'Page Setup berada di Pengaturan dan khusus PDF');
foreach(['A3PLUS','A4','A5','A6','F4','LEGAL','LETTER','INDIAN_LEGAL','JIS_B4','JIS_B5','C4','C6','DL','CUSTOM'] as $paper)$assert(str_contains($settings,'value="'.$paper.'"'),"ukuran kertas $paper tersedia");
foreach(['margin_atas_mm','margin_kanan_mm','margin_bawah_mm','margin_kiri_mm','margin_header_mm','margin_footer_mm','lebar_kertas_mm','tinggi_kertas_mm'] as $field)$assert(str_contains($settings,'name="'.$field.'"'),"pengaturan $field tersedia");
$assert(str_contains($service,'Page Setup global sengaja hanya memengaruhi PDF'),'pengaturan Excel tidak dipengaruhi Page Setup global');
$assert(str_contains($layout,'rich-document-editor.js'),'editor global dimuat aplikasi');
foreach(['data-insert="table"','data-insert="chart"','data-insert="image"','data-insert="shape"','data-table-size="rows"','data-table-size="cols"','data-rde-wrap','data-style="columnCount"'] as $feature)$assert(str_contains($editor,$feature),"editor global memiliki $feature");
$assert(str_contains($news,'Kembali ke Tabel Halaman Berita')||str_contains($editor,'Kembali ke Tabel Halaman Berita'),'editor berita memiliki navigasi kembali');
$assert(str_contains($news,'class="news-editor-workspace"')&&!str_contains($news,'inspectorContainer:".sidebarkanan #form_flyout"'),'editor berita memakai workspace dan inspector khusus');
$assert(str_contains($news,'{mode:"update"}'),'edit berita mengirim mode update yang diwajibkan backend');

require_once __DIR__.'/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__.'/../app/Services/PageSetupService.php';
$config=['paper'=>'CUSTOM','paper_width'=>216,'paper_height'=>330,'orientation'=>'P','font'=>'helvetica','font_size'=>10,'margin_left'=>17,'margin_top'=>19,'margin_right'=>13,'margin_bottom'=>21,'margin_header'=>6,'margin_footer'=>9];
$pdf=new TCPDF(PageSetupService::orientation($config),'mm',PageSetupService::tcpdfFormat($config),true,'UTF-8',false);
PageSetupService::applyPdf($pdf,$config);$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->AddPage();$pdf->Cell(0,8,'Page Setup PDF global',0,1);
$dims=$pdf->getPageDimensions();
$assert(abs($dims['wk']-216)<0.2&&abs($dims['hk']-330)<0.2,'ukuran custom 216 x 330 mm diterapkan');
$assert(abs($pdf->getMargins()['left']-17)<0.1&&abs($pdf->getMargins()['right']-13)<0.1,'margin custom diterapkan');
$out='/private/tmp/phase37-page-setup.pdf';file_put_contents($out,$pdf->Output('','S'));
$assert(is_file($out)&&filesize($out)>1000,'fixture PDF Page Setup berhasil dibuat');
echo "PDF_TEST=$out\nPHASE 37 PAGE SETUP AND NEWS EDITOR TESTS COMPLETE\n";
