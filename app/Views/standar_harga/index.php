<div class="ui container">

  <div class="ui info message">
    Data Harga Satuan : <?= strtoupper($tbl) ?>
  </div>

  <div class="ui hidden divider"></div>

  <div class="ui right floated basic icon buttons">
    <button class="ui button"
      data-ui="open-form"
      data-jns="add"
      data-tbl="<?= $tbl ?>"
      data-tooltip="Tambah Data"
      data-position="bottom center"
      data-container="flyout">
      <i class="plus icon"></i>
    </button>
    <button class="ui button" data-ui="open-form" jns="import" data-tooltip="Import XLSX" data-position="bottom center"><i class="upload icon"></i></button>
    <button class="ui icon button"
      type="button"
      data-tooltip="Download"
      data-position="bottom center"
      data-container="flyout"
      data-action="export"
      data-tbl="<?= $tbl ?>">
      <i class="alternate download icon"></i>
    </button>
  </div>

  <h3 class="ui dividing header">
    <i class="left align icon"></i>
    Tabel <?= strtoupper($tbl) ?>
  </h3>

  <div class="ui hidden divider"></div>

  <table class="ui celled striped table">
    <thead>
      <?php

      $columns = [
        'ssh' => [
          'Kode Aset',
          'Kode Akun',
          'Uraian',
          'Spesifikasi',
          'Satuan',
          'Harga Satuan'
        ],
        'hspk' => [
          'Kode Aset',
          'Kode Akun',
          'Uraian',
          'Spesifikasi',
          'Satuan',
          'Harga Satuan'
        ],
        'asb' => [
          'Kode Aset',
          'Kode Akun',
          'Uraian',
          'Spesifikasi',
          'Satuan',
          'Harga Satuan'
        ],
        'sbu' => [
          'Kode Aset',
          'Kode Akun',
          'Uraian',
          'Spesifikasi',
          'Satuan',
          'Harga Satuan'
        ]
      ];

      $currentColumns = $columns[$tbl] ?? ['Nama'];

      $totalCol = count($currentColumns) + 1; // +1 untuk kolom Aksi

      ?>

      <tr>
        <?php foreach ($currentColumns as $col): ?>
          <th><?= $col ?></th>
        <?php endforeach; ?>

        <th class="collapsing">Aksi</th>
      </tr>
    </thead>


    <!-- INI SAJA YANG BERBEDA -->
    <tbody name="tabel_standar_harga">
      <tr>
        <td colspan="<?= $totalCol ?>">
          <div class="ui active inline loader"></div>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="100%" class="right aligned">
          <div name="pagination_standar_harga"></div>
        </td>
      </tr>
    </tfoot>
  </table>
</div>