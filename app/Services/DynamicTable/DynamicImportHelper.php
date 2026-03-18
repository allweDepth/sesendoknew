<?php

namespace App\Services\DynamicTable;

class DynamicImportHelper
{
  public function normalizeHeader(?string $header): string // //
  {
    if ($header === null) return ''; // //

    $header = trim($header); // //

    if ($header === '') return ''; // //

    return strtolower(preg_replace('/[^a-z0-9_]/', '', $header)); // //
  }

  public function normalizeForCompare(?string $value): string // //
  {
    if (!is_string($value) || $value === '') return ''; // //

    $value = strtolower($value); // //

    return preg_replace('/[^a-z0-9]/', '', $value); // //
  }

  public function compressRowRanges(array $rows): array // //
  {
    sort($rows); // //

    $ranges = []; // //
    $start = null; // //
    $prev  = null; // //

    foreach ($rows as $row) {

      if ($start === null) {
        $start = $row;
        $prev  = $row;
        continue;
      }

      if ($row == $prev + 1) {
        $prev = $row;
        continue;
      }

      $ranges[] = ($start == $prev)
        ? (string)$start
        : $start . '-' . $prev; // //

      $start = $row;
      $prev  = $row;
    }

    if ($start !== null) {
      $ranges[] = ($start == $prev)
        ? (string)$start
        : $start . '-' . $prev; // //
    }

    return $ranges; // //
  }

  public function groupImportErrors(array $errors): array // //
  {
    $grouped = []; // //

    foreach ($errors as $err) {

      $msg = $err['message']; // //

      if (!isset($grouped[$msg])) {
        $grouped[$msg] = [
          'message' => $msg,
          'rows' => []
        ];
      }

      $grouped[$msg]['rows'][] = $err['row']; // //
    }

    foreach ($grouped as &$g) {
      $g['rows'] = $this->compressRowRanges($g['rows']); // //
    }

    return array_values($grouped); // //
  }
}