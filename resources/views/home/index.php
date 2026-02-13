<?php
// app/Views/home.php
// View awal (belum login) - localhost/sesendoknew/home

// Kalau pakai layout terpisah, include dulu guest layout
// require VIEW_PATH . '/layouts/guest.php';

// Kalau tidak pakai layout terpisah, mulai dari sini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>seSendok - Selamat Datang</title>
    <link rel="stylesheet" href="/assets/vendor/fomantic-ui/semantic.min.css">
    <style>
        body {
            background: linear-gradient(to right, #00b4db, #0083b0);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome-container {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="welcome-container">
    <h1 class="ui huge teal header">
        <i class="circular food icon"></i>
        Selamat Datang di seSendok
    </h1>

    <div class="ui hidden divider"></div>

    <p class="ui large text">
        Aplikasi Perencanaan, Anggaran, dan Realisasi Daerah<br>
        <small>Berbasis Web • Full Fomantic-UI • Struktur MVC</small>
    </p>

    <div class="ui hidden divider"></div>

    <div class="ui two huge buttons">
        <a href="/sesendoknew/login" class="ui teal button">
            <i class="sign in alternate icon"></i> Masuk (Login)
        </a>
        <div class="or" data-text="atau"></div>
        <a href="/sesendoknew/register" class="ui button">
            <i class="user plus icon"></i> Daftar (Register)
        </a>
    </div>

    <div class="ui hidden divider"></div>

    <div class="ui message info">
        <div class="header">Catatan Penting</div>
        <p>Gunakan akun yang sudah terdaftar atau daftar baru untuk mengakses dashboard, sidebar, dan menu Anggaran (Renstra, Renja, DPA, dll.)</p>
    </div>
</div>

<script src="/assets/vendor/fomantic-ui/semantic.min.js"></script>
<script>
    // Inisialisasi Fomantic-UI jika ada komponen yang perlu (opsional)
    $('.ui.button').addClass('animated fadeIn');
</script>

</body>
</html>