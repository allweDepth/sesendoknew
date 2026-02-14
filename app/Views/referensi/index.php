<div class="ui container">

  <div class="ui info message">
    Data referensi: <?= strtoupper($tbl) ?>
  </div>

  <div class="ui hidden divider"></div>

  <div class="ui right floated basic icon buttons">
    <button class="ui button" name="flyout" data-tooltip="Tambah Data" data-position="bottom center" jns="add"><i class="plus icon"></i></button>
    <button class="ui button" name="flyout" jns="import" data-tooltip="Import XLSX" data-position="bottom center"><i class="upload icon"></i></button> <button class="ui button" data-tooltip="Download" data-position="bottom center" name="ungguh" jns="dok" tbl="sub_keg" type="submit"><i class="alternate download icon"></i></button>
  </div>

  <h3 class="ui dividing header">
    <i class="left align icon"></i>
    Tabel <?= strtoupper($tbl) ?>
  </h3>

  <div class="ui hidden divider"></div>

  <table class="ui celled striped table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nama</th>
        <th class="collapsing">Aksi</th>
      </tr>
    </thead>

    <tbody>

      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="3">
            <div class="ui warning message">
              Data belum tersedia.
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= $row['id'] ?? '' ?></td>
            <td contenteditable="true"
              class="editable"
              data-id="<?= $row['id'] ?>"
              data-tbl="<?= $tbl ?>">
              <?= $row['nama'] ?? '' ?>
            </td>
            <td>
              <button class="ui red mini button btnDelete"
                data-id="<?= $row['id'] ?>"
                data-tbl="<?= $tbl ?>">
                <i class="trash icon"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>

    </tbody>

    <tfoot>
      <tr>
        <th colspan="3">
          <div class="ui center pagination menu">
            <a class="active item">1</a>
          </div>
        </th>
      </tr>
    </tfoot>
  </table>

</div>