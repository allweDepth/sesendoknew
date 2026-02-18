<?php

require_once __DIR__ . '/JsonResponse.php';

/**
 * ============================================================
 * DYNAMIC TABLE SERVICE v2.0
 * ============================================================
 *
 * Prinsip:
 * ------------------------------------------------------------
 * - jenis = menentukan aksi (add, edit, delete, dropdown)
 * - edit = dual mode (load data / update data)
 * - selain aksi = dianggap mode listing (buildQuery)
 *
 * Keamanan:
 * ------------------------------------------------------------
 * ✔ Role enforcement full server-side
 * ✔ User scope filtering otomatis
 * ✔ Audit trail otomatis (tgl_insert, username_update, dll)
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
       MAIN HANDLER (ENTRY POINT)
    ========================================================= */
    public function handle(array $request): string
    {
        try {

            $jenis = $request['jenis'] ?? '';
            $tbl   = $request['tbl'] ?? '';

            /* ==============================
               DROPDOWN
            ============================== */
            if ($jenis === 'dropdown' && !empty($request['source'])) {
                return $this->loadDropdown(
                    $request['source'],
                    $request['parent_value'] ?? null
                );
            }

            if (!$tbl) {
                return JsonResponse::error('Tabel tidak dikirim');
            }

            if (!isset($this->profiles[$tbl])) {
                return JsonResponse::error('Tabel tidak terdaftar');
            }

            $profile = $this->profiles[$tbl];
            $table   = $profile['table'];

            /* ==============================
               ADD (INSERT)
            ============================== */
            if ($jenis === 'add') {
                $this->authorize('add', $table);
                return $this->insert($table, $request);
            }

            /* ==============================
               EDIT (DUAL MODE)
            ============================== */
            if ($jenis === 'edit') {

                // MODE LOAD DATA
                if (!empty($request['id_row']) && count($request) <= 3) {
                    $this->authorize('view', $table);
                    return $this->getById($table, (int)$request['id_row']);
                }

                // MODE UPDATE
                if (!empty($request['id'])) {
                    $this->authorize('edit', $table);
                    return $this->update($table, $request);
                }

                return JsonResponse::error("ID tidak ditemukan");
            }

            /* ==============================
               DELETE
            ============================== */
            if ($jenis === 'delete' && !empty($request['id_row'])) {
                $this->authorize('delete', $table);
                return $this->delete($table, $profile, (int)$request['id_row']);
            }

            /* ==============================
               DEFAULT → LISTING
            ============================== */
            $this->authorize('view', $table);

            $mode = $jenis ?: 'default';

            return $this->buildQuery($table, $profile, $request, $mode);
        } catch (Exception $e) {
            return JsonResponse::error($e->getMessage());
        }
    }

    /* =========================================================
       GET SINGLE ROW
    ========================================================= */
    private function getById(string $table, int $id): string
    {
        $row = $this->db->query(
            "SELECT * FROM `$table` WHERE id = ? LIMIT 1",
            [$id]
        )->fetch();

        if (!$row) {
            return JsonResponse::error("Data tidak ditemukan");
        }

        return JsonResponse::success("Data ditemukan", null, $row);
    }

    /* =========================================================
       ROLE AUTHORIZATION (SERVER-SIDE ENFORCEMENT)
    ========================================================= */
    private function authorize(string $action, string $table): void
    {
        $role = $this->user['type_user'] ?? 'viewer';

        $permissions = [
            'super_admin' => ['add', 'edit', 'delete', 'view'],
            'admin'       => ['add', 'edit', 'delete', 'view'],
            'editor'      => ['add', 'edit', 'view'],
            'viewer'      => ['view']
        ];

        if (!in_array($action, $permissions[$role] ?? [])) {
            throw new Exception("Tidak memiliki hak akses");
        }
    }

    /* =========================================================
       AUDIT TRAIL INJECTION
       Otomatis isi tgl_insert, username_update, dll
    ========================================================= */
    private function injectAudit(array $data, string $mode): array
    {
        $now  = date('Y-m-d H:i:s');
        $user = $this->user['username'] ?? 'system';

        if ($mode === 'insert') {
            $data['tgl_insert'] = $now;
            $data['username_insert'] = $user;
        }

        if ($mode === 'update') {
            $data['tgl_update'] = $now;
            $data['username_update'] = $user;
        }

        return $data;
    }

    /* =========================================================
       INSERT
    ========================================================= */
    private function insert(string $table, array $request): string
    {
        $columns = $this->getTableColumns($table);
        $filtered = [];

        // Filter hanya kolom yang ada di DB
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
           VALIDASI KHUSUS PERIODE RPJMD
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

            $filtered['kd_wilayah'] = $kd_wilayah;

            // Handle status aktif (hanya 1 aktif per wilayah)
            if (!empty($filtered['status_aktif'])) {

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

        // Inject audit otomatis
        $filtered = $this->injectAudit($filtered, 'insert');

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

        // Inject audit otomatis
        $filtered = $this->injectAudit($filtered, 'update');

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
       BUILD QUERY (LISTING)
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

        // Apply scope berdasarkan role
        list($userWhere, $userParams) = $this->applyUserScope($table);

        $whereParts = array_merge($whereParts, $userWhere);
        $params     = array_merge($params, $userParams);

        $where = !empty($whereParts)
            ? 'WHERE ' . implode(' AND ', $whereParts)
            : '';

        $totalQuery = "SELECT COUNT(*) as total FROM `$table` $where";
        $totalRow = $this->db->query($totalQuery, $params)->fetch()['total'] ?? 0;

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
       APPLY USER SCOPE (ROLE AWARE)
    ========================================================= */
    private function applyUserScope(string $table): array
    {
        $role = $this->user['type_user'] ?? 'viewer';

        // super admin lihat semua
        if ($role === 'super_admin') {
            return [[], []];
        }

        $columns = $this->getTableColumns($table);

        $whereParts = [];
        $params     = [];

        if ($role === 'admin_wilayah' && in_array('kd_wilayah', $columns)) {
            $whereParts[] = "`kd_wilayah` = ?";
            $params[] = $this->user['kd_wilayah'] ?? null;
        }

        if ($role === 'admin_opd' && in_array('kd_opd', $columns)) {
            $whereParts[] = "`kd_opd` = ?";
            $params[] = $this->user['kd_opd'] ?? null;
        }

        return [$whereParts, $params];
    }

    /* =========================================================
       UTIL: GET TABLE COLUMNS
    ========================================================= */
    private function getTableColumns(string $table): array
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM `$table`");
        return array_column($stmt->fetchAll(), 'Field');
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

        // Terapkan user scope (role aware)
        list($scopeWhere, $scopeParams) = $this->applyUserScope($table);

        $whereParts = $scopeWhere;
        $params     = $scopeParams;

        // Jika dropdown punya parent dependency
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
}
