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
    <?php require __DIR__ . '/../partials/auth_navbar.php'; ?>

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

    <!-- ================= CORE ================= -->
    <script src="/assets/js/core/config.js"></script>
    <script src="/assets/js/core/state.js"></script>
    <script src="/assets/js/core/toast.js"></script>
    <script src="/assets/js/core/dialog.js"></script>
    <script src="/assets/js/core/ajax.js"></script>

    <!-- ================= CONFIG ================= -->
    <script src="/assets/js/config/role-config.js"></script>
    <script src="/assets/js/config/action-config.js"></script>
    <script src="/assets/js/config/ui-config.js"></script>

    <!-- ================= ENGINE ================= -->
    <script src="/assets/js/engine/form-engine.js"></script>
    <script src="/assets/js/engine/table-manager.js"></script>
    <script src="/assets/js/engine/form-container-manager.js"></script>

    <?php if (strpos($_SERVER['REQUEST_URI'], 'tata_naskah') !== false): ?>
        <script src="/assets/js/engine/document/document_schema.js"></script>
        <script src="/assets/js/engine/document/document_builder.js"></script>
    <?php endif; ?>

    <!-- ================= MODULES ================= -->
    <script src="/assets/js/modules/renstra.js"></script>
    <script src="/assets/js/modules/pengaturan.js"></script>
    <script src="/assets/js/modules/wallchat.js"></script>
    <script src="/assets/js/modules/profil.js"></script>

    <?php if (strpos($_SERVER['REQUEST_URI'], 'tata_naskah') !== false): ?>
        <script src="/assets/js/modules/tata_naskah.js"></script>
        <link rel="stylesheet" href="/assets/vendor/quill/quill.snow.css">
        <script src="/assets/vendor/quill/quill.min.js"></script>
    <?php endif; ?>

    <!-- ================= INIT (ENTRY POINT) ================= -->
    <script src="/assets/js/app-init.js"></script>
</body>

</html>