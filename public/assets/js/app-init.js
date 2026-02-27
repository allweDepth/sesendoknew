$(document).ready(function () {
	// ===============================
	// APP INIT
	// ===============================
	window.app = new App();
	window.app.init();

	const $context = $("#mainContext");

	// ===============================
	// SIDEBAR KIRI (STABIL)
	// ===============================
	const $sidebarUtama = $(".sidebarutama");

	$sidebarUtama.sidebar({
		context: $context,
		transition: "push",
	});

	$("#toggleSidebar").on("click", function () {
		$sidebarUtama.sidebar("toggle");
	});

	// ===============================
	// 🔥 SIDEBAR KANAN (INIT SEKALI SAJA)
	// ===============================
	const $sidebarKanan = $(".sidebarkanan");

	$sidebarKanan.sidebar({
		context: $context,
		transition: "push",
		dimPage: true,
		closable: true,
	});

	// ===============================
	// COMPONENT INIT
	// ===============================
	$(".ui.accordion").accordion();
	$(".ui.dropdown").dropdown();
	$(".ui.sticky").sticky({
		context: $context,
	});

	// ===============================
	// LOGOUT
	// ===============================
	$(document).on("click", "#btnLogout", function (e) {
		e.preventDefault();

		DialogEngine.show({
			title: "Konfirmasi Logout",
			message: "Yakin ingin keluar dari sistem?",
			icon: "sign out alternate red",
			approveText: "Ya, Logout",
			cancelText: "Batal",
			onApprove: () => {
				return new Promise((resolve) => {
					window.location.href = "/logout";
					resolve();
				});
			},
		});
	});


	// ===============================
	// FLYOUT CONTROLLER
	// ===============================
	window.Flyout = new FlyoutController();
});
