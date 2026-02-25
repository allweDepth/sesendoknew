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
// ===============================
// AUTO INIT MODULE BERDASARKAN URL
// ===============================
const path = window.location.pathname;
$(document).on("click", "#btnLogout", function (e) {

	e.preventDefault();

	DialogEngine.show({
		title: "Konfirmasi Logout",
		message: "Yakin ingin keluar dari sistem?",
		icon: "sign out alternate red",
		approveText: "Ya, Logout",
		cancelText: "Batal",

		onApprove: () => {

			// Return Promise agar loading state bekerja
			return new Promise((resolve) => {

				// Jika logout via server route
				window.location.href = "/logout";

				resolve();
			});
		}
	});
});
});