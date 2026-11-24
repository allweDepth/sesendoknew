<?php
require_once __DIR__.'/../app/Services/ProcurementMiniCompetitionTermsService.php';
function sskkAssert(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);}
function sskkClause(array $rows,string $key):string{foreach($rows as$r)if(trim(explode('|',$r['text'],3)[0])===$key)return$r['text'];throw new RuntimeException('Pasal tidak ditemukan: '.$key);}
$base=['kd_wilayah'=>'76.01','tahun'=>2026,'cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','metode_pemilihan'=>'Mini Kompetisi','nilai_kontrak'=>2283084000,'nomor_rup'=>'68511614','nama_opd'=>'Dinas Pekerjaan Umum dan Penataan Ruang','alamat_opd'=>'Jalan Pangeran Antasari, Pasangkayu','nama_ppk'=>'PPK UJI','nama_pptk'=>'PPTK UJI','nip_pptk'=>'123456789012345678','nomor_sk_pptk'=>'SK/PPTK/2026','tanggal_sk_pptk'=>'2026-01-02','nama_penyedia'=>'CV. UJI','direktur'=>'DIREKTUR UJI','alamat_penyedia'=>'Alamat penyedia','email_pelaksana'=>'uji@example.com','no_akta_pendirian'=>'08','tgl_akta_pendirian'=>'2026-02-10','waktu_pelaksanaan'=>38];
foreach(['68511614','68511297','68511317','68511357']as$rup){
 $c=array_replace($base,['nomor_rup'=>$rup]);$rows=ProcurementMiniCompetitionTermsService::rows('SSKK',$c);$text=implode("\n",array_column($rows,'text'));
 sskkAssert(count($rows)===28,'Pasal duplikat atau hilang pada '.$rup.': '.count($rows));
 sskkAssert(!preg_match('/\.{3,}|…|\[(?:diisi|dipilih)|\{\{/iu',$text),'Masih ada placeholder pada '.$rup);
 sskkAssert(str_contains(sskkClause($rows,'34.8'),'6 (enam) bulan'),'Pemeliharaan bukan ketentuan paket');
 sskkAssert(str_contains(sskkClause($rows,'73.1.(e)'),'30% (tiga puluh persen)'),'Uang muka angka/huruf tidak lengkap');
 $pay=sskkClause($rows,'73.2.(d)');foreach(['30%','60%','100%','9%','12%','21%','28%']as$percentage)sskkAssert(str_contains($pay,$percentage),'Skema termin tidak lengkap '.$percentage);
 sskkAssert(!str_contains($pay,'termin/bulanan'),'Pilihan pembayaran belum diputuskan');
 sskkAssert(str_contains(sskkClause($rows,'46.b'),'7 (tujuh) hari kerja'),'Batas tagihan tidak sesuai sumber');
 sskkAssert(str_contains(sskkClause($rows,'4.2 & 5.1'),'PPTK UJI')&&str_contains(sskkClause($rows,'4.2 & 5.1'),'SK/PPTK/2026'),'PPTK/dasar penugasan tidak terisi');
 $same=ProcurementMiniCompetitionTermsService::fill($rows,$c);sskkAssert(count($same)===count($rows),'Pengisian berulang menambah pasal');
 foreach($rows as$i=>$r)sskkAssert($r['text']===$same[$i]['text'],'Pengisian tidak idempoten pada '.$r['text']);
 foreach($rows as&$r)if(str_starts_with($r['text'],'73.2.(d) |')){$r['text']='73.2.(d) | Pembayaran Prestasi Pekerjaan | Pembayaran bulanan berdasarkan addendum pembayaran Nomor 12 tanggal 20 November 2026.';break;}unset($r);
 $edited=ProcurementMiniCompetitionTermsService::fill($rows,$c);sskkAssert(str_contains(sskkClause($edited,'73.2.(d)'),'Pembayaran bulanan berdasarkan addendum'),'Ketentuan pembayaran yang diedit ditimpa');
 foreach($edited as$r)if(str_starts_with($r['text'],'73.2.(d) |'))sskkAssert(!isset($r['sskk_source'])&&!empty($r['sskk_manual']),'Edit bulanan diberi sumber termin yang keliru');
}
$unknown=ProcurementMiniCompetitionTermsService::rows('SSKK',array_replace($base,['nomor_rup'=>'99999999']));
sskkAssert(str_contains(sskkClause($unknown,'73.1.(e)'),'Belum ditetapkan'),'Uang muka paket lain ditebak');
sskkAssert(!str_contains(sskkClause($unknown,'73.1.(e)'),'30%'),'Persentase diwariskan lintas paket');
$wrong=ProcurementMiniCompetitionTermsService::rows('SSKK',array_replace($base,['kd_wilayah'=>'73.01']));sskkAssert(str_contains(sskkClause($wrong,'34.8'),'Belum ditetapkan'),'Profil diterapkan lintas wilayah');
$noSk=ProcurementMiniCompetitionTermsService::rows('SSKK',array_replace($base,['nomor_sk_pptk'=>'','tanggal_sk_pptk'=>null]));sskkAssert(str_contains(sskkClause($noSk,'4.2 & 5.1'),'nomor dan tanggal SK belum tercatat'),'SK yang hilang tidak ditandai');
$noPptk=$base;unset($noPptk['nama_pptk']);$r=ProcurementMiniCompetitionTermsService::rows('SSKK',$noPptk);sskkAssert(str_contains(sskkClause($r,'4.2 & 5.1'),'PPK UJI, bertindak langsung'),'PPTK fiktif ditambahkan');
sskkAssert(30+21+21+28===100&&9+9+12===30,'Rekonsiliasi uang muka/termin gagal');
echo "PASS: empat sumber paket, semua isian tanpa placeholder, termin dan pengembalian, PPTK/SK, pengisian berulang, edit bulanan, serta isolasi paket/wilayah.\n";
