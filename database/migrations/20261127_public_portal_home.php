<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../../app/Core/DB.php';

$db = DB::getInstance();
$column = $db->query("SHOW COLUMNS FROM halaman_berita LIKE 'jenis_halaman'")->fetch();
if (!str_contains($column['Type'], "'home'")) {
    $db->query("ALTER TABLE halaman_berita MODIFY jenis_halaman ENUM('berita','data_teknis','organisasi','pelayanan','home') NOT NULL DEFAULT 'berita'");
}

echo "Tag Beranda tersedia.\n";
