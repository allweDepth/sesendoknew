<?php
require_once __DIR__ . '/../Core/Auth.php';

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
            http_response_code(403);
            die("Tabel tidak diizinkan");
        }

        $this->view('kepegawaian/index', [
            'tbl' => $tbl
        ], 'app');
    }
}