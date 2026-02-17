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
	//=========EXPORT EXCEL
	public function export()
	{
		$table = $_GET['tabel'] ?? null;

		if (!$table) {
			http_response_code(400);
			die("Tabel tidak ditemukan");
		}

		$data = DynamicTableService::getAll($table);

		if (empty($data)) {
			die("Data kosong di tabel: " . $table);
		}

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		$headers = array_keys($data[0]);
		$totalColumns = count($headers);
		$lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

		/* =====================================================
       1. LOGO PEMDA
    ===================================================== */

		$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
		$drawing->setName('Logo');
		$drawing->setDescription('Logo Pemda');
		$drawing->setPath(__DIR__ . '/../../public/assets/img/umum/logo.png');
		$drawing->setHeight(70);
		$drawing->setCoordinates('A1');
		$drawing->setWorksheet($sheet);

		/* =====================================================
       2. KOP SURAT
    ===================================================== */

		$sheet->mergeCells("B1:{$lastColumn}1");
		$sheet->setCellValue("B1", "PEMERINTAH KABUPATEN PASANGKAYU");

		$sheet->mergeCells("B2:{$lastColumn}2");
		$sheet->setCellValue("B2", "DINAS PEKERJAAN UMUM DAN PENATAAN RUANG");

		$sheet->mergeCells("B3:{$lastColumn}3");
		$sheet->setCellValue("B3", strtoupper("DATA " . $table));

		$sheet->getStyle("B1:B3")->getFont()->setBold(true)->setSize(12);
		$sheet->getStyle("B1:B3")->getAlignment()->setHorizontal('center');

		/* =====================================================
       3. HEADER TABLE
    ===================================================== */

		$headerRow = 5;

		foreach ($headers as $index => $header) {
			$columnLetter = Coordinate::stringFromColumnIndex($index + 1);
			$sheet->setCellValue($columnLetter . $headerRow, strtoupper($header));
		}

		// Warna khas OPD (Biru)
		$sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB('FF1F4E78');

		$sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
			->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');

		$sheet->freezePane('A6');

		/* =====================================================
       4. DATA + FORMAT RUPIAH
    ===================================================== */

		$row = 6;

		foreach ($data as $item) {

			$colIndex = 1;

			foreach ($item as $value) {

				$columnLetter = Coordinate::stringFromColumnIndex($colIndex);
				$cell = $columnLetter . $row;

				$sheet->setCellValue($cell, $value);

				// AUTO FORMAT RUPIAH jika numeric besar
				if (is_numeric($value) && $value > 1000) {
					$sheet->getStyle($cell)
						->getNumberFormat()
						->setFormatCode('"Rp" #,##0');
				}

				$colIndex++;
			}

			$row++;
		}

		$lastDataRow = $row - 1;

		/* =====================================================
       5. AUTO WIDTH
    ===================================================== */

		for ($i = 1; $i <= $totalColumns; $i++) {
			$columnLetter = Coordinate::stringFromColumnIndex($i);
			$sheet->getColumnDimension($columnLetter)->setAutoSize(true);
		}

		/* =====================================================
       6. BORDER
    ===================================================== */

		$sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastDataRow}")
			->getBorders()->getAllBorders()
			->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

		/* =====================================================
       7. AUTO FILTER
    ===================================================== */

		$sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastDataRow}");

		/* =====================================================
       8. GROUPING COLLAPSE (contoh grouping per 10 baris)
    ===================================================== */

		for ($r = 6; $r <= $lastDataRow; $r += 10) {
			$sheet->getRowDimension($r)
				->setOutlineLevel(1)
				->setVisible(true)
				->setCollapsed(false);
		}

		$sheet->setShowSummaryBelow(true);

		/* =====================================================
       OUTPUT
    ===================================================== */

		if (ob_get_length()) ob_end_clean();

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $table . '_OPD.xlsx"');
		header('Cache-Control: max-age=0');

		$writer = new Xlsx($spreadsheet);
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
