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

        <?php if ($type === 'super_admin'): ?>
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

        <table class="ui celled striped table">
            <thead></thead>
            <tbody name="tabel_periode_rpjmd"></tbody>
            <tfoot>
                <tr>
                    <td colspan="100%" class="right aligned">
                        <div name="pagination_periode_rpjmd"></div>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div name="pagination_pengaturan"></div>

    </div>
</div>