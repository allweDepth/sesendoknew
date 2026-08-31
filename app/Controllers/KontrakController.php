<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Services/KontrakRealisasiService.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

class KontrakController extends Controller
{
    public function index() { if(!Auth::check()){header('Location: '.app_url('/'));exit;} $this->view('kontrak/index'); }
    public function summary() { $this->json(fn()=>(new KontrakRealisasiService($_SESSION['user']??[]))->summary()); }
    public function pdf() { $id=(int)($_GET['id']??0); try{$body=(new KontrakRealisasiService($_SESSION['user']??[]))->contractPdf($id);header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="kontrak-'.$id.'.pdf"');echo $body;}catch(Throwable $e){http_response_code(400);echo $e->getMessage();}exit; }
    public function reportPdf() { try{$body=(new KontrakRealisasiService($_SESSION['user']??[]))->reportPdf();header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="laporan-kontrak-realisasi.pdf"');echo $body;}catch(Throwable $e){http_response_code(400);echo $e->getMessage();}exit; }
    public function reportExcel() { try{$file=(new KontrakRealisasiService($_SESSION['user']??[]))->reportExcel();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="laporan-kontrak-realisasi.xlsx"');readfile($file);unlink($file);}catch(Throwable $e){http_response_code(400);echo $e->getMessage();}exit; }
    private function json(callable $callback): void { header('Content-Type: application/json;charset=UTF-8'); if(!Auth::check()){echo JsonResponse::error('Unauthorized',401);return;} try{echo JsonResponse::success('Data laporan berhasil dimuat',[], $callback());}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);} }
}
