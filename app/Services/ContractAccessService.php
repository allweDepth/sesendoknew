<?php
require_once __DIR__.'/../Core/DB.php';
/** Assignment boundary shared by contract details, files, RAB and procurement. */
final class ContractAssignmentAccessDenied extends RuntimeException {}

final class ContractAccessService
{
    private static function restricted(array $user):bool
    {
        return in_array($user['type_user']??'viewer',['ppk','pptk','staf_opd','editor','viewer','user','pejabat_pengadaan','pokja_ulp'],true);
    }
    public static function condition(array $user,string $alias='k',string $flag='dapat_lihat'):array
    {
        if(!self::restricted($user))return ['',[]];
        if(!preg_match('/^[A-Za-z_]+$/D',$alias)||!in_array($flag,['dapat_lihat','dapat_input'],true))throw new InvalidArgumentException('Invalid assignment filter');
        return [" AND EXISTS(SELECT 1 FROM user_subkegiatan_neo access_assignment WHERE access_assignment.user_id=? AND access_assignment.kd_wilayah COLLATE utf8mb4_unicode_ci=$alias.kd_wilayah COLLATE utf8mb4_unicode_ci AND access_assignment.kd_opd COLLATE utf8mb4_unicode_ci=$alias.kd_opd COLLATE utf8mb4_unicode_ci AND access_assignment.tahun=$alias.tahun AND access_assignment.kd_sub_keg COLLATE utf8mb4_unicode_ci=$alias.kd_sub_keg COLLATE utf8mb4_unicode_ci AND access_assignment.$flag=1 AND access_assignment.berlaku_mulai<=CURDATE() AND access_assignment.berlaku_sampai>=CURDATE() AND access_assignment.is_deleted=0)",[(int)($user['id']??0)]];
    }
    public static function canInput(array $user,array $contract):bool
    {
        try{self::assert($user,$contract,'dapat_input');return true;}catch(ContractAssignmentAccessDenied $e){return false;}
    }
    public static function assert(array $user,array $contract,string $flag='dapat_lihat'):void
    {
        if(!self::restricted($user))return;
        if(!in_array($flag,['dapat_lihat','dapat_input'],true))throw new InvalidArgumentException('Invalid assignment flag');
        $row=DB::getInstance()->query("SELECT id FROM user_subkegiatan_neo WHERE user_id=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND kd_sub_keg=? AND $flag=1 AND berlaku_mulai<=CURDATE() AND berlaku_sampai>=CURDATE() AND is_deleted=0 LIMIT 1",[(int)($user['id']??0),$contract['kd_wilayah'],$contract['kd_opd'],$contract['tahun'],$contract['kd_sub_keg']??''])->fetch();
        if(!$row)throw new ContractAssignmentAccessDenied($flag==='dapat_input'?'Anda tidak memiliki izin input untuk sub kegiatan kontrak ini.':'Kontrak tidak termasuk sub kegiatan yang ditugaskan kepada pengguna.');
    }
}
