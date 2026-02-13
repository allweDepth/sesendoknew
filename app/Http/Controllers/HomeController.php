<?php
namespace app\Http\Controllers;

class HomeController {
    public function index() {
        if (!isset($_SESSION['user'])) {
            header('Location: /auth/login');
            exit;
        }
        view('home/index');
    }
}