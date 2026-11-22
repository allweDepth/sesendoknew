<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$c=require __DIR__.'/../../config/database.php';
$pdo=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach(['kualifikasi_json','pekerjaan_berjalan_json','personel_json','peralatan_json','pengalaman_json'] as $column){
    if(!$pdo->query("SHOW COLUMNS FROM rekanan_neo LIKE '".$column."'")->fetch())$pdo->exec('ALTER TABLE rekanan_neo ADD COLUMN '.$column.' JSON NULL');
}
echo "Supplier qualification JSON columns ready; existing data unchanged.\n";
