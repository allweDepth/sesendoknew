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
    overflow-x: hidden;
    overflow-y: auto;
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
    padding-top: 28px !important;
  }

  /* editor */
  .doc-editor {
    min-height: 32px;
    outline: none;
  }

  /* toolbar floating */
  .doc-toolbar {
    position: absolute;
    top: 2px;
    left: 4px;
    display: none;
    gap: 2px;
  }

  /* tampil saat hover / focus */
  .doc-cell:hover .doc-toolbar,
  .doc-editor:focus+.doc-toolbar {
    display: flex;
  }

  /* kecilkan button */
  .doc-toolbar .button {
    padding: 4px !important;
  }

  /* aktif */
  .doc-toolbar .button.active {
    background: #2185d0 !important;
    color: #fff !important;
  }

  /* ================================
   MOBILE FIX (TIDAK GANGGU DESKTOP)
================================ */

  @media (max-width: 768px) {

    /* 🔥 table tidak kepotong */
    .ui.table {
      display: block;
      overflow-x: auto;
    }

    /* 🔥 cell spacing normal */
    .doc-cell {
      padding-top: 8px !important;
    }

    /* 🔥 toolbar jadi inline */
    .doc-toolbar {
      position: static !important;
      display: grid !important;
      grid-template-columns: repeat(4, 1fr);
      gap: 4px;
      margin-bottom: 6px;
    }

    /* 🔥 editor lebih besar */
    .doc-editor {
      width: 100%;
      min-height: 60px;
    }

    /* 🔥 tombol lebih besar (touch friendly) */
    .doc-toolbar .button {
      padding: 8px !important;
      font-size: 14px;
    }
  }
  </style>
</head>

<body>

  <?php if (!isset($_SESSION['user'])) {
    header("Location: /");
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

  <!-- ================= CORE ================= -->
  <script src="/assets/js/core/config.js"></script>
  <script src="/assets/js/core/state.js"></script>
  <script src="/assets/js/core/toast.js"></script>
  <script src="/assets/js/core/ajax.js"></script> <!-- HARUS SEBELUM app.js -->
  <script src="/assets/js/config/ui-config.js"></script>
  <script src="/assets/js/core/spa-router.js"></script>
  <script src="/assets/js/core/page-loader.js"></script>

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