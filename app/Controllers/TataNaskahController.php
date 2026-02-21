<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class TataNaskahController extends Controller
{
    public function __construct()
    {
        Auth::check();
    }

    public function dashboard()
    {
        $this->view('tata_naskah/dashboard');
    }

    public function buat()
    {
        $db = DB::getInstance();

        $kelompok = $db->select(
            'ref_kelompok_naskah',
            'id, nama',
            'ORDER BY id ASC'
        );

        $this->view('tata_naskah/buat', [
            'kelompok' => $kelompok
        ]);
    }

    public function loadJenis()
    {
        $kelompokId = $_POST['kelompok_id'] ?? null;

        if (!$kelompokId) {
            echo json_encode([]);
            return;
        }

        $db = DB::getInstance();

        $jenis = $db->select(
            'ref_jenis_naskah',
            'id, nama',
            'WHERE kelompok_id = ? ORDER BY id ASC',
            [$kelompokId]
        );

        echo json_encode($jenis);
    }

    public function loadForm()
    {
        $jenisId = $_POST['jenis_id'] ?? null;

        if (!$jenisId) {
            echo json_encode([]);
            return;
        }

        $db = DB::getInstance();

        $template = $db->first(
            'ref_template_naskah',
            'WHERE jenis_id = ?',
            [$jenisId]
        );

        if (!$template) {
            echo json_encode([]);
            return;
        }

        echo $template['form_schema'];
    }

    public function generate_pdf()
    {
        echo json_encode(['status' => 'not_ready']);
    }
}