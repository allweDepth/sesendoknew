<?php

namespace App\Services\DynamicTable;

class DynamicResolver
{
  private DynamicConfigService $config;
  private array $profiles;
  private $getColumns; // 🔥 closure dari service
  public function __construct(
    DynamicConfigService $config,
    array $profiles,
    callable $getColumns // 🔥
  ) {
    $this->config     = $config;
    $this->profiles   = $profiles;
    $this->getColumns = $getColumns;
  }

  // =======================================
  // RESOLVE PERIODE (TABLE AWARE)
  // =======================================
  public function resolvePeriode(string $table, array $data): array
  {
    $columns = $this->getTableColumns($table);

    if (!in_array('periode_id', $columns)) {
      return $data;
    }

    if (!empty($data['periode_id'])) {
      return $data;
    }

    $periode = $this->config->getPeriodeAktif();

    if (!$periode) {
      throw new \Exception("Periode aktif tidak ditemukan.");
    }

    $data['periode_id'] = $periode['id'];

    return $data;
  }

  // =======================================
  // RESOLVE PERATURAN (TABLE AWARE)
  // =======================================
  public function resolvePeraturan(string $table, array $data): array
  {
    $columns = $this->getTableColumns($table);

    if (!in_array('peraturan_id', $columns)) {
      return $data;
    }

    if (!empty($data['peraturan_id'])) {
      return $data;
    }

    $data['peraturan_id'] = $this->resolvePeraturanId($table);

    return $data;
  }

  // =======================================
  // CORE: PERATURAN ID (PINDAH KE SINI 🔥)
  // =======================================
  public function resolvePeraturanId(string $table): int
  {
    $pengaturan = $this->config->getPengaturanAktif();

    if (!$pengaturan) {
      throw new \Exception("Pengaturan aktif tidak ditemukan.");
    }

    // cari profileKey dari table
    $profileKey = null;

    foreach ($this->profiles as $key => $profile) {
      if (($profile['table'] ?? null) === $table) {
        $profileKey = $key;
        break;
      }
    }

    if ($profileKey === null) {
      throw new \Exception("Profile tidak ditemukan untuk table $table");
    }

    $map = [
      'urusan'       => 'aturan_sub_kegiatan',
      'bidang'       => 'aturan_sub_kegiatan',
      'program'      => 'aturan_sub_kegiatan',
      'kegiatan'     => 'aturan_sub_kegiatan',
      'sub_kegiatan' => 'aturan_sub_kegiatan',
      'rekening_kegiatan' => 'aturan_sub_kegiatan',
      'ssh'          => 'aturan_ssh',
      'sbu'          => 'aturan_sbu',
      'asb'          => 'aturan_asb',
      'hspk'         => 'aturan_hspk',
      'satuan'       => 'aturan_ssh'
    ];

    if (!isset($map[$profileKey])) {
      throw new \Exception("Mapping peraturan_id tidak ditemukan untuk $profileKey");
    }

    return (int)$pengaturan[$map[$profileKey]];
  }

  // =======================================
  // RESOLVE FIELD (WHERE ENGINE)
  // =======================================
  public function resolveField(string $field, $value, string $table, array $user = [])
  {
    if ($value === 'user') {

      if (isset($user[$field])) {
        return $user[$field];
      }

      if ($field === 'peraturan_id') {
        return $this->resolvePeraturanId($table);
      }

      if ($field === 'periode_id') {
        $periode = $this->config->getPeriodeAktif();
        return $periode['id'] ?? null;
      }

      return null;
    }

    return $value;
  }

  // =======================================
  // 🔥 helper (inject dari service nanti)
  // =======================================
  private function getTableColumns(string $table): array
  {
    return call_user_func($this->getColumns, $table);
  }
}