<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$decoded = rawurldecode($path);
if (preg_match('#(?:^|/)[.]|\.(?:sql|log|ini|bak|env|phar|phtml)(?:/|$)#i', $decoded)
    || (preg_match('/\.php(?:\/|$)/i', $decoded) && $decoded !== '/index.php')
    || preg_match('#^/uploads/.*\.(?:php[0-9]?|phtml|phar|html?|svg|cgi|pl|sh)(?:\.|/|$)#i', $decoded)) {
    http_response_code(403);
    exit('Akses ditolak.');
}
$file = realpath(__DIR__ . $decoded);

if ($path !== '/' && $file && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
