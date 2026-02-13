<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>seSendok</title>
    <link rel="stylesheet" href="/vendor/semantic.min.css">
    <style>
        body { overflow: hidden; }
        .pusher { height: calc(100vh - 50px); margin-top: 50px; }
    </style>
</head>
<body>

<div class="ui teal top fixed inverted menu">
    <a class="item" id="sidebar-toggle"><i class="sidebar icon"></i></a>
    <div class="right menu">
        <div class="ui dropdown item">
            <i class="user icon"></i> User
            <i class="dropdown icon"></i>
            <div class="menu">
                <a class="item">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="ui inverted left vertical sidebar menu">
    <div class="item"><h3>seSendok</h3></div>
    <a class="item" href="/home"><i class="home icon"></i> Beranda</a>
    <div class="ui accordion item">
        <div class="title"><i class="dropdown icon"></i> Anggaran</div>
        <div class="content menu">
            <a class="item" href="/anggaran"><i class="tags icon"></i> Anggaran</a>
            <a class="item" href="/anggaran/renstra"><i class="sitemap icon"></i> RENSTRA</a>
            <a class="item" href="/anggaran/renja"><i class="tag icon"></i> RENJA</a>
            <a class="item" href="/anggaran/dpa"><i class="tags icon"></i> DPA</a>
            <a class="item" href="/anggaran/renja-perubahan"><i class="tags icon"></i> Renja Perubahan</a>
            <a class="item" href="/anggaran/dppa"><i class="tags icon"></i> DPPA</a>
        </div>
    </div>
</div>

<div class="ui bottom attached pushable">
    <div class="pusher">
        <?php echo $content ?? ''; ?>
    </div>
</div>

<script src="/vendor/semantic.min.js"></script>
<script>
    $('#sidebar-toggle').click(function() {
        $('.ui.sidebar').sidebar('toggle');
    });
    $('.ui.accordion').accordion();
    $('.ui.dropdown').dropdown();
</script>
</body>
</html>