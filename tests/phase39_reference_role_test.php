<?php
require_once __DIR__.'/../app/Services/DynamicTableService.php';

function authorizeAs(string $role,string $profile,string $action,string $table):bool
{
    $_SESSION['user']=['id'=>999999,'username'=>'PHASE39_TEST','type_user'=>$role,'kd_wilayah'=>'76.04','kd_opd'=>'1.01.01','tahun'=>(int)date('Y')];
    $service=new DynamicTableService();$reflection=new ReflectionClass($service);
    $property=$reflection->getProperty('activeProfileKey');$property->setValue($service,$profile);
    $method=$reflection->getMethod('authorize');
    try{$method->invoke($service,$action,$table);return true;}catch(Throwable){return false;}
}

$cases=[
    ['tapd','rekening_kegiatan','add','rekening_kegiatan',true],
    ['tapd','ssh','edit','master_biaya',true],
    ['tapd','akun','delete','akun_neo',false],
    ['tapd','organisasi','edit','organisasi_neo',false],
    ['admin_wilayah','organisasi','edit','organisasi_neo',true],
    ['admin_wilayah','rekening_kegiatan','add','rekening_kegiatan',false],
    ['super_admin','wilayah','edit','wilayah_neo',true],
    ['super_admin','organisasi','edit','organisasi_neo',false],
    ['admin_opd','akun','edit','akun_neo',false],
    ['admin_opd','asn','add','db_asn_pemda_neo',true],
    ['kepala_opd','asn','edit','db_asn_pemda_neo',false],
    ['kepala_opd','penugasan_subkegiatan','edit','user_subkegiatan_neo',true],
    ['super_admin','penugasan_subkegiatan','edit','user_subkegiatan_neo',false],
];
foreach($cases as [$role,$profile,$action,$table,$expected])if(authorizeAs($role,$profile,$action,$table)!==$expected)throw new RuntimeException("Otorisasi tidak sesuai: $role $profile $action");
echo "PHASE 39 REFERENCE ROLE TEST PASSED\n";
