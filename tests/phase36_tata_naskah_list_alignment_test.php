<?php

$assert=static function(bool $ok,string $message):void{
  if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}
  echo "PASS: $message\n";
};

$builder=file_get_contents(__DIR__.'/../public/assets/js/engine/document/document_builder.js');
$pdfTemplate=file_get_contents(__DIR__.'/../app/Services/PdfTemplateService.php');

foreach(['data-type="paragraph"','data-type="list"','data-type="numbered"','data-type="alpha"'] as $choice){
  $assert(str_contains($builder,$choice),"pilihan format $choice tersedia");
}
foreach(['data-align="left"','data-align="center"','data-align="right"','data-align="justify"'] as $choice){
  $assert(str_contains($builder,$choice),"pilihan alignment $choice tersedia");
}
$assert(str_contains($builder,'this.applyEditorAlignment(editor, align)'),'alignment langsung diterapkan ke editor');
$assert(str_contains($builder,'textAlignLast: value === "justify" ? "left" : "auto"'),'baris terakhir justify tetap rata kiri');
$assert(str_contains($pdfTemplate,"\$sequenceType!==\$type"),'nomor dan huruf berlanjut hanya untuk tipe yang sama');
$assert(str_contains($pdfTemplate,"'bullet'=>'list'"),'alias bullet lama tetap didukung');
$assert(str_contains($pdfTemplate,"\$pdf->Circle("),'bullet PDF digambar tanpa bergantung pada glyph font');
$assert(str_contains($pdfTemplate,"rtrim(\$text).\"\\n\""),'renderer PDF menjaga baris terakhir justify tetap rata kiri');

require_once __DIR__.'/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__.'/../app/Services/PdfTemplateService.php';
$pdf=new TCPDF('P','mm','A4',true,'UTF-8',false);
$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->SetMargins(25,20,20);$pdf->AddPage();
$renderer=new PdfTemplateService(['font'=>'times','font_size'=>11]);
$method=new ReflectionMethod(PdfTemplateService::class,'renderCollection');
$method->invoke($renderer,$pdf,'URAIAN',[
  ['type'=>'list','align'=>'justify','text'=>'Bullet pertama dengan kalimat panjang untuk memeriksa perataan isi paragraf pada hasil PDF.'],
  ['type'=>'list','align'=>'justify','text'=>'Bullet kedua.'],
  ['type'=>'numbered','align'=>'left','text'=>'Nomor pertama.'],
  ['type'=>'numbered','align'=>'left','text'=>'Nomor kedua.'],
  ['type'=>'alpha','align'=>'right','text'=>'Huruf pertama.'],
  ['type'=>'alpha','align'=>'center','text'=>'Huruf kedua.'],
  ['type'=>'paragraph','align'=>'justify','text'=>'Paragraf memutus urutan.'],
  ['type'=>'numbered','align'=>'left','text'=>'Nomor dimulai kembali.'],
],[]);
$out='/private/tmp/phase36-tata-naskah-list-alignment.pdf';
file_put_contents($out,$pdf->Output('','S'));
$assert(is_file($out)&&filesize($out)>1000,'fixture PDF list dan alignment berhasil dibuat');

echo "PDF_TEST=$out\nPHASE 36 TATA NASKAH LIST AND ALIGNMENT TESTS COMPLETE\n";
