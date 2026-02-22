<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SIPD</title>

    <link rel="stylesheet" href="/assets/css/fomantic.min.css">
    <style>
        #mainContext {
            min-height: calc(100vh - 40px);
        }

        .pusher .ui .container {
            padding: 20px !important;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        /* FIX SEGMENT PUSHABLE */
        #mainContext.ui.segment {
            height: calc(100vh - 40px);
            padding: 0 !important;
            overflow: hidden;
            /* PENTING */
        }

        /* PUSHER */
        #mainContext .pusher {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* STICKY HEADER */
        #mainContext .pusher>.ui.sticky {
            flex: 0 0 auto;
        }

        /* SCROLL AREA */
        #mainContext .content-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* ================================
   SIDEBAR KANAN WIDTH CONTROL
================================ */

        /* Default (Desktop) */
        .sidebarkanan {
            width: 420px !important;
        }

        /* Tablet */
        @media (max-width: 992px) {
            .sidebarkanan {
                width: 360px !important;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebarkanan {
                width: 100% !important;
            }
        }

        .sidebarkanan {
            width: 420px;
            display: flex;
        }

        .flyout-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .flyout-body {
            flex: 1;
            overflow-y: auto;
        }

        .flyout-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        #toastContainer {
            position: absolute;
            top: 60px;
            right: 20px;
            width: 350px;
            z-index: 5;
        }

        /* untuk tabel */
        .table-wrapper {
            overflow-x: auto;
        }
    </style>
</head>

<body>


    <!-- NAVBAR (DI LUAR PUSHABLE) @note navbar -->
    <div class="ui top attached menu">
        <div class="item" id="toggleSidebar">
            <a>
                <i class="bars icon"></i>
            </a>
        </div>
        <div class="right menu">
            <div class="ui inline item dropdown" id="countRow"><span><i class="list icon"></i></span><input type="hidden" name="countRow" value="5">
                <div class="text">5</div>
                <div class="menu">
                    <div class="item" data-value="all">All</div>
                    <div class="item selected" data-value="5">5</div>
                    <div class="item" data-value="10">10</div>
                    <div class="item" data-value="15">15</div>
                    <div class="item" data-value="20">20</div>
                    <div class="item" data-value="30">30</div>
                    <div class="item" data-value="40">40</div>
                    <div class="item" data-value="50">50</div>
                    <div class="item" data-value="100">100</div>
                </div>
            </div>
            <div class="item">
                <div class="ui cari_data transparent icon input">
                    <input type="text" placeholder="Search..." name="cari_data" id="cari_data">
                    <i class="search link icon"></i>
                </div>
            </div>
            <div class="right menu">
                <div class="ui dropdown item"><span><i class="user icon"></i></span><i class="dropdown icon"></i>
                    <div class="menu"><a class="item" data-tab="wallchat"><i class="circular comments outline icon"></i>Pesan</a><a class="item" name="change_themes"><i class="circular moon icon"></i>Change Themes</a><a class="item" data-tab="profil"><i class="circular qrcode icon"></i>Pengaturan</a><a class="item" href="/logout"><i class="circular sign out alternate icon"></i>Log
                            Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="toastContainer"></div>
    </div>
    <!-- MENU UTAMA SIDEBAR -->
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>
    <!-- FLYOUT -->
    <?php require __DIR__ . '/../partials/flyout.php'; ?>
    <!-- MAIN MODAL -->
    <?php require __DIR__ . '/../partials/main_modal.php'; ?>
    <!-- PUSHER -->
    <div class="pusher">

        <!-- sticky-->
        <div class="ui sticky">
            <div class="ui icon message dashboard"><i class="home icon"></i>
                <div class="content">
                    <div class="header">DASHBOARD</div>
                    <div class="pDashboard">seSendok</div>
                </div>
            </div>
        </div>
        <div class="content-scroll">
            <?= $content ?>
        </div>

    </div>

    </div>

    <!-- JS -->
    <script>
        window.USER_ROLE = "<?= $_SESSION['user']['type_user'] ?? '' ?>";
    </script>
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/fomantic.min.js"></script>
    <?php if (strpos($_SERVER['REQUEST_URI'], 'tata_naskah') !== false): ?>
        <script src="/assets/js/engine/document/document_schema.js"></script>
<script src="/assets/js/engine/document/document_builder.js"></script>
    <?php endif; ?>
    <script src="/assets/js/app.js"></script>
    <?php if (strpos($_SERVER['REQUEST_URI'], 'tata_naskah') !== false): ?>
        <script src="/assets/js/modules/tata_naskah.js"></script>
        <link rel="stylesheet" href="/assets/vendor/quill/quill.snow.css">
        <script src="/assets/vendor/quill/quill.min.js"></script>
    <?php endif; ?>
</body>

</html>