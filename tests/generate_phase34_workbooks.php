<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/AnggaranDocumentService.php';
$db=DB::getInstance();
$scope=$db->query('SELECT kd_wilayah,kd_opd,tahun FROM dpa_neo WHERE is_deleted=0 ORDER BY id LIMIT 1')->fetch();
if(!$scope)throw new RuntimeException('Scope DPA tidak tersedia');
$service=new AnggaranDocumentService($scope+['username'=>'PHASE34-QA','type_user'=>'admin_opd']);
$directory=__DIR__.'/../outputs/01a058f3-6239-7122-bdb7-1ec257ed0d1d';
if(!is_dir($directory)&&!mkdir($directory,0770,true))throw new RuntimeException('Folder output tidak dapat dibuat');
foreach(['dpa'=>'dpa-format-resmi.xlsx','dppa'=>'dppa-format-resmi.xlsx'] as $logical=>$name){$temporary=$service->exportExcel($logical);if(!copy($temporary,$directory.'/'.$name))throw new RuntimeException('Workbook gagal disalin');unlink($temporary);echo $directory.'/'.$name."\n";}
