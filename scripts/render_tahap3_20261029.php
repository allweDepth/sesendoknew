<?php
/** Generate only the 13 verified Tahap 3 packages using application TCPDF renderers. */
if(PHP_SAPI!=='cli')exit;chdir(dirname(__DIR__));date_default_timezone_set('Asia/Makassar');
spl_autoload_register(function($c){foreach(['app/Core/','app/Services/','app/Models/']as$p)if(is_file($p.$c.'.php')){require_once$p.$c.'.php';return;}});
require_once 'vendor/autoload.php';require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
$user=['id'=>1,'username'=>'TAHAP3_PDF_20261029','type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_opd'=>'Dinas Pekerjaan Umum dan Penataan Ruang','nama_pemda'=>'Pemerintah Kabupaten Pasangkayu'];$_SESSION['user']=$user;
$root='outputs/tahap3-20261028/pdf';$rows=json_decode(file_get_contents('outputs/tahap3-20261028/hasil-data.json'),true,512,JSON_THROW_ON_ERROR);$doc=new ProcurementDocumentService($user);$offer=new ContractOfferPrintService($user);$renderer=new ProcurementFixedPdfService($user);$out=[];
foreach($rows as$r){if($r['status']!=='ready')continue;$id=(int)$r['contract_id'];$dir=$root.'/'.$r['folder'];if(!is_dir($dir.'/Sampul'))mkdir($dir.'/Sampul',0775,true);$start=microtime(true);
$c=$doc->templates($id)['contract'];if(abs((float)$c['nilai_kontrak']-$r['offer'])>.011||(float)$c['nilai_kontrak']>$r['budget'])throw new RuntimeException('Contract/RAB mismatch '.$id);
$files=['01 - Generate Semua Dokumen.pdf'=>$doc->bundlePdf($id,['paper'=>'F4']),'02 - Surat Penawaran dan RAB.pdf'=>$offer->pdf($id,'bundle',['paper'=>'F4'])];
$setup=ProcurementDocumentFormatService::setup(PageSetupService::current($user),$c['print_settings']??[]);$setup['paper']='F4';$pdf=PageSetupService::createPdf($setup,'P');$pdf->SetTitle('Sampul SPK - '.$c['uraian_kontrak']);$renderer->cover($pdf,$c,$setup);$files['Sampul/Sampul SPK.pdf']=$pdf->Output('','S');
foreach($files as$name=>$bytes){if(!str_starts_with($bytes,'%PDF-'))throw new RuntimeException('Invalid PDF '.$id);file_put_contents($dir.'/'.$name,$bytes);}
$out[]=['contract_id'=>$id,'package'=>$r['package'],'company'=>$r['company'],'folder'=>$r['folder'],'files'=>array_keys($files),'offer'=>$r['offer'],'seconds'=>round(microtime(true)-$start,2)];file_put_contents('outputs/tahap3-20261028/hasil-pdf.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));echo $id.' | '.count($files).' PDF | '.end($out)['seconds'].'s | '.$r['package'].PHP_EOL;flush();
}if(count($out)!==13)throw new RuntimeException('Expected 13 completed packages');
