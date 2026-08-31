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
            $logical = $_GET['tbl'] ?? ''; $table = AnggaranCopyService::table($logical);
            $user = $_SESSION['user'] ?? []; $wilayah = $user['kd_wilayah'] ?? ''; $tahun = (int)($user['tahun'] ?? 0); $opd = $user['kd_opd'] ?? '';
            $select = str_starts_with($logical, 'rkpd') ? 'kd_sub_keg, indikator AS uraian, target AS volume, pagu AS jumlah, setujui' : 'kd_sub_keg, uraian, volume, jumlah, setujui';
            $sql = "SELECT $select FROM `$table` WHERE kd_wilayah=? AND tahun=? AND is_deleted=0"; $params=[$wilayah,$tahun];
            if ($opd !== '' && $opd !== '0') { $sql .= ' AND kd_opd=?'; $params[]=$opd; }
            $rows = DB::getInstance()->query($sql.' ORDER BY kd_sub_keg, id', $params)->fetchAll();
            require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
            $pdf = new TCPDF('L','mm','A4',true,'UTF-8'); $pdf->SetMargins(10,12,10); $pdf->AddPage(); $pdf->SetFont('helvetica','B',13);
            $pdf->Cell(0,8,strtoupper(str_replace('_p',' Perubahan',$logical)).' TAHUN '.$tahun,0,1,'C'); $pdf->SetFont('helvetica','',8);
            $html='<table border="1" cellpadding="4"><thead><tr style="font-weight:bold;background-color:#eee"><th width="5%">No</th><th width="20%">Sub Kegiatan</th><th width="45%">Uraian</th><th width="10%">Volume</th><th width="15%">Jumlah</th><th width="5%">OK</th></tr></thead><tbody>';
            foreach ($rows as $i=>$row) $html.='<tr><td>'.($i+1).'</td><td>'.htmlspecialchars((string)$row['kd_sub_keg']).'</td><td>'.htmlspecialchars((string)$row['uraian']).'</td><td align="right">'.number_format((float)$row['volume'],2,',','.').'</td><td align="right">'.number_format((float)$row['jumlah'],2,',','.').'</td><td>'.($row['setujui']?'Ya':'Tidak').'</td></tr>';
            if (!$rows) $html.='<tr><td colspan="6" align="center">Tidak ada data</td></tr>';
            $pdf->writeHTML($html.'</tbody></table>',true,false,true,false,'');
            header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.$logical.'-'.$tahun.'.pdf"'); echo $pdf->Output('','S');
        } catch (Throwable $e) { http_response_code(400); echo $e->getMessage(); }
        exit;
    }
}
