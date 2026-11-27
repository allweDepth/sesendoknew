<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title><?= $escape($article['judul'] ?? $pages[$active][1]) ?> | seSendok Pasangkayu</title>

  <link rel="stylesheet" href="<?= asset_url('/assets/vendor/fomantic-ui/semantic.min.css') ?>">
  <script src="<?= asset_url('/assets/vendor/jquery/jquery.min.js') ?>"></script>
  <script src="<?= asset_url('/assets/vendor/fomantic-ui/semantic.min.js') ?>"></script>
  <script>window.APP_BASE_PATH = <?= json_encode(APP_BASE_PATH) ?>;</script>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= asset_url('/assets/css/public-portal.css') ?>">
  <script defer src="<?= asset_url('/assets/js/public-portal.js') ?>"></script>
</head>

<body data-section="<?= $escape($active) ?>">

  <?php require __DIR__ . '/../partials/public_menu.php'; ?>

  <main class="public-shell" id="portal-main">
    <?= $content ?>
  </main>
  <footer class="public-footer">
    <div class="ui container footer-inner">
      <div><strong>seSendok Pasangkayu</strong><p>Dinas Pekerjaan Umum dan Penataan Ruang</p></div>
    </div>
  </footer>

  <?php require __DIR__ . '/../partials/auth_modal.php'; ?>
  <script src="<?= app_url('/assets/js/auth-session.js') ?>"></script>

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

      $('#registerModal .ui.dropdown').dropdown();


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
                type: 'notEmpty',
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
                type: 'notEmpty',
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
              type: 'notEmpty',
              prompt: 'Nama wajib diisi'
            }]
          },
          nip: {
            rules: [{
              type: 'notEmpty',
              prompt: 'NIP wajib diisi'
            }]
          },
          kontak_person: {
            rules: [{
              type: 'notEmpty',
              prompt: 'Kontak wajib diisi'
            }]
          },
          alamat: {
            rules: [{
              type: 'notEmpty',
              prompt: 'Alamat wajib diisi'
            }]
          },
          password: {
            rules: [{
                type: 'notEmpty',
                prompt: 'Password wajib diisi'
              },
              {
                type: 'minLength[12]',
                prompt: 'Minimal 12 karakter'
              }
            ]
          },
          kd_wilayah: {
            rules: [{
              type: 'notEmpty',
              prompt: 'Wilayah wajib dipilih'
            }]
          },
          kd_opd: {
            rules: [{
              type: 'notEmpty',
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
