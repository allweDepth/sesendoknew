<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>SIPD</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">

</head>
<body>

<!-- SIDEBAR -->
<div class="ui sidebar inverted vertical menu left">
    <a class="item" href="/">Dashboard</a>

    <div class="item">
        <div class="header">Anggaran</div>
        <div class="menu">
            <a class="item" href="/renstra">Renstra</a>
            <a class="item" href="/renja">Renja</a>
            <a class="item" href="/dpa">DPA</a>
            <a class="item" href="/renja_perubahan">Renja Perubahan</a>
            <a class="item" href="/dppa">DPPA</a>
        </div>
    </div>

    <a class="item" href="/logout">Logout</a>
</div>

<!-- PUSHER -->
<div class="pusher">

    <!-- NAVBAR -->
    <div class="ui top fixed menu">
        <div class="item">
            <button class="ui icon button" id="sidebar-toggle">
                <i class="bars icon"></i>
            </button>
        </div>
        <div class="header item">SIPD</div>
    </div>

    <div style="margin-top:70px;padding:20px">
        <?php require __DIR__.'/../'.$path.'.php'; ?>
    </div>

</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
<script src="/assets/js/app.js"></script>

</body>
</html>
