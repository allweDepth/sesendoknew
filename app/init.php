<?php
// app/init.php

define('BASE_PATH', dirname(__DIR__));

// Load helpers (env function)
require_once __DIR__ . '/helpers.php';

// Autoload classes
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $file = BASE_PATH . '/app/' . $class . '.php';
    if (file_exists($file)) require $file;
});

// Start session
session_start();

// View helper
function view(string $path, array $data = []): void
{
    extract($data);
    ob_start();
    require BASE_PATH . "/resources/views/{$path}.php";
    $content = ob_get_clean();
    require BASE_PATH . '/resources/views/layouts/app.php';
}

// Routes
require BASE_PATH . '/routes/web.php';