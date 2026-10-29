<?php
if(PHP_SAPI!=='cli')exit;require __DIR__.'/../app/Core/DB.php';
DB::getInstance()->query("CREATE TABLE IF NOT EXISTS kontrak_rab_total_neo(kontrak_id BIGINT NOT NULL,tahap ENUM('hps','penawaran','negoisasi') NOT NULL,nilai DECIMAL(20,2) NOT NULL,username_update VARCHAR(100),tgl_update DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(kontrak_id,tahap)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");echo "RAB totals schema ready\n";
