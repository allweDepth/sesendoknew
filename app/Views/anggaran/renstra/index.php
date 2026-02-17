<div class="ui container">

  <h2 class="ui dividing header">
    Modul Renstra
  </h2>

  <!-- MENU -->
  <div class="ui secondary pointing menu" id="renstraMenu">
    <a class="item" data-tbl="renstra_neo">Data Renstra</a>
    <a class="item active" data-tbl="misi_renstra_neo">Misi</a>
    <a class="item" data-tbl="tujuan_renstra_neo">Tujuan</a>
    <a class="item" data-tbl="sasaran_renstra_neo">Sasaran</a>
    <a class="item" data-tbl="indikator_sasaran_renstra_neo">Indikator Sasaran</a>
    <a class="item" data-tbl="program_renstra_neo">Program</a>
    <a class="item" data-tbl="indikator_program_renstra_neo">Indikator Program</a>
    <a class="item" data-tbl="anggaran_program_renstra_neo">Anggaran</a>
  </div>

  <!-- ACTION BUTTON -->
  <div class="ui right floated basic icon buttons" style="margin-top:10px;">
    <button class="ui button"
      data-ui="open-form"
      data-jns="add"
      data-tbl="misi_renstra_neo"
      id="btnTambah">
      
      <i class="plus icon"></i>
    </button>

    <button class="ui button"
      data-ui="open-form"
      data-jns="import"
      id="btnImport">
      <i class="upload icon"></i>
    </button>

    <button class="ui icon button"
      data-action="export"
      id="btnExport">
      <i class="alternate download icon"></i>
    </button>
  </div>

  <div class="ui hidden divider"></div>

  <h3 class="ui dividing header" id="judulTabel">
    MISI
  </h3>

  <!-- SATU TABEL SAJA -->
  <table class="ui celled striped table">
  <thead>
    <tr>
      <th>Data</th>
      <th class="collapsing">Aksi</th>
    </tr>
  </thead>

  <tbody name="tabel_renstra">
    <tr>
      <td colspan="2">
        <div class="ui active inline loader"></div>
      </td>
    </tr>
  </tbody>

  <tfoot>
    <tr>
      <td colspan="2" class="right aligned">
        <div name="pagination_renstra"></div>
      </td>
    </tr>
  </tfoot>
</table>

