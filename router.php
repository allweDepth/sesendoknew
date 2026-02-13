<?php
// router.php (di sesendoknew/)
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Hilangkan prefix jika ada
$uri = preg_replace('#^/sesendoknew#', '', $uri);

if ($uri === '/' || $uri === '/home') {
    require __DIR__ . '/public/index.php';
} elseif (file_exists(__DIR__ . '/public' . $uri)) {
    return false; // serve file statis (css/js/gambar)
} else {
    require __DIR__ . '/public/index.php'; // semua route ke index.php
}