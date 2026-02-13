<?php
//sementara agar error muncul
//Setelah perbaikan selesai, hapus atau set display_errors ke 0 pada production.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/init.php';