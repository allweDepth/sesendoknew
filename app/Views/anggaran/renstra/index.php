<div class="ui container">

  <h2 class="ui dividing header">
    Modul Renstra
  </h2>

  <!-- ======================================================
     MENU RENSTRA (STRICT SESUAI STRUKTUR SQL)
====================================================== -->
  <div class="ui secondary pointing menu" id="renstraMenu">

    <!-- Level 1 -->
    <a class="item" data-tbl="renstra_neo">Renstra OPD</a>

    <!-- Level 2 -->
    <a class="item active" data-tbl="misi_renstra_neo">Misi</a>

    <!-- Level 3 -->
    <a class="item" data-tbl="tujuan_renstra_neo">Tujuan</a>

    <!-- Level 4 -->
    <a class="item" data-tbl="sasaran_renstra_neo">Sasaran</a>

    <!-- Level 5 -->
    <a class="item" data-tbl="indikator_sasaran_renstra_neo">Indikator Sasaran</a>

    <!-- Level 6 -->
    <a class="item" data-tbl="program_renstra_neo">Program</a>

    <!-- Level 7 -->
    <a class="item" data-tbl="indikator_program_renstra_neo">Indikator Program</a>

    <!-- Level 8 -->
    <a class="item" data-tbl="kegiatan_renstra_neo">Kegiatan</a>

    <!-- Level 9 -->
    <a class="item" data-tbl="sub_kegiatan_renstra_neo">Sub Kegiatan</a>

  </div>

  <!-- ACTION BUTTON -->
  <div class="ui right floated basic icon buttons" style="margin-top:10px;">
    <button class="ui button"
      data-ui="open-form"
      data-jns="add"
      data-tbl="misi_renstra_neo"
      data-server='["renstra_neo"]'
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
        <td colspan="100%" class="right aligned">
          <div name="agination_renstra"></div>
        </td>
      </tr>
    </tfoot>
  </table>