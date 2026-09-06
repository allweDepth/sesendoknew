<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>seSendok</title>

  <link rel="stylesheet" href="<?= app_url('/assets/css/fomantic.min.css') ?>">
  <script src="<?= app_url('/assets/js/jquery.min.js') ?>"></script>
  <script src="<?= app_url('/assets/js/fomantic.min.js') ?>"></script>
  <script>window.APP_BASE_PATH = <?= json_encode(APP_BASE_PATH) ?>;</script>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
  :root{--ink:#102a43;--muted:#627d98;--blue:#1678b8;--teal:#00a69c;--paper:#f5f8fb}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Lato,Arial,sans-serif}.public-nav{height:76px!important;display:flex!important;align-items:center;gap:22px}.brand{display:flex;align-items:center;gap:10px;color:var(--ink);margin-right:auto}.brand img{width:43px;height:43px;object-fit:contain}.brand b,.brand small{display:block}.brand b{font-size:1.2rem}.brand small{color:var(--muted)}.public-links{display:flex;align-items:center;gap:4px}.public-links .item{padding:11px 13px;color:#486581;border-radius:10px;font-weight:700}.public-links .item.active,.public-links .item:hover{background:#e4f2fa;color:#0b6da8}.public-account{display:flex;gap:7px}.nav-toggle{display:none;border:0;background:none;font-size:1.25rem}.public-shell{margin:0!important;max-width:none!important}.public-footer{padding:50px 0 28px;background:#102a43;color:#d9e2ec;margin-top:70px}.public-footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:40px}.public-footer h3{color:#fff}.public-footer a{display:block;color:#bcccdc;margin:8px 0}.public-footer small{color:#829ab1}@media(max-width:850px){.public-nav{height:auto!important;padding:14px!important;flex-wrap:wrap}.nav-toggle{display:block}.public-links{display:none;order:4;width:100%;flex-direction:column;align-items:stretch}.public-nav.open .public-links{display:flex}.public-account .basic.button{display:none}.public-footer-grid{grid-template-columns:1fr}}
  </style>
</head>

<body>

  <?php require __DIR__ . '/../partials/public_menu.php'; ?>

  <main class="public-shell">
    <?= $content ?>
  </main><footer class="public-footer"><div class="ui container public-footer-grid"><div><h3>seSendok Kabupaten Pasangkayu</h3><p>Satu ekosistem data perencanaan, penganggaran, pelaksanaan, dan akuntabilitas kinerja daerah.</p><small>Informasi pada portal dikelola oleh perangkat daerah terkait.</small></div><div><h3>Navigasi</h3><a href="<?= app_url('/berita') ?>">Berita</a><a href="<?= app_url('/datateknis') ?>">Data Teknis</a><a href="<?= app_url('/pelayanan') ?>">Pelayanan</a></div><div><h3>Akses cepat</h3><a href="#" id="footerLogin">Login aplikasi</a><a href="<?= app_url('/organisasi') ?>">Struktur Organisasi</a></div></div></footer>

  <?php require __DIR__ . '/../partials/auth_modal.php'; ?>

  <script>
    $(document).ready(function() {

      /* ================= MODAL ================= */

      $('#loginModal').modal({
        autofocus: false,
        observeChanges: true
      });

      <?php if (!empty($loginError)): ?>
      $('#loginModal').modal('show');
      <?php endif; ?>

      $('#registerModal').modal({
        closable: false,
        autofocus: false,
        observeChanges: true
      });

      $('#btnLogin').on('click', function() {
        $('#loginModal').modal('show');
      });
      $('#footerLogin').on('click',function(e){e.preventDefault();$('#loginModal').modal('show')});
      $('.nav-toggle').on('click',function(){$('.public-nav').toggleClass('open')});
      $(document).on('click','a[href^="/"]',function(e){const href=$(this).attr('href');if(!window.APP_BASE_PATH||href===window.APP_BASE_PATH||href.startsWith(window.APP_BASE_PATH+'/'))return;e.preventDefault();window.location.href=window.APP_BASE_PATH+href;});

      $('#btnRegister').on('click', function() {
        $('#registerModal').modal('show');
      });


      /* ================= DROPDOWN ================= */

      const $wilayah = $('#dropdownWilayah');
      const $organisasi = $('#dropdownOrganisasi');

      $.ajaxPrefilter(function(options) {
        if (options.url && options.url.startsWith('/') && !options.url.startsWith('//')) {
          options.url = window.APP_BASE_PATH + options.url;
        }
      });

      $('.ui.dropdown').dropdown();


      /* ================= LOAD WILAYAH ================= */

      $.ajax({
        url: '/api',
        method: 'GET',
        dataType: 'json',
        data: {
          tbl: 'wilayah_neo', // // gunakan langsung nama tabel
          action: 'get' // // gunakan action standar service
        },
        success: function(res) {

          if (!res.success || !Array.isArray(res.data)) {
            console.error('Wilayah invalid:', res);
            return;
          }

          let menu = '';

          res.data.forEach(function(item) {
            menu += '<div class="item" data-value="' + item.kode + '">' + item.uraian + '</div>';
          });

          $wilayah.find('.menu').html(menu);
          $wilayah.dropdown('refresh');
        },
        error: function(xhr) {
          console.error('Error wilayah:', xhr.responseText);
        }
      });


      /* ================= CHANGE WILAYAH ================= */

      $wilayah.dropdown({
        onChange: function(value) {

          if (!value) return;

          $organisasi.addClass('disabled');
          $organisasi.dropdown('clear');
          $organisasi.find('.menu').html('');

          $.ajax({
            url: '/api',
            method: 'GET',
            dataType: 'json',
            data: {
              tbl: 'organisasi_neo',
              action: 'get',
              kd_wilayah: value
            },
            success: function(res) {

              if (!res.success || !Array.isArray(res.data)) {
                console.error('Organisasi invalid:', res);
                return;
              }

              let menu = '';

              res.data.forEach(function(item) {
                menu += '<div class="item" data-value="' + item.kode + '">' + item.uraian + '</div>';
              });

              $organisasi.find('.menu').html(menu);
              $organisasi.removeClass('disabled');
              $organisasi.dropdown('refresh');
            },
            error: function(xhr) {
              console.error('Error organisasi:', xhr.responseText);
            }
          });

        }
      });


      /* ================= FORM VALIDATION ================= */

      $('#formRegister').form({

        fields: {
          username: {
            rules: [{
                type: 'empty',
                prompt: 'Username wajib diisi'
              },
              {
                type: 'minLength[4]',
                prompt: 'Minimal 4 karakter'
              }
            ]
          },
          email: {
            rules: [{
                type: 'empty',
                prompt: 'Email wajib diisi'
              },
              {
                type: 'email',
                prompt: 'Format email tidak valid'
              }
            ]
          },
          nama: {
            rules: [{
              type: 'empty',
              prompt: 'Nama wajib diisi'
            }]
          },
          nip: {
            rules: [{
              type: 'empty',
              prompt: 'NIP wajib diisi'
            }]
          },
          kontak_person: {
            rules: [{
              type: 'empty',
              prompt: 'Kontak wajib diisi'
            }]
          },
          alamat: {
            rules: [{
              type: 'empty',
              prompt: 'Alamat wajib diisi'
            }]
          },
          password: {
            rules: [{
                type: 'empty',
                prompt: 'Password wajib diisi'
              },
              {
                type: 'minLength[6]',
                prompt: 'Minimal 6 karakter'
              }
            ]
          },
          kd_wilayah: {
            rules: [{
              type: 'empty',
              prompt: 'Wilayah wajib dipilih'
            }]
          },
          kd_opd: {
            rules: [{
              type: 'empty',
              prompt: 'Organisasi wajib dipilih'
            }]
          },
          setuju: {
            rules: [{
              type: 'checked',
              prompt: 'Harus menyetujui ketentuan'
            }]
          }
        },

        onSuccess: function(event) {

          event.preventDefault();

          let $form = $('#formRegister');
          let $button = $form.find('button[type=submit]');

          $button.addClass('loading');
          $form.removeClass('error success');
          $form.find('.error.message').hide();

          $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {

              $button.removeClass('loading');

              if (response.status === 'success') {

                $('body').toast({
                  class: 'success',
                  message: response.message
                });

                $form.form('reset');
                $organisasi.addClass('disabled');

              } else {

                $form
                  .addClass('error')
                  .find('.error.message')
                  .html(response.message)
                  .show();
              }
            },
            error: function() {

              $button.removeClass('loading');

              $form
                .addClass('error')
                .find('.error.message')
                .html('Terjadi kesalahan server')
                .show();
            }
          });

        }

      });

    });
  </script>

</body>

</html>
