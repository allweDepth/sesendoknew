<?php

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Services/LaporanKeuanganService.php';
require_once __DIR__ . '/../Services/RealisasiKeuanganService.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

class LaporanKeuanganController extends Controller
{
  private function json(callable $callback, string $message = 'Data berhasil dimuat'): void
  {
    header('Content-Type: application/json; charset=UTF-8');
    if (!Auth::check()) {
      echo JsonResponse::error('Unauthorized', 401);
      return;
    }
    try {
      echo JsonResponse::success($message, [], $callback());
    } catch (Throwable $e) {
      echo JsonResponse::error($e->getMessage(), 400);
    }
  }

  private function service(): LaporanKeuanganService
  {
    if (!Auth::check()) throw new RuntimeException('Silakan masuk kembali.');
    return new LaporanKeuanganService($_SESSION['user'] ?? []);
  }

  private function payload(): array
  {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : $_POST;
  }

  public function index(): void
  {
    if (!Auth::check()) {
      header('Location: ' . app_url('/'));
      exit;
    }
    $this->view('laporan_keuangan/index');
  }

  public function data(): void
  {
    $this->json(function () {
      $service = $this->service();
      return match ((string)($_GET['type'] ?? 'lra')) {
        'lra' => $service->lraData(),
        'neraca' => $service->balanceData(),
        'calk' => $service->noteData(),
        default => throw new InvalidArgumentException('Jenis laporan tidak dikenal.'),
      };
    });
  }

  public function verifyTransaction(): void
  {
    $payload = $this->payload();
    $uuid = trim((string)($payload['transaksi_uuid'] ?? ''));
    $this->json(
      fn() => (new RealisasiKeuanganService($_SESSION['user'] ?? []))->verify($uuid),
      'Transaksi berhasil diverifikasi.'
    );
  }

  public function saveBalances(): void
  {
    $payload = $this->payload();
    $rows = $payload['rows'] ?? null;
    $this->json(fn() => is_array($rows)
      ? $this->service()->saveBalances($rows)
      : throw new InvalidArgumentException('Rincian saldo Neraca tidak valid.'), 'Saldo Neraca berhasil disimpan.');
  }

  public function saveNotes(): void
  {
    $payload = $this->payload();
    $rows = $payload['rows'] ?? null;
    $this->json(fn() => is_array($rows)
      ? $this->service()->saveNotes($rows)
      : throw new InvalidArgumentException('Catatan CaLK tidak valid.'), 'Catatan CaLK berhasil disimpan.');
  }

  public function importBalances(): void
  {
    $file = $_FILES['file'] ?? [];
    try {
      if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Pilih file template Excel yang valid.');
      if ((int)($file['size'] ?? 0) > 10 * 1024 * 1024) throw new InvalidArgumentException('Ukuran file maksimal 10 MB.');
      $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
      if (!in_array($extension, ['xlsx', 'xls'], true)) throw new InvalidArgumentException('File impor harus berformat .xlsx atau .xls.');
      $data = $this->service()->importBalances((string)$file['tmp_name']);
      header('Content-Type: application/json; charset=UTF-8');
      echo JsonResponse::success('Saldo Neraca berhasil diimpor.', [], $data);
    } catch (Throwable $e) {
      http_response_code(400);
      header('Content-Type: application/json; charset=UTF-8');
      echo JsonResponse::error($e->getMessage(), 400);
    }
  }

  public function template(): void
  {
    try {
      $file = $this->service()->template();
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="template-saldo-neraca.xlsx"');
      header('Content-Length: ' . filesize($file));
      readfile($file);
      unlink($file);
    } catch (Throwable $e) {
      http_response_code(400);
      header('Content-Type: text/plain; charset=UTF-8');
      echo $e->getMessage();
    }
    exit;
  }

  public function export(): void
  {
    $report = (string)($_GET['report'] ?? '');
    $format = (string)($_GET['format'] ?? '');
    try {
      $file = $this->service()->export($report, $format);
      if ($format === 'xlsx') {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="laporan-keuangan-' . $report . '.xlsx"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink($file);
      } else {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="laporan-keuangan-' . $report . '.pdf"');
        echo $file;
      }
    } catch (Throwable $e) {
      http_response_code(400);
      header('Content-Type: text/plain; charset=UTF-8');
      echo $e->getMessage();
    }
    exit;
  }
}
