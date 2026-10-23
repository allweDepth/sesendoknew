<?php
/** Copy the existing regional procurement-official SKs into the actual Tata Naskah register. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Core/DB.php';
$db=DB::getInstance();
$scope=['76.01','1.03.0.00.0.00.01.0000'];
$paragraphs=static function(?string $json):array{
 $rows=json_decode($json?:'[]',true,512,JSON_THROW_ON_ERROR);$result=[];
 foreach($rows as $row){$text=(string)($row['isi']??'');if(trim($text)==='')continue;$result[]=['type'=>($row['p_l']??'L')==='P'?'paragraph':'numbered','text'=>$text,'align'=>'justify'];}
 return $result;
};
$months=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$db->begin();
try{
 $template=$db->query('SELECT jenis_id,klasifikasi_id FROM trx_naskah_dinas WHERE id=2 AND kd_wilayah=? AND kd_opd=?',$scope)->fetch();
 if(!$template)throw new RuntimeException('Acuan jenis SK Tata Naskah tidak ditemukan');
 $result=[];
 foreach([115,133] as $sourceId){
  $sk=$db->query('SELECT * FROM sk_asn_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0',[$sourceId,...$scope])->fetch();
  if(!$sk||!str_contains(strtolower($sk['tentang']),'pejabat pengadaan'))throw new RuntimeException('SK sumber tidak valid');
  $existing=$db->query("SELECT id,nomor FROM trx_naskah_dinas WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND (keterangan=? OR LOWER(perihal) LIKE '%pejabat pengadaan%') FOR UPDATE",[...$scope,$sk['tahun'],'SYNC_SK_ASN:'.$sourceId])->fetchAll();
  if($existing){$result[]=['tahun'=>$sk['tahun'],'status'=>'already_exists','documents'=>$existing];continue;}
  if($db->query('SELECT id FROM trx_naskah_dinas WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND nomor=?',[...$scope,$sk['tahun'],$sk['nomor']])->fetch())throw new RuntimeException('Nomor dokumen sudah digunakan');
  $signer=$db->query('SELECT id FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd=? AND nip=? AND is_deleted=0',[...$scope,$sk['pemberi_tgs']])->fetch();
  if(!$signer)throw new RuntimeException('ASN penandatangan tidak ditemukan');
  $assigned=array_values(json_decode($sk['nama_ditugaskan'],true,512,JSON_THROW_ON_ERROR));
  $asnIds=[];
  foreach($assigned as &$person){
   $asn=$db->query('SELECT id FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd=? AND nip=? AND is_deleted=0',[...$scope,$person['nip']])->fetch();
   if(!$asn)throw new RuntimeException('ASN Pejabat Pengadaan tidak ditemukan');
   $person['id']=(int)$asn['id'];$person['_id']=(string)$asn['id'];$asnIds[]=(string)$asn['id'];
  }unset($person);
  $date=new DateTimeImmutable($sk['tgl_surat_dibuat']);
  $payload=['jenis_id'=>(string)$template['jenis_id'],'nomor'=>$sk['nomor'],'klasifikasi_id'=>(int)$template['klasifikasi_id'],'tanggal_surat'=>$date->format('j').' '.$months[(int)$date->format('n')].' '.$date->format('Y'),'perihal'=>$sk['tentang'],'file'=>'','penandatangan'=>(string)$signer['id'],'jbt_pemberi_tgs'=>'Kepala Dinas','pangkat_pemberi_tgs'=>$sk['pangkat_pemberi_tgs'],'nama_penandatangan'=>$sk['nama_pemberi_tgs'],'jabatan_penandatangan'=>'Kepala Dinas','nip_penandatangan'=>$sk['pemberi_tgs'],'nama_ditugaskan'=>$assigned,'asn'=>implode(',',$asnIds),'bentuk_lampiran'=>(int)$sk['bentuk_lampiran'],'disable'=>0,'personnel_rows_are_individual'=>true,'keterangan'=>'Disalin dari sk_asn_neo ID '.$sourceId];
  foreach(['menimbang','mengingat','menetapkan_1','menetapkan_2','menetapkan_3','menetapkan_4','tembusan'] as $field)$payload[$field]=$paragraphs($sk[$field]);
  $payload['menetapkan']=[['type'=>'paragraph','text'=>'KEPUTUSAN KEPALA DINAS PEKERJAAN UMUM DAN PENATAAN RUANG TENTANG '.mb_strtoupper($sk['tentang']),'align'=>'justify']];
  $now=(new DateTimeImmutable('now',new DateTimeZone('Asia/Makassar')))->format('Y-m-d H:i:s');
  $id=$db->insert('trx_naskah_dinas',['uuid'=>bin2hex(random_bytes(16)),'jenis_id'=>$template['jenis_id'],'nomor'=>$sk['nomor'],'nomor_urut'=>6,'tahun'=>$sk['tahun'],'klasifikasi_id'=>$template['klasifikasi_id'],'tanggal_surat'=>$sk['tgl_surat_dibuat'],'perihal'=>$sk['tentang'],'status'=>'draft','workflow_status'=>'draft','kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'username_insert'=>'codex_sk_pengadaan','tgl_insert'=>$now,'keterangan'=>'SYNC_SK_ASN:'.$sourceId]);
  $db->insert('trx_naskah_struktur',['naskah_id'=>$id,'struktur_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$sk['tahun'],'tgl_insert'=>$now,'username_insert'=>'codex_sk_pengadaan']);
  $saved=$db->query('SELECT n.nomor,n.tahun,s.struktur_json FROM trx_naskah_dinas n JOIN trx_naskah_struktur s ON s.naskah_id=n.id WHERE n.id=?',[$id])->fetch();
  $savedPayload=json_decode($saved['struktur_json'],true,512,JSON_THROW_ON_ERROR);
  if($savedPayload['nama_penandatangan']!==$sk['nama_pemberi_tgs']||$savedPayload['nama_ditugaskan'][0]['nip']!==$assigned[0]['nip'])throw new RuntimeException('Verifikasi isi dokumen gagal');
  $result[]=['tahun'=>$sk['tahun'],'status'=>'inserted','naskah_id'=>$id,'nomor'=>$sk['nomor'],'tanggal'=>$sk['tgl_surat_dibuat'],'penandatangan'=>$sk['nama_pemberi_tgs']];
 }
 $db->commit();echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
