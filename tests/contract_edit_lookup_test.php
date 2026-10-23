<?php
ob_start();
// Exercise the real save path using connection-local temporary write targets.
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';
$c=require __DIR__.'/../config/database.php';
$pdo=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$contract=$pdo->query("SELECT * FROM kontrak_neo WHERE is_deleted=0 AND uraian_kontrak LIKE '%Drainase Jalan dalam Kota%' LIMIT 1")->fetch();
if(!$contract)throw new RuntimeException('Contract fixture unavailable');
$officials=$pdo->prepare("SELECT id,jenis_pejabat,nama_pegawai FROM pejabat_tahunan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 AND jenis_pejabat IN ('PPK','PPTK') ORDER BY id DESC");
$officials->execute([$contract['kd_wilayah'],$contract['kd_opd'],$contract['tahun']]);$byType=[];
foreach($officials->fetchAll() as $official) $byType[$official['jenis_pejabat']]??=$official;
if(!isset($byType['PPK'],$byType['PPTK']))throw new RuntimeException('Official fixtures unavailable');
foreach(['kontrak_neo','log_activity'] as $table){
    $ddl=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
    $pdo->exec(preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl));
}
$fields=array_keys($contract);$insert=$pdo->prepare('INSERT INTO kontrak_neo (`'.implode('`,`',$fields).'`) VALUES ('.implode(',',array_fill(0,count($fields),'?')).')');$insert->execute(array_values($contract));
(new ReflectionProperty(DB::class,'pdo'))->setValue(DB::getInstance(),$pdo);
$_SESSION=['user'=>['type_user'=>'admin_opd','username'=>'contract_lookup_test','kd_wilayah'=>$contract['kd_wilayah'],'kd_opd'=>$contract['kd_opd'],'tahun'=>$contract['tahun']]];
$service=new DynamicTableService();$profile=(require __DIR__.'/../app/Config/table_profiles.php')['kontrak'];
$lookup=new ReflectionMethod($service,'applyLookup');
function check($condition,$message){if(!$condition)throw new RuntimeException($message);echo "PASS: $message\n";}
$data=['ppk_id'=>$byType['PPK']['id'],'pptk_id'=>$byType['PPTK']['id'],'kd_wilayah'=>$contract['kd_wilayah'],'kd_opd'=>$contract['kd_opd'],'tahun'=>$contract['tahun']];
$args=[&$data,$profile];$lookup->invokeArgs($service,$args);
check($data['nama_ppk']===$byType['PPK']['nama_pegawai'],'PPK name resolves from the official table');
$wrong=$data;$wrong['kd_opd']='OTHER_OPD';unset($wrong['nama_ppk'],$wrong['nama_pptk']);$args=[&$wrong,$profile];$lookup->invokeArgs($service,$args);
check(!isset($wrong['nama_ppk'],$wrong['nama_pptk']),'Officials outside the contract OPD are not resolved');
$missing=['kd_wilayah'=>$contract['kd_wilayah'],'kd_opd'=>$contract['kd_opd'],'tahun'=>$contract['tahun']];$args=[&$missing,$profile];$lookup->invokeArgs($service,$args);
check(!isset($missing['nama_ppk'],$missing['nama_pptk']),'Missing optional official IDs do not choose an arbitrary name');
foreach(['SWAKELOLA','PENYEDIA'] as $way){
    $request=['action'=>'edit','mode'=>'update','tbl'=>'kontrak','id_row'=>$contract['id'],'cara_pengadaan'=>$way,'jenis_pengadaan'=>'KONSULTANSI_KONSTRUKSI','tipe_swakelola'=>'III','metode_pemilihan'=>$way==='SWAKELOLA'?'Swakelola':'Tender','ppk_id'=>$byType['PPK']['id'],'pptk_id'=>$byType['PPTK']['id'],'rekanan_id'=>$contract['rekanan_id'],'nomor_rup'=>'01','keterangan'=>'Temporary contract save regression'];
    $result=json_decode($service->handle($request),true);
    check(!empty($result['success']),"$way edit saves: ".($result['message']??json_encode($result)));
    $row=$pdo->query('SELECT * FROM kontrak_neo WHERE id='.(int)$contract['id'])->fetch();
    check($row['cara_pengadaan']===$way && $row['nama_ppk']===$byType['PPK']['nama_pegawai'] && (int)$row['pptk_id']===(int)$byType['PPTK']['id'],"$way save preserves official references and derived names");
    check($row['bentuk_kontrak']!==null && $row['bentuk_kontrak']!=='','Contract form is calculated when saving');
}
foreach (['cara_pengadaan'=>'INVALID', 'tipe_swakelola'=>'V', 'rekanan_id'=>0] as $field=>$invalid) {
    $invalidRequest = array_replace($request, ['cara_pengadaan'=>'SWAKELOLA','tipe_swakelola'=>'III', $field=>$invalid]);
    $error = json_decode($service->handle($invalidRequest), true);
    check(empty($error['success']) && !empty($error['errors'][$field]), "Validation identifies the invalid $field for inline form feedback");
}
echo "Production contracts and activity logs were not changed.\n";

ob_end_flush();
