<?php

require_once __DIR__ . '/../Core/DB.php';

/**
 * ============================================================
 * ANGARAN HIERARCHY SERVICE
 * ============================================================
 *
 * Tugas:
 * ------------------------------------------------------------
 * - Menghitung total berdasarkan prefix
 * - Digunakan untuk recalculation parent
 *
 * Service ini hanya melakukan SUM.
 * Tidak melakukan CRUD.
 *
 * ============================================================
 */

class AnggaranHierarchyService
{
  private DB $db;
  private string $table;
  private string $amountColumn;

  private int $tahun;
  private string $kd_wilayah;
  private string $kd_opd;

  /**
   * Constructor
   */
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
  }

  /**
   * Hitung total berdasarkan prefix field tertentu
   *
   * @param string $field (kd_sub_keg atau kd_akun)
   * @param string $prefix
   * @return float
   */
  public function sumByFieldPrefix(string $field, string $prefix): float
  {
    $sql = "
            SELECT SUM({$this->amountColumn}) as total
            FROM {$this->table}
            WHERE tahun=? 
            AND kd_wilayah=? 
            AND kd_opd=? 
            AND {$field} LIKE ?
        ";

    $result = $this->db->query($sql, [
      $this->tahun,
      $this->kd_wilayah,
      $this->kd_opd,
      $prefix . '%'
    ])->fetch();

    return (float) ($result['total'] ?? 0);
  }
}
