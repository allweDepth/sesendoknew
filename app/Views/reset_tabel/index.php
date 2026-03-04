<?php

/*
|--------------------------------------------------------------------------
| AMBIL TABEL DATABASE
|--------------------------------------------------------------------------
*/

$sql = "
SELECT TABLE_NAME, TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME
";

$result = DB::getInstance()
    ->query($sql)
    ->fetchAll();

/*
|--------------------------------------------------------------------------
| TABEL YANG TIDAK BOLEH DITAMPILKAN
|--------------------------------------------------------------------------
*/

$exclude_tables = [
    'user_sesendok_biila',
    'users'
];

/*
|--------------------------------------------------------------------------
| NORMALISASI DATA
|--------------------------------------------------------------------------
*/

$tables = [];

foreach ($result as $row) {

    if (in_array($row['TABLE_NAME'], $exclude_tables)) {
        continue;
    }

    $tables[$row['TABLE_NAME']] = $row['TABLE_ROWS'];
}

/*
|--------------------------------------------------------------------------
| META GENERATOR
|--------------------------------------------------------------------------
*/

function tableMeta($tbl)
{

    $label = ucwords(str_replace('_', ' ', $tbl));

    $meta = 'Data Sistem';
    $icon = 'grey database';

    if (str_contains($tbl,'renstra') || str_contains($tbl,'renja')) {
        $meta = 'Renstra';
        $icon = 'blue chart bar';
    }

    if (str_contains($tbl,'program') || str_contains($tbl,'kegiatan')) {
        $meta = 'Perencanaan';
        $icon = 'green clipboard';
    }

    if (str_starts_with($tbl,'ref_')) {
        $meta = 'Referensi';
        $icon = 'grey book';
    }

    if (str_contains($tbl,'paket') || str_contains($tbl,'rab')) {
        $meta = 'Paket';
        $icon = 'orange boxes';
    }

    if (str_contains($tbl,'naskah')) {
        $meta = 'Naskah';
        $icon = 'grey file';
    }

    if (str_starts_with($tbl,'trx_')) {
        $meta = 'Transaksi';
        $icon = 'grey exchange';
    }

    if (str_contains($tbl,'satuan')) {
        $meta = 'Satuan';
        $icon = 'teal ruler';
    }

    return [
        'header' => $label,
        'meta' => $meta,
        'icon' => $icon
    ];
}

/*
|--------------------------------------------------------------------------
| KELOMPOK TABEL
|--------------------------------------------------------------------------
*/

$group_master = array_filter(array_keys($tables), function($t){

    return str_starts_with($t,'ref_')
        || str_starts_with($t,'ssh_')
        || str_starts_with($t,'sbu_')
        || str_starts_with($t,'asb_')
        || str_starts_with($t,'hspk_')
        || str_contains($t,'satuan')
        || $t === 'urusan'
        || $t === 'bidang';

});

$group_renstra = array_filter(array_keys($tables), function($t){

    return str_contains($t,'renstra')
        || str_contains($t,'renja')
        || str_contains($t,'program')
        || str_contains($t,'kegiatan')
        || str_contains($t,'sasaran')
        || str_contains($t,'tujuan');

});

$group_trx = array_filter(array_keys($tables), function($t){

    return str_starts_with($t,'trx_')
        || str_contains($t,'paket')
        || str_contains($t,'naskah')
        || str_contains($t,'rab');

});

/*
|--------------------------------------------------------------------------
| RENDER CARD
|--------------------------------------------------------------------------
*/

function renderCard($tbl, $rows)
{

    $meta = tableMeta($tbl);
?>

<div class="card">

    <div class="content">

        <i class="right floated large ui bordered colored <?= $meta['icon'] ?> icon"></i>

        <div class="header">
            <?= $meta['header'] ?>
        </div>

        <div class="meta">
            <?= $meta['meta'] ?>
        </div>

        <div class="description">
            <?= number_format($rows) ?> Data
        </div>

    </div>

    <div class="extra content">

        <div class="ui three buttons">

            <div class="ui teal button"
                 data-action="delete_all"
                 data-table="<?= $tbl ?>">
                All
            </div>

            <div class="ui blue button"
                 data-action="delete_proyek"
                 data-table="<?= $tbl ?>">
                Dokumen
            </div>

            <div class="ui violet button"
                 data-action="reset"
                 data-table="<?= $tbl ?>">
                Reset
            </div>

        </div>

    </div>

</div>

<?php
}

?>

<div class="ui container">
<div class="ui grid">
<div class="column" data-module="reset_tabel">

<!-- BACKUP & RESTORE -->

<div class="ui placeholder segment">

<div class="ui two column stackable center aligned grid">

<div class="ui vertical divider">Or</div>

<div class="middle aligned row">

<div class="column">

<div class="ui icon header">
<i class="download icon"></i>
Backup Tabel
</div>

<div class="ui buttons">

<button class="ui blue button" data-action="backup_all">
ALL
</button>

<div class="or"></div>

<button class="ui positive button" data-action="backup_proyek">
Proyek
</button>

</div>

</div>

<div class="column">

<div class="ui icon header">
<i class="upload icon"></i>
Restore Tabel
</div>

<div class="ui buttons">

<button class="ui blue button" data-action="restore_all">
All
</button>

<div class="or"></div>

<button class="ui positive button" data-action="restore_proyek">
Proyek
</button>

</div>

</div>

</div>

</div>

</div>

<h3 class="ui dividing header">Master & Referensi</h3>

<div class="ui three stackable cards">
<?php foreach ($group_master as $tbl) renderCard($tbl,$tables[$tbl]); ?>
</div>

<h3 class="ui dividing header">Renstra & Perencanaan</h3>

<div class="ui three stackable cards">
<?php foreach ($group_renstra as $tbl) renderCard($tbl,$tables[$tbl]); ?>
</div>

<h3 class="ui dividing header">Transaksi</h3>

<div class="ui three stackable cards">
<?php foreach ($group_trx as $tbl) renderCard($tbl,$tables[$tbl]); ?>
</div>

</div>
</div>
</div>