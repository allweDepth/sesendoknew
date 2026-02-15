<?php

require_once __DIR__ . '/JsonResponse.php';

class DynamicTableService
{
  private DB $db;
  private array $profiles;

  public function __construct()
  {
    $this->db = DB::getInstance();
    $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
  }

  public function handle(array $request): string
  {
    $tbl   = $request['tbl']   ?? '';
    $jenis = $request['jenis'] ?? 'default';

    if (!$tbl) {
      return JsonResponse::error('Tabel tidak dikirim');
    }

    if (!isset($this->profiles[$tbl])) {
      return JsonResponse::error('Tabel tidak terdaftar');
    }

    $profile = $this->profiles[$tbl];
    $table   = $profile['table'];

    $mode = $profile['modes'][$jenis]
      ?? $profile['modes']['default']
      ?? null;

    if (!$mode) {
      return JsonResponse::error('Mode tidak tersedia');
    }

    return $this->buildQuery($table, $profile, $mode, $request);
  }

  private function buildQuery(
    string $table,
    array $profile,
    array $mode,
    array $request
  ): string {

    $limit  = max(1, (int)($request['rows'] ?? 10));
    $page   = max(1, (int)($request['halaman'] ?? 1));
    $search = trim($request['cari'] ?? '');

    $offset = ($page - 1) * $limit;

    // SELECT
    $select = '*';
    if (!empty($mode['select'])) {
      $select = implode(',', $mode['select']);
    }

    // WHERE
    $whereParts = [];
    $params = [];

    // where dari config
    if (!empty($mode['where'])) {
      $whereParts[] = $mode['where'];
    }

    // search dynamic
    if ($search !== '' && !empty($mode['searchable'])) {
      $searchParts = [];

      foreach ($mode['searchable'] as $field) {
        $searchParts[] = "$field LIKE ?";
        $params[] = "%$search%";
      }

      $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
    }

    $where = '';
    if (!empty($whereParts)) {
      $where = 'WHERE ' . implode(' AND ', $whereParts);
    }

    // ORDER
    $order = '';
    if (!empty($mode['order_by'])) {
      $order = 'ORDER BY ' . $mode['order_by'];
    }

    // TOTAL
    $totalQuery = "SELECT COUNT(*) as total FROM `$table` $where";
    $stmt = $this->db->query($totalQuery, $params);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRow = $row['total'] ?? 0;

    // DATA
    $dataQuery = "
            SELECT $select
            FROM `$table`
            $where
            $order
            LIMIT $offset, $limit
        ";

    $stmt = $this->db->query($dataQuery, $params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return JsonResponse::success(
      'Data berhasil',
      [
        'total' => (int)$totalRow,
        'page'  => $page,
        'limit' => $limit
      ],
      $rows
    );
  }
}
