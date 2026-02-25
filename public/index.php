<?php
session_start();

require_once '../app/Core/DB.php';
require_once '../app/Core/Auth.php';
require_once '../app/Core/Controller.php';
require_once '../app/Core/Router.php';

$basePath = '';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = str_replace('/index.php', '', $uri);

if ($uri === '') {
    $uri = '/';
}

$uri = str_replace($basePath, '', $uri);

if ($uri == '') $uri = '/';

// ===============================
// HANDLE SPA ?page=...
// ===============================
if (isset($_GET['page']) && $_GET['page'] !== '') {
    $uri = '/' . $_GET['page'];
}

$route = Router::route($uri);

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