document.addEventListener("DOMContentLoaded", function () {

    const app = new App();
    app.init();

    // =============================
    // SIDEBAR INIT SESUAI SISTEM LAMA
    // =============================

    const $sidebarUtama = $(".sidebarutama");

    $sidebarUtama.sidebar({
        context: $("#mainContext")
    });

    $("#toggleSidebar").on("click", function () {
        $sidebarUtama.sidebar("toggle");
    });

});


/**
 * ============================================
 * INIT FOMANTIC UI
 * ============================================
 * Semua komponen UI harus diinit ulang
 */
function initFomantic() {

    // Sidebar
    $('.ui.sidebar').sidebar({
        context: $('#mainContext')
    });

    // Accordion
    $('.ui.accordion').accordion();

    // Dropdown
    $('.ui.dropdown').dropdown();

    // Sticky
    $('.ui.sticky').sticky({
        context: '#mainContext'
    });

}