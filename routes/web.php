<?php
return [

    /*
    |--------------------------------------------------------------------------
    | 🌐 PUBLIC AREA (SERVER RENDERED)
    |--------------------------------------------------------------------------
    | Halaman publik tanpa login
    */
    '/'              => ['HomeController', 'home'],
    '/home'          => ['HomeController', 'home'],
    '/berita'        => ['HomeController', 'news'],
    '/datateknis'    => ['HomeController', 'datateknis'],
    '/organisasi'    => ['HomeController', 'organisasi'],
    '/pelayanan'     => ['HomeController', 'pelayanan'],

    /*
    |--------------------------------------------------------------------------
    | 🔐 AUTH
    |--------------------------------------------------------------------------
    */
    '/login/proses'      => ['AuthController', 'login'],
    '/logout'            => ['AuthController', 'logout'],
    '/register/proses'   => ['AuthController', 'register'],

    /*
    |--------------------------------------------------------------------------
    | 🏠 DASHBOARD (Internal SPA Entry)
    |--------------------------------------------------------------------------
    */
    '/dashboard' => ['DashboardController', 'index'],
    '/spa'       => ['HomeController', 'spa'],

    /*
    |--------------------------------------------------------------------------
    | ⚙️ CORE SPA ENGINE (DYNAMIC DISPATCHER)
    |--------------------------------------------------------------------------
    | Semua CRUD internal SPA via AJAX POST ke /dynamic
    */
    '/dynamic' => ['DynamicController', 'index'],

    /*
    |--------------------------------------------------------------------------
    | 📤 EXPORT / IMPORT GLOBAL (Excel)
    |--------------------------------------------------------------------------
    */
    '/export' => ['DynamicController', 'export'],
    '/import' => ['DynamicController', 'import'],

    /*
    |--------------------------------------------------------------------------
    | 📚 REFERENSI (Legacy Controller Mode)
    |--------------------------------------------------------------------------
    */
    '/referensi'          => ['ReferensiController', 'index'],
    '/referensi/store'    => ['ReferensiController', 'store'],
    '/referensi/update'   => ['ReferensiController', 'update'],
    '/referensi/delete'   => ['ReferensiController', 'delete'],

    /*
    |--------------------------------------------------------------------------
    | 🗂 RENSTRA
    |--------------------------------------------------------------------------
    */
    '/renstra'                => ['RenstraController', 'index'],
    '/renstra/load'           => ['RenstraController', 'load'],
    '/renstra/tree'           => ['RenstraController', 'tree'],
    '/renstra/store_misi'     => ['RenstraController', 'storeMisi'],
    '/renstra/store_tujuan'   => ['RenstraController', 'storeTujuan'],
    '/renstra/store_sasaran'  => ['RenstraController', 'storeSasaran'],
    '/renstra/store_indikator' => ['RenstraController', 'storeIndikator'],
    '/renstra/store_program'  => ['RenstraController', 'storeProgram'],
    '/renstra/store_anggaran' => ['RenstraController', 'storeAnggaran'],
    '/renstra/update'         => ['RenstraController', 'update'],
    '/renstra/delete'         => ['RenstraController', 'delete'],
    '/renstra/import'         => ['RenstraController', 'importExcel'],
    '/renstra/export_word'    => ['RenstraController', 'exportWord'],

    /*
    |--------------------------------------------------------------------------
    | 📅 RENJA & DPA
    |--------------------------------------------------------------------------
    */
    '/renja'           => ['RenjaController', 'index'],
    '/dpa'             => ['DpaController', 'index'],
    '/renja_perubahan' => ['RenjaPerubahanController', 'index'],
    '/dppa'            => ['DppaController', 'index'],

    /*
    |--------------------------------------------------------------------------
    | 👥 KEPEGAWAIAN
    |--------------------------------------------------------------------------
    */
    '/kepegawaian'        => ['KepegawaianController', 'index'],
    /*
    |--------------------------------------------------------------------------
    | 💰 STANDAR HARGA
    |--------------------------------------------------------------------------
    */
    '/standar_harga'        => ['StandarHargaController', 'index'],

    /*
    |--------------------------------------------------------------------------
    | ⚙️ PENGATURAN
    |--------------------------------------------------------------------------
    */
    '/pengaturan'        => ['PengaturanController', 'index'],
    '/pengaturan/fragment' => ['PengaturanController', 'fragment'],
    /*
    |--------------------------------------------------------------------------
    | 💬 WALLCHAT
    |--------------------------------------------------------------------------
    */
    '/wallchat'          => ['WallchatController', 'index'],

    /*
    |--------------------------------------------------------------------------
    | 👤 PROFIL
    |--------------------------------------------------------------------------
    */
    '/profil'        => ['ProfilController', 'index'],

    /*
    |--------------------------------------------------------------------------
    | 📝 TATA NASKAH
    |--------------------------------------------------------------------------
    */
    '/tata_naskah/dashboard'      => ['TataNaskahController', 'dashboard'],
    '/tata_naskah/buat'           => ['TataNaskahController', 'buat'],
    '/tata_naskah/load_jenis'     => ['TataNaskahController', 'loadJenis'],
    '/tata_naskah/load_form'      => ['TataNaskahController', 'loadForm'],
    '/tata_naskah/generate_pdf'   => ['TataNaskahController', 'generate_pdf'],
    '/tata_naskah/schema'         => ['TataNaskahController', 'schema'],
    '/tata_naskah/generateNomor'  => ['TataNaskahController', 'generateNomor'],
    '/tata_naskah/simpan'         => ['TataNaskahController', 'simpan'],
    '/tata_naskah/daftar'         => ['TataNaskahController', 'daftar'],
    '/tata_naskah/get_kelompok'   => ['TataNaskahController', 'get_kelompok'],

    /*
    |--------------------------------------------------------------------------
    | 🗑 RESET TABEL
    |--------------------------------------------------------------------------
    */
    '/reset_tabel'          => ['ResetTabelController', 'index'],
    '/reset_tabel/reset'    => ['ResetTabelController', 'reset'],
    '/reset_tabel/backup'   => ['ResetTabelController', 'backup'],
    '/reset_tabel/restore'  => ['ResetTabelController', 'restore'],

    /*
    |--------------------------------------------------------------------------
    | 🔗 MAPPING AKUN
    |--------------------------------------------------------------------------
    */
    '/mapping' => ['MappingController', 'index'],

    /*
    |--------------------------------------------------------------------------
    | 🔌 API PUBLIC (Register / Login Dropdown)
    |--------------------------------------------------------------------------
    */
    '/api' => ['ApiController', 'handle'],
    /*
/*
|--------------------------------------------------------------------------
| ANGGARAN MODULE (RENJA → DPPA)
|--------------------------------------------------------------------------
| Semua dokumen memakai engine yang sama
| Controller menentukan tabel yang digunakan
|
| renja          → renja_neo
| renja_perubahan→ renja_perubahan_neo
| rka            → rka_neo
| rka_perubahan  → rka_perubahan_neo
| dpa            → dpa_neo
| dppa           → dppa_neo
|--------------------------------------------------------------------------
*/

    '/renja'            => ['AnggaranController', 'renja'],
    '/renja_perubahan'  => ['AnggaranController', 'renjaPerubahan'],
    '/rka'              => ['AnggaranController', 'rka'],
    '/rka_perubahan'    => ['AnggaranController', 'rkaPerubahan'],
    '/dpa'              => ['AnggaranController', 'dpa'],
    '/dppa'             => ['AnggaranController', 'dppa'],


    /*
|--------------------------------------------------------------------------
| AJAX DATA
|--------------------------------------------------------------------------
*/

    '/anggaran/sub_kegiatan' => ['AnggaranController', 'subKegiatan'],

    '/anggaran/rekap_akun'   => ['AnggaranController', 'rekapAkun'],

    '/anggaran/rincian'      => ['AnggaranController', 'rincian'],
];
