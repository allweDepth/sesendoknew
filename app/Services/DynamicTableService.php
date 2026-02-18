<?php

require_once __DIR__ . '/JsonResponse.php';

/**
 * ============================================================
 * DYNAMIC TABLE SERVICE
 * ============================================================
 *
 * Engine CRUD dinamis berbasis konfigurasi profile tabel.
 * Mendukung:
 * - Insert
 * - Update
 * - Delete
 * - List + Pagination + Search
 *
 * Fitur keamanan:
 * - Auto filter berdasarkan session user (kd_wilayah, kd_opd, tahun)
 * - Tidak bisa akses data lintas OPD/tahun
 * - Auto audit field
 *
 * Arsitektur:
 * - Reusable user scope filter (applyUserScope)
 * - Dynamic column detection (SHOW COLUMNS)
 * - Fully profile-driven
 *
 * ============================================================
 */

class DynamicTableService
{
    /* =========================================================
       PROPERTIES
    ========================================================= */

    private DB $db;              // Instance koneksi database (Singleton)
    private array $profiles;     // Konfigurasi semua tabel (table_profiles.php)
    private array $user;         // Data user login dari session


    /* =========================================================
       CONSTRUCTOR
       - Load DB
       - Load konfigurasi tabel
       - Ambil data user dari session
    ========================================================= */

    public function __construct()
    {
        $this->db = DB::getInstance(); // Ambil koneksi database
        $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
        $this->user = $_SESSION['user'] ?? []; // User login aktif
    }


    /* =========================================================
       HANDLE REQUEST (Router Utama)
       - Menentukan aksi berdasarkan parameter 'jenis'
    ========================================================= */

    public function handle(array $request): string
    {
        $tbl   = $request['tbl']   ?? '';
        $jenis = $request['jenis'] ?? 'default';

        // Validasi tabel dikirim
        if (!$tbl) {
            return JsonResponse::error('Tabel tidak dikirim');
        }

        // Validasi tabel terdaftar di profile
        if (!isset($this->profiles[$tbl])) {
            return JsonResponse::error('Tabel tidak terdaftar');
        }

        $profile = $this->profiles[$tbl];
        $table   = $profile['table'];

        // Routing berdasarkan jenis aksi
        if ($jenis === 'add') {
            return $this->insert($table, $request);
        }

        if ($jenis === 'edit' && !empty($request['id'])) {
            return $this->update($table, $request);
        }

        if ($jenis === 'delete' && !empty($request['id_row'])) {
            return $this->delete($table, $profile, (int)$request['id_row']);
        }

        // Default: tampilkan data
        return $this->buildQuery($table, $profile, $request);
    }


    /* =========================================================
       AMBIL STRUKTUR KOLOM TABEL
       Digunakan untuk:
       - Filter field valid saat insert/update
       - Cek apakah tabel punya kolom scope
    ========================================================= */

    private function getTableColumns(string $table): array
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM `$table`");

        $columns = [];

        foreach ($stmt->fetchAll() as $col) {
            $columns[] = $col['Field'];
        }

        return $columns;
    }


    /* =========================================================
       USER DATA SCOPE (Reusable Filter Engine)
       =========================================================
       Membatasi data berdasarkan:
       - kd_wilayah
       - kd_opd
       - tahun
       Jika kolom ada di tabel dan ada di session.
    ========================================================= */

    private function applyUserScope(string $table): array
    {
        $columns = $this->getTableColumns($table);

        $whereParts = [];
        $params     = [];

        // Mapping field yang di-scope
        $mapping = [
            'kd_wilayah' => $this->user['kd_wilayah'] ?? null,
            'kd_opd'     => $this->user['kd_opd'] ?? null,
            'tahun'      => $this->user['tahun'] ?? null,
        ];

        foreach ($mapping as $field => $value) {

            // Hanya filter jika:
            // - Kolom ada di tabel
            // - Session punya nilai
            if (in_array($field, $columns) && !empty($value)) {
                $whereParts[] = "`$field` = ?";
                $params[] = $value;
            }
        }

        return [$whereParts, $params];
    }


    /* =========================================================
       INSERT DATA DINAMIS
       =========================================================
       - Filter hanya kolom valid
       - Auto inject kd_wilayah, kd_opd, tahun
       - Auto audit field
    ========================================================= */

    private function insert(string $table, array $request): string
    {
        $columns = $this->getTableColumns($table);
        $filtered = [];

        // Filter hanya field yang memang ada di tabel
        foreach ($request as $key => $value) {

            if (in_array($key, ['jenis', 'tbl'])) continue;

            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        /* -------------------------------
           AUTO INJECT USER SCOPE FIELD
        ------------------------------- */

        $mapping = [
            'kd_wilayah' => $this->user['kd_wilayah'] ?? null,
            'kd_opd'     => $this->user['kd_opd'] ?? null,
            'tahun'      => $this->user['tahun'] ?? null,
        ];

        foreach ($mapping as $field => $value) {

            if (in_array($field, $columns) && !isset($filtered[$field])) {

                if (empty($value)) {
                    return JsonResponse::error("Session $field tidak ditemukan");
                }

                $filtered[$field] = $value;
            }
        }

        /* -------------------------------
           AUTO AUDIT FIELD
        ------------------------------- */

        if (in_array('tgl_insert', $columns)) {
            $filtered['tgl_insert'] = date('Y-m-d H:i:s');
        }

        if (in_array('username_insert', $columns)) {
            $filtered['username_insert'] = $this->user['username'] ?? 'system';
        }

        if (in_array('disable', $columns) && !isset($filtered['disable'])) {
            $filtered['disable'] = 0;
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa disimpan");
        }

        $this->db->insert($table, $filtered);

        return JsonResponse::success("Data berhasil disimpan");
    }


    /* =========================================================
       UPDATE DATA DINAMIS
       =========================================================
       - Filter kolom valid
       - Auto update audit field
       - Scoped (tidak bisa update lintas OPD/tahun)
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

        // Auto audit update
        if (in_array('tgl_update', $columns)) {
            $filtered['tgl_update'] = date('Y-m-d H:i:s');
        }

        if (in_array('username_update', $columns)) {
            $filtered['username_update'] = $this->user['username'] ?? 'system';
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa diupdate");
        }

        // Terapkan user scope
        list($scopeWhere, $scopeParams) = $this->applyUserScope($table);

        $whereClause = "WHERE id = ?";

        if (!empty($scopeWhere)) {
            $whereClause .= " AND " . implode(" AND ", $scopeWhere);
        }

        $params = array_merge([$id], $scopeParams);

        $this->db->update($table, $filtered, $whereClause, $params);

        return JsonResponse::success("Data berhasil diupdate");
    }


    /* =========================================================
       DELETE DATA
       Scoped (tidak bisa hapus lintas OPD/tahun)
    ========================================================= */

    private function delete(string $table, array $profile, int $id): string
    {
        $primaryKey = $profile['primary_key'] ?? 'id';

        list($scopeWhere, $scopeParams) = $this->applyUserScope($table);

        $whereClause = "WHERE `$primaryKey` = ?";

        if (!empty($scopeWhere)) {
            $whereClause .= " AND " . implode(" AND ", $scopeWhere);
        }

        $params = array_merge([$id], $scopeParams);

        $this->db->delete($table, $whereClause, $params);

        return JsonResponse::success('Data berhasil dihapus');
    }


    /* =========================================================
       BUILD QUERY (LIST + PAGINATION + SEARCH)
    ========================================================= */

    private function buildQuery(
        string $table,
        array $profile,
        array $request
    ): string {

        $limit  = max(1, (int)($request['rows'] ?? 10));
        $page   = max(1, (int)($request['halaman'] ?? 1));
        $search = trim($request['cari'] ?? '');
        $offset = ($page - 1) * $limit;

        $whereParts = [];
        $params     = [];

        /* -------------------------------
           APPLY USER SCOPE FILTER
        ------------------------------- */

        list($userWhere, $userParams) = $this->applyUserScope($table);

        $whereParts = array_merge($whereParts, $userWhere);
        $params     = array_merge($params, $userParams);

        /* -------------------------------
           SEARCH FILTER
        ------------------------------- */

        if ($search !== '' && !empty($profile['searchable'])) {

            $searchParts = [];

            foreach ($profile['searchable'] as $field) {
                $searchParts[] = "`$field` LIKE ?";
                $params[] = "%$search%";
            }

            $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        $where = !empty($whereParts)
            ? 'WHERE ' . implode(' AND ', $whereParts)
            : '';

        /* -------------------------------
           TOTAL COUNT QUERY
        ------------------------------- */

        $totalQuery = "SELECT COUNT(*) as total FROM `$table` $where";
        $totalRow = $this->db->query($totalQuery, $params)->fetch()['total'] ?? 0;

        /* -------------------------------
           DATA QUERY
        ------------------------------- */

        $dataQuery = "
            SELECT *
            FROM `$table`
            $where
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
}