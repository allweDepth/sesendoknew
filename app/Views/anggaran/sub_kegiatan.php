<?php
/*
VIEW GLOBAL ANGGRAN
Digunakan untuk:
renja
rka
dpa
renja_p
rka_p
dppa
*/
?>

<div class="ui container"
     data-module="anggaran"
     data-table="<?= $table ?>"
     data-tahap="<?= $tahap ?>">

    <!-- HEADER -->

    <h3 class="ui dividing header">
        Sub Kegiatan Belanja
    </h3>

    <!-- INFO OPD -->

    <div class="ui segment">

        <b>
        DINAS PEKERJAAN UMUM DAN PENATAAN RUANG
        </b>

    </div>

    <!-- TABEL SUB KEGIATAN -->

    <div class="ui segment">

        <table class="ui celled selectable table"
               id="tableSubKegiatan">

            <thead>

                <tr>
                    <th>Kode Sub Kegiatan</th>
                    <th>Nama Sub Kegiatan</th>
                    <th>Total Anggaran</th>
                </tr>

            </thead>

            <tbody>

                <!-- diisi JS -->

            </tbody>

        </table>

    </div>


    <!-- PANEL RINCIAN (hidden dulu) -->

    <div class="ui segment" id="panelRincian" style="display:none">

        <h4 class="ui header">
            Rincian Belanja
        </h4>

        <table class="ui celled table"
               id="tableRincian">

            <thead>

                <tr>

                    <th>Uraian</th>
                    <th>Volume</th>
                    <th>Harga</th>
                    <th>Jumlah</th>

                </tr>

            </thead>

            <tbody></tbody>

        </table>

    </div>

</div>