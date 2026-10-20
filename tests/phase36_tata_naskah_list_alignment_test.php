<?php

$assert=static function(bool $ok,string $message):void{
  if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}
  echo "PASS: $message\n";
};

$builder=file_get_contents(__DIR__.'/../public/assets/js/engine/document/document_builder.js');
$pdfTemplate=file_get_contents(__DIR__.'/../app/Services/PdfTemplateService.php');
$migration=file_get_contents(__DIR__.'/../database/migrations/20261005_phase62_tata_naskah_row_format.php');

foreach(["paragraph: '<i class=\"align left icon\"></i>'","list: '<i class=\"list ul icon\"></i>'","numbered: '<i class=\"list ol icon\"></i>'","alpha: '<span aria-hidden=\"true\">a.</span>'"] as $choice){
  $assert(str_contains($builder,$choice),"pilihan format $choice tersedia");
}
foreach(["left: '<i class=\"align left icon\"></i>'","center: '<i class=\"align center icon\"></i>'","right: '<i class=\"align right icon\"></i>'","justify: '<i class=\"align justify icon\"></i>'"] as $choice){
  $assert(str_contains($builder,$choice),"pilihan alignment $choice tersedia");
}
$assert(str_contains($builder,'field.row_options'),'toolbar membaca konfigurasi field dari schema database');
$assert(str_contains($builder,'options.default_type'),'baris baru mengikuti format default pada schema');
$assert(str_contains($builder,'options.default_align'),'perataan default mengikuti schema');
$assert(str_contains($builder,'scrollContent.scrollTop(scrollTop)'),'posisi scroll modal dipulihkan setelah tambah baris');
$assert(!str_contains($builder,'table[data-type="editable_table"]'),'dropdown tidak dialihkan ke tabel editable pertama');
$assert(str_contains($builder,'window.Toast?.error?.(message)'),'kesalahan target dropdown disampaikan ke pengguna');
$assert(str_contains($builder,'.text(value ?? "")'),'data dropdown dimasukkan sebagai teks, bukan HTML');
$assert(str_contains($migration,"'menimbang', 'mengingat', 'tembusan' => 'numbered'"),'bagian koleksi yang sesuai menggunakan format bernomor pada schema non-SK');
$assert(str_contains($migration,"'row_options'"),'migrasi menyimpan pengaturan toolbar di schema database');
$assert(str_contains($migration,"strtolower((string)\$template['kode_form']) === 'sk'"),'migrasi tidak menerapkan opsi format ke jenis SK');
$assert(str_contains($migration,"unset(\$field['row_options'])"),'migrasi menghapus opsi format yang sebelumnya terpasang pada schema SK');
$assert(str_contains($migration,"\$field['row_options']['hanging_indent'] = true"),'schema non-SK mengaktifkan perataan gantung untuk daftar');
$assert(str_contains($migration,'DELETE FROM cache_schema_naskah'),'migrasi membersihkan cache schema lama');
$assert(str_contains($migration,"rtrim(\$rawSchema).']'"),'migrasi memperbaiki schema lama dengan kurung penutup hilang');
$assert(str_contains($migration,'array_push($flatSchema, ...$item)'),'migrasi meratakan grup field lama yang bersarang');
$assert(!str_contains($builder,'applyEditorAlignment'),'alignment tidak mengubah tampilan input');
$assert(!str_contains($builder,'style="text-align:'),'editor menampilkan ketikan sebagai teks mentah');
$assert(str_contains($builder,'removeAttr("style")'),'style lama dibersihkan dari text field plain');
$assert(str_contains($pdfTemplate,"\$sequenceType!==\$type"),'nomor dan huruf berlanjut hanya untuk tipe yang sama');
$assert(str_contains($pdfTemplate,"'bullet'=>'list'"),'alias bullet lama tetap didukung');
$assert(str_contains($pdfTemplate,"\$pdf->Circle("),'bullet PDF digambar tanpa bergantung pada glyph font');
$assert(str_contains($pdfTemplate,'$pdf->MultiCell(0,6,$renderText,0,$align'),'alignment metadata diterapkan oleh renderer PDF');
$assert(str_contains($pdfTemplate,'rtrim($text)."\\n"'),'baris terakhir justify tetap rata kiri');
$assert(str_contains($pdfTemplate,'$options=is_array($item[\'row_options\']??null)'),'renderer koleksi menerima opsi format dari schema');
$assert(str_contains($pdfTemplate,'$row[\'type\']??$defaultType'),'baris lama memakai format default schema');
$assert(str_contains($pdfTemplate,'schemaRowOptions($schema,$key)'),'bagian SK memakai konfigurasi schema saat dicetak');
$assert(str_contains($pdfTemplate,"\$hangingIndent?'R':'L'"),'nomor daftar non-SK dirapatkan ke kolom teks');
$assert(str_contains($pdfTemplate,'$pdf->Circle($x+($hangingIndent?4:2.2),$y+3'),'bullet non-SK disejajarkan dalam kolom penanda tanpa mengubah posisi SK');

require_once __DIR__.'/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__.'/../app/Services/PdfTemplateService.php';
require_once __DIR__.'/../app/Services/PdfService.php';
$serviceReflection=new ReflectionClass(PdfService::class);
$service=$serviceReflection->newInstanceWithoutConstructor();
$fallbackMethod=$serviceReflection->getMethod('fallbackSchema');
$fallback=$fallbackMethod->invoke($service,['isi'=>[['type'=>'list','align'=>'justify','text'=>'Butir']]]);
$assert(($fallback[0]['type']??'')==='editable_table','fallback schema mempertahankan koleksi paragraf untuk renderer PDF');
$assert(($fallback[0]['columns']??[])===['text'],'fallback schema tidak mencampur metadata ke kolom isi');
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

class SkRowOptionsProbe extends TCPDF {
  public array $cells=[];
  public array $cellDetails=[];
  public array $multiCells=[];
  public function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=false,$link='',$stretch=0,$ignore_min_height=false,$calign='T',$valign='M'){
    $this->cells[]=(string)$txt;
    $this->cellDetails[]=['text'=>(string)$txt,'x'=>$this->GetX(),'y'=>$this->GetY(),'width'=>$w,'align'=>$align];
    parent::Cell($w,$h,$txt,$border,$ln,$align,$fill,$link,$stretch,$ignore_min_height,$calign,$valign);
  }
  public function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=false,$ln=1,$x=null,$y=null,$reseth=true,$stretch=0,$ishtml=false,$autopadding=true,$maxh=0,$valign='T',$fitcell=false){
    $this->multiCells[]=['text'=>(string)$txt,'align'=>$align,'x'=>$x!==null&&$x!==''?$x:$this->GetX()];
    parent::MultiCell($w,$h,$txt,$border,$align,$fill,$ln,$x,$y,$reseth,$stretch,$ishtml,$autopadding,$maxh,$valign,$fitcell);
  }
}
$listProbe=new SkRowOptionsProbe('P','mm','A4',true,'UTF-8',false);
$listProbe->setPrintHeader(false);$listProbe->setPrintFooter(false);$listProbe->SetMargins(25,20,20);$listProbe->AddPage();
$method->invoke($renderer,$listProbe,'URAIAN',[['text'=>'Baris tanpa metadata']],[],['default_type'=>'numbered','default_align'=>'left']);
$assert(in_array('1.',$listProbe->cells,true),'format default schema menomori baris lama');
$assert(($listProbe->multiCells[0]['align']??'')==='L','perataan default schema diterapkan pada PDF');
$hangingProbe=new SkRowOptionsProbe('P','mm','A4',true,'UTF-8',false);
$hangingProbe->setPrintHeader(false);$hangingProbe->setPrintFooter(false);$hangingProbe->SetMargins(25,20,20);$hangingProbe->AddPage();
$method->invoke($renderer,$hangingProbe,'URAIAN',[
  ['type'=>'list','text'=>'Bullet text starts after its marker.'],
  ['type'=>'numbered','text'=>'Numbered text starts after its marker.'],
],[],['hanging_indent'=>true,'default_align'=>'left']);
$numberMarkerCells=array_values(array_filter($hangingProbe->cellDetails,static fn(array $cell):bool=>$cell['text']==='1.'));
$assert(($hangingProbe->multiCells[0]['x']??null)===33.0,'teks bullet dimulai setelah gutter penanda 8 mm');
$assert(($numberMarkerCells[0]['align']??'')==='R','nomor rata kanan ke tepi gutter sebelum teks');
$assert(($hangingProbe->multiCells[1]['x']??null)===33.0,'teks bernomor menggunakan awal kolom yang sama dengan bullet');

echo "PDF_TEST=$out\nPHASE 36 TATA NASKAH LIST AND ALIGNMENT TESTS COMPLETE\n";
