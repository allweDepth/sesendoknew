<?php
class Router
{
    public static function route($uri)
    {
        $routes = require __DIR__ . '/../../routes/web.php';

        // POTONG QUERY STRING
        $uri = parse_url($uri, PHP_URL_PATH);

        // The route map is the allow-list. Blocking URLs by words such as
        // "store" or "delete" also blocked legitimate authenticated routes.
        return $routes[$uri] ?? null;
    }
}
