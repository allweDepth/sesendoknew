<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/PdfService.php';
require_once __DIR__.'/../app/Services/DynamicTableService.php';

$_SESSION['user']=['username'=>'TRACE_TEST','type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_pemda'=>'PEMERINTAH KABUPATEN PASANGKAYU','nama_opd'=>'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG'];
$db=DB::getInstance();
$assert=static function(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";};

foreach(['ref_jenis_naskah','ref_kelompok_naskah','ref_klasifikasi_keamanan','trx_naskah_dinas','trx_naskah_meta','trx_naskah_struktur','trx_nomor_counter','trx_naskah_status_history'] as $table){
 $exists=(int)$db->query('SELECT COUNT(*) total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table])->fetch()['total'];
 $assert($exists===1,"tabel $table tersedia");
}
$assert((int)$db->query('SELECT COUNT(*) total FROM ref_jenis_naskah')->fetch()['total']>=20,'jenis naskah tersedia');
$routes=file_get_contents(__DIR__.'/../routes/web.php');
foreach(['/tata_naskah','/tata_naskah/dokumen','/tata_naskah/buat','/tata_naskah/daftar','/tata_naskah/generate_pdf','/tata_naskah/update_status'] as $route)$assert(str_contains($routes,"'$route'"),"route $route terdaftar");

$classification=$db->query('SELECT id FROM ref_klasifikasi_keamanan ORDER BY id LIMIT 1')->fetch();
$assert((bool)$classification,'klasifikasi keamanan tersedia');
$cid=(int)$classification['id'];
$db->query('START TRANSACTION');
$db->query('INSERT INTO trx_nomor_counter(klasifikasi_id,tahun,last_number) VALUES(?,?,LAST_INSERT_ID(1)) ON DUPLICATE KEY UPDATE last_number=LAST_INSERT_ID(last_number+1)',[$cid,2026]);
$n1=(int)$db->query('SELECT LAST_INSERT_ID() n')->fetch()['n'];
$db->query('COMMIT');
$db->query('START TRANSACTION');
$db->query('INSERT INTO trx_nomor_counter(klasifikasi_id,tahun,last_number) VALUES(?,?,LAST_INSERT_ID(1)) ON DUPLICATE KEY UPDATE last_number=LAST_INSERT_ID(last_number+1)',[$cid,2026]);
$n2=(int)$db->query('SELECT LAST_INSERT_ID() n')->fetch()['n'];
$db->query('COMMIT');
$assert($n2===$n1+1,"counter nomor atomik dan berurutan ($n1 -> $n2)");

$row=$db->query("SELECT t.id FROM trx_naskah_dinas t JOIN trx_naskah_struktur s ON s.naskah_id=t.id WHERE t.uuid='TRACE_TEST_PHASE6' LIMIT 1")->fetch();
$assert((bool)$row,'data naskah dengan struktur tersedia');
$assert((int)$db->query('SELECT COUNT(*) total FROM trx_naskah_meta WHERE naskah_id=?',[$row['id']])->fetch()['total']>=3,'metadata pengendalian naskah tersimpan');
$oldStructure=$db->query('SELECT struktur_json FROM trx_naskah_struktur WHERE naskah_id=?',[$row['id']])->fetch()['struktur_json'];
$payload=json_decode($oldStructure,true)?:[];$payload['perihal']='TRACE EDIT TATA NASKAH TERSIMPAN';
$service=new DynamicTableService();$edited=json_decode($service->handle(['action'=>'edit_json','tbl'=>'trx_naskah_dinas','id_row'=>$row['id'],'struktur_json'=>json_encode($payload)]),true);
$assert(($edited['success']??false)===true,'edit Tata Naskah mengembalikan sukses');
$saved=$db->query('SELECT struktur_json FROM trx_naskah_struktur WHERE naskah_id=?',[$row['id']])->fetch();$assert(str_contains($saved['struktur_json'],'TRACE EDIT TATA NASKAH TERSIMPAN'),'hasil edit Tata Naskah tersimpan');
$db->update('trx_naskah_struktur',['struktur_json'=>$oldStructure],'WHERE naskah_id=?',[$row['id']]);
$pdf=(new PdfService())->generate('trx_naskah_dinas',(int)$row['id']);
$assert(str_starts_with($pdf,'%PDF-'),'PDF Tata Naskah valid');
$out='/private/tmp/phase6-tata-naskah.pdf';file_put_contents($out,$pdf);
echo "PDF_TEST=$out\nPHASE 6 TESTS COMPLETE\n";
