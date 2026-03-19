<?php

namespace App\Services\DynamicTable;

class DynamicDoc
{
  public function apply(array $rows, array $alias): array // //
  {
    if (empty($alias)) { // //
      return $rows; // //
    }

    foreach ($rows as &$row) { // //

      foreach ($alias as $old => $new) { // //

        if (array_key_exists($old, $row)) { // //

          $row[$new] = $row[$old]; // copy value //
          unset($row[$old]); // remove original //

        }
      }
    }

    return $rows; // //
  }
}