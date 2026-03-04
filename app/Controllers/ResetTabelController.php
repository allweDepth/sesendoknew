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

    /*
    |--------------------------------------------------------------------------
    | TABEL YANG BOLEH DIRESET
    |--------------------------------------------------------------------------
    */

    private $allowedTables = [
        'satuan_neo',
        'urusan',
        'bidang',
        'program',
        'kegiatan',
        'sub_kegiatan',
        'rekanan_neo',
        'organisasi_neo',
        'wilayah_neo',
        'sumber_dana_neo'
    ];


    /*
    |--------------------------------------------------------------------------
    | HALAMAN RESET TABEL
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $this->checkAuth();

        $sql = "
            SELECT TABLE_NAME, TABLE_ROWS
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_TYPE='BASE TABLE'
            ORDER BY TABLE_NAME
        ";

        $tables = DB::getInstance()
            ->query($sql)
            ->fetchAll();

        $this->view('reset_tabel/index', [
            'tables' => $tables
        ], 'app');
    }


    /*
    |--------------------------------------------------------------------------
    | RESET DATA TABEL
    |--------------------------------------------------------------------------
    */

    public function reset()
    {
        $this->checkAuth();

        $table = $_POST['table'] ?? null;

        if (!$table) {
            echo json_encode(['success'=>false,'message'=>'Table kosong']);
            exit;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            echo json_encode(['success'=>false,'message'=>'Nama tabel tidak valid']);
            exit;
        }

        if (!in_array($table, $this->allowedTables)) {
            echo json_encode(['success'=>false,'message'=>'Tabel tidak diizinkan']);
            exit;
        }

        $db = DB::getInstance();

        $role = $_SESSION['user']['type_user'] ?? null;
        $kd_wilayah = $_SESSION['user']['kd_wilayah'] ?? null;

        try {

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
                } else {
                    echo json_encode(['success'=>false,'message'=>'Tabel tidak memiliki kolom kd_wilayah']);
                    exit;
                }

            } else {

                echo json_encode(['success'=>false,'message'=>'Tidak memiliki akses']);
                exit;

            }

            echo json_encode([
                'success' => true,
                'message' => 'Reset tabel berhasil'
            ]);

        } catch (Exception $e) {

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        }

    }


    /*
    |--------------------------------------------------------------------------
    | BACKUP DATABASE
    |--------------------------------------------------------------------------
    */

    public function backup()
    {
        $this->checkAuth();

        if (($_SESSION['user']['type_user'] ?? null) !== 'super_admin') {
            exit;
        }

        $db = DB::getInstance();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $sql = "";

        foreach ($tables as $table) {

            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql .= $create['Create Table'] . ";\n\n";

            $rows = $db->query("SELECT * FROM `$table`")->fetchAll();

            foreach ($rows as $row) {

                $values = array_map(function ($v) {
                    if ($v === null) return "NULL";
                    return "'" . addslashes($v) . "'";
                }, array_values($row));

                $sql .= "INSERT INTO `$table` VALUES (" . implode(",", $values) . ");\n";
            }

            $sql .= "\n\n";
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=backup_' . date('Ymd_His') . '.sql');

        echo $sql;
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE DATABASE
    |--------------------------------------------------------------------------
    */

    public function restore()
    {
        $this->checkAuth();

        if (($_SESSION['user']['type_user'] ?? null) !== 'super_admin') {
            exit;
        }

        if (!isset($_FILES['file'])) {
            exit;
        }

        $sql = file_get_contents($_FILES['file']['tmp_name']);

        $db = DB::getInstance();

        $db->query("SET FOREIGN_KEY_CHECKS=0");
        $db->query($sql);
        $db->query("SET FOREIGN_KEY_CHECKS=1");

        echo json_encode(['success'=>true]);
    }


    /*
    |--------------------------------------------------------------------------
    | COUNT DATA TABEL
    |--------------------------------------------------------------------------
    */

    public function count()
    {
        $this->checkAuth();

        $table = $_POST['table'] ?? null;

        if (!$table) exit;

        $db = DB::getInstance();

        $role = $_SESSION['user']['type_user'] ?? null;
        $kd_wilayah = $_SESSION['user']['kd_wilayah'] ?? null;

        if ($role === 'super_admin') {

            $row = $db->query("SELECT COUNT(*) as jml FROM `$table`")->fetch();

        } else {

            $row = $db->query(
                "SELECT COUNT(*) as jml FROM `$table` WHERE kd_wilayah=?",
                [$kd_wilayah]
            )->fetch();

        }

        echo json_encode([
            'success'=>true,
            'count'=>$row['jml']
        ]);
    }

}