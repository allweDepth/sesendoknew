<?php
class RenstraController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $this->view('anggaran/renstra/index', [], 'app');
    }
}