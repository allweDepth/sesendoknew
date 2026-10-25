<?php
// Run once with the database setup account; no contract or financial data is modified.
$config=require __DIR__.'/../config/database.php';
$pdo=new PDO('mysql:unix_socket='.$config['socket'].';dbname='.$config['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE IF NOT EXISTS kontrak_print_settings_neo (kontrak_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,settings_json LONGTEXT NOT NULL,updated_by VARCHAR(100) NOT NULL,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
echo "Contract print settings storage ready\n";
