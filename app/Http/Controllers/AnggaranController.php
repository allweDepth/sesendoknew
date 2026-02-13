<?php
namespace app\Http\Controllers;

class AnggaranController {
    public function index() {
        view('anggaran/index');
    }

    public function renstra() {
        view('anggaran/renstra');
    }

    // Tambah renja, dpa, dll.
}