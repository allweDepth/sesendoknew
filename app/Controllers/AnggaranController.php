<?php

class AnggaranController
{

    public function renja()
    {
        $table='renja_neo';
        $tahap='renja';

        require __DIR__.'/../Views/anggaran/index.php';
    }

    public function rka()
    {
        $table='rka_neo';
        $tahap='rka';

        require __DIR__.'/../Views/anggaran/index.php';
    }

    public function dpa()
    {
        $table='dpa_neo';
        $tahap='dpa';

        require __DIR__.'/../Views/anggaran/index.php';
    }

}