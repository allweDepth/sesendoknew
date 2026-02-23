<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>404 - Halaman Tidak Ditemukan</title>

    <link rel="stylesheet" href="/assets/css/fomantic.min.css">
    <link rel="stylesheet" href="/assets/css/dark.css">

    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
        }

        body.dark-mode {
            background: #1b1c1d;
        }

        .error-box {
            text-align: center;
            max-width: 500px;
            padding: 40px;
        }

        .error-code {
            font-size: 80px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .error-icon {
            font-size: 70px;
            margin-bottom: 20px;
        }

        .dark-mode .error-code,
        .dark-mode .error-text {
            color: #ffffff;
        }
    </style>
</head>

<body>

<div class="ui raised very padded segment error-box">

    <div class="error-icon">
        <i class="exclamation triangle red icon"></i>
    </div>

    <div class="error-code">404</div>

    <h2 class="ui header error-text">
        Halaman Tidak Ditemukan
    </h2>

    <p>
        Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.
    </p>

    <div class="ui hidden divider"></div>

    <a href="/" class="ui primary button">
        <i class="home icon"></i>
        Kembali ke Dashboard
    </a>

    <div class="ui hidden divider"></div>

    <button onclick="history.back()" class="ui basic button">
        <i class="arrow left icon"></i>
        Kembali ke Halaman Sebelumnya
    </button>

</div>

<script>
    // Apply dark mode jika tersimpan
    if (localStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark-mode");
    }
</script>

</body>
</html>