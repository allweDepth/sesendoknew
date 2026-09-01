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

	// Tema pengguna persisten dan tetap aktif setelah navigasi SPA/refresh.
	const applyTheme = (dark) => {
		$("body").toggleClass("dark-mode", dark);
		const toggle = $("#darkToggle");
		toggle.find("span").text(dark ? "Mode Terang" : "Mode Gelap");
		toggle.find("i").toggleClass("moon", !dark).toggleClass("sun", dark);
		toggle.attr("aria-pressed", dark ? "true" : "false");
	};
	let savedDark = false;
	try { savedDark = localStorage.getItem("sesendok-theme") === "dark"; } catch (_) {}
	applyTheme(savedDark);
	$(document).off("click.userTheme keydown.userTheme", "#darkToggle")
		.on("click.userTheme keydown.userTheme", "#darkToggle", function (e) {
			if (e.type === "keydown" && !["Enter", " "].includes(e.key)) return;
			e.preventDefault();
			const dark = !$("body").hasClass("dark-mode");applyTheme(dark);
			try { localStorage.setItem("sesendok-theme", dark ? "dark" : "light"); } catch (_) {}
			$("#userMenu").dropdown("hide");
		});
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
					window.location.href = window.appUrl ? window.appUrl("/logout") : "/logout";
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
	// =====================================
	// SIMPLE ACCORDION (FIX FINAL STABIL)
	// =====================================
	$(".sidebarutama > .item > .title")
		.off("click")
		.on("click", function () {
			const $title = $(this);
			const $item = $title.parent(); // item induk
			const $content = $item.children(".content");

			const $allItem = $(".sidebarutama > .item");

			// tutup semua selain ini
			$allItem.not($item).children(".content").slideUp(150);
			$allItem.not($item).children(".title").removeClass("active");

			// toggle current
			$content.stop(true, true).slideToggle(150);
			$title.toggleClass("active");
		});
});
