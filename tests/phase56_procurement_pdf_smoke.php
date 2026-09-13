<?php
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/ProcurementDocumentService.php';
$db=DB::getInstance();
$contract=$db->query('SELECT * FROM kontrak_neo WHERE is_deleted=0 ORDER BY id DESC LIMIT 1')->fetch();
if(!$contract)throw new RuntimeException('Kontrak uji tidak tersedia');
$user=$db->query("SELECT * FROM user_sesendok_biila WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND disable=0 ORDER BY id LIMIT 1",[$contract['kd_wilayah'],$contract['kd_opd'],$contract['tahun']])->fetch();
if(!$user)throw new RuntimeException('Pengguna lingkup kontrak tidak tersedia');
$user['type_user']='admin_opd';$_SESSION['user']=$user;
$master=$db->query("SELECT * FROM master_dokumen_pengadaan_neo WHERE kode='SPK_KONSTRUKSI' AND aktif=1 AND is_deleted=0 LIMIT 1")->fetch();
if(!$master)throw new RuntimeException('Master SPK konstruksi tidak tersedia');
$db->begin();try{
  $documentId=$db->insert('dokumen_pengadaan_neo',['kontrak_id'=>$contract['id'],'master_id'=>$master['id'],'kode_dokumen'=>$master['kode'],'nomor_dokumen'=>$contract['nomor_kontrak'],'tanggal_dokumen'=>$contract['tanggal_kontrak']?:date('Y-m-d'),'judul'=>'Surat Perintah Kerja Pekerjaan Konstruksi','status'=>'DRAFT','dasar_hukum_snapshot'=>$master['dasar_hukum'],'versi_master'=>$master['versi'],'pembuat_role'=>'admin_opd','kd_wilayah'=>$contract['kd_wilayah'],'kd_opd'=>$contract['kd_opd'],'tahun'=>$contract['tahun'],'username_insert'=>'phase56_pdf_test','is_deleted'=>0]);
  $sections=$db->query('SELECT * FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND is_deleted=0 ORDER BY urutan,id',[$master['id']])->fetchAll();
  foreach($sections as $s)$db->insert('dokumen_pengadaan_bagian_neo',['dokumen_id'=>$documentId,'master_bagian_id'=>$s['id'],'kode_bagian'=>$s['kode_bagian'],'judul'=>$s['judul'],'urutan'=>$s['urutan'],'isi'=>$s['isi_template'],'username_insert'=>'phase56_pdf_test','is_deleted'=>0]);
  $db->insert('dokumen_pengadaan_lampiran_neo',['dokumen_id'=>$documentId,'jenis_lampiran'=>'DAFTAR_KUANTITAS','judul'=>'Daftar Kuantitas dan Harga','urutan'=>1,'isi'=>'<table border="1" cellpadding="4"><tr><th>Uraian</th><th>Nilai</th></tr><tr><td>Contoh pekerjaan</td><td>Rp 100.000.000</td></tr></table>','username_insert'=>'phase56_pdf_test','is_deleted'=>0]);
  $pdf=(new ProcurementDocumentService($user))->pdf((int)$documentId);
  file_put_contents('/tmp/phase56-procurement.pdf',$pdf);
  $db->rollback();
}catch(Throwable $e){$db->rollback();throw$e;}
echo "/tmp/phase56-procurement.pdf\n";
