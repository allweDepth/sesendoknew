<div class="ui container">

  <div class="ui info message">
    Data referensi: <?= strtoupper($tbl) ?>
  </div>

  <div class="ui hidden divider"></div>

  <div class="ui right floated basic icon buttons">
    <button class="ui button"
      data-ui="open-form"
      data-jns="add"
      data-tbl="<?= $tbl ?>"
      data-tooltip="Tambah Data"
      data-position="bottom center"
      data-container="modal">
      <i class="plus icon"></i>
    </button>
    <button class="ui button" data-ui="open-form" jns="import" data-tooltip="Import XLSX" data-position="bottom center"><i class="upload icon"></i></button> <button class="ui button" data-tooltip="Download" data-position="bottom center" name="ungguh" jns="dok" tbl="sub_keg" type="submit"><i class="alternate download icon"></i></button>
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
      <td colspan="<?= $totalCol ?>" class="right aligned">
          <div class="ui center pagination menu" name="pagination_kepegawaian"></div>
        </th>
      </tr>
    </tfoot>
  </table>
</div>