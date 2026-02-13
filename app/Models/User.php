<?php
namespace app\Models;

use app\Database;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM user_sesendok_biila WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO user_sesendok_biila (username, email, nama, password, kd_organisasi, nama_org, kd_wilayah, type_user, tgl_daftar, tgl_login, tahun) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data['username'], $data['email'], $data['nama'], password_hash($data['password'], PASSWORD_DEFAULT), $data['kd_organisasi'], $data['nama_org'], $data['kd_wilayah'], 'user', date('Y')]);
    }

    // Tambah method untuk tabel lain seperti renstra_neo, dll.
}