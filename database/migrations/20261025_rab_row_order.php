<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$c=require __DIR__.'/../../config/database.php';$p=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(!$p->query("SHOW COLUMNS FROM rab_paket_neo LIKE 'urutan'")->fetch())$p->exec('ALTER TABLE rab_paket_neo ADD COLUMN urutan INT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX idx_rab_contract_order (kontrak_id,is_deleted,urutan,id)');
echo "RAB source-row ordering ready.\n";
