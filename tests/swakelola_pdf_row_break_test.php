<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Services/SwakelolaDocumentService.php';
function paragraph(string $text):array{return ['type'=>'paragraph','text'=>$text];}
function rowPdf(array $row,string $path):void{
    $pdf=new TCPDF('P','mm','A4',true,'UTF-8');$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->SetMargins(15,15,15);$pdf->SetAutoPageBreak(true,15);$pdf->SetFont('helvetica','',10);$pdf->AddPage();$pdf->SetY(255);
    $pdf->writeHTML(SwakelolaDocumentService::html([['type'=>'table','column_widths'=>[50,50],'rows'=>[$row]]]),true,false,true,false,'');
    $pdf->Output($path,'F');
}
$directory=$argv[1]??sys_get_temp_dir();
$normal=[['blocks'=>[paragraph('11. Proposal - ROW_TITLE')]],['blocks'=>array_map('paragraph',['Proposal terdiri dari:','a. Latar belakang','b. Metodologi pelaksanaan','c. Rencana penggunaan personel','d. Rencana jadwal pelaksanaan','e. Mitigasi risiko dan evaluasi','f. Pelaporan - ROW_END'])]];
rowPdf($normal,$directory.'/row-keep-together.pdf');
$long=[['blocks'=>[paragraph('LONG_ROW_TITLE')]],['blocks'=>array_map(fn($i)=>paragraph(sprintf('LONG_LINE_%03d Informasi pekerjaan yang tetap harus tercetak lengkap.',$i)),range(1,110))]];
rowPdf($long,$directory.'/row-oversized.pdf');
echo "Generated normal and oversized row fixtures.\n";
