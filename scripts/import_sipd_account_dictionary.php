<?php
/** Memperbarui Referensi > Akun dari rekening resmi pada DPA SIPD. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once __DIR__.'/../app/Core/DB.php';
$path=$argv[1]??'';if(!is_file($path))throw new RuntimeException('JSON ekstraksi tidak ditemukan');
$payload=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
$year=(int)($payload['tahun']??0);
if(($payload['kd_wilayah']??'')!=='76.01'||$year<2000||$year>2100)throw new RuntimeException('Scope sumber tidak sesuai');
$db=DB::getInstance();
$rows=[];foreach($payload['documents']??[]as$document)foreach($document['accounts']??[]as$code=>$description)$rows[$code]=$description;
$actor='IMPORT_AKUN_DPA_SIPD_'.$year;
$db->begin();try{$inserted=0;$updated=0;foreach($rows as$code=>$description){$parts=array_map('intval',explode('.',$code));$values=array_pad($parts,6,null);$existing=$db->query('SELECT id,uraian FROM akun_neo WHERE kode=? LIMIT 1',[$code])->fetch();$data=['akun'=>$values[0],'kelompok'=>$values[1],'jenis_akun'=>$values[2],'objek'=>$values[3],'rincian_objek'=>$values[4],'sub_rincian_objek'=>$values[5],'uraian'=>$description,'peraturan'=>4,'disable'=>0,'keterangan'=>'Referensi rekening DPA SIPD PUPR Pasangkayu Tahun '.$year,'is_deleted'=>0,'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')];if($existing){$db->update('akun_neo',$data,'WHERE id=?',[(int)$existing['id']]);$updated++;}else{$data['kode']=$code;$data['username_insert']=$actor;$data['tgl_insert']=date('Y-m-d H:i:s');unset($data['username_update'],$data['tgl_update']);$db->insert('akun_neo',$data);$inserted++;}}if($db->query("SHOW TABLES LIKE 'akun_dokumen_neo'")->fetch())$db->query("UPDATE akun_dokumen_neo SET is_deleted=1,username_update='MIGRASI_KE_AKUN_NEO',tgl_update=NOW() WHERE is_deleted=0 AND sumber=?",['DPA SIPD PUPR '.$year]);$db->commit();echo json_encode(['reference'=>'akun_neo','year'=>$year,'accounts'=>count($rows),'inserted'=>$inserted,'updated'=>$updated,'budget_rows_changed'=>0],JSON_PRETTY_PRINT).PHP_EOL;}catch(Throwable$e){$db->rollback();throw$e;}
