<?php

namespace App\Services\DynamicTable;

class DynamicMetadataService
{
  private $db; // //

  public function __construct() // //
  {
    $this->db = \DB::getInstance(); // //
  }

  public function getTableColumns(string $table): array // //
  {
    return $this->db->getColumnListing($table); // //
  }

  public function getPrimaryKey(string $table): string // //
  {
    return $this->db->getPrimaryKey($table); // //
  }

  public function tableExists(string $table): bool // //
  {
    return $this->db->tableExists($table); // //
  }

  public function getProfileByTable(string $table): array // //
  {
    return []; // //
  }
}