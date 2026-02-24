<?php
$tbl      = $tbl      ?? 'trx_naskah_dinas';
$jenis    = $jenis    ?? 'default';
$totalCol = $totalCol ?? 5;
?>
<div class="ui container">

  <div class="ui info message">
    Data Naskah: <?= strtoupper($tbl) ?>
  </div>

  <div class="ui hidden divider"></div>

  <div class="ui right floated basic icon buttons">

    <!-- ADD (DIRECT KE BUAT) -->
    <a href="/tata_naskah/buat"
       class="ui button"
       data-tooltip="Buat Naskah"
       data-position="bottom center">
        <i class="plus icon"></i>
    </a>

  </div>

  <h3 class="ui dividing header">
    <i class="file alternate icon"></i>
    Tabel <?= $tbl ?>
  </h3>

  <div class="ui hidden divider"></div>

  <div class="table-wrapper">
    <table class="ui very compact celled striped unstackable table">
      <thead>
        <tr>
          <th>No</th>
          <th>Nomor</th>
          <th>Status</th>
          <th>Tanggal</th>
          <th class="collapsing">Aksi</th>
        </tr>
      </thead>

      <tbody name="tabel_<?= $tbl ?>">
        <tr>
          <td colspan="<?= $totalCol ?>" class="center aligned">
            <div class="ui active inline loader"></div>
          </td>
        </tr>
      </tbody>

      <tfoot>
        <tr>
          <td colspan="100%" class="right aligned">
            <div name="pagination_<?= $tbl ?>"></div>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>

</div>

