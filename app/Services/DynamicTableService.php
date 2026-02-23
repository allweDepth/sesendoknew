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
       MAIN HANDLER (ENTRY POINT)
    ========================================================= */
    public function handle(array $request): string
    {
        try {

            $action = $request['action'] ?? '';
            $module = $request['module'] ?? '';
            $tbl    = $request['tbl'] ?? '';

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

            if ($action === 'add') {
                $this->authorize('add', $table);
                return $this->insert($table, $request);
            }

            if ($action === 'edit') {

                if (!empty($request['id_row']) && count($request) <= 4) {
                    $this->authorize('view', $table);
                    return $this->getById($table, $request['id_row']);
                }

                if (!empty($request['id'])) {
                    $this->authorize('edit', $table);
                    return $this->update($table, $request);
                }

                return JsonResponse::error("ID tidak ditemukan");
            }

            if ($action === 'delete' && !empty($request['id_row'])) {
                $this->authorize('delete', $table);
                return $this->delete($table, $profile, $request['id_row']);
            }

            if ($action === 'export') {
                $this->authorize('view', $table);
                return $this->export($table, $profile, $request, 'default');
            }

            $this->authorize('view', $table);
            $mode = $action ?: 'default';

            return $this->buildQuery($table, $profile, $request, $mode);
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage());
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
    /* =========================================================
   INSERT (FIXED STABLE VERSION v3.1)
========================================================= */
    private function insert(string $table, array $request): string
    {
        $columns  = $this->getTableColumns($table);
        $filtered = [];
        /* =====================================================
        FILTER FIELD SESUAI KOLOM TABEL
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
       NORMALISASI CHECKBOX / BOOLEAN
    ===================================================== */
        foreach ($columns as $col) {

            if (!isset($filtered[$col])) continue;

            if ($filtered[$col] === 'on') {
                $filtered[$col] = 1;
            }
        }
        /* =====================================================
   NORMALISASI FORMAT DATE (DD/MM/YYYY → YYYY-MM-DD)
===================================================== */

        foreach ($columns as $col) {

            if (!isset($filtered[$col])) continue;

            // cek apakah kolom bertipe date
            $schema = $this->buildRulesFromSchema($table);

            if (isset($schema[$col])) {

                $columnInfo = $this->db->query("SHOW COLUMNS FROM `$table` LIKE ?", [$col])->fetch();
                $type = $columnInfo['Type'] ?? '';

                if (str_contains($type, 'date')) {

                    $value = $filtered[$col];

                    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {

                        $parts = explode('/', $value);

                        $filtered[$col] = sprintf(
                            '%04d-%02d-%02d',
                            $parts[2],
                            $parts[1],
                            $parts[0]
                        );
                    }
                }
            }
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
       AUTO INJECT USER SCOPE (LEBIH AMAN)
    ===================================================== */
        $userScopeMapping = [
            'kd_wilayah' => $this->user['kd_wilayah'] ?? null,
            'kd_opd'     => $this->user['kd_opd'] ?? null,
            'tahun'      => $this->user['tahun'] ?? null,
        ];

        foreach ($userScopeMapping as $field => $value) {

            if (in_array($field, $columns) && $value !== null) {
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
   AUTO DEFAULT SYSTEM FIELDS (STRICT MODE)
   HARD STOP PRODUCTION SAFE
===================================================== */

        if (in_array('disable', $columns) && !isset($filtered['disable'])) {
            $filtered['disable'] = 0;
        }

        if (in_array('is_deleted', $columns) && !isset($filtered['is_deleted'])) {
            $filtered['is_deleted'] = 0;
        }

        /* =====================================================
   STRICT PERATURAN VALIDATION
===================================================== */

        if (in_array('peraturan', $columns) && !isset($filtered['peraturan'])) {

            $pengaturan = $this->getPengaturanAktif();

            if (!$pengaturan) {
                return JsonResponse::error("Pengaturan aktif tidak ditemukan.");
            }

            // 🔥 Tentukan field aturan
            if (in_array($table, [
                'urusan',
                'bidang',
                'program',
                'kegiatan',
                'sub_kegiatan'
            ])) {

                $aturanField = 'aturan_sub_kegiatan';
            } else {

                $map = [
                    'ssh_neo'     => 'aturan_ssh',
                    'sbu_neo'     => 'aturan_sbu',
                    'asb_neo'     => 'aturan_asb',
                    'hspk_neo'    => 'aturan_hspk',
                    'akun_neo'    => 'aturan_akun',
                    'satuan_neo'  => 'aturan_ssh'
                ];

                if (!isset($map[$table])) {
                    return JsonResponse::error(
                        "Mapping peraturan untuk tabel '{$table}' belum dikonfigurasi."
                    );
                }

                $aturanField = $map[$table];
            }

            if (empty($pengaturan[$aturanField])) {
                return JsonResponse::error(
                    "Field '{$aturanField}' pada pengaturan aktif belum diisi."
                );
            }

            $filtered['peraturan'] = $pengaturan[$aturanField];
        }
        /* =====================================================
       AUDIT TRAIL (PINDAH SEBELUM VALIDASI)
    ===================================================== */
        $filtered = $this->injectAudit($filtered, 'insert');

        /* =====================================================
       VALIDASI HYBRID
    ===================================================== */
        $profile = $this->getProfileByTable($table);
        $errors  = $this->validate($filtered, $table, $profile);

        if (!empty($errors)) {
            return JsonResponse::error("Validation gagal", 422, $errors);
        }

        /* =====================================================
       FINAL INSERT
    ===================================================== */
        // $this->db->insert($table, $filtered);

        // return JsonResponse::success("Data berhasil disimpan");

        /* =====================================================
        FINAL INSERT + AUDIT + TRANSACTION
        ===================================================== */
        return $this->runTransaction(function () use ($table, $filtered) {

            $this->db->insert($table, $filtered);

            $id = $this->db->lastInsertId();

            $this->logActivity($table, $id, 'insert', null, $filtered);

            return JsonResponse::success("Data berhasil disimpan");
        });
    }
    /* =========================================================
       UPDATE (FULL IDENTIK LOGIC ASLI)
    ========================================================= */
    /* =========================================================
   UPDATE (FIXED STABLE VERSION v3.1)
========================================================= */
    private function update(string $table, array $request): string
    {
        $columns     = $this->getTableColumns($table);
        $primaryKey  = $this->getPrimaryKey($table);

        $id = $request['id'] ?? null;
        if (!$id) {
            return JsonResponse::error("ID tidak ditemukan");
        }

        unset($request['id']);

        $filtered = [];

        /* =====================================================
       FILTER FIELD SESUAI KOLOM
    ===================================================== */
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
       NORMALISASI CHECKBOX / BOOLEAN
    ===================================================== */
        foreach ($columns as $col) {

            if (!isset($filtered[$col])) continue;

            if ($filtered[$col] === 'on') {
                $filtered[$col] = 1;
            }
        }

        /* =====================================================
       AUTO INJECT USER SCOPE (OPSIONAL SAFE OVERRIDE)
       Supaya user tidak bisa ubah kd_opd/kd_wilayah manual
    ===================================================== */
        $userScopeMapping = [
            'kd_wilayah' => $this->user['kd_wilayah'] ?? null,
            'kd_opd'     => $this->user['kd_opd'] ?? null,
            'tahun'      => $this->user['tahun'] ?? null,
        ];

        foreach ($userScopeMapping as $field => $value) {

            if (in_array($field, $columns) && $value !== null) {
                $filtered[$field] = $value;
            }
        }

        /* =====================================================
       AUDIT TRAIL (PINDAH SEBELUM VALIDASI)
    ===================================================== */
        $filtered = $this->injectAudit($filtered, 'update');
        /* =====================================================
   AUTO PRESERVE REQUIRED COLUMNS (FIX 422 UPDATE)
===================================================== */

        $oldRow = $this->db->query(
            "SELECT * FROM `$table` WHERE `$primaryKey` = ?",
            [$id]
        )->fetch();

        if ($oldRow) {

            foreach ($oldRow as $field => $value) {

                // Jika field tidak dikirim saat update
                if (!isset($filtered[$field])) {

                    $filtered[$field] = $value;
                }
            }
        }
        /* =====================================================
       VALIDASI HYBRID
    ===================================================== */
        $profile = $this->getProfileByTable($table);
        $errors  = $this->validate($filtered, $table, $profile, $id);

        if (!empty($errors)) {
            return JsonResponse::error("Validation gagal", 422, $errors);
        }

        /* =====================================================
       CEK AKSES BERDASARKAN SCOPE
    ===================================================== */
        if (!$this->checkAccess($table, $id)) {
            return JsonResponse::error("Tidak memiliki akses untuk update data ini");
        }

        /* =====================================================
        FINAL UPDATE
        ===================================================== */
        // $this->db->update(
        //     $table,
        //     $filtered,
        //     "WHERE `$primaryKey` = ?",
        //     [$id]
        // );
        // return JsonResponse::success("Data berhasil diupdate");
        /* =====================================================
        BACKUP EDIT
        ===================================================== */
        /* =====================================================
   SMART DIFF + TRANSACTION + AUDIT
===================================================== */
        $oldData = $this->db->query(
            "SELECT * FROM `$table` WHERE `$primaryKey` = ?",
            [$id]
        )->fetch();

        if (!$oldData) {
            return JsonResponse::error("Data tidak ditemukan");
        }

        $diff = [];

        foreach ($filtered as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] != $value) {
                $diff[$key] = $value;
            }
        }

        if (empty($diff)) {
            return JsonResponse::success("Tidak ada perubahan");
        }

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
    private function normalizeHeader(string $header): string
    {
        return strtolower(
            preg_replace('/[^a-z0-9]+/i', '_', trim($header))
        );
    }
    /* =========================================================
   VALIDASI IMPORT PERMISSION
========================================================= */
    private function validateImportPermission(string $table): void
    {
        $role = $this->user['type_user'] ?? 'viewer';

        $restricted = [
            'urusan_neo',
            'bidang_neo',
            'program_neo',
            'kegiatan_neo',
            'sub_kegiatan_neo'
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

            if (in_array('peraturan', $columns)) {

                $uniqueFields['peraturan'] =
                    $data['peraturan'] ?? null;

                if (empty($uniqueFields['peraturan'])) {
                    throw new Exception(
                        "peraturan tidak boleh kosong untuk validasi duplicate."
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
            'bidang_neo' => [
                'parent_table' => 'urusan_neo',
                'match' => ['urusan_id' => 'id']
            ],
            'program_neo' => [
                'parent_table' => 'bidang_neo',
                'match' => ['bidang_id' => 'id']
            ],
            'kegiatan_neo' => [
                'parent_table' => 'program_neo',
                'match' => ['program_id' => 'id']
            ],
            'sub_kegiatan_neo' => [
                'parent_table' => 'kegiatan_neo',
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

                if (in_array('peraturan', $parentColumns)) {
                    $where[] = "`peraturan` = ?";
                    $params[] = $data['peraturan'] ?? null;
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
   IMPORT STRICT MODE (NO PARTIAL INSERT)
========================================================= */
    /* =========================================================
   IMPORT STRICT MODE (ENTERPRISE SAFE)
   ---------------------------------------------------------
   - Role aware
   - Config aware
   - Duplicate aware
   - Hierarchy aware
   - Soft lock aware
   - Auto session aware
   - Strict fail (1 error stop)
========================================================= */
    public function importStrict(string $tableKey, string $filePath, int $jmlHeader = 1): string
    {
        if (!isset($this->profiles[$tableKey])) {
            return JsonResponse::error("Tabel tidak terdaftar.");
        }

        $profile = $this->profiles[$tableKey];
        $table   = $profile['table'];

        $config = $this->validateImportConfig($tableKey);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) <= $jmlHeader) {
            return JsonResponse::error("File kosong atau header tidak sesuai.");
        }

        $headers = array_map([$this, 'normalizeHeader'], $rows[$jmlHeader - 1]);

        return $this->runTransaction(function () use (
            $rows,
            $headers,
            $jmlHeader,
            $profile,
            $table,
            $config,
            $tableKey
        ) {

            $inserted = 0;

            foreach (array_slice($rows, $jmlHeader) as $rowIndex => $row) {

                if (empty(array_filter($row))) continue;

                $data = [];

                foreach ($headers as $i => $col) {
                    if (!empty($col)) {
                        $data[$col] = $row[$i] ?? null;
                    }
                }

                if (!empty($profile['auto_session'])) {
                    foreach ($profile['auto_session'] as $field) {
                        if (!isset($data[$field]) && isset($this->user[$field])) {
                            $data[$field] = $this->user[$field];
                        }
                    }
                }

                if (($config['check_duplicate'] ?? false)) {
                    $this->validateDuplicate($table, $data);
                }

                if (($config['check_hierarchy'] ?? false)) {
                    $this->validateHierarchy($table, $data);
                }

                $response = $this->handle(array_merge(
                    $data,
                    [
                        'action' => 'add',
                        'tbl'    => $tableKey
                    ]
                ));

                $decoded = json_decode($response, true);

                if (empty($decoded['success'])) {
                    throw new Exception(
                        "Error pada baris ke " . ($rowIndex + 1)
                    );
                }

                $inserted++;
            }

            return JsonResponse::success("Import berhasil.", [
                'inserted' => $inserted
            ]);
        });
    }
    /* =========================================================
   IMPORT STRUKTUR NASIONAL (GLOBAL HIRARKI)
========================================================= */
    public function importStruktur(string $filePath): string
    {
        return $this->runTransaction(function () use ($filePath) {

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

            if (count($rows) < 2) {
                throw new Exception("File kosong.");
            }

            $headers = array_map([$this, 'normalizeHeader'], $rows[0]);

            foreach (array_slice($rows, 1) as $row) {

                if (empty(array_filter($row))) continue;

                $data = [];

                foreach ($headers as $i => $col) {
                    $data[$col] = $row[$i] ?? null;
                }

                // ===============================
                // 1️⃣ URUSAN
                // ===============================
                $this->insertIfNotExists('urusan_neo', [
                    'kode' => $data['kode_urusan'] ?? null,
                    'nama' => $data['nama_urusan'] ?? null
                ]);

                // ===============================
                // 2️⃣ BIDANG
                // ===============================
                $bidangId = $this->insertIfNotExists('bidang_neo', [
                    'kode' => $data['kode_bidang'] ?? null,
                    'nama' => $data['nama_bidang'] ?? null
                ]);

                // ===============================
                // 3️⃣ PROGRAM
                // ===============================
                $programId = $this->insertIfNotExists('program_neo', [
                    'kode' => $data['kode_program'] ?? null,
                    'nama' => $data['nama_program'] ?? null
                ]);

                // ===============================
                // 4️⃣ KEGIATAN
                // ===============================
                $kegiatanId = $this->insertIfNotExists('kegiatan_neo', [
                    'kode' => $data['kode_kegiatan'] ?? null,
                    'nama' => $data['nama_kegiatan'] ?? null
                ]);

                // ===============================
                // 5️⃣ SUB KEGIATAN
                // ===============================
                $this->insertIfNotExists('sub_kegiatan_neo', [
                    'kode' => $data['kode_sub_kegiatan'] ?? null,
                    'nama' => $data['nama_sub_kegiatan'] ?? null
                ]);
            }

            return JsonResponse::success("Import struktur berhasil.");
        });
    }
    private function insertIfNotExists(string $table, array $data): ?int
    {
        $columns = $this->getTableColumns($table);

        if (!in_array('kode', $columns) || empty($data['kode'])) {
            return null;
        }

        $exists = $this->db->query(
            "SELECT id FROM `$table` WHERE kode = ? LIMIT 1",
            [$data['kode']]
        )->fetch();

        if ($exists) {
            return $exists['id'];
        }

        $data = $this->injectAudit($data, 'insert');

        $this->db->insert($table, $data);

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
}
