<?php
/** CLI: seed missing defaults and export portable, annotated SQL. Never changes saved documents. */
if(PHP_SAPI!=='cli')exit;
chdir(dirname(__DIR__));
spl_autoload_register(function($c){foreach(['app/Core/','app/Services/']as$p)if(is_file($p.$c.'.php')){require_once$p.$c.'.php';return;}});
$db=DB::getInstance();$schema=file_get_contents('database/swakelola/schema.sql');
if(in_array('--install', $argv, true))foreach(explode(';',$schema)as$sql)if(trim($sql)!=='')$db->query($sql);
$json=fn($x)=>json_encode($x,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$masters=$db->query("SELECT m.kode,m.nama,s.kode_bagian,s.id,s.isi_template FROM master_dokumen_pengadaan_neo m JOIN master_dokumen_pengadaan_bagian_neo s ON s.master_id=m.id AND s.is_deleted=0 WHERE m.kode LIKE 'SW\\_%' AND m.aktif=1 AND m.is_deleted=0 ORDER BY m.kode,s.urutan,s.id")->fetchAll();
$db->begin();try{
foreach($masters as$m){
 if($db->query('SELECT 1 FROM swakelola_teks_default_neo WHERE kode_dokumen=? AND kode_bagian=? LIMIT 1',[$m['kode'],$m['kode_bagian']])->fetch())continue;
 $blocks=SwakelolaDocumentService::loadBlocks($db,'master_bagian_id',(int)$m['id'])??json_decode($m['isi_template'],true,512,JSON_THROW_ON_ERROR);
 $blocks=SwakelolaTcpdfLayoutService::decorate($m['kode'],$blocks);$type=explode('_',$m['kode'])[1];
 $add=function($location,$parent,$kind,$order,$text,$format)use($db,$m,$type,$json){$db->insert('swakelola_teks_default_neo',['kode_dokumen'=>$m['kode'],'tipe'=>$type,'nama_dokumen'=>$m['nama'],'kode_bagian'=>$m['kode_bagian'],'kode_lokasi'=>$location,'lokasi_induk'=>$parent?:null,'jenis_blok'=>$kind,'urutan'=>$order,'teks_default'=>$text,'format_json'=>$json($format)]);};
 $walk=function($items,$parent='')use(&$walk,$add){foreach($items as$i=>$b){$path=($parent?$parent.'/':'').($b['type']==='table'?'T':'P').sprintf('%03d',$i+1);$meta=$b;unset($meta['text'],$meta['rows']);$add($path,$parent,$b['type'],$i,$b['text']??null,$meta);if($b['type']==='table')foreach($b['rows']as$r=>$row){$rp=$path.'/R'.sprintf('%03d',$r+1);$add($rp,$path,'row',$r,null,[]);foreach($row as$c=>$cell){$cp=$rp.'/C'.sprintf('%03d',$c+1);$cm=$cell;unset($cm['blocks']);$add($cp,$rp,'cell',$c,null,$cm);$walk($cell['blocks'],$cp);}}}};$walk($blocks);
}
$db->query("INSERT IGNORE INTO swakelola_pengaturan_default_neo(tipe,kunci,nilai_json,keterangan) VALUES('SEMUA','paragraf',?,?)",[$json(['font'=>'Arial','size'=>11,'line_height'=>1,'before'=>0,'after'=>6]),'Fallback hanya untuk properti paragraf yang belum diatur. Format tiap baris tetap lebih utama. Kertas dan margin dari pengaturan OPD.']);
$db->commit();}catch(Throwable$e){$db->rollback();throw$e;}
$rows=$db->query('SELECT kode_dokumen,tipe,nama_dokumen,kode_bagian,kode_lokasi,lokasi_induk,jenis_blok,urutan,teks_default,format_json,aktif FROM swakelola_teks_default_neo ORDER BY kode_dokumen,kode_bagian,kode_lokasi')->fetchAll();
// Readable Unicode SQL; NO_BACKSLASH_ESCAPES preserves JSON and literal newlines.
$literal=fn($v)=>$v===null?'NULL':"'".str_replace("'","''",(string)$v)."'";
$out="-- DEFAULT SWAKELOLA I / II / III / IV — seSendoknew\n-- Pilih database sesendoknew_db sebelum import. Tidak mengubah dokumen hasil.\n-- Import ulang mengembalikan default ke isi file ini; backup penyesuaian terlebih dahulu.\nSET NAMES utf8mb4;\nSET @swakelola_sql_mode=@@SESSION.sql_mode;\nSET SESSION sql_mode=CONCAT(@@SESSION.sql_mode,',NO_BACKSLASH_ESCAPES');\n".$schema."\nSTART TRANSACTION;\n";
foreach(['swakelola_pengaturan_default_neo'=>$db->query('SELECT * FROM swakelola_pengaturan_default_neo ORDER BY tipe,kunci')->fetchAll(),'swakelola_teks_default_neo'=>$rows]as$table=>$records)foreach($records as$r){$out.='-- '.($r['kode_dokumen']??$r['tipe']).' | '.($r['kode_bagian']??'pengaturan').' | '.($r['kode_lokasi']??$r['kunci']).' | '.($r['jenis_blok']??'JSON')."\n";if(isset($r['teks_default'])&&$r['teks_default']!=='')$out.='-- Teks: '.str_replace(["\r","\n"],['',' / '],mb_substr($r['teks_default'],0,180))."\n";$columns=array_keys($r);$out.="INSERT INTO $table (`".implode('`,`',$columns).'`) VALUES ('.implode(',',array_map($literal,array_values($r))).') ON DUPLICATE KEY UPDATE '.implode(',',array_map(fn($k)=>"`$k`=VALUES(`$k`)",$columns)).";\n";}
$out.="COMMIT;\nSET SESSION sql_mode=@swakelola_sql_mode;\n";file_put_contents('database/swakelola/default-swakelola.sql',$out);
echo count($masters).' bagian, '.count($rows).' baris default; SQL '.strlen($out)." byte.\n";
