<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>seSendok</title>

  <link rel="stylesheet" href="/assets/css/fomantic.min.css">
  <link rel="stylesheet" href="/assets/css/dark.css">
  <style>
  #mainContext {
    min-height: calc(100vh - 40px);
  }

  .pusher .ui .container {
    padding: 20px !important;
  }

  /* 🔥 FIX GLOBAL SCROLL (JANGAN LOCK TOTAL) */
  html,
  body {
    height: 100%;
    overflow: hidden;
    /* 🔥 KUNCI UTAMA */
  }

  /* FIX SEGMENT PUSHABLE */
  #mainContext.ui.segment {
    height: calc(100vh - 40px);
    padding: 0 !important;
    overflow: hidden;
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

  /* Sidebar kiri tetap dapat digulir, tetapi identitas aplikasi/tahun tidak ikut hilang. */
  #mainContext .sidebarutama {
    overflow-y: auto !important;
    scrollbar-width: thin;
    scrollbar-color: rgba(84, 200, 255, .65) transparent;
    background: linear-gradient(180deg, #14283b 0%, #0c1722 100%) !important;
  }
  #mainContext .sidebarutama > .item:first-child {
    position: sticky;
    top: 0;
    z-index: 12;
    background: linear-gradient(135deg, #173a58, #10283c) !important;
    box-shadow: 0 8px 22px rgba(0,0,0,.28);
  }
  #mainContext .sidebarutama .content .item {
    margin: 3px 8px !important;
    border-radius: 8px !important;
    transition: background .18s ease, transform .18s ease;
  }
  #mainContext .sidebarutama .content .item:hover,
  #mainContext .sidebarutama .content .item.active {
    background: rgba(84,200,255,.16) !important;
    transform: translateX(3px);
  }
  #crudMenu.ui.pointing.menu {
    background: #fff;
    border: 1px solid #e3ebf3;
    border-radius: 12px;
    padding: 5px;
    box-shadow: 0 8px 25px rgba(20,52,80,.06);
  }

  /* ================================
   SIDEBAR KANAN WIDTH CONTROL
================================ */

  /* Default (Desktop) */
  .sidebarkanan {
    width: 420px !important;
    display: flex;
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

  /* FLYOUT */
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

  /* TOAST */
  #toastContainer {
    position: fixed;
    /* FIX: keluar dari stacking context parent */
    top: 5px;
    right: 20px;
    width: 350px;
    z-index: 99999;
    /* FIX: pastikan di atas dimmer */
  }
  #toastContainer:empty { display:none !important; width:0 !important; }
  .ui.toast-container:empty,.ui.notifications:empty { display:none !important; border:0 !important; box-shadow:none !important; }

  /* TABLE WRAPPER */
  .table-wrapper {
    overflow-x: auto;
  }

  /* ================================
   DOCUMENT BUILDER (DESKTOP)
================================ */

  /* container cell */
  .doc-cell {
    position: relative;
    padding-top: 20px !important;
    /* lebih compact */
  }

  /* editor */
  .doc-editor {
    min-height: 32px;
    outline: none;
    padding: 4px;
    line-height: 1.4;
  }

  /* ================= TOOLBAR ================= */

  .doc-toolbar {
    position: absolute;
    top: 0;
    left: 2px;

    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 2px;

    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(4px);

    padding: 2px 4px;
    border-radius: 4px;

    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);

    opacity: 0;
    transform: translateY(-4px);
    transition: all 0.15s ease;

    pointer-events: auto;
    z-index: 10;
  }

  /* tampil saat hover / focus */
  .doc-cell:hover .doc-toolbar,
  .doc-cell:focus-within .doc-toolbar {
    opacity: 1;
    transform: translateY(0);
  }

  /* group tombol */
  .doc-toolbar .btn-group {
    display: flex;
    gap: 2px;
    align-items: center;
  }

  /* divider vertikal */
  .doc-toolbar .divider {
    width: 1px;
    height: 14px;
    background: rgba(0, 0, 0, 0.12);
    margin: 0 2px;
  }

  /* tombol */
  .doc-toolbar .button {
    min-width: 22px;
    height: 22px;
    font-size: 11px;
    padding: 2px !important;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 3px;
  }

  /* hover */
  .doc-toolbar .button:hover {
    background: rgba(0, 0, 0, 0.06) !important;
  }

  /* aktif */
  .doc-toolbar .button.active {
    background: #2185d0 !important;
    color: #fff !important;
  }

  /* ================================
   MOBILE FIX
================================ */

  @media (max-width: 768px) {

    /* table scroll */
    .ui.table {
      display: block;
      overflow-x: auto;
    }

    /* spacing */
    .doc-cell {
      padding-top: 8px !important;
    }

    /* toolbar jadi inline */
    .doc-toolbar {
      position: static;
      opacity: 1;
      transform: none;

      display: flex !important;
      flex-wrap: wrap;
      gap: 6px;

      margin-bottom: 6px;
      padding: 0;

      background: transparent;
      box-shadow: none;
      border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .doc-toolbar .btn-group {
      flex-wrap: wrap;
      gap: 4px;
    }

    .doc-toolbar .divider {
      display: none;
    }

    /* tombol mobile */
    .doc-toolbar .button {
      padding: 8px !important;
      font-size: 14px;
      min-width: 36px;
      height: 36px;
    }

    /* editor */
    .doc-editor {
      width: 100%;
      min-height: 60px;
    }
  }
  </style>
</head>

<body>

  <?php if (!isset($_SESSION['user'])) {
    header('Location: ' . app_url('/'));
    exit;
  } ?>
  <!-- NAVBAR (DI LUAR PUSHABLE) @note navbar -->
  <!-- NAVBAR (DI LUAR PUSHABLE) -->
  <?php require __DIR__ . '/../partials/auth_navbar.php'; ?>

  <!-- SIDEBAR WRAPPER (membuka #mainContext) -->
  <?php require __DIR__ . '/../partials/sidebar.php'; ?>

  <!-- FLYOUT (masih di dalam #mainContext) -->
  <?php require __DIR__ . '/../partials/flyout.php'; ?>

  <!-- MAIN MODAL -->
  <?php require __DIR__ . '/../partials/main_modal.php'; ?>

  <!-- PUSHER -->
  <div class="pusher">

    <div id="toastContainer"></div>
    <!-- sticky-->
    <div class="ui sticky">
      <div class="ui icon message dashboard" id="dynamicHeader"><i class="home icon" id="dynamicHeaderIcon"></i>
        <div class="content">
          <div class="header" id="dynamicHeaderTitle">DASHBOARD</div>
          <div class="pDashboard">seSendok</div>
        </div>
      </div>
    </div>
    <div class="content-scroll">
      <div id="main-content">
        <?= $content ?>
      </div>
    </div>

  </div>



  <!-- JS -->
  <script>
  window.USER_ROLE = "<?= $_SESSION['user']['type_user'] ?? '' ?>";
  </script>
  <script>
  window.app = window.app || {};
  window.app.user = <?= json_encode($_SESSION['user'] ?? []); ?>;
  </script>
  <!-- LIBRARY -->
  <script src="/assets/js/jquery.min.js"></script>
  <script src="/assets/js/fomantic.min.js"></script>
  <script>
  window.APP_BASE_PATH = <?= json_encode(APP_BASE_PATH) ?>;
  window.appUrl = function(path) {
    if (!path || !path.startsWith('/') || path.startsWith('//')) return path;
    if (window.APP_BASE_PATH && (path === window.APP_BASE_PATH || path.startsWith(window.APP_BASE_PATH + '/'))) return path;
    return window.APP_BASE_PATH + path;
  };
  window.appRoutePath = function(path) {
    path = path || '/';
    if (window.APP_BASE_PATH && (path === window.APP_BASE_PATH || path.startsWith(window.APP_BASE_PATH + '/'))) {
      path = path.slice(window.APP_BASE_PATH.length) || '/';
    }
    return path;
  };
  $.ajaxPrefilter(function(options) { options.url = window.appUrl(options.url); });
  const nativeFetch = window.fetch.bind(window);
  window.fetch = function(resource, options) {
    return nativeFetch(typeof resource === 'string' ? window.appUrl(resource) : resource, options);
  };
  </script>

  <!-- ================= CORE ================= -->
  <script src="/assets/js/core/config.js"></script>
  <script src="/assets/js/core/state.js"></script>
  <script src="/assets/js/core/toast.js"></script>
  <script src="/assets/js/core/ajax.js"></script> <!-- HARUS SEBELUM app.js -->
  <script src="/assets/js/config/ui-config.js"></script>
  <script src="/assets/js/core/spa-router.js"></script>
  <script src="/assets/js/core/page-loader.js"></script>
  <script src="/assets/js/core/validation-engine.js"></script>
  <script src="/assets/js/core/dialog.js"></script>
  <!-- UI REGISTRY -->
  <script src="/assets/js/ui/ui-registry.js"></script>
  <!-- ================= ENGINE ================= -->

  <script src="/assets/js/engine/table-manager.js"></script>
  <script src="/assets/js/engine/form-engine.js"></script>
  <script src="/assets/js/engine/form-container-manager.js"></script>
  <script src="/assets/js/engine/flyout-controller.js"></script>
  <script src="/assets/js/engine/dropdown_engine.js"></script>
  <!-- ================= MODULES ================= -->
  <script src="/assets/js/modules/base-crud-module.js"></script>

  <!-- ================= APP CORE ================= -->
  <script src="/assets/js/app.js"></script> <!-- SETELAH semua dependency -->



  <!-- UI COMPONENTS -->
  <script src="/assets/js/ui/ui-components.js"></script>
  <script src="/assets/js/ui/ui-extensions.js"></script>
  <!-- ENGINE -->
  <!-- <script src="/assets/js/engine/document/document_builder.js"></script> -->


  <!-- ================= ENTRY POINT ================= -->
  <script src="/assets/js/app-init.js"></script>

  <script>
  window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
  </script>
</body>

</html>
