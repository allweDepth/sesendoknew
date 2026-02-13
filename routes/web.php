<?php
// Router sederhana
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);
$controllerName = ucfirst($parts[0] ?? 'Home') . 'Controller';
$method = $parts[1] ?? 'index';

$namespace = "app\\Http\\Controllers\\";
if (class_exists($namespace . $controllerName)) {
    $controller = new ($namespace . $controllerName)();
    if (method_exists($controller, $method)) {
        $controller->$method();
    } else {
        echo "404 - Method not found";
    }
} else {
    echo "404 - Controller not found";
}