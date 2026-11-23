<?php
require_once __DIR__.'/../app/Services/ProcurementPerformanceSecurityService.php';
function checkSecurity(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$c=['jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','nilai_kontrak'=>800000000,'nilai_hps'=>1000000000,'waktu_pelaksanaan'=>90];
$s=ProcurementPerformanceSecurityService::construction($c);
checkSecurity($s['amount']===40000000.0&&$s['basis']==='nilai Kontrak'&&$s['days']===90,'Tepat 80% harus memakai nilai kontrak');
$s=ProcurementPerformanceSecurityService::construction(array_replace($c,['nilai_kontrak'=>799999999]));
checkSecurity($s['amount']===50000000.0&&$s['basis']==='HPS','Di bawah 80% harus memakai HPS');
foreach(['KONSULTANSI_KONSTRUKSI','KONSULTANSI_NON_KONSTRUKSI']as$kind)checkSecurity(ProcurementPerformanceSecurityService::construction(array_replace($c,['jenis_pengadaan'=>$kind]))===null,'Konsultansi tidak memakai jaminan pelaksanaan');
checkSecurity(ProcurementPerformanceSecurityService::construction(array_replace($c,['nilai_kontrak'=>200000000]))===null,'Nilai sampai Rp200 juta tidak wajib jaminan');
foreach([['nilai_hps'=>0],['waktu_pelaksanaan'=>0]]as$missing){
 try{ProcurementPerformanceSecurityService::construction(array_replace($c,$missing));throw new RuntimeException('Data wajib kosong diterima');}catch(InvalidArgumentException $e){}
}
echo "PASS: dasar jaminan pada batas 80%, nominal, masa berlaku, pengecualian konsultansi dan validasi data wajib.\n";
