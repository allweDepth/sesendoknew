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
    public function getAll($kdWilayah = null, string $type='berita')
    {
        $sql = "
            SELECT id,kd_wilayah,judul,slug AS id_pengenal,
                   COALESCE(NULLIF(keterangan,''),'Informasi') AS kelompok,
                   konten AS uraian_html,
                   LEFT(TRIM(REGEXP_REPLACE(konten,'<[^>]*>',' ')),220) AS uraian_singkat,
                   COALESCE(tgl_update,tgl_insert) AS tanggal,
                   gambar,tgl_insert,tgl_update,username_insert,username_update,keterangan,0 AS urutan
            FROM halaman_berita
            WHERE is_deleted = 0 AND aktif=1 AND jenis_halaman=?
        ";

        $params = [$type];

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
            FROM halaman_berita
            WHERE id = ?
              AND is_deleted = 0
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
                COALESCE(NULLIF(keterangan,''),'Informasi') kelompok,
                LEFT(TRIM(REGEXP_REPLACE(konten,'<[^>]*>',' ')),220) uraian_singkat,
                COALESCE(tgl_update,tgl_insert) tanggal
            FROM halaman_berita
            WHERE is_deleted = 0
            ORDER BY COALESCE(tgl_update,tgl_insert) DESC
            LIMIT $limit
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
