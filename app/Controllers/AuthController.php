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
}
