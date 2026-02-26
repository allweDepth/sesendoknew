<?php

class PengaturanController extends Controller
{
    public function index()
    {
        $this->view('pengaturan/index');
    }
    public function load_single()
    {
        $kd_wilayah = $_SESSION['user']['kd_wilayah'];

        $db = DB::getInstance();

        $data = $db->query(
            "SELECT * FROM pengaturan_neo WHERE kd_wilayah = ? LIMIT 1",
            [$kd_wilayah]
        )->fetch();

        if (!$data) {
            $db->insert('pengaturan_neo', [
                'kd_wilayah' => $kd_wilayah
            ]);

            $data = $db->query(
                "SELECT * FROM pengaturan_neo WHERE kd_wilayah = ? LIMIT 1",
                [$kd_wilayah]
            )->fetch();
        }

        echo JsonResponse::success("Data ditemukan", $data);
    }
}
