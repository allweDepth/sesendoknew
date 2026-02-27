<?php

/**
 * ==============================================================
 * DynamicController
 * --------------------------------------------------------------
 * Controller untuk:
 * - Render dynamic table (AJAX)
 * - Export Excel profesional (Logo + Kop + Styling)
 * - Import Excel ke database
 * ==============================================================
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Services/DynamicTableService.php';
require_once __DIR__ . '/../Core/DB.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DynamicController
{
    /**
     * ==========================================================
     * INDEX
     * ----------------------------------------------------------
     * Menangani request AJAX dynamic table
     * ==========================================================
     */
    public function index($params = null)
    {
        $action = $_POST['action'] ?? null;

        // 🔥 ROUTING KHUSUS IMPORT
        if ($action === 'import') {
            return $this->import();
        }

        // 🔥 DEFAULT CRUD
        $service = new DynamicTableService($_POST);
        echo $service->handle($_POST);
    }

    /**
     * ==========================================================
     * EXPORT EXCEL PROFESIONAL
     * ----------------------------------------------------------
     * Fitur:
     * - Logo Pemda
     * - Kop Surat
     * - Header warna OPD
     * - Freeze header
     * - Format Rupiah otomatis
     * - Auto column width (max 30)
     * - Wrap text jika melebihi batas
     * - Border
     * - Auto filter
     * - Grouping
     * ==========================================================
     */
    public function export()
    {
        $table = $_GET['tabel'] ?? null;

        if (!$table) {
            http_response_code(400);
            die("Tabel tidak ditemukan");
        }

        // Ambil data via service
        $service = new DynamicTableService();

        $request = [
            'tbl'   => $table,
            'jenis' => 'export'
        ];

        $response = $service->handle($request);
        $responseData = json_decode($response, true);

        $data = $responseData['data'] ?? [];

        if (empty($data)) {
            // die("Data kosong di tabel: " . $table);
            $data = []; // tetap lanjut
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if (!empty($data)) {
            $headers = array_keys($data[0]);
        } else {
            $headers = ['Keterangan'];
        }
        $totalColumns = count($headers);
        $lastColumn = Coordinate::stringFromColumnIndex($totalColumns);

        /**
         * ======================================================
         * 1. LOGO PEMDA
         * ------------------------------------------------------
         * Menampilkan logo di kiri atas (A1)
         * ======================================================
         */
        $logoPath = __DIR__ . '/../../public/assets/img/umum/logo.png';

        if (file_exists($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo Pemda');
            $drawing->setPath($logoPath);
            $drawing->setHeight(70);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        }

        /**
         * ======================================================
         * 2. KOP SURAT
         * ======================================================
         */
        $sheet->mergeCells("B1:{$lastColumn}1");
        $sheet->setCellValue("B1", "PEMERINTAH KABUPATEN PASANGKAYU");

        $sheet->mergeCells("B2:{$lastColumn}2");
        $sheet->setCellValue("B2", "DINAS PEKERJAAN UMUM DAN PENATAAN RUANG");

        $sheet->mergeCells("B3:{$lastColumn}3");
        $sheet->setCellValue("B3", strtoupper("DATA " . $table));

        $sheet->getStyle("B1:B3")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("B1:B3")->getAlignment()->setHorizontal('center');

        /**
         * ======================================================
         * 3. HEADER TABLE
         * ======================================================
         */
        $headerRow = 5;

        foreach ($headers as $index => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($columnLetter . $headerRow, strtoupper($header));
        }

        // Warna header OPD
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF1F4E78');

        // Font putih + bold
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
            ->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');

        // Alignment tengah + wrap
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        // Tinggi header
        $sheet->getRowDimension($headerRow)->setRowHeight(40);

        // Freeze agar header tetap saat scroll
        $sheet->freezePane('A6');

        /**
         * ======================================================
         * 4. DATA + FORMAT RUPIAH OTOMATIS
         * ======================================================
         */
        $row = 6;

        $row = 6;

        if (!empty($data)) {

            foreach ($data as $item) {

                $colIndex = 1;

                foreach ($item as $value) {

                    $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $cell = $columnLetter . $row;

                    $sheet->setCellValue($cell, $value);

                    if (is_numeric($value) && $value > 1000) {
                        $sheet->getStyle($cell)
                            ->getNumberFormat()
                            ->setFormatCode('"Rp" #,##0');
                    }

                    $colIndex++;
                }

                $row++;
            }
        } else {
            $sheet->setCellValue('A6', 'Tidak ada data');
            $row++;
        }

        $lastDataRow = $row - 1;

        /**
         * ======================================================
         * 5. AUTO COLUMN WIDTH (DINAMIS MAX 30)
         * ------------------------------------------------------
         * - Jika isi < 30 karakter → width mengikuti isi
         * - Jika > 30 → dibatasi 30 + wrap text aktif
         * ======================================================
         */
        $maxLimit = 30;

        for ($colIndex = 1; $colIndex <= $totalColumns; $colIndex++) {

            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $maxLength = 0;

            // Cek header
            $headerValue = $sheet->getCell($columnLetter . $headerRow)->getValue();
            $maxLength = strlen($headerValue);

            // Cek isi kolom
            for ($r = $headerRow + 1; $r <= $lastDataRow; $r++) {
                $cellValue = $sheet->getCell($columnLetter . $r)->getValue();
                $length = strlen((string)$cellValue);
                if ($length > $maxLength) {
                    $maxLength = $length;
                }
            }

            $calculatedWidth = $maxLength + 2;

            if ($calculatedWidth > $maxLimit) {
                $calculatedWidth = $maxLimit;

                $sheet->getStyle($columnLetter)
                    ->getAlignment()
                    ->setWrapText(true);
            }

            $sheet->getColumnDimension($columnLetter)->setWidth($calculatedWidth);
        }

        /**
         * ======================================================
         * 6. BORDER TABLE
         * ======================================================
         */
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastDataRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        /**
         * ======================================================
         * 7. AUTO FILTER
         * ======================================================
         */
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastDataRow}");

        /**
         * ======================================================
         * 8. GROUPING (Contoh: tiap 10 baris)
         * ======================================================
         */
        for ($r = 6; $r <= $lastDataRow; $r += 10) {
            $sheet->getRowDimension($r)
                ->setOutlineLevel(1)
                ->setVisible(true)
                ->setCollapsed(false);
        }

        $sheet->setShowSummaryBelow(true);

        /**
         * ======================================================
         * OUTPUT FILE
         * ======================================================
         */
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $table . '_OPD.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * ==========================================================
     * IMPORT EXCEL KE DATABASE
     * ==========================================================
     */
    public function import()
    {
        
        try {

            $profiles = require __DIR__ . '/../Config/table_profiles.php';
            $action   = $_POST['action'] ?? null;
$jns = $action; //
            if ($jns === 'import_struktur') {

                $service = new DynamicTableService();

                echo $service->importStruktur(
                    $_FILES['file']['tmp_name']
                );

                exit;
            }
            $tableKey = $_POST['tbl'] ?? null;
            $jmlHeader = (int)($_POST['jml_header'] ?? 1);

            if (!$tableKey || !isset($profiles[$tableKey])) {
                http_response_code(403);
                echo json_encode(['error' => 'Tabel tidak diizinkan']);
                exit;
            }

            if (empty($_FILES['file']['tmp_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'File tidak ditemukan']);
                exit;
            }

            $service = new DynamicTableService();

            echo $service->importStrict(
                $tableKey,
                $_FILES['file']['tmp_name'],
                $jmlHeader
            );
        } catch (Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }
}
