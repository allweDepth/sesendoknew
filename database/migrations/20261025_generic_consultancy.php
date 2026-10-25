<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../../app/Core/DB.php';$db=DB::getInstance();$c=require __DIR__.'/../../config/database.php';
$pdo=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach(['kontrak_neo','master_dokumen_pengadaan_neo'] as $table){$type=$pdo->query("SHOW COLUMNS FROM `$table` LIKE 'jenis_pengadaan'")->fetch(PDO::FETCH_ASSOC)['Type'];if(!str_contains($type,"'KONSULTANSI'")){$type=substr($type,0,-1).",'KONSULTANSI')";$default=$table==='kontrak_neo'?'BARANG':'SEMUA';$pdo->exec("ALTER TABLE `$table` MODIFY jenis_pengadaan $type NOT NULL DEFAULT '$default'");}}
$db->begin();try{
 if(!$db->query("SELECT id FROM master_dokumen_pengadaan_neo WHERE kode='SPK_KONSULTANSI'")->fetch()){
  $source=$db->query("SELECT * FROM master_dokumen_pengadaan_neo WHERE kode='SPK_KONSULTANSI_NON_KONSTRUKSI' AND is_deleted=0")->fetch();if(!$source)throw new RuntimeException('Consultancy SPK master missing');$oldId=$source['id'];unset($source['id']);$source['kode']='SPK_KONSULTANSI';$source['nama']='Surat Perintah Kerja Konsultansi';$source['jenis_pengadaan']='KONSULTANSI';$source['username_insert']='migration_generic_consultancy';$source['tgl_insert']=date('Y-m-d H:i:s');$newId=$db->insert('master_dokumen_pengadaan_neo',$source);
  foreach($db->query('SELECT * FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND is_deleted=0',[$oldId])->fetchAll() as $part){unset($part['id']);$part['master_id']=$newId;$db->insert('master_dokumen_pengadaan_bagian_neo',$part);}
 }
 $db->commit();echo "Generic Konsultansi category and SPK master ready.\n";
}catch(Throwable $e){$db->rollback();throw $e;}
