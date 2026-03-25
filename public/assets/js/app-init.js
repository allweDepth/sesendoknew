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
		// =====================================
		// FIX 1: tutup sidebar kanan jika terbuka
		// =====================================
		if ($sidebarKanan.hasClass("visible")) {
			// cek state fomantic
			$sidebarKanan.sidebar("hide"); // tutup flyout kanan
		}

		// =====================================
		// FIX 2: toggle sidebar kiri
		// =====================================
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

	$(document).on("click", ".message-close", function () {
		$(this).closest(".message").transition("fade");
	});
	// ===============================
	// FLYOUT CONTROLLER
	// ===============================
	window.Flyout = new FlyoutController();
	// =====================================
	// SIMPLE ACCORDION TANPA FOMANTIC
	// =====================================
	$(".sidebarutama .title")
		.off("click")
		.on("click", function () {
			const $content = $(this).next(".content");

			// tutup semua kecuali ini
			$(".sidebarutama .content").not($content).slideUp(150);
			$(".sidebarutama .title").not(this).removeClass("active");

			// toggle current
			$content.stop(true, true).slideToggle(150);
			$(this).toggleClass("active");
		});
});
