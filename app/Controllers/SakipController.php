<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Services/SakipReportService.php';

class SakipController extends Controller
{
    public function __construct() { Auth::check(); }

    public function exportPdf(): void
    {
        try {
            $type=(string)($_GET['tbl']??'iku_opd');
            $pdf=(new SakipReportService($_SESSION['user']??[]))->reportPdf($type);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="laporan-sakip-'.preg_replace('/[^a-z0-9_-]/i','',$type).'.pdf"');
            echo $pdf;
        } catch (Throwable $e) { http_response_code(422); echo $e->getMessage(); }
        exit;
    }

    public function performanceTreePdf(): void
    {
        try {
            $pdf=(new SakipReportService($_SESSION['user']??[]))->performanceTreePdf();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="pohon-kinerja-hirarki.pdf"');
            echo $pdf;
        } catch (Throwable $e) { http_response_code(422); echo $e->getMessage(); }
        exit;
    }
}

