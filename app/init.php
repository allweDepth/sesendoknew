<?php
// app/init.php

define('BASE_PATH', dirname(__DIR__));

// Load helpers jika ada
if (file_exists(BASE_PATH . '/app/helpers.php')) {
    require_once BASE_PATH . '/app/helpers.php';
}

// Autoload classes (Controllers, Models, dll.)
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $file = BASE_PATH . '/app/' . $class . '.php';
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