<?php
$tbl      = $tbl      ?? 'trx_naskah_dinas';
$jenis    = $jenis    ?? 'default';
$totalCol = $totalCol ?? 5;
?>
<div class="ui container">

  <div class="ui blue segment"><h2 class="ui header"><i class="file alternate outline icon"></i><div class="content">Daftar Tata Naskah<div class="sub header">Cari dokumen, pantau status, lanjutkan edit, atau cetak PDF.</div></div></h2><div class="ui fluid icon input"><input id="searchTataNaskah" placeholder="Cari nomor, status, atau tanggal..."><i class="search icon"></i></div></div>

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
            <div name="pagination_tata_naskah"></div>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>

</div>
