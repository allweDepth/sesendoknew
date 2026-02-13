<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="/vendor/semantic.min.css">
</head>
<body>
    <div class="ui top fixed menu">
        <a class="item sidebar-toggle"><i class="sidebar icon"></i></a>
        <!-- Navbar lain -->
    </div>
    <div class="ui vertical inverted sidebar menu left">
        <a class="item" href="/anggaran">Anggaran <i class="dropdown icon"></i></a>
        <div class="menu">
            <a class="item" href="/anggaran/renstra">Renstra</a>
            <a class="item" href="/anggaran/renja">Renja</a>
            <a class="item" href="/anggaran/dpa">DPA</a>
            <a class="item" href="/anggaran/renja_perubahan">Renja Perubahan</a>
            <a class="item" href="/anggaran/dppa">DPPA</a>
        </div>
        <a class="item" href="/kontrak">Kontrak</a>
        <a class="item" href="/realisasi">Realisasi <i class="dropdown icon"></i></a>
        <div class="menu">
            <a class="item" href="/realisasi/input_realisasi">Input Realisasi</a>
            <a class="item" href="/realisasi/spj">SPJ</a>
            <a class="item" href="/realisasi/evaluasi_renja">Evaluasi Renja</a>
        </div>
        <!-- Tambah menu lain: Referensi, Kepegawaian, Chat, Pengaturan -->
    </div>
    <div class="pusher" style="margin-top: 50px;">
        <?= $content ?>  <!-- Isi view di sini -->
    </div>
    <script src="/vendor/jquery.min.js"></script>
    <script src="/vendor/semantic.min.js"></script>
    <script>
        $('.sidebar-toggle').on('click', function() {
            $('.ui.sidebar').sidebar('toggle');
        });
        $('.ui.accordion').accordion();
        $('.ui.dropdown').dropdown();
    </script>
</body>
</html>