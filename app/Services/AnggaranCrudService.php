<?php

require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/AnggaranParserService.php';

/**
 * ============================================================
 * ANGARAN CRUD SERVICE (FINAL - INCREMENTAL + PERATURAN)
 * ============================================================
 *
 * Fitur:
 * ------------------------------------------------------------
 * - Insert
 * - Update
 * - Delete (single row)
 * - DeleteHierarchy (cascade)
 * - Auto ensure parent hierarchy
 * - Incremental parent update (tanpa SUM berat)
 * - Lookup uraian master berdasarkan PERATURAN
 *
 * Prinsip Kerja:
 * ------------------------------------------------------------
 * - Parent tidak dihitung ulang dengan SUM,
 *   tetapi ditambah/dikurangi berdasarkan delta.
 * - Hierarki parent otomatis dibuat jika belum ada.
 * - Uraian parent diambil dari tabel master:
 *      sub_kegiatan_neo
 *      akun_neo
 *   dengan filter peraturan.
 *
 * ============================================================
 */

class AnggaranCrudService
{
  /* ============================================================
       PROPERTIES
    ============================================================ */

  private DB $db;
  private string $table;
  private string $amountColumn;

  private int $tahun;
  private string $kd_wilayah;
  private string $kd_opd;

  private int $peraturan; // 🔥 peraturan DI-INJECT lewat constructor

  private AnggaranParserService $parser;

  /* ============================================================
       CONSTRUCTOR
       ------------------------------------------------------------
       $peraturan WAJIB diberikan saat instansiasi class.
    ============================================================ */

  public function __construct(
    string $table,
    string $amountColumn,
    int $peraturan
  ) {
    if (
      !isset($_SESSION['tahun']) ||
      !isset($_SESSION['kd_wilayah']) ||
      !isset($_SESSION['kd_opd'])
    ) {
      throw new Exception("Session anggaran tidak lengkap.");
    }

    $this->db = DB::getInstance();
    $this->table = $table;
    $this->amountColumn = $amountColumn;
    $this->peraturan = $peraturan;

    $this->tahun      = $_SESSION['tahun'];
    $this->kd_wilayah = $_SESSION['kd_wilayah'];
    $this->kd_opd     = $_SESSION['kd_opd'];

    $this->parser = new AnggaranParserService();
  }

  /* ============================================================
       CREATE
    ============================================================ */

  public function create(array $data): int
  {
    $this->db->query("START TRANSACTION");

    try {

      // 1️⃣ Pastikan hierarki sub kegiatan ada
      if (!empty($data['kd_sub_keg'])) {
        $this->ensureHierarchy('kd_sub_keg', $data['kd_sub_keg']);
      }

      // 2️⃣ Pastikan hierarki akun ada (jika ada kd_akun)
      if (!empty($data['kd_akun'])) {
        $this->ensureHierarchy(
          'kd_akun',
          $data['kd_akun'],
          $data['kd_sub_keg']
        );
      }

      // 3️⃣ Inject identity session
      $data['tahun']      = $this->tahun;
      $data['kd_wilayah'] = $this->kd_wilayah;
      $data['kd_opd']     = $this->kd_opd;

      // 4️⃣ Insert row detail
      $id = $this->db->insert($this->table, $data);

      // 5️⃣ Tambah parent dengan delta (incremental engine)
      $this->updateParentByDelta(
        $data['kd_sub_keg'],
        $data['kd_akun'] ?? null,
        (float)$data[$this->amountColumn]
      );

      $this->db->query("COMMIT");

      return $id;
    } catch (Exception $e) {
      $this->db->query("ROLLBACK");
      throw $e;
    }
  }

  /* ============================================================
       UPDATE
    ============================================================ */

  public function update(int $id, array $data): int
  {
    $this->db->query("START TRANSACTION");

    try {

      // Ambil data lama untuk hitung delta
      $old = $this->db->first(
        $this->table,
        "WHERE id=? AND tahun=? AND kd_wilayah=? AND kd_opd=?",
        [$id, $this->tahun, $this->kd_wilayah, $this->kd_opd]
      );

      $oldAmount = (float)$old[$this->amountColumn];
      $newAmount = (float)$data[$this->amountColumn];
      $delta = $newAmount - $oldAmount;

      unset($data['tahun'], $data['kd_wilayah'], $data['kd_opd']);

      $this->db->update(
        $this->table,
        $data,
        "WHERE id=? AND tahun=? AND kd_wilayah=? AND kd_opd=?",
        [$id, $this->tahun, $this->kd_wilayah, $this->kd_opd]
      );

      // Update parent sesuai delta
      $this->updateParentByDelta(
        $old['kd_sub_keg'],
        $old['kd_akun'] ?? null,
        $delta
      );

      $this->db->query("COMMIT");

      return 1;
    } catch (Exception $e) {
      $this->db->query("ROLLBACK");
      throw $e;
    }
  }

  /* ============================================================
       DELETE SINGLE ROW
    ============================================================ */

  public function delete(int $id): int
  {
    $this->db->query("START TRANSACTION");

    try {

      $row = $this->db->first(
        $this->table,
        "WHERE id=? AND tahun=? AND kd_wilayah=? AND kd_opd=?",
        [$id, $this->tahun, $this->kd_wilayah, $this->kd_opd]
      );

      $this->db->delete(
        $this->table,
        "WHERE id=? AND tahun=? AND kd_wilayah=? AND kd_opd=?",
        [$id, $this->tahun, $this->kd_wilayah, $this->kd_opd]
      );

      // Kurangi parent
      $this->updateParentByDelta(
        $row['kd_sub_keg'],
        $row['kd_akun'] ?? null,
        -(float)$row[$this->amountColumn]
      );

      $this->db->query("COMMIT");

      return 1;
    } catch (Exception $e) {
      $this->db->query("ROLLBACK");
      throw $e;
    }
  }

  /* ============================================================
       ENSURE HIERARCHY
       ------------------------------------------------------------
       Membuat parent jika belum ada
       dan mengisi uraian dari master berdasarkan PERATURAN
    ============================================================ */

  private function ensureHierarchy(string $field, string $kode, ?string $kd_sub_keg = null)
  {
    $levels = $this->parser->buildHierarchy($kode);

    foreach ($levels as $level) {

      $where = "tahun=? AND kd_wilayah=? AND kd_opd=?";
      $params = [$this->tahun, $this->kd_wilayah, $this->kd_opd];

      if ($field === 'kd_sub_keg') {
        $where .= " AND kd_sub_keg=?";
        $params[] = $level;
      }

      if ($field === 'kd_akun') {
        $where .= " AND kd_sub_keg=? AND kd_akun=?";
        $params[] = $kd_sub_keg;
        $params[] = $level;
      }

      $exists = $this->db->first($this->table, "WHERE $where", $params);

      if (!$exists) {

        // 🔥 Ambil uraian master berdasarkan peraturan
        $uraian = $this->getMasterUraian($field, $level);

        $insert = [
          'tahun'      => $this->tahun,
          'kd_wilayah' => $this->kd_wilayah,
          'kd_opd'     => $this->kd_opd,
          'uraian'     => $uraian,
          $this->amountColumn => 0
        ];

        if ($field === 'kd_sub_keg') {
          $insert['kd_sub_keg'] = $level;
        }

        if ($field === 'kd_akun') {
          $insert['kd_sub_keg'] = $kd_sub_keg;
          $insert['kd_akun']    = $level;
        }

        $this->db->insert($this->table, $insert);
      }
    }
  }

  /* ============================================================
       GET MASTER URAIAN BERDASARKAN PERATURAN
    ============================================================ */

  /* ============================================================
   GET MASTER URAIAN (STRICT + CACHE)
   ============================================================ */

  private array $masterCache = [];

  /**
   * Ambil uraian master berdasarkan peraturan
   * + strict validation
   * + caching
   */
  private function getMasterUraian(string $field, string $kode): string
  {
    // 🔹 Inisialisasi cache array jika belum ada
    if (!isset($this->masterCache[$field])) {
      $this->masterCache[$field] = [];
    }

    // 🔹 Jika sudah pernah diambil, gunakan cache
    if (isset($this->masterCache[$field][$kode])) {
      return $this->masterCache[$field][$kode];
    }

    // 🔹 Query ke tabel master
    if ($field === 'kd_sub_keg') {

      $row = $this->db->first(
        'sub_kegiatan_neo',
        "WHERE kode=? AND peraturan=?",
        [$kode, $this->peraturan]
      );
    } elseif ($field === 'kd_akun') {

      $row = $this->db->first(
        'akun_neo',
        "WHERE kode=? AND peraturan=?",
        [$kode, $this->peraturan]
      );
    } else {
      throw new Exception("Field master tidak valid.");
    }

    // 🔴 STRICT VALIDATION
    if (!$row) {
      throw new Exception(
        "Kode '{$kode}' tidak ditemukan di master {$field} untuk peraturan {$this->peraturan}"
      );
    }

    // 🔹 Simpan ke cache
    $this->masterCache[$field][$kode] = $row['uraian'];

    return $row['uraian'];
  }

  /* ============================================================
       UPDATE PARENT BY DELTA
       ------------------------------------------------------------
       Core incremental engine
    ============================================================ */

  private function updateParentByDelta(
    string $kd_sub_keg,
    ?string $kd_akun,
    float $delta
  ) {
    // Update hierarchy kegiatan
    $subLevels = $this->parser->buildHierarchy($kd_sub_keg);

    foreach ($subLevels as $level) {

      $this->db->query(
        "UPDATE {$this->table}
                 SET {$this->amountColumn} = {$this->amountColumn} + ?
                 WHERE tahun=? 
                 AND kd_wilayah=? 
                 AND kd_opd=? 
                 AND kd_sub_keg=? 
                 AND kd_akun IS NULL",
        [
          $delta,
          $this->tahun,
          $this->kd_wilayah,
          $this->kd_opd,
          $level
        ]
      );
    }

    // Update hierarchy rekening
    if ($kd_akun) {

      $akunLevels = $this->parser->buildHierarchy($kd_akun);

      foreach ($akunLevels as $level) {

        $this->db->query(
          "UPDATE {$this->table}
                     SET {$this->amountColumn} = {$this->amountColumn} + ?
                     WHERE tahun=? 
                     AND kd_wilayah=? 
                     AND kd_opd=? 
                     AND kd_sub_keg=? 
                     AND kd_akun=?",
          [
            $delta,
            $this->tahun,
            $this->kd_wilayah,
            $this->kd_opd,
            $kd_sub_keg,
            $level
          ]
        );
      }
    }
  }
}
