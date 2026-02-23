$(document).ready(function () {
	// ==========================
	// INIT CORE
	// ==========================
	window.tableManager = new TableManager();
	const formContainerManager = new FormContainerManager();

	formContainerManager.registerPlugin("tata_naskah.*", ({ container }) => {
		if (AppState.action !== "add") return;

		$.post(
			AppConfig.apiUrl + "tata_naskah/generateNomor",
			function (res) {
				if (res.success && res.data?.nomor) {
					container.find('[name="nomor"]').val(res.data.nomor);
				}
			},
			"json",
		);
	});

	const $context = $("#mainContext");
	const $sidebarUtama = $(".sidebarutama");

	const params = new URLSearchParams(window.location.search);
	const currentPath = window.location.pathname.replace(/^\/+/g, "");
	AppState.page = currentPath;

	// ==========================
	// SIDEBAR
	// ==========================
	$sidebarUtama.sidebar({
		context: $context,
		transition: "push",
	});

	// ==========================
	// INIT GLOBAL UI COMPONENTS
	// ==========================
	$(".ui.dropdown").dropdown();
	$(".ui.accordion").accordion({ exclusive: false });

	$("#toggleSidebar").on("click", function () {
		$sidebarUtama.sidebar("toggle");
	});

	// ==========================
	// GLOBAL MODULE AUTO INIT
	// ==========================
	const moduleConfig = UIConfig[currentPath];

	if (moduleConfig) {
		let tbl = params.get("tbl") || Object.keys(moduleConfig)[0];

		if (tbl) {
			tableManager.load(currentPath, tbl);
		}
	}

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
	// MODULE INIT (UI ONLY)
	// ==========================
	WallchatModule.init();
	PengaturanModule.init(currentPath, tableManager);
	RenstraModule.init(currentPath, tableManager);

	if (window.location.pathname === "/profil") {
		ProfilModule.load();
	}
	// DARK MODULE
	document.getElementById("darkToggle").addEventListener("click", function () {
		document.body.classList.toggle("dark-mode");

		localStorage.setItem(
			"darkMode",
			document.body.classList.contains("dark-mode"),
		);
	});

	window.addEventListener("load", function () {
		if (localStorage.getItem("darkMode") === "true") {
			document.body.classList.add("dark-mode");
		}
	});
});
