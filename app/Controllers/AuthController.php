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
            header("Location: /");
            exit;
        }

        // LOGIN BERHASIL
        header("Location: /dashboard");
        exit;
    }

    public function logout()
    {
        Auth::logout();
        header("Location: /");
        exit;
    }
    public function register()
    {
        $data = [
            'username'      => $_POST['username'] ?? '',
            'email'         => $_POST['email'] ?? '',
            'nama_lengkap'  => $_POST['nama_lengkap'] ?? '',
            'nip'           => $_POST['nip'] ?? '',
            'kontak_person' => $_POST['kontak_person'] ?? '',
            'alamat'        => $_POST['alamat'] ?? '',
            'password'      => $_POST['password'] ?? '',
            'wilayah'       => $_POST['wilayah'] ?? '',
            'organisasi'    => $_POST['organisasi'] ?? '',
        ];

        // Validasi sederhana
        if (empty($data['username']) || empty($data['password'])) {
            $_SESSION['register_error'] = "Username dan password wajib diisi";
            header("Location: /");
            exit;
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Simpan ke database
        require_once __DIR__ . '/../Models/UserModel.php';
        $userModel = new UserModel();

        if (!$userModel->insertUser($data)) {
            $_SESSION['register_error'] = "Gagal menyimpan data";
            header("Location: /");
            exit;
        }

        $_SESSION['register_success'] = "Registrasi berhasil, silakan login";
        header("Location: /");
        exit;
    }
}
