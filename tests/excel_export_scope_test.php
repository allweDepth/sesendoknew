<?php
ob_start();require_once __DIR__.'/../app/Services/DynamicTableService.php';$db=DB::getInstance();if(!str_starts_with($db->query('SELECT DATABASE() d')->fetch()['d'],'qa_'))throw new RuntimeException('QA database required');
$_SESSION=['user'=>['id'=>1,'username'=>'EXCEL_SCOPE_TEST','type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026]];
$request=['tbl'=>'mapping','action'=>'export','req'=>'ssh'];$_POST=$request;$result=json_decode((new DynamicTableService())->handle($request),true);
if(!$result['success'])throw new RuntimeException($result['message']);$rows=$result['data'];$ids=array_column($rows,'id');
if(count($ids)!==count(array_unique($ids)))throw new RuntimeException('Duplicate mapping IDs in export');
foreach($rows as$row)if((int)$row['tahun']!==2026||$row['tipe']!=='ssh')throw new RuntimeException('Unexpected year/type in mapping export');
$expected=(int)$db->query('SELECT COUNT(*) n FROM master_biaya_akun a JOIN master_biaya m ON m.id=a.master_biaya_id WHERE a.kd_wilayah=? AND m.tahun=? AND m.tipe=? AND a.is_deleted=0 AND m.is_deleted=0',['76.01',2026,'ssh'])->fetch()['n'];
if(count($rows)!==$expected)throw new RuntimeException('Mapping row count differs from scoped base data');
ob_end_clean();echo 'PASS: Mapping export respects region/year/type and returns one row per mapping ('.count($rows)." rows)\n";
