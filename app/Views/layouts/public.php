<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>seSendok</title>

  <link rel="stylesheet" href="/assets/css/fomantic.min.css">
  <script src="/assets/js/jquery.min.js"></script>
  <script src="/assets/js/fomantic.min.js"></script>
  <script src="/node_modules/crypto-js/crypto-js.js"></script>
</head>

<body>

  <!-- ================= MENU ================= -->
  <?php require __DIR__ . '/../partials/public_menu.php'; ?>

  <!-- ================= CONTENT ================= -->
  <div class="ui container" style="margin-top:40px">
    <?= $content ?>
  </div>

  <!-- ================= AUTH MODAL ================= -->
  <?php require __DIR__ . '/../partials/auth_modal.php'; ?>
  <script>
    $(document).ready(function() {

      /* ================= MODAL ================= */

      $('#loginModal').modal({
        autofocus: false,
        observeChanges: true
      });

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

      $('.ui.dropdown').dropdown();

      // LOAD WILAYAH (AMAN DARI ERROR)
      $.ajax({
        url: 'wilayah/load',
        method: 'GET',
        dataType: 'json',
        success: function(res) {

          if (!Array.isArray(res)) {
            console.error('Wilayah bukan array:', res);
            return;
          }

          let menu = '';

          res.forEach(function(item) {
            menu += '<div class="item" data-value="' + item.kode + '">' + item.uraian + '</div>';
          });

          $wilayah.find('.menu').html(menu);
          $wilayah.dropdown('refresh');
        },
        error: function(xhr) {
          console.error('Error wilayah:', xhr.responseText);
        }
      });


      // CHANGE WILAYAH
      $wilayah.dropdown({
        onChange: function(value) {

          if (!value) return;

          $organisasi.addClass('disabled');
          $organisasi.dropdown('clear');
          $organisasi.find('.menu').html('');

          $.ajax({
            url: 'organisasi/load',
            method: 'GET',
            data: {
              kd_wilayah: value
            },
            dataType: 'json',

            success: function(res) {

              if (!Array.isArray(res)) {
                console.error('Organisasi bukan array:', res);
                return;
              }

              let menu = '';

              res.forEach(function(item) {
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

            error: function(xhr) {

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