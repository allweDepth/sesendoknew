<?php
require_once __DIR__.'/../Core/Controller.php';

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . app_url('/'));
            exit;
        }

        $this->view('dashboard/index');
    }
}
