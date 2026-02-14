<?php
require_once __DIR__.'/../Core/Auth.php';
require_once __DIR__.'/../Core/DB.php';

class ReferensiController extends Controller
{
    private $allowedTables = [
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
        'wilayah'
    ];

    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $tbl = $_GET['tbl'] ?? 'program';

        if (!in_array($tbl, $this->allowedTables)) {
            die("Tabel tidak diizinkan");
        }

        $db = DB::getInstance();

        // contoh mapping tabel database
        $mapTable = [
            'program' => 'sub_kegiatan_neo',
            'kegiatan' => 'sub_kegiatan_neo',
            'sub_kegiatan' => 'sub_kegiatan_neo'
        ];

        $tableName = $mapTable[$tbl] ?? $tbl;

        $rows = $db->get($tableName);

        $this->view('referensi/index', [
            'rows' => $rows,
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

    public function update()
    {
        $db = DB::getInstance();

        $db->update(
            $_POST['tbl'],
            $_POST['data'],
            "WHERE id = ?",
            [$_POST['id']]
        );

        echo json_encode(['status'=>'ok']);
    }

    public function delete()
    {
        $db = DB::getInstance();

        $db->delete(
            $_POST['tbl'],
            "WHERE id = ?",
            [$_POST['id']]
        );

        echo json_encode(['status'=>'ok']);
    }
}