<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';
require_once __DIR__.'/../app/Services/AnggaranCopyService.php';
require_once __DIR__.'/../app/Services/AnggaranDocumentService.php';

$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
$db=DB::getInstance();
$user=$db->query("SELECT * FROM user_sesendok_biila WHERE disable=0 AND kd_wilayah<>'' AND kd_opd IS NOT NULL AND kd_opd<>'0' ORDER BY tahun DESC,id LIMIT 1")->fetch();
$ok((bool)$user,'pengguna OPD untuk pengujian tersedia dari database');
$_SESSION['user']=$user;

$_POST=['action'=>'dropdown','tbl'=>'kontrak'];
$dynamicService=new DynamicTableService();
$dropdown=json_decode($dynamicService->handle($_POST),true);
$ok(($dropdown['success']??false)===true,'dropdown kontrak dengan JOIN memakai scope yang tidak ambigu');

$scopeMethod=new ReflectionMethod($dynamicService,'resolveAutoFields');
$scoped=$scopeMethod->invoke($dynamicService,'dppa_neo',['tahun'=>'Ballpoint','kd_wilayah'=>'x','kd_opd'=>'y']);
$ok((int)$scoped['tahun']===(int)$user['tahun']&&$scoped['kd_wilayah']===$user['kd_wilayah']&&$scoped['kd_opd']===$user['kd_opd'],'scope tahun, wilayah, dan OPD selalu berasal dari sesi database');

$ruleMethod=new ReflectionMethod($dynamicService,'applyChangeDocumentRules');
$old=['source_id'=>91,'volume'=>'5.0000','komponen'=>'Ballpoint','harga_satuan'=>'5600.0000'];
$changed=$old;$changed['volume']='0.0000';
$args=['dppa_neo',$old,&$changed];$ruleMethod->invokeArgs($dynamicService,$args);
$ok($changed['status_perubahan']==='hapus','volume baris turunan boleh dinolkan');
$blocked=false;$changedIdentity=$old;$changedIdentity['komponen']='Komponen lain';
try{$args=['dppa_neo',$old,&$changedIdentity];$ruleMethod->invokeArgs($dynamicService,$args);}catch(RuntimeException $e){$blocked=true;}
$ok($blocked,'identitas komponen baris turunan tidak dapat diganti');

$detailsService=new AnggaranDocumentService($user);
$groups=$detailsService->groups('dppa');
if($groups){
    $details=$detailsService->details('dppa',(string)$groups[0]['kd_sub_keg']);
    $ok(!$details || array_key_exists('source_id',$details[0]),'rincian DPPA mengirim metadata baris turunan');
}

$dynamic=file_get_contents(__DIR__.'/../app/Services/DynamicTableService.php');
$copy=file_get_contents(__DIR__.'/../app/Services/AnggaranCopyService.php');
$ui=file_get_contents(__DIR__.'/../public/assets/js/modules/anggaran-document.js');
$ok(str_contains($dynamic,"['renja_p_neo','rka_p_neo','dppa_neo']")&&str_contains($dynamic,"'status_perubahan'=>'hapus'")&&str_contains($dynamic,"'volume'=>0"),'dokumen perubahan mempertahankan baris asal dengan volume nol');
$ok(str_contains($dynamic,"'dppa_neo' => [")&&!str_contains($dynamic,"'dpppa_neo' => ["),'validasi DPPA memakai nama tabel yang benar');
$ok(str_contains($copy,"'renja_p:rka_p'")&&str_contains($copy,"'rka_p:dppa'"),'alur Renja Perubahan ke RKA Perubahan lalu DPPA tersedia');
$ok(str_contains($ui,'renja_p: "rka_p"')&&str_contains($ui,'rka_p: "dppa"'),'aksi proses dokumen mengikuti urutan perubahan');
$ok(str_contains($ui,'groupLocked')&&str_contains($ui,'Nolkan volume'),'UI menutup aksi ketika terkunci dan menjelaskan nol volume untuk baris turunan');
echo "PHASE 54 CONTRACT/CHANGE WORKFLOW TESTS COMPLETE\n";
