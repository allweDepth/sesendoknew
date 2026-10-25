<?php
/** Bring the Page Setup schema in sync with its signature alignment control. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$c=require __DIR__.'/../../config/database.php';
$p=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(!$p->query("SHOW COLUMNS FROM page_setup_opd_neo LIKE 'alignment_tanda_tangan'")->fetch())$p->exec("ALTER TABLE page_setup_opd_neo ADD COLUMN alignment_tanda_tangan ENUM('L','C','R','J') NOT NULL DEFAULT 'C' AFTER posisi_tanda_tangan");
echo "Page Setup signature alignment schema ready.\n";
