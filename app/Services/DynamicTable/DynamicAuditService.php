<?php

namespace App\Services\DynamicTable;

class DynamicAuditService
{
  public function injectAudit(array $data): array // //
  {
    $now = date('Y-m-d H:i:s'); // //

    if (!isset($data['created_at'])) {
      $data['created_at'] = $now; // //
    }

    $data['updated_at'] = $now; // //

    return $data; // //
  }

  public function logActivity(string $action, array $data): void // //
  {
    // sesuai file asli: tidak ada implementasi kompleks // //
  }
}