<?php
require_once __DIR__.'/../app/Services/ProcurementReferenceTermsService.php';
require_once __DIR__.'/../app/Services/ContractPrintSettingsService.php';
function miniCheck(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$c=['cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','metode_pemilihan'=>'Mini Kompetisi','nilai_kontrak'=>2300000000,'terms_variant'=>'MINI_KOMPETISI','nama_ppk'=>'PPK UJI','nama_penyedia'=>'PENYEDIA UJI','waktu_pelaksanaan'=>38,'masa_pemeliharaan_bulan'=>6];
$ssuk=ProcurementReferenceTermsService::rows('SSUK',$c);$text=implode("\n",array_column($ssuk,'text'));
miniCheck(count($ssuk)>600&&str_contains($text,'83. Itikad Baik')&&str_contains($text,'73. Pembayaran'),'SSUK mini kompetisi tidak lengkap');
$clauseNumbers=[];foreach($ssuk as $r)if(($r['format']??'')==='clause-heading'&&preg_match('/^(\d+)\./',$r['text'],$m))$clauseNumbers[]=(int)$m[1];miniCheck($clauseNumbers===range(1,83),'Pasal SSUK hilang atau tidak berurutan');
$sskk=ProcurementReferenceTermsService::rows('SSKK',$c);$special=implode("\n",array_column($sskk,'text'));
foreach(['28.1 |','34.8 |','73.1.(e) |','73.2.(d) |','81.2 |','PPK UJI','PENYEDIA UJI','38 hari kalender','6 bulan'] as $needle)miniCheck(str_contains($special,$needle),'SSKK hilang: '.$needle);
miniCheck(!str_contains($special,'27.1 |')&&!str_contains($special,'70.1 (e) |'),'Pasal SSKK standar tercampur dengan mini kompetisi');
$sskk[5]['text']='28.1 | Masa Pelaksanaan | Nilai lama';
$filled=ProcurementReferenceTermsService::fillSskkRows($sskk,$c);
miniCheck(count($filled)===count($sskk),'Pengisian SSKK menambah pasal standar');
$standard=ProcurementReferenceTermsService::rows('SSKK',array_replace($c,['terms_variant'=>'STANDARD']));
miniCheck(str_contains(implode("\n",array_column($standard,'text')),'27.1 |'),'Format standar berubah');
foreach([array_replace($c,['jenis_pengadaan'=>'BARANG']),array_replace($c,['metode_pemilihan'=>'Pengadaan Langsung','nilai_kontrak'=>35000000]),array_replace($c,['terms_variant'=>'UNKNOWN'])] as $bad){$rejected=false;try{ProcurementMiniCompetitionTermsService::variant($bad);}catch(InvalidArgumentException $e){$rejected=true;}miniCheck($rejected,'Pilihan tidak valid diterima');}
miniCheck(ProcurementMiniCompetitionTermsService::eligible(array_replace($c,['nilai_kontrak'=>35000000])),'Surat pesanan konstruksi tidak tersedia');
miniCheck(ContractPrintSettingsService::validate([])['terms_variant']==='STANDARD','Dokumen lama bukan standar');
echo "PASS: SSUK lengkap, nomor pasal SSKK berpasangan, pengisian identitas, standar lama, batas jenis kontrak dan validasi pilihan.\n";

// Printed PDF labels are authoritative; a heading must never interrupt another clause.
$current=0;$subclauses=[];
foreach($ssuk as$row){
 if($row['format']==='clause-heading'&&preg_match('/^(\d+)\./',$row['text'],$m))$current=(int)$m[1];
 elseif($row['format']!=='clause-heading'&&preg_match('/^(\d+)\.(\d+)\.?\s/',$row['text'],$m)){
  miniCheck((int)$m[1]===$current,'Ayat berada di bawah judul pasal yang salah: '.$row['text']);$subclauses[]=$m[1].'.'.$m[2];
 }
}
foreach(['32.1','33.1','41.1','45.7','47.2','55.4','56.1','73.4','83.2']as$n)miniCheck(in_array($n,$subclauses,true),'Ayat cetak hilang: '.$n);
foreach(['16. Penemuan-penemuan','61. Kepemilikan Dokumen','64. Alih Pengalaman/Keahlian']as$heading)miniCheck(in_array($heading,array_column($ssuk,'text'),true),'Judul menyatu dengan isi: '.$heading);
$source=ProcurementMiniCompetitionTermsService::ssukSource();
miniCheck(hash_file('sha256',__DIR__.'/../resources/procurement/reference/ssuk-mini-kompetisi-bina-marga.json')===$source['content_sha256'],'Naskah berbeda dari hasil verifikasi PDF');
$old=['kode_dokumen'=>'SSUK','status'=>'DRAFT','data_isian'=>json_encode(['terms_variant'=>'MINI_KOMPETISI']),'sections'=>[['kode_bagian'=>'ISI','isi'=>json_encode([['text'=>'1. LINGKUP PEKERJAAN']])]]];
$fixed=ProcurementMiniCompetitionTermsService::ssukDocument($old,$c);
miniCheck(json_decode($fixed['sections'][0]['isi'],true)===$ssuk,'Draft berlabel mini masih memuat naskah SPK');
miniCheck(ProcurementMiniCompetitionTermsService::ssukDocument($fixed,$c)===$fixed,'Normalisasi SSUK tidak idempoten');
foreach(['DIAJUKAN','DISETUJUI','DITANDATANGANI']as$status){$frozen=array_replace($old,['status'=>$status]);miniCheck(ProcurementMiniCompetitionTermsService::ssukDocument($frozen,$c)===$frozen,'Snapshot non-draft berubah');}
$standard=array_replace($old,['data_isian'=>'{"terms_variant":"STANDARD"}']);
miniCheck(ProcurementMiniCompetitionTermsService::ssukDocument($standard,$c)===$standard,'SSUK standar ikut diganti');
echo "PASS: ayat dan judul sesuai PDF, draft SPK lama dipulihkan, snapshot non-draft dan standar dipertahankan.\n";
