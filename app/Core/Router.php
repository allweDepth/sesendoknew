<?php
class Router{
    public static function route($uri){
        $routes=require __DIR__.'/../../routes/web.php';
        return $routes[$uri]??null;
    }
}
