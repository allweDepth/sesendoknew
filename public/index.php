<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
header_remove('X-Powered-By');

set_exception_handler(static function (Throwable $error): void {
  if (!$error instanceof DatabaseError) error_log((string)$error);
  // Discard any partial page before returning a safe failure response.
  while (ob_get_level() > 0) ob_end_clean();
  $status = $error instanceof DatabaseError ? $error->httpStatus : 500;
  $message = $error instanceof DatabaseError
    ? $error->getMessage()
    : 'Terjadi gangguan pada aplikasi. Silakan coba lagi atau hubungi administrator.';
  http_response_code($status);
  if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
      || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
  } else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Gangguan layanan</title><p>'
      . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></html>';
  }
});

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
  return preg_replace_callback('#(href|src|action)=([\'\"])(/(?!/)[^\'\"]*)#i', static function (array $match): string {
    if ($match[3] === APP_BASE_PATH || str_starts_with($match[3], APP_BASE_PATH . '/')) return $match[0];
    return $match[1] . '=' . $match[2] . APP_BASE_PATH . $match[3];
  }, $output);
});
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ini_set('session.cookie_secure', '1');
session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Baseline browser hardening. TLS is terminated by the web server/proxy; HSTS
// is emitted only when the current request is known to be HTTPS.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=(self)");
$mapsSearchSettings = require __DIR__ . '/../config/maps.php';
$mapsSearchParts = parse_url($mapsSearchSettings['search_endpoint']);
$mapsSearchOrigin = '';
if (is_array($mapsSearchParts) && ($mapsSearchParts['scheme'] ?? '') === 'https' && preg_match('/^[a-z0-9.-]+$/i', $mapsSearchParts['host'] ?? '')) {
  $mapsSearchOrigin = ' https://' . $mapsSearchParts['host'] . (isset($mapsSearchParts['port']) ? ':' . (int)$mapsSearchParts['port'] : '');
}
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https://*.tile.openstreetmap.org https://*.tile.opentopomap.org https://*.basemaps.cartocdn.com https://server.arcgisonline.com https://*.google.com https://*.googleapis.com https://*.gstatic.com https://*.googleusercontent.com; media-src 'self' blob:; font-src 'self' data: https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://maps.googleapis.com https://maps.gstatic.com; connect-src 'self'{$mapsSearchOrigin} https://maps.googleapis.com https://maps.gstatic.com https://*.googleapis.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-store, private');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (APP_BASE_PATH !== '' && ($requestPath === APP_BASE_PATH || str_starts_with($requestPath, APP_BASE_PATH . '/'))) {
  $requestPath = substr($requestPath, strlen(APP_BASE_PATH)) ?: '/';
}
$maxRequestBytes = $requestPath === '/maps/upload' ? 100 * 1024 * 1024 : ($requestPath === '/maps/geometry' ? 40 * 1024 * 1024 : 4 * 1024 * 1024);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > $maxRequestBytes) {
  http_response_code(413);
  exit($requestPath === '/maps/upload' ? 'Ukuran paket ZIP shapefile maksimal 96 MB.' : 'Request terlalu besar');
}
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

require_once __DIR__ . '/../app/Core/DB.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/RequestGuard.php';

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
if (RequestGuard::requiresPost($route) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  header('Content-Type: application/json; charset=utf-8');
  exit(json_encode(['success' => false, 'message' => 'Metode permintaan tidak diizinkan.']));
}

// ==============================
// 🔒 PROTEKSI LOGIN GLOBAL
// ==============================

// Route yang BOLEH tanpa login
$publicRoutes = [
  '/',                 // halaman login
  '/login',            // kalau ada
  '/login/proses',
  '/login/session',
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

// One CSRF gate for every authenticated state-changing endpoint. Controllers
// may retain their local checks as defence in depth.
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'OPTIONS'], true)) {
  $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
  if (!is_string($sent)) $sent = '';
  if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $sent)) {
    $expectsJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
      || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($uri === '/login/proses' && !$expectsJson) {
      // Reject the stale submission; never authenticate or replay its password.
      $_SESSION['login_error'] = 'Sesi form login sudah berubah. Silakan masukkan kembali akun Anda.';
      header('Location: ' . app_url('/'), true, 303);
      exit;
    }
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sesi keamanan tidak valid. Muat ulang halaman lalu coba lagi.']);
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
