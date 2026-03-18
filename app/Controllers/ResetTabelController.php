<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class ResetTabelController extends Controller
{

  private function checkAuth()
  {
    if (!Auth::check()) {
      header("Location: /");
      exit;
    }
  }

  /*
    |--------------------------------------------------------------------------
    | TABEL YANG BOLEH DIRESET
    |--------------------------------------------------------------------------
    */

  private $allowedTables = [
    'satuan_neo',
    'rekening_kegiatan',
    'rekanan_neo',
    'organisasi_neo',
    'wilayah_neo',
    'sumber_dana_neo',
    'renstra',
    'misi_renstra_neo',
    'tujuan_renstra_neo',
    'sasaran_renstra_neo', // ditambahkan
    'sub_kegiatan_renstra_neo',
    'program_renstra_neo',
    'indikator_program_renstra_neo',
    'kegiatan_renstra_neo',
    'cache_schema_naskah',
    'ref_jenis_naskah',
    'trx_naskah_dinas',
    'trx_naskah_meta',
    'trx_naskah_struktur',
    'trx_nomor_counter',
    'rekanan_neo',
  ];


  /*
    |--------------------------------------------------------------------------
    | HALAMAN RESET TABEL
    |--------------------------------------------------------------------------
    */

  public function index()
  {
    $this->checkAuth();

    $sql = "
            SELECT TABLE_NAME, TABLE_ROWS
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_TYPE='BASE TABLE'
            ORDER BY TABLE_NAME
        ";

    $tables = DB::getInstance()
      ->query($sql)
      ->fetchAll();

    $this->view('reset_tabel/index', [
      'tables' => $tables
    ], 'app');
  }


  /*
    |--------------------------------------------------------------------------
    | RESET DATA TABEL
    |--------------------------------------------------------------------------
    */

  public function reset()
  {
    // ======================================================
    // CEK LOGIN USER
    // ======================================================
    $this->checkAuth();

    // ======================================================
    // AMBIL NAMA TABEL DARI POST
    // ======================================================
    $table = $_POST['table'] ?? null;

    // ======================================================
    // VALIDASI TABLE TIDAK BOLEH KOSONG
    // ======================================================
    if (!$table) {
      echo json_encode([
        'success' => false,
        'message' => 'Table kosong'
      ]);
      exit;
    }

    // ======================================================
    // VALIDASI NAMA TABEL
    // hanya huruf angka underscore
    // mencegah SQL injection pada nama tabel
    // ======================================================
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {

      echo json_encode([
        'success' => false,
        'message' => 'Nama tabel tidak valid'
      ]);
      exit;
    }

    // ======================================================
    // VALIDASI WHITELIST TABEL
    // strict mode agar tidak ada type juggling
    // ======================================================
    if (!in_array($table, $this->allowedTables, true)) {

      echo json_encode([
        'success' => false,
        'message' => 'Tabel tidak diizinkan'
      ]);
      exit;
    }

    // ======================================================
    // AMBIL INSTANCE DATABASE
    // ======================================================
    $db = DB::getInstance();

    // ======================================================
    // AMBIL ROLE USER DARI SESSION
    // ======================================================
    $role = $_SESSION['user']['type_user'] ?? null;

    // ======================================================
    // AMBIL KODE WILAYAH USER
    // ======================================================
    $kd_wilayah = $_SESSION['user']['kd_wilayah'] ?? null;

    try {

      // ==================================================
      // ROLE SUPER ADMIN
      // reset seluruh isi tabel
      // ==================================================
      if ($role === 'super_admin') {

        // nonaktifkan foreign key sementara
        $db->query("SET FOREIGN_KEY_CHECKS=0");

        // truncate tabel
        $db->query("TRUNCATE TABLE `$table`");

        // aktifkan kembali foreign key
        $db->query("SET FOREIGN_KEY_CHECKS=1");
      }

      // ==================================================
      // ROLE ADMIN WILAYAH
      // hanya menghapus data wilayah user
      // ==================================================
      elseif ($role === 'admin_wilayah') {

        // cek apakah tabel memiliki kolom kd_wilayah
        $colCheck = $db->query("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'kd_wilayah'
            ", [$table])->fetch();

        // ==================================================
        // jika tabel memiliki kolom kd_wilayah
        // ==================================================
        if ($colCheck['cnt'] > 0) {

          // hapus hanya data wilayah user
          $db->delete(
            $table,
            "WHERE kd_wilayah = ?",
            [$kd_wilayah]
          );
        } else {

          // tabel tidak memiliki kolom wilayah
          echo json_encode([
            'success' => false,
            'message' => 'Tabel tidak memiliki kolom kd_wilayah'
          ]);
          exit;
        }
      }

      // ==================================================
      // ROLE TIDAK MEMILIKI AKSES
      // ==================================================
      else {

        echo json_encode([
          'success' => false,
          'message' => 'Tidak memiliki akses'
        ]);
        exit;
      }

      // ==================================================
      // RESPONSE SUKSES
      // ==================================================
      echo json_encode([
        'success' => true,
        'message' => 'Reset tabel berhasil'
      ]);
    }

    // ======================================================
    // ERROR HANDLER
    // ======================================================
    catch (Exception $e) {

      echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
      ]);
    }
  }


  /*
    |--------------------------------------------------------------------------
    | BACKUP DATABASE
    |--------------------------------------------------------------------------
    */

  public function backup()
  {
    // ======================================================
    // CEK LOGIN USER
    // ======================================================
    $this->checkAuth();

    // ======================================================
    // HANYA SUPER ADMIN BOLEH BACKUP
    // ======================================================
    if (($_SESSION['user']['type_user'] ?? null) !== 'super_admin') {
      exit;
    }

    // ======================================================
    // AMBIL INSTANCE DATABASE
    // ======================================================
    $db = DB::getInstance();

    // ======================================================
    // AMBIL DAFTAR TABEL
    // ======================================================
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    // ======================================================
    // SET HEADER DOWNLOAD SQL
    // ======================================================
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename=backup_' . date('Ymd_His') . '.sql');

    // ======================================================
    // LOOP SETIAP TABEL
    // ======================================================
    foreach ($tables as $table) {

      // ==================================================
      // AMBIL CREATE TABLE
      // ==================================================
      $create = $db->query("SHOW CREATE TABLE `$table`")->fetch();

      // tulis struktur tabel
      echo $create['Create Table'] . ";\n\n";

      // ==================================================
      // AMBIL DATA TABEL DENGAN STREAMING
      // ==================================================
      $stmt = $db->query("SELECT * FROM `$table`");

      // ==================================================
      // LOOP BARIS DATA
      // ==================================================
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // ==================================================
        // ESCAPE VALUE
        // ==================================================
        $values = array_map(function ($v) {

          // jika null
          if ($v === null) {
            return "NULL";
          }

          // escape string
          return "'" . addslashes($v) . "'";
        }, array_values($row));

        // ==================================================
        // CETAK QUERY INSERT
        // ==================================================
        echo "INSERT INTO `$table` VALUES (" . implode(",", $values) . ");\n";
      }

      // ==================================================
      // JEDA ANTAR TABEL
      // ==================================================
      echo "\n\n";
    }

    // ======================================================
    // AKHIR SCRIPT
    // ======================================================
    exit;
  }


  /*
    |--------------------------------------------------------------------------
    | RESTORE DATABASE
    |--------------------------------------------------------------------------
    */

  public function restore()
  {
    $this->checkAuth();

    if (($_SESSION['user']['type_user'] ?? null) !== 'super_admin') {
      exit;
    }

    if (!isset($_FILES['file'])) {
      exit;
    }

    $sql = file_get_contents($_FILES['file']['tmp_name']);

    $db = DB::getInstance();

    $db->query("SET FOREIGN_KEY_CHECKS=0");
    $db->query($sql);
    $db->query("SET FOREIGN_KEY_CHECKS=1");

    echo json_encode(['success' => true]);
  }


  /*
    |--------------------------------------------------------------------------
    | COUNT DATA TABEL
    |--------------------------------------------------------------------------
    */

  public function count()
  {
    // ======================================================
    // CEK LOGIN USER
    // ======================================================
    $this->checkAuth();

    // ======================================================
    // AMBIL NAMA TABEL
    // ======================================================
    $table = $_POST['table'] ?? null;

    // jika kosong langsung keluar
    if (!$table) {
      exit;
    }

    // ======================================================
    // VALIDASI FORMAT NAMA TABEL
    // ======================================================
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
      exit;
    }

    // ======================================================
    // VALIDASI WHITELIST
    // ======================================================
    if (!in_array($table, $this->allowedTables, true)) {
      exit;
    }

    // ======================================================
    // AMBIL INSTANCE DATABASE
    // ======================================================
    $db = DB::getInstance();

    // ======================================================
    // AMBIL ROLE USER
    // ======================================================
    $role = $_SESSION['user']['type_user'] ?? null;

    // ======================================================
    // AMBIL KODE WILAYAH USER
    // ======================================================
    $kd_wilayah = $_SESSION['user']['kd_wilayah'] ?? null;

    // ======================================================
    // SUPER ADMIN → HITUNG SEMUA DATA
    // ======================================================
    if ($role === 'super_admin') {

      $row = $db->query(
        "SELECT COUNT(*) as jml FROM `$table`"
      )->fetch();
    }

    // ======================================================
    // ADMIN WILAYAH → HITUNG DATA PER WILAYAH
    // ======================================================
    else {

      $row = $db->query(
        "SELECT COUNT(*) as jml FROM `$table` WHERE kd_wilayah=?",
        [$kd_wilayah]
      )->fetch();
    }

    // ======================================================
    // RESPONSE JSON
    // ======================================================
    echo json_encode([
      'success' => true,
      'count' => $row['jml']
    ]);
  }
}