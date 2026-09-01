<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/AnggaranDocumentService.php';
require_once __DIR__.'/../vendor/autoload.php';

$ok=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";};
$db=DB::getInstance();
$scope=$db->query('SELECT kd_wilayah,kd_opd,tahun FROM dpa_neo WHERE is_deleted=0 ORDER BY id LIMIT 1')->fetch();
$ok((bool)$scope,'scope data anggaran tersedia');
$service=new AnggaranDocumentService($scope+['username'=>'FORMAT_PHASE20','type_user'=>'admin_opd','nama_lengkap'=>'Pengguna Anggaran']);

foreach(['dpa'=>false,'dppa'=>true] as $logical=>$change){
    $pdf=$service->exportPdf($logical);
    $ok(str_starts_with($pdf,'%PDF-'),"PDF $logical valid");
    $ok(strlen($pdf)>5000,"PDF $logical berisi dokumen lengkap");
    $file=$service->exportExcel($logical);$book=PhpOffice\PhpSpreadsheet\IOFactory::load($file);$groups=$service->groups($logical);
    $ok($book->getSheetCount()===count($groups),"Excel $logical satu sheet per sub kegiatan");
    $sheet=$book->getSheet(0);$ok(str_contains((string)$sheet->getCell('A1')->getValue(),'DOKUMEN PELAKSANAAN'),"Excel $logical memakai kepala formulir resmi");
    $ok((string)$sheet->getCell('C6')->getValue()===($change?'SEBELUM PERUBAHAN':'KOEFISIEN/VOLUME'),"Excel $logical memakai format ".($change?'perubahan':'normal'));
    unlink($file);
}

$engine=file_get_contents(__DIR__.'/../public/assets/js/engine/form-engine.js');
$ok(str_contains($engine,'const hasRemoteSource = Boolean(dropdown.data("source"))'),'opsi dropdown statis tidak dihapus saat edit');
$ok(str_contains($engine,'data.jenis_standar_harga || "ssh"'),'sumber komponen standar mengikuti jenis saat edit');
echo "PHASE 20 TESTS COMPLETE\n";
