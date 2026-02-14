<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SIPD</title>

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">

</head>

<body>

    <!-- NAVBAR (DI LUAR PUSHABLE) -->
    <div class="ui top fixed menu">
        <div class="item">
            <a id="sidebar-toggle">
                <i class="bars icon"></i>
            </a>
        </div>

        <div class="header item">
            Sesendok
        </div>

        <div class="right menu">
            <a href="/logout" class="item">Logout</a>
        </div>
    </div>


    <!-- SIDEBAR WRAPPER -->
    <div class="ui pushable" style="margin-top:50px; min-height:100vh;">

        <!-- SIDEBAR -->
        <div class="ui sidebar inverted vertical menu">
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
            <div class="ui container" style="padding-top:20px;">
                <?= $content ?>
            </div>
        </div>

    </div>
    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
    <script src="/assets/js/app.js"></script>

</body>

</html>