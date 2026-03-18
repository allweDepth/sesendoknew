<?php

namespace App\Services\DynamicTable;

class DynamicValidationService
{
  public function validate(array $data, string $table): array // //
  {
    // sesuai file: validate dipanggil tapi tidak standalone rule engine penuh // //
    return $data; // //
  }

  public function validateDuplicate(array $data, string $table): void // //
  {
    // dipanggil di insert/update/import // //
  }

  public function validateHierarchy(array $data, string $table): void // //
  {
    // dipanggil di insert/update // //
  }

  public function validateAkunMapping(array $data): void // //
  {
    // dipanggil di insert // //
  }
}