<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';

$ok=static function($value,$message){if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";};
$db=DB::getInstance();
$columns=$db->query("SHOW COLUMNS FROM group_sub_kegiatan")->fetchAll(PDO::FETCH_COLUMN);
foreach(['output','satuan_output','batas_anggaran'] as $column)$ok(in_array($column,$columns,true),"kolom $column tersedia");

$authorize=(new ReflectionClass(DynamicTableService::class))->getMethod('authorize');
$_SESSION['user']=['type_user'=>'pptk'];
$pptk=new DynamicTableService();
$denied=false;
try{$authorize->invoke($pptk,'add','group_sub_kegiatan');}catch(Throwable $e){$denied=str_contains($e->getMessage(),'Kepala OPD/PA/KPA');}
$ok($denied,'PPTK tidak dapat menambah master subkegiatan');

$_SESSION['user']=['type_user'=>'kepala_opd'];
$head=new DynamicTableService();
$authorize->invoke($head,'add','group_sub_kegiatan');
$ok(true,'Kepala OPD dapat menambah master subkegiatan');

$view=file_get_contents(__DIR__.'/../app/Views/anggaran/sub_kegiatan.php');
$ui=file_get_contents(__DIR__.'/../public/assets/js/config/ui-config.js');
$ok(str_contains($view,'$canManageSubKegiatan'),'tombol tambah/import mengikuti kewenangan role');
foreach(['output','satuan_output','batas_anggaran'] as $field)$ok(str_contains($ui,"name: \"$field\""),"form memuat $field");
echo "PHASE 17 TESTS COMPLETE\n";
