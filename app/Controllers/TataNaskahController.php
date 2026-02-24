<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../Services/JsonResponse.php';

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
      echo JsonResponse::success("Kosong", null, []);
      return;
    }

    $db = DB::getInstance();

    $jenis = $db->query("
        SELECT 
            j.id,
            j.nama,
            j.kode_form,
            j.sub_kategori,
            k.kode AS kelompok_kode,
            k.nama AS kelompok_nama
        FROM ref_jenis_naskah j
        LEFT JOIN ref_kelompok_naskah k ON j.kelompok_id = k.id
        WHERE j.kelompok_id = ?
        ORDER BY j.sub_kategori ASC, j.urutan ASC
    ", [$kelompokId])->fetchAll();

    echo JsonResponse::success("Data ditemukan", null, $jenis);
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
      echo JsonResponse::error("Klasifikasi wajib dipilih");
      return;
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
      echo JsonResponse::error("Klasifikasi tidak ditemukan");
      return;
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

    echo JsonResponse::success("Nomor dibuat", [
      "nomor" => $nomorFinal
    ]);
    return;
  }
  public function simpan()
  {
    $db = DB::getInstance();

    $jenisId = $_POST['jenis_id'] ?? null;

    if (!$jenisId) {
      echo JsonResponse::error("Jenis tidak ditemukan");
      return;
    }

    $db->query("START TRANSACTION");

    try {

      // Simpan header umum saja
      $db->insert("trx_naskah_dinas", [
        "jenis_id" => $jenisId,
        "nomor" => $_POST['nomor'] ?? null,
        "workflow_status" => "draft",
        "kd_opd" => $_SESSION['user']['kd_opd'],
        "kd_wilayah" => $_SESSION['user']['kd_wilayah'],
        "tahun" => $_SESSION['user']['tahun'],
        "tgl_insert" => date("Y-m-d H:i:s"),
        "username_insert" => $_SESSION['user']['username']
      ]);

      $naskahId = $db->lastInsertId();

      // 🔥 Gabungkan semua POST kecuali sistem field
      $payload = $_POST;
      unset($payload['jenis_id']);
      unset($payload['module']);
      unset($payload['action']);
      unset($payload['tbl']);

      $db->insert("trx_naskah_struktur", [
        "naskah_id" => $naskahId,
        "struktur_json" => json_encode($payload),
        "kd_wilayah" => $_SESSION['user']['kd_wilayah'],
        "kd_opd" => $_SESSION['user']['kd_opd'],
        "tahun" => $_SESSION['user']['tahun'],
        "tgl_insert" => date("Y-m-d H:i:s"),
        "username_insert" => $_SESSION['user']['username']
      ]);

      $db->query("COMMIT");

      echo JsonResponse::success("Berhasil disimpan");
      return;
    } catch (Exception $e) {
      $db->query("ROLLBACK");
      die($e->getMessage());
    }
  }
  public function cetak($id)
  {
    $service = new TataNaskahPdfService();
    $file = $service->generate($id);

    echo JsonResponse::success("PDF dibuat", ["file" => $file]);
    return;
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
      echo JsonResponse::error("Data tidak lengkap");
      return;
    }

    $allowed = ['draft', 'verifikasi', 'ttd', 'final'];

    if (!in_array($status, $allowed)) {
      echo JsonResponse::error("Status tidak valid");
      return;
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

    echo JsonResponse::success("Status diperbarui");
    return;
  }

  public function uploadSignature()
  {
    if (!isset($_FILES['signature'])) {
      echo JsonResponse::error("File tidak ditemukan");
      return;
    }

    $file = $_FILES['signature'];
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (!in_array(strtolower($ext), ['png'])) {
      echo JsonResponse::error("Format harus PNG");
      return;
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

    echo JsonResponse::success("TTD berhasil diupload, menunggu verifikasi");
    return;
  }
  public function schema()
  {
    header('Content-Type: application/json');

    $jenisId = $_POST['jenis_id'] ?? null;

    if (!$jenisId) {
      echo json_encode([]);
      return;
    }

    $db = DB::getInstance();

    /* ===============================
       AMBIL DATA JENIS
    =============================== */
    $jenis = $db->query(
      "SELECT * FROM ref_jenis_naskah WHERE id = ?",
      [$jenisId]
    )->fetch();

    if (!$jenis) {
      echo json_encode([]);
      return;
    }

    /* ===============================
       CEK PERMISSION
    =============================== */
    $userRole = $_SESSION['user']['type_user'] ?? null;

    if (!empty($jenis['allowed_roles'])) {

      $allowed = explode(',', $jenis['allowed_roles']);

      if (!in_array($userRole, $allowed)) {
        echo json_encode([
          "error" => "Anda tidak memiliki akses ke jenis ini"
        ]);
        return;
      }
    }
    /* ===============================
   CACHING SCHEMA (AMAN FINAL)
=============================== */

    $schema = [];

    $cache = $db->query(
      "SELECT schema_json FROM cache_schema_naskah 
     WHERE jenis_id = ? AND schema_version = ?",
      [$jenisId, $jenis['schema_version']]
    )->fetch();

    if ($cache && !empty($cache['schema_json'])) {

      $decoded = json_decode($cache['schema_json'], true);
      if (is_array($decoded)) {
        $schema = $decoded;
      }
    } else {

      if (!empty($jenis['schema_json'])) {

        $decoded = json_decode($jenis['schema_json'], true);

        if (is_array($decoded)) {
          $schema = $decoded;

          // simpan ke cache hanya jika valid
          $db->insert("cache_schema_naskah", [
            "jenis_id" => $jenisId,
            "schema_version" => $jenis['schema_version'],
            "schema_json" => $jenis['schema_json'],
            "tgl_insert" => date("Y-m-d H:i:s")
          ]);
        }
      }
    }

    /* ===============================
       PRELOAD NOMOR OTOMATIS
    =============================== */

    $nomor = null;

    if (!empty($jenis['auto_generate_nomor'])) {
      $nomor = $this->generateNomorInternal();
    }

    /* ===============================
       AMBIL ASN
    =============================== */
    /* ===============================
   AMBIL ASN (db_asn_pemda_neo)
=============================== */
    $asn = $db->query(
      "SELECT 
        id, 
        CONCAT(
            IFNULL(gelar_depan,''), 
            IF(gelar_depan IS NULL OR gelar_depan = '', '', ' '),
            nama,
            IF(gelar IS NULL OR gelar = '', '', CONCAT(', ', gelar))
        ) AS uraian
     FROM db_asn_pemda_neo
     WHERE disable = 0 
       AND is_deleted = 0
     ORDER BY nama ASC"
    )->fetchAll();

    /* ===============================
       AMBIL KLASIFIKASI
    =============================== */
    $klasifikasi = $db->query(
      "SELECT id,CONCAT(kode, ' - ', nama) AS uraian
     FROM ref_klasifikasi_keamanan
     ORDER BY kode ASC"
    )->fetchAll();

    echo json_encode([
      "schema" => $schema,
      "asn" => $asn,
      "klasifikasi" => $klasifikasi,
      "nomor_auto" => $nomor
    ]);
  }
  private function generateNomorInternal()
  {
    $tahun = date('Y');

    $db = DB::getInstance();

    $count = $db->query(
      "SELECT COUNT(*) as total FROM trx_naskah_dinas 
         WHERE tahun = ?",
      [$tahun]
    )->fetch()['total'];

    $number = $count + 1;

    return sprintf("%03d/TN/%s", $number, $tahun);
  }
  public function daftar()
  {
    $this->view('tata_naskah/daftar', [
        'tbl'      => 'trx_naskah_dinas',
        'jenis'    => 'default',
        'totalCol' => 5
    ]);
  }
}
