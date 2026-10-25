<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$c=require __DIR__.'/../../config/database.php';$name=getenv('SESENDOK_DB_NAME')?:$c['dbname'];
$p=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$name.';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$has=false;foreach($p->query('SHOW INDEX FROM akun_neo')->fetchAll(PDO::FETCH_ASSOC) as $idx)if($idx['Column_name']==='kode'&&(int)$idx['Seq_in_index']===1)$has=true;
if(!$has)$p->exec('ALTER TABLE akun_neo ADD INDEX idx_akun_kode_lookup (kode)');
echo "Account code lookup index ready.\n";
