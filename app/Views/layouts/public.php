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
    $('.ui.dropdown').dropdown();
    // $('.menu .item').tab();
    $('#btnLogin').click(function() {
      $('#loginModal').modal('show');
    });
    $('#btnRegister').click(function() {
      $('#registerModal').modal('show');
    });
    $('#registerModal').modal({
      closable: false
    });
  </script>
</body>
</html>