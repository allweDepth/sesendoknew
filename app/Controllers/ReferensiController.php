<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class ReferensiController extends Controller
{
    private $allowedTables = [
        'urusan',
        'bidang',
        'program',
        'kegiatan',
        'sub_kegiatan',
        'rekanan',
        'satuan',
        'mapping',
        'aset',
        'akun',
        'sumber_dana',
        'organisasi',
        'peraturan',
        'wilayah',
        'rpjmd_kabupaten',
        'usulan_pembangunan',
        'evaluasi_renja'
    ];


    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $tbl = $_GET['tbl'] ?? 'program';

        // 🔥 Jika tidak diizinkan, fallback ke default
        if (!in_array($tbl, $this->allowedTables)) {
            $tbl = 'program'; // fallback aman
        }

        $this->view('referensi/index', [
            'tbl'  => $tbl
        ], 'app');
    }



    public function store()
    {
        $this->legacyMutationDisabled();
    }
    public function load()
    {
        require_once __DIR__ . '/../Services/DynamicTableService.php';

        $service = new DynamicTableService();
        echo json_encode($service->handle($_POST));
    }
    public function update()
    {
        $this->legacyMutationDisabled();
    }

    public function delete()
    {
        $this->legacyMutationDisabled();
    }
    private function legacyMutationDisabled():void
    {
        http_response_code(403);header('Content-Type: application/json;charset=UTF-8');
        echo json_encode(['success'=>false,'message'=>'Mutasi referensi harus melalui engine berizin sesuai role.']);
    }
}
