<?php

require_once __DIR__ . '/../Core/DB.php';

class PublicModel
{
  private $db;

  public function __construct()
  {
    $this->db = DB::getInstance();
  }

  public function wilayah($req = [])
  {
    $stmt = $this->db->query(
      "SELECT kode, uraian 
         FROM wilayah_neo 
         WHERE is_deleted = 0 
         ORDER BY uraian ASC"
    );

    return $stmt->fetchAll();
  }

  public function organisasi($req = [])
  {
    $kd = $req['kd_wilayah'] ?? '';

    $stmt = $this->db->query(
      "SELECT kode, uraian 
         FROM organisasi_neo 
         WHERE kd_wilayah = ? AND is_deleted = 0
         ORDER BY uraian ASC",
      [$kd]
    );

    return $stmt->fetchAll();
  }
}
