<?php

/*
|--------------------------------------------------------------------------
| KONTRAK CONTROLLER
|--------------------------------------------------------------------------
| Digunakan untuk halaman kontrak
|--------------------------------------------------------------------------
*/

class KontrakController
{
  /*
    |--------------------------------------------------------------------------
    | DEFAULT
    |--------------------------------------------------------------------------
    */

  public function index()
  {
    $table = 'kontrak'; // // tabel utama kontrak

    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {

      ob_start();
      require __DIR__ . '/../Views/kontrak/index.php'; // // load view kontrak
      $content = ob_get_clean();

      require __DIR__ . '/../Views/layouts/app.php'; // // layout utama
    } else {

      require __DIR__ . '/../Views/kontrak/index.php'; // // SPA load
    }
  }
}