<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/../../app/Core/DB.php';

DB::getInstance()->query(
    "CREATE TABLE IF NOT EXISTS anggaran_metadata_neo (
        kd_wilayah VARCHAR(60) NOT NULL,
        kd_opd VARCHAR(60) NOT NULL,
        tahun YEAR NOT NULL,
        dokumen VARCHAR(12) NOT NULL,
        kode VARCHAR(50) NOT NULL,
        data_json LONGTEXT NOT NULL CHECK (JSON_VALID(data_json)),
        username_update VARCHAR(100) NOT NULL,
        tgl_update DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (kd_wilayah, kd_opd, tahun, dokumen, kode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "Tabel metadata anggaran tersedia.\n";
