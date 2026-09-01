<?php
require_once __DIR__.'/../Core/Auth.php';
require_once __DIR__.'/../Services/RenstraExportService.php';
class RenstraController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $this->view('anggaran/renstra/index', [], 'app');
    }

    public function exportPdf():void{try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$body=(new RenstraExportService($_SESSION['user']??[]))->pdf();header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="renstra-resmi.pdf"');echo$body;}catch(Throwable $e){http_response_code(400);echo$e->getMessage();}exit;}
    public function exportExcel():void{try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$file=(new RenstraExportService($_SESSION['user']??[]))->excel();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="renstra-resmi.xlsx"');readfile($file);unlink($file);}catch(Throwable $e){http_response_code(400);echo$e->getMessage();}exit;}
}
