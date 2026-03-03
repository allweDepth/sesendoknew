<div class="ui container">

    <h2 class="ui header">
        <i class="erase icon"></i>
        <div class="content">
            Reset Tabel
        </div>
    </h2>

    <!-- GLOBAL ACTION (SESUI FILE MNT - DI ATAS) -->
    <?php if ($role === 'super_admin'): ?>
        <div class="ui segment">

            <div class="ui blue button" id="backupBtn">
                <i class="download icon"></i>
                Backup
            </div>

            <form id="restoreForm" enctype="multipart/form-data" style="display:inline;">
                <input type="file" name="file" required style="display:none;" id="restoreFile">
                <div class="ui green button" id="restoreBtn">
                    <i class="upload icon"></i>
                    Restore
                </div>
            </form>

        </div>
    <?php endif; ?>

    <!-- CARD GRID SESUAI FILE MNT -->
    <div class="ui stackable cards">

        <?php foreach ($tables as $tbl): ?>

            <div class="ui fluid card">

                <div class="content">
                    <div class="header">
                        <?= $tbl ?>
                    </div>
                </div>

                <div class="extra content">

                    <!-- DELETE -->
                    <div class="ui red button"
                         data-action="delete"
                         data-table="<?= $tbl ?>">
                        <i class="trash icon"></i>
                        Delete
                    </div>

                    <!-- RESET -->
                    <div class="ui violet button"
                         data-action="reset"
                         data-table="<?= $tbl ?>">
                        <i class="erase icon"></i>
                        Reset
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>
