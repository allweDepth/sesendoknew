<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';

class AuthController extends Controller
{
    public function loginForm()
    {
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (Auth::login($_POST['username'], $_POST['password'])) {
                header("Location: /");
                exit;
            } else {
                $_SESSION['error'] = "Username atau Password salah";
                header("Location: /login");
                exit;
            }
        }
    }

    public function logout()
    {
        Auth::logout();
        header("Location: /login");
        exit;
    }
}
