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
    public function monthlyAccountPage():void{if(!Auth::check()){header('Location: '.app_url('/'));exit;}$table=(string)($_GET['tbl']??'dpa');$sub=(string)($_GET['kd_sub_keg']??'');if(!in_array($table,['dpa','dppa'],true)||$sub===''){http_response_code(400);echo'Parameter DPA/DPPA dan sub kegiatan wajib diisi';return;}$this->view('anggaran/monthly_account',['table'=>$table,'subCode'=>$sub]);}

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
            $result = (new AnggaranCopyService(Auth::scopedUser()))->copy($_POST['from'] ?? '', $_POST['to'] ?? '', (int)($_POST['tahun'] ?? 0), !empty($_POST['source_id']) ? (int)$_POST['source_id'] : null);
            echo JsonResponse::success('Dokumen berhasil diproses', $result);
        } catch (Throwable $e) { echo JsonResponse::error($e->getMessage(), 400); }
    }

    public function groups(): void { $this->documentJson('groups'); }
    public function details(): void { $this->documentJson('details'); }
    public function approval():void
    {
        header('Content-Type: application/json;charset=UTF-8');
        if(!Auth::check()){echo JsonResponse::error('Unauthorized',401);return;}
        if($_SERVER['REQUEST_METHOD']!=='POST'){echo JsonResponse::error('Method tidak diizinkan',405);return;}
        if(empty($_SESSION['csrf_token'])||($_SERVER['HTTP_X_CSRF_TOKEN']??'')!==$_SESSION['csrf_token']){echo JsonResponse::error('CSRF validation gagal',403);return;}
        try{$data=(new AnggaranDocumentService(Auth::scopedUser()))->setApproval((string)($_POST['tbl']??''),(string)($_POST['kd_sub_keg']??''),!empty($_POST['approved']));echo JsonResponse::success(!empty($_POST['approved'])?'Dokumen disetujui dan dikunci':'Persetujuan dokumen dibuka kembali',[],$data);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}
    }
    private function documentJson(string $action): void
    {
        header('Content-Type: application/json;charset=UTF-8');
        if(!Auth::check()){echo JsonResponse::error('Unauthorized',401);return;}
        try{$service=new AnggaranDocumentService(Auth::scopedUser());$data=$action==='groups'?$service->groups($_GET['tbl']??''):$service->details($_GET['tbl']??'',$_GET['kd_sub_keg']??'');echo JsonResponse::success('Data dokumen berhasil dimuat',[],$data);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}
    }

    public function exportPdf(): void
    {
        try {
            if (!Auth::check()) throw new RuntimeException('Sesi login tidak valid');
            $logical=(string)($_GET['tbl']??'');$pdf=(new AnggaranDocumentService(Auth::scopedUser()))->exportRegulatoryPdf($logical);
            header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$logical.'-per-sub-kegiatan.pdf"');echo $pdf;
        } catch (Throwable $e) { http_response_code(400); echo $e->getMessage(); }
        exit;
    }
    public function exportExcel(): void
    {try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$logical=(string)($_GET['tbl']??'');$file=(new AnggaranDocumentService($_SESSION['user']??[]))->exportExcel($logical);header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$logical.'-per-sub-kegiatan.xlsx"');readfile($file);unlink($file);}catch(Throwable $e){http_response_code(400);echo $e->getMessage();}exit;}
    public function exportRecapExcel():void{try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$logical=(string)($_GET['tbl']??'');$file=(new AnggaranDocumentService($_SESSION['user']??[]))->exportRecapExcel($logical);header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$logical.'-rekapitulasi-belanja.xlsx"');readfile($file);unlink($file);}catch(Throwable $e){http_response_code(400);echo$e->getMessage();}exit;}
    public function exportRecapPdf():void{try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$logical=(string)($_GET['tbl']??'');$pdf=(new AnggaranDocumentService($_SESSION['user']??[]))->exportRecapPdf($logical);header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$logical.'-rekapitulasi-belanja.pdf"');echo$pdf;}catch(Throwable $e){http_response_code(400);echo$e->getMessage();}exit;}
    public function monthlyPlan():void{header('Content-Type: application/json;charset=UTF-8');try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$s=new AnggaranDocumentService($_SESSION['user']??[]);$data=$s->monthlyPlan((string)($_REQUEST['tbl']??''),(int)($_REQUEST['id']??0));if($_SERVER['REQUEST_METHOD']==='POST'){$raw=$_POST['months']??[];if(is_string($raw))$raw=json_decode($raw,true)?:[];$data=$s->saveMonthlyPlan((string)$_POST['tbl'],(int)$_POST['id'],$raw);}echo JsonResponse::success('Rencana realisasi bulanan tersimpan',[],$data);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}}
    public function monthlyAccounts():void{header('Content-Type: application/json;charset=UTF-8');try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$service=new AnggaranDocumentService($_SESSION['user']??[]);$table=(string)($_REQUEST['tbl']??'');$sub=(string)($_REQUEST['kd_sub_keg']??'');if($_SERVER['REQUEST_METHOD']==='POST'){$months=$_POST['months']??[];if(is_string($months))$months=json_decode($months,true)?:[];$rows=$service->saveAccountMonthly($table,$sub,(string)($_POST['kd_akun']??''),$months,(string)($_POST['jenis']??'belanja'));}else$rows=$service->accountMonthlyRows($table,$sub);echo JsonResponse::success('Rencana rekening bulanan berhasil dimuat',[],$rows);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}}
    public function tapdList():void{header('Content-Type: application/json;charset=UTF-8');try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$rows=(new AnggaranDocumentService($_SESSION['user']??[]))->activeTapd($_GET['tanggal']??null);echo JsonResponse::success('Daftar TAPD aktif',[],$rows);}catch(Throwable $e){echo JsonResponse::error($e->getMessage(),400);}}
    public function tapdSave():void
    {
        header('Content-Type: application/json;charset=UTF-8');try{if(!Auth::check())throw new RuntimeException('Sesi login tidak valid');$user=Auth::scopedUser();$role=$user['type_user']??'';if(!in_array($role,['super_admin','admin_wilayah'],true))throw new RuntimeException('Hanya super admin atau admin wilayah yang dapat mengatur penugasan TAPD');$start=(string)($_POST['tanggal_mulai']??'');$end=(string)($_POST['tanggal_selesai']??'');$employeeId=(int)($_POST['pegawai_id']??0);if(!$start||!$end||$start>$end||!$employeeId)throw new InvalidArgumentException('Pegawai dan masa berlaku penugasan wajib valid');$db=DB::getInstance();$employee=$db->query('SELECT id,CONCAT_WS(" ",gelar_depan,nama,gelar) nama,nip FROM db_asn_pemda_neo WHERE id=? AND kd_wilayah=? AND is_deleted=0 AND disable=0 AND COALESCE(aktif,1)=1 LIMIT 1',[$employeeId,$user['kd_wilayah']])->fetch();if(!$employee)throw new RuntimeException('Pegawai aktif tidak ditemukan pada wilayah pengguna');$db->insert('tapd_penugasan_neo',['kd_wilayah'=>$user['kd_wilayah'],'tahun'=>$user['tahun'],'user_id'=>null,'pegawai_id'=>$employeeId,'nama'=>$employee['nama'],'nip'=>$employee['nip'],'jabatan'=>trim((string)($_POST['jabatan']??'Anggota')),'urutan'=>(int)($_POST['urutan']??1),'tanggal_mulai'=>$start,'tanggal_selesai'=>$end,'aktif'=>1,'username_insert'=>$user['username']??'system','is_deleted'=>0]);echo JsonResponse::success('Penugasan TAPD tersimpan dari master pegawai wilayah');}catch(Throwable$e){echo JsonResponse::error($e->getMessage(),400);}
    }
}
