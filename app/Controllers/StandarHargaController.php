<?php
require_once __DIR__ . '/../Core/Auth.php';

class StandarHargaController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $this->view('standar_harga/index', [], 'app');
    }
}
