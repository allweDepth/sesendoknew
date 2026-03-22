<?php

class KontrakController
{
  public function index()
  {
    // // jika bukan AJAX → load layout utama
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {

      ob_start();
      // // tidak perlu view khusus
      echo ""; // // kosong, SPA akan isi
      $content = ob_get_clean();

      require __DIR__ . '/../Views/layouts/app.php';
    } else {

      // // SPA request → tidak perlu apa-apa
      echo "";
    }
  }
}