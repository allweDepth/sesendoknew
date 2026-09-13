<?php
require_once __DIR__.'/../app/Core/DB.php';

$db=DB::getInstance();
$ok=static function(bool $value,string $message):void{
    if(!$value)throw new RuntimeException('FAIL: '.$message);
    echo "PASS: $message\n";
};

$renderer=file_get_contents(__DIR__.'/../app/Services/ProcurementFixedPdfService.php');
$contractJs=file_get_contents(__DIR__.'/../public/assets/js/modules/kontrak.js');
$settings=file_get_contents(__DIR__.'/../app/Views/pengaturan/form.php');
$routes=file_get_contents(__DIR__.'/../routes/web.php');

$ok(str_contains($renderer,"str_starts_with(\$code,'SPK_')")&&str_contains($renderer,"\$code==='SURAT_PERJANJIAN'")&&str_contains($renderer,"\$code==='SSKK'")&&str_contains($renderer,"\$code==='SSUK'"),'renderer tetap membedakan SPK, Surat Perjanjian, SSKK, dan SSUK');
$ok(str_contains($renderer,'OfficialLetterheadPdfService::draw')&&str_contains($renderer,'PageSetupService::applyPdf'),'PDF memakai kop resmi dan Page Setup');
$ok(str_contains($contractJs,'data-contract-pdf="${c.id}"')&&!str_contains($contractJs,'prompt("ID kontrak'),'tombol PDF kontrak berada pada setiap row tanpa meminta ID');
$ok(str_contains($contractJs,'paragraphMode')&&str_contains($contractJs,'collectStructure?.()'),'Surat Perjanjian, SSKK, dan SSUK memakai editor paragraf Tata Naskah');
$ok(str_contains($contractJs,'transaction_files[]')&&str_contains($contractJs,'data-realization-file-delete'),'input/edit realisasi mendukung unggah, unduh, dan hapus file transaksi');
$ok(strpos($settings,'data-tab="page-setup"')<strpos($settings,'data-tab="kop-surat"'),'tab Kop Surat Resmi tepat setelah Page Setup');
foreach(['/kontrak/realization/detail','/kontrak/realization/file/delete','/kontrak/realization/file/download'] as $route)$ok(str_contains($routes,"'$route'"),"route $route tersedia");
$ok((bool)$db->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='realisasi_dokumen_neo'")->fetch(),'tabel file transaksi realisasi tersedia');
$masters=$db->query("SELECT m.kode,JSON_VALID(b.isi_template) valid,JSON_LENGTH(b.isi_template) jumlah FROM master_dokumen_pengadaan_neo m JOIN master_dokumen_pengadaan_bagian_neo b ON b.master_id=m.id AND b.is_deleted=0 WHERE m.kode IN ('SURAT_PERJANJIAN','SSKK','SSUK')")->fetchAll();
$ok(count($masters)===3&&array_reduce($masters,fn($carry,$row)=>$carry&&(int)$row['valid']===1&&((int)$row['jumlah']>0),true),'master tiga dokumen tersimpan sebagai paragraf JSON valid tanpa HTML');
echo "PHASE 58 FIXED PROCUREMENT PDF/REALIZATION UI TESTS COMPLETE\n";
