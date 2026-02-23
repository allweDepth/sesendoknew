













// Simulasi role login
AppState.role = window.USER_ROLE || "viewer";




// Kenapa Ini Versi CEO & Interaktif?
function loadProfil() {
	$.post(
		"/profil/load",
		function (res) {
			if (res.status === "success") {
				let d = res.data;

				for (let key in d) {
					$('[name="' + key + '"]').val(d[key]);
				}

				$("#card_nama").text(d.nama);
				$("#card_type").text(d.type_user);
				$("#card_tahun").text(d.tahun);
				$("#card_login").text(d.tgl_login ?? "-");

				if (d.photo) {
					$("#preview_photo").attr("src", "uploads/" + d.photo);
				}

				$(".ui.dropdown").dropdown("refresh");
			}
		},
		"json",
	);
}

/* =========================================================
	 INIT APPLICATION (document.ready)
	 ---------------------------------------------------------
	 → Semua inisialisasi dijalankan saat DOM siap
	 → Mengatur:
			 - TableManager
			 - Auto load berdasarkan URL
			 - Sidebar
			 - Menu click
			 - Dropdown rows
			 - Pagination click
			 - Search
			 - Flyout
========================================================= */

$(document).ready(function () { // @note $ready
	


	/* ---------------------------------------------
	   Sinkronisasi jumlah rows pertama kali
	---------------------------------------------- */
	if ($("#countRow").length) {
		let value = $("#countRow").dropdown("get value");
		AppState.rows = parseInt(value) || 10;
	}

	/* ---------------------------------------------
	   Auto load tabel jika ada ?tbl=
	---------------------------------------------- */

	if (tblFromUrl && currentPath !== "renstra") {
		tableManager.load(currentPath, tblFromUrl);
	}




	/* ---------------------------------------------
	   Inisialisasi dropdown & accordion
	---------------------------------------------- */
	$(".ui.dropdown").dropdown();
	$(".ui.accordion").accordion({ exclusive: false });



	/* ---------------------------------------------
	   Inisialisasi Flyout Manager
	---------------------------------------------- */
	// let flyoutManager = new FlyoutManager("#mainContext");
	let formContainerManager = new FormContainerManager();
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


	/* ---------------------------------------------
	   Load Profil jika halaman profil
	---------------------------------------------- */
	if (window.location.pathname === "/profil") {
		loadProfil();
	}

	// ==============================
	// INIT DEFAULT RENSTRA TAB
	// ==============================

	let defaultTbl = $("#renstraMenu .item.active").data("tbl");


});
