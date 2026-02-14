<?php
session_start();

require_once '../app/Core/DB.php';
require_once '../app/Core/Auth.php';
require_once '../app/Core/Controller.php';
require_once '../app/Core/Router.php';

$uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$route=Router::route($uri);

if($route){
    require_once "../app/Controllers/".$route[0].".php";
    $controller=new $route[0];
    $method=$route[1];
    $controller->$method();
}else{
    echo "404 Not Found";
}
