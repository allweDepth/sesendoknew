<?php

class ApiController
{
  public function handle()
  {
    ini_set('display_errors', 0); // matikan error display agar response tetap JSON

    header('Content-Type: application/json'); // set header response JSON

    // ==============================
    // START SESSION JIKA BELUM ADA
    // ==============================
    if (session_status() === PHP_SESSION_NONE) {
      session_start(); // mulai session
    }

    // ==============================
    // AMBIL PARAMETER REQUEST
    // ==============================
    $tbl = $_REQUEST['tbl'] ?? null; // nama resource / tabel
    $action = $_REQUEST['action'] ?? null; // aksi yang diminta

    // ==============================
    // VALIDASI PARAMETER
    // ==============================
    if (!$tbl || !$action) { // jika tbl atau action kosong
      http_response_code(400); // set HTTP error

      echo json_encode([
        'success' => false,
        'message' => 'tbl atau action tidak valid' // pesan error
      ]);

      return; // hentikan eksekusi
    }

    // ==============================
    // RESOURCE YANG BOLEH TANPA LOGIN
    // ==============================
    $publicModules = ['public']; // daftar resource publik

    // ==============================
    // CEK SESSION LOGIN
    // ==============================
    if (!in_array($tbl, $publicModules)) { // jika bukan resource public
      if (!isset($_SESSION['user'])) { // jika session user tidak ada

        http_response_code(401); // unauthorized

        echo json_encode([
          'success' => false,
          'expired' => true,
          'message' => 'Session habis. Silakan login ulang.'
        ]);

        return; // hentikan eksekusi
      }
    }

    // ==============================
    // RESOLVE MODEL BERDASARKAN TBL
    // ==============================
    // ==============================
    // LOAD DYNAMIC TABLE SERVICE
    // ==============================
    require_once __DIR__ . '/../Services/DynamicTableService.php'; // load service utama

    // ==============================
    // BUAT INSTANCE SERVICE
    // ==============================
    $service = new DynamicTableService(); // buat instance service

    try {

      // ==============================
      // EKSEKUSI SERVICE
      // ==============================
      $result = $service->handle($_REQUEST); // jalankan handler service

      // ==============================
      // RESPONSE BERHASIL
      // ==============================
      echo json_encode([
        'success' => true,
        'data' => $result
      ]);
    } catch (Exception $e) {

      // ==============================
      // RESPONSE ERROR SERVER
      // ==============================
      http_response_code(500);

      echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
      ]);
    }
  }
}
