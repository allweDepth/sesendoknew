<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class KepegawaianController extends Controller
{
    private $allowedTables = [
        'asn',
        'sk_asn',
        'register_surat',
        'tata_naskah'
    ];

    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $tbl = $_GET['tbl'] ?? 'asn';

        if (!in_array($tbl, $this->allowedTables)) {
            $tbl = 'asn';
        }

        $this->view('referensi/index', [
            'tbl' => $tbl
        ], 'app');
    }
}