<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';

ob_start();
$_SESSION['user']=['username'=>'TRACE_TEST','type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026];
$db=DB::getInstance(); $service=new DynamicTableService();
$assert=static function(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";};
$call=static function(array $request)use($service):array{$result=json_decode($service->handle($request),true);if(!is_array($result))throw new RuntimeException('Respons bukan JSON');return $result;};

foreach(['db_asn_pemda_neo','riwayat_jabatan_neo','riwayat_pangkat_neo','cuti_pegawai_neo','sk_pegawai_neo'] as $table){
 $exists=(int)$db->query('SELECT COUNT(*) total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table])->fetch()['total'];
 $assert($exists===1,"tabel $table tersedia");
}
$asn=$call(['action'=>'list','tbl'=>'asn','req'=>'kepegawaian','halaman'=>1,'rows'=>5]);
$assert(($asn['success']??false)===true,'Data ASN dapat dibaca tanpa kolom uraian yang salah');
$_POST=['action'=>'dropdown','tbl'=>'asn','source'=>'asn'];
$dropdown=$call($_POST);
$assert(($dropdown['success']??false)===true && !empty($dropdown['data']),'Dropdown pegawai memakai nama ASN dan berisi opsi');
$assert(!empty($dropdown['data'][0]['text']) && !empty($dropdown['data'][0]['value']),'Opsi pegawai memiliki nama dan ID');
foreach(['pppk','riwayat_jabatan','riwayat_pangkat','cuti','sk_pegawai'] as $logical){
 $result=$call(['action'=>'list','tbl'=>$logical,'req'=>'kepegawaian','halaman'=>1,'rows'=>5]);
 $assert(($result['success']??false)===true,"subtab $logical terhubung backend");
}
$output=ob_get_clean(); echo $output,"PHASE 5 TESTS COMPLETE\n";
