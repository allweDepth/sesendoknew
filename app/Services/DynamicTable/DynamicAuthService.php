<?php

namespace App\Services\DynamicTable;

class DynamicAuthService
{
  public function authorize(string $table, string $action): void // //
  {
    // sesuai file: hanya gate check // //
  }

  public function checkAccess(string $table, $id): bool // //
  {
    return true; // //
  }

  public function applyUserScope(string $table, $query) // //
  {
    return $query; // //
  }

  public function resolveScope(string $table): array // //
  {
    return []; // //
  }
}