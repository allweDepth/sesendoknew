<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = preg_replace('#/public/index\.php$#', '', $scriptName);
if ($basePath === $scriptName) {
  $basePath = rtrim(dirname($scriptName), '/.');
}
$basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
define('APP_BASE_PATH', $basePath);

function app_url(string $path = '/'): string
{
  $path = '/' . ltrim($path, '/');
  return APP_BASE_PATH . ($path === '/' ? '/' : $path);
}

ob_start(function ($output) {
  if (APP_BASE_PATH === '') return $output;
  return preg_replace('#(href|src|action)=([\'\"])/(?!/)#i', '$1=$2' . APP_BASE_PATH . '/', $output);
});
session_start();

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Router.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (APP_BASE_PATH !== '' && ($uri === APP_BASE_PATH || str_starts_with($uri, APP_BASE_PATH . '/'))) {
  $uri = substr($uri, strlen(APP_BASE_PATH)) ?: '/';
}

// Hapus index.php jika ada
$uri = str_replace('/index.php', '', $uri);

// Jika kosong → '/'
if ($uri === '' || $uri === false) {
  $uri = '/';
}

// ==============================
// 🔒 ROUTE RESOLVE
// ==============================
$route = Router::route($uri);

// ==============================
// 🔒 PROTEKSI LOGIN GLOBAL
// ==============================

// Route yang BOLEH tanpa login
$publicRoutes = [
  '/',                 // halaman login
  '/login',            // kalau ada
  '/login/proses',
  '/logout',
  '/register',
  '/register/proses',
  '/api',
  '/berita',
  '/datateknis',
  '/organisasi',
  '/pelayanan'
];

// Jika bukan route public → wajib login
if (!in_array($uri, $publicRoutes)) {

  if (!Auth::check()) {

    // Jika request AJAX → kirim JSON 401
    if (
      isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
      http_response_code(401);
      echo json_encode([
        "success" => false,
        "expired" => true,
        "message" => "Session habis. Silakan login ulang."
      ]);
      exit;
    }

    // Jika normal request → redirect login
    header('Location: ' . app_url('/'));
    exit;
  }
}

// ==============================
// 🔒 JALANKAN CONTROLLER
// ==============================
if ($route) {

  require_once __DIR__ . "/../app/Controllers/" . $route[0] . ".php";

  $controller = new $route[0];
  $method = $route[1];

  $controller->$method();
} else {

  http_response_code(404);
  require __DIR__ . '/../app/Views/errors/404.php';
  exit;
}
ob_end_flush(); // 🔥 kirim output setelah semua aman
