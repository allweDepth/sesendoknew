<?php
require_once __DIR__.'/../app/Services/ProcurementReferenceTermsService.php';
require_once __DIR__.'/../app/Services/ProcurementContractFormService.php';
function checkAgreement(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$old=[['type'=>'paragraph','align'=>'center','text'=>'Pasal 1 ISTILAH DAN UNGKAPAN'],['text'=>'(b) Kalimat panjang yang harus menjadi daftar.'],['text'=>'1) Anak daftar dengan teks panjang.'],['text'=>'a) Daftar dokumen.'],['text'=>'Isi paragraf biasa.']];
$fixed=ProcurementParagraphLayoutService::normalize($old);
checkAgreement(count($fixed)===6&&$fixed[0]['text']==='Pasal 1'&&$fixed[1]['text']==='ISTILAH DAN UNGKAPAN','Pasal dan nama pasal belum terpisah');
checkAgreement($fixed[2]['format']==='hanging-list'&&$fixed[3]['format']==='hanging-list'&&$fixed[4]['format']==='hanging-list','Format daftar lama tidak dipulihkan');
checkAgreement(!isset($fixed[5]['format']),'Paragraf biasa berubah menjadi daftar');
checkAgreement(ProcurementParagraphLayoutService::normalize($fixed)===$fixed,'Normalisasi tidak idempoten');
foreach(['PEKERJAAN_KONSTRUKSI','KONSULTANSI_KONSTRUKSI','BARANG','JASA_LAINNYA','KONSULTANSI_NON_KONSTRUKSI'] as $kind){
 $rows=ProcurementReferenceTermsService::rows('SURAT_PERJANJIAN',['jenis_pengadaan'=>$kind,'uraian_kontrak'=>'Paket Uji','waktu_pelaksanaan'=>30]);
 $text=implode("\n",array_column($rows,'text'));$pasal=array_filter($rows,fn($row)=>preg_match('/^Pasal \d+$/',$row['text']));
 checkAgreement(count($pasal)===(in_array($kind,['PEKERJAAN_KONSTRUKSI','KONSULTANSI_KONSTRUKSI'],true)?5:6),'Jumlah pasal salah '.$kind);
 if($kind==='KONSULTANSI_KONSTRUKSI')checkAgreement(!str_contains($text,'Masa Pemeliharaan')&&str_contains($text,'Daftar Personel')&&str_contains($text,'Kerangka Acuan Kerja'),'Model konsultansi masih memakai syarat pelaksanaan konstruksi');
 if(in_array($kind,['BARANG','JASA_LAINNYA','KONSULTANSI_NON_KONSTRUKSI'],true))checkAgreement(!str_contains($text,'Undang-Undang Nomor 2 Tahun 2017')&&str_contains($text,'HAK DAN KEWAJIBAN'),'Model LKPP masih memakai model PUPR');
}
echo "PASS: paragraf terpisah, inden daftar lama, normalisasi idempoten, lima jenis model perjanjian.\n";

foreach([
 ['PEKERJAAN_KONSTRUKSI','Pengadaan Langsung',400000000,'SPK'],['PEKERJAAN_KONSTRUKSI','Tender',400000001,'SURAT_PERJANJIAN'],
 ['PEKERJAAN_KONSTRUKSI','E-purchasing',1460793000,'SURAT_PERJANJIAN'],['PEKERJAAN_KONSTRUKSI','Mini Kompetisi',400000000,'SURAT_PESANAN'],
 ['BARANG','Pengadaan Langsung',200000000,'SPK'],['BARANG','Tender',200000001,'SURAT_PERJANJIAN'],['BARANG','E-katalog',900000000,'SURAT_PESANAN'],
 ['JASA_LAINNYA','Pengadaan Langsung',200000001,'SURAT_PERJANJIAN'],['KONSULTANSI_KONSTRUKSI','Pengadaan Langsung',100000000,'SPK'],['KONSULTANSI_KONSTRUKSI','Seleksi',100000001,'SURAT_PERJANJIAN']
] as [$kind,$method,$value,$form])checkAgreement(ProcurementContractFormService::recommend(['jenis_pengadaan'=>$kind,'metode_pemilihan'=>$method,'nilai_kontrak'=>$value])===$form,'Bentuk kontrak salah pada batas nilai: '.$kind.' '.$method.' '.$value);
echo "PASS: batas nilai SPK/perjanjian, pesanan katalog, dan perjanjian konstruksi katalog.\n";
