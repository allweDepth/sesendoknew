<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class KepegawaianController extends Controller
{
    private $allowedTables = [
        'asn',
        'pppk',
        'riwayat_jabatan',
        'riwayat_pangkat',
        'cuti',
        'sk_pegawai',
        'pejabat_tahunan',
        'absensi',
        'penugasan_subkegiatan',
        'dokumen_pegawai'
    ];

    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
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
