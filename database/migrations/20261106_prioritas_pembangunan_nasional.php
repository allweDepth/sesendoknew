<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/../../app/Core/DB.php';
DB::getInstance()->query(
    "CREATE TABLE IF NOT EXISTS prioritas_pembangunan_nasional_neo (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kd_wilayah VARCHAR(60) NOT NULL,
        tahun YEAR NOT NULL,
        kode VARCHAR(30) NOT NULL,
        uraian VARCHAR(500) NOT NULL,
        aktif TINYINT NOT NULL DEFAULT 1,
        username_update VARCHAR(100) NOT NULL,
        tgl_update DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_prioritas_nasional_scope (kd_wilayah,tahun,kode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Tabel prioritas pembangunan nasional tersedia.\n";
