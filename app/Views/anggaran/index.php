<div data-module="anggaran"
     data-table="<?= $table ?>"
     data-tahap="<?= $tahap ?>">

<!-- SUB KEGIATAN -->

<?php require __DIR__.'/sub_kegiatan.php'; ?>

<!-- REKAP AKUN -->

<div id="panelRekap"
     style="display:none">

<?php require __DIR__.'/panel_rekap.php'; ?>

</div>

<!-- RINCIAN -->

<div id="panelRincian"
     style="display:none">

<?php require __DIR__.'/panel_rincian.php'; ?>

</div>

</div>