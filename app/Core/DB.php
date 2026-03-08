<?php

class DB
{
    // ======================================================
    // INSTANCE SINGLETON
    // ======================================================
    private static $instance = null;

    // ======================================================
    // OBJECT PDO
    // ======================================================
    private $pdo;

    // ======================================================
    // JUMLAH ROW TERPENGARUH QUERY
    // ======================================================
    private $count = 0;

    // ======================================================
    // LAST INSERT ID
    // ======================================================
    private $lastInsertId = 0;

    // ======================================================
    // QUERY BUILDER STORAGE
    // ======================================================
    private $qb = [];

    // ======================================================
    // CONSTRUCTOR PRIVATE
    // agar hanya bisa dibuat lewat getInstance()
    // ======================================================
    private function __construct()
    {
        // ==================================================
        // LOAD KONFIGURASI DATABASE
        // ==================================================
        $config = require __DIR__ . '/../../config/database.php';

        try {

            // ==================================================
            // CEK SOCKET MYSQL OTOMATIS
            // ==================================================

            $socket = $config['socket'] ?? null;

            /* ===============================
AUTO DETECT SOCKET
=============================== */

            if (!$socket) {

                $possibleSockets = [
                    '/run/mysqld/mysqld.sock',
                    '/run/mysqld/mysqld10.sock',
                    '/var/run/mysqld/mysqld.sock'
                ];

                foreach ($possibleSockets as $s) {

                    if (file_exists($s)) {
                        $socket = $s;
                        break;
                    }
                }
            }

            // jika socket tidak di set tapi file socket ada
            if (!$socket && file_exists('/run/mysqld/mysqld.sock')) {
                $socket = '/run/mysqld/mysqld.sock';
            }

            // jika socket Synology
            if ($socket) {
                $dsn = "mysql:unix_socket={$socket};dbname={$config['dbname']};charset=utf8mb4";
            } else {
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
            }

            // ==================================================
            // JIKA SOCKET ADA → PAKAI SOCKET
            // ==================================================

            if ($socket) {

                $dsn = "mysql:unix_socket={$socket};dbname={$config['dbname']};charset=utf8mb4";
            }

            // ==================================================
            // JIKA TIDAK → PAKAI HOST
            // ==================================================

            else {

                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
            }

            // ==================================================
            // BUAT OBJECT PDO
            // ==================================================
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [

                    // ==================================================
                    // THROW EXCEPTION JIKA ERROR SQL
                    // ==================================================
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // ==================================================
                    // DEFAULT FETCH MODE ARRAY ASSOCIATIVE
                    // ==================================================
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                    // ==================================================
                    // NONAKTIFKAN EMULATE PREPARE
                    // agar prepared statement native mysql
                    // ==================================================
                    PDO::ATTR_EMULATE_PREPARES => false,

                ]
            );
        } catch (PDOException $e) {

            // ==================================================
            // JIKA KONEKSI GAGAL
            // ==================================================
            die("Database Error: " . $e->getMessage());
        }
    }

    // ======================================================
    // SINGLETON INSTANCE
    // ======================================================
    public static function getInstance()
    {
        if (!self::$instance) {

            // buat instance baru
            self::$instance = new DB();
        }

        return self::$instance;
    }

    /* ======================================================
       CORE QUERY
    ====================================================== */

    public function query($sql, $params = [])
    {
        // ==================================================
        // PREPARE QUERY
        // ==================================================
        $stmt = $this->pdo->prepare($sql);

        // ==================================================
        // EXECUTE QUERY DENGAN PARAMETER
        // ==================================================
        $stmt->execute($params);

        // ==================================================
        // SIMPAN JUMLAH ROW TERPENGARUH
        // ==================================================
        $this->count = $stmt->rowCount();

        // ==================================================
        // DETEKSI QUERY INSERT
        // ==================================================
        if (stripos(trim($sql), 'insert') === 0) {

            // simpan last insert id
            $this->lastInsertId = $this->pdo->lastInsertId();
        }

        // ==================================================
        // RETURN PDOStatement
        // agar bisa fetch(), fetchAll(), dll
        // ==================================================
        return $stmt;
    }

    // ======================================================
    // SELECT ALL DATA
    // ======================================================
    public function get($table, $where = "", $params = [])
    {
        // ==================================================
        // VALIDASI NAMA TABEL
        // ==================================================
        $this->validateTable($table);

        $sql = "SELECT * FROM {$table} {$where}";

        return $this->query($sql, $params)->fetchAll();
    }

    // ======================================================
    // SELECT COLUMN CUSTOM
    // ======================================================
    public function select($table, $columns = '*', $where = '', $params = [])
    {
        // ==================================================
        // VALIDASI NAMA TABEL
        // ==================================================
        $this->validateTable($table);
        $sql = "SELECT {$columns} FROM {$table} {$where}";

        return $this->query($sql, $params)->fetchAll();
    }

    // ======================================================
    // AMBIL SATU DATA
    // ======================================================
    public function first($table, $where = "", $params = [])
    {
        $sql = "SELECT * FROM {$table} {$where} LIMIT 1";

        return $this->query($sql, $params)->fetch();
    }

    // ======================================================
    // INSERT DATA
    // ======================================================
    // ======================================================
    // INSERT DATA (SAFE VERSION)
    // ======================================================
    public function insert($table, $data)
    {
        /* =========================================
    VALIDASI NAMA TABEL
    ========================================= */

        $this->validateTable($table);

        /* =========================================
    VALIDASI DATA
    ========================================= */

        if (empty($data)) {
            throw new Exception("Insert data kosong.");
        }

        /* =========================================
    BUILD QUERY INSERT
    ========================================= */

        $columns = implode(",", array_keys($data));

        $placeholders = implode(
            ",",
            array_fill(0, count($data), "?")
        );

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        /* =========================================
    EXECUTE QUERY
    ========================================= */

        $this->query($sql, array_values($data));

        /* =========================================
    RETURN INSERT ID
    ========================================= */

        return $this->lastInsertId;
    }

    // ======================================================
    // UPDATE DATA
    // ======================================================
    public function update($table, $data, $where, $paramsWhere)
    {
        // ==================================================
        // VALIDASI NAMA TABEL
        // ==================================================
        $this->validateTable($table);
        // buat set kolom
        $set = implode(",", array_map(fn($col) => "{$col}=?", array_keys($data)));

        // query update
        $sql = "UPDATE {$table} SET {$set} {$where}";

        // gabungkan parameter
        $params = array_merge(array_values($data), $paramsWhere);

        // jalankan query
        $this->query($sql, $params);

        // return jumlah row terpengaruh
        return $this->count;
    }

    // ======================================================
    // DELETE DATA
    // ======================================================
    public function delete($table, $where, $params)
    {
        // ==================================================
        // VALIDASI NAMA TABEL
        // ==================================================
        $this->validateTable($table);
        $sql = "DELETE FROM {$table} {$where}";

        $this->query($sql, $params);

        return $this->count;
    }

    // ======================================================
    // AMBIL JUMLAH ROW TERPENGARUH
    // ======================================================
    public function count()
    {
        return $this->count;
    }

    // ======================================================
    // AMBIL LAST INSERT ID
    // ======================================================
    public function lastInsertId()
    {
        // simpan id
        $id = $this->lastInsertId;

        // reset agar tidak reuse
        $this->lastInsertId = 0;

        return $id;
    }

    /* ======================================================
       TRANSACTION HELPER
    ====================================================== */

    public function begin()
    {
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        $this->pdo->commit();
    }

    public function rollback()
    {
        $this->pdo->rollBack();
    }

    /* ======================================================
       MULTI QUERY EXECUTOR
       DIGUNAKAN UNTUK RESTORE SQL
    ====================================================== */

    public function execMulti($sql)
    {
        // ==================================================
        // SPLIT QUERY BERDASARKAN ;
        // ==================================================
        $queries = array_filter(array_map('trim', explode(";", $sql)));

        foreach ($queries as $query) {

            // ==================================================
            // EKSEKUSI SATU PER SATU
            // ==================================================
            $this->pdo->exec($query);
        }

        return true;
    }

    /* ======================================================
       QUERY BUILDER
    ====================================================== */

    public function table($table)
    {
        $this->qb = [
            'table' => $table,
            'select' => '*',
            'join' => [],
            'where' => [],
            'params' => [],
            'order' => '',
            'limit' => ''
        ];

        return $this;
    }

    public function selectQB($columns = '*')
    {
        $this->qb['select'] = $columns;

        return $this;
    }

    public function join($table, $condition, $type = 'LEFT')
    {
        $this->qb['join'][] = "{$type} JOIN {$table} ON {$condition}";

        return $this;
    }

    public function where($condition, $params = [])
    {
        $this->qb['where'][] = $condition;

        $this->qb['params'] = array_merge($this->qb['params'], $params);

        return $this;
    }

    public function orderBy($order)
    {
        $this->qb['order'] = "ORDER BY {$order}";

        return $this;
    }

    public function limit($limit)
    {
        $this->qb['limit'] = "LIMIT {$limit}";

        return $this;
    }

    public function qbGet()
    {
        // ==================================================
        // BANGUN QUERY SELECT
        // ==================================================
        $sql = "SELECT {$this->qb['select']} FROM {$this->qb['table']}";

        // ==================================================
        // JOIN CLAUSE
        // ==================================================
        if (!empty($this->qb['join'])) {
            $sql .= " " . implode(" ", $this->qb['join']);
        }

        // ==================================================
        // WHERE CLAUSE
        // ==================================================
        if (!empty($this->qb['where'])) {
            $sql .= " WHERE " . implode(" AND ", $this->qb['where']);
        }

        // ==================================================
        // ORDER BY
        // ==================================================
        if ($this->qb['order']) {
            $sql .= " " . $this->qb['order'];
        }

        // ==================================================
        // LIMIT
        // ==================================================
        if ($this->qb['limit']) {
            $sql .= " " . $this->qb['limit'];
        }

        // ==================================================
        // EKSEKUSI QUERY
        // ==================================================
        $result = $this->query($sql, $this->qb['params'])->fetchAll();

        // ==================================================
        // RESET QUERY BUILDER
        // ==================================================
        $this->qb = [];

        return $result;
    }

    public function qbFirst()
    {
        // ==================================================
        // SET LIMIT 1
        // ==================================================
        $this->qb['limit'] = "LIMIT 1";

        // ==================================================
        // EKSEKUSI QUERY
        // ==================================================
        $result = $this->qbGet();

        // ==================================================
        // AMBIL BARIS PERTAMA
        // ==================================================
        return $result[0] ?? null;
    }
    private function validateTable($table)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception("Invalid table name");
        }
    }
}
