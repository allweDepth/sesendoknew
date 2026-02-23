<?php
return [
  '/' => ['HomeController', 'home'],
  '/home' => ['HomeController', 'home'],
  '/berita' => ['HomeController', 'berita'],
  '/datateknis' => ['HomeController', 'datateknis'],
  '/organisasi' => ['HomeController', 'organisasi'],
  '/pelayanan' => ['HomeController', 'pelayanan'],
  '/login/proses' => ['AuthController', 'login'],
  '/logout' => ['AuthController', 'logout'],
  '/register/proses' => ['AuthController', 'register'],
  '/dashboard' => ['DashboardController', 'index'],
  '/berita' => ['HomeController', 'news'],

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
  // ==============================
  // STANDAR HARGA
  // ==============================
  '/standar_harga' => ['StandarHargaController', 'index'],
  '/standar_harga/load' => ['StandarHargaController', 'load'],
  '/standar_harga/store' => ['StandarHargaController', 'store'],
  '/standar_harga/update' => ['StandarHargaController', 'update'],
  '/standar_harga/delete' => ['StandarHargaController', 'delete'],
  // ==============================
  // P E N G A T U R A N
  // ==============================
  '/pengaturan' => ['PengaturanController', 'index'],
  '/pengaturan/load' => ['PengaturanController', 'load'],
  '/pengaturan/store' => ['PengaturanController', 'store'],
  '/pengaturan/update' => ['PengaturanController', 'update'],
  '/pengaturan/delete' => ['PengaturanController', 'delete'],
  // ==============================
  // W A L L C H A T
  // ==============================
  '/wallchat' => ['WallchatController', 'index'],
  '/wallchat/load' => ['WallchatController', 'load'],
  '/wallchat/store' => ['WallchatController', 'store'],
  '/wallchat/comment' => ['WallchatController', 'comment'],
  // ==============================
  // P R O F I L
  // ==============================
  '/profil' => ['ProfilController', 'index'],
  '/profil/load' => ['ProfilController', 'load'],
  '/profil/update' => ['ProfilController', 'update'],
  // EXPORT IMPORT EXCEL
  '/export' => ['DynamicController', 'export'],
  '/import' => ['DynamicController', 'import'],
  // ==============================
  // R E N S T R A
  // ==============================

  '/renstra' => ['RenstraController', 'index'],
  '/renstra/load' => ['RenstraController', 'load'],
  '/renstra/tree' => ['RenstraController', 'tree'],

  '/renstra/store_misi' => ['RenstraController', 'storeMisi'],
  '/renstra/store_tujuan' => ['RenstraController', 'storeTujuan'],
  '/renstra/store_sasaran' => ['RenstraController', 'storeSasaran'],
  '/renstra/store_indikator' => ['RenstraController', 'storeIndikator'],
  '/renstra/store_program' => ['RenstraController', 'storeProgram'],
  '/renstra/store_anggaran' => ['RenstraController', 'storeAnggaran'],

  '/renstra/update' => ['RenstraController', 'update'],
  '/renstra/delete' => ['RenstraController', 'delete'],

  '/renstra/import' => ['RenstraController', 'importExcel'],
  '/renstra/export_word' => ['RenstraController', 'exportWord'],
  //untuk login register
  '/wilayah/load' => ['AuthController', 'getWilayah'],
  '/organisasi/load' => ['AuthController', 'getOrganisasi'],
// ==============================
// T A T A  N A S K A H
// ==============================
'/tata_naskah/dashboard' => ['TataNaskahController', 'dashboard'],
'/tata_naskah/buat' => ['TataNaskahController', 'buat'],
'/tata_naskah/load_jenis' => ['TataNaskahController', 'loadJenis'],
'/tata_naskah/load_form' => ['TataNaskahController', 'loadForm'],
'/tata_naskah/generate_pdf' => ['TataNaskahController', 'generate_pdf'],
'/tata_naskah/schema' => ['TataNaskahController', 'schema'],
// ==============================
// R E S E T  T A B E L
// ==============================
'/reset_tabel' => ['ResetTabelController', 'index'],
'/reset_tabel/load' => ['ResetTabelController', 'load'],
'/reset_tabel/store' => ['ResetTabelController', 'store'],
'/reset_tabel/update' => ['ResetTabelController', 'update'],
'/reset_tabel/delete' => ['ResetTabelController', 'delete'],
'/reset_tabel/reset' => ['ResetTabelController', 'reset'],
];
