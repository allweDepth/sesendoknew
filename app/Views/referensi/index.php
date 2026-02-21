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
      data-container="flyout">
      <i class="plus icon"></i>
    </button>
    <button class="ui button" data-ui="open-form" data-tbl="<?= $tbl ?>" data-jns="import_xlsx" data-tooltip="Import XLSX" data-position="bottom center" data-container="flyout"><i class="upload icon"></i></button> 
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

  <div class="table-wrapper">
    <table class="ui very compact celled striped unstackable table">
      <thead>
        <tr>
            <th></th>
          <th class="collapsing">Aksi</th>
        </tr>
      </thead>
    <tbody name="tabel_referensi">
      <tr>
        <td colspan="<?= $totalCol ?>">
          <div class="ui active inline loader"></div>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="100%" class="right aligned">
          <div name="pagination_referensi"></div>
        </td>
      </tr>
    </tfoot>
  </table>
</div>
</div>