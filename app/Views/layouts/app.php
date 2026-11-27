<?php require_once __DIR__.'/../../Core/Auth.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>seSendok</title>

  <link rel="stylesheet" href="<?= asset_url('/assets/vendor/fomantic-ui/semantic.min.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('/assets/vendor/leaflet/leaflet.css') ?>">
  <link rel="stylesheet" href="/assets/css/modern-tables.css?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/css/modern-tables.css') ?>">
  <link rel="stylesheet" href="/assets/css/dark.css?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/css/dark.css') ?>">
  <link rel="stylesheet" href="/assets/css/module-tabs.css?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/css/module-tabs.css') ?>">
  <style>
    .table-content-excerpt {
      display: -webkit-box;
      max-width: 320px;
      overflow: hidden;
      color: #52606d;
      line-height: 1.45;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
    }

    body.dark-mode .table-content-excerpt {
      color: #cbd5e1;
    }

    .pusher .ui .container {
      padding: 20px !important;
    }

    /* Let the toolbar take its actual height; the remaining viewport scrolls. */
    html,
    body {
      height: 100%;
      overflow: hidden;
    }
    body { display: flex; flex-direction: column; height: 100dvh; }
    body > .ui.top.attached.menu { flex: 0 0 auto; width: 100%; margin: 0; }

    /* FIX SEGMENT PUSHABLE */
    #mainContext.ui.segment {
      flex: 1 1 0;
      min-height: 0;
      height: auto;
      width: 100%;
      margin: 0;
      padding: 0 !important;
      overflow: hidden;
    }

    /* PUSHER */
    #mainContext .pusher {
      height: 100%;
      min-height: 0;
      display: flex;
      flex-direction: column;
    }

    /* STICKY HEADER */
    #mainContext .pusher>.ui.sticky {
      flex: 0 0 auto;
    }

    /* SCROLL AREA */
    #mainContext .content-scroll {
      flex: 1 1 0;
      min-height: 0;
      min-width: 0;
      overflow-y: auto;
      padding: 20px;
    }

    #crudMenu.ui.pointing.menu {
      background: #fff;
      border: 1px solid #e3ebf3;
      border-radius: 12px;
      padding: 5px;
      box-shadow: 0 8px 25px rgba(20, 52, 80, .06);
    }

    /* ================================
   SIDEBAR KANAN WIDTH CONTROL
================================ */

    /* Default (Desktop) */
    .sidebarkanan {
      width: 420px !important;
      display: flex;
      background: #fff !important;
      color: #1b1c1d;
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
      /* Toast tidak menutupi header, pencarian, dan dropdown utama. */
      top: var(--app-toast-top, 150px);
      bottom: auto;
      right: 20px;
      width: 350px;
      z-index: 99999;
    }

    #toastContainer:empty {
      display: none !important;
      width: 0 !important;
    }

    #toastContainer:not(:has(.ui.toast)):not(:has(.app-toast)) {
      display: none !important;
      width: 0 !important;
      pointer-events: none !important;
    }

    #toastContainer .toast-box:not(:has(.ui.toast)):not(:has(.app-toast)) {
      display: none !important;
    }

    #toastContainer .app-toast {
      position: relative;
      display: block !important;
      width: 360px;
      max-width: calc(100vw - 28px);
      margin: 0 0 10px !important;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0, 0, 0, .16) !important;
      transition: opacity .18s, transform .18s
    }

    .app-toast-leave {
      opacity: 0;
      transform: translateX(20px)
    }

    .sidebarkanan .form-feedback-zone {
      position: sticky;
      top: 0;
      z-index: 12;
      margin-bottom: 12px;
      background: inherit;
    }

    .sidebarkanan .form-feedback-zone .ui.message {
      margin: 0 0 10px !important;
      box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
    }

    .app-toast-progress {
      position: absolute;
      left: 0;
      bottom: 0;
      height: 3px;
      width: 100%;
      background: rgba(0, 0, 0, .2);
      transform-origin: left;
      animation: appToastProgress 3.2s linear forwards
    }

    @keyframes appToastProgress {
      to {
        transform: scaleX(0)
      }
    }

    .ui.toast-container:empty,
    .ui.notifications:empty {
      display: none !important;
      border: 0 !important;
      box-shadow: none !important;
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
      padding-top: 20px !important;
      /* lebih compact */
    }

    /* editor */
    .doc-editor {
      min-height: 32px;
      outline: none;
      padding: 4px;
      line-height: 1.4;
      text-align-last: left;
    }

    /* Baris terakhir paragraf justified tidak boleh ikut direnggangkan. */
    .doc-editor p,
    .doc-editor div,
    .ql-editor p,
    .document-preview p {
      text-align-last: left;
    }

    body.session-expired #mainContext,
    body.session-expired .ui.top.attached.menu {
      pointer-events: none !important;
      opacity: .72;
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
  <link rel="stylesheet" href="<?= asset_url('/assets/css/workspace.css') ?>">
</head>

<body>
  <script>
    try {
      document.body.classList.toggle('dark-mode', localStorage.getItem('sesendok-theme') === 'dark');
      document.body.classList.toggle('workspace-nav-collapsed', localStorage.getItem('sesendok-navigation') === 'collapsed');
      const style = localStorage.getItem('sesendok-workspace-style');
      document.body.dataset.workspaceStyle = ['pasangkayu', 'pesisir', 'classic'].includes(style) ? style : 'pasangkayu';
    } catch (_) {}
  </script>

  <?php if (!isset($_SESSION['user'])) {
    header('Location: ' . app_url('/'));
    exit;
  } ?>
  <!-- NAVBAR (DI LUAR PUSHABLE) @note navbar -->
  <!-- NAVBAR (DI LUAR PUSHABLE) -->
  <?php require __DIR__ . '/../partials/auth_navbar.php'; ?>
  <div class="ui small modal workspace-menu-dialog" id="workspaceMenuSearch" role="dialog" aria-modal="true" aria-labelledby="workspaceMenuHeading">
    <div class="header" id="workspaceMenuHeading">Cari menu</div>
    <div class="content">
      <div class="ui fluid left icon input"><i class="search icon" aria-hidden="true"></i><input type="search" id="workspaceMenuQuery" placeholder="Misalnya RKA atau Data ASN" aria-label="Cari berdasarkan nama menu" autocomplete="off"></div>
      <p class="workspace-search-status" role="status"></p>
      <div class="ui list workspace-search-results" aria-label="Hasil pencarian menu"></div>
    </div>
    <div class="actions"><button type="button" class="ui basic cancel button">Tutup</button></div>
  </div>
  <div class="ui small modal workspace-menu-dialog" id="workspaceTheme" role="dialog" aria-modal="true" aria-labelledby="workspaceThemeHeading">
    <div class="header" id="workspaceThemeHeading">Tema seSendok</div>
    <div class="content">
      <div class="workspace-theme-options" role="group" aria-label="Pilihan tema">
        <button type="button" class="ui button workspace-theme-option" data-style="pasangkayu" aria-pressed="true"><span class="theme-swatch" aria-hidden="true"></span>Hijau Pasangkayu</button>
        <button type="button" class="ui button workspace-theme-option" data-style="pesisir" aria-pressed="false"><span class="theme-swatch" aria-hidden="true"></span>Biru Pesisir</button>
        <button type="button" class="ui button workspace-theme-option" data-style="classic" aria-pressed="false"><span class="theme-swatch" aria-hidden="true"></span>Klasik</button>
      </div>
      <p class="workspace-search-status" data-theme-status role="status"></p>
    </div>
    <div class="actions"><button type="button" class="ui basic cancel button">Tutup</button></div>
  </div>

  <!-- Toast global harus berada di luar pushable/dimmer agar tetap terlihat saat sidebar kanan aktif. -->
  <div id="toastContainer"></div>

  <!-- SIDEBAR WRAPPER (membuka #mainContext) -->
  <?php require __DIR__ . '/../partials/sidebar.php'; ?>

  <!-- FLYOUT (masih di dalam #mainContext) -->
  <?php require __DIR__ . '/../partials/flyout.php'; ?>

  <!-- MAIN MODAL -->
  <?php require __DIR__ . '/../partials/main_modal.php'; ?>

  <!-- PUSHER -->
  <div class="pusher">

    <!-- sticky-->
    <div class="ui sticky">
      <div class="ui icon message dashboard" id="dynamicHeader"><i class="home icon" id="dynamicHeaderIcon"></i>
        <div class="content">
          <div class="header" id="dynamicHeaderTitle">DASHBOARD</div>
          <div class="pDashboard">seSendok</div>
        </div>
        <?php if (in_array($_SESSION['user']['type_user'] ?? '', ['super_admin','admin_wilayah','tapd'], true)): ?>
        <div class="ui compact form" id="regionalScopePicker" style="margin-left:auto;min-width:320px">
          <div class="field"><label>OPD yang ditampilkan</label><div class="ui fluid search selection dropdown"><input type="hidden" name="scope_kd_opd"><i class="dropdown icon"></i><div class="default text">Seluruh OPD</div><div class="menu"></div></div></div>
        </div>
        <?php endif; ?>
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
    window.ASSET_VERSION =
      "<?= (string)max((int)@filemtime(__DIR__ . '/../../../public/assets/js/modules/referensi.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/app.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/modules/pengaturan.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/modules/anggaran-document.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/modules/profil.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/modules/kepegawaian.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/modules/kontrak.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/modules/laporan-keuangan.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/core/validation-engine.js'), (int)@filemtime(__DIR__ . '/../../../public/assets/js/core/toast.js')) ?>";
    window.USER_ROLE = "<?= $_SESSION['user']['type_user'] ?? '' ?>";
  </script>
  <script>
    window.app = window.app || {};
    window.app.user = <?= json_encode(Auth::scopedUser(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  </script>
  <!-- LIBRARY -->
  <script src="<?= asset_url('/assets/vendor/jquery/jquery.min.js') ?>"></script>
  <script src="<?= asset_url('/assets/vendor/fomantic-ui/semantic.min.js') ?>"></script>
  <script>
    // Keep Fomantic calendar popups outside horizontally scrolling tables and flyouts.
    $.fn.calendar.settings.context = 'body';
    $.extend($.fn.calendar.settings.popupOptions, {
      context: 'body', boundary: window, position: 'bottom left',
      prefer: 'opposite', lastResort: 'bottom left', hideOnScroll: true
    });
  </script>
  <script>
    window.APP_BASE_PATH = <?= json_encode(APP_BASE_PATH) ?>;
    window.appUrl = function(path) {
      if (!path || !path.startsWith('/') || path.startsWith('//')) return path;
      if (window.APP_BASE_PATH && (path === window.APP_BASE_PATH || path.startsWith(window.APP_BASE_PATH + '/')))
        return path;
      return window.APP_BASE_PATH + path;
    };
    window.appRoutePath = function(path) {
      path = path || '/';
      if (window.APP_BASE_PATH && (path === window.APP_BASE_PATH || path.startsWith(window.APP_BASE_PATH + '/'))) {
        path = path.slice(window.APP_BASE_PATH.length) || '/';
      }
      return path;
    };
    $.ajaxPrefilter(function(options) {
      options.url = window.appUrl(options.url);
    });
    const nativeFetch = window.fetch.bind(window);
    window.fetch = function(resource, options) {
      return nativeFetch(typeof resource === 'string' ? window.appUrl(resource) : resource, options);
    };
  </script>

  <!-- ================= CORE ================= -->
  <script src="/assets/js/core/config.js"></script>
  <script src="/assets/js/core/state.js"></script>
  <script src="/assets/js/core/toast.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/core/toast.js') ?>"></script>
  <script src="/assets/js/services/e2e-message.js"></script>
  <script src="/assets/js/core/ajax.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/core/ajax.js') ?>"></script> <!-- HARUS SEBELUM app.js -->
  <script src="/assets/js/config/ui-config.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/config/ui-config.js') ?>"></script>
  <script src="/assets/js/core/spa-router.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/core/spa-router.js') ?>"></script>
  <script src="/assets/js/core/page-loader.js"></script>
  <script src="/assets/js/core/validation-engine.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/core/validation-engine.js') ?>"></script>
  <script src="/assets/js/core/dialog.js"></script>
  <script src="/assets/js/core/permission-engine.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/core/permission-engine.js') ?>"></script>
  <!-- UI REGISTRY -->
  <script src="/assets/js/ui/ui-registry.js"></script>
  <script src="/assets/js/ui/rich-document-editor.js?v=20260905.6"></script>
  <script src="/assets/js/ui/modern-table-resizer.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/ui/modern-table-resizer.js') ?>"></script>
  <!-- ================= ENGINE ================= -->

  <script src="/assets/js/engine/table-manager.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/engine/table-manager.js') ?>"></script>
  <script src="/assets/js/engine/form-engine.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/engine/form-engine.js') ?>"></script>
  <script src="/assets/js/engine/form-container-manager.js"></script>
  <script src="/assets/js/engine/flyout-controller.js"></script>
  <script src="/assets/js/engine/dropdown_engine.js"></script>
  <!-- ================= MODULES ================= -->
  <script src="/assets/js/modules/base-crud-module.js"></script>

  <!-- ================= APP CORE ================= -->
  <script src="/assets/js/app.js"></script> <!-- SETELAH semua dependency -->

  <script src="<?= asset_url('/assets/vendor/leaflet/leaflet.js') ?>"></script>
  <script src="<?= asset_url('/assets/vendor/proj4/proj4.js') ?>"></script>

  <!-- UI COMPONENTS -->
  <script src="/assets/js/ui/ui-components.js"></script>
  <link rel="stylesheet" href="<?= asset_url('/assets/css/export-buttons.css') ?>?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/css/export-buttons.css') ?>">
  <script src="<?= asset_url('/assets/js/ui/export-buttons.js') ?>?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/ui/export-buttons.js') ?>"></script>
  <script src="/assets/js/ui/ui-extensions.js"></script>
  <script src="<?= asset_url('/assets/vendor/polygon-clipping/polygon-clipping.min.js') ?>"></script>
  <script src="/assets/js/maps-geometry.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/maps-geometry.js') ?>"></script>
  <script src="/assets/js/maps-editor.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/maps-editor.js') ?>"></script>
  <script src="/assets/js/maps.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/maps.js') ?>"></script>
  <!-- ENGINE: wajib tersedia sebelum modul Kontrak/Tata Naskah diinisialisasi. -->
  <script src="/assets/js/engine/document/document_builder.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/engine/document/document_builder.js') ?>"></script>


  <!-- ================= ENTRY POINT ================= -->
  <script src="/assets/js/app-init.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/app-init.js') ?>"></script>
  <script src="<?= asset_url('/assets/js/ui/workspace.js') ?>"></script>
  <script src="/assets/js/ui/dashboard-status.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/assets/js/ui/dashboard-status.js') ?>"></script>

  <script>
    window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
  </script>
</body>

</html>
