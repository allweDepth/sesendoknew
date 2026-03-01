<?php
class Router{
    public static function route($uri){
        $routes = require __DIR__.'/../../routes/web.php';

        // POTONG QUERY STRING
        $uri = parse_url($uri, PHP_URL_PATH);

        return $routes[$uri] ?? null;
    }
}