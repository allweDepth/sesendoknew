<?php
require_once __DIR__.'/../Core/Auth.php';

class HomeController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            header("Location: /dashboard");
            exit;
        }

        $this->view('home/index');
    }
}