<?php

namespace app;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : 3306) . ";dbname=" . DB_DATABASE;
        try {
            $this->pdo = new \PDO($dsn, DB_USERNAME, DB_PASSWORD);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            // tampilkan pesan error (development). Di production, log saja.
            echo "Database connection error: " . $e->getMessage();
            exit;
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance->pdo;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
