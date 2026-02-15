<?php

class DynamicTableService
{
    private DB $db;
    private array $profiles;

    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->profiles = require __DIR__ . '/../../config/table_profiles.php';
    }

    public function handle(array $request): string
    {
        $jenis = $request['jenis'] ?? '';
        $table = $request['tbl'] ?? '';

        if (!$jenis || !$table) {
            return JsonResponse::error('Parameter tidak lengkap');
        }

        if (!isset($this->profiles[$table])) {
            return JsonResponse::error('Tabel tidak terdaftar');
        }

        return match ($jenis) {
            'get_tbl'       => $this->getTable($table, $request),
            'get_row'       => $this->getRow($table, $request),
            'get_row_json'  => $this->getDropdown($table, $request),
            'get_data'      => $this->getDataByCondition($table, $request),
            default         => JsonResponse::error('Jenis tidak dikenali'),
        };
    }

    private function getTable(string $table, array $request): string
    {
        $limit  = max(1, (int)($request['rows'] ?? 10));
        $page   = max(1, (int)($request['halaman'] ?? 1));
        $search = trim($request['cari'] ?? '');

        $profile = $this->profiles[$table]['get_tbl'] ?? null;

        if (!$profile) {
            return JsonResponse::error('Profile get_tbl tidak tersedia');
        }

        $offset = ($page - 1) * $limit;

        $where  = '';
        $params = [];

        if ($search && isset($profile['search'])) {
            $where  = "WHERE {$profile['search']}";
            $params = array_fill(
                0,
                substr_count($profile['search'], '?'),
                "%$search%"
            );
        }

        $order = $profile['order'] ?? '';
        $clause = trim("$where $order LIMIT $offset, $limit");

        $rows = $this->db->select($table, '*', $clause, $params);

        $totalRow = $this->db
            ->select($table, 'COUNT(*) as total')[0]['total'] ?? 0;

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

    private function getRow(string $table, array $request): string
    {
        $id = (int)($request['id_row'] ?? 0);

        if ($id <= 0) {
            return JsonResponse::error('ID tidak valid');
        }

        $row = $this->db->first($table, "WHERE id = ?", [$id]);

        return $row
            ? JsonResponse::success('Data ditemukan', null, $row)
            : JsonResponse::error('Data tidak ditemukan');
    }

    private function getDropdown(string $table, array $request): string
    {
        $search = trim($request['cari'] ?? '');

        $profile = $this->profiles[$table]['dropdown'] ?? null;

        if (!$profile) {
            return JsonResponse::error('Profile dropdown tidak tersedia');
        }

        $where  = '';
        $params = [];

        if ($search && isset($profile['search'])) {
            $where  = "WHERE {$profile['search']}";
            $params = ["%$search%"];
        }

        $rows = $this->db->select($table, '*', $where, $params);

        $results = array_map(function ($row) use ($profile) {
            return [
                'name'  => $row[$profile['text']] ?? '',
                'value' => $row[$profile['value']] ?? ''
            ];
        }, $rows);

        return JsonResponse::success('Dropdown loaded', null, $results);
    }

    private function getDataByCondition(string $table, array $request): string
    {
        $profile = $this->profiles[$table]['fields'] ?? null;

        $field = $request['field'] ?? '';
        $value = $request['value'] ?? '';

        if (!$field || !$value) {
            return JsonResponse::error('Parameter tidak lengkap');
        }

        if ($profile && !in_array($field, $profile, true)) {
            return JsonResponse::error('Field tidak diizinkan');
        }

        $rows = $this->db->get($table, "WHERE $field = ?", [$value]);

        return JsonResponse::success('Data ditemukan', null, $rows);
    }
    // klik menu referensi 
    
}
