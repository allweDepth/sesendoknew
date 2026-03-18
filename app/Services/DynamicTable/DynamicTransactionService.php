<?php

namespace App\Services\DynamicTable;

class DynamicTransactionService
{
  private $db; // //

  public function __construct() // //
  {
    $this->db = \DB::getInstance(); // //
  }

  public function runTransaction(callable $callback) // //
  {
    $this->db->beginTransaction(); // //

    try {
      $result = $callback(); // //
      $this->db->commit(); // //
      return $result; // //
    } catch (\Throwable $e) {
      $this->db->rollback(); // //
      throw $e; // //
    }
  }

  public function insertSafe(string $table, array $data) // //
  {
    return $this->db->table($table)->insert($data); // //
  }
}