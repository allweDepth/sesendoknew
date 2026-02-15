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
        <th width="5%">No</th>
        <th>Nama</th>
        <th width="15%">Aksi</th>
      </tr>
    </thead>


    <!-- INI SAJA YANG BERBEDA -->
    <tbody name="tabel_kepegawaian">
    <tr>
      <td colspan="3">
        <div class="ui active inline loader"></div>
      </td>
    </tr>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3">
          <div class="ui center pagination menu" name="pagination_kepegawaian"></div>
        </th>
      </tr>
    </tfoot>
  </table>
</div>