<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Services/DynamicTableService.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DynamicController
{
	public function index($params = null)
	{
		$service = new DynamicTableService($_POST);
		echo $service->handle($_POST);
	}
	public function export()
	{
		// Support GET dan POST
		$table = $_GET['tabel'] ?? null;

		if (!$table) {
			http_response_code(400);
			die("Tabel tidak ditemukan");
		}

		$data = DynamicTableService::getAll($table);

		if (empty($data)) {
			die("Data kosong di tabel: " . $table);
		}

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		// HEADER
		$headers = array_keys($data[0]);
		$col = 'A';

		$headers = array_keys($data[0]);

		foreach ($headers as $index => $header) {
			$columnLetter = Coordinate::stringFromColumnIndex($index + 1);
			$sheet->setCellValue($columnLetter . '1', $header);
		}

		// DATA
		$row = 2;

		$row = 2;

		foreach ($data as $item) {

			$colIndex = 1;

			foreach ($item as $value) {

				$columnLetter = Coordinate::stringFromColumnIndex($colIndex);
				$sheet->setCellValue($columnLetter . $row, $value);

				$colIndex++;
			}

			$row++;
		}

		// CLEAN BUFFER
		if (ob_get_length()) ob_end_clean();

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $table . '.xlsx"');
		header('Cache-Control: max-age=0');

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$writer->save('php://output');
		exit;
	}
	public function import()
	{
		$table = $_POST['tabel'] ?? null;

		if (!$table || !isset($_FILES['file'])) {
			http_response_code(400);
			exit;
		}

		$allowed = ['asn', 'users', 'referensi', 'wallchat'];
		if (!in_array($table, $allowed)) {
			http_response_code(403);
			exit;
		}

		$file = $_FILES['file']['tmp_name'];

		$spreadsheet = IOFactory::load($file);
		$sheet = $spreadsheet->getActiveSheet();
		$rows = $sheet->toArray();

		$pdo = DB::getInstance()->getConnection();
		$pdo->beginTransaction();

		try {

			$headers = $rows[0];

			$stmt = $pdo->query("DESCRIBE `$table`");
			$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

			foreach ($headers as $h) {
				if (!in_array($h, $columns)) {
					throw new Exception("Kolom $h tidak ada");
				}
			}

			for ($i = 1; $i < count($rows); $i++) {

				$row = $rows[$i];

				$colString = implode(',', $headers);
				$placeholders = implode(',', array_fill(0, count($headers), '?'));

				$stmt = $pdo->prepare("
                INSERT INTO `$table` ($colString)
                VALUES ($placeholders)
            ");

				$stmt->execute($row);
			}

			$pdo->commit();
			echo json_encode(['success' => true]);
		} catch (Exception $e) {

			$pdo->rollBack();
			http_response_code(500);
			echo json_encode(['error' => $e->getMessage()]);
		}

		exit;
	}
}
