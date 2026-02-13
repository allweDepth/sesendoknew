<?php
namespace app\Http\Controllers;

use app\Models\User;

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userModel = new User();
            $user = $userModel->findByEmail($_POST['email']);
            if ($user && password_verify($_POST['password'], $user['password'])) {
                $_SESSION['user'] = $user;
                header('Location: /home');
                exit;
            }
        }
        view('auth/login');
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userModel = new User();
            $userModel->create($_POST);
            header('Location: /auth/login');
            exit;
        }
        view('auth/register');
    }

    public function logout() {
        session_destroy();
        header('Location: /auth/login');
        exit;
    }
}