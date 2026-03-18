<?php

namespace App\Services\DynamicTable;

class DynamicConfigService
{
  private $db; // //

  public function __construct() // //
  {
    $this->db = \DB::getInstance(); // //
  }

  public function getPengaturanAktif(): ?array // //
  {
    return $this->db->table('pengaturan')->where('is_active', 1)->first(); // //
  }

  public function getPeriodeAktif(): ?array // //
  {
    return $this->db->table('periode')->where('is_active', 1)->first(); // //
  }
}