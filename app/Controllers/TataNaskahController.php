<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class TataNaskahController extends Controller
{
  public function __construct()
  {
    Auth::check();
  }

  public function dashboard()
  {
    $this->view('tata_naskah/dashboard');
  }

  public function buat()
  {
    $db = DB::getInstance();

    $kelompok = $db->select(
      'ref_kelompok_naskah',
      'id, nama',
      'ORDER BY id ASC'
    );

    $this->view('tata_naskah/buat', [
      'kelompok' => $kelompok
    ]);
  }

  public function loadJenis()
{
    $kelompokId = $_POST['kelompok_id'] ?? null;

    if (!$kelompokId) {
        header('Content-Type: application/json');
        echo json_encode([]);
        return;
    }

    $db = DB::getInstance();

    $jenis = $db->query("
        SELECT 
            j.id,
            j.nama,
            j.sub_kategori,
            k.kode AS kelompok_kode,
            k.nama AS kelompok_nama
        FROM ref_jenis_naskah j
        LEFT JOIN ref_kelompok_naskah k ON j.kelompok_id = k.id
        WHERE j.kelompok_id = ?
        ORDER BY j.sub_kategori ASC, j.urutan ASC
    ", [$kelompokId])->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($jenis);
}

  public function loadForm()
{
    $jenisId = $_POST['jenis_id'] ?? null;

    if (!$jenisId) {
        echo json_encode([]);
        return;
    }

    $db = DB::getInstance();

    $jenis = $db->query(
      "SELECT schema_json FROM ref_jenis_naskah WHERE id = ?",
      [$jenisId]
    )->fetch();

    if (!$jenis || empty($jenis['schema_json'])) {
      echo json_encode([]);
      return;
    }

    header('Content-Type: application/json');
    echo $jenis['schema_json'];
}

  public function generate_pdf()
  {
    echo json_encode(['status' => 'not_ready']);
  }
  public function generateNomor()
  {
    $db = DB::getInstance();

    $klasifikasiId = $_POST['klasifikasi_id'] ?? null;

    if (!$klasifikasiId) {
      return JsonResponse::error("Klasifikasi wajib dipilih");
    }

    $tahun = date('Y');

    /* ==============================
       Ambil data klasifikasi
    ============================== */
    $klasifikasi = $db->query(
      "SELECT kode FROM ref_klasifikasi_keamanan WHERE id = ?",
      [$klasifikasiId]
    )->fetch();

    if (!$klasifikasi) {
      return JsonResponse::error("Klasifikasi tidak ditemukan");
    }

    $kodeKlasifikasi = $klasifikasi['kode'];

    /* ==============================
       Ambil / update counter
    ============================== */
    $counter = $db->query(
      "SELECT * FROM trx_nomor_counter 
         WHERE klasifikasi_id = ? AND tahun = ?",
      [$klasifikasiId, $tahun]
    )->fetch();

    if (!$counter) {

      $number = 1;

      $db->insert("trx_nomor_counter", [
        "klasifikasi_id" => $klasifikasiId,
        "tahun" => $tahun,
        "last_number" => 1
      ]);
    } else {

      $number = $counter['last_number'] + 1;

      $db->update(
        "trx_nomor_counter",
        ["last_number" => $number],
        "WHERE id = ?",
        [$counter['id']]
      );
    }

    /* ==============================
       Format nomor
    ============================== */
    $kodeOpd = $_SESSION['user']['kd_opd'];
    $nomorUrut = sprintf("%03d", $number);

    $nomorFinal = "$nomorUrut/$kodeKlasifikasi/$kodeOpd/$tahun";

    return JsonResponse::success("Nomor dibuat", [
      "nomor" => $nomorFinal
    ]);
  }
  public function simpan()
  {
    $db = DB::getInstance();

    $jenisId = $_POST['jenis_id'] ?? null;
    $strukturJson = $_POST['struktur_json'] ?? null;

    if (!$jenisId || !$strukturJson) {
      return JsonResponse::error("Data tidak lengkap");
    }

    $db->query("START TRANSACTION");

    try {

      /* ==============================
           SIMPAN METADATA
        ============================== */

      $db->insert("trx_naskah_dinas", [
        "jenis_id" => $jenisId,
        "workflow_status" => "draft",
        "kd_opd" => $_SESSION['user']['kd_opd'],
        "kd_wilayah" => $_SESSION['user']['kd_wilayah'],
        "tahun" => $_SESSION['user']['tahun'],
        "tgl_insert" => date("Y-m-d H:i:s"),
        "username_insert" => $_SESSION['user']['username']
      ]);

      $naskahId = $db->lastInsertId();

      /* ==============================
           SIMPAN STRUKTUR JSON
        ============================== */

      $db->insert("trx_naskah_struktur", [
        "naskah_id" => $naskahId,
        "struktur_json" => $strukturJson,
        "tgl_insert" => date("Y-m-d H:i:s"),
        "username_insert" => $_SESSION['user']['username'] ?? 'system'
      ]);

      $db->query("COMMIT");

      return JsonResponse::success("Berhasil disimpan");
    } catch (Exception $e) {

      $db->query("ROLLBACK");
      return JsonResponse::error($e->getMessage());
    }
  }
  public function cetak($id)
  {
    $service = new TataNaskahPdfService();
    $file = $service->generate($id);

    return JsonResponse::success("PDF dibuat", ["file" => $file]);
  }
  public function updateStatus()
  {
    $db = DB::getInstance();

    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    if ($status === 'final') {

      $struktur = $db->query(
        "SELECT struktur_json FROM trx_naskah_struktur WHERE naskah_id = ?",
        [$id]
      )->fetch();

      $hash = hash('sha256', $struktur['struktur_json']);

      $update['document_hash'] = $hash;
      $update['final_at'] = date("Y-m-d H:i:s");
    }
    if (!$id || !$status) {
      return JsonResponse::error("Data tidak lengkap");
    }

    $allowed = ['draft', 'verifikasi', 'ttd', 'final'];

    if (!in_array($status, $allowed)) {
      return JsonResponse::error("Status tidak valid");
    }

    $update = [
      "workflow_status" => $status
    ];

    if ($status === 'verifikasi') {
      $update['verified_by'] = $_SESSION['user']['username'];
      $update['verified_at'] = date("Y-m-d H:i:s");
    }

    if ($status === 'ttd') {
      $update['signed_by'] = $_SESSION['user']['username'];
      $update['signed_at'] = date("Y-m-d H:i:s");
    }

    if ($status === 'final') {
      $update['final_at'] = date("Y-m-d H:i:s");
    }

    $db->update("trx_naskah_dinas", $update, "WHERE id = ?", [$id]);

    return JsonResponse::success("Status diperbarui");
  }
 
  public function uploadSignature()
  {
    if (!isset($_FILES['signature'])) {
      return JsonResponse::error("File tidak ditemukan");
    }

    $file = $_FILES['signature'];
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (!in_array(strtolower($ext), ['png'])) {
      return JsonResponse::error("Format harus PNG");
    }

    $filename = "ttd_" . $_SESSION['user']['id'] . ".png";

    $path = __DIR__ . "/../../public/uploads/signature/" . $filename;

    move_uploaded_file($file['tmp_name'], $path);

    DB::getInstance()->update(
      "user_sesendok_biila",
      [
        "signature_image" => $filename,
        "signature_verified" => 0
      ],
      "WHERE id = ?",
      [$_SESSION['user']['id']]
    );

    return JsonResponse::success("TTD berhasil diupload, menunggu verifikasi");
  }
}
