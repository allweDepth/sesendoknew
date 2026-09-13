<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/ProcurementDocumentService.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';

$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
$db=DB::getInstance();
foreach([
  'anggaran_approval_log_neo','master_dokumen_pengadaan_neo','master_dokumen_pengadaan_bagian_neo',
  'dokumen_pengadaan_neo','dokumen_pengadaan_bagian_neo','dokumen_pengadaan_lampiran_neo'
] as $table)$ok((bool)$db->query('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1',[$table])->fetch(),"tabel $table tersedia");

$masterCount=(int)$db->query('SELECT COUNT(*) jumlah FROM master_dokumen_pengadaan_neo WHERE aktif=1 AND is_deleted=0')->fetch()['jumlah'];
$ok($masterCount>=28,'master meliputi dokumen penyedia dan Swakelola I-IV');
$sectionCount=(int)$db->query('SELECT COUNT(*) jumlah FROM master_dokumen_pengadaan_bagian_neo WHERE is_deleted=0')->fetch()['jumlah'];
$ok($sectionCount>=$masterCount*3,'setiap master memiliki bagian narasi yang dapat disalin dan diedit');

$cases=[
 ['PENYEDIA','BARANG','',10000000,'BUKTI_PEMBELIAN'],['PENYEDIA','BARANG','',50000000,'KUITANSI'],
 ['PENYEDIA','BARANG','',200000000,'SPK'],['PENYEDIA','BARANG','',200000001,'SURAT_PERJANJIAN'],
 ['PENYEDIA','KONSULTANSI_KONSTRUKSI','',100000000,'SPK'],['PENYEDIA','KONSULTANSI_NON_KONSTRUKSI','',100000001,'SURAT_PERJANJIAN'],
 ['PENYEDIA','PEKERJAAN_KONSTRUKSI','',400000000,'SPK'],['PENYEDIA','PEKERJAAN_KONSTRUKSI','',400000001,'SURAT_PERJANJIAN'],
 ['PENYEDIA','BARANG','e-purchasing katalog',999999999,'SURAT_PESANAN'],['SWAKELOLA','BARANG','',1,'PERJANJIAN_SWAKELOLA']
];
foreach($cases as [$way,$kind,$method,$value,$expected])$ok(ProcurementDocumentService::recommendForm($way,$kind,$method,$value)===$expected,"bentuk kontrak $kind / $value = $expected");

$files='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../app')) as $f)if($f->isFile()&&preg_match('/\.(php|js)$/',$f->getFilename()))$files.=file_get_contents($f->getPathname())."\n";foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../public')) as $f)if($f->isFile()&&preg_match('/\.(php|js)$/',$f->getFilename()))$files.=file_get_contents($f->getPathname())."\n";
$ok(!preg_match('/<input(?=[^>]*\btype=["\'](?:date|time|datetime-local)["\'])/i',$files),'tidak ada input native date/time/datetime-local; seluruhnya memakai UI Calendar');
$workflow=file_get_contents(__DIR__.'/../public/assets/js/modules/anggaran-document.js');
$ok(str_contains($workflow,'renja: "rka"')&&str_contains($workflow,'rka: "dpa"')&&str_contains($workflow,'dpa: "renja_p"')&&str_contains($workflow,'renja_p: "rka_p"')&&str_contains($workflow,'rka_p: "dppa"'),'rantai persetujuan TAPD lengkap Renja sampai DPPA');
$ok(str_contains($workflow,'KUNCI_SALIN')&&str_contains($workflow,'KUNCI_GANTI_TUJUAN')&&str_contains($workflow,'KUNCI'),'tiga skenario persetujuan TAPD tersedia');
$ok(str_contains($workflow,'same-price-excel')&&str_contains($workflow,'export_rekap_pdf'),'tombol ekspor rekap, uraian harga sama, dan PDF tersedia');

$scope=['56.TEST','OPD.TEST',2099];$_SESSION['user']=['type_user'=>'admin_opd','username'=>'phase56','kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2]];
$db->begin();try{
  $parent=$db->insert('struktur_jabatan_opd_neo',['kd_wilayah'=>$scope[0],'kd_opd'=>'BUPATI','tahun'=>$scope[2],'pegawai_id'=>101,'nama_jabatan'=>'Kepala Daerah','nomor_sk_pengangkatan'=>'SK-1','tanggal_sk_pengangkatan'=>'2099-01-01','tmt_jabatan'=>'2099-01-01','berlaku_mulai'=>'2099-01-01','status_jabatan'=>'AKTIF','urutan'=>1,'is_deleted'=>0]);
  $head=$db->insert('struktur_jabatan_opd_neo',['kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'pegawai_id'=>102,'parent_id'=>$parent,'parent_kd_opd'=>'BUPATI','nama_jabatan'=>'Kepala OPD','nomor_sk_pengangkatan'=>'SK-2','tanggal_sk_pengangkatan'=>'2099-01-01','tmt_jabatan'=>'2099-01-01','berlaku_mulai'=>'2099-01-01','status_jabatan'=>'AKTIF','urutan'=>1,'is_deleted'=>0]);
  $service=new DynamicTableService();$method=(new ReflectionClass($service))->getMethod('normalizeSakipMetrics');
  $iku=$method->invoke($service,'iku_opd_neo',['tanggal_penetapan'=>'2099-06-01']);
  $ok((int)$iku['penanggung_jawab_struktur_id']===(int)$head&&(int)$iku['penanggung_jawab_pegawai_id']===102,'penanggung jawab SAKIP otomatis dari struktur aktif');
  $pk=$method->invoke($service,'perjanjian_kinerja_neo',['tanggal_dokumen'=>'2099-06-01']);
  $ok((int)$pk['pihak_pertama_struktur_id']===(int)$head&&(int)$pk['pihak_kedua_struktur_id']===(int)$parent,'pihak PK otomatis mengikuti kepala OPD dan atasan struktur aktif');
  $db->rollback();
}catch(Throwable $e){$db->rollback();throw$e;}
echo "PHASE 56 PROCUREMENT/CALENDAR/TAPD/SAKIP TESTS COMPLETE\n";
