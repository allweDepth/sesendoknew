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
/**
 * ============================================================
 * DYNAMIC TABLE SERVICE v3.2 — ENTERPRISE SAFE IMPORT READY
 * ============================================================
 *
 * ARSITEKTUR UTAMA:
 * ------------------------------------------------------------
 * Class ini adalah CORE BUSINESS ENGINE seluruh sistem.
 *
 * Semua operasi CRUD, LISTING, DROPDOWN, EXPORT, IMPORT
 * melewati service ini.
 *
 * DESIGN PRINCIPLE:
 * ------------------------------------------------------------
 * 1️⃣ Profile-driven (berbasis table_profiles.php)
 * 2️⃣ Schema-aware (ambil rules dari DB langsung)
 * 3️⃣ Role-aware (authorize + scope)
 * 4️⃣ Soft-lock aware
 * 5️⃣ Time-window aware
 * 6️⃣ Hierarchy-aware
 * 7️⃣ Duplicate-aware
 * 8️⃣ Audit trail ready
 * 9️⃣ Transaction safe
 *
 * ------------------------------------------------------------
 * 🔥 IMPORT ENGINE STATUS:
 * - Strict mode (rollback jika ada error)
 * - Relation mapping (text → foreign key)
 * - Multi relation support
 * - Relation cache (no query per row)
 * - Duplicate & hierarchy validation
 * - Auto session injection
 *
 * ------------------------------------------------------------
 * TIDAK ADA LOGIC LAMA YANG DIHAPUS
 * Hanya enhancement yang ditambahkan secara isolated.
 * ============================================================
 */
class DynamicTableService
{
    private DB $db;
    private array $profiles;
    private array $user;
    // ======================================================
    // 🔥 CACHE RELATION IMPORT
    // Digunakan untuk menyimpan hasil lookup relasi
    // agar tidak query database per baris Excel
    // ======================================================
    private array $relationCache = [];
    /* =========================================================
            INTERNAL CACHE (ANTI DOUBLE QUERY)
            ========================================================= */
    private static array $columnCache = [];
    private static array $schemaCache = [];
    private ?array $pengaturanAktifCache = null;
    private ?array $periodeAktifCache = null;
    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
        $this->user = $_SESSION['user'] ?? [];
    }

    /* =========================================================
        MAIN HANDLER (ENTRY POINT) — HARDENED VERSION
        ---------------------------------------------------------
        PERUBAHAN:
        - Wajib action eksplisit
        - Tidak ada fallback implicit
        - Validasi action lebih awal
        - Tetap kompatibel dengan arsitektur lama
    ========================================================= */
    public function handle(array $request): string
    {
        try {

            /* =====================================================
            1️⃣ VALIDASI ACTION
            ===================================================== */
            if (empty($request['action'])) {
                return JsonResponse::error("Action wajib dikirim");
            }

            $action = $request['action'];

            // 🔥 Daftar action yang diizinkan sistem
            $allowedActions = [
                'add',
                'edit',
                'delete',
                'dropdown',
                'export',
                'list'
            ];

            if (!in_array($action, $allowedActions)) {
                return JsonResponse::error("Action tidak valid");
            }

            /* =====================================================
            2️⃣ VALIDASI TABEL
            ===================================================== */
            $tbl = $request['tbl'] ?? null;

            if (!$tbl) {
                return JsonResponse::error("Tabel tidak dikirim");
            }

            if (!isset($this->profiles[$tbl])) {
                return JsonResponse::error("Tabel tidak terdaftar");
            }

            $profile = $this->profiles[$tbl];
            $table   = $profile['table'];

            /* =====================================================
            3️⃣ EKSEKUSI ACTION
            ===================================================== */
            return $this->executeAction(
                $action,
                $tbl,
                $table,
                $profile,
                $request
            );
        } catch (\Throwable $e) {

            return JsonResponse::error($e->getMessage());
        }
    }

    /* =========================================================
    EXECUTE ACTION (NO IMPLICIT FALLBACK)
    ---------------------------------------------------------
    PERUBAHAN:
    - Listing hanya via action = 'list'
    - Tidak ada default auto listing
    - Lebih eksplisit & SPA konsisten
    ========================================================= */
    private function executeAction(
        string $action,
        string $tbl,
        string $table,
        array $profile,
        array $request
    ): string {

        switch ($action) {

            /* =====================================================
            ➕ ADD
            ===================================================== */
            case 'add':
                $this->authorize('add', $table);
                return $this->insert($table, $request);


                /* =====================================================
            ✏ EDIT
            - Jika hanya id_row → ambil data
            - Jika ada id → update
            ===================================================== */
            case 'edit':

                // 🔍 GET SINGLE ROW
                if (!empty($request['id_row']) && count($request) <= 4) {
                    $this->authorize('view', $table);
                    return $this->getById($table, $request['id_row']);
                }

                // 🔄 UPDATE
                if (!empty($request['id'])) {
                    $this->authorize('edit', $table);
                    return $this->update($table, $request);
                }

                return JsonResponse::error("ID tidak ditemukan");


                /* =====================================================
            🗑 DELETE
            ===================================================== */
            case 'delete':
                $this->authorize('delete', $table);
                return $this->delete(
                    $table,
                    $profile,
                    $request['id_row'] ?? null
                );


                /* =====================================================
            📥 DROPDOWN
            ===================================================== */
            case 'dropdown':
                return $this->loadDropdown(
                    $request['tbl'] ?? null,
                    $request['parent_value'] ?? null,
                    $request['kd_akun'] ?? null
                );


                /* =====================================================
            📤 EXPORT
            ===================================================== */
            case 'export':
                $this->authorize('view', $table);
                return $this->export(
                    $table,
                    $profile,
                    $request,
                    'default'
                );


                /* =====================================================
            📋 LISTING (WAJIB action = list)
            ===================================================== */
            case 'list':
                $this->authorize('view', $table);
                return $this->listing(
                    $table,
                    $profile,
                    $request,
                    'default'
                );


                /* =====================================================
            ❌ NO FALLBACK
            ===================================================== */
            default:
                return JsonResponse::error("Action tidak dikenali");
        }
    }
    /* =========================================================
            GET SINGLE ROW
            ========================================================= */
    private function getById(string $table, int|string $id): string
    {
        $primaryKey = $this->getPrimaryKey($table);

        if (!$this->checkAccess($table, $id)) {
            return JsonResponse::error("Data tidak ditemukan atau tidak memiliki akses");
        }

        $row = $this->db->query(
            "SELECT * FROM `$table` WHERE `$primaryKey` = ? LIMIT 1",
            [$id]
        )->fetch();

        return JsonResponse::success("Data ditemukan", [], $row ?? []);
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
    /* =========================================================
            INSERT (FIXED STABLE VERSION v3.1)
            ========================================================= */
    private function insert(string $table, array $request): string
    {
        $columns  = $this->getTableColumns($table);
        $filtered = [];

        /* =====================================================
                1️⃣ FILTER FIELD SESUAI KOLOM TABEL
                ===================================================== */
        foreach ($request as $key => $value) {

            if (in_array($key, ['action', 'module', 'tbl'])) continue;

            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa disimpan");
        }

        /* =====================================================
        2️⃣ NORMALISASI BOOLEAN & DATE
        ===================================================== */
        foreach ($filtered as $field => $value) {

            // checkbox
            if ($value === 'on') {
                $filtered[$field] = 1;
            }

            // date format
            if (
                is_string($value)
                && $value !== ''
                && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)
            ) {

                $parts = explode('/', $value);

                $filtered[$field] = sprintf(
                    '%04d-%02d-%02d',
                    $parts[2],
                    $parts[1],
                    $parts[0]
                );
            }
        }

        /* =====================================================
        3️⃣ AUTO FIELD RESOLUTION (SCOPE)
        ===================================================== */
        $filtered = $this->resolveAutoFields($table, $filtered);
        //date time
        $filtered = $this->normalizeDateTimeFields($table, $filtered);
        /* =====================================================
        4️⃣ SPECIAL TABLE RULES
        ===================================================== */

        // 🔥 Periode RPJMD
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

        // 🔥 Auto periode untuk renstra
        $filtered = $this->resolvePeriode($table, $filtered);
        $this->validateTimeWindow($table);

        // 🔥 Auto generate kode misi
        if ($table === 'misi_renstra_neo') {

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
        5️⃣ SYSTEM DEFAULT FIELD
        ===================================================== */
        if (in_array('disable', $columns) && !isset($filtered['disable'])) {
            $filtered['disable'] = 0;
        }

        if (in_array('is_deleted', $columns) && !isset($filtered['is_deleted'])) {
            $filtered['is_deleted'] = 0;
        }

        /* =====================================================
        6️⃣ PERATURAN RESOLUTION (GLOBAL CLEAN)
        ===================================================== */
        $filtered = $this->resolvePeraturan($table, $filtered);

        /* =====================================================
        7️⃣ BUSINESS VALIDATION LAYER
        ===================================================== */
        $this->validateHierarchy($table, $filtered);
        $this->validateDuplicate($table, $filtered);
        // 🔥 VALIDASI MAPPING AKUN
        $this->validateAkunMapping($table, $filtered);

        /* =====================================================
        8️⃣ SANITATION & AUDIT
        ===================================================== */
        $filtered = $this->applySanitization($table, $filtered);
        $filtered = $this->injectAudit($filtered, 'insert');

        /* =====================================================
        9️⃣ HYBRID VALIDATION (SCHEMA + PROFILE)
        ===================================================== */
        $profile = $this->getProfileByTable($table);
        $errors  = $this->validate($filtered, $table, $profile);

        if (!empty($errors)) {
            return JsonResponse::error("Validation gagal", 422, $errors);
        }

        /* =====================================================
        🔟 FINAL TRANSACTION
        ===================================================== */
        return $this->runTransaction(function () use ($table, $filtered) {

            $this->db->insert($table, $filtered);

            $id = $this->db->lastInsertId();

            $this->logActivity($table, $id, 'insert', null, $filtered);

            return JsonResponse::success("Data berhasil disimpan");
        });
    }
    /* =========================================================
            UPDATE (FIXED STABLE VERSION v3.1)
            ========================================================= */
    private function update(string $table, array $request): string
    {
        $columns    = $this->getTableColumns($table);
        $primaryKey = $this->getPrimaryKey($table);

        $id = $request['id'] ?? null;

        if (!$id) {
            return JsonResponse::error("ID tidak ditemukan");
        }

        unset($request['id']);

        /* =====================================================
        1️⃣ FILTER FIELD SESUAI KOLOM
        ===================================================== */
        $filtered = [];

        foreach ($request as $key => $value) {

            if (in_array($key, ['action', 'module', 'tbl'])) continue;

            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa diupdate");
        }

        /* =====================================================
        2️⃣ NORMALISASI BOOLEAN & DATE
        ===================================================== */
        foreach ($filtered as $field => $value) {

            if ($value === 'on') {
                $filtered[$field] = 1;
            }

            if (
                is_string($value)
                && $value !== ''
                && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)
            ) {

                $parts = explode('/', $value);

                $filtered[$field] = sprintf(
                    '%04d-%02d-%02d',
                    $parts[2],
                    $parts[1],
                    $parts[0]
                );
            }
        }

        /* =====================================================
        3️⃣ LOAD OLD DATA
        ===================================================== */
        $oldData = $this->db->query(
            "SELECT * FROM `$table` WHERE `$primaryKey` = ?",
            [$id]
        )->fetch();

        if (!$oldData) {
            return JsonResponse::error("Data tidak ditemukan");
        }

        /* =====================================================
        4️⃣ AUTO FIELD RESOLUTION
        ===================================================== */
        $filtered = $this->resolveAutoFields($table, $filtered);
        $filtered = $this->normalizeDateTimeFields($table, $filtered);
        $filtered = $this->resolvePeraturan($table, $filtered);
        $filtered = $this->resolvePeriode($table, $filtered);

        /* =====================================================
        5️⃣ BUSINESS VALIDATION
        ===================================================== */
        $this->validateHierarchy($table, $filtered);
        // 🔥 VALIDASI MAPPING AKUN
        $this->validateAkunMapping($table, $filtered);
        /* =====================================================
        6️⃣ SANITATION & AUDIT
        ===================================================== */
        $filtered = $this->applySanitization($table, $filtered);
        $filtered = $this->injectAudit($filtered, 'update');

        /* =====================================================
        7️⃣ PRESERVE REQUIRED FIELDS
        ===================================================== */
        foreach ($oldData as $field => $value) {
            if (!isset($filtered[$field])) {
                $filtered[$field] = $value;
            }
        }

        /* =====================================================
        8️⃣ VALIDATION HYBRID
        ===================================================== */
        $profile = $this->getProfileByTable($table);
        $errors  = $this->validate($filtered, $table, $profile, $id);

        if (!empty($errors)) {
            return JsonResponse::error("Validation gagal", 422, $errors);
        }

        /* =====================================================
        9️⃣ DIFF CHECK
        ===================================================== */
        $diff = [];

        foreach ($filtered as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] != $value) {
                $diff[$key] = $value;
            }
        }

        if (empty($diff)) {
            return JsonResponse::success("Tidak ada perubahan");
        }

        /* =====================================================
        🔟 FINAL TRANSACTION
        ===================================================== */

        return $this->runTransaction(function () use ($table, $primaryKey, $id, $diff, $oldData) {

            $this->db->update(
                $table,
                $diff,
                "WHERE `$primaryKey` = ?",
                [$id]
            );

            $this->logActivity($table, $id, 'update', $oldData, $diff);

            return JsonResponse::success("Data berhasil diupdate");
        });
    }
    /* =========================================================
            DELETE (FULL IDENTIK LOGIC ASLI)
            ========================================================= */
    private function delete(string $table, array $profile, int|string $id): string
    {
        $primaryKey = $this->getPrimaryKey($table);

        if (!$this->checkAccess($table, $id)) {
            return JsonResponse::error("Tidak memiliki akses");
        }

        $oldData = $this->db->query(
            "SELECT * FROM `$table` WHERE `$primaryKey` = ?",
            [$id]
        )->fetch();

        return $this->runTransaction(function () use ($table, $primaryKey, $id, $oldData) {

            $columns = $this->getTableColumns($table);

            if (in_array('deleted_at', $columns)) {

                $this->db->update(
                    $table,
                    [
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'deleted_by' => $this->user['username'] ?? 'system'
                    ],
                    "WHERE `$primaryKey` = ?",
                    [$id]
                );
            } else {

                $this->db->delete(
                    $table,
                    "WHERE `$primaryKey` = ?",
                    [$id]
                );
            }

            $this->logActivity($table, $id, 'delete', $oldData, null);

            return JsonResponse::success("Data berhasil dihapus");
        });
    }

    /* =========================================================
                BUILD QUERY (LISTING + SEARCH + SCOPE FULL IDENTIK)
                ========================================================= */
    private function listing(
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
        $search = isset($request['cari']) && is_string($request['cari'])
            ? trim($request['cari'])
            : '';
        $offset = ($page - 1) * $limit;

        // 🔥 1️⃣ Resolve Scope
        list($scopeWhere, $scopeParams) =
            $this->resolveScope($table, $profile, $mode);

        // 🔥 2️⃣ Resolve Search
        list($searchWhere, $searchParams) =
            $this->resolveSearch($table, $modeConfig, $search);

        $whereParts = array_merge($scopeWhere, $searchWhere);
        $params     = array_merge($scopeParams, $searchParams);

        $where = !empty($whereParts)
            ? "WHERE " . implode(" AND ", $whereParts)
            : "";

        $select = implode(',', $modeConfig['select'] ?? ['*']);

        $primaryKey = $this->getPrimaryKey($table);
        $orderBy = $modeConfig['order_by'] ?? "`$primaryKey` DESC";

        // 🔥 VALIDASI KOLOM ORDER BY
        $columns = $this->getTableColumns($table);

        preg_match('/`?([a-zA-Z0-9_]+)`?/i', $orderBy, $match);
        $orderColumn = $match[1] ?? $primaryKey;

        if (!in_array($orderColumn, $columns)) {
            $orderBy = "`$primaryKey` DESC";
        }

        $total = $this->db->query(
            "SELECT COUNT(*) as total FROM `$table` $where",
            $params
        )->fetch()['total'] ?? 0;

        $rows = $this->db->query(
            "SELECT $select FROM `$table`
            $where
            ORDER BY $orderBy
            LIMIT $offset, $limit",
            $params
        )->fetchAll();

        return JsonResponse::success(
            "Data berhasil ditampilkan",
            [
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'primary_key' => $primaryKey
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
    /* =========================================================
            APPLY USER SCOPE (LOGIC TIDAK DIUBAH)
            HANYA PERIODE AKTIF DI-CACHE
            ========================================================= */
    private function applyUserScope(string $table): array
    {
        $role = $this->user['type_user'] ?? 'viewer';

        if ($role === 'super_admin') {
            return [[], []];
        }

        $columns = $this->getTableColumns($table);

        $whereParts = [];
        $params     = [];

        if ($role === 'admin_wilayah') {

            if (in_array('kd_wilayah', $columns)) {
                $whereParts[] = "`kd_wilayah` = ?";
                $params[] = $this->user['kd_wilayah'] ?? null;
            }
        }

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

            if (in_array('periode_id', $columns)) {

                $periodeAktif = $this->getPeriodeAktif();

                if ($periodeAktif) {
                    $whereParts[] = "`periode_id` = ?";
                    $params[] = $periodeAktif['id'];
                }
            }
        }

        return [$whereParts, $params];
    }

    /* =========================================================
            CACHE OPTIMIZATION SECTION
            ========================================================= */

    private function getTableColumns(string $table): array
    {
        if (isset(self::$columnCache[$table])) {
            return self::$columnCache[$table];
        }

        $stmt = $this->db->query("SHOW COLUMNS FROM `$table`");
        self::$columnCache[$table] = array_column($stmt->fetchAll(), 'Field');

        return self::$columnCache[$table];
    }

    /* =========================================================
            HYBRID VALIDATION ENGINE (FULL IDENTIK)
            ========================================================= */
    private function validate(array $data, string $table, array $profile, $currentId = null): array
    {
        $errors = [];

        $customRules = $profile['validation'] ?? [];
        $schemaRules = $this->buildRulesFromSchema($table);

        $rules = array_merge($schemaRules, $customRules);

        foreach ($rules as $field => $fieldRules) {

            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {

                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = "$field wajib diisi";
                }

                if ($rule === 'numeric' && !empty($value) && !is_numeric($value)) {
                    $errors[$field] = "$field harus berupa angka";
                }

                if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "$field tidak valid";
                }
                //rule validasi
                if ($rule === 'unique' && ($value !== null && $value !== '')) {

                    $primaryKey = $this->getPrimaryKey($table);

                    if ($currentId) {
                        $exists = $this->db->query(
                            "SELECT $primaryKey FROM `$table` 
                                    WHERE `$field` = ? 
                                    AND `$primaryKey` != ? 
                                    LIMIT 1",
                            [$value, $currentId]
                        )->fetch();
                    } else {
                        $exists = $this->db->query(
                            "SELECT $primaryKey FROM `$table` 
                                    WHERE `$field` = ? 
                                    LIMIT 1",
                            [$value]
                        )->fetch();
                    }

                    if ($exists) {
                        $errors[$field] = "$field sudah digunakan";
                    }
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
        if (isset(self::$schemaCache[$table])) {
            return self::$schemaCache[$table];
        }

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

        self::$schemaCache[$table] = $rules;

        return $rules;
    }

    /* =========================================================
            DROPDOWN ENGINE (FULL IDENTIK)
            ========================================================= */
    /* =========================================================
        DROPDOWN ENGINE (PROFILE-DRIVEN + AKUN FILTER)
        ========================================================= */
    private function loadDropdown(
        string $source,
        $parentValue = null,
        ?string $kdAkun = null
    ): string {

        // 🔎 Pastikan profile ada
        if (!isset($this->profiles[$source])) {
            return JsonResponse::error("Source tidak ditemukan");
        }

        $profile = $this->profiles[$source];
        $table   = $profile['table'];

        // 🔥 Primary key WAJIB didefinisikan di awal
        $primaryKey = $profile['primary_key'] ?? 'id';

        $valueField = $profile['dropdown']['value'] ?? $primaryKey;
        $labelField = $profile['dropdown']['label'] ?? 'nama';

        $columns = $this->getTableColumns($table);

        /* ======================================================
            🔥 RELASI PARENT (JIKA ADA)
        ====================================================== */
        $whereParts = [];
        $params     = [];

        if ($parentValue !== null && !empty($profile['relations'])) {

            foreach ($profile['relations'] as $relation) {

                $localKey = $relation['local_key'] ?? null;

                if ($localKey && in_array($localKey, $columns)) {
                    $whereParts[] = "`$table`.`$localKey` = ?";
                    $params[] = $parentValue;
                }
            }
        }

        /* ======================================================
            🔥 PROFILE-DRIVEN AKUN FILTER
        ====================================================== */
        $join       = '';
        $akunWhere  = '';
        $akunParams = [];

        $filterByAkun = $profile['dropdown']['filter_by_akun'] ?? false;
        $pivotConfig  = $profile['pivot'] ?? null;

        if ($filterByAkun && $kdAkun && $pivotConfig) {

            $pivotTable = $pivotConfig['table'];
            $fkField    = $pivotConfig['foreign_key'];

            $join = "
                INNER JOIN `$pivotTable` p
                    ON p.`$fkField` = `$table`.`$primaryKey`
            ";

            $pengaturan = $this->getPengaturanAktif();

            $akunWhere = "
                AND p.`kd_akun` = ?
                AND p.`kd_wilayah` = ?
                AND p.`peraturan_id` = ?
            ";

            $akunParams = [
                $kdAkun,
                $this->user['kd_wilayah'] ?? null,
                $pengaturan['aturan_sbu'] ?? null
            ];
        }

        /* ======================================================
            🔥 FINAL WHERE BUILD
        ====================================================== */
        $where = '';

        if (!empty($whereParts)) {
            $where = "WHERE " . implode(" AND ", $whereParts);
        }

        /* ======================================================
            🔥 FINAL QUERY
        ====================================================== */
        $query = "
            SELECT 
                `$table`.`$valueField` as id,
                `$table`.`$labelField` as uraian
            FROM `$table`
            $join
            $where
            $akunWhere
            ORDER BY `$table`.`$valueField` ASC
        ";

        $rows = $this->db->query(
            $query,
            array_merge($params, $akunParams)
        )->fetchAll();

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
    private function logActivity(
        string $table,
        $recordId,
        string $action,
        $oldData = null,
        $newData = null
    ): void {

        if (!$this->tableExists('log_activity')) {
            return;
        }

        try {

            $this->db->insert('log_activity', [
                'table_name' => $table,
                'record_id'  => $recordId,
                'action'     => $action,
                'old_data'   => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                'new_data'   => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                'username'   => $this->user['username'] ?? 'system',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Jangan sampai audit log bikin sistem utama gagal
        }
    }
    /* =========================================================
        UTIL: CEK APAKAH TABEL ADA
        ========================================================= */
    private function tableExists(string $table): bool
    {
        $result = $this->db->query(
            "SHOW TABLES LIKE ?",
            [$table]
        )->fetch();

        return (bool)$result;
    }
    private function runTransaction(callable $callback)
    {
        try {

            $this->db->query("START TRANSACTION");

            $result = $callback();

            $this->db->query("COMMIT");

            return $result;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }
    private function getPengaturanAktif(): ?array
    {
        if ($this->pengaturanAktifCache !== null) {
            return $this->pengaturanAktifCache;
        }

        $kd_wilayah = $this->user['kd_wilayah'] ?? null;
        $tahun      = $this->user['tahun'] ?? null;

        if (!$kd_wilayah || !$tahun) {
            return null;
        }

        $result = $this->db->query("
            SELECT *
            FROM pengaturan_neo
            WHERE kd_wilayah = ?
            AND tahun = ?
            AND disable = 0
            LIMIT 1
        ", [$kd_wilayah, $tahun])->fetch();

        $this->pengaturanAktifCache = $result ?: null;

        return $this->pengaturanAktifCache;
    }
    private function getPeriodeAktif(): ?array
    {
        if ($this->periodeAktifCache !== null) {
            return $this->periodeAktifCache;
        }

        $kd_wilayah = $this->user['kd_wilayah'] ?? null;

        if (!$kd_wilayah) {
            return null;
        }

        $this->periodeAktifCache = $this->db->query("
                    SELECT id
                    FROM periode_rpjmd
                    WHERE kd_wilayah = ?
                    AND status_aktif = 1
                    LIMIT 1
                ", [$kd_wilayah])->fetch();

        return $this->periodeAktifCache;
    }
    /* =========================================================
        NORMALIZE HEADER XLSX → snake_case
        ========================================================= */
    private function normalizeHeader(?string $header): string
    {
        if ($header === null) {
            return '';
        }

        $header = trim($header);

        if ($header === '') {
            return '';
        }

        return strtolower(
            preg_replace('/[^a-z0-9_]/', '', $header)
        );
    }
    /* =========================================================
        VALIDASI IMPORT PERMISSION
        ========================================================= */
    private function validateImportPermission(string $table): void
    {
        $role = $this->user['type_user'] ?? 'viewer';

        $restricted = [
            'urusan',
            'bidang',
            'program',
            'kegiatan',
            'sub_kegiatan'
        ];

        if ($role === 'admin_opd' && in_array($table, $restricted)) {
            throw new Exception("Admin OPD tidak diperbolehkan import tabel master.");
        }
    }
    /* =========================================================
        VALIDASI DUPLICATE GLOBAL (SCOPE-AWARE PATCH)
        ---------------------------------------------------------
        - Composite aware
        - Scope aware (kd_wilayah + peraturan)
        - Tidak merusak arsitektur lama
        ========================================================= */
    private function validateDuplicate(string $table, array $data): void
    {
        $columns = $this->getTableColumns($table);

        $uniqueFields = [];

        // 🔥 Composite utama untuk struktur
        if (in_array('kode', $columns) && isset($data['kode'])) {

            $uniqueFields['kode'] = $data['kode'];

            if (in_array('kd_wilayah', $columns)) {

                $uniqueFields['kd_wilayah'] =
                    $data['kd_wilayah'] ?? $this->user['kd_wilayah'] ?? null;

                if (empty($uniqueFields['kd_wilayah'])) {
                    throw new Exception(
                        "kd_wilayah tidak boleh kosong untuk validasi duplicate."
                    );
                }
            }

            if (in_array('peraturan_id', $columns)) {

                $uniqueFields['peraturan_id'] =
                    $data['peraturan_id'] ?? null;

                if (empty($uniqueFields['peraturan_id'])) {
                    throw new Exception(
                        "peraturan_id tidak boleh kosong untuk validasi duplicate."
                    );
                }
            }
        }

        // 🔥 Tambahan rekening jika ada
        if (in_array('rekening', $columns) && isset($data['rekening'])) {
            $uniqueFields['rekening'] = $data['rekening'];
        }

        if (empty($uniqueFields)) return;

        $whereParts = [];
        $params     = [];

        foreach ($uniqueFields as $field => $value) {
            $whereParts[] = "`$field` = ?";
            $params[]     = $value;
        }

        $exists = $this->db->query(
            "SELECT id FROM `$table`
                WHERE " . implode(" AND ", $whereParts) . "
                LIMIT 1",
            $params
        )->fetch();

        if ($exists) {
            throw new Exception("Duplicate data terdeteksi (composite scope).");
        }
    }
    /* =========================================================
        VALIDASI HIERARKI & DEPENDENSI LINTAS TABEL
        ========================================================= */
    private function validateHierarchy(string $table, array $data): void
    {
        $rules = [

            // Struktur kode
            'bidang' => [
                'parent_table' => 'urusan',
                'match' => ['urusan_id' => 'id']
            ],
            'program' => [
                'parent_table' => 'bidang',
                'match' => ['bidang_id' => 'id']
            ],
            'kegiatan' => [
                'parent_table' => 'program',
                'match' => ['program_id' => 'id']
            ],
            'sub_kegiatan' => [
                'parent_table' => 'kegiatan',
                'match' => ['kegiatan_id' => 'id']
            ],

            // Renstra
            'renstra_neo' => [
                'parent_table' => 'periode_rpjmd',
                'match' => ['periode_id' => 'id']
            ],

            // Renja
            'renja_neo' => [
                'parent_table' => 'renstra_neo',
                'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
            ],
            'renja_p_neo' => [
                'parent_table' => 'renja_neo',
                'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
            ],

            // DPA
            'dpa_neo' => [
                'parent_table' => 'renja_neo',
                'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
            ],
            'dpppa_neo' => [
                'parent_table' => 'dpa_neo',
                'match_scope' => ['tahun', 'kd_opd', 'kd_wilayah']
            ],
        ];

        if (!isset($rules[$table])) return;

        $rule = $rules[$table];
        $parent = $rule['parent_table'];

        // Foreign key match
        if (isset($rule['match'])) {
            foreach ($rule['match'] as $childField => $parentField) {

                if (empty($data[$childField])) {
                    throw new Exception("Field $childField wajib diisi.");
                }

                $parentColumns = $this->getTableColumns($parent);

                $where = ["`$parentField` = ?"];
                $params = [$data[$childField]];

                // 🔥 Tambah scope jika ada
                if (in_array('kd_wilayah', $parentColumns)) {
                    $where[] = "`kd_wilayah` = ?";
                    $params[] = $data['kd_wilayah'] ?? $this->user['kd_wilayah'] ?? null;
                }

                if (in_array('peraturan_id', $parentColumns)) {
                    $where[] = "`peraturan_id` = ?";
                    $params[] = $data['peraturan_id'] ?? null;
                }

                $exists = $this->db->query(
                    "SELECT id FROM `$parent`
                            WHERE " . implode(" AND ", $where) . "
                            LIMIT 1",
                    $params
                )->fetch();

                if (!$exists) {
                    throw new Exception("Parent di $parent belum tersedia.");
                }
            }
        }

        // Scope match
        if (isset($rule['match_scope'])) {

            $where = [];
            $params = [];

            foreach ($rule['match_scope'] as $field) {

                if (!isset($data[$field])) {
                    throw new Exception("Field $field wajib ada.");
                }

                $where[] = "`$field` = ?";
                $params[] = $data[$field];
            }
            $parentColumns = $this->getTableColumns($parent);

            if (in_array('peraturan', $parentColumns) && isset($data['peraturan'])) {
                $where[] = "`peraturan` = ?";
                $params[] = $data['peraturan'];
            }
            $exists = $this->db->query(
                "SELECT id FROM `$parent`
                    WHERE " . implode(" AND ", $where) . "
                    LIMIT 1",
                $params
            )->fetch();

            if (!$exists) {
                throw new Exception("Parent scope di $parent belum tersedia.");
            }
        }
    }
    /* =========================================================
        IMPORT STRICT MODE (ENTERPRISE SAFE)
        ---------------------------------------------------------
        Alur besar import:
        1️⃣ Validasi profile & role
        2️⃣ Load Excel
        3️⃣ Mapping header Excel → kolom DB
        4️⃣ Loop setiap baris
        5️⃣ Resolve relation_map (text → FK)
        6️⃣ Inject auto session
        7️⃣ Validate duplicate
        8️⃣ Validate hierarchy
        9️⃣ Insert via handle('add')
        🔟 Jika ada 1 error → rollback semua
    ========================================================= */
    public function importStrict($tbl, $file, $jmlHeader = 1)
    {

        /* =====================================================
        1️⃣ VALIDASI PROFILE TABEL
        -----------------------------------------------------
        memastikan tabel yang diimport terdaftar
        di table_profiles.php
        ===================================================== */

        if (!isset($this->profiles[$tbl])) {
            throw new Exception("Profile tabel tidak ditemukan");
        }

        // ambil konfigurasi profile
        $profile = $this->profiles[$tbl];

        // nama tabel fisik di database
        $table   = $profile['table'];


        /* =====================================================
        2️⃣ AMBIL CONTEXT SESSION USER
        -----------------------------------------------------
        semua data sistem ini bersifat scope-aware
        ===================================================== */

        // tahun aktif dari session
        $tahun = $this->user['tahun'] ?? date('Y');

        // wilayah user
        $kd_wilayah = $this->user['kd_wilayah'] ?? null;

        if (!$kd_wilayah) {
            throw new Exception("kd_wilayah tidak ditemukan pada session.");
        }

        // ambil pengaturan aktif (untuk mengetahui peraturan)
        $pengaturan = $this->getPengaturanAktif();

        if (!$pengaturan) {
            throw new Exception("Pengaturan aktif belum tersedia.");
        }

        // peraturan SBU aktif
        $peraturan_id = $pengaturan['aturan_sbu'] ?? null;


        /* =====================================================
        3️⃣ LOAD FILE EXCEL
        -----------------------------------------------------
        menggunakan PhpSpreadsheet
        ===================================================== */

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);

        // hanya membaca data tanpa style
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file);

        // ubah seluruh sheet menjadi array
        $rows = $spreadsheet->getActiveSheet()->toArray();


        /* =====================================================
        4️⃣ NORMALISASI HEADER EXCEL
        -----------------------------------------------------
        header Excel → kolom database
        ===================================================== */

        $headerRow = $rows[0];

        // alias mapping header excel
        $alias = [
            'kodekelompokbarang' => 'kd_aset',
            'uraianbarang'       => 'uraian_barang',
            'spesifikasi'        => 'spesifikasi',
            'satuan'             => 'satuan',
            'hargasatuan'        => 'harga_satuan',
            'koderekening'       => 'kd_rekening'
        ];

        $headers = [];

        foreach ($headerRow as $h) {

            // normalisasi header (hapus spasi dll)
            $key = $this->normalizeForCompare($h);

            // mapping header ke kolom DB
            $headers[] = $alias[$key] ?? null;
        }


        /* =====================================================
        5️⃣ CACHE SATUAN
        -----------------------------------------------------
        agar tidak query satuan setiap baris excel
        ===================================================== */

        $satuanCache = [];

        $satuanRows = $this->db
            ->query("SELECT id,item FROM satuan_neo")
            ->fetchAll();

        foreach ($satuanRows as $s) {

            // key = nama satuan lowercase
            $satuanCache[strtolower(trim($s['item']))] = $s['id'];
        }


        /* =====================================================
        6️⃣ CACHE AKUN
        -----------------------------------------------------
        pivot menggunakan kd_akun (string)
        ===================================================== */

        $akunCache = [];

        $akunRows = $this->db
            ->query("SELECT kode FROM akun_neo")
            ->fetchAll();

        foreach ($akunRows as $a) {

            // cache kode akun
            $akunCache[$a['kode']] = $a['kode'];
        }


        /* =====================================================
        7️⃣ CACHE MAPPING REKENING → ASET
        -----------------------------------------------------
        untuk auto assign kd_aset
        ===================================================== */

        $mapCache = [];

        $mapRows = $this->db
            ->query("SELECT kd_rekening,kd_aset FROM mapping_rekening_aset")
            ->fetchAll();

        foreach ($mapRows as $m) {

            $mapCache[$m['kd_rekening']] = $m['kd_aset'];
        }


        /* =====================================================
        8️⃣ TRANSACTION IMPORT
        -----------------------------------------------------
        jika ada 1 error → rollback semua
        ===================================================== */

        return $this->runTransaction(function () use (
            $rows,
            $headers,
            $jmlHeader,
            $table,
            $kd_wilayah,
            $tahun,
            $peraturan_id,
            $satuanCache,
            $akunCache,
            $mapCache
        ) {

            foreach ($rows as $i => $row) {

                // skip header
                if ($i < $jmlHeader) continue;

                $data = [];

                /* =================================================
                MAPPING KOLOM EXCEL → ARRAY DATA
                ================================================= */

                foreach ($row as $k => $v) {

                    if (!$headers[$k]) continue;

                    $data[$headers[$k]] = trim($v);
                }

                // skip baris kosong
                if (empty($data['uraian_barang'])) continue;

                // inject scope
                $data['kd_wilayah']   = $kd_wilayah;
                $data['tahun']        = $tahun;
                $data['peraturan_id'] = $peraturan_id;


                /* =================================================
                RESOLVE SATUAN TEXT → satuan_id
                ================================================= */

                if (!empty($data['satuan'])) {

                    $key = strtolower(trim($data['satuan']));

                    if (isset($satuanCache[$key])) {

                        $data['satuan_id'] = $satuanCache[$key];
                    }

                    // hapus kolom text satuan
                    unset($data['satuan']);
                }


                /* =================================================
                CEK DUPLICATE SBU
                ================================================= */

                $exists = $this->db->query(
                    "SELECT id FROM `$table`
                    WHERE kd_wilayah = ?
                    AND tahun = ?
                    AND uraian_barang = ?
                    AND harga_satuan = ?
                    LIMIT 1",
                    [
                        $kd_wilayah,
                        $tahun,
                        $data['uraian_barang'],
                        $data['harga_satuan']
                    ]
                )->fetch();

                if ($exists) {

                    // gunakan id yang sudah ada
                    $itemId = $exists['id'];
                } else {

                    // insert data baru
                    $this->db->insert($table, $data);

                    $itemId = $this->db->lastInsertId();
                }


                /* =================================================
                SPLIT KODE REKENING
                ================================================= */

                $codes = [];

                if (!empty($data['kd_rekening'])) {

                    $codes = preg_split('/[,;\n]+/', $data['kd_rekening']);

                    $codes = array_filter($codes);
                }


                /* =================================================
                INSERT PIVOT AKUN
                ================================================= */

                foreach ($codes as $code) {

                    if (!isset($akunCache[$code])) continue;

                    $akunCode = $akunCache[$code];

                    // cek apakah pivot sudah ada
                    $pivot = $this->db->query(
                        "SELECT id FROM sbu_akun_map
                        WHERE sbu_id = ?
                        AND kd_akun = ?
                        AND kd_wilayah = ?
                        AND peraturan_id = ?
                        LIMIT 1",
                        [
                            $itemId,
                            $akunCode,
                            $kd_wilayah,
                            $peraturan_id
                        ]
                    )->fetch();

                    if (!$pivot) {

                        // insert pivot akun
                        $this->db->insert("sbu_akun_map", [
                            'sbu_id'       => $itemId,
                            'kd_akun'      => $akunCode,
                            'kd_wilayah'   => $kd_wilayah,
                            'peraturan_id' => $peraturan_id
                        ]);
                    }


                    /* =============================================
                    AUTO MAP ASET
                    ============================================= */

                    if (isset($mapCache[$code])) {

                        $this->db->update(
                            $table,
                            ['kd_aset' => $mapCache[$code]],
                            "WHERE id = ?",
                            [$itemId]
                        );
                    }
                }
            }

            // jika semua sukses
            return JsonResponse::success("Import selesai");
        });
    }
    /* =========================================================
        IMPORT STRUKTUR NASIONAL (GLOBAL HIRARKI)
        ========================================================= */
    public function importStruktur(string $filePath, int $jmlHeader = 1): string
    {
        return $this->runTransaction(function () use ($filePath, $jmlHeader) {

            // 🔹 Load file Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

            // 🔹 Ambil semua baris jadi array
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

            // 🔹 Validasi minimal baris
            if (count($rows) <= $jmlHeader) {
                throw new Exception("File kosong atau header tidak sesuai.");
            }

            $inserted = 0;

            // 🔹 Loop mulai setelah header
            foreach (array_slice($rows, $jmlHeader) as $rowIndex => $row) {

                // 🔹 Hitung nomor baris asli Excel
                $excelRow = $rowIndex + $jmlHeader + 1;

                // 🔹 Ambil kolom pertama = kode
                $kode = trim((string)($row[0] ?? ''));

                // 🔹 Ambil kolom kedua = nama
                $nama = trim((string)($row[1] ?? ''));

                // 🔹 Skip jika kosong
                if ($kode === '' || $nama === '') {
                    continue;
                }

                // 🔹 Pecah kode berdasarkan titik
                $segments = explode('.', $kode);

                // 🔹 Hitung jumlah segment
                $segmentCount = count($segments);

                // 🔹 Validasi semua segment harus angka
                foreach ($segments as $seg) {
                    if ($seg === '' || !ctype_digit($seg)) {
                        // Lompat ke baris Excel berikutnya
                        continue 2;
                    }
                }

                try {

                    switch ($segmentCount) {

                        // ==================================================
                        // 1 SEGMENT = URUSAN
                        // contoh: 1
                        // ==================================================
                        case 1:

                            $this->insertIfNotExists('urusan', [
                                'kode' => $kode,
                                'nama' => $nama
                            ]);

                            break;


                        // ==================================================
                        // 2 SEGMENT = BIDANG
                        // contoh: 1.01
                        // ==================================================
                        case 2:

                            // parent = urusan
                            $kodeUrusan = $segments[0];

                            // pastikan urusan ada
                            $this->ensureParentExists('urusan', [
                                'kode' => $kodeUrusan
                            ]);

                            $this->insertIfNotExists('bidang', [
                                'kode' => $kode,
                                'kode_urusan' => $kodeUrusan,
                                'nama' => $nama
                            ]);

                            break;


                        // ==================================================
                        // 3 SEGMENT = PROGRAM
                        // contoh: 1.01.02
                        // ==================================================
                        case 3:

                            // parent = bidang
                            $kodeBidang = implode('.', array_slice($segments, 0, 2));

                            $this->ensureParentExists('bidang', [
                                'kode' => $kodeBidang
                            ]);

                            $this->insertIfNotExists('program', [
                                'kode' => $kode,
                                'kode_urusan' => $segments[0],
                                'kode_bidang' => $kodeBidang,
                                'nama' => $nama
                            ]);

                            break;


                        // ==================================================
                        // 5 SEGMENT = KEGIATAN
                        // contoh: 1.01.02.2.01
                        // ==================================================
                        case 5:

                            // parent = program (3 segment pertama)
                            $kodeProgram = implode('.', array_slice($segments, 0, 3));

                            $this->ensureParentExists('program', [
                                'kode' => $kodeProgram
                            ]);

                            $this->insertIfNotExists('kegiatan', [
                                'kode' => $kode,
                                'kode_urusan' => $segments[0],
                                'kode_bidang' => implode('.', array_slice($segments, 0, 2)),
                                'kode_program' => $kodeProgram,
                                'nama' => $nama
                            ]);

                            break;


                        // ==================================================
                        // 6 SEGMENT = SUB KEGIATAN
                        // contoh: 1.01.02.2.01.0001
                        // ==================================================
                        case 6:

                            // parent = kegiatan (5 segment pertama)
                            $kodeKegiatan = implode('.', array_slice($segments, 0, 5));

                            $this->ensureParentExists('kegiatan', [
                                'kode' => $kodeKegiatan
                            ]);

                            $this->insertIfNotExists('sub_kegiatan', [
                                'kode' => $kode,
                                'kode_urusan' => $segments[0],
                                'kode_bidang' => implode('.', array_slice($segments, 0, 2)),
                                'kode_program' => implode('.', array_slice($segments, 0, 3)),
                                'kode_kegiatan' => $kodeKegiatan,
                                'nama' => $nama
                            ]);

                            break;


                        // ==================================================
                        // Format lain diabaikan
                        // ==================================================
                        default:
                            continue 2;
                    }

                    $inserted++;
                } catch (\Throwable $e) {

                    // 🔥 Jika error, tampilkan baris Excel
                    throw new Exception(
                        "Baris Excel {$excelRow} gagal → " . $e->getMessage()
                    );
                }
            }

            return JsonResponse::success("Import struktur berhasil.", [
                'inserted' => $inserted
            ]);
        });
    }
    private function ensureParentExists(string $table, array $data): void
    {
        $columns = $this->getTableColumns($table);

        $whereParts = ["`kode` = ?"];
        $params = [$data['kode']];

        if (in_array('kd_wilayah', $columns)) {
            $whereParts[] = "`kd_wilayah` = ?";
            $params[] = $this->user['kd_wilayah'];
        }

        if (in_array('peraturan_id', $columns)) {

            $pengaturan = $this->getPengaturanAktif();

            if (!$pengaturan) {
                throw new Exception("Pengaturan aktif belum tersedia.");
            }

            $fieldMap = [
                'urusan'       => 'aturan_sub_kegiatan',
                'bidang'       => 'aturan_sub_kegiatan',
                'program'      => 'aturan_sub_kegiatan',
                'kegiatan'     => 'aturan_sub_kegiatan',
                'sub_kegiatan' => 'aturan_sub_kegiatan',
                'ssh_neo'      => 'aturan_ssh',
                'sbu_neo'      => 'aturan_sbu',
                'asb_neo'      => 'aturan_asb',
                'hspk_neo'     => 'aturan_hspk',
            ];

            if (isset($fieldMap[$table])) {

                $field = $fieldMap[$table];

                $whereParts[] = "`peraturan_id` = ?";
                $params[] = (int)$pengaturan[$field];
            }
        }

        $exists = $this->db->query(
            "SELECT id FROM `$table`
                WHERE " . implode(" AND ", $whereParts) . "
                LIMIT 1",
            $params
        )->fetch();

        if (!$exists) {
            throw new Exception("Parent $table belum tersedia.");
        }
    }
    private function safeInsert(string $table, array $data, int $excelRow): void
    {
        try {

            $this->insertIfNotExists($table, $data);
        } catch (\Throwable $e) {

            throw new Exception(
                "Tabel {$table} gagal pada baris Excel {$excelRow} → "
                    . $e->getMessage()
            );
        }
    }
    private function insertIfNotExists(string $table, array $data): ?int
    {
        $columns = $this->getTableColumns($table);

        // 🔥 Filter hanya kolom yang benar-benar ada
        $filtered = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        if (!isset($filtered['kode'])) {
            return null;
        }

        $whereParts = ["`kode` = ?"];
        $params     = [$filtered['kode']];

        // 🔥 Scope kd_wilayah
        if (in_array('kd_wilayah', $columns)) {
            $filtered['kd_wilayah'] = $this->user['kd_wilayah'] ?? null;
            $whereParts[] = "`kd_wilayah` = ?";
            $params[] = $filtered['kd_wilayah'];
        }

        // 🔥 Scope peraturan
        if (in_array('peraturan_id', $columns)) {

            $pengaturan = $this->getPengaturanAktif();

            if (!$pengaturan || empty($pengaturan['aturan_sub_kegiatan'])) {
                throw new Exception("Peraturan aktif belum dikonfigurasi.");
            }

            $filtered['peraturan_id'] =
                (int)$pengaturan['aturan_sub_kegiatan'];

            $whereParts[] = "`peraturan_id` = ?";
            $params[] = $filtered['peraturan_id'];
        }

        // 🔥 Cek duplicate
        $exists = $this->db->query(
            "SELECT id FROM `$table`
                WHERE " . implode(" AND ", $whereParts) . "
                LIMIT 1",
            $params
        )->fetch();

        if ($exists) {
            return $exists['id'];
        }
        /* =====================================================
        ENTERPRISE SANITATION
        ===================================================== */
        $filtered = $this->applySanitization($table, $filtered);
        $filtered = $this->injectAudit($filtered, 'insert');

        $this->db->insert($table, $filtered);

        return $this->db->lastInsertId();
    }
    /* =========================================================
            VALIDASI IMPORT CONFIG DARI PROFILE
            ========================================================= */
    private function validateImportConfig(string $tableKey): array
    {
        if (!isset($this->profiles[$tableKey]['import'])) {
            throw new Exception("Import belum dikonfigurasi.");
        }

        $config = $this->profiles[$tableKey]['import'];

        if (!($config['enabled'] ?? false)) {
            throw new Exception("Import tidak diizinkan untuk tabel ini.");
        }

        $role = $this->user['type_user'] ?? 'viewer';

        if (!in_array($role, $config['allowed_roles'] ?? [])) {
            throw new Exception("Role tidak diizinkan untuk import.");
        }

        return $config;
    }
    /* =========================================================
        APPLY NORMALISASI BERDASARKAN PROFILE app/Config/table_profiles.php'
        program' => [
            'table' => 'program',
            'primary_key' => 'id',
            'normalize_space' => ['nama']
        ],
        ========================================================= */
    private function applyNormalization(string $table, array $data): array
    {
        $profile = $this->getProfileByTable($table);

        if (empty($profile['normalize_space'])) {
            return $data;
        }

        foreach ($profile['normalize_space'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = $this->normalizeSpaces($data[$field]);
            }
        }

        return $data;
    }
    /* =========================================================
        NORMALISASI SPASI GLOBAL
        ========================================================= */
    private function normalizeSpaces(string $value): string
    {
        $value = trim((string)$value);
        return preg_replace('/\s+/u', ' ', $value);
    }
    /* =========================================================
        SANITIZE SINGLE VALUE
        ========================================================= */
    private function sanitizeValue(?string $value, ?array $rules = null): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim((string)$value);

        // Hapus control characters
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        // Normalize multi space
        $value = preg_replace('/\s+/u', ' ', $value);

        // Strip HTML
        $value = strip_tags($value);

        if (!empty($rules['case'])) {
            switch ($rules['case']) {
                case 'upper':
                    $value = mb_strtoupper($value);
                    break;
                case 'lower':
                    $value = mb_strtolower($value);
                    break;
                case 'title':
                    $value = mb_convert_case($value, MB_CASE_TITLE);
                    break;
            }
        }

        return $value;
    }
    /* =========================================================
        ENTERPRISE SANITATION ENGINE $filtered = $this->applySanitization($table, $filtered);
        ========================================================= */
    private function applySanitization(string $table, array $data): array
    {
        $profile = $this->getProfileByTable($table);

        foreach ($data as $field => $value) {

            if (!is_string($value)) {
                continue;
            }

            $rules = $profile['sanitize'][$field] ?? null;

            $data[$field] = $this->sanitizeValue($value, $rules);
        }

        return $data;
    }
    private function resolveScope(
        string $table,
        array $profile,
        string $mode
    ): array {

        $modeConfig = $profile['modes'][$mode] ?? [];
        $columns = $this->getTableColumns($table);

        $where = [];
        $params = [];

        $role = $this->user['type_user'] ?? 'viewer';

        if ($role !== 'super_admin') {

            if ($role === 'admin_wilayah' && in_array('kd_wilayah', $columns)) {
                $where[] = "`kd_wilayah` = ?";
                $params[] = $this->user['kd_wilayah'];
            }

            if ($role === 'admin_opd') {

                foreach (['kd_opd', 'kd_wilayah', 'tahun'] as $field) {
                    if (in_array($field, $columns)) {
                        $where[] = "`$field` = ?";
                        $params[] = $this->user[$field] ?? null;
                    }
                }
            }
        }

        if (!empty($modeConfig['scope'])) {

            foreach ($modeConfig['scope'] as $field => $value) {

                if (!in_array($field, $columns)) continue;

                $where[] = "`$field` = ?";
                $params[] = $value === 'user'
                    ? $this->user[$field] ?? null
                    : $value;
            }
        }

        return [$where, $params];
    }
    private function resolveSearch(
        string $table,
        array $modeConfig,
        string $search
    ): array {

        if (empty($search) || empty($modeConfig['searchable'])) {
            return [[], []];
        }

        $columns = $modeConfig['searchable'] === ['*']
            ? $this->getTableColumns($table)
            : $modeConfig['searchable'];

        $where = [];
        $params = [];

        foreach ($columns as $col) {
            $where[] = "`$col` LIKE ?";
            $params[] = "%$search%";
        }

        return [
            ["(" . implode(" OR ", $where) . ")"],
            $params
        ];
    }
    private function resolveAutoFields(string $table, array $data): array
    {
        $columns = $this->getTableColumns($table);

        $autoMap = [
            'kd_wilayah',
            'kd_opd',
            'tahun'
        ];

        foreach ($autoMap as $field) {

            if (in_array($field, $columns) && isset($this->user[$field])) {
                $data[$field] = $this->user[$field];
            }
        }

        return $data;
    }
    private function resolvePeraturan(string $table, array $data): array
    {
        $columns = $this->getTableColumns($table);

        // 🔥 Gunakan peraturan_id sekarang
        $colPeraturan = null;

        if (in_array('peraturan_id', $columns)) {
            $colPeraturan = 'peraturan_id';
        }

        if (in_array('peraturan', $columns)) {
            $colPeraturan = 'peraturan';
        }

        if (!$colPeraturan) {
            return $data;
        }

        if (!empty($data[$colPeraturan])) {
            return $data;
        }

        if (!empty($data['peraturan_id'])) {
            return $data;
        }

        $pengaturan = $this->getPengaturanAktif();

        if (!$pengaturan) {
            throw new Exception("Pengaturan aktif tidak ditemukan.");
        }

        $map = [
            'urusan'       => 'aturan_sub_kegiatan',
            'bidang'       => 'aturan_sub_kegiatan',
            'program'      => 'aturan_sub_kegiatan',
            'kegiatan'     => 'aturan_sub_kegiatan',
            'sub_kegiatan' => 'aturan_sub_kegiatan',

            'satuan'     => 'aturan_ssh',
            'satuan_neo' => 'aturan_ssh',

            'ssh'        => 'aturan_ssh',
            'ssh_neo'    => 'aturan_ssh',

            'sbu'        => 'aturan_sbu',
            'sbu_neo'    => 'aturan_sbu',

            'asb'        => 'aturan_asb',
            'asb_neo'    => 'aturan_asb',

            'hspk'       => 'aturan_hspk',
            'hspk_neo'   => 'aturan_hspk',

            'akun'       => 'aturan_akun',
            'akun_neo'   => 'aturan_akun'
        ];

        if (!isset($map[$table])) {
            return $data;
        }

        $field = $map[$table];

        if (empty($pengaturan[$field])) {
            throw new Exception("Field {$field} pada pengaturan aktif kosong.");
        }

        // 🔥 CAST KE INT
        $data[$colPeraturan] = (int)$pengaturan[$field];

        return $data;
    }
    private function resolvePeriode(string $table, array $data): array
    {
        $columns = $this->getTableColumns($table);

        if (!in_array('periode_id', $columns)) {
            return $data;
        }

        if (!empty($data['periode_id'])) {
            return $data;
        }

        $periode = $this->getPeriodeAktif();

        if (!$periode) {
            throw new Exception("Periode aktif tidak ditemukan.");
        }

        $data['periode_id'] = $periode['id'];

        return $data;
    }
    private function normalizeDateTimeFields(string $table, array $data): array
    {
        $columns = $this->getTableColumns($table);

        foreach ($data as $field => $value) {

            if (!is_string($value) || $value === '') {
                continue;
            }

            // cek apakah kolom bertipe date/datetime
            if (!$this->isDateColumn($table, $field)) {
                continue;
            }

            $data[$field] = $this->normalizeToMySQLDateTime($value);
        }

        return $data;
    }
    private function isDateColumn(string $table, string $field): bool
    {
        $columns = $this->db->query(
            "SHOW COLUMNS FROM `$table` WHERE Field = ?",
            [$field]
        )->fetch();

        if (!$columns) return false;

        return str_contains($columns['Type'], 'date');
    }
    private function normalizeToMySQLDateTime(?string $value): string
    {
        // 1️⃣ dd/mm/yyyy
        if (!is_string($value) || $value === '') {
            return '';
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
            return sprintf(
                '%04d-%02d-%02d 00:00:00',
                $m[3],
                $m[2],
                $m[1]
            );
        }

        // 2️⃣ ISO date
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ' 00:00:00';
        }

        // 3️⃣ datetime without seconds
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        // 4️⃣ ISO T format
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return str_replace('T', ' ', $value) . ':00';
        }

        // 5️⃣ full datetime valid
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return $value;
    }
    // ⚠ Currently unused. Reserved for future date-range validation.
    private function validateDateRange(array $data, string $start, string $end): void
    {
        if (empty($data[$start]) || empty($data[$end])) {
            return;
        }

        $startTime = strtotime($data[$start]);
        $endTime   = strtotime($data[$end]);

        if ($startTime === false || $endTime === false) {
            throw new Exception("Format tanggal tidak valid.");
        }

        if ($startTime >= $endTime) {
            throw new Exception("$start harus lebih kecil dari $end");
        }
    }
    private function validateTimeWindow(string $table): void
    {
        $role = $this->user['type_user'] ?? 'viewer';

        // Super admin bebas
        if ($role === 'super_admin') {
            return;
        }

        $pengaturan = $this->getPengaturanAktif();

        if (!$pengaturan) {
            throw new Exception("Pengaturan aktif belum tersedia.");
        }

        $now = date('Y-m-d H:i:s');

        $map = [

            // RENSTRA
            'renstra_neo' => [
                'start' => 'awal_renstra',
                'end'   => 'akhir_renstra',
                'lock'  => 'kunci_renstra'
            ],

            // RENJA
            'renja_neo' => [
                'start' => 'awal_renja',
                'end'   => 'akhir_renja',
                'lock'  => 'kunci_renja'
            ],

            'renja_p_neo' => [
                'start' => 'awal_renja_p',
                'end'   => 'akhir_renja_p',
                'lock'  => 'kunci_renja_p'
            ],

            // DPA
            'dpa_neo' => [
                'start' => 'awal_dpa',
                'end'   => 'akhir_dpa',
                'lock'  => 'kunci_dpa'
            ],

            'dpppa_neo' => [
                'start' => 'awal_dppa',
                'end'   => 'akhir_dppa',
                'lock'  => 'kunci_dppa'
            ],

            // Paket / Realisasi
            'paket_neo' => [
                'lock' => 'kunci_paket'
            ],

            'realisasi_neo' => [
                'lock' => 'kunci_realisasi'
            ]
        ];

        if (!isset($map[$table])) {
            return; // tabel lain bebas
        }

        $config = $map[$table];

        // 🔒 CEK LOCK
        if (!empty($config['lock']) && !empty($pengaturan[$config['lock']])) {
            throw new Exception("Modul sedang dikunci.");
        }

        // ⏳ CEK PERIODE
        if (!empty($config['start']) && !empty($config['end'])) {

            $start = $pengaturan[$config['start']] ?? null;
            $end   = $pengaturan[$config['end']] ?? null;

            if (!$start || !$end) {
                throw new Exception("Periode belum dikonfigurasi.");
            }

            if ($now < $start || $now > $end) {
                throw new Exception("Di luar periode input yang diizinkan.");
            }
        }
    }
    // ======================================================
    // NORMALIZE UNTUK PERBANDINGAN HEADER
    // ======================================================
    private function normalizeForCompare(?string $value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

        $value = strtolower($value);

        return preg_replace('/[^a-z0-9]/', '', $value);
    }

    // ======================================================
    // BUILD COLUMN MAP BERDASARKAN KOLOM TABEL
    // ======================================================
    private function buildColumnMap(string $table): array
    {
        $columns = $this->getTableColumns($table);

        $map = [];
        $collision = [];

        foreach ($columns as $col) {

            $normalized = $this->normalizeForCompare($col);

            if (isset($map[$normalized])) {
                $collision[] = $col;
            } else {
                $map[$normalized] = $col;
            }
        }

        if (!empty($collision)) {
            throw new Exception(
                "Collision kolom terdeteksi di tabel {$table}."
            );
        }

        return $map;
    }
    // ======================================================
    // 🔥 RESOLVE IMPORT RELATIONS (STRICT + CACHE + MULTI MAP)
    // ------------------------------------------------------
    // - Tidak auto create
    // - Support multi relation
    // - Mengisi foreign key berdasarkan text Excel
    // - Menyimpan error per baris
    // ======================================================
    private function resolveImportRelations(
        array &$data,          // Data baris Excel (by reference)
        array $relationMap,    // Mapping relasi dari profile
        int $rowIndex,         // Index baris Excel
        array &$errorRows      // Array penampung error (by reference)
    ): void {

        // Loop semua relation_map yang didefinisikan di profile
        foreach ($relationMap as $excelField => $map) {

            // Jika kolom tidak ada atau kosong → skip
            if (empty($data[$excelField])) {
                continue;
            }

            // Ambil konfigurasi target
            $targetTable = $map['target_table'];   // contoh: satuan_neo
            $targetField = $map['target_field'];   // contoh: item
            $targetId    = $map['target_id'];      // contoh: id
            $storeAs     = $map['store_as'];       // contoh: satuan_id

            // Normalisasi value untuk lookup (case insensitive)
            $lookupValue = strtolower(trim((string)$data[$excelField]));

            // Buat cache key unik
            $cacheKey = "{$targetTable}|{$targetField}|{$lookupValue}";

            // ======================================================
            // 🔥 1️⃣ CEK CACHE DULU
            // ======================================================
            if (isset($this->relationCache[$cacheKey])) {

                // Ambil ID dari cache
                $data[$storeAs] = $this->relationCache[$cacheKey];

                // Hapus field text asli
                unset($data[$excelField]);

                continue;
            }

            // ======================================================
            // 🔥 2️⃣ QUERY DATABASE
            // ======================================================
            $found = $this->db->query(
                "SELECT `$targetId`
                FROM `$targetTable`
                WHERE LOWER(`$targetField`) = ?
                LIMIT 1",
                [$lookupValue]
            )->fetch();

            // ======================================================
            // 🔥 3️⃣ JIKA TIDAK DITEMUKAN → SIMPAN ERROR
            // ======================================================
            if (!$found) {

                $errorRows[] = [
                    'row'    => $rowIndex + 1,          // nomor baris Excel
                    'field'  => $excelField,            // nama kolom Excel
                    'value'  => $data[$excelField],     // nilai yang gagal
                    'message' => "Tidak ditemukan di tabel {$targetTable}"
                ];

                continue;
            }

            // ======================================================
            // 🔥 4️⃣ SIMPAN KE CACHE
            // ======================================================
            $this->relationCache[$cacheKey] = $found[$targetId];

            // ======================================================
            // 🔥 5️⃣ SIMPAN KE DATA FINAL
            // ======================================================
            $data[$storeAs] = $found[$targetId];

            // Hapus field text Excel agar tidak bentrok insert
            unset($data[$excelField]);
        }
    }
    // ======================================================
    // 🔥 INSERT AKUN PIVOT (GENERIC UNTUK SBU/SSH/ASB/HSPK)
    // ======================================================
    private function insertAkunPivot(
        string $tableKey,
        int $masterId,
        string $kdAkunString,
        array $data
    ): void {

        // Mapping master → pivot
        $pivotMap = [
            'sbu'  => ['table' => 'sbu_akun_map',  'fk' => 'sbu_id'],
            'ssh'  => ['table' => 'ssh_akun_map',  'fk' => 'ssh_id'],
            'asb'  => ['table' => 'asb_akun_map',  'fk' => 'asb_id'],
            'hspk' => ['table' => 'hspk_akun_map', 'fk' => 'hspk_id'],
        ];

        if (!isset($pivotMap[$tableKey])) {
            return;
        }

        $pivotTable = $pivotMap[$tableKey]['table'];
        $fkField    = $pivotMap[$tableKey]['fk'];

        // 🔥 Split berdasarkan koma
        $akunList = array_filter(array_map('trim', explode(',', $kdAkunString)));

        foreach ($akunList as $akun) {

            // Insert pivot
            $this->db->insert($pivotTable, [
                $fkField        => $masterId,
                'kd_akun'       => $akun,
                'kd_wilayah'    => $data['kd_wilayah'],
                'peraturan_id'  => $data['peraturan_id']
            ]);
        }
    }
    // ======================================================
    // 🔥 VALIDATE AKUN MAPPING (GENERIC UNTUK SBU/SSH/ASB/HSPK)
    // ------------------------------------------------------
    // Digunakan saat Renja/DPA menggunakan master biaya
    // Agar akun belanja sesuai mapping pivot
    // ======================================================
    private function validateAkunMapping(
        string $table,
        array $data
    ): void {

        // Mapping foreign key → pivot table
        $map = [
            'sbu_id'  => ['pivot' => 'sbu_akun_map',  'fk' => 'sbu_id'],
            'ssh_id'  => ['pivot' => 'ssh_akun_map',  'fk' => 'ssh_id'],
            'asb_id'  => ['pivot' => 'asb_akun_map',  'fk' => 'asb_id'],
            'hspk_id' => ['pivot' => 'hspk_akun_map', 'fk' => 'hspk_id'],
        ];

        foreach ($map as $foreignKey => $config) {

            // Jika tabel ini tidak menggunakan foreign key tersebut → skip
            if (empty($data[$foreignKey])) {
                continue;
            }

            // kd_akun wajib ada untuk validasi
            if (empty($data['kd_akun'])) {
                throw new Exception("kd_akun wajib diisi untuk validasi mapping.");
            }

            $pivotTable = $config['pivot'];
            $fkField    = $config['fk'];

            // ======================================================
            // 🔥 CEK EXIST DI PIVOT
            // ======================================================
            $exists = $this->db->query(
                "SELECT id
                FROM `$pivotTable`
                WHERE `$fkField` = ?
                AND `kd_akun` = ?
                AND `kd_wilayah` = ?
                AND `peraturan_id` = ?
                LIMIT 1",
                [
                    $data[$foreignKey],
                    $data['kd_akun'],
                    $data['kd_wilayah'] ?? $this->user['kd_wilayah'] ?? null,
                    $data['peraturan_id'] ?? null
                ]
            )->fetch();

            if (!$exists) {
                throw new Exception(
                    "Mapping akun tidak ditemukan untuk {$foreignKey} dengan kd_akun {$data['kd_akun']}."
                );
            }
        }
    }
}
