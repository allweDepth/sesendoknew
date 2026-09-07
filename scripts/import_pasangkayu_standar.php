<?php
/** Impor idempoten SSH/HSPK/ASB/SBU ekspor SIPD untuk satu tahun Pasangkayu. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../app/Services/StandarHargaService.php';

$year = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT);
if (!$year || $year < 2000 || $year > 2100) {
    throw new RuntimeException('Pemakaian: php scripts/import_pasangkayu_standar.php TAHUN SSH HSPK ASB SBU');
}
$files = [
    'ssh'  => $argv[2] ?? '',
    'hspk' => $argv[3] ?? '',
    'asb'  => $argv[4] ?? '',
    'sbu'  => $argv[5] ?? '',
];
foreach ($files as $type => $file) {
    if (!is_file($file)) throw new RuntimeException("File $type tidak ditemukan: $file");
}

$db = DB::getInstance();
$columns = array_column($db->query('SHOW COLUMNS FROM master_biaya')->fetchAll(), 'Field');
if (!in_array('sipd_id', $columns, true)) $db->query('ALTER TABLE master_biaya ADD COLUMN sipd_id VARCHAR(60) NULL AFTER tipe');
if (!in_array('kode_kelompok', $columns, true)) $db->query('ALTER TABLE master_biaya ADD COLUMN kode_kelompok VARCHAR(100) NULL AFTER kode_aset');
$indexes = array_column($db->query('SHOW INDEX FROM master_biaya')->fetchAll(), 'Key_name');
if (!in_array('idx_master_biaya_sipd', $indexes, true)) $db->query('ALTER TABLE master_biaya ADD INDEX idx_master_biaya_sipd (tipe,sipd_id,kd_wilayah,tahun,peraturan_id,is_deleted)');

$username = 'IMPORT_SIPD_PASANGKAYU_' . $year;
$supplementalAccounts = [
    '5.1.02.02.01.0080' => 'Belanja Honorarium Penanggungjawaban Pengelola Keuangan',
    '5.1.02.02.01.0081' => 'Belanja Honorarium Pengadaan Barang/Jasa',
    '5.1.02.02.13.0019' => 'Belanja Insentif Pegawai Non ASN atas Pemungutan Pajak Barang dan Jasa Tertentu (PBJT)',
    '5.1.02.02.13.0020' => 'Belanja Insentif Pegawai Non ASN atas Pemungutan Opsen Pajak Kendaraan Bermotor (PKB)',
    '5.1.02.02.13.0021' => 'Belanja Insentif Pegawai Non ASN atas Pemungutan Opsen Bea Balik Nama Kendaraan Bermotor',
];
$accountsAdded = 0;
foreach ($supplementalAccounts as $code=>$description) {
    if ($db->query('SELECT id FROM akun_neo WHERE kode=? AND is_deleted=0 LIMIT 1',[$code])->fetch()) continue;
    $parts = array_pad(array_map('intval',explode('.',$code)),6,null);
    $db->insert('akun_neo',[
        'akun'=>$parts[0],'kelompok'=>$parts[1],'jenis_akun'=>$parts[2],
        'objek'=>$parts[3],'rincian_objek'=>$parts[4],'sub_rincian_objek'=>$parts[5],
        'kode'=>$code,'uraian'=>$description,'peraturan'=>4,'disable'=>0,
        'keterangan'=>'Pelengkap nomenklatur rekening SIPD untuk impor standar harga '.$year,
        'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$username,'is_deleted'=>0,
    ]);
    $accountsAdded++;
}
$service = new StandarHargaService([
    'type_user' => 'tapd', 'kd_wilayah' => '76.01',
    'tahun' => $year, 'username' => $username,
    '_allow_disabled_settings' => true,
]);
$results = [];
foreach ($files as $type => $file) {
    $results[$type] = $service->importSipd($type, $file, $year);
}

// Placeholder lama tidak boleh muncul bersama standar harga definitif.
$removed = $db->query(
    "UPDATE master_biaya SET is_deleted=1,tgl_update=NOW(),username_update=?
     WHERE kd_wilayah=? AND tahun=? AND is_deleted=0
       AND (kode LIKE 'PUPR-PAGU-%' OR UPPER(COALESCE(keterangan,'')) LIKE '%DUMMY%')",
    [$username, '76.01', $year]
)->rowCount();

$audit = $db->query(
    'SELECT tipe,COUNT(*) jumlah,COALESCE(SUM(harga),0) total_harga
     FROM master_biaya WHERE kd_wilayah=? AND tahun=? AND is_deleted=0
     GROUP BY tipe ORDER BY tipe',
    ['76.01', $year]
)->fetchAll();
echo json_encode(['tahun'=>$year,'akun_referensi_ditambahkan'=>$accountsAdded,'hasil'=>$results,'dummy_dinonaktifkan'=>$removed,'audit'=>$audit], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), PHP_EOL;
