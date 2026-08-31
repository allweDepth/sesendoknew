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
}
