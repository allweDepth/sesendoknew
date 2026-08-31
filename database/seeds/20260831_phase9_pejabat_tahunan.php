<?php
require_once __DIR__ . '/../../app/Core/DB.php';

$db = DB::getInstance();
$scope = $db->query("SELECT kd_wilayah,kd_opd,tahun,COALESCE(username_insert,'system') username,kd_sub_keg FROM dpa_neo WHERE COALESCE(is_deleted,0)=0 ORDER BY id LIMIT 1")->fetch();
$pegawai = $db->query("SELECT id FROM db_asn_pemda_neo WHERE COALESCE(is_deleted,0)=0 AND COALESCE(aktif,1)=1 ORDER BY id LIMIT 2")->fetchAll();
if (!$scope || count($pegawai) < 2) {
    throw new RuntimeException('Scope DPA atau dua ASN aktif belum tersedia.');
}

foreach ([['PPK', $pegawai[0]['id']], ['PPTK', $pegawai[1]['id']]] as [$jenis, $pegawaiId]) {
    $exists = $db->query(
        'SELECT id FROM pejabat_tahunan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND jenis_pejabat=? AND kd_sub_keg=? AND COALESCE(is_deleted,0)=0 LIMIT 1',
        [$scope['kd_wilayah'], $scope['kd_opd'], $scope['tahun'], $jenis, $scope['kd_sub_keg']]
    )->fetch();
    if ($exists) continue;
    $db->insert('pejabat_tahunan_neo', [
        'kd_wilayah' => $scope['kd_wilayah'], 'kd_opd' => $scope['kd_opd'], 'tahun' => $scope['tahun'],
        'jenis_pejabat' => $jenis, 'pegawai_id' => $pegawaiId,
        'nomor_sk' => 'SK-TRACE/'.$jenis.'/'.$scope['tahun'], 'tanggal_sk' => $scope['tahun'].'-01-02',
        'berlaku_mulai' => $scope['tahun'].'-01-01', 'berlaku_sampai' => $scope['tahun'].'-12-31',
        'kd_sub_keg' => $scope['kd_sub_keg'], 'keterangan' => 'Data contoh pejabat aktif Phase 9',
        'username_insert' => $scope['username'], 'tgl_insert' => date('Y-m-d H:i:s'), 'is_deleted' => 0,
    ]);
    echo "SEEDED {$jenis}\n";
}
