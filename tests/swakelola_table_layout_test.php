<?php
require_once __DIR__.'/../app/Services/SwakelolaDocumentService.php';
$catalog=SwakelolaDocumentService::catalog();
foreach($catalog['templates'] as $template){
    $blocks=SwakelolaDocumentService::normalizeLayout($template['blocks']);
    SwakelolaDocumentService::validate($blocks);
    if(SwakelolaDocumentService::normalizeLayout($blocks)!=$blocks)throw new RuntimeException('Layout tidak stabil: '.$template['code']);
    SwakelolaDocumentService::html($blocks);
}
$sections=json_decode(file_get_contents(__DIR__.'/../outputs/swakelola-editor-20261101/sections.json'),true,512,JSON_THROW_ON_ERROR);
$blocks=json_decode($sections[0]['isi'],true,512,JSON_THROW_ON_ERROR);
$html=SwakelolaDocumentService::html($blocks);
if(!str_contains($html,'border="0"')||!str_contains($html,'Isi yang harus tetap tersimpan'))throw new RuntimeException('Layout/isi hasil editor hilang');
if(in_array('--database',$argv,true)){
    require_once __DIR__.'/../app/Core/DB.php';$db=DB::getInstance();$db->begin();
    try{
        $part=$db->query("SELECT p.id FROM dokumen_pengadaan_bagian_neo p JOIN dokumen_pengadaan_neo d ON d.id=p.dokumen_id JOIN master_dokumen_pengadaan_neo m ON m.id=d.master_id WHERE m.kode LIKE 'SW\\_%' LIMIT 1")->fetch();
        if(!$part)throw new RuntimeException('Tidak ada bagian Swakelola untuk uji rollback');
        SwakelolaDocumentService::storeBlocks($db,'dokumen_bagian_id',(int)$part['id'],$blocks);
        if(SwakelolaDocumentService::loadBlocks($db,'dokumen_bagian_id',(int)$part['id'])!=$blocks)throw new RuntimeException('Metadata lebar/struktur berubah setelah disimpan');
    }finally{$db->rollback();}
    echo "PASS: database block roundtrip, rolled back\n";
}
if(in_array('--pdf',$argv,true)){
    require_once __DIR__.'/../vendor/autoload.php';
    $pdf=new TCPDF('P','mm','A4',true,'UTF-8');$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->SetMargins(15,15,15);$pdf->SetAutoPageBreak(true,15);$pdf->SetFont('helvetica','',11);$pdf->AddPage();$pdf->writeHTML($html,true,false,true,false,'');
    $pdf->Output(__DIR__.'/../outputs/swakelola-editor-20261101/layout.pdf','F');
}
echo 'PASS: '.count($catalog['templates'])." master, normalisasi stabil, struktur editor dan HTML TCPDF\n";
