<?php

namespace App\Services\DynamicTable;

class DynamicSanitizer
{
  private DynamicMetadataService $meta; // //

  public function __construct(DynamicMetadataService $meta) // //
  {
    $this->meta = $meta; // //
  }

  public function applySanitization(string $table, array $data): array // //
  {
    $profile = $this->meta->getProfileByTable($table); // //

    foreach ($data as $field => $value) {

      if (!is_string($value)) continue;

      $rules = $profile['sanitize'][$field] ?? null; // //

      $data[$field] = $this->sanitizeValue($value, $rules); // //
    }

    return $data; // //
  }

  public function sanitizeValue(string $value, $rules): string // //
  {
    $value = $this->normalizeSpaces($value); // //

    return $value; // //
  }

  public function normalizeSpaces(string $value): string // //
  {
    return preg_replace('/\s+/', ' ', trim($value)); // //
  }
}