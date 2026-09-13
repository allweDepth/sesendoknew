<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/ProcurementDocumentService.php';

$db=DB::getInstance();$ok=static function(bool $v,string $m):void{if(!$v)throw new RuntimeException('FAIL: '.$m);echo "PASS: $m\n";};
$layout=file_get_contents(__DIR__.'/../app/Services/ProcurementZipPdfService.php');
$expected=['UNDANGAN_PL','JADWAL_DOKUMEN','BA_SURVEY_HARGA','PAKTA_INTEGRITAS','BA_KLARIFIKASI_NEGOSIASI','LAMPIRAN_NEGOSIASI','DAFTAR_HADIR_KLARIFIKASI','PENETAPAN_PENYEDIA','PENGUMUMAN_PENYEDIA','BA_EVALUASI','BAHP','SPPBJ','SPMK','RAPAT_PRA_KONTRAK'];
foreach($expected as $code)$ok(str_contains($layout,"'$code'"),"layout khusus $code tersedia");
$ok(substr_count($layout,'private function ')>=20,'dokumen ZIP tidak memakai satu body generik bersama');
$routes=file_get_contents(__DIR__.'/../routes/web.php');$js=file_get_contents(__DIR__.'/../public/assets/js/modules/kontrak.js');
$ok(str_contains($routes,"'/kontrak/procurement/delete'")&&str_contains($js,'data-procurement-delete'),'aksi hapus dokumen hasil tersedia pada row');
$contract=$db->query('SELECT * FROM kontrak_neo WHERE is_deleted=0 ORDER BY id LIMIT 1')->fetch();$master=$db->query("SELECT * FROM master_dokumen_pengadaan_neo WHERE kode='BA_SURVEY_HARGA' AND is_deleted=0 LIMIT 1")->fetch();$ok((bool)$contract&&(bool)$master,'kontrak dan master uji tersedia');
$_SESSION['user']=['username'=>'phase59','type_user'=>'admin_opd','kd_wilayah'=>$contract['kd_wilayah'],'kd_opd'=>$contract['kd_opd'],'tahun'=>$contract['tahun']];
$id=$db->insert('dokumen_pengadaan_neo',['kontrak_id'=>$contract['id'],'master_id'=>$master['id'],'kode_dokumen'=>$master['kode'],'nomor_dokumen'=>'DELETE/PHASE59','tanggal_dokumen'=>date('Y-m-d'),'judul'=>'QA delete phase59','status'=>'DRAFT','dasar_hukum_snapshot'=>$master['dasar_hukum'],'versi_master'=>$master['versi'],'pembuat_role'=>'admin_opd','kd_wilayah'=>$contract['kd_wilayah'],'kd_opd'=>$contract['kd_opd'],'tahun'=>$contract['tahun'],'username_insert'=>'phase59','is_deleted'=>0]);
$db->insert('dokumen_pengadaan_bagian_neo',['dokumen_id'=>$id,'kode_bagian'=>'QA','judul'=>'QA','urutan'=>1,'isi'=>'QA','username_insert'=>'phase59','is_deleted'=>0]);
$result=(new ProcurementDocumentService($_SESSION['user']))->delete((int)$id);$ok(!empty($result['deleted']),'service menghapus dokumen hasil');
$ok((int)$db->query('SELECT is_deleted FROM dokumen_pengadaan_neo WHERE id=?',[$id])->fetch()['is_deleted']===1,'header dokumen di-soft-delete');
$ok((int)$db->query('SELECT is_deleted FROM dokumen_pengadaan_bagian_neo WHERE dokumen_id=?',[$id])->fetch()['is_deleted']===1,'bagian dokumen di-soft-delete');
$count=(int)$db->query("SELECT COUNT(*) n FROM master_dokumen_pengadaan_neo WHERE kode IN ('LAMPIRAN_NEGOSIASI','DAFTAR_HADIR_KLARIFIKASI') AND aktif=1 AND is_deleted=0")->fetch()['n'];$ok($count===2,'dua lembar tambahan ZIP tersedia sebagai master');
echo "PHASE 59 ZIP LAYOUT/DELETE TESTS COMPLETE\n";
