<?php
session_start();

require_once '../app/Core/DB.php';
require_once '../app/Core/Auth.php';
require_once '../app/Core/Controller.php';
require_once '../app/Core/Router.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

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
        header("Location: /");
        exit;
    }
}

// ==============================
// 🔒 JALANKAN CONTROLLER
// ==============================
if ($route) {

    require_once "../app/Controllers/" . $route[0] . ".php";

    $controller = new $route[0];
    $method = $route[1];

    $controller->$method();

} else {

    http_response_code(404);
    require __DIR__ . '/../app/Views/errors/404.php';
    exit;

}