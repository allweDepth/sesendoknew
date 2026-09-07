<?php
/** CLI idempoten untuk empat workbook ekspor SIPD Kabupaten Pasangkayu. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../app/Services/StandarHargaService.php';

$files = [
    'ssh'  => $argv[1] ?? '/Users/alwi_mansyur/Downloads/export_excel_ssh_Kab. Pasangkayu.xlsx',
    'hspk' => $argv[2] ?? '/Users/alwi_mansyur/Downloads/export_excel_hspk_Kab. Pasangkayu.xlsx',
    'asb'  => $argv[3] ?? '/Users/alwi_mansyur/Downloads/export_excel_asb_Kab. Pasangkayu.xlsx',
    'sbu'  => $argv[4] ?? '/Users/alwi_mansyur/Downloads/export_excel_sbu_Kab. Pasangkayu.xlsx',
];
foreach ($files as $file) if (!is_file($file)) throw new RuntimeException("File tidak ditemukan: {$file}");

$db=DB::getInstance();
$columns=array_column($db->query('SHOW COLUMNS FROM master_biaya')->fetchAll(),'Field');
if(!in_array('sipd_id',$columns,true))$db->query('ALTER TABLE master_biaya ADD COLUMN sipd_id VARCHAR(60) NULL AFTER tipe');
if(!in_array('kode_kelompok',$columns,true))$db->query('ALTER TABLE master_biaya ADD COLUMN kode_kelompok VARCHAR(100) NULL AFTER kode_aset');
$indexes=array_column($db->query('SHOW INDEX FROM master_biaya')->fetchAll(),'Key_name');
if(!in_array('idx_master_biaya_sipd',$indexes,true))$db->query('ALTER TABLE master_biaya ADD INDEX idx_master_biaya_sipd (tipe,sipd_id,kd_wilayah,tahun,peraturan_id,is_deleted)');

$user=['type_user'=>'tapd','kd_wilayah'=>'76.01','tahun'=>2026,'username'=>'IMPORT_SIPD_PASANGKAYU_2026'];
$service=new StandarHargaService($user);
foreach($files as $type=>$file){
    $result=$service->importSipd($type,$file,2026);
    echo strtoupper($type).': '.json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}

