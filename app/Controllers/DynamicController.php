<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Services/DynamicTableService.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

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
    $req = $_GET['req'] ?? null;

    if (!$tableKey) {
      http_response_code(400);
      die("Tabel tidak ditemukan");
    }

    $service = new DynamicTableService();

    $exportRequest = [
      'action' => 'export',
      'tbl'    => $tableKey
    ];

    if ($req !== null && $req !== '') {
      $exportRequest['req'] = $req;
    }

    $response = $service->handle($exportRequest);

    $decoded = json_decode($response, true);

    if (!$decoded['success']) {
      http_response_code(400);
      die($decoded['message']);
    }

    $data = $decoded['data'] ?? [];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr(preg_replace('/[\\\\\/?*\[\]:]/', '-', $req ?: $tableKey), 0, 31));

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
      foreach ($item as $field => $value) {
        $col = Coordinate::stringFromColumnIndex($colIndex);
        $cell = $col . $row;
        $fieldName = strtolower((string)$field);

        if (preg_match('/(^kode$|^kd_|_kode$|^nip$|^npwp$|nomor)/', $fieldName)) {
          $sheet->setCellValueExplicit($cell, (string)$value, DataType::TYPE_STRING);
        } elseif ($value !== null && $value !== '' && is_numeric($value)) {
          $sheet->setCellValue($cell, (float)$value);
          if (preg_match('/harga|nilai|pagu|anggaran|total|jumlah/', $fieldName)) {
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
          }
        } elseif ($value && preg_match('/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}:\d{2})?$/', (string)$value)) {
          $sheet->setCellValue($cell, SpreadsheetDate::PHPToExcel(new DateTime((string)$value)));
          $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(
            str_contains((string)$value, ' ') ? 'dd/mm/yyyy hh:mm' : 'dd/mm/yyyy'
          );
        } else {
          $sheet->setCellValue($cell, $value);
        }
        $colIndex++;
      }
      $row++;
    }

    if (ob_get_length()) ob_end_clean();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $safeSuffix = $req ? '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $req) : '';
    $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '', $tableKey) . $safeSuffix . '.xlsx';
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $sheet->setAutoFilter("A1:{$lastColumn}1");
    $sheet->freezePane('A2');
    $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType('solid')->getStartColor()->setARGB('FF176B87');
    $sheet->getStyle("A1:{$lastColumn}".max(1,$row-1))->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setARGB('FFB7C9D3');
    $sheet->getStyle("A1:{$lastColumn}".max(1,$row-1))->getAlignment()->setVertical('top')->setWrapText(true);
    for($stripe=2;$stripe<$row;$stripe+=2)$sheet->getStyle("A{$stripe}:{$lastColumn}{$stripe}")->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF0F7FA');
    foreach (range(1, $totalColumns) as $columnIndex) {
      $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }

  public function template()
  {
    if (empty($_SESSION['user'])) { http_response_code(401); die('Unauthorized'); }
    $key=(string)($_GET['tabel']??''); $profiles=require __DIR__.'/../Config/table_profiles.php';
    if(!$key||empty($profiles[$key]['table'])){http_response_code(400);die('Template tabel tidak tersedia');}
    $table=$profiles[$key]['table']; $db=DB::getInstance();
    $skip=['id','kd_wilayah','kd_opd','tahun','tgl_insert','tgl_update','username_insert','username_update','is_deleted','disable','kunci','setujui'];
    $columns=array_values(array_filter(array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field'),fn($c)=>!in_array($c,$skip,true)));
    $book=new Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setTitle(substr('Template-'.$key,0,31));
    foreach($columns as $i=>$column){$cell=Coordinate::stringFromColumnIndex($i+1).'1';$sheet->setCellValue($cell,strtoupper($column));$sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i+1))->setWidth(max(14,min(32,strlen($column)+5)));}
    $last=Coordinate::stringFromColumnIndex(max(1,count($columns)));$sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle("A1:{$last}1")->getFill()->setFillType('solid')->getStartColor()->setARGB('FF176B87');$sheet->getStyle("A1:{$last}50")->getBorders()->getAllBorders()->setBorderStyle('hair')->getColor()->setARGB('FFD5E1E8');$sheet->freezePane('A2');$sheet->setAutoFilter("A1:{$last}1");
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="template-'.preg_replace('/[^a-z0-9_-]/i','',$key).'.xlsx"');(new Xlsx($book))->save('php://output');exit;
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
