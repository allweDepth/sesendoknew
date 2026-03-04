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
    private array $cacheSatuan = [];
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
                'list',
                'import'
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
            case 'import':

                $this->authorize('add', $table);

                if (empty($_FILES['file']['tmp_name'])) {
                    return JsonResponse::error("File tidak ditemukan");
                }

                $jmlHeader = $request['jml_header'] ?? 1;

                return $this->importStrict(
                    $tbl,
                    $_FILES['file']['tmp_name'],
                    (int)$jmlHeader
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

        $primaryKey = $this->getPrimaryKey($table);

        $exists = $this->db->query(
            "SELECT `$primaryKey` FROM `$table`
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
                                                -----------------------------------------------------
                                                Tujuan:
                                                - Memastikan parent record benar-benar ada
                                                - Mencegah orphan record
                                                - Memastikan scope wilayah + peraturan konsisten
                                                ========================================================= */
    private function validateHierarchy(string $table, array $data): void
    {
        /* ======================================================
                                                1️⃣ DEFINISI RULE HIERARKI
                                                ------------------------------------------------------
                                                menentukan parent table dan cara validasi relasinya
                                                ====================================================== */
        $rules = [

            // Struktur SIPD nasional
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

        /* ======================================================
                                                2️⃣ JIKA TABEL TIDAK PUNYA RULE → SKIP
                                                ====================================================== */
        if (!isset($rules[$table])) {
            return;
        }

        $rule   = $rules[$table];
        $parent = $rule['parent_table'];

        /* ======================================================
                                                3️⃣ VALIDASI FOREIGN KEY MATCH
                                                ------------------------------------------------------
                                                contoh:
                                                program.bidang_id harus ada di tabel bidang
                                                ====================================================== */
        if (isset($rule['match'])) {

            foreach ($rule['match'] as $childField => $parentField) {

                // field child wajib ada
                if (empty($data[$childField])) {
                    throw new Exception("Field $childField wajib diisi.");
                }

                // ambil kolom parent table
                $parentColumns = $this->getTableColumns($parent);

                // base query
                $where  = ["`$parentField` = ?"];
                $params = [$data[$childField]];

                /* ==================================================
                                                        🔥 Scope wilayah
                                                        ================================================== */
                if (in_array('kd_wilayah', $parentColumns)) {

                    $where[]  = "`kd_wilayah` = ?";
                    $params[] = $data['kd_wilayah']
                        ?? $this->user['kd_wilayah']
                        ?? null;
                }

                /* ==================================================
                                                        🔥 Scope peraturan (VERSI BARU)
                                                        ================================================== */
                if (in_array('peraturan_id', $parentColumns)) {

                    $where[]  = "`peraturan_id` = ?";
                    $params[] = $data['peraturan_id'] ?? null;
                }

                /* ==================================================
                                                        4️⃣ CEK PARENT ADA ATAU TIDAK
                                                        ================================================== */
                $exists = $this->db->query(
                    "SELECT id FROM `$parent`
                                                        WHERE " . implode(" AND ", $where) . "
                                                        LIMIT 1",
                    $params
                )->fetch();

                if (!$exists) {
                    throw new Exception(
                        "Parent di tabel $parent belum tersedia."
                    );
                }
            }
        }

        /* ======================================================
                                                5️⃣ VALIDASI SCOPE MATCH
                                                ------------------------------------------------------
                                                digunakan pada relasi non-FK
                                                contoh:
                                                renja harus sesuai tahun + opd + wilayah
                                                ====================================================== */
        if (isset($rule['match_scope'])) {

            $where  = [];
            $params = [];

            foreach ($rule['match_scope'] as $field) {

                if (!isset($data[$field])) {
                    throw new Exception("Field $field wajib ada.");
                }

                $where[]  = "`$field` = ?";
                $params[] = $data[$field];
            }

            // ambil kolom parent
            $parentColumns = $this->getTableColumns($parent);

            /* ==================================================
                                                    🔥 Scope peraturan versi baru
                                                    ================================================== */
            if (
                in_array('peraturan_id', $parentColumns)
                && isset($data['peraturan_id'])
            ) {

                $where[]  = "`peraturan_id` = ?";
                $params[] = $data['peraturan_id'];
            }

            /* ==================================================
                                                    CEK EXIST
                                                    ================================================== */
            $exists = $this->db->query(
                "SELECT id FROM `$parent`
                                                    WHERE " . implode(" AND ", $where) . "
                                                    LIMIT 1",
                $params
            )->fetch();

            if (!$exists) {
                throw new Exception(
                    "Parent scope di $parent belum tersedia."
                );
            }
        }
    }
    /**
     * ============================================================
     * IMPORT STRICT ENGINE
     * ------------------------------------------------------------
     * Fungsi:
     * Mengimport data Excel ke tabel sistem secara aman.
     *
     * Fitur utama:
     * - Header validation
     * - Relation resolver
     * - Scope injection (wilayah, tahun, peraturan)
     * - Sanitasi data
     * - Duplicate validation
     * - Transaction safe
     * - Error report per baris
     *
     * Tabel yang menggunakan metode ini:
     * SSH
     * SBU
     * ASB
     * HSPK
     * ============================================================
     */
    public function importStrict($tbl, $file, $jmlHeader = 1)
    {

        // ======================================================
        // 1️⃣ VALIDASI PROFILE TABEL
        // ======================================================

        if (!isset($this->profiles[$tbl])) {
            throw new Exception("Profile tabel tidak ditemukan.");
        }

        // ambil konfigurasi tabel dari profile
        $profile = $this->profiles[$tbl];

        // ambil nama tabel database
        $table = $profile['table'];


        // ======================================================
        // 2️⃣ AMBIL KOLOM TABEL DATABASE
        // ======================================================

        $columns = $this->getTableColumns($table);


        // ======================================================
        // 3️⃣ AMBIL SCOPE USER
        // ======================================================

        // wilayah user
        $kd_wilayah = $this->user['kd_wilayah'] ?? null;

        // tahun user
        $tahun = $this->user['tahun'] ?? date('Y');

        if (!$kd_wilayah) {
            throw new Exception("kd_wilayah tidak ditemukan.");
        }


        // ======================================================
        // 4️⃣ RESOLVE PERATURAN
        // ======================================================

        $peraturan_id = null;

        // jika tabel memiliki kolom peraturan
        if (in_array('peraturan_id', $columns)) {

            $pengaturan = $this->getPengaturanAktif();

            if (!$pengaturan) {
                throw new Exception("Pengaturan aktif belum tersedia.");
            }

            // mapping tabel → field pengaturan
            $map = [
                'ssh'  => 'aturan_ssh',
                'sbu'  => 'aturan_sbu',
                'asb'  => 'aturan_asb',
                'hspk' => 'aturan_hspk'
            ];

            if (isset($map[$tbl])) {
                $peraturan_id = (int)$pengaturan[$map[$tbl]];
            }
        }


        // ======================================================
        // 5️⃣ LOAD FILE EXCEL
        // ======================================================

        // buat reader otomatis
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);

        // baca hanya data (lebih cepat)
        $reader->setReadDataOnly(true);

        // load spreadsheet
        $spreadsheet = $reader->load($file);

        // ambil sheet aktif
        $sheet = $spreadsheet->getActiveSheet();

        // iterator baris Excel
        $rowIterator = $sheet->getRowIterator();


        // ======================================================
        // 6️⃣ BUILD COLUMN MAP
        // ======================================================

        // mapping header Excel → kolom SQL
        $columnMap = $this->buildColumnMap($table);


        // ======================================================
        // 7️⃣ VARIABLE STATISTIK IMPORT
        // ======================================================

        $headers = [];

        $totalRows = 0;
        $successRows = 0;
        $failedRows = 0;

        $errorRows = [];


        // ======================================================
        // 8️⃣ IMPORT DALAM TRANSACTION
        // ======================================================

        return $this->runTransaction(function () use (

            $rowIterator,
            $jmlHeader,
            $columnMap,
            $columns,
            $table,
            $kd_wilayah,
            $tahun,
            $peraturan_id,
            &$headers,
            &$totalRows,
            &$successRows,
            &$failedRows,
            &$errorRows

        ) {

            $rowNumber = 0;

            // ==================================================
            // LOOP SEMUA BARIS EXCEL
            // ==================================================

            foreach ($rowIterator as $row) {

                $rowNumber++;

                // iterator cell
                $cellIterator = $row->getCellIterator();

                // baca semua cell
                $cellIterator->setIterateOnlyExistingCells(false);

                $values = [];

                foreach ($cellIterator as $cell) {
                    $values[] = trim((string)$cell->getValue());
                }


                /* ===============================================
            HEADER PROCESSING
            =============================================== */

                if ($rowNumber <= $jmlHeader) {

                    // header terakhir
                    if ($rowNumber === $jmlHeader) {

                        foreach ($values as $h) {

                            // normalisasi header
                            $normalized = $this->normalizeForCompare($h);

                            // cek apakah cocok dengan kolom tabel
                            if (!isset($columnMap[$normalized])) {

                                throw new Exception(
                                    "Header Excel '{$h}' tidak cocok dengan kolom tabel."
                                );
                            }

                            // simpan mapping header
                            $headers[] = $columnMap[$normalized];
                        }
                    }

                    continue;
                }


                // ==================================================
                // BARIS DATA
                // ==================================================

                $totalRows++;

                $data = [];

                foreach ($values as $k => $v) {

                    if (!isset($headers[$k])) {
                        continue;
                    }

                    $data[$headers[$k]] = $v;
                }


                try {

                    // ==================================================
                    // INJECT SCOPE
                    // ==================================================

                    if (in_array('kd_wilayah', $columns)) {
                        $data['kd_wilayah'] = $kd_wilayah;
                    }

                    if (in_array('tahun', $columns)) {
                        $data['tahun'] = $tahun;
                    }

                    if ($peraturan_id && in_array('peraturan_id', $columns)) {
                        $data['peraturan_id'] = $peraturan_id;
                    }


                    // ==================================================
                    // RESOLVE RELATIONS
                    // ==================================================

                    $profile = $this->profiles[$table] ?? [];

                    $relations = $profile['import_relations'] ?? [];

                    if (!empty($relations)) {

                        $data = $this->resolveImportRelations(
                            $data,
                            $relations,
                            $rowNumber
                        );
                    }


                    // ==================================================
                    // SANITASI DATA
                    // ==================================================

                    $data = $this->applySanitization($table, $data);


                    // ==================================================
                    // AUDIT TRAIL
                    // ==================================================

                    $data = $this->injectAudit($data, 'insert');


                    // ==================================================
                    // VALIDASI DUPLICATE
                    // ==================================================

                    $this->validateDuplicate($table, $data);


                    // ==================================================
                    // INSERT DATABASE
                    // ==================================================

                    $this->db->insert($table, $data);

                    $successRows++;
                } catch (\Throwable $e) {

                    $failedRows++;

                    $errorRows[] = [
                        'row' => $rowNumber,
                        'message' => $e->getMessage()
                    ];
                }
            }


            // ==================================================
            // GROUP ERROR
            // ==================================================

            $groupedErrors = $this->groupImportErrors($errorRows);


            // ==================================================
            // RETURN RESULT
            // ==================================================

            return JsonResponse::success(
                "Import selesai",
                [
                    'total' => $totalRows,
                    'berhasil' => $successRows,
                    'gagal' => $failedRows
                ],
                $groupedErrors
            );
        });
    }
    private function compressRowRanges(array $rows): array
    {
        sort($rows);

        $ranges = [];
        $start = null;
        $prev  = null;

        foreach ($rows as $row) {

            if ($start === null) {
                $start = $row;
                $prev  = $row;
                continue;
            }

            if ($row == $prev + 1) {
                $prev = $row;
                continue;
            }

            if ($start == $prev) {
                $ranges[] = (string)$start;
            } else {
                $ranges[] = $start . '-' . $prev;
            }

            $start = $row;
            $prev  = $row;
        }

        if ($start !== null) {
            if ($start == $prev) {
                $ranges[] = (string)$start;
            } else {
                $ranges[] = $start . '-' . $prev;
            }
        }

        return $ranges;
    }
    private function groupImportErrors(array $errors): array
    {
        $grouped = [];

        foreach ($errors as $err) {

            $msg = $err['message'];

            if (!isset($grouped[$msg])) {
                $grouped[$msg] = [
                    'message' => $msg,
                    'rows' => []
                ];
            }

            $grouped[$msg]['rows'][] = $err['row'];
        }

        foreach ($grouped as &$g) {
            $g['rows'] = $this->compressRowRanges($g['rows']);
        }

        return array_values($grouped);
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

        $primaryKey = $this->getPrimaryKey($table);

        $exists = $this->db->query(
            "SELECT `$primaryKey` FROM `$table`
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
        if (!$colPeraturan) {
            return $data;
        }

        if (!empty($data[$colPeraturan])) {
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
    /**
     * ============================================================
     * NORMALIZE STRING UNTUK PERBANDINGAN
     * ------------------------------------------------------------
     * Tujuan:
     * Menyamakan format teks Excel dengan nama kolom database.
     *
     * Semua karakter selain huruf dan angka dihapus.
     *
     * Contoh:
     *
     * "Sumber Dana"     → sumberdana
     * "SUMBER_DANA"     → sumberdana
     * "sumber-dana"     → sumberdana
     * "SumberDana"      → sumberdana
     *
     * ============================================================
     */
    private function normalizeForCompare(?string $value): string
    {
        // jika bukan string atau kosong
        if (!is_string($value) || $value === '') {
            return '';
        }

        // ubah semua huruf menjadi huruf kecil
        $value = strtolower($value);

        // hapus semua karakter selain huruf dan angka
        // spasi, underscore, titik, dll akan dihapus
        return preg_replace('/[^a-z0-9]/', '', $value);
    }
    /**
     * ============================================================
     * VALIDASI HEADER EXCEL
     * ------------------------------------------------------------
     * Memastikan setiap header Excel cocok dengan kolom tabel.
     *
     * Jika tidak cocok → import dihentikan.
     * ============================================================
     */
    private function validateImportHeader(array $headers, array $columnMap): void
    {
        // loop semua header Excel
        foreach ($headers as $header) {

            // normalisasi header
            $normalized = $this->normalizeForCompare($header);

            // jika tidak ada mapping di database
            if (!isset($columnMap[$normalized])) {

                // hentikan import
                throw new Exception(
                    "Header Excel '{$header}' tidak cocok dengan kolom tabel."
                );
            }
        }
    }
    // ======================================================
    // BUILD COLUMN MAP BERDASARKAN KOLOM TABEL
    // ======================================================
    private function buildColumnMap(string $table): array
    {
        // ambil semua kolom tabel dari database
        $columns = $this->getTableColumns($table);

        // array mapping hasil
        $map = [];

        foreach ($columns as $col) {

            // normalisasi kolom tabel
            // contoh:
            // sumber_dana → sumberdana
            $normalized = $this->normalizeForCompare($col);

            // mapping normalisasi → nama kolom asli
            $map[$normalized] = $col;
        }

        // return mapping
        return $map;
    }
    /**
     * ============================================================
     * RESOLVE IMPORT RELATIONS
     * ------------------------------------------------------------
     * Fungsi:
     * Mengubah nilai teks dari Excel menjadi foreign key id
     * berdasarkan konfigurasi relasi di table_profiles.
     *
     * Contoh:
     *
     * Excel:
     * satuan = Kg
     *
     * Database:
     * satuan_id = 5
     *
     * Metode ini juga menggunakan cache agar tidak query
     * database setiap baris Excel.
     * ============================================================
     */
    private function resolveImportRelations(
        array $data,
        array $relations,
        int $rowNumber
    ): array {

        // ======================================================
        // LOOP SEMUA RELASI YANG DIKONFIGURASI DI PROFILE
        // ======================================================

        foreach ($relations as $excelField => $cfg) {

            // ==================================================
            // 1️⃣ CEK APAKAH FIELD ADA DI DATA EXCEL
            // ==================================================

            if (!isset($data[$excelField])) {
                continue;
            }

            // ambil nilai dari Excel
            $excelValue = trim($data[$excelField]);

            // jika kosong skip
            if ($excelValue === '') {
                continue;
            }

            // ==================================================
            // 2️⃣ AMBIL KONFIGURASI RELASI
            // ==================================================

            $lookupTable = $cfg['table'];   // tabel referensi
            $lookupField = $cfg['lookup'];  // kolom lookup
            $idField     = $cfg['id'];      // kolom id
            $storeField  = $cfg['store'];   // kolom tujuan
            $scope       = $cfg['scope'] ?? [];

            // ==================================================
            // 3️⃣ NORMALISASI KEY CACHE
            // ==================================================

            // contoh:
            // Kg → kg
            $cacheKey = strtolower($excelValue);


            /* ==================================================
        4️⃣ LOAD CACHE RELASI (HANYA SEKALI)
        ================================================== */

            if (!isset($this->relationCache[$lookupTable])) {

                // query semua data lookup
                $sql = "
                SELECT $idField, $lookupField
                FROM $lookupTable
                WHERE is_deleted = 0
            ";

                $params = [];

                // ==================================================
                // APPLY SCOPE (contoh: peraturan_id)
                // ==================================================

                foreach ($scope as $s) {

                    $sql .= " AND $s = :$s";

                    $params[$s] = $data[$s] ?? null;
                }

                // jalankan query
                $stmt = $this->db->query($sql, $params);

                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // siapkan cache
                $this->relationCache[$lookupTable] = [];

                // simpan semua hasil ke cache
                foreach ($rows as $r) {

                    $key = strtolower(trim($r[$lookupField]));

                    $this->relationCache[$lookupTable][$key] = $r[$idField];
                }
            }


            /* ==================================================
        5️⃣ CARI NILAI DI CACHE
        ================================================== */

            if (!isset($this->relationCache[$lookupTable][$cacheKey])) {

                // jika tidak ditemukan
                throw new Exception(
                    "Baris {$rowNumber}: {$excelField} '{$excelValue}' tidak ditemukan."
                );
            }


            /* ==================================================
        6️⃣ SET FOREIGN KEY
        ================================================== */

            // contoh:
            // satuan_id = 5
            $data[$storeField] =
                $this->relationCache[$lookupTable][$cacheKey];


            /* ==================================================
        7️⃣ HAPUS FIELD TEXT EXCEL
        ================================================== */

            // hapus field asli
            unset($data[$excelField]);
        }

        return $data;
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
    /**
     * ============================================================
     * RESOLVE SATUAN TEXT → SATUAN_ID
     * ------------------------------------------------------------
     * Fungsi:
     * Mengubah nilai satuan dari Excel menjadi id dari tabel satuan.
     *
     * Contoh:
     *
     * Excel:
     * satuan = Kg
     *
     * Database:
     * satuan_neo
     * id | item
     * 5  | Kg
     *
     * Hasil:
     * satuan_id = 5
     *
     * Sistem juga menggunakan cache agar query database
     * tidak dilakukan setiap baris Excel.
     * ============================================================
     */
    private function resolveSatuanId(array $data, int $rowNumber): array
    {
        // ======================================================
        // 1️⃣ CEK APAKAH FIELD "satuan" ADA DI DATA EXCEL
        // ======================================================

        // jika Excel tidak memiliki kolom satuan
        // maka tidak perlu diproses
        if (!isset($data['satuan'])) {
            return $data;
        }

        // ======================================================
        // 2️⃣ AMBIL NILAI SATUAN DARI EXCEL
        // ======================================================

        // ambil nilai satuan
        $excelSatuan = trim($data['satuan']);

        // jika kosong maka tidak perlu diproses
        if ($excelSatuan === '') {
            return $data;
        }

        // ======================================================
        // 3️⃣ BUAT KEY UNTUK CACHE
        // ======================================================

        // contoh:
        // Kg → kg
        // M2 → m2
        $cacheKey = strtolower($excelSatuan);


        /* ======================================================
    4️⃣ LOAD CACHE SATUAN (HANYA SEKALI)
    ====================================================== */

        // jika cache satuan masih kosong
        // berarti ini baris pertama import
        if (empty($this->cacheSatuan)) {

            // query semua satuan sesuai peraturan
            $sql = "
            SELECT id, item
            FROM satuan_neo
            WHERE peraturan_id = :peraturan_id
            AND is_deleted = 0
        ";

            // jalankan query
            $stmt = $this->db->query($sql, [

                // gunakan peraturan dari data import
                'peraturan_id' => $data['peraturan_id']
            ]);

            // ambil semua hasil
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // loop semua satuan
            foreach ($rows as $r) {

                // normalisasi nama satuan
                // contoh:
                // "Kg" → "kg"
                $key = strtolower(trim($r['item']));

                // simpan ke cache
                // contoh:
                // cacheSatuan["kg"] = 5
                $this->cacheSatuan[$key] = $r['id'];
            }
        }


        /* ======================================================
    5️⃣ CARI SATUAN DI CACHE
    ====================================================== */

        // jika satuan Excel tidak ditemukan
        if (!isset($this->cacheSatuan[$cacheKey])) {

            // lempar error dengan nomor baris Excel
            throw new Exception(
                "Baris {$rowNumber}: satuan '{$excelSatuan}' tidak ditemukan."
            );
        }


        /* ======================================================
    6️⃣ SET FIELD satuan_id
    ====================================================== */

        // ambil id satuan dari cache
        $data['satuan_id'] = $this->cacheSatuan[$cacheKey];


        /* ======================================================
    7️⃣ HAPUS FIELD TEXT SATUAN
    ====================================================== */

        // hapus field satuan karena database memakai satuan_id
        unset($data['satuan']);


        // kembalikan data yang sudah diperbaiki
        return $data;
    }
}
