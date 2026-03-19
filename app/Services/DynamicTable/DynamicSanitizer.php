<?php

namespace App\Services\DynamicTable;

class DynamicSanitizer
{
  private $service; // //

  public function __construct($service) // //
  {
    $this->service = $service; // //
  }

  public function applySanitization(string $table, array $data): array // //
  {
    $profile = $this->service->getProfileByTable($table); // //

    foreach ($data as $field => $value) {

      if (!is_string($value)) {
        continue;
      }

      $rules = $profile['sanitize'][$field] ?? null; // //

      $data[$field] = $this->sanitizeValue($value, $rules); // //
    }

    return $data; // //
  }

  public function sanitizeValue(?string $value, ?array $rules = null): string // //
  {
    if ($value === null) {
      return ''; // //
    }

    $value = trim((string)$value); // //

    // Hapus control characters
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value); // //

    // Normalize multi space
    $value = $this->normalizeSpaces($value); // //

    // Strip HTML
    $value = strip_tags($value); // //

    if (!empty($rules['case'])) {
      switch ($rules['case']) {
        case 'upper':
          $value = mb_strtoupper($value);
          break;
        case 'lower':
          $value = mb_strtolower($value);
          break;
        case 'title':
          $value = mb_convert_case($value, MB_CASE_TITLE);
          break;
      }
    }

    return $value; // //
  }

  public function normalizeSpaces(string $value): string // //
  {
    $value = trim((string)$value); // //
    return preg_replace('/\s+/u', ' ', $value); // //
  }
}
