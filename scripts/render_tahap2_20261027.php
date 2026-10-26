<?php
if(PHP_SAPI!=='cli')exit;
chdir(dirname(__DIR__));date_default_timezone_set('Asia/Makassar');
spl_autoload_register(function($c){foreach(['app/Core/','app/Services/','app/Models/']as$p)if(is_file($p.$c.'.php')){require_once$p.$c.'.php';return;}});
require_once 'vendor/autoload.php';require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
$user=['id'=>1,'username'=>'TAHAP2_PDF_20261027','type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_opd'=>'Dinas Pekerjaan Umum dan Penataan Ruang','nama_pemda'=>'Pemerintah Kabupaten Pasangkayu'];$_SESSION['user']=$user;
$rows=json_decode(file_get_contents('outputs/tahap2-20261027/hasil-data.json'),true,512,JSON_THROW_ON_ERROR);$doc=new ProcurementDocumentService($user);$offer=new ContractOfferPrintService($user);
$root='/Users/nabiila_inayah/Desktop/DPPA 2026 - Tahap 2 - 27 Oktober 2026';if(!is_dir($root))mkdir($root,0775,true);$out=[];$selected=[];foreach($argv as$arg)if(str_starts_with($arg,'--contracts='))$selected=array_map('intval',explode(',',substr($arg,12)));
foreach($rows as$r){$id=(int)$r['contract_id'];if($selected&&!in_array($id,$selected,true))continue;if(isset($argv[1])&&ctype_digit($argv[1])&&$id!==(int)$argv[1])continue;
 $name=preg_replace('/[\\\\\/:*?"<>|]/u',' - ',$r['package']);$dir=$root.'/'.$name;if(!is_dir($dir))mkdir($dir,0775,true);
 $files=['01 - Generate Semua Dokumen.pdf'=>$doc->bundlePdf($id,['paper'=>'F4']),'02 - Surat Penawaran dan RAB.pdf'=>$offer->pdf($id,'bundle',['paper'=>'F4'])];
 foreach($files as$file=>$bytes){if(!str_starts_with($bytes,'%PDF-'))throw new RuntimeException('Invalid PDF');file_put_contents($dir.'/'.$file,$bytes);}
 $out[]=array_replace($r,['folder'=>$dir,'files'=>array_keys($files)]);echo $id.' '.count($files).' PDF '.$r['package'].PHP_EOL;flush();
}
if($selected){$prior=json_decode(file_get_contents('outputs/tahap2-20261027/hasil-pdf.json'),true);$out=array_merge(array_values(array_filter($prior,fn($r)=>!in_array((int)$r['contract_id'],$selected,true))),$out);}
file_put_contents('outputs/tahap2-20261027/hasil-pdf.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
