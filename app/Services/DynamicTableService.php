<?php

require_once __DIR__ . '/JsonResponse.php';

/**
 * ============================================================
 * DYNAMIC TABLE SERVICE (MODE + ACTION AWARE)
 * ============================================================
 *
 * Konsep final:
 * ------------------------------------------------------------
 * - jenis = bisa berupa:
 *      ✔ add / edit / delete / dropdown  → dianggap AKSI
 *      ✔ selain itu → dianggap MODE tampilan
 *
 * - Mode akan membaca:
 *      $profile['modes'][$mode]
 *
 * - Jika mode tidak ada → fallback ke default
 *
 * ============================================================
 */

class DynamicTableService
{
    private DB $db;
    private array $profiles;
    private array $user;

    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
        $this->user = $_SESSION['user'] ?? [];
    }

    /* =========================================================
       MAIN HANDLER
    ========================================================= */

    public function handle(array $request): string
    {
        $jenis = $request['jenis'] ?? '';
        $tbl   = $request['tbl'] ?? '';

        /* =========================================
           1️⃣ DROPDOWN (tidak perlu tbl)
        ========================================= */
        if ($jenis === 'dropdown' && !empty($request['source'])) {
            return $this->loadDropdown(
                $request['source'],
                $request['parent_value'] ?? null
            );
        }

        /* =========================================
           2️⃣ VALIDASI TABEL
        ========================================= */
        if (!$tbl) {
            return JsonResponse::error('Tabel tidak dikirim');
        }

        if (!isset($this->profiles[$tbl])) {
            return JsonResponse::error('Tabel tidak terdaftar');
        }

        $profile = $this->profiles[$tbl];
        $table   = $profile['table'];

        /* =========================================
           3️⃣ AKSI CRUD
        ========================================= */
        if ($jenis === 'add') {
            return $this->insert($table, $request);
        }

        if ($jenis === 'edit' && !empty($request['id'])) {
            return $this->update($table, $request);
        }

        if ($jenis === 'delete' && !empty($request['id_row'])) {
            return $this->delete($table, $profile, (int)$request['id_row']);
        }

        /* =========================================
           4️⃣ SELAIN ITU = MODE TAMPILAN
        ========================================= */
        $mode = $jenis ?: 'default';

        return $this->buildQuery($table, $profile, $request, $mode);
    }

    /* =========================================================
       DROPDOWN ENGINE
    ========================================================= */

    private function loadDropdown(string $source, $parentValue = null): string
    {
        if (!isset($this->profiles[$source])) {
            return JsonResponse::error("Source tidak ditemukan");
        }

        $profile = $this->profiles[$source];
        $table   = $profile['table'];

        list($scopeWhere, $scopeParams) = $this->applyUserScope($table);

        $whereParts = $scopeWhere;
        $params     = $scopeParams;

        if ($parentValue !== null) {
            foreach ($this->getTableColumns($table) as $col) {
                if (str_starts_with($col, 'id_')) {
                    $whereParts[] = "`$col` = ?";
                    $params[] = $parentValue;
                    break;
                }
            }
        }

        $where = !empty($whereParts)
            ? "WHERE " . implode(" AND ", $whereParts)
            : "";

        $valueField = $profile['dropdown']['value'] ?? 'id';
        $labelField = $profile['dropdown']['label'] ?? 'uraian';

        $query = "
            SELECT `$valueField` as id, `$labelField` as uraian
            FROM `$table`
            $where
            ORDER BY `$valueField` ASC
        ";

        $rows = $this->db->query($query, $params)->fetchAll();

        return JsonResponse::success("Dropdown loaded", [], $rows);
    }

    /* =========================================================
       BUILD QUERY (MODE AWARE)
    ========================================================= */

    private function buildQuery(
        string $table,
        array $profile,
        array $request,
        string $mode
    ): string {

        $modeConfig = $profile['modes'][$mode]
            ?? $profile['modes']['default']
            ?? [];

        $limit  = max(1, (int)($request['rows'] ?? 10));
        $page   = max(1, (int)($request['halaman'] ?? 1));
        $search = trim($request['cari'] ?? '');
        $offset = ($page - 1) * $limit;

        $whereParts = [];
        $params     = [];

        /* =========================================
           USER SCOPE
        ========================================= */
        list($userWhere, $userParams) = $this->applyUserScope($table);

        $whereParts = array_merge($whereParts, $userWhere);
        $params     = array_merge($params, $userParams);

        /* =========================================
           CUSTOM WHERE MODE (jika ada)
        ========================================= */
        if (!empty($modeConfig['where'])) {

            if (is_array($modeConfig['where'])) {
                foreach ($modeConfig['where'] as $field => $source) {

                    if ($source === 'user') {
                        $value = $this->user[$field] ?? null;

                        if ($value !== null) {
                            $whereParts[] = "`$field` = ?";
                            $params[] = $value;
                        }
                    }
                }
            }

            if (is_string($modeConfig['where'])) {
                $whereParts[] = $modeConfig['where'];
            }
        }

        /* =========================================
           SEARCH MODE
        ========================================= */
        $searchable = $modeConfig['searchable'] ?? [];

        if ($search !== '' && !empty($searchable)) {

            $searchParts = [];

            foreach ($searchable as $field) {
                $searchParts[] = "`$field` LIKE ?";
                $params[] = "%$search%";
            }

            $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        $where = !empty($whereParts)
            ? 'WHERE ' . implode(' AND ', $whereParts)
            : '';

        /* =========================================
           TOTAL COUNT
        ========================================= */
        $totalQuery = "SELECT COUNT(*) as total FROM `$table` $where";
        $totalRow = $this->db->query($totalQuery, $params)->fetch()['total'] ?? 0;

        /* =========================================
           SELECT MODE
        ========================================= */
        $select = $modeConfig['select'] ?? ['*'];
        $selectClause = implode(',', $select);

        $orderBy = $modeConfig['order_by'] ?? 'id DESC';

        $dataQuery = "
            SELECT $selectClause
            FROM `$table`
            $where
            ORDER BY $orderBy
            LIMIT $offset, $limit
        ";

        $rows = $this->db->query($dataQuery, $params)->fetchAll();

        return JsonResponse::success(
            'Data berhasil ditampilkan',
            [
                'total' => (int)$totalRow,
                'page'  => $page,
                'limit' => $limit
            ],
            $rows
        );
    }

    /* =========================================================
       INSERT
    ========================================================= */

    private function insert(string $table, array $request): string
    {
        $columns = $this->getTableColumns($table);
        $filtered = [];

        foreach ($request as $key => $value) {
            if (in_array($key, ['jenis', 'tbl'])) continue;
            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa disimpan");
        }

        /* =====================================================
       KHUSUS PERIODE RPJMD
    ===================================================== */
        if ($table === 'periode_rpjmd') {

            $mulai   = (int)($filtered['periode_mulai'] ?? 0);
            $selesai = (int)($filtered['periode_selesai'] ?? 0);

            $kd_wilayah = $this->user['kd_wilayah'] ?? null;

            if (!$kd_wilayah) {
                return JsonResponse::error("Wilayah tidak ditemukan");
            }

            if ($mulai >= $selesai) {
                return JsonResponse::error("Periode tidak valid");
            }

            // CEK OVERLAP
            $cek = $this->db->query("
            SELECT id FROM periode_rpjmd
            WHERE kd_wilayah = ?
            AND (
                (? BETWEEN periode_mulai AND periode_selesai)
                OR
                (? BETWEEN periode_mulai AND periode_selesai)
            )
        ", [$kd_wilayah, $mulai, $selesai])->fetch();

            if ($cek) {
                return JsonResponse::error("Periode tumpang tindih");
            }

            // inject wilayah
            $filtered['kd_wilayah'] = $kd_wilayah;

            // HANDLE STATUS AKTIF
            if (!empty($filtered['status_aktif'])) {

                // nonaktifkan semua dulu
                $this->db->query("
                UPDATE periode_rpjmd
                SET status_aktif = 0
                WHERE kd_wilayah = ?
            ", [$kd_wilayah]);

                $filtered['status_aktif'] = 1;
            } else {
                $filtered['status_aktif'] = 0;
            }
        }
        $filtered['tgl_insert'] = date('Y-m-d H:i:s');
        $filtered['username_insert'] = $this->user['username'] ?? 'system';
        /* =====================================================
       INSERT FINAL
    ===================================================== */

        $this->db->insert($table, $filtered);

        return JsonResponse::success("Data berhasil disimpan");
    }

    /* =========================================================
       UPDATE
    ========================================================= */

    private function update(string $table, array $request): string
    {
        $columns = $this->getTableColumns($table);

        $id = $request['id'] ?? null;
        if (!$id) {
            return JsonResponse::error("ID tidak ditemukan");
        }

        unset($request['id']);

        $filtered = [];

        foreach ($request as $key => $value) {
            if (in_array($key, ['jenis', 'tbl'])) continue;
            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa diupdate");
        }

        $this->db->update($table, $filtered, "WHERE id = ?", [$id]);

        return JsonResponse::success("Data berhasil diupdate");
    }

    /* =========================================================
       DELETE
    ========================================================= */

    private function delete(string $table, array $profile, int $id): string
    {
        $primaryKey = $profile['primary_key'] ?? 'id';

        $this->db->delete(
            $table,
            "WHERE `$primaryKey` = ?",
            [$id]
        );

        return JsonResponse::success('Data berhasil dihapus');
    }

    /* =========================================================
       UTILITIES
    ========================================================= */

    private function getTableColumns(string $table): array
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM `$table`");
        return array_column($stmt->fetchAll(), 'Field');
    }

    private function applyUserScope(string $table): array
    {
        $columns = $this->getTableColumns($table);

        $whereParts = [];
        $params     = [];

        $mapping = [
            'kd_wilayah' => $this->user['kd_wilayah'] ?? null,
            'kd_opd'     => $this->user['kd_opd'] ?? null,
            'tahun'      => $this->user['tahun'] ?? null,
        ];

        foreach ($mapping as $field => $value) {
            if (in_array($field, $columns) && !empty($value)) {
                $whereParts[] = "`$field` = ?";
                $params[] = $value;
            }
        }

        return [$whereParts, $params];
    }
}
