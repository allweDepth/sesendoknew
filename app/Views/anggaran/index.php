<?php
/*
|--------------------------------------------------------------------------
| CONTAINER MODUL ANGGARAN
|--------------------------------------------------------------------------
| View akan ditimpa sesuai tahap
|--------------------------------------------------------------------------
*/
?>

<div class="ui container"
     id="anggaranContainer"
     data-module="anggaran"
     data-table="<?= $table ?>"
     data-tahap="<?= $tahap ?>">

<?php require __DIR__.'/sub_kegiatan.php'; ?>

</div>