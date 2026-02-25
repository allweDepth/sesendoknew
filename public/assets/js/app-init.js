// INIT APP LANGSUNG TANPA DOMContentLoaded

const app = new App();
app.init();

// INIT FOMANTIC UI
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