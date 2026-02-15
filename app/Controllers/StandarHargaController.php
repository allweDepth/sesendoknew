<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class StandarHargaController extends Controller
{
    private $allowedTables = [
        'ssh',
        'hspk',
        'asb',
        'sbu'
    ];

    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $tbl = $_GET['tbl'] ?? 'sbu';

        if (!in_array($tbl, $this->allowedTables)) {
            die("Tabel tidak diizinkan");
        }

        $this->view('standar_harga/index', [
            'tbl' => $tbl
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