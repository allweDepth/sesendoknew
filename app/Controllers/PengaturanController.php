<?php
require_once __DIR__ . '/../Core/Auth.php';

class PengaturanController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $this->view('pengaturan/index', [], 'app');
    }
    public function fragment()
    {
        $this->view('pengaturan/form');
    }
    public function current()
    {
        header('Content-Type: application/json;charset=UTF-8');
        if(!Auth::check()){echo json_encode(['success'=>false,'message'=>'Unauthorized']);return;}
        require_once __DIR__.'/../Core/DB.php';require_once __DIR__.'/../Services/JsonResponse.php';
        $row=DB::getInstance()->query('SELECT * FROM pengaturan_neo WHERE kd_wilayah=? AND tahun=? AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$_SESSION['user']['kd_wilayah'],$_SESSION['user']['tahun']])->fetch();
        echo $row?JsonResponse::success('Jadwal aktif ditemukan',[],$row):JsonResponse::error('Jadwal tahun aktif belum dibuat',404);
    }

    public function paguLimits()
    {
        header('Content-Type: application/json;charset=UTF-8');
        if (!Auth::check()) { echo JsonResponse::error('Unauthorized', 401); return; }
        require_once __DIR__.'/../Core/DB.php';require_once __DIR__.'/../Services/JsonResponse.php';
        $user = $_SESSION['user'];
        $usageSql = implode(' UNION ALL ', array_map(
            static fn($item) => "SELECT kd_wilayah,kd_opd,tahun,'{$item[0]}' dokumen,COALESCE(SUM(jumlah),0) terpakai FROM `{$item[1]}` WHERE is_deleted=0 GROUP BY kd_wilayah,kd_opd,tahun",
            [['renja','renja_neo'],['rka','rka_neo'],['dpa','dpa_neo'],['renja_p','renja_p_neo'],['rka_p','rka_p_neo'],['dppa','dppa_neo']]
        ));
        $rows = DB::getInstance()->query(
            "SELECT b.id,b.kd_opd,COALESCE(o.uraian,b.kd_opd) nama_opd,b.dokumen,b.pagu_maksimal,COALESCE(u.terpakai,0) terpakai,GREATEST(b.pagu_maksimal-COALESCE(u.terpakai,0),0) sisa,b.keterangan FROM batas_pagu_opd_neo b LEFT JOIN organisasi_neo o ON o.kode=b.kd_opd AND o.kd_wilayah=b.kd_wilayah AND o.is_deleted=0 LEFT JOIN ($usageSql) u ON u.kd_wilayah=b.kd_wilayah AND u.kd_opd=b.kd_opd AND u.tahun=b.tahun AND u.dokumen=b.dokumen WHERE b.kd_wilayah=? AND b.tahun=? AND b.is_deleted=0 ORDER BY nama_opd,b.dokumen",
            [$user['kd_wilayah'], $user['tahun']]
        )->fetchAll();
        $opd = DB::getInstance()->query(
            "SELECT kode,uraian FROM organisasi_neo WHERE kd_wilayah=? AND is_deleted=0 AND kode IS NOT NULL AND kode<>'' AND kode<>'0' ORDER BY uraian",
            [$user['kd_wilayah']]
        )->fetchAll();
        echo JsonResponse::success('Batas pagu OPD', [], ['rows'=>$rows,'opd'=>$opd]);
    }

    public function savePaguLimit()
    {
        header('Content-Type: application/json;charset=UTF-8');
        if (!Auth::check()) { echo JsonResponse::error('Unauthorized', 401); return; }
        require_once __DIR__.'/../Core/DB.php';require_once __DIR__.'/../Services/JsonResponse.php';
        try {
            $user = $_SESSION['user'];
            if (($user['type_user'] ?? '') !== 'admin_wilayah') throw new RuntimeException('Hanya admin wilayah yang dapat menetapkan batas pagu OPD.');
            $opd = trim((string)($_POST['kd_opd'] ?? ''));
            $document = trim((string)($_POST['dokumen'] ?? ''));
            $maximum = filter_var($_POST['pagu_maksimal'] ?? null, FILTER_VALIDATE_FLOAT);
            $allowed = ['renja','rka','dpa','renja_p','rka_p','dppa'];
            if ($opd === '' || !in_array($document, $allowed, true) || $maximum === false || $maximum < 0) throw new InvalidArgumentException('OPD, dokumen, dan pagu maksimal wajib valid.');
            $db = DB::getInstance();
            if (!$db->query('SELECT id FROM organisasi_neo WHERE kode=? AND kd_wilayah=? AND is_deleted=0 LIMIT 1',[$opd,$user['kd_wilayah']])->fetch()) throw new RuntimeException('OPD tidak ditemukan pada wilayah pengguna.');
            $tables = ['renja'=>'renja_neo','rka'=>'rka_neo','dpa'=>'dpa_neo','renja_p'=>'renja_p_neo','rka_p'=>'rka_p_neo','dppa'=>'dppa_neo'];
            $used = (float)($db->query("SELECT COALESCE(SUM(jumlah),0) total FROM `{$tables[$document]}` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0",[$user['kd_wilayah'],$opd,$user['tahun']])->fetch()['total'] ?? 0);
            if ($maximum + 0.01 < $used) throw new RuntimeException('Pagu maksimal tidak boleh lebih kecil dari pagu yang sudah terinput Rp '.number_format($used,0,',','.').'.');
            $db->query(
                'INSERT INTO batas_pagu_opd_neo(kd_wilayah,kd_opd,tahun,dokumen,pagu_maksimal,keterangan,username_insert,is_deleted) VALUES(?,?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE pagu_maksimal=VALUES(pagu_maksimal),keterangan=VALUES(keterangan),tgl_update=NOW(),username_update=VALUES(username_insert),is_deleted=0',
                [$user['kd_wilayah'],$opd,$user['tahun'],$document,$maximum,trim((string)($_POST['keterangan']??'')),$user['username']??'system']
            );
            echo JsonResponse::success('Batas pagu OPD berhasil disimpan.');
        } catch (Throwable $e) { echo JsonResponse::error($e->getMessage(), 400); }
    }
}
