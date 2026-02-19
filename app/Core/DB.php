<?php

class DB
{
    private static $instance = null;
    private $pdo;
    private $count = 0;
    private $lastInsertId = 0;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        try {
            // $this->pdo = new PDO(
            //     "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            //     $config['username'],
            //     $config['password'],
            //     [
            //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            //         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            //     ]
            // );
            //GANTI MENJADI INI (PAKAI SOCKET SYNology) yesss jalan di synologi sebelumnya error "ERROR 2002 (HY000): Can't connect to server on '127.0.0.1' (115)"
            $this->pdo = new PDO(
                "mysql:unix_socket=/run/mysqld/mysqld10.sock;dbname={$config['dbname']};charset=utf8mb4",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new DB();
        }
        return self::$instance;
    }

    /* ===============================
       CORE QUERY
    =============================== */

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->count = $stmt->rowCount();
        $this->lastInsertId = $this->pdo->lastInsertId();
        return $stmt;
    }

    public function get($table, $where = "", $params = [])
    {
        $sql = "SELECT * FROM {$table} {$where}";
        return $this->query($sql, $params)->fetchAll();
    }
    public function select($table, $columns = '*', $where = '', $params = [])
    {
        $sql = "SELECT {$columns} FROM {$table} {$where}";
        return $this->query($sql, $params)->fetchAll();
    }
    public function first($table, $where = "", $params = [])
    {
        $sql = "SELECT * FROM {$table} {$where} LIMIT 1";
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data)
    {
        $columns = implode(",", array_keys($data));
        $placeholders = implode(",", array_fill(0, count($data), "?"));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->lastInsertId;
    }

    public function update($table, $data, $where, $paramsWhere)
    {
        $set = implode(",", array_map(fn($col) => "{$col}=?", array_keys($data)));

        $sql = "UPDATE {$table} SET {$set} {$where}";
        $params = array_merge(array_values($data), $paramsWhere);

        $this->query($sql, $params);
        return $this->count;
    }

    public function delete($table, $where, $params)
    {
        $sql = "DELETE FROM {$table} {$where}";
        $this->query($sql, $params);
        return $this->count;
    }

    public function count()
    {
        return $this->count;
    }

    public function lastInsertId()
    {
        return $this->lastInsertId;
    }
    /* ===============================
       MINI QUERY BUILDER
    =============================== */

    private $qb = [];

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
        $sql = "SELECT {$this->qb['select']} FROM {$this->qb['table']}";

        if (!empty($this->qb['join'])) {
            $sql .= " " . implode(" ", $this->qb['join']);
        }

        if (!empty($this->qb['where'])) {
            $sql .= " WHERE " . implode(" AND ", $this->qb['where']);
        }

        if ($this->qb['order']) {
            $sql .= " " . $this->qb['order'];
        }

        if ($this->qb['limit']) {
            $sql .= " " . $this->qb['limit'];
        }

        return $this->query($sql, $this->qb['params'])->fetchAll();
    }

    public function qbFirst()
    {
        $this->qb['limit'] = "LIMIT 1";

        $sql = "SELECT {$this->qb['select']} FROM {$this->qb['table']}";

        if (!empty($this->qb['join'])) {
            $sql .= " " . implode(" ", $this->qb['join']);
        }

        if (!empty($this->qb['where'])) {
            $sql .= " WHERE " . implode(" AND ", $this->qb['where']);
        }

        if ($this->qb['order']) {
            $sql .= " " . $this->qb['order'];
        }

        if ($this->qb['limit']) {
            $sql .= " " . $this->qb['limit'];
        }

        $result = $this->query($sql, $this->qb['params'])->fetchAll();

        return $result[0] ?? null;
    }
}
