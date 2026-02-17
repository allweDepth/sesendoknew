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
    if ($jenis === 'add') {
      return $this->insert($table, $profile, $request);
    }

    if ($jenis === 'edit' && !empty($request['id'])) {
      return $this->update($table, $profile, $request);
    }

    if ($jenis === 'delete' && !empty($request['id_row'])) {
      return $this->delete($table, $profile, $request['id_row']);
    }
    return $this->buildQuery($table, $profile, $mode, $request);
  }
  private function insert(string $table, array $profile, array $request): string
  {
    $primaryKey = $profile['primary_key'] ?? 'id';

    $fields = [];
    $values = [];
    $params = [];

    foreach ($request as $key => $value) {

      if ($key === 'jenis' || $key === 'tbl') continue;
      if ($key === $primaryKey) continue;

      $fields[] = "`$key`";
      $values[] = "?";
      $params[] = $value;
    }

    if (empty($fields)) {
      return JsonResponse::error('Tidak ada data dikirim');
    }

    $query = "
        INSERT INTO `$table`
        (" . implode(',', $fields) . ")
        VALUES (" . implode(',', $values) . ")
    ";

    $this->db->query($query, $params);

    return JsonResponse::success('Data berhasil disimpan');
  }
  private function update(string $table, array $profile, array $request): string
  {
    $primaryKey = $profile['primary_key'] ?? 'id';
    $id = (int)$request['id'];

    if (!$id) {
      return JsonResponse::error('ID tidak valid');
    }

    $sets = [];
    $params = [];

    foreach ($request as $key => $value) {

      if (in_array($key, ['jenis', 'tbl', 'id'])) continue;

      $sets[] = "`$key` = ?";
      $params[] = $value;
    }

    if (empty($sets)) {
      return JsonResponse::error('Tidak ada data diubah');
    }

    $params[] = $id;

    $query = "
        UPDATE `$table`
        SET " . implode(',', $sets) . "
        WHERE `$primaryKey` = ?
    ";

    $this->db->query($query, $params);

    return JsonResponse::success('Data berhasil diperbarui');
  }
  private function delete(string $table, array $profile, int $id): string
  {
    $primaryKey = $profile['primary_key'] ?? 'id';

    $query = "
        DELETE FROM `$table`
        WHERE `$primaryKey` = ?
    ";

    $this->db->query($query, [$id]);

    return JsonResponse::success('Data berhasil dihapus');
  }
  private function buildQuery(
    string $table,
    array $profile,
    array $mode,
    array $request
  ): string {

    $idRow = (int)($request['id_row'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | MODE EDIT
    |--------------------------------------------------------------------------
    */
    if (($request['jenis'] ?? '') === 'edit' && $idRow > 0) {

      $primaryKey = $profile['primary_key'] ?? 'id';

      $select = '*';
      if (!empty($mode['select'])) {
        $select = implode(',', $mode['select']);
      }

      $query = "
        SELECT $select
        FROM `$table`
        WHERE `$primaryKey` = ?
        LIMIT 1
      ";

      $stmt = $this->db->query($query, [$idRow]);
      $row  = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row) {
        return JsonResponse::error('Data tidak ditemukan');
      }

      return JsonResponse::success(
        'Data berhasil diambil',
        [
          'mode' => 'edit',
          'id'   => $idRow
        ],
        $row
      );
    }

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
    $params     = [];

    /*
    |--------------------------------------------------------------------------
    | WHERE DARI CONFIG
    |--------------------------------------------------------------------------
    */
    if (!empty($mode['where'])) {

      // jika string (backward compatible)
      if (is_string($mode['where'])) {
        $whereParts[] = $mode['where'];
      }

      // jika array (field => source/value)
      if (is_array($mode['where'])) {

        foreach ($mode['where'] as $field => $source) {

          // ambil dari user login
          if ($source === 'user') {

            $value = $_SESSION['user'][$field] ?? null;

            if ($value !== null && $value !== '') {
              $whereParts[] = "`$field` = ?";
              $params[]     = $value;
            }
          }

          // static value
          else {
            $whereParts[] = "`$field` = ?";
            $params[]     = $source;
          }
        }
      }
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */
    if ($search !== '' && !empty($mode['searchable'])) {

      $searchParts = [];

      foreach ($mode['searchable'] as $field) {
        $searchParts[] = "`$field` LIKE ?";
        $params[] = "%$search%";
      }

      $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
    }

    $where = '';
    if (!empty($whereParts)) {
      $where = 'WHERE ' . implode(' AND ', $whereParts);
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */
    $order = '';
    if (!empty($mode['order_by'])) {
      $order = 'ORDER BY ' . $mode['order_by'];
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL
    |--------------------------------------------------------------------------
    */
    $totalQuery = "SELECT COUNT(*) as total FROM `$table` $where";
    $stmt = $this->db->query($totalQuery, $params);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRow = $row['total'] ?? 0;

    /*
    |--------------------------------------------------------------------------
    | DATA
    |--------------------------------------------------------------------------
    */
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
