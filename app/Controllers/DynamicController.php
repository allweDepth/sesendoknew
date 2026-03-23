<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Services/DynamicTableService.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DynamicController
{
  /* ==========================================================
       🔒 GLOBAL GUARD (UNTUK INDEX & IMPORT)
    ========================================================== */
  private function guard(): void
  {
    // Method wajib POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      header('Content-Type: application/json; charset=UTF-8');
      echo JsonResponse::error("Method tidak diizinkan");
      exit;
    }

    // Harus login
    if (empty($_SESSION['user'])) {
      http_response_code(401);
      header('Content-Type: application/json; charset=UTF-8'); // 🔥
      echo JsonResponse::error("Unauthorized");
      exit;
    }

    // CSRF
    $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (
      empty($_SESSION['csrf_token']) ||
      $csrfHeader !== $_SESSION['csrf_token']
    ) {
      http_response_code(403);
      header('Content-Type: application/json; charset=UTF-8');
      echo JsonResponse::error("CSRF validation gagal");
      exit;
    }
  }

  /* ==========================================================
       INDEX → SEMUA CRUD SPA
    ========================================================== */
  public function index($params = null)
  {
    $this->guard();

    $action = $_POST['action'] ?? null;

    if ($action === 'import') {
      $this->import();
      return;
    }

    $service = new DynamicTableService();
    // 🔥 FIX: pastikan response JSON
    header('Content-Type: application/json; charset=UTF-8'); //
    echo $service->handle($_POST);
    exit;
  }

  /* ==========================================================
       EXPORT (GET DENGAN SESSION CHECK)
    ========================================================== */
  public function export()
  {
    // 🔒 Session wajib
    if (empty($_SESSION['user'])) {
      http_response_code(401);
      die("Unauthorized");
    }

    $tableKey = $_GET['tabel'] ?? null;

    if (!$tableKey) {
      http_response_code(400);
      die("Tabel tidak ditemukan");
    }

    $service = new DynamicTableService();

    $response = $service->handle([
      'action' => 'export',
      'tbl'    => $tableKey
    ]);

    $decoded = json_decode($response, true);

    if (!$decoded['success']) {
      http_response_code(400);
      die($decoded['message']);
    }

    $data = $decoded['data'] ?? [];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if (!empty($data)) {
      $headers = array_keys($data[0]);
    } else {
      $headers = ['Keterangan'];
    }

    $totalColumns = count($headers);
    $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

    // HEADER
    foreach ($headers as $i => $header) {
      $col = Coordinate::stringFromColumnIndex($i + 1);
      $sheet->setCellValue($col . '1', strtoupper($header));
    }

    // DATA
    $row = 2;
    foreach ($data as $item) {
      $colIndex = 1;
      foreach ($item as $value) {
        $col = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue($col . $row, $value);
        $colIndex++;
      }
      $row++;
    }

    if (ob_get_length()) ob_end_clean();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $tableKey . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }

  /* ==========================================================
       IMPORT (STRICT + PROTECTED)
    ========================================================== */
  private function import()
  {
    $profiles = require __DIR__ . '/../Config/table_profiles.php';

    $tableKey = $_POST['tbl'] ?? null;
    $jmlHeader = (int)($_POST['jml_header'] ?? 1);

    if (!$tableKey || !isset($profiles[$tableKey])) {
      header('Content-Type: application/json; charset=UTF-8');
      echo JsonResponse::error("Tabel tidak diizinkan");
      exit;
    }

    if (empty($_FILES['file']['tmp_name'])) {
      header('Content-Type: application/json; charset=UTF-8');
      echo JsonResponse::error("File tidak ditemukan");
      exit;
    }

    $service = new DynamicTableService();
    header('Content-Type: application/json; charset=UTF-8');
    echo $service->importStrict(
      $tableKey,
      $_FILES['file']['tmp_name'],
      $jmlHeader
    );

    exit;
  }
}
