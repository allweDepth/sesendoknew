<?php
require_once __DIR__.'/../app/Services/ContractListReportService.php';
function contractReportCheck(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$c=['nomor_rup'=>'00123456','tanggal_kontrak'=>'2026-11-23','tanggal_spmk'=>'2026-11-23','waktu_pelaksanaan'=>39,'nama_penyedia'=>'CV. UJI','proses_pengadaan'=>json_encode(['UNDANGAN_PL'=>['tanggal'=>'2026-11-20','nomor'=>'UND/01'],'BA_KLARIFIKASI_NEGOSIASI'=>['tanggal'=>'2026-11-23','nomor'=>'NEGO/01']])];
$r=ContractListReportService::project($c);
contractReportCheck(array_keys($r)===array_keys(ContractListReportService::COLUMNS),'Susunan kolom unduhan tidak konsisten');
contractReportCheck($r['tanggal_undangan']==='2026-11-20'&&$r['tanggal_negosiasi']==='2026-11-23','Tanggal proses tidak terbaca');
contractReportCheck($r['tanggal_kontrak']==='2026-11-23'&&$r['tanggal_spmk']==='2026-11-23'&&$r['waktu_pelaksanaan']===39,'Data pelaksanaan hilang');
contractReportCheck($r['tanggal_evaluasi']===''&&$r['tanggal_selesai']==='','Tanggal kosong direka');
contractReportCheck($r['nomor_rup']==='00123456','Kode RUP berubah menjadi angka');
$c['dokumen_referensi']=['BA_KLARIFIKASI_NEGOSIASI'=>['nomor_dokumen'=>'NEGO/02','tanggal_dokumen'=>'2026-11-24']];$r=ContractListReportService::project($c);
contractReportCheck($r['tanggal_negosiasi']==='2026-11-24'&&$r['nomor_negosiasi']==='NEGO/02','Perubahan dokumen hasil tidak diikuti');
$c['tanggal_spmk']=null;$c['dokumen_referensi']['SPMK']=['nomor_dokumen'=>'SPMK/02','tanggal_dokumen'=>'2026-11-25'];$r=ContractListReportService::project($c);
contractReportCheck($r['tanggal_spmk']==='2026-11-25','Tanggal SPMK hasil dokumen tidak terbaca');
echo "PASS: 32 kolom, tanggal proses, durasi, tanggal kosong, kode teks dan pembaruan dokumen hasil.\n";
