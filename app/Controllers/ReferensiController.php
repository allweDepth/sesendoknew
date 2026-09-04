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
        $db = DB::getInstance();
        $tbl = $_POST['tbl'];

        $db->insert($tbl, $_POST['data']);

        echo json_encode(['status' => 'ok']);
    }
    public function load()
    {
        require_once __DIR__ . '/../Services/DynamicTableService.php';

        $service = new DynamicTableService();
        echo json_encode($service->handle($_POST));
    }
    public function update()
    {
        $db = DB::getInstance();

        $db->update(
            $_POST['tbl'],
            $_POST['data'],
            "WHERE id = ?",
            [$_POST['id']]
        );

        echo json_encode(['status' => 'ok']);
    }

    public function delete()
    {
        $db = DB::getInstance();

        $db->delete(
            $_POST['tbl'],
            "WHERE id = ?",
            [$_POST['id']]
        );

        echo json_encode(['status' => 'ok']);
    }
}
