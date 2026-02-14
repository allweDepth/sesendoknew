<?php
return [
    '/' => ['DashboardController','index'],
    '/login'=>['AuthController','loginForm'],
    '/login/proses'=>['AuthController','login'],
    '/logout'=>['AuthController','logout'],

    '/renstra'=>['RenstraController','index'],
    '/renja'=>['RenjaController','index'],
    '/dpa'=>['DpaController','index'],
    '/renja_perubahan'=>['RenjaPerubahanController','index'],
    '/dppa'=>['DppaController','index'],
];
