<?php
require_once __DIR__ . '/../Core/Auth.php';

class WallchatController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $this->view('wallchat/index', [], 'app');
    }
}