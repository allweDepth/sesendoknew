<?php
require_once __DIR__ . '/../Core/Auth.php';

class ResetTabelController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        $this->view('reset_tabel/index', [], 'app');
    }

    public function reset()
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'expired' => true
            ]);
            return;
        }

        // Logic reset tetap di sini atau pindahkan ke Service
    }
}