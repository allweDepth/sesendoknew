<?php

/*
|--------------------------------------------------------------------------
| ANGGARAN CONTROLLER
|--------------------------------------------------------------------------
| Digunakan oleh:
| renja
| renja_perubahan
| rka
| rka_perubahan
| dpa
| dppa
|--------------------------------------------------------------------------
*/

class AnggaranController
{
    /*
|--------------------------------------------------------------------------
| DEFAULT METHOD
|--------------------------------------------------------------------------
| Dipanggil oleh router jika method tidak ditentukan
| Dialihkan ke RENJA
|--------------------------------------------------------------------------
*/

    public function index()
    {

        $table = 'renja_neo';

        $tahap = 'renja';

        require __DIR__ . '/../Views/anggaran/index.php';
    }
    /*
    |--------------------------------------------------------------------------
    | RENJA
    |--------------------------------------------------------------------------
    */

    public function renja()
    {

        $table = 'renja_neo';

        $tahap = 'renja';

        require __DIR__ . '/../Views/anggaran/index.php';
    }



    /*
    |--------------------------------------------------------------------------
    | RENJA PERUBAHAN
    |--------------------------------------------------------------------------
    */

    public function renjaPerubahan()
    {

        $table = 'renja_perubahan_neo';

        $tahap = 'renja_p';

        require __DIR__ . '/../Views/anggaran/index.php';
    }



    /*
    |--------------------------------------------------------------------------
    | RKA
    |--------------------------------------------------------------------------
    */

    public function rka()
    {

        $table = 'rka_neo';

        $tahap = 'rka';

        require __DIR__ . '/../Views/anggaran/index.php';
    }



    /*
    |--------------------------------------------------------------------------
    | RKA PERUBAHAN
    |--------------------------------------------------------------------------
    */

    public function rkaPerubahan()
    {

        $table = 'rka_perubahan_neo';

        $tahap = 'rka_p';

        require __DIR__ . '/../Views/anggaran/index.php';
    }



    /*
    |--------------------------------------------------------------------------
    | DPA
    |--------------------------------------------------------------------------
    */

    public function dpa()
    {

        $table = 'dpa_neo';

        $tahap = 'dpa';

        require __DIR__ . '/../Views/anggaran/index.php';
    }



    /*
    |--------------------------------------------------------------------------
    | DPPA
    |--------------------------------------------------------------------------
    */

    public function dppa()
    {

        $table = 'dppa_neo';

        $tahap = 'dppa';

        require __DIR__ . '/../Views/anggaran/index.php';
    }



    /*
    |--------------------------------------------------------------------------
    | AJAX SUB KEGIATAN
    |--------------------------------------------------------------------------
    */

    public function subKegiatan()
    {

        $table = $_POST['table'];

        $data = AnggaranHierarchyService::subKegiatan($table);

        echo json_encode($data);
    }



    /*
    |--------------------------------------------------------------------------
    | AJAX REKAP AKUN
    |--------------------------------------------------------------------------
    */

    public function rekapAkun()
    {

        $table = $_POST['table'];

        $sub   = $_POST['sub'];

        $data = AnggaranHierarchyService::rekapAkun($table, $sub);

        echo json_encode($data);
    }



    /*
    |--------------------------------------------------------------------------
    | AJAX RINCIAN
    |--------------------------------------------------------------------------
    */

    public function rincian()
    {

        $table = $_POST['table'];

        $sub   = $_POST['sub'];

        $akun  = $_POST['akun'];

        $data = AnggaranHierarchyService::rincian($table, $sub, $akun);

        echo json_encode($data);
    }
}
