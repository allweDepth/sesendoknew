<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Services/KontrakRealisasiService.php';
require_once __DIR__ . '/../Services/JsonResponse.php';
require_once __DIR__ . '/../Services/ProcurementDocumentService.php';

class KontrakController extends Controller
{
  public function index()
  {
    if (!Auth::check()) {
      header('Location: ' . app_url('/'));
      exit;
    }
    $this->view('kontrak/index');
  }
  public function summary()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->summary());
  }
  public function availableSubActivities()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->availableSubActivities((int)($_GET['contract_id'] ?? 0), (string)($_GET['q'] ?? '')));
  }
  public function availableItems()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->availableItems((string)($_GET['q'] ?? ''), (int)($_GET['contract_id'] ?? 0), (string)($_GET['kd_sub_keg'] ?? ''), (int)($_GET['limit'] ?? 50)));
  }
  public function realizationContracts()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->realizationContracts());
  }
  public function realizationItems()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->realizationItems((int)($_GET['contract_id'] ?? 0)));
  }
  public function realizationDetail()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->realizationDetail((int)($_GET['id'] ?? 0)));
  }
  public function saveRealization()
  {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($payload)) $payload = $_POST;
    $items = $payload['items'] ?? [];
    if (is_string($items)) $items = json_decode($items, true);
    $files=[];$upload=$_FILES['transaction_files']??[];if(isset($upload['name'])&&is_array($upload['name']))foreach($upload['name'] as $i=>$name)$files[]=['name'=>$name,'type'=>$upload['type'][$i]??'','tmp_name'=>$upload['tmp_name'][$i]??'','error'=>$upload['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$upload['size'][$i]??0];elseif(!empty($upload))$files[]=$upload;
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->saveRealization($payload, is_array($items) ? $items : [],$files), 'Realisasi berhasil disimpan');
  }
  public function deleteRealizationDocument(){ $this->json(fn()=>(new KontrakRealisasiService($_SESSION['user']??[]))->deleteRealizationDocument((int)($_POST['id']??0)),'File transaksi berhasil dihapus'); }
  public function downloadRealizationDocument(){try{$d=(new KontrakRealisasiService($_SESSION['user']??[]))->realizationDocument((int)($_GET['id']??0));$path=dirname(__DIR__,2).'/'.$d['path_file'];if(!is_file($path))throw new RuntimeException('File fisik transaksi tidak ditemukan');header('Content-Type: '.$d['mime_type']);header('Content-Disposition: attachment; filename="'.rawurlencode($d['nama_file_asli']).'"');header('Content-Length: '.filesize($path));readfile($path);}catch(Throwable $e){http_response_code(404);echo$e->getMessage();}exit;}
  public function items()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->contractItems((int)($_GET['contract_id'] ?? 0)));
  }
  public function saveItems()
  {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($payload)) $payload = $_POST;
    $items = $payload['items'] ?? [];
    if (is_string($items)) $items = json_decode($items, true);
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->saveContractItems((int)($payload['contract_id'] ?? 0), is_array($items) ? $items : []), 'Rincian uraian kontrak berhasil disimpan');
  }
  public function delivery()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->delivery((int)($_GET['contract_id'] ?? 0)));
  }
  public function saveRab()
  {
    $p = $this->payload();
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->saveRab((int)($p['contract_id'] ?? 0), $this->arrayValue($p['items'] ?? [])), 'RAB berhasil disimpan dan bobot item dihitung ulang');
  }
  public function saveSchedule()
  {
    $p = $this->payload();
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->saveSchedule((int)($p['contract_id'] ?? 0), $this->arrayValue($p['weeks'] ?? [])), 'Time Schedule per item RAB berhasil disimpan dan Kurva S dihitung ulang');
  }
  public function rabExcel()
  {
    $id = (int)($_GET['contract_id'] ?? 0);
    try {
      $file = (new KontrakRealisasiService($_SESSION['user'] ?? []))->rabExcel($id);
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="rab-kurva-s-' . $id . '.xlsx"');
      readfile($file);
      unlink($file);
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function rabPdf()
  {
    $id = (int)($_GET['contract_id'] ?? 0);
    try {
      $body = (new KontrakRealisasiService($_SESSION['user'] ?? []))->rabPdf($id);
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="rab-kurva-s-' . $id . '.pdf"');
      echo $body;
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function termsPdf()
  {
    $id = (int)($_GET['contract_id'] ?? 0);
    $type = (string)($_GET['type'] ?? 'SSKK');
    try {
      $body = (new KontrakRealisasiService($_SESSION['user'] ?? []))->termsPdf($id, $type);
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . strtolower($type) . '-' . $id . '.pdf"');
      echo $body;
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function importRab()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->importRab((int)($_POST['contract_id'] ?? 0), (string)($_FILES['file']['tmp_name'] ?? '')), 'RAB berhasil diimpor');
  }
  public function uploadDocument()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->uploadDocument((int)($_POST['contract_id'] ?? 0), $_POST, $_FILES['file'] ?? []), 'Dokumen kontrak berhasil diunggah');
  }
  public function deleteDocument()
  {
    $this->json(fn() => (new KontrakRealisasiService($_SESSION['user'] ?? []))->deleteDocument((int)($_POST['id'] ?? 0)), 'Dokumen kontrak berhasil dihapus');
  }
  public function downloadDocument()
  {
    try {
      $d = (new KontrakRealisasiService($_SESSION['user'] ?? []))->document((int)($_GET['id'] ?? 0));
      $path = dirname(__DIR__, 2) . '/' . $d['path_file'];
      if (!is_file($path)) throw new RuntimeException('File fisik dokumen tidak ditemukan');
      header('Content-Type: ' . $d['mime_type']);
      header('Content-Disposition: attachment; filename="' . rawurlencode($d['nama_file_asli']) . '"');
      header('Content-Length: ' . filesize($path));
      readfile($path);
    } catch (Throwable $e) {
      http_response_code(404);
      echo $e->getMessage();
    }
    exit;
  }
  public function pdf()
  {
    $id = (int)($_GET['id'] ?? 0);
    try {
      $body = (new KontrakRealisasiService($_SESSION['user'] ?? []))->contractPdf($id);
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="kontrak-' . $id . '.pdf"');
      echo $body;
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function reportPdf()
  {
    try {
      $body = (new KontrakRealisasiService($_SESSION['user'] ?? []))->reportPdf();
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="laporan-kontrak-realisasi.pdf"');
      echo $body;
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function reportExcel()
  {
    try {
      $file = (new KontrakRealisasiService($_SESSION['user'] ?? []))->reportExcel();
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="laporan-kontrak-realisasi.xlsx"');
      readfile($file);
      unlink($file);
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function financialExcel()
  {
    $f = (string)($_GET['format'] ?? 'lra');
    try {
      $file = (new KontrakRealisasiService($_SESSION['user'] ?? []))->financialExcel($f);
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="' . $f . '.xlsx"');
      readfile($file);
      unlink($file);
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function financialPdf()
  {
    $f = (string)($_GET['format'] ?? 'lra');
    try {
      $body = (new KontrakRealisasiService($_SESSION['user'] ?? []))->financialPdf($f);
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . $f . '.pdf"');
      echo $body;
    } catch (Throwable $e) {
      http_response_code(400);
      echo $e->getMessage();
    }
    exit;
  }
  public function procurementTemplates()
  {
    $this->json(fn() => (new ProcurementDocumentService($_SESSION['user'] ?? []))->templates((int)($_GET['contract_id'] ?? 0)));
  }
  public function procurementDocuments()
  {
    $service = new ProcurementDocumentService($_SESSION['user'] ?? []);
    $this->json(fn() => !empty($_GET['id']) ? $service->detail((int)$_GET['id']) : $service->list((int)($_GET['contract_id'] ?? 0)));
  }
  public function procurementDraft()
  {
    $this->json(fn() => (new ProcurementDocumentService($_SESSION['user'] ?? []))->draft((int)($_GET['contract_id'] ?? 0), (int)($_GET['master_id'] ?? 0)));
  }
  public function procurementSave()
  {
    $p = $this->payload();
    $this->json(fn() => (new ProcurementDocumentService($_SESSION['user'] ?? []))->save($p), 'Dokumen pengadaan berhasil disimpan sebagai snapshot yang dapat diedit');
  }
  public function procurementPdf()
  {
    $id=(int)($_GET['id']??0);
    try{$body=(new ProcurementDocumentService($_SESSION['user']??[]))->pdf($id);header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="dokumen-pengadaan-'.$id.'.pdf"');echo$body;}catch(Throwable $e){http_response_code(400);echo$e->getMessage();}exit;
  }
  private function json(callable $callback, string $message = 'Data berhasil dimuat'): void
  {
    header('Content-Type: application/json;charset=UTF-8');
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
  private function payload(): array
  {
    $p = json_decode((string)file_get_contents('php://input'), true);
    return is_array($p) ? $p : $_POST;
  }
  private function arrayValue($value): array
  {
    if (is_string($value)) $value = json_decode($value, true);
    return is_array($value) ? $value : [];
  }
}
