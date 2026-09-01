<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/AnggaranCopyService.php';
require_once __DIR__ . '/../Services/JsonResponse.php';
require_once __DIR__ . '/../Services/AnggaranDocumentService.php';

class AnggaranController extends Controller
{
    public function index() { $this->renja(); }
    public function rkpd() { $this->document('rkpd', 'RKPD'); }
    public function renja() { $this->document('renja', 'Renja'); }
    public function rka() { $this->document('rka', 'RKA'); }
    public function dpa() { $this->document('dpa', 'DPA'); }
    public function rkpdPerubahan() { $this->document('rkpd_p', 'RKPD Perubahan'); }
    public function renjaPerubahan() { $this->document('renja_p', 'Renja Perubahan'); }
    public function rkaPerubahan() { $this->document('rka_p', 'RKA Perubahan'); }
    public function dppa() { $this->document('dppa', 'DPPA'); }

    private function document(string $table, string $title): void
    {
        if (!Auth::check()) { header('Location: ' . app_url('/')); exit; }
        $this->view('anggaran/document', ['table'=>$table, 'title'=>$title]);
    }

    public function advance(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo JsonResponse::error('Method tidak diizinkan', 405); return; }
        if (empty($_SESSION['csrf_token']) || ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '') !== $_SESSION['csrf_token']) { echo JsonResponse::error('CSRF validation gagal', 403); return; }
        try {
            $result = (new AnggaranCopyService($_SESSION['user'] ?? []))->copy($_POST['from'] ?? '', $_POST['to'] ?? '', (int)($_POST['tahun'] ?? 0), !empty($_POST['source_id']) ? (int)$_POST['source_id'] : null);
            echo JsonResponse::success('Dokumen berhasil diproses', $result);
        } catch (Throwable $e) { echo JsonResponse::error($e->getMessage(), 400); }
    }

    public function groups(): void { $this->documentJson('groups'); }
    public function details(): void { $this->documentJson('details'); }
    private function documentJson(string $action): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        if(!Auth::check()){echo JsonResponse::error('Unauthorized',401);return;}
        try{$service=new AnggaranDocumentService($_SESSION['user']??[]);$data=$action==='groups'?$service->groups($_GET['tbl']??''):$service->details($_GET['tbl']??'',$_GET['kd_sub_keg']??'');echo JsonResponse::success('Data dokumen berhasil dimuat',[],$data);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}
    }

    public function exportPdf(): void
    {
        try {
            if (!Auth::check()) throw new RuntimeException('Sesi login tidak valid');
            $logical=(string)($_GET['tbl']??'');$pdf=(new AnggaranDocumentService($_SESSION['user']??[]))->exportPdf($logical);
            header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$logical.'-per-sub-kegiatan.pdf"');echo $pdf;
        } catch (Throwable $e) { http_response_code(400); echo $e->getMessage(); }
        exit;
    }
    public function exportExcel(): void
    {try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$logical=(string)($_GET['tbl']??'');$file=(new AnggaranDocumentService($_SESSION['user']??[]))->exportExcel($logical);header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$logical.'-per-sub-kegiatan.xlsx"');readfile($file);unlink($file);}catch(Throwable $e){http_response_code(400);echo $e->getMessage();}exit;}
}
