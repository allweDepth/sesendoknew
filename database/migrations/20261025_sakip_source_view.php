<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$c=require __DIR__.'/../../config/database.php';$name=getenv('SESENDOK_DB_NAME')?:$c['dbname'];
$p=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$name.';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$base=' JOIN tujuan_renstra_neo t ON t.id=s.tujuan_id AND t.is_deleted=0 JOIN misi_renstra_neo m ON m.id=t.misi_id AND m.is_deleted=0 JOIN renstra_neo r ON r.id=m.renstra_id AND r.is_deleted=0';
$spec=[
 ['sasaran_renstra','s.id','s.nama_sasaran','sasaran_renstra_neo s'.$base,'s.is_deleted=0'],
 ['program_renstra','p.id','p.uraian','program_renstra_neo p JOIN sasaran_renstra_neo s ON s.id=p.sasaran_id AND s.is_deleted=0'.$base,'p.is_deleted=0'],
 ['kegiatan_renstra','k.id','k.uraian','kegiatan_renstra_neo k JOIN program_renstra_neo p ON p.id=k.program_id AND p.is_deleted=0 JOIN sasaran_renstra_neo s ON s.id=p.sasaran_id AND s.is_deleted=0'.$base,'k.is_deleted=0'],
 ['sub_kegiatan_renstra','sk.id','rk.uraian','sub_kegiatan_renstra_neo sk JOIN rekening_kegiatan rk ON rk.id=sk.master_sub_kegiatan_id JOIN kegiatan_renstra_neo k ON k.id=sk.kegiatan_renstra_id AND k.is_deleted=0 JOIN program_renstra_neo p ON p.id=k.program_id AND p.is_deleted=0 JOIN sasaran_renstra_neo s ON s.id=p.sasaran_id AND s.is_deleted=0'.$base,'sk.is_deleted=0']
];$select=[];
foreach($spec as [$type,$id,$text,$from,$where])$select[]="SELECT CONVERT(CONCAT('$type:', $id) USING utf8mb4) COLLATE utf8mb4_unicode_ci value_key,$id sumber_id,_utf8mb4'$type' COLLATE utf8mb4_unicode_ci sumber_ref,CONVERT($text USING utf8mb4) COLLATE utf8mb4_unicode_ci uraian,CONVERT(r.kd_wilayah USING utf8mb4) COLLATE utf8mb4_unicode_ci kd_wilayah,CONVERT(r.kd_opd USING utf8mb4) COLLATE utf8mb4_unicode_ci kd_opd,0 is_deleted FROM $from WHERE $where";
$p->exec('CREATE OR REPLACE SQL SECURITY INVOKER VIEW sakip_sumber_kinerja_v AS '.implode(' UNION ALL ',$select));
echo "SAKIP performance source view ready.\n";
