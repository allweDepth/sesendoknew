<div class="ui container">

    <h2 class="ui dividing header">
        <i class="settings icon"></i>
        <div class="content">
            Pengaturan Sistem
        </div>
    </h2>

    <?php include __DIR__ . '/form.php'; ?>
<div class="ui container">

    <h2 class="ui dividing header">
        <i class="settings icon"></i>
        <div class="content">Pengaturan</div>
    </h2>

    <?php if($type === 'super_admin'): ?>
    <div class="ui segment">
        <button class="ui primary button"
                data-ui="open-form"
                data-container="flyout"
                data-jns="add"
                data-tbl="periode_rpjmd">
            <i class="plus icon"></i>
            Tambah Periode RPJMD
        </button>
        
    </div>
    <?php endif; ?>

    <table class="ui celled table">
        <thead>
            <tr>
                <th>Periode Mulai</th>
                <th>Periode Selesai</th>
                <th>Status</th>
                <th class="collapsing">Aksi</th>
            </tr>
        </thead>
        <tbody name="tabel_pengaturan"></tbody>
    </table>

    <div name="pagination_pengaturan"></div>

</div>
</div>