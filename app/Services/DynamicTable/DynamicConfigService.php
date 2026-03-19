<?php

namespace App\Services\DynamicTable;

class DynamicConfigService
{
  private $db; // //
  private array $user; // //
  private ?array $pengaturanAktifCache = null; // //
  private ?array $periodeAktifCache = null; // //

  public function __construct(array $user) // //
  {
    $this->db = \DB::getInstance(); // //
    $this->user = $user; // //
  }

  public function getPengaturanAktif(): ?array // //
  {
    if ($this->pengaturanAktifCache !== null) {
      return $this->pengaturanAktifCache; // //
    }

    $kd_wilayah = $this->user['kd_wilayah'] ?? null; // //
    $tahun      = $this->user['tahun'] ?? null; // //

    if (!$kd_wilayah || !$tahun) {
      return null; // //
    }

    $result = $this->db->query("
      SELECT *
      FROM pengaturan_neo
      WHERE kd_wilayah = ?
      AND tahun = ?
      AND disable = 0
      LIMIT 1
      ", [$kd_wilayah, $tahun])->fetch(); // //

    $this->pengaturanAktifCache = $result ?: null; // //

    return $this->pengaturanAktifCache; // //
  }

  public function getPeriodeAktif(): ?array // //
  {
    if ($this->periodeAktifCache !== null) {
      return $this->periodeAktifCache; // //
    }

    $kd_wilayah = $this->user['kd_wilayah'] ?? null; // //
    $tahun      = $this->user['tahun'] ?? null; // //

    if (!$kd_wilayah || !$tahun) {
      return null; // //
    }

    $result = $this->db->query("
      SELECT *
      FROM periode
      WHERE kd_wilayah = ?
      AND tahun = ?
      AND is_active = 1
      LIMIT 1
      ", [$kd_wilayah, $tahun])->fetch(); // //

    $this->periodeAktifCache = $result ?: null; // //

    return $this->periodeAktifCache; // //
  }
}
