$(document).ready(function () {
	// ==========================
	// INIT CORE
	// ==========================
	tableManager = new TableManager();
	const formContainerManager = new FormContainerManager();

	const $context = $("#mainContext");
	const $sidebarUtama = $(".sidebarutama");

	const params = new URLSearchParams(window.location.search);
	const tblFromUrl = params.get("tbl");

	const currentPath = window.location.pathname.replace(/^\/+/g, "");
	AppState.page = currentPath;

	// ==========================
	// SIDEBAR
	// ==========================
	$sidebarUtama.sidebar({
		context: $context,
		transition: "push",
	});

	$("#toggleSidebar").on("click", function () {
		$sidebarUtama.sidebar("toggle");
	});

	// ==========================
	// MENU CLICK LOAD TABLE
	// ==========================
	$(document).on("click", '[name="menu_table"]', function (e) {
		e.preventDefault();
		let jenis = $(this).attr("jenis");
		let tbl = $(this).attr("tbl");
		tableManager.load(jenis, tbl);
	});

	// ==========================
	// DROPDOWN COUNT ROW
	// ==========================
	$("#countRow").dropdown({
		onChange: function (value) {
			AppState.rows = parseInt(value) || 10;
			AppState.halaman = 1;
			tableManager.fetch();
		},
	});

	// ==========================
	// PAGINATION
	// ==========================
	$(document).on("click", '[name^="pagination_"] .item', function () {
		let page = parseInt($(this).data("page"));
		if (!page) return;
		AppState.halaman = page;
		tableManager.fetch();
	});

	// ==========================
	// SEARCH
	// ==========================
	let searchTimer;
	$("#cari_data").on("input", function () {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(() => {
			AppState.cari = $(this).val().trim();
			AppState.halaman = 1;
			tableManager.fetch();
		}, 700);
	});

	// ==========================
	// GLOBAL ACTION DISPATCHER
	// ==========================
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

	// ==========================
	// MODULE INIT
	// ==========================
	WallchatModule.init();
	PengaturanModule.init(currentPath, tableManager);
	if (window.location.pathname === "/profil") {
		ProfilModule.load();
	}
  //Module Renstra
  RenstraModule.init(currentPath, tableManager);
});
