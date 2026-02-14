<?php
return [
    '/' => ['HomeController','index'],
    '/home' => ['HomeController','index'],

    '/login/proses'=>['AuthController','login'],
    '/logout'=>['AuthController','logout'],

    '/dashboard'=>['DashboardController','index'],

    '/renstra'=>['RenstraController','index'],
    '/renja'=>['RenjaController','index'],
    '/dpa'=>['DpaController','index'],
    '/renja_perubahan'=>['RenjaPerubahanController','index'],
    '/dppa'=>['DppaController','index'],
];