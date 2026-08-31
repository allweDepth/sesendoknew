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
  '/kontrak' => ['KontrakController', 'index'],
  '/kontrak/summary' => ['KontrakController', 'summary'],
  '/kontrak/available-items' => ['KontrakController', 'availableItems'],
  '/kontrak/available-subactivities' => ['KontrakController', 'availableSubActivities'],
  '/kontrak/items' => ['KontrakController', 'items'],
  '/kontrak/items/save' => ['KontrakController', 'saveItems'],
  '/kontrak/delivery' => ['KontrakController', 'delivery'],
  '/kontrak/rab/save' => ['KontrakController', 'saveRab'],
  '/kontrak/schedule/save' => ['KontrakController', 'saveSchedule'],
  '/kontrak/rab_excel' => ['KontrakController', 'rabExcel'],
  '/kontrak/rab_pdf' => ['KontrakController', 'rabPdf'],
  '/kontrak/terms_pdf' => ['KontrakController', 'termsPdf'],
  '/kontrak/rab/import' => ['KontrakController', 'importRab'],
  '/kontrak/document/upload' => ['KontrakController', 'uploadDocument'],
  '/kontrak/document/download' => ['KontrakController', 'downloadDocument'],
  '/kontrak/pdf' => ['KontrakController', 'pdf'],
  '/kontrak/laporan_pdf' => ['KontrakController', 'reportPdf'],
  '/kontrak/laporan_excel' => ['KontrakController', 'reportExcel'],
  '/kontrak/financial_excel' => ['KontrakController', 'financialExcel'],
  '/kontrak/financial_pdf' => ['KontrakController', 'financialPdf'],
  '/halaman_berita' => ['HalamanBeritaController', 'index'], //Controller hanya return HTML shell BUKAN data tabel
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
  '/template_import' => ['DynamicController', 'template'],
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
  '/standar_harga/export_pdf' => ['StandarHargaController', 'exportPdf'],
  '/standar_harga/copy_year' => ['StandarHargaController', 'copyYear'],

  /*
    |--------------------------------------------------------------------------
    | ⚙️ PENGATURAN
    |--------------------------------------------------------------------------
    */
  '/pengaturan'        => ['PengaturanController', 'index'],
  '/pengaturan/fragment' => ['PengaturanController', 'fragment'],
  '/pengaturan/current' => ['PengaturanController', 'current'],
  '/user_opd' => ['UserOpdController', 'index'],
  '/user_opd/list' => ['UserOpdController', 'list'],
  '/user_opd/save' => ['UserOpdController', 'save'],
  '/user_opd/delete' => ['UserOpdController', 'delete'],
  /*
    |--------------------------------------------------------------------------
    | 💬 WALLCHAT
    |--------------------------------------------------------------------------
    */
  '/wallchat'          => ['WallchatController', 'index'],
  '/wallchat/feed'     => ['WallchatController', 'feed'],
  '/wallchat/store'    => ['WallchatController', 'store'],
  '/wallchat/comment'  => ['WallchatController', 'comment'],
  '/wallchat/private'  => ['WallchatController', 'privateMessage'],
  '/wallchat/private/read' => ['WallchatController', 'readPrivate'],
  '/wallchat/private/delete' => ['WallchatController', 'deletePrivate'],
  '/wallchat/private/file' => ['WallchatController', 'privateFile'],
  '/wallchat/delete'   => ['WallchatController', 'delete'],

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
  '/tata_naskah'                => ['TataNaskahController', 'dashboard'],
  '/tata_naskah/dokumen'        => ['TataNaskahController', 'dashboard'],
  '/tata_naskah/buat'           => ['TataNaskahController', 'buat'],
  '/tata_naskah/jenis'     => ['TataNaskahController', 'Jenis'],
  '/tata_naskah/form'      => ['TataNaskahController', 'Form'],
  '/tata_naskah/generate_pdf'   => ['TataNaskahController', 'generate_pdf'],
  '/tata_naskah/schema'         => ['TataNaskahController', 'schema'],
  '/tata_naskah/generateNomor'  => ['TataNaskahController', 'generateNomor'],
  '/tata_naskah/simpan'         => ['TataNaskahController', 'simpan'],
  '/tata_naskah/daftar'         => ['TataNaskahController', 'daftar'],
  '/tata_naskah/get_kelompok'   => ['TataNaskahController', 'get_kelompok'],
  '/tata_naskah/update_status'  => ['TataNaskahController', 'updateStatus'],
  '/tata_naskah/upload_signature' => ['TataNaskahController', 'uploadSignature'],
  '/kop_surat' => ['KopSuratController', 'index'],
  '/kop_surat/save' => ['KopSuratController', 'save'],

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
|--------------------------------------------------------------------------
| ANGGARAN MODULE (RENJA → DPPA)
|--------------------------------------------------------------------------
| Semua dokumen memakai engine yang sama
| Controller menentukan tabel yang digunakan
|
| rkpd           → rkpd_neo
| renja          → renja_neo
| renja_perubahan→ renja_p_neo
| rka            → rka_neo
| rka_perubahan  → rka_perubahan_neo
| dpa            → dpa_neo
| dppa           → dppa_neo
|--------------------------------------------------------------------------
*/

  '/rkpd'             => ['AnggaranController', 'rkpd'],
  '/renja'            => ['AnggaranController', 'renja'],
  '/rka'              => ['AnggaranController', 'rka'],
  '/dpa'              => ['AnggaranController', 'dpa'],
  '/rkpd_perubahan'   => ['AnggaranController', 'rkpdPerubahan'],
  '/renja_perubahan'  => ['AnggaranController', 'renjaPerubahan'],
  '/rka_perubahan'    => ['AnggaranController', 'rkaPerubahan'],
  '/dppa'             => ['AnggaranController', 'dppa'],


  /*
|--------------------------------------------------------------------------
| AJAX DATA
|--------------------------------------------------------------------------
*/

  '/anggaran/advance'      => ['AnggaranController', 'advance'],
  '/anggaran/export_pdf'   => ['AnggaranController', 'exportPdf'],
  '/anggaran/export_excel' => ['AnggaranController', 'exportExcel'],
  '/anggaran/groups'       => ['AnggaranController', 'groups'],
  '/anggaran/details'      => ['AnggaranController', 'details'],
];
