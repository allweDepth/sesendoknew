<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../../app/Core/DB.php';
$db=DB::getInstance();$db->begin();
try{
 $c=$db->query('SELECT * FROM kontrak_neo WHERE id=821 AND tahun=2026 AND is_deleted=0 FOR UPDATE')->fetch();
 if(!$c||$c['uraian_kontrak']!=='Normalisasi Sungai/Saluran Desa Saptanajaya')throw new RuntimeException('Package mismatch');
 $original=$db->query('SELECT * FROM rekanan_neo WHERE id=? AND is_deleted=0 FOR UPDATE',[$c['rekanan_id']])->fetch();
 if(!$original||$original['nama_perusahaan']!=='CV. ALIF MAPPARIMULA'||$original['direktur']!=='IZHAR FAUSAN')throw new RuntimeException('Original vendor mismatch');
 $settings=$db->query('SELECT * FROM kontrak_print_settings_neo WHERE kontrak_id=821 FOR UPDATE')->fetch();
 $others=$db->query('SELECT id,rekanan_id,nama_penyedia FROM kontrak_neo WHERE id<>821 ORDER BY id')->fetchAll();
 if(file_put_contents(__DIR__.'/before-'.date('Ymd-His').'.json',json_encode(['contract'=>$c,'vendor'=>$original,'settings'=>$settings],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
 $actor='SAPTANAJAYA_KUASA_20261119';$now=date('Y-m-d H:i:s');
 $clone=$original;unset($clone['id']);
 $clone['excel_source_id']=null;$clone['direktur']='M. SUAIB NUR, ST';$clone['jabatan']='Kuasa Direktur';
 $clone['no_ktp']='7605012101900001';$clone['alamat_dir']='Lingkungan Garogo, Kelurahan Baru, Kecamatan Banggae, Kabupaten Majene, Provinsi Sulawesi Barat';
 $clone['keterangan']='Duplikat rekanan ID '.$original['id'].' untuk paket Saptanajaya (821). Penandatangan M. SUAIB NUR, ST sebagai Kuasa Direktur; pemberi kuasa IZHAR FAUSAN. Sumber: KUASA DIREKSI CV. ALIF MAPPARIMULA.pdf.';
 $clone['username_insert']=$actor;$clone['tgl_insert']=$now;$clone['username_update']=$actor;$clone['tgl_update']=$now;
 $newId=(int)$db->insert('rekanan_neo',$clone);
 if(!$newId)throw new RuntimeException('Clone failed');
 $changes=['rekanan_id'=>$newId,'username_update'=>$actor,'tgl_update'=>$now];
 $db->update('kontrak_neo',$changes,'WHERE id=?',[$c['id']]);
 $raw=$settings?json_decode($settings['settings_json'],true,512,JSON_THROW_ON_ERROR):[];
 $raw['offer_print']=array_replace($raw['offer_print']??[],['penandatangan_nama'=>'M. SUAIB NUR, ST','penandatangan_jabatan'=>'Kuasa Direktur']);
 $json=json_encode($raw,JSON_UNESCAPED_UNICODE);
 $db->query('INSERT INTO kontrak_print_settings_neo(kontrak_id,settings_json,updated_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE settings_json=VALUES(settings_json),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP',[821,$json,$actor]);
 if($db->query('SELECT * FROM rekanan_neo WHERE id=?',[$original['id']])->fetch()!==$original)throw new RuntimeException('Original vendor changed');
 if($db->query('SELECT id,rekanan_id,nama_penyedia FROM kontrak_neo WHERE id<>821 ORDER BY id')->fetchAll()!==$others)throw new RuntimeException('Other contracts changed');
 $after=$db->query('SELECT * FROM kontrak_neo WHERE id=821')->fetch();
 foreach($c as $key=>$value)if($after[$key]!=($changes[$key]??$value))throw new RuntimeException('Unexpected contract change '.$key);
 $new=$db->query('SELECT * FROM rekanan_neo WHERE id=?',[$newId])->fetch();
 foreach($clone as $key=>$value)if($new[$key]!=$value)throw new RuntimeException('Clone verification failed '.$key);
 if($db->query('SELECT settings_json FROM kontrak_print_settings_neo WHERE kontrak_id=821')->fetchColumn()!==$json)throw new RuntimeException('Print settings verification failed');
 $db->commit();
 $report=['contract_id'=>821,'original_vendor_id'=>$original['id'],'duplicate_vendor_id'=>$newId,'company'=>$new['nama_perusahaan'],'signer'=>$new['direktur'],'position'=>$new['jabatan'],'original_and_other_contracts_unchanged'=>true];
 file_put_contents(__DIR__.'/result.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
