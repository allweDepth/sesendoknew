<?php

require_once __DIR__ . '/JsonResponse.php';

class DynamicTableService
{
    /* =========================================================
       PROPERTIES
    ========================================================= */

    private DB $db;              // Instance database
    private array $profiles;     // Konfigurasi tabel
    private array $user;         // Data user login dari session


    /* =========================================================
       CONSTRUCTOR
       - Load DB
       - Load table profiles
       - Ambil user dari session
    ========================================================= */

    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->profiles = require __DIR__ . '/../Config/table_profiles.php';
        $this->user = $_SESSION['user'] ?? [];
    }


    /* =========================================================
       HANDLE REQUEST
       - Router utama berdasarkan jenis (add/edit/delete/list)
    ========================================================= */

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

        if ($jenis === 'add') {
            return $this->insert($table, $request);
        }

        if ($jenis === 'edit' && !empty($request['id'])) {
            return $this->update($table, $request);
        }

        if ($jenis === 'delete' && !empty($request['id_row'])) {
            return $this->delete($table, $profile, (int)$request['id_row']);
        }

        return $this->buildQuery($table, $profile, $request);
    }


    /* =========================================================
       AMBIL STRUKTUR KOLOM TABEL
       - Digunakan untuk filter kolom valid
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
       INSERT DATA DINAMIS
       - Filter hanya kolom valid
       - Auto inject kd_wilayah, kd_opd
       - Auto audit fields
    ========================================================= */

    private function insert(string $table, array $request): string
    {
        $columns = $this->getTableColumns($table);

        $filtered = [];

        /* -------------------------------
           FILTER FIELD VALID
        ------------------------------- */

        foreach ($request as $key => $value) {

            if (in_array($key, ['jenis', 'tbl'])) continue;

            if (in_array($key, $columns)) {
                $filtered[$key] = $value;
            }
        }

        /* -------------------------------
   AUTO INJECT SYSTEM FIELD (SAFE)
------------------------------- */

        $user = $this->user ?? [];

        /* kd_wilayah */
        if (in_array('kd_wilayah', $columns) && !isset($filtered['kd_wilayah'])) {

            if (!empty($user['kd_wilayah'])) {
                $filtered['kd_wilayah'] = $user['kd_wilayah'];
            } else {
                return JsonResponse::error("Session kd_wilayah tidak ditemukan");
            }
        }

        /* kd_opd */
        if (in_array('kd_opd', $columns) && !isset($filtered['kd_opd'])) {

            if (!empty($user['kd_opd'])) {
                $filtered['kd_opd'] = $user['kd_opd'];
            } else {
                return JsonResponse::error("Session kd_opd tidak ditemukan");
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
       - Filter kolom valid
       - Auto update audit field
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

        /* -------------------------------
           AUTO UPDATE FIELD
        ------------------------------- */

        if (in_array('tgl_update', $columns)) {
            $filtered['tgl_update'] = date('Y-m-d H:i:s');
        }

        if (in_array('username_update', $columns)) {
            $filtered['username_update'] = $this->user['username'] ?? 'system';
        }

        if (empty($filtered)) {
            return JsonResponse::error("Tidak ada data yang bisa diupdate");
        }

        $this->db->update($table, $filtered, "WHERE id = ?", [$id]);

        return JsonResponse::success("Data berhasil diupdate");
    }


    /* =========================================================
       DELETE DATA
    ========================================================= */

    private function delete(string $table, array $profile, int $id): string
    {
        $primaryKey = $profile['primary_key'] ?? 'id';

        $this->db->delete($table, "WHERE `$primaryKey` = ?", [$id]);

        return JsonResponse::success('Data berhasil dihapus');
    }


    /* =========================================================
       BUILD QUERY (LIST DATA + PAGINATION + SEARCH)
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
        $params = [];

        /* -------------------------------
           FILTER BY USER (AUTO)
        ------------------------------- */

        $columns = $this->getTableColumns($table);

        if (in_array('kd_wilayah', $columns) && isset($this->user['kd_wilayah'])) {
            $whereParts[] = "kd_wilayah = ?";
            $params[] = $this->user['kd_wilayah'];
        }

        if (in_array('kd_opd', $columns) && isset($this->user['kd_opd'])) {
            $whereParts[] = "kd_opd = ?";
            $params[] = $this->user['kd_opd'];
        }

        /* -------------------------------
           SEARCH
        ------------------------------- */

        if ($search !== '' && !empty($profile['searchable'])) {

            $searchParts = [];

            foreach ($profile['searchable'] as $field) {
                $searchParts[] = "`$field` LIKE ?";
                $params[] = "%$search%";
            }

            $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        $where = '';

        if (!empty($whereParts)) {
            $where = 'WHERE ' . implode(' AND ', $whereParts);
        }

        /* -------------------------------
           TOTAL COUNT
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
