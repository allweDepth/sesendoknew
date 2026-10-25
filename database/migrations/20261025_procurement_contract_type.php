<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$c=require __DIR__.'/../../config/database.php';$name=getenv('SESENDOK_DB_NAME')?:$c['dbname'];$p=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$name.';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(!$p->query("SHOW COLUMNS FROM kontrak_neo LIKE 'jenis_kontrak'")->fetch())$p->exec('ALTER TABLE kontrak_neo ADD COLUMN jenis_kontrak VARCHAR(40) NULL AFTER bentuk_kontrak');
// Backfill the selection; no contract amount, provider or schedule is changed.
$p->exec("UPDATE kontrak_neo SET jenis_kontrak=CASE WHEN cara_pengadaan='SWAKELOLA' THEN 'PERJANJIAN_SWAKELOLA' WHEN jenis_pengadaan='PEKERJAAN_KONSTRUKSI' THEN 'HARGA_SATUAN' ELSE 'LUMSUM' END WHERE jenis_kontrak IS NULL OR jenis_kontrak=''");
echo "Procurement contract types ready.\n";
