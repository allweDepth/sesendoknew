<?php
class Router
{
    public static function route($uri)
    {
        if (
            str_contains($uri, '/load') ||
            str_contains($uri, '/store') ||
            str_contains($uri, '/update') ||
            str_contains($uri, '/delete')
        ) {
            http_response_code(403);
            die("Legacy endpoint dinonaktifkan (Strict Mode)");
        }
        $routes = require __DIR__ . '/../../routes/web.php';

        // POTONG QUERY STRING
        $uri = parse_url($uri, PHP_URL_PATH);

        return $routes[$uri] ?? null;
    }
}
