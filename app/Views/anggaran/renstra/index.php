<div class="ui stackable grid renstra-page">
  <div class="two wide left column">
    <div class="ui red secondary vertical pointing fluid menu">
      <a class="active item inayah" data-tab="tab_renstra" tbl="renstra">
        Renstra
      </a>
      <a class="item inayah" data-tab="tab_renstra" tbl="tujuan_sasaran_renstra">
        Tujuan dan Sasaran
      </a>
    </div>
  </div>
  <div class="fourteen wide stretched right column">
    <h1 class="ui header">Rencana Strategis (Renstra) <div class="sub header">dokumen perencanaan
        berorientasi
        pada hasil yang ingin dicapai</div>
    </h1>
    <div class="ui hidden divider"></div>
    <div class="ui stretched stackable five column grid">
      <div class="column">
        <div class="ui orange icon message goyang"><i class="book icon"></i>
          <div class="content">
            <div class="header">Total Anggaran</div>
            <p name="total-anggaran"></p>
          </div>
        </div>
      </div>
      <div class="column">
        <div class="ui icon yellow message goyang">
          <i class="chart icon" name="chart-realisasi-fisik-mini"></i>
          <div class="content">
            <div class="header">Jumlah Program</div>
            <p name="realisasi-fisik"></p>
          </div>
        </div>
      </div>
      <div class="column">
        <div class="ui olive icon message goyang"><i class="chart icon" name="chart-realisasi-keu-mini"></i>
          <div class="content">
            <div class="header">Jumlah Kegiatan</div>
            <p name="realisasi-keu"></p>
          </div>
        </div>
      </div>
      <div class="column">
        <div class="ui icon red message goyang"><i class="spinner loading icon"></i>
          <div class="content">
            <div class="header">Jumlah Sub Kegiatan</div>
            <p name="sisa-fisik"></p>
          </div>
        </div>
      </div>
      <div class="column">
        <div class="ui positive icon message goyang"><i class="spinner loading icon"></i>
          <div class="content">
            <div class="header">Sisa Keuangan</div>
            <p name="sisa-keu"></p>
          </div>
        </div>
      </div>
      <div class="ui fluid container">
        <div class="ui hidden divider"></div>
        <div class="ui right floated basic icon buttons">
          <?php
          if ($_SESSION['user']['type_user'] == 'admin') {
            echo '<button class="ui button" data-ui="open-form" data-tooltip="Tambah Data" data-position="bottom center" jns="add" tbl="tujuan_sasaran_renstra"><i class="plus icon"></i></button>
                            <button class="ui button" data-ui="open-form" jns="import" data-tooltip="Import XLSX" data-position="bottom center" jns="import"><i class="upload icon"></i></button>';
          }
          ?>
          <button class="ui button" data-tooltip="Download" data-position="bottom center" name="ungguh" jns="dok" tbl="" type="submit"><i class="alternate download icon"></i></button>
        </div>
        <h3 class="ui dividing header"><i class="left align icon"></i>Tabel Dokumen</h3>
        <div class="ui hidden divider"></div>
        <div class="ui hidden divider"></div>
        <div class="ui long scrolling fluid container">
          <table class="ui head foot stuck unstackable celled striped table insert">
            <thead>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
