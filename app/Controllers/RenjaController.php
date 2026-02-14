<?php
require_once __DIR__.'/../Core/Controller.php';

class RenjaController extends Controller
{
    public function index()
    {
        $db = DB::getInstance();
        $tahun = $_SESSION['user']['tahun'];

        $data['renja'] = $db->get(
            'renja_neo',
            'WHERE tahun = ? AND disable = 0',
            [$tahun]
        );

        $this->view('anggaran/renja/index', $data);
    }
}
