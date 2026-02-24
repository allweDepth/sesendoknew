$(document).ready(function () {
	// ==========================
	// GLOBAL INIT (JALAN SEKALI)
	// ==========================

	window.tableManager = new TableManager();
	const formContainerManager = new FormContainerManager();

	formContainerManager.registerPlugin("tata_naskah.*", ({ container }) => {

	if (AppState.action !== "add") return;

	// Pastikan field nomor ada dulu
	let $nomor = container.find('[name="nomor"]');
	if (!$nomor.length) return;

	// Pastikan klasifikasi sudah dipilih
	let klasifikasiId = container.find('[name="klasifikasi_id"]').val();
	if (!klasifikasiId) return;

	$.ajax({
		url: AppConfig.apiUrl + "tata_naskah/generateNomor",
		method: "POST",
		data: { klasifikasi_id: klasifikasiId },
		dataType: "json"
	})
	.done(function (res) {
		if (res.success && res.data?.nomor) {
			$nomor.val(res.data.nomor);
		}
	})
	.fail(function () {
		// Jangan bikin sistem berhenti
		console.warn("generateNomor gagal, tidak fatal.");
	});
});

	const $context = $("#mainContext");
	const $sidebarUtama = $(".sidebarutama");

	$sidebarUtama.sidebar({
		context: $context,
		transition: "push",
	});

	$("#toggleSidebar").on("click", function () {
		$sidebarUtama.sidebar("toggle");
	});

	// ==========================
	// APP LIFECYCLE (PER PAGE)
	// ==========================

	window.App = {
		init() {
			const currentPath = window.location.pathname.replace(/^\/+/g, "");
			AppState.page = currentPath;

			// UI components rebind
			$(".ui.dropdown").dropdown();
			$(".ui.accordion").accordion({ exclusive: false });

			// 🔥 Load tabel sesuai URL
			PageLoader.loadFromUrl();

			// 🔥 Module init berbasis page
			WallchatModule.init();
			PengaturanModule.init(currentPath, tableManager);
			RenstraModule.init(currentPath, tableManager);

			if (window.location.pathname === "/profil") {
				ProfilModule.load();
			}
		},
	};

	// ==========================
	// EVENT GLOBAL (sekali saja)
	// ==========================

	$(document).on("click", '[name="menu_table"]', function (e) {
		e.preventDefault();
		let jenis = $(this).attr("jenis");
		let tbl = $(this).attr("tbl");
		tableManager.load(jenis, tbl);
	});

	$("#countRow").dropdown({
		onChange: function (value) {
			AppState.rows = parseInt(value) || 10;
			AppState.halaman = 1;
			tableManager.fetch();
		},
	});

	$(document).on("click", '[name^="pagination_"] .item', function () {
		let page = parseInt($(this).data("page"));
		if (!page) return;
		AppState.halaman = page;
		tableManager.fetch();
	});

	let searchTimer;
	$(document).on("input", "#cari_data", function () {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(() => {
			AppState.cari = $(this).val().trim();
			AppState.halaman = 1;
			tableManager.fetch();
		}, 700);
	});

	$(document).on("click", "[data-action]", function (e) {
		e.preventDefault();
		let action = $(this).data("action");

		switch (action) {
			case "logout":
				DialogEngine.show({
					title: "Logout",
					message: "Anda yakin ingin keluar?",
					icon: "sign out alternate",
					approveText: "Logout",
					onApprove: () => {
						window.location.href = "/logout";
					},
				});
				break;

			case "export":
				let table = $(this).data("tbl");
				window.location.href = AppConfig.apiUrl + "export?tabel=" + table;
				break;

			case "delete":
				let tbl = $(this).data("tbl");
				let id = $(this).data("id");

				DialogEngine.show({
					title: "Hapus Data",
					message: "Data yang dihapus tidak dapat dikembalikan.",
					icon: "trash alternate outline red",
					approveText: "Hapus",
					onApprove: () => {
						return new Promise((resolve, reject) => {
							$.post(
								AppConfig.apiUrl + "dynamic",
								{
									module: AppState.module,
									action: "delete",
									tbl: tbl,
									id_row: id,
								},
								function (res) {
									if (res.success) {
										tableManager.fetch();
										resolve();
									} else {
										reject();
									}
								},
								"json",
							);
						});
					},
				});
				break;
		}
	});

	// DARK TOGGLE (register sekali saja)
	$(document)
		.off("click", "#darkToggle")
		.on("click", "#darkToggle", function () {
			const isDark = document.body.classList.toggle("dark-mode");

			localStorage.setItem("darkMode", isDark ? "true" : "false");
		});
	// ==========================
	// INIT PERTAMA
	// ==========================
	App.init();

	if (typeof SPARouter !== "undefined") {
		SPARouter.init();
	}
});
