<div class="ui stackable grid">
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
            echo '<button class="ui button" name="flyout" data-tooltip="Tambah Data" data-position="bottom center" jns="add" tbl="tujuan_sasaran_renstra"><i class="plus icon"></i></button>
                            <button class="ui button" name="flyout" jns="import" data-tooltip="Import XLSX" data-position="bottom center" jns="import"><i class="upload icon"></i></button>';
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
<script>
$(document).ready(function () {

  // ==============================
  // INIT FOMANTIC
  // ==============================
  $('.ui.dropdown').dropdown();
  $('.ui.checkbox').checkbox();

  $('.ui.calendar').calendar({
    type: 'date',
    formatter: {
      date: function (date) {
        if (!date) return '';
        const day = ("0" + date.getDate()).slice(-2);
        const month = ("0" + (date.getMonth()+1)).slice(-2);
        const year = date.getFullYear();
        return year + '-' + month + '-' + day;
      }
    }
  });

  // ==============================
  // FOMANTIC VALIDATION
  // ==============================
  $('.ui.form').form({
    fields: {
      kd_wilayah: {
        identifier: 'kd_wilayah',
        rules: [{ type: 'empty', prompt: 'Kode Wilayah wajib diisi' }]
      },
      tahun: {
        identifier: 'tahun',
        rules: [{ type: 'empty', prompt: 'Tahun wajib diisi' }]
      },
      tahun_renstra: {
        identifier: 'tahun_renstra',
        rules: [{ type: 'empty', prompt: 'Tahun Renstra wajib dipilih' }]
      },
      aturan_anggaran: {
        identifier: 'aturan_anggaran',
        rules: [{ type: 'empty', prompt: 'Aturan Anggaran wajib dipilih' }]
      }
    }
  });

  // ==============================
  // AUTO LOCK GLOBAL
  // ==============================
  $('input[name="kunci"]').change(function () {
    if ($(this).is(':checked')) {
      $('.ui.form input, .ui.form .ui.dropdown')
        .not('[name="kunci"]')
        .addClass('disabled');
    } else {
      $('.ui.form input, .ui.form .ui.dropdown')
        .removeClass('disabled');
    }
  });

  // ==============================
  // AUTO LOCK PER DOKUMEN
  // ==============================
  function lockSection(trigger, fields) {
    $(trigger).change(function () {
      if ($(this).is(':checked')) {
        fields.forEach(name => {
          $('[name="'+name+'"]').closest('.field')
            .find('input, .ui.dropdown')
            .addClass('disabled');
        });
      } else {
        fields.forEach(name => {
          $('[name="'+name+'"]').closest('.field')
            .find('input, .ui.dropdown')
            .removeClass('disabled');
        });
      }
    });
  }

  lockSection('input[name="kunci_renja"]', ['awal_renja','akhir_renja']);
  lockSection('input[name="kunci_dpa"]', ['awal_dpa','akhir_dpa']);
  lockSection('input[name="kunci_renstra"]', ['awal_renstra','akhir_renstra']);

  // ==============================
  // DYNAMIC LOAD DROPDOWN
  // ==============================
  function loadDropdown(name, url) {
    $.get(url, function (res) {
      let menu = $('[name="'+name+'"]').siblings('.menu');
      menu.html('');
      res.data.forEach(item => {
        menu.append('<div class="item" data-value="'+item.id+'">'+item.nama+'</div>');
      });
    }, 'json');
  }

  // contoh pemanggilan
  loadDropdown('aturan_anggaran', '/referensi/load?jenis=anggaran');
  loadDropdown('aturan_akun', '/referensi/load?jenis=akun');

  // ==============================
  // MODE EDIT (AUTO LOAD DATA)
  // ==============================
  function loadDataEdit() {
    $.get('/pengaturan/load', function (res) {

      if (!res.status) return;

      Object.keys(res.data).forEach(key => {
        let el = $('[name="'+key+'"]');

        if (el.attr('type') === 'checkbox') {
          if (res.data[key] == 1) {
            el.closest('.ui.checkbox').checkbox('check');
          }
        } else if (el.closest('.ui.dropdown').length) {
          el.closest('.ui.dropdown')
            .dropdown('set selected', res.data[key]);
        } else {
          el.val(res.data[key]);
        }
      });

    }, 'json');
  }

  // Panggil kalau mode edit
  loadDataEdit();

  // ==============================
  // SUBMIT FORM
  // ==============================
  $('.ui.form').submit(function (e) {

    e.preventDefault();

    if (!$('.ui.form').form('is valid')) {
      Swal.fire({
        icon: 'warning',
        title: 'Form belum lengkap',
        text: 'Silakan lengkapi data terlebih dahulu'
      });
      return;
    }

    $.ajax({
      url: '/pengaturan/store',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('.ui.primary.button').addClass('loading');
      },
      success: function (res) {

        $('.ui.primary.button').removeClass('loading');

        if (res.status) {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: res.message
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: res.message
          });
        }
      },
      error: function () {
        $('.ui.primary.button').removeClass('loading');
        Swal.fire({
          icon: 'error',
          title: 'Server Error',
          text: 'Terjadi kesalahan sistem'
        });
      }
    });

  });

});
</script>