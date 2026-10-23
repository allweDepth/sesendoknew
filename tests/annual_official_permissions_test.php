<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';
function serviceAs(string $role, string $opd='1.03.0.00.0.00.01.0000'): DynamicTableService {
    $_SESSION['user']=['type_user'=>$role,'username'=>'test_annual_permissions','kd_wilayah'=>'76.01','kd_opd'=>$opd,'tahun'=>2026];
    unset($_SESSION['scope_kd_wilayah']);
    $_SESSION['scope_kd_opd']=$opd;
    $s=new DynamicTableService();
    (new ReflectionProperty($s,'activeProfileKey'))->setValue($s,'pejabat_tahunan');
    return $s;
}
function must(bool $value,string $message): void {if(!$value)throw new RuntimeException($message);}
$authorize=new ReflectionMethod(DynamicTableService::class,'authorize');
foreach(['admin_wilayah','admin_opd','kepala_opd','pa_kpa','super_admin','tapd','ppk','pptk','staf_opd','viewer'] as $role) {
    foreach(['add','edit','delete'] as $action) {
        $allowed=true;
        try {$authorize->invoke(serviceAs($role),$action,'pejabat_tahunan_neo');} catch(Throwable) {$allowed=false;}
        must($allowed===in_array($role,['admin_wilayah','admin_opd','kepala_opd','pa_kpa'],true),"Incorrect permission $role $action");
    }
}
foreach(['','0'] as $opd) {
    $allowed=true;try{$authorize->invoke(serviceAs('admin_wilayah',$opd),'add','pejabat_tahunan_neo');}catch(Throwable){$allowed=false;}
    must(!$allowed,'Regional writes require a selected OPD');
}
$s=serviceAs('admin_opd');$db=DB::getInstance();
$employee=$db->query("SELECT id,nama,nip FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd=? AND is_deleted=0 AND disable=0 AND COALESCE(aktif,1)=1 LIMIT 1",['76.01','1.03.0.00.0.00.01.0000'])->fetch();
must((bool)$employee,'Employee fixture is available');
$normalize=new ReflectionMethod($s,'normalizeAnnualOfficial');
$data=['pegawai_id'=>$employee['id'],'jenis_pejabat'=>'BENDAHARA','berlaku_mulai'=>'2026-01-01','berlaku_sampai'=>'2026-12-31','nama_pegawai'=>'forged','nip'=>'forged'];
$resolved=$normalize->invoke($s,$data);
must($resolved['nip']===$employee['nip'] && $resolved['nama_pegawai']!=='forged','Identity comes from the scoped employee');
$failed=false;try{$normalize->invoke(serviceAs('admin_opd','OTHER_OPD'),$data);}catch(Throwable){$failed=true;}
must($failed,'Employee from another OPD is rejected');
$failed=false;try{$normalize->invoke($s,array_replace($data,['jenis_pejabat'=>'PPK']));}catch(Throwable){$failed=true;}
must($failed,'PPK requires a subactivity');
// Existing row authorization must reject scope changes for edit/delete.
$row=$db->query('SELECT id,kd_opd FROM pejabat_tahunan_neo WHERE kd_wilayah=? AND tahun=2026 AND is_deleted=0 LIMIT 1',['76.01'])->fetch();
if($row){$access=new ReflectionMethod($s,'checkAccess');must($access->invoke(serviceAs('kepala_opd',$row['kd_opd']),'pejabat_tahunan_neo',$row['id']),'Kepala OPD can access their row');must(!$access->invoke(serviceAs('admin_opd','OTHER_OPD'),'pejabat_tahunan_neo',$row['id']),'Another OPD cannot edit/delete the row');}
echo "PASS: Annual official CRUD roles, selected OPD, employee identity, required subactivity and cross-OPD row protection (read-only tests)\n";
