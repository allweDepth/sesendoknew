<?php
require_once __DIR__.'/../app/Core/Auth.php';
require_once __DIR__.'/../app/Services/AnggaranDocumentService.php';

function expectFailure(callable $callback,string $contains):void
{
    try{$callback();}catch(Throwable $e){if(str_contains($e->getMessage(),$contains))return;throw new RuntimeException("Pesan penolakan tidak sesuai: {$e->getMessage()}");}
    throw new RuntimeException("Operasi seharusnya ditolak: $contains");
}

$base=['id'=>999999,'username'=>'PHASE38_TEST','kd_wilayah'=>'76.04','kd_opd'=>'0','tahun'=>(int)date('Y')];

$_SESSION['user']=$base+['type_user'=>'tapd'];
unset($_SESSION['scope_kd_opd'],$_SESSION['scope_kd_wilayah']);
$tapd=new AnggaranDocumentService($_SESSION['user']);
expectFailure(fn()=>$tapd->saveMonthlyPlan('dpa',1,[]),'akses baca');
expectFailure(fn()=>$tapd->setApproval('dpa','TEST',true),'Pilih satu OPD');

$_SESSION['user']=$base+['type_user'=>'viewer'];
$viewer=new AnggaranDocumentService($_SESSION['user']);
expectFailure(fn()=>$viewer->setApproval('dpa','TEST',true),'tidak memiliki kewenangan');

$matrix=require __DIR__.'/../app/Config/role_matrix.php';
if(in_array('edit',$matrix['tapd']['actions'],true)||!in_array('approve',$matrix['tapd']['actions'],true))throw new RuntimeException('Matriks TAPD tidak sesuai');
if(in_array('approve',$matrix['admin_opd']['actions'],true))throw new RuntimeException('Admin OPD tidak boleh menyetujui');

$db=DB::getInstance();
$sample=$db->query("SELECT kd_wilayah,kd_opd,tahun,kd_sub_keg,MAX(setujui) setujui FROM dpa_neo WHERE is_deleted=0 AND kd_sub_keg<>'' GROUP BY kd_wilayah,kd_opd,tahun,kd_sub_keg LIMIT 1")->fetch();
if($sample){
    $_SESSION['user']=array_merge($base,['type_user'=>'tapd','kd_wilayah'=>$sample['kd_wilayah'],'tahun'=>(int)$sample['tahun']]);
    $_SESSION['scope_kd_wilayah']=$sample['kd_wilayah'];$_SESSION['scope_kd_opd']=$sample['kd_opd'];
    $db->begin();
    try{(new AnggaranDocumentService($_SESSION['user']))->setApproval('dpa',$sample['kd_sub_keg'],!(bool)$sample['setujui']);}
    finally{$db->rollback();}
}

echo "PHASE 38 ROLE SCOPE TEST PASSED\n";
