<?php

namespace App\Services\DynamicTable;

class DynamicResolver
{
  private DynamicConfigService $config; // //

  public function __construct(DynamicConfigService $config) // //
  {
    $this->config = $config; // //
  }

  public function resolvePeriode(array $data): array // //
  {
    $periode = $this->config->getPeriodeAktif(); // //

    if ($periode && !isset($data['periode_id'])) {
      $data['periode_id'] = $periode['id']; // //
    }

    return $data; // //
  }

  public function resolvePeraturan(array $data): array // //
  {
    $pengaturan = $this->config->getPengaturanAktif(); // //

    if ($pengaturan && !isset($data['peraturan_id'])) {
      $data['peraturan_id'] = $pengaturan['id']; // //
    }

    return $data; // //
  }
}