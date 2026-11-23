<?php
require_once __DIR__.'/../app/Services/ProcurementDocumentService.php';
function checkNegotiation(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$db=DB::getInstance();$catalog=ProcurementProcessService::catalog();
$master=$db->query("SELECT * FROM master_dokumen_pengadaan_neo WHERE kode='LAMPIRAN_NEGOSIASI' AND aktif=1 AND is_deleted=0 LIMIT 1")->fetch();
checkNegotiation((bool)$master&&empty($master['metode_pemilihan']),'Lampiran masih terbatas pada satu metode');
$reflection=new ReflectionClass(ProcurementDocumentService::class);$service=$reflection->newInstanceWithoutConstructor();$applicable=$reflection->getMethod('assertApplicable');
foreach(['Pengadaan Langsung','Tender','E-Purchasing','Mini Kompetisi'] as $method){
 $c=['cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','metode_pemilihan'=>$method,'nilai_kontrak'=>2300000000,'proses_pengadaan'=>json_encode(['BA_KLARIFIKASI_NEGOSIASI'=>['nomor'=>'NEG/23','tanggal'=>'2026-11-23','waktu'=>'09:00']])];
 $applicable->invoke($service,$master,$c);
 $codes=array_column(ProcurementProcessService::steps($c,$catalog),0);
 checkNegotiation(in_array('BA_KLARIFIKASI_NEGOSIASI',$codes,true)&&in_array('LAMPIRAN_NEGOSIASI',$codes,true),'BA/lampiran tidak ada pada urutan '.$method);
 $value=ProcurementProcessService::documentValue($c,'LAMPIRAN_NEGOSIASI');
 checkNegotiation($value['tanggal']==='2026-11-23'&&$value['nomor']==='NEG/23','Identitas lampiran tidak mengikuti BA '.$method);
 $c['proses_pengadaan']=json_encode(['LAMPIRAN_NEGOSIASI'=>['tanggal'=>'','nomor'=>''],'BA_KLARIFIKASI_NEGOSIASI'=>['nomor'=>'NEG/24','tanggal'=>'2026-11-24']]);
 checkNegotiation(ProcurementProcessService::documentValue($c,'LAMPIRAN_NEGOSIASI')['tanggal']==='2026-11-24','Isian lampiran kosong memutus tanggal BA');
 $c['dokumen_referensi']=['LAMPIRAN_NEGOSIASI'=>['nomor_dokumen'=>'LAMP/EDIT','tanggal_dokumen'=>'2026-11-25']];
 checkNegotiation(ProcurementProcessService::documentValue($c,'LAMPIRAN_NEGOSIASI')['nomor']==='LAMP/EDIT','Identitas lampiran hasil edit tertimpa');
}
checkNegotiation(ProcurementProcessService::documentValue([],'LAMPIRAN_NEGOSIASI')['tanggal']==='','Tanggal lampiran dibuat tanpa sumber');
$restricted=$master;$restricted['kode']='UNDANGAN_PL';$restricted['metode_pemilihan']='Pengadaan Langsung';
try{$applicable->invoke($service,$restricted,['cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','metode_pemilihan'=>'E-Purchasing']);throw new RuntimeException('Master khusus metode lolos');}catch(InvalidArgumentException $expected){}
echo "PASS: lampiran lintas metode, urutan bundel, tanggal BA, identitas edit, dan pembatasan master lain.\n";
