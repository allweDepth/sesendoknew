<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Services/StandarHargaService.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

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

    public function exportPdf()
    {
        $type = $_GET['tbl'] ?? '';
        try {
            $pdf = (new StandarHargaService($_SESSION['user'] ?? []))->exportPdf($type);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="standar-harga-' . $type . '.pdf"');
            echo $pdf;
        } catch (Throwable $exception) {
            http_response_code(400);
            echo $exception->getMessage();
        }
        exit;
    }

    public function copyYear()
    {
        header('Content-Type: application/json; charset=UTF-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo JsonResponse::error('Method tidak diizinkan');
            return;
        }
        if (empty($_SESSION['csrf_token']) || ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '') !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo JsonResponse::error('CSRF validation gagal');
            return;
        }
        try {
            $result = (new StandarHargaService($_SESSION['user'] ?? []))->copyYear(
                $_POST['tbl'] ?? '',
                (int)($_POST['target_year'] ?? 0)
            );
            echo JsonResponse::success('Copy tahun selesai', $result, []);
        } catch (Throwable $exception) {
            http_response_code(400);
            echo JsonResponse::error($exception->getMessage());
        }
    }
}
