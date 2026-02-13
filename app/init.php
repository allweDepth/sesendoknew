<?php
// app/init.php

define('BASE_PATH', dirname(__DIR__));

// Load helpers jika ada
if (file_exists(BASE_PATH . '/app/helpers.php')) {
    require_once BASE_PATH . '/app/helpers.php';
}
// Load .env ke konstanta (sederhana)
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        // Jika belum didefinisikan, define
        if (!defined($k)) define($k, $v);
    }
}

// Autoload classes (Controllers, Models, dll.)
spl_autoload_register(function ($class) {
    // ubah backslashes ke slash
    $class = str_replace('\\', '/', $class);

    // Jika $class sudah berisi 'app/...' maka kita jangan tambah '/app/' dua kali
    // Path final: BASE_PATH . '/' . $class . '.php'
    $file = BASE_PATH . '/' . $class . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Start session
session_start();

// View helper (mendukung @extends & @section sederhana via ob)
function view($path, $data = []) {
    extract($data);
    ob_start();
    require BASE_PATH . "/resources/views/{$path}.php";
    $content = ob_get_clean();

    // Load layout utama
    require BASE_PATH . '/resources/views/layouts/app.php';
}

// Load routes
require BASE_PATH . '/routes/web.php';