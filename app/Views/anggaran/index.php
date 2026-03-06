<?php
/*
ROOT MODULE ANGGARAN
Digunakan untuk:
renja
renja_perubahan
rka
rka_perubahan
dpa
dppa
*/
?>

<div data-module="anggaran"
     data-table="<?= $table ?>"
     data-tahap="<?= $tahap ?>">

<!-- ===================================================== -->
<!-- HEADER -->
<!-- ===================================================== -->

<h3 class="ui dividing header">

Sub Kegiatan Belanja

<div class="sub header">

DINAS PEKERJAAN UMUM DAN PENATAAN RUANG

</div>

</h3>


<!-- ===================================================== -->
<!-- SUB KEGIATAN -->
<!-- ===================================================== -->

<?php require __DIR__.'/sub_kegiatan.php'; ?>


<!-- ===================================================== -->
<!-- REKAP AKUN -->
<!-- ===================================================== -->

<div id="panelRekap"
     style="display:none">

<?php require __DIR__.'/panel_rekap.php'; ?>

</div>


<!-- ===================================================== -->
<!-- RINCIAN BELANJA -->
<!-- ===================================================== -->

<div id="panelRincian"
     style="display:none">

<?php require __DIR__.'/panel_rincian.php'; ?>

</div>

</div>