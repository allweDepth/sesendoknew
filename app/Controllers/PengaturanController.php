<?php
require_once __DIR__ . '/../Core/Auth.php';

class PengaturanController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $this->view('pengaturan/index', [], 'app');
    }
    public function fragment()
    {
        $this->view('pengaturan/form');
    }
}
