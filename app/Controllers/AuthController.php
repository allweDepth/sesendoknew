<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthRateLimiter.php';

class AuthController extends Controller
{

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (!is_string($username) || !is_string($password) || strlen($username) > 254 || strlen($password) > 1024) {
            http_response_code(400);
            exit('Isian login tidak valid.');
        }
        $limiter = new AuthRateLimiter(DB::getInstance());
        // Trust REMOTE_ADDR only; clients cannot spoof their bucket using forwarded headers.
        $retry = $limiter->consume('login:ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 30, 900);
        $accountKey = 'login:account:' . mb_strtolower(trim($username), 'UTF-8');
        if (!$retry) $retry = $limiter->consume($accountKey, 10, 900);
        if ($retry) {
            http_response_code(429);
            header('Retry-After: ' . $retry);
            exit('Terlalu banyak percobaan login. Silakan coba lagi dalam beberapa menit.');
        }

        if (!Auth::login($username, $password)) {
            $_SESSION['login_error'] = "Username atau password salah";
            header('Location: ' . app_url('/'));
            exit;
        }
        $limiter->clear($accountKey);
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

    public function status(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'expired' => true, 'message' => 'Session habis. Silakan login ulang.']);
            return;
        }
        echo json_encode(['success' => true, 'message' => 'Session aktif']);
    }
    public function register()
    {
        header('Content-Type: application/json');
        $retry = (new AuthRateLimiter(DB::getInstance()))->consume('register:ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 3600);
        if ($retry) {
            http_response_code(429);
            header('Retry-After: ' . $retry);
            exit(json_encode(['status' => 'error', 'message' => 'Terlalu banyak permintaan registrasi. Silakan coba lagi nanti.']));
        }

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

        foreach ($data as $value) {
            if (!is_string($value) || strlen($value) > 1024) {
                http_response_code(422);
                exit(json_encode(['status' => 'error', 'message' => 'Isian registrasi tidak valid.']));
            }
        }
        if (strlen($data['password']) < 12 || strlen($data['password']) > 72
            || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            exit(json_encode(['status' => 'error', 'message' => 'Email harus valid dan password harus 12–72 karakter.']));
        }
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
