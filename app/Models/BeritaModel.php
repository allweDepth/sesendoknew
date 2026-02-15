<?php

require_once __DIR__ . '/../Core/DB.php';

class BeritaModel
{
    private $db;

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    /**
     * Ambil semua berita aktif
     */
    public function getAll($kdWilayah = null)
    {
        $sql = "
            SELECT 
                id,
                kd_wilayah,
                judul,
                id_pengenal,
                kelompok,
                uraian_html,
                uraian_singkat,
                tanggal,
                tgl_insert,
                tgl_update,
                username_insert,
                username_update,
                keterangan,
                urutan
            FROM berita_neo
            WHERE disable = 0
        ";

        $params = [];

        // Optional filter wilayah
        if ($kdWilayah) {
            $sql .= " AND kd_wilayah = ?";
            $params[] = $kdWilayah;
        }

        $sql .= " ORDER BY urutan ASC, tanggal DESC";

        return $this->db->query($sql, $params)
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil detail berita berdasarkan ID
     */
    public function getById($id)
    {
        return $this->db->query("
            SELECT *
            FROM berita_neo
            WHERE id = ?
              AND disable = 0
            LIMIT 1
        ", [$id])->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil berita terbaru (misal untuk featured)
     */
    public function getLatest($limit = 3)
    {
        return $this->db->query("
            SELECT 
                id,
                judul,
                kelompok,
                uraian_singkat,
                tanggal
            FROM berita_neo
            WHERE disable = 0
            ORDER BY tanggal DESC
            LIMIT $limit
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}