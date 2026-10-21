<?php
require_once __DIR__ . '/../app/Services/RegionalLeadershipService.php';
function verify($ok, $message) { if (!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
$titles = RegionalLeadershipService::titles('76.01', 'PASANGKAYU');
verify($titles['kepala'] === 'Bupati PASANGKAYU', 'Sebutan kabupaten');
verify(RegionalLeadershipService::titles('76', 'SULAWESI BARAT')['wakil'] === 'Wakil Gubernur SULAWESI BARAT', 'Sebutan provinsi');
verify(RegionalLeadershipService::titles('73.71', 'MAKASSAR')['kepala'] === 'Walikota MAKASSAR', 'Sebutan kota');
$empty = RegionalLeadershipService::merge([], [], $titles, 'OPD-A', false);
verify(count($empty) === 3 && $empty[2]['parent_key'] === 'wilayah:kepala', 'Tiga posisi tetap tanpa data ASN');
verify($empty[0]['nama_pegawai'] === '' && !$empty[0]['can_manage'], 'Posisi kosong dalam mode baca OPD');
$legacy = ['id'=>11,'kd_opd'=>'BUPATI','nama_jabatan'=>'Bupati Kabupaten Pasangkayu','struktur_key'=>'BUPATI:11','parent_key'=>'BUPATI:0','username_insert'=>'migration_phase55','nama_pegawai'=>'DUMMY'];
$local = ['id'=>12,'kd_opd'=>'OPD-A','nama_jabatan'=>'Kepala OPD','struktur_key'=>'OPD-A:12','parent_key'=>'BUPATI:11','parent_id'=>11];
$other = array_merge($local, ['kd_opd'=>'OPD-B']);
$official = ['jenis_pimpinan'=>'kepala','nama_pejabat'=>'Pejabat Uji','file_sk'=>'uploads/test.pdf','foto'=>'uploads/test.png'];
$a = RegionalLeadershipService::merge([$legacy,$local,$other], [$official], $titles, 'OPD-A', true);
$b = RegionalLeadershipService::merge([], [$official], $titles, 'OPD-B', false);
verify(count($a) === 4 && $a[3]['parent_key'] === 'wilayah:kepala', 'Tidak menggandakan pimpinan lama dan menjaga tautan OPD');
verify($a[0]['nama_pegawai'] === $b[0]['nama_pegawai'] && $a[0]['file_sk'] === $b[0]['file_sk'], 'Semua OPD membaca data pimpinan yang sama');
verify($a[0]['can_manage'] && !$b[0]['can_manage'], 'Hak pengelolaan pimpinan per role');
verify(RegionalLeadershipService::merge([$legacy], [], $titles, 'OPD-A', false)[0]['nama_pegawai'] === '', 'Data dummy tidak menjadi nama pejabat');
try { RegionalLeadershipService::save(null, ['type_user'=>'admin_opd'], ['jenis_pimpinan'=>'kepala']); throw new LogicException('Akses OPD lolos'); }
catch (RuntimeException $e) { verify(!($e instanceof LogicException), 'Backend menolak perubahan pimpinan oleh OPD'); }
if (in_array('--database', $argv, true)) {
    require_once __DIR__ . '/../app/Core/DB.php';
    $db = DB::getInstance();
    $user = ['type_user'=>'admin_wilayah','kd_wilayah'=>'76.01','tahun'=>2026,'username'=>'REGIONAL_LEADERSHIP_TEST'];
    $db->begin();
    try {
        $input = ['jenis_pimpinan'=>'wakil','nama_pejabat'=>'Pejabat Uji','nomor_sk_pengangkatan'=>'TEST/2026','tanggal_sk_pengangkatan'=>'2026-10-20','tmt_jabatan'=>'2026-10-21'];
        RegionalLeadershipService::save($db, $user, $input);
        $row = $db->query('SELECT * FROM pimpinan_wilayah_neo WHERE kd_wilayah=? AND tahun=? AND jenis_pimpinan=?', ['76.01',2026,'wakil'])->fetch();
        verify($row['nama_pejabat'] === 'Pejabat Uji', 'Penyimpanan pejabat non-ASN ke database');
        $input['nama_pejabat'] = 'Pejabat Uji Revisi';
        RegionalLeadershipService::save($db, $user, $input);
        $rows = $db->query('SELECT * FROM pimpinan_wilayah_neo WHERE kd_wilayah=? AND tahun=? AND jenis_pimpinan=?', ['76.01',2026,'wakil'])->fetchAll();
        verify(count($rows) === 1 && $rows[0]['nama_pejabat'] === 'Pejabat Uji Revisi', 'Perubahan data memakai satu posisi per wilayah/tahun');
        verify(!$db->query('SELECT * FROM pimpinan_wilayah_neo WHERE kd_wilayah=? AND tahun=? AND username_insert=?', ['76.02',2026,'REGIONAL_LEADERSHIP_TEST'])->fetch(), 'Data tidak menyebar ke wilayah berbeda');
        $input['tanggal_sk_pengangkatan'] = '2026-02-30';
        try { RegionalLeadershipService::save($db, $user, $input); throw new LogicException('Tanggal tidak valid lolos'); }
        catch (InvalidArgumentException $e) { verify(true, 'Menolak tanggal SK tidak valid'); }
    } finally { $db->rollback(); }
}
