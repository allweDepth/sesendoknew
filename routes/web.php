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

    '/dynamic' => ['DynamicController', 'index'],

    '/renstra' => ['RenstraController', 'index'],
    '/renja' => ['RenjaController', 'index'],
    '/dpa' => ['DpaController', 'index'],
    '/renja_perubahan' => ['RenjaPerubahanController', 'index'],
    '/dppa' => ['DppaController', 'index'],
    // ==============================
    // K E P E G A W A I A N
    // ==============================
    '/kepegawaian' => ['KepegawaianController', 'index'],
    '/kepegawaian/load' => ['KepegawaianController', 'load'],
    '/kepegawaian/store' => ['KepegawaianController', 'store'],
    '/kepegawaian/update' => ['KepegawaianController', 'update'],
    '/kepegawaian/delete' => ['KepegawaianController', 'delete'],
];
