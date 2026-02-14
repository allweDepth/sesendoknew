<?php
return [
    '/' => ['HomeController', 'index'],
    '/home' => ['HomeController', 'index'],
    '/berita' => ['HomeController', 'berita'],
    '/organisasi' => ['HomeController', 'organisasi'],

    '/login/proses' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    '/register/proses' => ['AuthController', 'register'],
    '/dashboard' => ['DashboardController', 'index'],

    '/renstra' => ['RenstraController', 'index'],
    '/renja' => ['RenjaController', 'index'],
    '/dpa' => ['DpaController', 'index'],
    '/renja_perubahan' => ['RenjaPerubahanController', 'index'],
    '/dppa' => ['DppaController', 'index'],
];
