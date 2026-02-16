<?php

require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/AnggaranParserService.php';

/**
 * ============================================================
 * ANGARAN CRUD SERVICE (FINAL - INCREMENTAL ENGINE)
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
 *
 * Prinsip:
 * ------------------------------------------------------------
 * Parent tidak dihitung ulang dengan SUM,
 * tetapi ditambah / dikurangi berdasarkan delta.
 *
 * Lebih cepat dan scalable.
 *
 * ============================================================
 */

class AnggaranCrudService
{
  private DB $db;
  private string $table;
  private string $amountColumn;

  private int $tahun;
  private string $kd_wilayah;
  private string $kd_opd;

  private AnggaranParserService $parser;

  public function __construct(string $table, string $amountColumn = 'jumlah')
  {
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

      // 1️⃣ Ensure hierarchy kegiatan
      if (!empty($data['kd_sub_keg'])) {
        $this->ensureHierarchy('kd_sub_keg', $data['kd_sub_keg']);
      }

      // 2️⃣ Ensure hierarchy rekening
      if (!empty($data['kd_akun'])) {
        $this->ensureHierarchy(
          'kd_akun',
          $data['kd_akun'],
          $data['kd_sub_keg']
        );
      }

      // 3️⃣ Inject session identity
      $data['tahun']      = $this->tahun;
      $data['kd_wilayah'] = $this->kd_wilayah;
      $data['kd_opd']     = $this->kd_opd;

      // 4️⃣ Insert row
      $id = $this->db->insert($this->table, $data);

      // 5️⃣ Update parent with delta
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

      // Update parent by delta
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
       DELETE HIERARCHY (CASCADE)
    ============================================================ */

  public function deleteHierarchy(string $field, string $prefix): int
  {
    if (!in_array($field, ['kd_sub_keg', 'kd_akun'])) {
      throw new Exception("Field tidak valid.");
    }

    $this->db->query("START TRANSACTION");

    try {

      $sample = $this->db->first(
        $this->table,
        "WHERE tahun=? AND kd_wilayah=? AND kd_opd=? 
                 AND {$field} LIKE ?",
        [
          $this->tahun,
          $this->kd_wilayah,
          $this->kd_opd,
          $prefix . '%'
        ]
      );

      if (!$sample) {
        $this->db->query("ROLLBACK");
        return 0;
      }

      // Ambil total subtree sekali saja
      $totalRow = $this->db->query(
        "SELECT SUM({$this->amountColumn}) as total
                 FROM {$this->table}
                 WHERE tahun=? AND kd_wilayah=? AND kd_opd=? 
                 AND {$field} LIKE ?",
        [
          $this->tahun,
          $this->kd_wilayah,
          $this->kd_opd,
          $prefix . '%'
        ]
      )->fetch();

      $delta = -(float)($totalRow['total'] ?? 0);

      // Hapus subtree
      $deleted = $this->db->delete(
        $this->table,
        "WHERE tahun=? AND kd_wilayah=? AND kd_opd=? 
                 AND {$field} LIKE ?",
        [
          $this->tahun,
          $this->kd_wilayah,
          $this->kd_opd,
          $prefix . '%'
        ]
      );

      // Update parent
      $this->updateParentByDelta(
        $sample['kd_sub_keg'],
        $sample['kd_akun'] ?? null,
        $delta
      );

      $this->db->query("COMMIT");

      return $deleted;
    } catch (Exception $e) {

      $this->db->query("ROLLBACK");
      throw $e;
    }
  }

  /* ============================================================
       ENSURE HIERARCHY
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

        $insert = [
          'tahun'      => $this->tahun,
          'kd_wilayah' => $this->kd_wilayah,
          'kd_opd'     => $this->kd_opd,
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
       UPDATE PARENT BY DELTA (CORE ENGINE)
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

    // Update rekening jika ada
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
