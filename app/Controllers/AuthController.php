<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';

class AuthController extends Controller
{

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!Auth::login($username, $password)) {
            $_SESSION['login_error'] = "Username atau password salah";
            header('Location: ' . app_url('/'));
            exit;
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // LOGIN BERHASIL
        header('Location: ' . app_url('/dashboard'));
        exit;
    }

    public function logout()
    {
        Auth::logout();
        header('Location: ' . app_url('/'));
        exit;
    }
    public function register()
    {
        header('Content-Type: application/json');

        $data = [
            'username'      => $_POST['username'] ?? '',
            'email'         => $_POST['email'] ?? '',
            'nama'          => $_POST['nama'] ?? '',
            'nip'           => $_POST['nip'] ?? '',
            'kontak_person' => $_POST['kontak_person'] ?? '',
            'alamat'        => $_POST['alamat'] ?? '',
            'password'      => $_POST['password'] ?? '',
            'kd_wilayah'    => $_POST['kd_wilayah'] ?? '',
            'kd_opd'        => $_POST['kd_opd'] ?? '',
        ];

        // Validasi sederhana
        if (empty($data['username']) || empty($data['password'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username dan password wajib diisi'
            ]);
            exit;
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        require_once __DIR__ . '/../Models/UserModel.php';
        $userModel = new UserModel();

        if (!$userModel->insertUser($data)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menyimpan data'
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Registrasi berhasil 🎉'
        ]);
        exit;
    }
    public function getWilayah()
    {
        header('Content-Type: application/json');

        $db = DB::getInstance();

        $data = $db->query("
        SELECT kode, uraian 
        FROM wilayah_neo
        ORDER BY uraian ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($data);
    }


    public function getOrganisasi()
    {
        header('Content-Type: application/json');

        $kd_wilayah = $_GET['kd_wilayah'] ?? null;

        if (!$kd_wilayah) {
            echo json_encode([]);
            exit;
        }

        $db = DB::getInstance();

        $data = $db->query(
            "SELECT kode, uraian 
         FROM organisasi_neo 
         WHERE kd_wilayah = ?
         ORDER BY uraian ASC",
            [$kd_wilayah]
        )->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($data);
        exit;
    }
}
