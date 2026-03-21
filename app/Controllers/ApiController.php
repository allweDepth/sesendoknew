<?php

class ApiController
{
  public function handle()
  {
    ini_set('display_errors', 0); // // matikan error agar response tetap JSON

    header('Content-Type: application/json'); // // set header JSON

    // ==============================
    // START SESSION
    // ==============================
    if (session_status() === PHP_SESSION_NONE) {
      session_start(); // // mulai session
    }

    // ==============================
    // AMBIL PARAMETER
    // ==============================
    $tbl = $_REQUEST['tbl'] ?? null; // // ambil tbl
    $action = $_REQUEST['action'] ?? null; // // ambil action

    // ==============================
    // VALIDASI PARAMETER
    // ==============================
    if (!$tbl || !$action) {
      http_response_code(400); // // bad request

      echo json_encode([
        'success' => false,
        'message' => 'tbl atau action tidak valid'
      ]);

      return;
    }

    // ==============================
    // PUBLIC MODULE
    // ==============================
    $publicModules = [
      'public',          // // existing
      'tbl_wilayah',     // // wilayah tanpa login
      'organisasi_neo'   // // organisasi tanpa login
    ];

    // ==============================
    // CEK LOGIN
    // ==============================
    if (!in_array($tbl, $publicModules)) {
      if (!isset($_SESSION['user'])) {

        http_response_code(401);

        echo json_encode([
          'success' => false,
          'expired' => true,
          'message' => 'Session habis. Silakan login ulang.'
        ]);

        return;
      }
    }

    // ==============================
    // LOAD DB CLASS
    // ==============================
    require_once __DIR__ . '/../Core/DB.php';

    $db = DB::getInstance(); // // instance DB

    // ==============================
    // HANDLE WILAYAH
    // ==============================
    if ($tbl === 'tbl_wilayah' && $action === 'get') {

      $data = $db->select(
        'tbl_wilayah',
        'kd_wilayah AS kode, nama_wilayah AS uraian',
        ''
      );

      echo json_encode([
        'success' => true,
        'data' => $data
      ]);

      return;
    }

    // ==============================
    // HANDLE ORGANISASI
    // ==============================
    if ($tbl === 'organisasi_neo' && $action === 'get') {

      $kd_wilayah = $_REQUEST['kd_wilayah'] ?? null; // // ambil parameter

      if (!$kd_wilayah) {
        echo json_encode([
          'success' => false,
          'message' => 'kd_wilayah wajib diisi'
        ]);
        return;
      }

      $data = $db->select(
        'organisasi_neo', // // tabel
        'kd_organisasi AS kode, nama_organisasi AS uraian', // // kolom
        'WHERE kd_wilayah = ?', // // kondisi
        [$kd_wilayah] // // parameter
      );

      echo json_encode([
        'success' => true,
        'data' => $data
      ]);

      return;
    }

    // ==============================
    // FALLBACK KE SERVICE
    // ==============================
    require_once __DIR__ . '/../Services/DynamicTableService.php';

    $service = new DynamicTableService();

    try {

      $result = $service->handle($_REQUEST); // // jalankan service

      // ==============================
      // FIX DOUBLE JSON
      // ==============================
      if (is_string($result)) {
        $decoded = json_decode($result, true);

        if (json_last_error() === JSON_ERROR_NONE) {
          echo json_encode($decoded);
          return;
        }
      }

      echo json_encode([
        'success' => true,
        'data' => $result
      ]);
    } catch (Exception $e) {

      http_response_code(500);

      echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
      ]);
    }
  }
}
