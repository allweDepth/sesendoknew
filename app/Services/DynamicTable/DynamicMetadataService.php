<?php

namespace App\Services\DynamicTable;

class DynamicMetadataService
{
  private $db; // //

  public function __construct() // //
  {
    $this->db = \DB::getInstance(); // //
  }

  public function getTableColumns(string $table): array // //
  {
    return $this->db->getColumnListing($table); // //
  }

  public function getPrimaryKey(string $table): string // //
  {
    return $this->db->getPrimaryKey($table); // //
  }

  public function tableExists(string $table): bool // //
  {
    return $this->db->tableExists($table); // //
  }

  public function getProfileByTable(string $table): array // //
  {
    return []; // //
  }
  public function buildWhere(array $conditions, array $columns, string $table, callable $resolver): array
  {
    $parts = [];
    $params = [];

    foreach ($conditions as $key => $value) {

      if ($key === 'AND' && is_array($value)) {

        $subParts = [];
        $subParams = [];

        foreach ($value as $cond) {
          list($sql, $bind) = $this->buildWhere($cond, $columns, $table, $resolver);

          if ($sql) {
            $subParts[] = $sql;
            $subParams = array_merge($subParams, $bind);
          }
        }

        if ($subParts) {
          $parts[] = '(' . implode(' AND ', $subParts) . ')';
          $params = array_merge($params, $subParams);
        }
      } elseif ($key === 'OR' && is_array($value)) {

        $subParts = [];
        $subParams = [];

        foreach ($value as $cond) {
          list($sql, $bind) = $this->buildWhere($cond, $columns, $table, $resolver);

          if ($sql) {
            $subParts[] = $sql;
            $subParams = array_merge($subParams, $bind);
          }
        }

        if ($subParts) {
          $parts[] = '(' . implode(' OR ', $subParts) . ')';
          $params = array_merge($params, $subParams);
        }
      } elseif (strpos($key, ' ') !== false) {

        list($field, $op) = explode(' ', $key, 2);

        if (!in_array($field, $columns)) continue;

        $parts[] = "`$field` $op ?";
        $params[] = $resolver($field, $value, $table);
      } else {

        if (!in_array($key, $columns)) continue;

        $parts[] = "`$key` = ?";
        $params[] = $resolver($key, $value, $table);
      }
    }

    return [implode(' AND ', $parts), $params];
  }
}