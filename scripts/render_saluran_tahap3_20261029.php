<?php
/** Generate only the 13 verified Tahap 3 packages using application TCPDF renderers. */
if(PHP_SAPI!=='cli')exit;chdir(dirname(__DIR__));date_default_timezone_set('Asia/Makassar');
spl_autoload_register(function($c){foreach(['app/Core/','app/Services/','app/Models/']as$p)if(is_file($p.$c.'.php')){require_once$p.$c.'.php';return;}});
require_once 'vendor/autoload.php';require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
$user=['id'=>1,'username'=>'TAHAP3_PDF_20261029','type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_opd'=>'Dinas Pekerjaan Umum dan Penataan Ruang','nama_pemda'=>'Pemerintah Kabupaten Pasangkayu'];$_SESSION['user']=$user;
$root='outputs/saluran-tahap3-20261029/pdf';$doc=new ProcurementDocumentService($user);$offer=new ContractOfferPrintService($user);$renderer=new ProcurementFixedPdfService($user);$out=[];
foreach([820,821] as$id){$c=$doc->templates($id)['contract'];$folder=str_replace('/',' - ',$c['uraian_kontrak']);$dir=$root.'/'.$folder;if(!is_dir($dir.'/Sampul'))mkdir($dir.'/Sampul',0775,true);
$files=['01 - Generate Semua Dokumen.pdf'=>$doc->bundlePdf($id,['paper'=>'F4']),'02 - Surat Penawaran dan RAB.pdf'=>$offer->pdf($id,'bundle',['paper'=>'F4'])];
$setup=ProcurementDocumentFormatService::setup(PageSetupService::current($user),$c['print_settings']??[]);$setup['paper']='F4';$pdf=PageSetupService::createPdf($setup,'P');$renderer->cover($pdf,$c,$setup);$files['Sampul/Sampul SPK.pdf']=$pdf->Output('','S');foreach($files as$name=>$bytes)file_put_contents($dir.'/'.$name,$bytes);$out[]=['contract_id'=>$id,'package'=>$c['uraian_kontrak'],'folder'=>$folder,'files'=>array_keys($files)];}
file_put_contents('outputs/saluran-tahap3-20261029/pdf-manifest.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo 'Generated 6 PDFs'.PHP_EOL;
