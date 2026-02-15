<?php
return [
    '/' => ['HomeController', 'index'],
    '/home' => ['HomeController', 'index'],
    '/datateknis' => ['HomeController', 'datateknis'],
    '/organisasi' => ['HomeController', 'organisasi'],
    '/pelayanan' => ['HomeController', 'pelayanan'],
    '/login/proses' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    '/register/proses' => ['AuthController', 'register'],
    '/dashboard' => ['DashboardController', 'index'],

    '/referensi' => ['ReferensiController', 'index'],
    '/referensi/load' => ['ReferensiController', 'load'],
    '/referensi/store' => ['ReferensiController', 'store'],
    '/referensi/update' => ['ReferensiController', 'update'],
    '/referensi/delete' => ['ReferensiController', 'delete'],

    '/renstra' => ['RenstraController', 'index'],
    '/renja' => ['RenjaController', 'index'],
    '/dpa' => ['DpaController', 'index'],
    '/renja_perubahan' => ['RenjaPerubahanController', 'index'],
    '/dppa' => ['DppaController', 'index'],
];
