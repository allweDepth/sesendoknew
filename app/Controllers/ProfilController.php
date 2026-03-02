<?php
require_once __DIR__ . '/../Core/Auth.php';

class ProfilController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $this->view('profil/index', [], 'app');
    }
}