<?php

class AnggaranController extends Controller
{

    public function index()
    {

        $table = $_GET['table'] ?? 'renja_neo';

        $tahap = $_GET['tahap'] ?? 'renja';

        require APPPATH.'/Views/anggaran/index.php';

    }



    public function subKegiatan()
    {

        $table = $_POST['table'];

        $tahun = $_POST['tahun'];

        $opd = $_POST['opd'];

        $data = AnggaranHierarchyService::getSubKegiatan($table,$tahun,$opd);

        JsonResponse::success($data);

    }



    public function rekapAkun()
    {

        $table = $_POST['table'];

        $kd_sub_keg = $_POST['kd_sub_keg'];

        $data = AnggaranHierarchyService::getRekapAkun($table,$kd_sub_keg);

        JsonResponse::success($data);

    }



    public function rincian()
    {

        $table = $_POST['table'];

        $kd_sub_keg = $_POST['kd_sub_keg'];

        $kd_akun = $_POST['kd_akun'];

        $data = AnggaranHierarchyService::getRincian($table,$kd_sub_keg,$kd_akun);

        JsonResponse::success($data);

    }

}