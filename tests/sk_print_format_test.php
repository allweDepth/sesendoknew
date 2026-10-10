<?php
require_once __DIR__.'/../app/Services/PdfService.php';
function verify(bool $value,string $message):void {
  if(!$value)throw new RuntimeException($message);
  echo "PASS: $message\n";
}
class SkHtmlProbe extends TCPDF {
  public string $html='';
  public function writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='') {
    $this->html.=$html;
  }
}
$renderer=new PdfTemplateService();
$header=['jenis_naskah'=>'Naskah Dinas Penetapan','nomor'=>'SK/USER/17','tanggal_surat'=>'2026-10-10','perihal'=>'Judul dari pengguna'];
$data=['nama_penandatangan'=>'Pejabat Pengguna','jabatan_penandatangan'=>'Kepala Unit Pengguna','tempat_ditetapkan'=>'Kota Pengguna',
  'nama_ditugaskan'=>[['nama'=>'Nama & Pengguna','nip'=>'001234567890','pangkat'=>'Golongan pengguna','jabatan'=>'Jabatan pengguna','jabatan_sk'=>'Tugas pengguna']]];
$method=new ReflectionMethod(PdfTemplateService::class,'renderAssignmentAttachment');
foreach([1=>true,0=>false] as $format=>$table){
  $pdf=new SkHtmlProbe();$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->AddPage();
  $method->invoke($renderer,$pdf,$header,array_merge($data,['bentuk_lampiran'=>$format]));
  verify(str_contains($pdf->html,'<thead>')===$table,"Format $format diterapkan");
  foreach(['Nama &amp; Pengguna','001234567890','Tugas pengguna','SK/USER/17','Judul dari pengguna','10 Oktober 2026','Kota Pengguna','PEJABAT PENGGUNA'] as $value)
    verify(str_contains($pdf->html,$value),"$format mempertahankan data: $value");
  verify($pdf->getNumPages()===2,'Lampiran dimulai di halaman baru');
}
$normalize=new ReflectionMethod(PdfTemplateService::class,'decisionRows');
$rows=$normalize->invoke($renderer,[['URAIAN'=>'Dasar dari pengguna','type'=>'alpha','align'=>'right','style'=>['bold']]]);
verify($rows[0]['text']==='Dasar dari pengguna'&&$rows[0]['type']==='alpha'&&$rows[0]['align']==='right'&&$rows[0]['style']===['bold'],'Metadata paragraf dan isi lama dipertahankan');
$scalar=$normalize->invoke($renderer,'Diktum pengguna','paragraph');
verify($scalar[0]['type']==='paragraph'&&$scalar[0]['text']==='Diktum pengguna','Diktum teks tidak mendapat nomor tambahan');
