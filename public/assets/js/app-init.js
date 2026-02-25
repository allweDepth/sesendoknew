// INIT APP
$(document).ready(function () {

    const app = new App();
    app.init();

    // ==============================
    // FUNCTION LOAD PAGE
    // ==============================
    function loadPage(page, tbl = null) {

        if (!page) return;

        // Jika App Anda punya method loadModule gunakan itu
        if (typeof app.loadModule === "function") {
            app.loadModule(page, tbl);
            return;
        }

        // fallback jika pakai tableManager global
        if (typeof tableManager !== "undefined") {
            tableManager.load(page, tbl);
        }
    }

    // ==============================
    // BACA PARAMETER URL SAAT REFRESH
    // ==============================
    const params = new URLSearchParams(window.location.search);
    const page = params.get("page");
    const tbl = params.get("tbl");

    if (page) {
        loadPage(page, tbl);
    }

    // ==============================
    // HANDLE KLIK LINK SPA
    // ==============================
    $(document).on("click", 'a[href*="?page="]', function (e) {

        e.preventDefault();

        const url = new URL($(this).attr("href"), window.location.origin);
        const page = url.searchParams.get("page");
        const tbl = url.searchParams.get("tbl");

        // update URL TANPA reload
        history.pushState(null, null, url.pathname + url.search);

        loadPage(page, tbl);
    });

    // ==============================
    // HANDLE BACK / FORWARD BROWSER
    // ==============================
    window.addEventListener("popstate", function () {

        const params = new URLSearchParams(window.location.search);
        const page = params.get("page");
        const tbl = params.get("tbl");

        loadPage(page, tbl);
    });

    // ==============================
    // INIT FOMANTIC UI
    // ==============================
    const $sidebarUtama = $(".sidebarutama");
    const $context = $("#mainContext");

    $sidebarUtama.sidebar({
        context: $context,
        transition: "push"
    });

    $("#toggleSidebar").on("click", function () {
        $sidebarUtama.sidebar("toggle");
    });

    $('.ui.accordion').accordion();
    $('.ui.dropdown').dropdown();
    $('.ui.sticky').sticky({
        context: $context
    });

});