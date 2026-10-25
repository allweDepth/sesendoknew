<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$c=require __DIR__.'/../../config/database.php';
$pdo=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(!$pdo->query("SHOW COLUMNS FROM kontrak_neo LIKE 'proses_pengadaan'")->fetch())$pdo->exec('ALTER TABLE kontrak_neo ADD COLUMN proses_pengadaan JSON NULL');
echo "Contract process storage ready; existing rows unchanged.\n";
