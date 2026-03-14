<div class="ui stackable grid container" id="profilPage">

  <!-- ==============================
       CARD PROFIL
  ============================== -->
  <div class="four wide column">

    <div class="ui fluid card">

      <div class="image blurring dimmable">
        <div class="ui dimmer">
          <div class="content">
            <div class="center">
              <button type="button" name="direct" jns="upload" tbl="user_sesendok_biila" dok="photo"
                class="ui inverted icon button">
                <i class="camera icon"></i>
                Ganti Foto
              </button>
            </div>
          </div>
        </div>

        <img id="preview_photo" class="ui medium rounded image" src="assets/img/avatar/default.png"
          onerror="this.src='assets/img/avatar/default.png'">
      </div>

      <div class="content center aligned">
        <h3 class="ui header" id="card_nama">
          Nama User
        </h3>
        <div class="meta">
          <span id="card_type">Type User</span>
        </div>
      </div>

      <div class="extra content">
        <div class="ui two small statistics">
          <div class="statistic">
            <div class="value" id="card_tahun">2025</div>
            <div class="label">Tahun Aktif</div>
          </div>
          <div class="statistic">
            <div class="value" id="card_login">-</div>
            <div class="label">Login Terakhir</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ==============================
       FORM PROFIL
  ============================== -->
  <div class="twelve wide column">

    <form class="ui form profil" name="profil" jns="edit" tbl="profil">

      <h3 class="ui dividing header">
        <i class="user icon"></i>
        <div class="content">Pengaturan Akun</div>
      </h3>

      <div class="two fields">
        <div class="field">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" placeholder="Nama Lengkap">
        </div>
        <div class="field">
          <label>NIP</label>
          <input type="text" name="nip" placeholder="NIP">
        </div>
      </div>

      <div class="two fields">
        <div class="field">
          <label>Username</label>
          <input type="text" name="username" readonly>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email">
        </div>
      </div>

      <div class="two fields">
        <div class="field">
          <label>Tahun Anggaran Aktif</label>
          <input type="text" name="tahun">
        </div>
        <div class="field">
          <label>Type User</label>
          <input type="text" name="type_user" readonly>
        </div>
      </div>

      <div class="two fields">
        <div class="field">
          <label>Kontak Person</label>
          <input type="text" name="kontak_person">
        </div>
        <div class="field">
          <label>Kode OPD</label>
          <input type="text" name="kd_organisasi" readonly>
        </div>
      </div>

      <div class="field">
        <label>Organisasi</label>
        <input type="text" name="nama_org" readonly>
      </div>

      <h4 class="ui dividing header">Preferensi Tampilan</h4>

      <div class="three fields">

        <div class="field">
          <label>Theme</label>
          <div class="ui fluid selection dropdown">
            <input type="hidden" name="theme">
            <i class="dropdown icon"></i>
            <div class="default text">Pilih Theme</div>
            <div class="menu">
              <div class="item" data-value="auto">Auto</div>
              <div class="item" data-value="light">Light</div>
              <div class="item" data-value="dark">Dark</div>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Warna Tabel</label>
          <div class="ui fluid selection dropdown">
            <input type="hidden" name="warna_tbl">
            <i class="dropdown icon"></i>
            <div class="default text">Pilih Warna</div>
            <div class="menu">
              <div class="item" data-value="non">Default</div>
              <div class="item" data-value="red">Merah</div>
              <div class="item" data-value="green">Hijau</div>
              <div class="item" data-value="blue">Biru</div>
              <div class="item" data-value="orange">Orange</div>
              <div class="item" data-value="purple">Ungu</div>
              <div class="item" data-value="black">Hitam</div>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Font Size</label>
          <input type="text" name="font_size" readonly>
        </div>

      </div>

      <div class="field">
        <label>Keterangan</label>
        <textarea name="ket" rows="2"></textarea>
      </div>

      <div class="ui hidden divider"></div>

      <button class="ui primary right floated button">
        <i class="save icon"></i>
        Simpan Perubahan
      </button>

      <div class="ui clearing divider"></div>

      <div class="ui success message hidden">
        <div class="header">Berhasil</div>
        <p>Profil berhasil diperbarui</p>
      </div>

      <div class="ui error message hidden"></div>

    </form>

  </div>

</div>

<script>
  function initProfil() {

    // =============================
    // LOAD DATA
    // =============================
    Ajax.post({
      module: 'profil',
      tbl: 'profil',
      action: 'load'
    }).then(function(res) {

      if (res.status === 'success') {

        let d = res.data;

        Object.keys(d).forEach(function(key) {
          let el = $('[name="' + key + '"]');
          if (el.length) {
            el.val(d[key]);
          }
        });

        $('#card_nama').text(d.nama ?? '');
        $('#card_type').text(d.type_user ?? '');
        $('#card_tahun').text(d.tahun ?? '');
        $('#card_login').text(d.tgl_login ?? '-');

        if (d.photo) {
          $('#preview_photo').attr('src', 'uploads/' + d.photo);
        }

        $('.ui.dropdown').dropdown('refresh');
      }

    }, 'json');


    // =============================
    // FORM VALIDATION (FORMANTIC)
    // =============================
    $('.ui.form.profil')
      .form({
        fields: {
          nama: {
            identifier: 'nama',
            rules: [{
              type: 'empty',
              prompt: 'Nama tidak boleh kosong'
            }]
          },
          email: {
            identifier: 'email',
            rules: [{
                type: 'empty',
                prompt: 'Email tidak boleh kosong'
              },
              {
                type: 'email',
                prompt: 'Format email tidak valid'
              }
            ]
          },
          tahun: {
            identifier: 'tahun',
            rules: [{
              type: 'empty',
              prompt: 'Tahun wajib diisi'
            }]
          }
        },
        onSuccess: function(event, fields) {
          event.preventDefault();

          Ajax.post({
            module: 'profil',
            tbl: 'profil',
            action: 'update',
            data: fields
          }).then(function(res) {

            if (res.status === 'success') {

              $('.ui.form.profil .success.message')
                .removeClass('hidden')
                .transition('fade');

            } else {

              $('.ui.form.profil .error.message')
                .removeClass('hidden')
                .html(res.message);

            }

          }, 'json');
        }
      });

    $('.ui.dropdown').dropdown();
    $('.blurring.dimmable.image').dimmer({
      on: 'hover'
    });

  }

  // Jalankan saat view sudah ada
  setTimeout(function() {
    if ($('#profilPage').length) {
      initProfil();
    }
  }, 100);
</script>