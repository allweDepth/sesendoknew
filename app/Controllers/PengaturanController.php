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
        // simpan data
        echo json_encode([
            'status' => true,
            'message' => 'Data pengaturan berhasil disimpan'
        ]);
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