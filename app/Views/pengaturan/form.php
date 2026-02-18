<?php
/**
 * =========================================================
 * FORM PENGATURAN SISTEM (ENTERPRISE STRUCTURE)
 * =========================================================
 * Role Matrix:
 *
 * super_admin   → Mengatur ATURAN REFERENSI
 * admin_wilayah → Mengatur PERIODE & KONTROL SISTEM
 * admin_opd     → Read Only
 * viewer        → Read Only
 *
 * Basis data: kd_wilayah
 * =========================================================
 */

require_once __DIR__ . '/../../Core/DB.php';
$db = DB::getInstance();

/* =========================================================
   SESSION
========================================================= */
$type        = $_SESSION['user']['type_user'] ?? 'viewer';
$kd_wilayah  = $_SESSION['user']['kd_wilayah'] ?? '';
$kd_opd      = $_SESSION['user']['kd_opd'] ?? '';
$tahunUser   = $_SESSION['user']['tahun'] ?? date('Y');

/* =========================================================
   LOAD DATA
========================================================= */
$pengaturan = $db->query(
    "SELECT * FROM pengaturan_neo WHERE kd_wilayah = ? LIMIT 1",
    [$kd_wilayah]
)->fetch();

$peraturan = $db->query(
    "SELECT id, judul FROM peraturan_neo ORDER BY judul ASC"
)->fetchAll();

/* =========================================================
   ROLE PERMISSION
========================================================= */
$canEditAturan  = ($type === 'super_admin');
$canEditPeriode = ($type === 'admin_wilayah');
$canEditKontrol = ($type === 'admin_wilayah');

$disAturan  = $canEditAturan  ? '' : 'disabled';
$disPeriode = $canEditPeriode ? '' : 'disabled';
$disKontrol = $canEditKontrol ? '' : 'disabled';
?>
<?php
/* ==========================================
   ROLE MESSAGE CONFIGURATION
========================================== */
$roleInfo = [
    'super_admin'   => ['color'=>'red','icon'=>'shield alternate','title'=>'Super Admin','desc'=>'Mengatur aturan referensi sistem'],
    'admin_wilayah' => ['color'=>'orange','icon'=>'globe','title'=>'Admin Wilayah','desc'=>'Mengatur periode dan kontrol sistem'],
    'admin_opd'     => ['color'=>'blue','icon'=>'building','title'=>'Admin OPD','desc'=>'Mengikuti pengaturan wilayah'],
    'viewer'        => ['color'=>'grey','icon'=>'eye','title'=>'Viewer','desc'=>'Hanya dapat melihat pengaturan']
];

$currentRole = $roleInfo[$type] ?? $roleInfo['viewer'];
?>

<!-- ======================================================
     ROLE MESSAGE (FOMANTIC)
====================================================== -->
<div class="ui <?= $currentRole['color']; ?> icon message">
    <i class="<?= $currentRole['icon']; ?> icon"></i>
    <div class="content">
        <div class="header">
            Role Anda: <?= strtoupper($currentRole['title']); ?>
        </div>
        <p><?= $currentRole['desc']; ?></p>
        <p>
            Wilayah: <strong><?= $kd_wilayah ?></strong> |
            OPD: <strong><?= $kd_opd ?></strong>
        </p>
    </div>
</div>

<form class="ui form" method="post" action="/pengaturan/store">


<?php if($type === 'super_admin'): ?>

<div class="ui raised segment">
<h4 class="ui dividing header">Periode RPJMD</h4>

<div class="ui three column grid">

 <div class="column">
        <div class="field">
            <label>Periode Mulai</label>
            <div class="ui calendar" id="rpjmd_mulai_calendar">
                <div class="ui input left icon">
                    <i class="calendar icon"></i>
                    <input type="text"
                           name="rpjmd_mulai"
                           placeholder="Pilih Tahun"
                           autocomplete="off">
                </div>
            </div>
        </div>
    </div>

    <div class="column">
        <div class="field">
            <label>Periode Selesai</label>
            <div class="ui calendar" id="rpjmd_selesai_calendar">
                <div class="ui input left icon">
                    <i class="calendar icon"></i>
                    <input type="text"
                           name="rpjmd_selesai"
                           placeholder="Pilih Tahun"
                           autocomplete="off">
                </div>
            </div>
        </div>
    </div>

<div class="column">
<div class="field">
<label>Keterangan</label>
<input type="text" name="rpjmd_keterangan">
</div>
</div>

</div>

</div>

<?php endif; ?>
<!-- ======================================================
     ATURAN REFERENSI (SUPER ADMIN)
====================================================== -->
<div class="ui raised segment">
<h4 class="ui dividing header">Aturan Referensi</h4>

<?php
$aturanFields = [
'aturan_anggaran','aturan_organisasi','aturan_pengadaan',
'aturan_akun','aturan_asb','aturan_sbu','aturan_ssh',
'aturan_hspk','aturan_sumber_dana','aturan_sub_kegiatan'
];

foreach($aturanFields as $field):
?>
<div class="field">
<label><?= ucwords(str_replace('_',' ',$field)); ?></label>
<div class="ui fluid selection dropdown <?= $disAturan ?>">
<input type="hidden"
       name="<?= $field ?>"
       value="<?= $pengaturan[$field] ?? '' ?>">
<i class="dropdown icon"></i>
<div class="default text">Pilih</div>
<div class="menu">
<?php foreach($peraturan as $row): ?>
<div class="item"
     data-value="<?= $row['id']; ?>">
     <?= htmlspecialchars($row['judul']); ?>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endforeach; ?>

</div>

<!-- ======================================================
     PERIODE DOKUMEN (ADMIN WILAYAH)
====================================================== -->
<div class="ui raised segment">
<h4 class="ui dividing header">Periode Dokumen</h4>

<div class="ui four column stackable grid">
<?php foreach(['awal_renja','akhir_renja','awal_dpa','akhir_dpa'] as $f): ?>
<div class="column">
<div class="field">
<label><?= ucwords(str_replace('_',' ',$f)); ?></label>
<input type="date"
       name="<?= $f ?>"
       value="<?= $pengaturan[$f] ?? '' ?>"
       <?= $disPeriode ?>>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="ui four column stackable grid">
<?php foreach(['awal_renja_p','akhir_renja_p','awal_dppa','akhir_dppa'] as $f): ?>
<div class="column">
<div class="field">
<label><?= ucwords(str_replace('_',' ',$f)); ?></label>
<input type="date"
       name="<?= $f ?>"
       value="<?= $pengaturan[$f] ?? '' ?>"
       <?= $disPeriode ?>>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="ui two column stackable grid">
<?php foreach(['awal_renstra','akhir_renstra'] as $f): ?>
<div class="column">
<div class="field">
<label><?= ucwords(str_replace('_',' ',$f)); ?></label>
<input type="date"
       name="<?= $f ?>"
       value="<?= $pengaturan[$f] ?? '' ?>"
       <?= $disPeriode ?>>
</div>
</div>
<?php endforeach; ?>
</div>

</div>

<!-- ======================================================
     KONTROL SISTEM (ADMIN WILAYAH)
====================================================== -->
<div class="ui raised segment">
<h4 class="ui dividing header">Kontrol Sistem</h4>

<div class="ui stackable four column grid">

<?php
function kontrolCard($title,$kunci,$setujui,$pengaturan,$disabled){
?>
<div class="column">
<div class="ui segment">
<h5 class="ui header"><?= $title ?></h5>

<div class="ui form">
<div class="field">
<div class="ui toggle checkbox">
<input type="checkbox"
       name="<?= $kunci ?>"
       <?= !empty($pengaturan[$kunci]) ? 'checked' : '' ?>
       <?= $disabled ?>>
<label>Kunci</label>
</div>
</div>

<div class="field">
<div class="ui toggle checkbox">
<input type="checkbox"
       name="<?= $setujui ?>"
       <?= !empty($pengaturan[$setujui]) ? 'checked' : '' ?>
       <?= $disabled ?>>
<label>Setujui</label>
</div>
</div>
</div>

</div>
</div>
<?php } ?>

<?php
kontrolCard('Global','kunci','setujui',$pengaturan,$disKontrol);
kontrolCard('Renstra','kunci_renstra','setujui_renstra',$pengaturan,$disKontrol);
kontrolCard('Renja','kunci_renja','setujui_renja',$pengaturan,$disKontrol);
kontrolCard('DPA','kunci_dpa','setujui_dpa',$pengaturan,$disKontrol);
kontrolCard('Renja P','kunci_renja_p','setujui_renja_p',$pengaturan,$disKontrol);
kontrolCard('DPPA','kunci_dppa','setujui_dppa',$pengaturan,$disKontrol);
kontrolCard('Paket','kunci_paket','setujui_paket',$pengaturan,$disKontrol);
kontrolCard('Realisasi','kunci_realisasi','setujui_realisasi',$pengaturan,$disKontrol);
?>

</div>
</div>

<!-- ======================================================
     SUBMIT
====================================================== -->
<div class="ui right aligned segment">

<?php if($canEditAturan || $canEditPeriode || $canEditKontrol): ?>
<button type="submit" class="ui primary button">
<i class="save icon"></i> Simpan
</button>
<?php else: ?>
<div class="ui grey disabled button">
Tidak memiliki hak perubahan
</div>
<?php endif; ?>

</div>

</form>