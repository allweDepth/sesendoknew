<?php
/*
|--------------------------------------------------------------------------
| GLOBAL VIEW ANGGARAN
|--------------------------------------------------------------------------
| Digunakan oleh:
| renja
| renja_p
| rka
| rka_p
| dpa
| dppa
|
| Variabel dari controller:
| $table
| $tahap
*/
?>

<div class="ui container"
     data-module="anggaran"
     data-table="<?= $table ?>"
     data-tahap="<?= $tahap ?>">

    <!-- ================================================= -->
    <!-- HEADER -->
    <!-- ================================================= -->

    <h3 class="ui dividing header">

        Sub Kegiatan Belanja

    </h3>

    <!-- ================================================= -->
    <!-- INFO OPD -->
    <!-- ================================================= -->

    <div class="ui segment">

        <b>
        DINAS PEKERJAAN UMUM DAN PENATAAN RUANG
        </b>

    </div>

    <!-- ================================================= -->
    <!-- TABEL SUB KEGIATAN -->
    <!-- ================================================= -->

    <div class="ui segment">

        <table class="ui celled selectable compact table"
               id="tableSubKegiatan">

            <thead>

                <tr>

                    <th style="width:160px">
                        Kode
                    </th>

                    <th>
                        Sub Kegiatan
                    </th>

                    <th style="width:180px">
                        Total Anggaran
                    </th>

                </tr>

            </thead>

            <tbody></tbody>

        </table>

    </div>


    <!-- ================================================= -->
    <!-- PANEL REKAP AKUN -->
    <!-- ================================================= -->

    <div id="panelRekap"
         style="display:none">

        <?php require __DIR__.'/panel_rekap.php'; ?>

    </div>


    <!-- ================================================= -->
    <!-- PANEL RINCIAN -->
    <!-- ================================================= -->

    <div id="panelRincian"
         style="display:none">

        <?php require __DIR__.'/panel_rincian.php'; ?>

    </div>

</div>