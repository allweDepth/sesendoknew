<?php

class PengaturanController extends Controller
{
    public function index()
    {
        $this->view('pengaturan/index');
    }

    public function load()
    {
        // nanti isi untuk datatable / ajax
        echo json_encode([
            'status' => true,
            'message' => 'Load pengaturan berhasil'
        ]);
    }

    public function store()
    {
        require_once __DIR__ . '/../Core/DB.php';
        $db = DB::getInstance();

        $user = $_SESSION['user'] ?? [];
        $type = $user['type_user'] ?? 'viewer';
        $kd_wilayah = $user['kd_wilayah'] ?? '';

        /* =====================================================
       1️⃣ SUPER ADMIN → SIMPAN PERIODE RPJMD
    ===================================================== */
        if ($type === 'super_admin' && !empty($_POST['rpjmd_mulai'])) {

            $mulai  = (int)$_POST['rpjmd_mulai'];
            $selesai = (int)$_POST['rpjmd_selesai'];
            $ket    = $_POST['rpjmd_keterangan'] ?? '';

            // VALIDASI
            if ($mulai >= $selesai) {
                $_SESSION['error'] = "Periode tidak valid";
                header("Location: /pengaturan");
                exit;
            }

            // CEK OVERLAP
            $cek = $db->query("
            SELECT id FROM periode_rpjmd
            WHERE kd_wilayah = ?
            AND (
                (? BETWEEN periode_mulai AND periode_selesai)
                OR
                (? BETWEEN periode_mulai AND periode_selesai)
            )
        ", [$kd_wilayah, $mulai, $selesai])->fetch();

            if ($cek) {
                $_SESSION['error'] = "Periode tumpang tindih";
                header("Location: /pengaturan");
                exit;
            }

            // NONAKTIFKAN PERIODE LAMA
            $db->query("
            UPDATE periode_rpjmd
            SET status_aktif = 0
            WHERE kd_wilayah = ?
        ", [$kd_wilayah]);

            // INSERT BARU
            $db->query("
            INSERT INTO periode_rpjmd
            (kd_wilayah, periode_mulai, periode_selesai,
             status_aktif, keterangan,
             tgl_insert, username_insert)
            VALUES (?, ?, ?, 1, ?, NOW(), ?)
        ", [
                $kd_wilayah,
                $mulai,
                $selesai,
                $ket,
                $user['username'] ?? 'system'
            ]);
        }

        /* =====================================================
       2️⃣ ADMIN WILAYAH → UPDATE pengaturan_neo
    ===================================================== */
        if ($type === 'admin_wilayah') {

            $data = $_POST;

            $db->update(
                'pengaturan_neo',
                $data,
                "WHERE kd_wilayah = ?",
                [$kd_wilayah]
            );
        }

        header("Location: /pengaturan");
    }

    public function update()
    {
        echo json_encode([
            'status' => true,
            'message' => 'Data pengaturan berhasil diupdate'
        ]);
    }

    public function delete()
    {
        echo json_encode([
            'status' => true,
            'message' => 'Data pengaturan berhasil dihapus'
        ]);
    }
}
