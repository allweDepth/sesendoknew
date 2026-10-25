<?php
/** CLI-only, scoped, transactional import of SIPD before/after PDFs. Default: dry run. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Core/DB.php';
$path=$argv[1]??'';$apply=in_array('--apply',$argv,true);$partial=in_array('--available-only',$argv,true);
$p=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
$scope=['76.01','1.03.0.00.0.00.01.0000',2026];
if([$p['kd_wilayah'],$p['kd_opd'],(int)$p['tahun']]!==$scope)throw new RuntimeException('Invalid import scope');
$cents=static fn($n)=>(int)round((float)$n*100);
$norm=static fn($v)=>trim((string)$v)==='-'?'':mb_strtolower(trim((string)preg_replace('/\s+/u',' ',(string)$v)));
$key=static fn($r)=>implode('|',[$r['kd_sub_keg'],$r['kd_akun'],$norm($r['komponen']??''),$norm($r['spesifikasi']??'')]);
$documents=[];$recap=$p['recap']['rows'];
foreach($p['documents'] as $doc){
 $code=$doc['sub_kegiatan'];if(isset($documents[$code])||!isset($recap[$code]))throw new RuntimeException('Unexpected/duplicate source code');
 foreach(['before','after'] as $label){$sum=0;foreach($doc['items'] as $item)$sum+=$cents($item[$label]['jumlah']);if($sum!==$cents($recap[$code][$label]))throw new RuntimeException("Source total mismatch $code $label");}
 $documents[$code]=$doc;
}
$db=DB::getInstance();$tables=['renja_p_neo'=>'renja_neo','rka_p_neo'=>'rka_neo','dppa_neo'=>'dpa_neo'];
$original=[];$columns=[];$initialTotals=[];$missing=[];
foreach($tables as $target=>$source){
 $columns[$target]=array_flip(array_column($db->query("SHOW COLUMNS FROM `$target`")->fetchAll(),'Field'));
 $original[$source]=$db->query("SELECT * FROM `$source` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY id",$scope)->fetchAll();
 $initialTotals[$source]=array_reduce($original[$source],fn($n,$r)=>$n+$cents($r['jumlah']),0);
}
$dpaTotals=[];foreach($original['dpa_neo'] as $r)$dpaTotals[$r['kd_sub_keg']]=($dpaTotals[$r['kd_sub_keg']]??0)+$cents($r['jumlah']);
foreach($recap as $code=>$row){
 if(isset($documents[$code]))continue;
 if($cents($row['after'])!==($dpaTotals[$code]??0))$missing[$code]=$row;
 elseif($cents($row['before'])!==$cents($row['after']))throw new RuntimeException("Missing changed detail $code");
}
if($apply && $missing && !$partial)throw new RuntimeException('Missing detail PDFs: '.implode(', ',array_keys($missing)));
$username='IMPORT_SIPD_DPPA_PUPR_2026';$now=date('Y-m-d H:i:s');$stats=[];$funds=$db->query('SELECT id,uraian FROM sumber_dana_neo WHERE is_deleted=0')->fetchAll();
$findFund=static function($text)use($funds,$norm){foreach($funds as $f)if($norm($f['uraian'])===$norm($text))return $f['id'];return null;};
$db->begin();
try{
 foreach($tables as $target=>$source){
  $existing=$db->query("SELECT * FROM `$target` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY id FOR UPDATE",$scope)->fetchAll();
  $index=[];foreach($existing as $r)$index[$key($r)][]=$r;
  $sourceIndex=[];foreach($original[$source] as $r)$sourceIndex[$key($r)][]=$r;
  $used=[];$stats[$target]=['updated'=>0,'inserted'=>0,'retired'=>0];
  foreach($documents as $code=>$doc){
   foreach($doc['items'] as $i=>$item){
    $identity=$key($item+['kd_sub_keg'=>$code]);$before=$item['before'];$after=$item['after'];$match=null;
    foreach($sourceIndex[$identity]??[] as $j=>$candidate)if($cents($candidate['jumlah'])===$cents($before['jumlah'])){$match=$candidate;unset($sourceIndex[$identity][$j]);break;}
    $old=null;
    foreach($index[$identity]??[] as $j=>$candidate){if(isset($used[$candidate['id']]))continue;if($match && (int)$candidate['source_id']===(int)$match['id']){$old=$candidate;break;}if(!$old)$old=$candidate;}
    $row=$old??$match??[];
    foreach(['id','tgl_update','username_update'] as $field)unset($row[$field]);
    $status=$cents($before['jumlah'])===0&&$cents($after['jumlah'])>0?'tambah':($cents($before['jumlah'])>0&&$cents($after['jumlah'])===0?'hapus':(($cents($before['jumlah'])===$cents($after['jumlah']) && $before['volume']==$after['volume'] && $before['harga_satuan']===$after['harga_satuan'])?'awal':'ubah'));
    $row=array_replace($row,['kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'kd_sub_keg'=>$code,'kd_akun'=>$item['kd_akun'],'kel_rek'=>$item['kd_akun'],'objek_belanja'=>str_starts_with($item['kd_akun'],'5.2.')?'belanja_modal':'belanja_operasi','source_table'=>$source,'source_id'=>$match['id']??null,'uraian'=>$item['uraian_kelompok'],'jenis_kelompok'=>'pemaketan','kelompok'=>$item['kelompok'],'komponen'=>$item['komponen'],'spesifikasi'=>$item['spesifikasi'],'volume'=>$after['volume'],'harga_satuan'=>$after['harga_satuan'],'jumlah'=>$after['jumlah'],'pajak'=>$after['pajak'],'koefisien_keterangan'=>$after['koefisien_keterangan'],'status_perubahan'=>$status,'sumber_dana_id'=>$findFund($item['sumber_dana']),'sumber_dana_teks'=>$item['sumber_dana'],'is_deleted'=>0,'disable'=>0,'kunci'=>$old['kunci']??0,'setujui'=>$old['setujui']??0,'keterangan'=>'Sumber PDF: '.$doc['source_file'].'; halaman '.$item['source_page'].'; SHA256 '.$doc['sha256']]);
    foreach(['komponen','spesifikasi'] as $field)$row[$field.'_awal']=$item[$field];
    foreach(['volume','harga_satuan','jumlah','pajak'] as $field)$row[$field.'_awal']=$before[$field];
    foreach(['jenis_standar_harga','id_standar_harga','tkdn'] as $field)$row[$field.'_awal']=$match[$field]??null;
    // A changed price must not retain a reference to a different standard price.
    if(!$match || (float)$match['harga_satuan']!==(float)$after['harga_satuan']){$row['jenis_standar_harga']=null;$row['id_standar_harga']=null;}
    foreach(range(1,5) as $n){$row['vol_'.$n]=$after['factors'][$n-1];$row['sat_'.$n]=$after['factor_units'][$n-1];}
    if(!trim((string)$row['sat_1']))$row['sat_1']=$after['satuan'];
    $row=array_intersect_key($row,$columns[$target]);
    if($old){$used[$old['id']]=true;unset($row['tgl_insert'],$row['username_insert']);$row['tgl_update']=$now;$row['username_update']=$username;$db->update($target,$row,'WHERE id=?',[$old['id']]);$stats[$target]['updated']++;}
    else{$row['tgl_insert']=$now;$row['username_insert']=$username;$db->insert($target,$row);$stats[$target]['inserted']++;}
   }
  }
  foreach($existing as $r)if(isset($documents[$r['kd_sub_keg']])&&!isset($used[$r['id']])){$db->update($target,['is_deleted'=>1,'tgl_update'=>$now,'username_update'=>$username],'WHERE id=?',[$r['id']]);$stats[$target]['retired']++;}
  // Verify full row content, not just totals, so swapped descriptions cannot pass.
  $rowSignature=static function($r)use($cents){return json_encode([$r['kd_akun'],$r['kelompok'],$r['uraian'],$r['komponen'],$r['spesifikasi'],$r['koefisien_keterangan'],round((float)$r['volume'],4),$cents($r['harga_satuan']),$cents($r['jumlah']),$cents($r['jumlah_awal']),$cents($r['harga_satuan_awal']),round((float)$r['volume_awal'],4)]);};
  foreach($documents as $code=>$doc){
   $expected=[];
   foreach($doc['items'] as $item){$r=['kd_akun'=>$item['kd_akun'],'kelompok'=>$item['kelompok'],'uraian'=>$item['uraian_kelompok'],'komponen'=>$item['komponen'],'spesifikasi'=>$item['spesifikasi'],'koefisien_keterangan'=>$item['after']['koefisien_keterangan'],'volume'=>$item['after']['volume'],'harga_satuan'=>$item['after']['harga_satuan'],'jumlah'=>$item['after']['jumlah'],'volume_awal'=>$item['before']['volume'],'harga_satuan_awal'=>$item['before']['harga_satuan'],'jumlah_awal'=>$item['before']['jumlah']];$expected[]=$rowSignature($r);}
   $saved=$db->query("SELECT * FROM `$target` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND kd_sub_keg=? AND is_deleted=0",[...$scope,$code])->fetchAll();
   $actualRows=array_map($rowSignature,$saved);sort($expected);sort($actualRows);
   if($expected!==$actualRows)throw new RuntimeException("Row content mismatch $target $code");
  }
  // Compare each scoped subactivity with the source recap, allowing only explicitly missing PDFs.
  $totals=$db->query("SELECT kd_sub_keg,COUNT(*) n,SUM(jumlah) amount FROM `$target` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 GROUP BY kd_sub_keg",$scope)->fetchAll();
  $actual=[];foreach($totals as $t)$actual[$t['kd_sub_keg']]=$t;
  foreach($recap as $code=>$r)if(!isset($missing[$code])&&$cents($actual[$code]['amount']??0)!==$cents($r['after']))throw new RuntimeException("Target total mismatch: $target $code");
  foreach($actual as $code=>$r)if(!isset($recap[$code]))throw new RuntimeException("Unexpected target subactivity: $code");
  $stats[$target]['active_rows']=array_sum(array_column($totals,'n'));$stats[$target]['total']=array_reduce($totals,fn($sum,$r)=>$sum+$cents($r['amount']),0)/100;
 }
 foreach($tables as $target=>$source){$unchanged=$db->query("SELECT * FROM `$source` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY id",$scope)->fetchAll();if($unchanged!=$original[$source])throw new RuntimeException('Initial document unexpectedly changed');}
 // Cash plans from the old DPPA cannot be presented as the new plan: these
 // RKA source PDFs contain no monthly distribution. Retire only stale DPPA plans.
 $cashRetired=0;
 foreach($documents as $code=>$doc){
  if($cents($doc['total_before'])===$cents($doc['total_after']))continue;
  foreach(['rencana_rekening_anggaran_neo','rencana_realisasi_anggaran_neo'] as $cashTable){
   $cashRetired+=$db->query("UPDATE `$cashTable` SET is_deleted=1,tgl_update=?,username_update=? WHERE dokumen='dppa' AND kd_wilayah=? AND kd_opd=? AND tahun=? AND kd_sub_keg=? AND is_deleted=0",[$now,$username,...$scope,$code])->rowCount();
  }
 }
 // Keep regional nomenclature tied to the year and the official recap labels.
 foreach($p['recap']['hierarchy'] as $code=>$r){
  $parts=count(explode('.',$code));$level=match($parts){3=>'program',5=>'kegiatan',6=>'sub_kegiatan',default=>null};
  if(!$level)continue;
  $db->query('INSERT INTO rekening_kegiatan_historis(tahun,kd_wilayah,kode,level,uraian,sumber) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE uraian=VALUES(uraian),sumber=VALUES(sumber)',[$scope[2],$scope[0],$code,$level,$r['name'],$p['recap']['source_file']]);
 }
 if(!$missing){
  foreach(array_keys($tables) as $target){
   $logical=substr($target,0,-4);
   $limit=$db->query('SELECT id FROM batas_pagu_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND dokumen=? LIMIT 1',[...$scope,$logical])->fetch();
   $data=['pagu_maksimal'=>$p['recap']['total_after'],'keterangan'=>'Sesuai rekap APBD Perubahan 2026 DPUPR','tgl_update'=>$now,'username_update'=>$username,'is_deleted'=>0];
   if($limit)$db->update('batas_pagu_opd_neo',$data,'WHERE id=?',[$limit['id']]);
   else $db->insert('batas_pagu_opd_neo',$data+['kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],'dokumen'=>$logical,'username_insert'=>$username]);
  }
 }
 if($apply){$db->commit();}else{$db->rollback();}
 echo json_encode(['applied'=>$apply,'scope'=>$scope,'source_documents'=>count($documents),'recap_total'=>$p['recap']['total_after'],'missing_details'=>$missing,'tables'=>$stats,'stale_dppa_cash_rows_retired'=>$cashRetired],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
