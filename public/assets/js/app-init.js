$(document).ready(function () {

    window.app = new App();
    window.app.init();

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