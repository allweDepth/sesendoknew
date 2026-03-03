<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/DB.php';

class ResetTabelController extends Controller
{
    private function checkAuth()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }
    }

    public function index()
    {
        $this->checkAuth();

        $db = DB::getInstance();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $this->view('reset_tabel/index', [
            'tables' => $tables,
            'role'   => $_SESSION['type_user'] ?? null
        ], 'app');
    }

    public function reset()
    {
        $this->checkAuth();

        $table = $_POST['table'] ?? null;
        if (!$table) exit;

        $db = DB::getInstance();
        $role = $_SESSION['type_user'] ?? null;
        $kd_wilayah = $_SESSION['kd_wilayah'] ?? null;

        if ($role === 'super_admin') {

            $db->query("SET FOREIGN_KEY_CHECKS=0");
            $db->query("TRUNCATE TABLE `$table`");
            $db->query("SET FOREIGN_KEY_CHECKS=1");

        } elseif ($role === 'admin_wilayah') {

            $colCheck = $db->query("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'kd_wilayah'
            ", [$table])->fetch();

            if ($colCheck['cnt'] > 0) {
                $db->delete($table, "WHERE kd_wilayah = ?", [$kd_wilayah]);
            }
        }

        echo json_encode(['success' => true]);
    }

    public function backup()
    {
        $this->checkAuth();

        if ($_SESSION['type_user'] ?? null !== 'super_admin') exit;

        $db = DB::getInstance();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $sql = "";

        foreach ($tables as $table) {

            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql .= $create['Create Table'] . ";\n\n";

            $rows = $db->query("SELECT * FROM `$table`")->fetchAll();

            foreach ($rows as $row) {
                $values = array_map(function($v){
                    if ($v === null) return "NULL";
                    return "'" . addslashes($v) . "'";
                }, array_values($row));

                $sql .= "INSERT INTO `$table` VALUES (" . implode(",", $values) . ");\n";
            }

            $sql .= "\n\n";
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=backup_'.date('Ymd_His').'.sql');
        echo $sql;
        exit;
    }

    public function restore()
    {
        $this->checkAuth();

        if ($_SESSION['role'] !== 'super_admin') exit;
        if (!isset($_FILES['file'])) exit;

        $sql = file_get_contents($_FILES['file']['tmp_name']);

        $db = DB::getInstance();

        $db->query("SET FOREIGN_KEY_CHECKS=0");
        $db->query($sql);
        $db->query("SET FOREIGN_KEY_CHECKS=1");

        echo json_encode(['success' => true]);
    }
}