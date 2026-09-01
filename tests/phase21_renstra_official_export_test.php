<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/RenstraExportService.php';
require_once __DIR__.'/../app/Services/KontrakRealisasiService.php';
require_once __DIR__.'/../vendor/autoload.php';
$ok=static function(bool $v,string $m):void{if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$db=DB::getInstance();$scope=$db->query('SELECT kd_wilayah,kd_opd FROM renstra_neo WHERE is_deleted=0 ORDER BY id LIMIT 1')->fetch();$ok((bool)$scope,'scope Renstra tersedia');$user=$scope+['tahun'=>(int)date('Y'),'type_user'=>'admin_opd','username'=>'PHASE21'];
$service=new RenstraExportService($user);$header=$service->header();$ok(!empty($header['visi']),'visi Renstra termuat');$ok(count($service->strategyRows((int)$header['id']))>0,'hierarki tujuan dan sasaran termuat');$ok(count($service->programRows((int)$header['id']))>0,'hierarki program dan pendanaan termuat');
$pdf=$service->pdf();$ok(str_starts_with($pdf,'%PDF-')&&strlen($pdf)>5000,'PDF Renstra T-C.25 dan T-C.27 valid');
$file=$service->excel();$book=PhpOffice\PhpSpreadsheet\IOFactory::load($file);$ok($book->getSheetCount()===2,'Excel Renstra memiliki sheet T-C.25 dan T-C.27');$ok(str_contains($book->getSheet(0)->getTitle(),'TC-25'),'sheet tujuan dan sasaran tersedia');$ok(str_contains($book->getSheet(1)->getTitle(),'TC-27'),'sheet program dan pendanaan tersedia');unlink($file);
$routes=file_get_contents(__DIR__.'/../routes/web.php');$ui=file_get_contents(__DIR__.'/../public/assets/js/modules/renstra.js');$ok(str_contains($routes,"'/renstra/export_pdf'")&&str_contains($routes,"'/renstra/export_excel'"),'route export Renstra tersedia');$ok(str_contains($ui,'data-renstra-export="excel"')&&str_contains($ui,'data-renstra-export="pdf"'),'tombol export Renstra tersedia');
$financial=new KontrakRealisasiService($user);foreach(['spj','lra'] as $format){$ok(str_starts_with($financial->financialPdf($format),'%PDF-'),"PDF $format tetap valid");$f=$financial->financialExcel($format);$ok(is_file($f)&&filesize($f)>1000,"Excel $format tetap valid");unlink($f);}echo "PHASE 21 TESTS COMPLETE\n";
