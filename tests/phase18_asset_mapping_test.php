<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';

ob_start();
$db=DB::getInstance();
$ok=static function($value,$message){if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";};
$user=$db->query("SELECT * FROM user_sesendok_biila WHERE type_user='super_admin' AND disable=0 ORDER BY id LIMIT 1")->fetch();
if(!$user)$user=$db->query('SELECT * FROM user_sesendok_biila WHERE disable=0 ORDER BY id LIMIT 1')->fetch();
$_SESSION['user']=$user;
$service=new DynamicTableService();

$config=file_get_contents(__DIR__.'/../public/assets/js/config/ui-config.js');
$ok(str_contains($config,'name: "kode_aset"')&&str_contains($config,'source: "aset"'),'semua standar harga mewarisi dropdown kode aset');
$ok(str_contains($config,'kode_aset: { required: true }'),'kode aset wajib pada SSH/SBU/ASB/HSPK');

$_POST=['source'=>'aset','search'=>'TANAH','limit'=>10];
$dropdown=json_decode($service->handle(['action'=>'dropdown','tbl'=>'aset','source'=>'aset','search'=>'TANAH','limit'=>10]),true);
$ok(($dropdown['success']??false)===true&&!empty($dropdown['data']),'dropdown aset dapat dicari');
$first=$dropdown['data'][0];
$ok(str_contains((string)$first['text'],' — '),'opsi menampilkan kode dan uraian aset');

$invalid=json_decode($service->handle(['action'=>'add','tbl'=>'ssh','kode'=>'TRACE.INVALID.ASET','kode_aset'=>'KODE-TIDAK-ADA','uraian'=>'TRACE INVALID ASSET','satuan_id'=>1,'harga'=>1]),true);
$ok(($invalid['success']??true)===false&&str_contains($invalid['message']??'','tidak valid'),'backend menolak kode aset di luar referensi');

$asset=$db->query("SELECT kode FROM aset_neo WHERE disable=0 AND is_deleted=0 ORDER BY kode LIMIT 1")->fetch();
$account=$db->query("SELECT kode FROM akun_neo WHERE is_deleted=0 ORDER BY kode LIMIT 1")->fetch();
$standardId=(int)$db->insert('master_biaya',['tipe'=>'ssh','kode'=>'TRACE.PHASE18.MAP','kode_aset'=>$asset['kode'],'uraian'=>'TRACE PHASE18 MAPPING','harga'=>1,'kd_wilayah'=>$user['kd_wilayah'],'tahun'=>$user['tahun'],'peraturan_id'=>4,'disable'=>0,'is_deleted'=>0,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$user['username']]);
$db->insert('master_biaya_akun',['master_biaya_id'=>$standardId,'kd_akun'=>$account['kode'],'kd_wilayah'=>$user['kd_wilayah'],'peraturan_id'=>4,'disable'=>0,'is_deleted'=>0,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$user['username']]);
try{
  $validator=(new ReflectionClass(DynamicTableService::class))->getMethod('validateAkunMapping');
  $validator->invoke($service,'renja_neo',['id_standar_harga'=>$standardId,'jenis_standar_harga'=>'ssh','kd_akun'=>$account['kode'],'kd_wilayah'=>$user['kd_wilayah'],'tahun'=>$user['tahun']]);
  $ok(true,'standar harga dengan pasangan rekening dan aset valid diterima');
  $rejected=false;try{$validator->invoke($service,'renja_neo',['id_standar_harga'=>$standardId,'jenis_standar_harga'=>'ssh','kd_akun'=>'9.9.9.TIDAK.ADA','kd_wilayah'=>$user['kd_wilayah'],'tahun'=>$user['tahun']]);}catch(Throwable $e){$rejected=str_contains($e->getMessage(),'tidak dipetakan');}
  $ok($rejected,'rincian anggaran menolak pasangan rekening yang tidak dimapping');
}finally{
  $db->query('DELETE FROM master_biaya_akun WHERE master_biaya_id=?',[$standardId]);
  $db->query('DELETE FROM master_biaya WHERE id=?',[$standardId]);
}
echo "PHASE 18 TESTS COMPLETE\n";
ob_end_flush();
