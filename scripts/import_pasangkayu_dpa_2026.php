<?php
/** Import DPA SIPD PUPR Pasangkayu 2026 ke seluruh fondasi dokumen anggaran. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__.'/../app/Core/DB.php';

$jsonPath=$argv[1]??'';
if(!is_file($jsonPath))throw new RuntimeException('JSON hasil ekstraksi tidak ditemukan');
$payload=json_decode(file_get_contents($jsonPath),true,512,JSON_THROW_ON_ERROR);
if(($payload['kd_wilayah']??'')!=='76.01'||(int)($payload['tahun']??0)!==2026)throw new RuntimeException('Scope sumber bukan Pasangkayu tahun 2026');
if(count($payload['documents']??[])!==33||(int)($payload['total_items']??0)!==525||abs((float)$payload['total_amount']-42280799999)>0.01)throw new RuntimeException('Rekonsiliasi sumber belum cocok; import dibatalkan');

$db=DB::getInstance();$scope=['76.01','1.03.0.00.0.00.01.0000',2026];$username='IMPORT_DPA_SIPD_PUPR_PASANGKAYU_2026';
$tables=['renja_neo','rka_neo','dpa_neo','renja_p_neo','rka_p_neo','dppa_neo'];
foreach($tables as$table){
  $cols=array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field');
  if(!in_array('sumber_dana_teks',$cols,true))$db->query("ALTER TABLE `$table` ADD COLUMN sumber_dana_teks VARCHAR(500) NULL AFTER sumber_dana_id");
  if(!in_array('koefisien_keterangan',$cols,true))$db->query("ALTER TABLE `$table` ADD COLUMN koefisien_keterangan VARCHAR(500) NULL AFTER volume");
  $db->query("ALTER TABLE `$table` MODIFY kelompok VARCHAR(500) NULL, MODIFY sat_1 VARCHAR(100) NULL, MODIFY sat_2 VARCHAR(100) NULL, MODIFY sat_3 VARCHAR(100) NULL, MODIFY sat_4 VARCHAR(100) NULL, MODIFY sat_5 VARCHAR(100) NULL");
}

$normalize=static fn(string$value):string=>mb_strtolower(trim(preg_replace('/\s+/u',' ',$value)),'UTF-8');
$standards=[];
foreach($db->query("SELECT id,tipe,uraian,harga,tkdn FROM master_biaya WHERE kd_wilayah=? AND tahun=? AND is_deleted=0",['76.01',2026])->fetchAll()as$row){
  $standards[$normalize((string)$row['uraian']).'|'.number_format((float)$row['harga'],2,'.','')][]=$row;
}
$funds=$db->query('SELECT id,uraian FROM sumber_dana_neo WHERE is_deleted=0')->fetchAll();
$fundId=static function(string$name)use($funds,$normalize):?int{$needle=$normalize($name);foreach($funds as$f){$hay=$normalize((string)$f['uraian']);if($needle!==''&&($hay===$needle||str_contains($hay,$needle)||str_contains($needle,$hay)))return(int)$f['id'];}return null;};
$columns=[];foreach($tables as$table)$columns[$table]=array_flip(array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field'));
$filter=static fn(array$row,array$allowed):array=>array_intersect_key($row,$allowed);

$db->begin();
try{
  foreach($tables as$table)$db->query("UPDATE `$table` SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0",[$username,...$scope]);
  $db->query("UPDATE rencana_realisasi_anggaran_neo SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0",[$username,...$scope]);
  $db->query("UPDATE rencana_rekening_anggaran_neo SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0",[$username,...$scope]);
  $dummy=$db->query("UPDATE master_biaya SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE kd_wilayah=? AND tahun=? AND is_deleted=0 AND (kode LIKE 'PUPR-PAGU-%' OR UPPER(COALESCE(keterangan,'')) LIKE '%DUMMY%')",[$username,'76.01',2026])->rowCount();
  $ids=[];$matched=0;$unmatched=0;
  foreach($payload['documents']as$document){
    $code=(string)$document['sub_kegiatan'];$ids[$code]=[];
    foreach($document['items']as$item){
      $key=$normalize((string)$item['komponen']).'|'.number_format((float)$item['harga_satuan'],2,'.','');$match=$standards[$key][0]??null;$match?$matched++:$unmatched++;
      $base=['source_table'=>'sipd_dpa_pdf','source_id'=>null,'kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'kd_sub_keg'=>$code,'kd_akun'=>$item['kd_akun'],'kel_rek'=>$item['kd_akun'],'objek_belanja'=>str_starts_with($item['kd_akun'],'5.2')?'belanja_modal':'belanja_operasi','uraian'=>$item['uraian_kelompok'],'jenis_kelompok'=>'pemaketan','kelompok'=>$item['kelompok'],'jenis_standar_harga'=>$match?strtoupper($match['tipe']):null,'id_standar_harga'=>$match['id']??null,'komponen'=>$item['komponen'],'spesifikasi'=>$item['spesifikasi'],'tkdn'=>$match['tkdn']??0,'pajak'=>$item['pajak'],'harga_satuan'=>$item['harga_satuan'],'volume'=>$item['volume'],'koefisien_keterangan'=>$item['koefisien_keterangan'],'sat_5'=>$item['satuan'],'jumlah'=>$item['jumlah'],'sumber_dana_id'=>$fundId((string)$item['sumber_dana']),'sumber_dana_teks'=>$item['sumber_dana']?:$document['sumber_pendanaan'],'keterangan'=>'Biaya Utama','disable'=>0,'kunci'=>0,'setujui'=>0,'is_deleted'=>0,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$username];
      foreach(range(1,5)as$i){$base['vol_'.$i]=$item['factors'][$i-1]??0;$base['sat_'.$i]=$item['factor_units'][$i-1]??'';}
      $renjaId=(int)$db->insert('renja_neo',$filter($base,$columns['renja_neo']));$ids[$code]['renja'][]=$renjaId;
      $rka=$base;$rka['source_table']='renja_neo';$rka['source_id']=$renjaId;$rkaId=(int)$db->insert('rka_neo',$filter($rka,$columns['rka_neo']));$ids[$code]['rka'][]=$rkaId;
      $dpa=$base;$dpa['source_table']='rka_neo';$dpa['source_id']=$rkaId;$dpaId=(int)$db->insert('dpa_neo',$filter($dpa,$columns['dpa_neo']));$ids[$code]['dpa'][]=$dpaId;
      foreach([['renja_p_neo','renja_neo',$renjaId],['rka_p_neo','rka_neo',$rkaId],['dppa_neo','dpa_neo',$dpaId]]as[$target,$source,$sourceId]){$change=$base;$change['source_table']=$source;$change['source_id']=$sourceId;$change['status_perubahan']='awal';foreach(['jenis_standar_harga','id_standar_harga','komponen','spesifikasi','tkdn','pajak','harga_satuan','volume','jumlah']as$field)$change[$field.'_awal']=$base[$field];$db->insert($target,$filter($change,$columns[$target]));}
    }
    // PDF memberi rencana kas per subkegiatan. Distribusikan proporsional per rekening,
    // dengan rekening terakhir menampung koreksi pembulatan agar total tetap persis.
    $accountTotals=[];foreach($document['items']as$item)$accountTotals[$item['kd_akun']]=($accountTotals[$item['kd_akun']]??0)+(float)$item['jumlah'];
    $docTotal=array_sum($accountTotals);$accounts=array_keys($accountTotals);
    foreach(['dpa','dppa']as$logical)foreach(($document['monthly']??[])as$month=>$monthTotal){$left=(float)$monthTotal;foreach($accounts as$idx=>$account){$value=$idx===array_key_last($accounts)?$left:round($docTotal?((float)$monthTotal*$accountTotals[$account]/$docTotal):0,2);$left-=$value;$db->query('INSERT INTO rencana_rekening_anggaran_neo(dokumen,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,jenis,bulan,nilai,username_insert,is_deleted) VALUES(?,?,?,?,?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai),username_update=VALUES(username_insert),tgl_update=NOW(),is_deleted=0',[$logical,...$scope,$code,$account,'belanja',(int)$month,$value,$username]);}}
  }
  foreach($tables as$table){$check=$db->query("SELECT COUNT(*) jumlah,COALESCE(SUM(jumlah),0) total FROM `$table` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0",$scope)->fetch();if((int)$check['jumlah']!==525||abs((float)$check['total']-42280799999)>0.01)throw new RuntimeException("Rekonsiliasi tabel $table gagal");}
  $db->commit();echo json_encode(['documents'=>33,'items_per_stage'=>525,'total_per_stage'=>42280799999,'standard_matched'=>$matched,'standard_unmatched'=>$unmatched,'dummy_prices_removed'=>$dummy],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable$e){$db->rollback();throw$e;}
