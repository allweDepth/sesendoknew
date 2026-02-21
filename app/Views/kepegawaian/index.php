<div class="ui container">

  <div class="ui info message">
    Data referensi: <?= strtoupper($tbl) ?>
  </div>

  <div class="ui hidden divider"></div>

  <div class="ui right floated basic icon buttons">

    <!-- ADD -->
    <button class="ui button"
      data-ui="open-form"
      data-jns="add"
      data-tbl="<?= $tbl ?>"
      data-jenis="<?= $jenis ?>"
      data-container="flyout"
      data-tooltip="Tambah Data"
      data-position="bottom center">
      <i class="plus icon"></i>
    </button>

    <!-- IMPORT XLSX -->
    <button class="ui button"
      data-ui="open-form"
      data-jns="import_xlsx"
      data-tbl="<?= $tbl ?>"
      data-jenis="<?= $jenis ?>"
      data-container="modal"
      data-tooltip="Import XLSX"
      data-position="bottom center">
      <i class="upload icon"></i>
    </button>

    <!-- EXPORT -->
    <button class="ui icon button"
      type="button"
      data-action="export"
      data-tbl="<?= $tbl ?>"
      data-tooltip="Download"
      data-position="bottom center">
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
        'asn' => [
          'Nama',
          'NIP',
          'Alamat',
          'Golongan',
          'ruang',
          'Jabatan',
          'Keterangan'
        ],
        'sk_asn' => [
          'Nomor',
          'Tanggal Surat',
          'Tentang',
          'Pemberi Tugas',
          'Keterangan'
        ],
        'register_surat' => [
          'Jenis Naskah',
          'Sifat',
          'Nomor',
          'Tanggal',
          'Uraian',
          'File',
          'Keterangan'
        ],
        'tata_naskah' => [
          'Jenis',
          'Nomor',
          'Tanggal',
          'Uraian',
          'Keterangan'
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
    <tbody name="tabel_kepegawaian">
      <tr>
        <td colspan="<?= $totalCol ?>">
          <div class="ui active inline loader"></div>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="100%" class="right aligned">
          <div name="pagination_kepegawaian"></div>
        </td>
      </tr>
    </tfoot>
  </table>
</div>