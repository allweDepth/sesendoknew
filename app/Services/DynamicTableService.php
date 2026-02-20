<?php

require_once __DIR__ . '/JsonResponse.php';

/**
 * ============================================================
 * DYNAMIC TABLE SERVICE v3.0 (FULL IDENTIK + ACTION VERSION)
 * ============================================================
 *
 * PERUBAHAN:
 * ------------------------------------------------------------
 * ❌ jenis  → diganti action
 * ❌ mode   → diganti action
 * ✅ module → hanya konteks (tidak mengganggu logic lama)
 *
 * Semua fitur lama DIPERTAHANKAN 100%
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

            // 🔥 ARSITEKTUR BARU
            $action = $request['action'] ?? '';
            $module = $request['module'] ?? ''; // hanya konteks
            $tbl    = $request['tbl'] ?? '';

            /* ==============================
               DROPDOWN
            ============================== */
            if ($action === 'dropdown' && !empty($request['source'])) {
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
            if ($action === 'add') {
                $this->authorize('add', $table);
                return $this->insert($table, $request);
            }

            /* ==============================
               EDIT (DUAL MODE)
            ============================== */
            if ($action === 'edit') {

                // MODE LOAD DATA
                if (!empty($request['id_row']) && count($request) <= 4) {
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
            if ($action === 'delete' && !empty($request['id_row'])) {
                $this->authorize('delete', $table);
                return $this->delete($table, $profile, (int)$request['id_row']);
            }

            /* ==============================
               EXPORT
            ============================== */
            if ($action === 'export') {
                $this->authorize('view', $table);
                return $this->export($table, $profile, $request, 'default');
            }

            /* ==============================
               DEFAULT → LISTING
            ============================== */
            $this->authorize('view', $table);

            $mode = $action ?: 'default';

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
        $primaryKey = $this->getPrimaryKey($table);

        if (!$this->checkAccess($table, $id)) {
            return JsonResponse::error("Data tidak ditemukan atau tidak memiliki akses");
        }

        $row = $this->db->query(
            "SELECT * FROM `$table` WHERE `$primaryKey` = ? LIMIT 1",
            [$id]
        )->fetch();

        return JsonResponse::success("Data ditemukan", null, $row);
    }

    /* =========================================================
       ROLE AUTHORIZATION (TIDAK DIUBAH)
    ========================================================= */
    private function authorize(string $action, string $table): void
    {
        $role = $this->user['type_user'] ?? 'viewer';

        $permissions = [
            'super_admin'   => ['add', 'edit', 'delete', 'view'],
            'admin_wilayah' => ['add', 'edit', 'delete', 'view'],
            'admin_opd'     => ['add', 'edit', 'delete', 'view'],
            'editor'        => ['add', 'edit', 'view'],
            'viewer'        => ['view']
        ];

        if (!in_array($action, $permissions[$role] ?? [])) {
            throw new Exception("Tidak memiliki hak akses");
        }
    }

    /* =========================================================
       AUDIT TRAIL (TIDAK DIUBAH)
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
       INSERT (FULL IDENTIK LOGIC ASLI)
    ========================================================= */
    private function insert(string $table, array $request): string
    {
        $columns = $this->getTableColumns($table);
        $filtered = [];

        foreach ($request as $key => $value) {

            // 🔥 Abaikan field kontrol sistem (SUDAH DIGANTI)
            if (in_array($key, ['action', 'module', 'tbl'])) continue;

            // Hanya masukkan field yang memang ada di tabel
            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa disimpan");
        }

        /* =====================================================
        VALIDASI KHUSUS TABEL PERIODE RPJMD
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

            // Cek overlap periode
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

            // Inject wilayah dari session
            $filtered['kd_wilayah'] = $kd_wilayah;

            // Jika diset aktif → nonaktifkan yang lain
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

        /* =====================================================
        AUTO INJECT USER SCOPE
        ===================================================== */
        $userScopeMapping = [
            'kd_wilayah' => $this->user['kd_wilayah'] ?? null,
            'kd_opd'     => $this->user['kd_opd'] ?? null,
            'tahun'      => $this->user['tahun'] ?? null,
        ];

        foreach ($userScopeMapping as $field => $value) {

            if (in_array($field, $columns) && !isset($filtered[$field]) && $value !== null) {
                $filtered[$field] = $value;
            }
        }

        /* =====================================================
        AUTO SET PERIODE AKTIF UNTUK RENSTRA
        ===================================================== */
        if ($table === 'renstra_neo' && in_array('periode_id', $columns)) {

            $kd_wilayah = $this->user['kd_wilayah'] ?? null;

            $periodeAktif = $this->db->query("
                SELECT id
                FROM periode_rpjmd
                WHERE kd_wilayah = ?
                AND status_aktif = 1
                LIMIT 1
            ", [$kd_wilayah])->fetch();

            if ($periodeAktif) {
                $filtered['periode_id'] = $periodeAktif['id'];
            } else {

                $periodeTerbaru = $this->db->query("
                    SELECT id
                    FROM periode_rpjmd
                    WHERE kd_wilayah = ?
                    ORDER BY periode_mulai DESC
                    LIMIT 1
                ", [$kd_wilayah])->fetch();

                if ($periodeTerbaru) {
                    $filtered['periode_id'] = $periodeTerbaru['id'];
                } else {
                    return JsonResponse::error("Belum ada periode RPJMD terdaftar");
                }
            }
        }

        /* =====================================================
        AUTO GENERATE KODE MISI
        ===================================================== */
        if ($table === 'misi_renstra_neo' && in_array('kode', $columns)) {

            $renstraId = $filtered['renstra_id'] ?? null;

            if (!$renstraId) {
                return JsonResponse::error("Renstra wajib dipilih");
            }

            $lastKode = $this->db->query("
                SELECT MAX(CAST(kode AS UNSIGNED)) as max_kode
                FROM misi_renstra_neo
                WHERE renstra_id = ?
            ", [$renstraId])->fetch()['max_kode'] ?? 0;

            $filtered['kode'] = $lastKode + 1;
        }

        /* =====================================================
        VALIDASI HYBRID (PROFILE + SCHEMA)
        ===================================================== */
        $profile = $this->getProfileByTable($table);
        $errors = $this->validate($filtered, $table, $profile);

        if (!empty($errors)) {
            return JsonResponse::error("Validation gagal", 422, $errors);
        }

        /* =====================================================
        AUDIT TRAIL
        ===================================================== */
        $filtered = $this->injectAudit($filtered, 'insert');

        /* =====================================================
        FINAL INSERT
        ===================================================== */
        $this->db->insert($table, $filtered);

        return JsonResponse::success("Data berhasil disimpan");
    }
    /* =========================================================
       UPDATE (FULL IDENTIK LOGIC ASLI)
    ========================================================= */
    private function update(string $table, array $request): string
    {
        $columns = $this->getTableColumns($table);
        $primaryKey = $this->getPrimaryKey($table);

        $id = $request['id'] ?? null;
        if (!$id) {
            return JsonResponse::error("ID tidak ditemukan");
        }

        unset($request['id']);

        $filtered = [];

        foreach ($request as $key => $value) {

            // 🔥 SUDAH DIGANTI (hapus action/module/tbl)
            if (in_array($key, ['action', 'module', 'tbl'])) continue;

            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa diupdate");
        }

        $profile = $this->getProfileByTable($table);
        $errors = $this->validate($filtered, $table, $profile);

        if (!empty($errors)) {
            return JsonResponse::error("Validation gagal", 422, $errors);
        }

        $filtered = $this->injectAudit($filtered, 'update');

        if (!$this->checkAccess($table, $id)) {
            return JsonResponse::error("Tidak memiliki akses untuk update data ini");
        }

        $this->db->update(
            $table,
            $filtered,
            "WHERE `$primaryKey` = ?",
            [$id]
        );

        return JsonResponse::success("Data berhasil diupdate");
    }

    /* =========================================================
       DELETE (FULL IDENTIK LOGIC ASLI)
    ========================================================= */
    private function delete(string $table, array $profile, int $id): string
    {
        $primaryKey = $this->getPrimaryKey($table);

        if (!$this->checkAccess($table, $id)) {
            return JsonResponse::error("Tidak memiliki akses untuk menghapus data ini");
        }

        $this->db->delete(
            $table,
            "WHERE `$primaryKey` = ?",
            [$id]
        );

        return JsonResponse::success('Data berhasil dihapus');
    }

    /* =========================================================
       BUILD QUERY (LISTING + SEARCH + SCOPE FULL IDENTIK)
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

        // 🔥 Apply scope berdasarkan role
        list($userWhere, $userParams) = $this->applyUserScope($table);

        /* =====================================================
           SEARCH ENGINE (TIDAK DIUBAH)
        ===================================================== */
        if (!empty($search) && !empty($modeConfig['searchable'])) {

            $searchParts = [];
            $searchableColumns = $modeConfig['searchable'];

            if ($searchableColumns === ['*']) {
                $searchableColumns = $this->getTableColumns($table);
            }

            foreach ($searchableColumns as $column) {
                $searchParts[] = "`$column` LIKE ?";
                $params[] = "%$search%";
            }

            if (!empty($searchParts)) {
                $whereParts[] = "(" . implode(" OR ", $searchParts) . ")";
            }
        }

        $whereParts = array_merge($whereParts, $userWhere);
        $params     = array_merge($params, $userParams);

        $where = !empty($whereParts)
            ? 'WHERE ' . implode(' AND ', $whereParts)
            : '';

        /* ==============================
           TOTAL COUNT
        ============================== */
        $totalQuery = "SELECT COUNT(*) as total FROM `$table` $where";
        $totalRow = $this->db->query($totalQuery, $params)->fetch()['total'] ?? 0;

        /* ==============================
           SELECT CLAUSE
        ============================== */
        $select = $modeConfig['select'] ?? ['*'];
        $selectClause = implode(',', $select);

        $primaryKey = $this->getPrimaryKey($table);
        $orderBy = $modeConfig['order_by'] ?? "`$primaryKey` DESC";

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
                'limit' => $limit,
                'primary_key' => $this->getPrimaryKey($table)
            ],
            $rows
        );
    }
    /* =========================================================
       GET ALL RAW DATA (UNTUK EXPORT / REPORT)
    ========================================================= */
    private function getAllRaw(
        string $table,
        array $profile,
        array $request,
        string $mode
    ): array {

        $modeConfig = $profile['modes'][$mode]
            ?? $profile['modes']['default']
            ?? [];

        $select = $modeConfig['select'] ?? ['*'];
        $selectClause = implode(',', $select);

        list($userWhere, $userParams) = $this->applyUserScope($table);

        $where = !empty($userWhere)
            ? 'WHERE ' . implode(' AND ', $userWhere)
            : '';

        $primaryKey = $this->getPrimaryKey($table);

        $query = "
            SELECT $selectClause
            FROM `$table`
            $where
            ORDER BY `$primaryKey` DESC
        ";

        return $this->db->query($query, $userParams)->fetchAll();
    }

    /* =========================================================
       EXPORT ENGINE
    ========================================================= */
    private function export(
        string $table,
        array $profile,
        array $request,
        string $mode
    ): string {

        $this->authorize('view', $table);

        $rows = $this->getAllRaw($table, $profile, $request, $mode);

        // 🔥 Jika kosong tetap success tapi beri pesan
        if (empty($rows)) {
            return JsonResponse::success(
                "Data kosong",
                [
                    'total' => 0
                ],
                []
            );
        }

        return JsonResponse::success(
            "Data export berhasil",
            [
                'total' => count($rows)
            ],
            $rows
        );
    }

    /* =========================================================
       APPLY USER SCOPE (ROLE AWARE FULL IDENTIK)
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

        /* =====================================================
           ADMIN WILAYAH
        ===================================================== */
        if ($role === 'admin_wilayah') {

            if (in_array('kd_wilayah', $columns)) {
                $whereParts[] = "`kd_wilayah` = ?";
                $params[] = $this->user['kd_wilayah'] ?? null;
            }
        }

        /* =====================================================
           ADMIN OPD
        ===================================================== */
        if ($role === 'admin_opd') {

            if (in_array('kd_opd', $columns)) {
                $whereParts[] = "`kd_opd` = ?";
                $params[] = $this->user['kd_opd'] ?? null;
            }

            if (in_array('kd_wilayah', $columns)) {
                $whereParts[] = "`kd_wilayah` = ?";
                $params[] = $this->user['kd_wilayah'] ?? null;
            }

            if (in_array('tahun', $columns) && isset($this->user['tahun'])) {
                $whereParts[] = "`tahun` = ?";
                $params[] = $this->user['tahun'];
            }

            // Scope periode aktif
            if (in_array('periode_id', $columns)) {

                $kd_wilayah = $this->user['kd_wilayah'] ?? null;

                $periodeAktif = $this->db->query("
                    SELECT id
                    FROM periode_rpjmd
                    WHERE kd_wilayah = ?
                    AND status_aktif = 1
                    LIMIT 1
                ", [$kd_wilayah])->fetch();

                if ($periodeAktif) {
                    $whereParts[] = "`periode_id` = ?";
                    $params[] = $periodeAktif['id'];
                }
            }
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
       HYBRID VALIDATION ENGINE (FULL IDENTIK)
    ========================================================= */
    private function validate(array $data, string $table, array $profile): array
    {
        $errors = [];

        $customRules = $profile['validation'] ?? [];
        $schemaRules = $this->buildRulesFromSchema($table);

        $rules = array_merge($schemaRules, $customRules);

        foreach ($rules as $field => $fieldRules) {

            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {

                if ($rule === 'required' && empty($value)) {
                    $errors[$field] = "$field wajib diisi";
                }

                if ($rule === 'numeric' && !empty($value) && !is_numeric($value)) {
                    $errors[$field] = "$field harus berupa angka";
                }

                if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "$field tidak valid";
                }

                if (str_starts_with($rule, 'max:') && !empty($value)) {

                    $max = (int)explode(':', $rule)[1];

                    if (strlen($value) > $max) {
                        $errors[$field] = "$field maksimal $max karakter";
                    }
                }
            }
        }

        return $errors;
    }

    /* =========================================================
       BUILD RULE DARI SCHEMA DATABASE
    ========================================================= */
    private function buildRulesFromSchema(string $table): array
    {
        $rules = [];

        $columns = $this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll();

        foreach ($columns as $col) {

            $field = $col['Field'];
            $type  = $col['Type'];
            $null  = $col['Null'];

            $fieldRules = [];

            if ($null === 'NO' && $field !== 'id') {
                $fieldRules[] = 'required';
            }

            if (str_contains($type, 'int') || str_contains($type, 'decimal')) {
                $fieldRules[] = 'numeric';
            }

            if (preg_match('/varchar\((\d+)\)/', $type, $match)) {
                $fieldRules[] = 'max:' . $match[1];
            }

            if (!empty($fieldRules)) {
                $rules[$field] = $fieldRules;
            }
        }

        return $rules;
    }

    /* =========================================================
       DROPDOWN ENGINE (FULL IDENTIK)
    ========================================================= */
    private function loadDropdown(string $source, $parentValue = null): string
    {
        if (!isset($this->profiles[$source])) {
            return JsonResponse::error("Source tidak ditemukan");
        }

        $profile = $this->profiles[$source];
        $table   = $profile['table'];

        $primaryKey = $profile['primary_key'] ?? 'id';
        $valueField = $profile['dropdown']['value'] ?? $primaryKey;
        $labelField = $profile['dropdown']['label'] ?? 'nama';

        list($scopeWhere, $scopeParams) = $this->applyUserScope($table);

        $whereParts = $scopeWhere;
        $params     = $scopeParams;

        if ($parentValue !== null) {
            foreach ($this->getTableColumns($table) as $col) {
                if (str_starts_with($col, 'kode_') || str_starts_with($col, 'id_')) {
                    $whereParts[] = "`$col` = ?";
                    $params[] = $parentValue;
                    break;
                }
            }
        }

        $where = !empty($whereParts)
            ? "WHERE " . implode(" AND ", $whereParts)
            : "";

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
       UTIL: GET PROFILE BY TABLE
    ========================================================= */
    private function getProfileByTable(string $table): array
    {
        foreach ($this->profiles as $profile) {
            if (($profile['table'] ?? '') === $table) {
                return $profile;
            }
        }
        return [];
    }

    /* =========================================================
       UTIL: GET PRIMARY KEY
    ========================================================= */
    private function getPrimaryKey(string $table): string
    {
        $profile = $this->getProfileByTable($table);
        return $profile['primary_key'] ?? 'id';
    }

    /* =========================================================
       UTIL: CHECK ACCESS WITH SCOPE
    ========================================================= */
    private function checkAccess(string $table, $id): bool
    {
        $primaryKey = $this->getPrimaryKey($table);

        list($scopeWhere, $scopeParams) = $this->applyUserScope($table);

        $whereParts = array_merge(["`$primaryKey` = ?"], $scopeWhere);
        $params     = array_merge([$id], $scopeParams);

        $where = "WHERE " . implode(" AND ", $whereParts);

        $row = $this->db->query(
            "SELECT `$primaryKey` FROM `$table` $where LIMIT 1",
            $params
        )->fetch();

        return (bool)$row;
    }
}
