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
}
