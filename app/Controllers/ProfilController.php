<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class ProfilController extends Controller
{
    private $db;

    public function __construct()
    {
        Auth::check();
        $this->db = DB::getInstance();
    }

    public function index()
    {
        $this->view('profil/index');
    }

    public function load()
    {
        if (!isset($_SESSION['user'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session expired'
            ]);
            return;
        }

        $email = $_SESSION['user']['email'];

        $user = $this->db->first(
            'user_sesendok_biila',
            'WHERE email = ?',
            [$email]
        );

        unset($user['password'], $user['remember_token']);

        echo json_encode([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function update()
    {
        if (!isset($_SESSION['user'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session expired'
            ]);
            return;
        }

        $email = $_SESSION['user']['email'];

        $data = [
            'nama'           => $_POST['nama'] ?? null,
            'nip'            => $_POST['nip'] ?? null,
            'tahun'          => $_POST['tahun'] ?? null,
            'kontak_person'  => $_POST['kontak_person'] ?? null,
            'alamat'         => $_POST['alamat'] ?? null,
            'font_size'      => $_POST['font_size'] ?? null,
            'theme'          => $_POST['theme'] ?? null,
            'warna_tbl'      => $_POST['warna_tbl'] ?? null,
            'scrolling_table'=> $_POST['scrolling_table'] ?? null,
            'ket'            => $_POST['ket'] ?? null,
        ];

        $data = array_filter($data, fn($v) => $v !== null);

        $this->db->update(
            'user_sesendok_biila',
            $data,
            'WHERE email = ?',
            [$email]
        );

        echo json_encode([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui'
        ]);
    }
}