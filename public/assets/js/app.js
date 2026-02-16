/* =========================================================
	 GLOBAL CONFIG
	 ---------------------------------------------------------
	 → Konfigurasi dasar aplikasi
	 → Jika ganti base URL atau endpoint API, ubah di sini
========================================================= */

const AppConfig = {
	baseUrl: window.BASEURL || "/", // Base URL aplikasi
	apiUrl: window.BASEURL || "/", // Endpoint API utama
	defaultMethod: "POST", // Default method AJAX
};

/* =========================================================
	 GLOBAL STATE (UNIVERSAL)
	 ---------------------------------------------------------
	 → Menyimpan state aktif tabel & pagination
	 → Dipakai oleh TableManager dan modul lain
========================================================= */

const AppState = {
	halaman: 1, // Halaman aktif saat ini
	rows: 10, // Jumlah data per halaman
	jenis: "", // Module aktif (referensi, renstra, dll)
	mode: "", // add / edit / detail  <-- TAMBAH INI
	tbl: "", // Tabel aktif
	cari: "", // Keyword pencarian
	currentMenu: "", // Tracking menu sebelumnya
};

/* =========================================================
	 CORE AJAX ENGINE
	 ---------------------------------------------------------
	 → Wrapper universal untuk semua AJAX request
	 → Semua komunikasi backend lewat sini
========================================================= */

class AjaxEngine {
	constructor(url = "") {
		this.url = url;
	}

	request({
		url = this.url,
		method = AppConfig.defaultMethod,
		data = {},
		beforeSend = null,
		success = null,
		error = null,
		complete = null,
	}) {
		return $.ajax({
			type: method,
			url: url,
			data: data,
			dataType: "json",
			beforeSend: function () {
				if (beforeSend) beforeSend();
			},
			success: function (res) {
				if (success) success(res);
			},
			error: function (err) {
				console.error("AJAX ERROR:", err);
				if (error) error(err);
			},
			complete: function () {
				if (complete) complete();
			},
		});
	}
}

/* =========================================================
	 UNIVERSAL TABLE MODULE
	 ---------------------------------------------------------
	 → Konfigurasi tombol aksi per module/tabel
	 → Tambah atau ubah aturan tombol di sini
========================================================= */

const ActionConfig = {
	// DEFAULT GLOBAL (jika tidak ada aturan khusus)
	default: {
		buttons: ["edit", "delete"],
	},

	// MODULE REFERENSI
	referensi: {
		sub_kegiatan: {
			buttons: ["edit", "delete"],
		},
		satuan: {
			buttons: ["edit"],
		},
	},

	// MODULE RENSTRA
	renstra: {
		tujuan_sasaran_renstra: {
			buttons: ["edit"], // hanya tombol edit
		},
	},
};

/* =========================================================
	 TABLE MANAGER
	 ---------------------------------------------------------
	 → Engine utama load & render tabel
	 → Mengatur:
			 - Load data
			 - Render table
			 - Render pagination
========================================================= */

class TableManager {
	constructor() {
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
	}

	/* -----------------------------------------------------
		 Load tabel berdasarkan module & nama tabel
		 Dipanggil saat klik menu
	------------------------------------------------------ */
	load(jenis, tbl) {
		// Reset halaman jika pindah menu
		if (AppState.currentMenu !== tbl) {
			AppState.halaman = 1;
		}

		AppState.jenis = jenis;
		AppState.tbl = tbl;
		AppState.currentMenu = tbl;

		this.fetch();
	}

	/* -----------------------------------------------------
		 Fetch data dari backend
		 Mengirim:
				 jenis, tbl, halaman, rows, cari
	------------------------------------------------------ */
	fetch() {
		// Sinkronisasi jumlah rows dari dropdown (Fomantic UI)
		if ($("#countRow").length) {
			let value = $("#countRow").dropdown("get value");
			AppState.rows = parseInt(value) || AppState.rows;
		}

		this.ajax.request({
			data: {
				jenis: AppState.jenis,
				tbl: AppState.tbl,
				halaman: AppState.halaman,
				rows: AppState.rows,
				cari: AppState.cari,
			},
			success: (res) => {
				if (!res || res.success !== true) {
					console.warn(res?.message || "Response tidak valid");
					this.renderTable([]);
					return;
				}

				let rows = Array.isArray(res.data) ? res.data : [];
				let meta = res.meta || {};

				this.renderTable(rows);

				this.renderPagination({
					total: meta.total || 0,
					limit: meta.limit || AppState.rows,
					page: meta.page || AppState.halaman,
				});
			},
		});
	}

	/* -----------------------------------------------------
		 Render isi tbody tabel
	------------------------------------------------------ */
	renderTable(rows) {
		let html = "";

		rows.forEach((row) => {
			html += "<tr>";

			Object.keys(row).forEach((key) => {
				if (key === "id") return; // ← skip kolom id
				html += `<td>${row[key] ?? ""}</td>`;
			});

			html += `<td class="collapsing">
			${this.buildActionButtons(row)}
		</td>`;

			html += "</tr>";
		});

		let target = `tbody[name="tabel_${AppState.jenis}"]`;

		$(target).html(html);
	}

	/* -----------------------------------------------------
		 Render pagination
		 ⚠️ LOGIC ASLI TIDAK DIUBAH
	------------------------------------------------------ */
	renderPagination(meta) {
		let target = `div[name="pagination_${AppState.jenis}"]`;

		if (!meta || !meta.total) {
			$(target).html("");
			return;
		}

		let currentPage = meta.page || 1;
		let limit = meta.limit || 10;
		let totalPage = Math.ceil(meta.total / limit);

		if (totalPage <= 1) {
			$(target).html("");
			return;
		}

		let html = `<div class="ui pagination menu">`;

		if (currentPage > 1) {
			html += `<a class="item" data-page="1">
					<i class="angle double left chevron icon"></i>
				 </a>`;

			html += `<a class="item" data-page="${currentPage - 1}">
					<i class="angle left icon"></i>
				 </a>`;
		}

		let start = Math.max(1, currentPage - 1);
		let end = Math.min(totalPage, currentPage + 1);

		for (let i = start; i <= end; i++) {
			let active = i === currentPage ? "active" : "";
			html += `<a class="item ${active}" data-page="${i}">${i}</a>`;
		}

		if (currentPage < totalPage) {
			html += `<a class="item" data-page="${currentPage + 1}">
					<i class="angle right icon"></i>
				 </a>`;

			html += `<a class="item" data-page="${totalPage}">
					<i class="angle double right chevron icon"></i>
				 </a>`;
		}

		html += `</div>`;

		$(target).html(html);
	}

	/* -----------------------------------------------------
		 Build tombol aksi (edit/delete/detail)
	------------------------------------------------------ */
	buildActionButtons(row) {
		let jenis = AppState.jenis;
		let tbl = AppState.tbl;

		let config =
			ActionConfig[jenis]?.[tbl] || ActionConfig[jenis] || ActionConfig.default;

		let buttons = config.buttons || [];

		if (!buttons.length) return "";

		let html = `<div class="ui icon basic mini buttons">`;

		buttons.forEach((btn) => {
			if (btn === "edit") {
				html += `
				<button class="ui button"
					name="flyout"
					jns="edit"
					tbl="${tbl}"
					id_row="${row.id}">
					<i class="edit outline blue icon"></i>
				</button>
			`;
			}

			if (btn === "delete") {
				html += `
				<button class="ui red button"
					name="del_row"
					jns="delete"
					tbl="${tbl}"
					id_row="${row.id}">
					<i class="trash alternate outline red icon"></i>
				</button>
			`;
			}

			if (btn === "detail") {
				html += `
				<button class="ui green button"
					name="detail_row"
					tbl="${tbl}"
					id_row="${row.id}">
					<i class="eye icon"></i>
				</button>
			`;
			}
		});

		html += `</div>`;

		return html;
	}
}
/* =========================================================
	 FORM ENGINE PRO - FIELD VARIATIONS (FOMANTIC STYLE)
========================================================= */

class FormEngine {
	/* ============================
		 Render Form ke Container
	============================ */
	static render(container, elements = []) {
		let html = `<form class="ui form">`;
		html += this.build(elements);
		html += `</form>`;

		$(container).html(html);
		this.init();
	}

	/* ============================
		 Build All Elements
	============================ */
	static build(elements = []) {
		let html = "";
		elements.forEach((el) => {
			html += this.element(el);
		});
		return html;
	}

	/* ============================
		 Build Single Element
	============================ */
	static element(el) {
		const { tag, prop = {} } = el;

		switch (tag) {
			/* ===== SINGLE FIELD ===== */
			case "field":
				return this.fieldWrapper(this.input(prop), prop);

			case "fieldTextarea":
				return this.fieldWrapper(
					`<textarea name="${prop.name}" ${prop.atribut || ""}></textarea>`,
					prop,
				);

			case "fieldDropdown":
				return this.fieldWrapper(this.dropdown(prop), prop);

			case "fieldCheckbox":
				return `
					<div class="field ${prop.classField || ""}">
						<div class="ui checkbox">
							<input type="checkbox" name="${prop.name}" ${prop.atribut || ""}>
							<label>${prop.label || ""}</label>
						</div>
					</div>
				`;

			/* ===== GROUP FIELDS ===== */
			case "fields":
				return `
					<div class="fields ${prop.classGroup || ""}">
						${this.build(prop.children || [])}
					</div>
				`;

			/* ===== INLINE FIELDS ===== */
			case "inlineFields":
				return `
					<div class="inline fields">
						${this.build(prop.children || [])}
					</div>
				`;

			/* ===== DIVIDER ===== */
			case "divider":
				return `
					<h4 class="ui dividing header">
						${prop.label || ""}
					</h4>
				`;

			default:
				return "";
		}
	}

	/* ============================
		 Field Wrapper
	============================ */
	static fieldWrapper(inner, prop) {
		return `
			<div class="field 
				${prop.classField || ""} 
				${prop.width || ""}">
				
				${prop.label ? `<label>${prop.label}</label>` : ""}
				${inner}
			</div>
		`;
	}

	/* ============================
		 Basic Input
	============================ */
	static input(prop) {
		// icon input
		if (prop.icon) {
			return `
				<div class="ui icon input ${prop.classInput || ""}">
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
					<i class="${prop.icon} icon"></i>
				</div>
			`;
		}

		// labeled input
		if (prop.labelInput) {
			return `
				<div class="ui labeled input ${prop.classInput || ""}">
					<div class="ui label">${prop.labelInput}</div>
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
				</div>
			`;
		}

		// action input
		if (prop.action) {
			return `
				<div class="ui action input ${prop.classInput || ""}">
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
					<button class="ui button">${prop.action}</button>
				</div>
			`;
		}

		// default input
		return `
			<input type="${prop.type || "text"}"
				   name="${prop.name}"
				   ${prop.atribut || ""}>
		`;
	}

	/* ============================
		 Dropdown
	============================ */
	static dropdown(prop) {
		let options = "";

		(prop.options || []).forEach((opt) => {
			options += `
				<div class="item" data-value="${opt.value}">
					${opt.text}
				</div>
			`;
		});

		return `
			<div class="ui selection dropdown ${prop.classInput || ""}">
				<input type="hidden" name="${prop.name}">
				<i class="dropdown icon"></i>
				<div class="default text">Pilih</div>
				<div class="menu">
					${options}
				</div>
			</div>
		`;
	}

	/* ============================
		 Init Fomantic Components
	============================ */
	static init() {
		$(".ui.dropdown").dropdown();
		$(".ui.checkbox").checkbox();
	}
}
/* =========================================================
	 FLYOUT MANAGER
	 ---------------------------------------------------------
	 → Mengatur panel flyout (add/edit/detail)
	 → Mengontrol buka/tutup
	 → Mengisi form menggunakan FormEngine
========================================================= */

class FlyoutManager {
	constructor(contextSelector = "#mainContext") {
		this.$context = $(contextSelector);
		this.$flyout = this.$context.children(".ui.flyout");

		this.$icon = $("#icon_flyout");
		this.$header = $("#content_flyout");
		this.$form = $("#form_flyout");

		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		this.init();
	}

	/* ---------------------------------------------
		 Inisialisasi Flyout Fomantic
	---------------------------------------------- */
	init() {
		this.$flyout.flyout({
			context: this.$context,
			transition: "push",
			closable: false,
		});

		this.bindEvents();
	}

	/* ---------------------------------------------
		 Event Binding
	---------------------------------------------- */
	bindEvents() {
		// Klik tombol edit/add (name="flyout")
		$(document).on("click", '[name="flyout"]', (e) => {
			e.preventDefault();
			this.open($(e.currentTarget));
		});

		// Tombol close
		$(document).on("click", ".btnFlyoutClose, .close.icon", () => {
			this.hide();
		});
	}

	/* ---------------------------------------------
		 Open Flyout
	---------------------------------------------- */
	open($btn) {
		const jenis = $btn.attr("jns");
		const tbl = $btn.attr("tbl");
		const idRow = $btn.attr("id_row");

		AppState.mode = jenis;

		// ⬇️ JANGAN timpa tbl kalau tidak ada
		if (tbl) {
			AppState.tbl = tbl;
		}

		let config = this.buildConfig(jenis, AppState.tbl);

		this.render(config);

		if (jenis === "edit" && idRow) {
			this.loadData(idRow);
		} else {
			this.show();
		}
	}

	/* ---------------------------------------------
		 Konfigurasi Form per jenis
		 Bisa kamu modifikasi sesuai kebutuhan
	---------------------------------------------- */
	buildConfig(jenis, tbl) {
		let config = {
			icon: "folder icon",
			header: "",
			elements: [],
		};

		if (jenis === "add") {
			config.icon = "plus icon";
			config.header = "Tambah Data";

			config.elements = [
				{
					tag: "field",
					prop: {
						label: "Nama",
						name: "nama",
						classField: "required",
						atribut: `placeholder="Nama"`,
					},
				},
			];
		}

		if (jenis === "edit") {
			config.icon = "edit icon";
			config.header = "Edit Data";

			config.elements = [
				{
					tag: "field",
					prop: {
						label: "Nama",
						name: "nama",
						classField: "required",
						atribut: `placeholder="Nama"`,
					},
				},
			];
		}

		return config;
	}

	/* ---------------------------------------------
		 Render ke Flyout
	---------------------------------------------- */
	render(config) {
		this.$icon.attr("class", config.icon);
		this.$header.text(config.header);

		// Gunakan FormEngine untuk isi form
		if (config.elements) {
			FormEngine.render("#form_flyout", config.elements);
		}
	}

	/* ---------------------------------------------
		 Load Data (Mode Edit)
	---------------------------------------------- */
	loadData(idRow) {
		this.ajax.request({
			data: {
				jenis: "edit",
				tbl: AppState.tbl,
				id_row: idRow,
			},
			success: (res) => {
				if (res.success && res.data) {
					// Prefill form berdasarkan name=""
					Object.keys(res.data).forEach((key) => {
						this.$form.find(`[name="${key}"]`).val(res.data[key]);
					});

					this.show();
				}
			},
		});
	}

	/* ---------------------------------------------
		 Show / Hide
	---------------------------------------------- */
	show() {
		this.$flyout.flyout("show");
	}

	hide() {
		this.$flyout.flyout("hide");
	}
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

$(document).ready(function () {
	/* ---------------------------------------------
		 Inisialisasi Table Manager
		 → Engine utama tabel
	---------------------------------------------- */
	tableManager = new TableManager();

	/* ---------------------------------------------
		 Ambil elemen context utama
	---------------------------------------------- */
	const $context = $("#mainContext");

	/* ---------------------------------------------
		 Sidebar reference
	---------------------------------------------- */
	const $sidebar = $context.children(".ui.sidebar");

	/* ---------------------------------------------
		 Ambil parameter URL (?tbl=...)
		 Contoh: /referensi?tbl=program
	---------------------------------------------- */
	const params = new URLSearchParams(window.location.search);
	const tblFromUrl = params.get("tbl");

	/* ---------------------------------------------
		 Ambil path aktif
		 Contoh: /referensi
	---------------------------------------------- */
	const currentPath = window.location.pathname.replace(/^\/+/g, "");

	/* ---------------------------------------------
		 Sinkronisasi jumlah rows pertama kali
		 (Dropdown Fomantic UI)
	---------------------------------------------- */
	if ($("#countRow").length) {
		let value = $("#countRow").dropdown("get value");
		AppState.rows = parseInt(value) || 10;
	}

	/* ---------------------------------------------
		 Auto load tabel jika ada ?tbl=
		 (Dipanggil 2x sesuai kode asli)
	---------------------------------------------- */
	if (tblFromUrl && currentPath) {
		tableManager.load(currentPath, tblFromUrl);
	}
	if (tblFromUrl && currentPath) {
		tableManager.load(currentPath, tblFromUrl);
	}

	/* ---------------------------------------------
	Inisialisasi Sidebar Fomantic
	---------------------------------------------- */
	$sidebar.sidebar({
		context: $context,
		transition: "push",
	});

	/* ---------------------------------------------
		 Toggle Sidebar Button
	---------------------------------------------- */
	$("#toggleSidebar").on("click", function () {
		$sidebar.sidebar("toggle");
	});

	/* =============================================
		 MENU CLICK LOAD TABLE
		 → Saat klik menu sidebar
		 → Akan load tabel berdasarkan jenis & tbl
	============================================= */
	$(document).on("click", '[name="menu_table"]', function (e) {
		e.preventDefault();

		let jenis = $(this).attr("jenis");
		let tbl = $(this).attr("tbl");

		tableManager.load(jenis, tbl);
	});

	/* =============================================
		 DROPDOWN COUNT ROW
		 → Mengubah jumlah data per halaman
	============================================= */
	$("#countRow").dropdown({
		onChange: function (value) {
			AppState.rows = parseInt(value) || 10;
			AppState.halaman = 1;
			tableManager.fetch();
		},
	});

	/* ---------------------------------------------
		 Inisialisasi semua dropdown & accordion
	---------------------------------------------- */
	$(".ui.dropdown").dropdown();
	$(".ui.accordion").accordion({ exclusive: false });

	/* =============================================
		 PAGINATION CLICK
		 → Klik nomor halaman
	============================================= */
	$(document).on("click", '[name^="pagination_"] .item', function () {
		let page = parseInt($(this).data("page"));
		if (!page) return;

		AppState.halaman = page;
		tableManager.fetch();
	});

	/* =============================================
		 SEARCH INPUT (Debounce 700ms)
		 → Pencarian otomatis setelah berhenti mengetik
	============================================= */
	let searchTimer;

	$("#cari_data").on("input", function () {
		clearTimeout(searchTimer);

		searchTimer = setTimeout(() => {
			AppState.cari = $(this).val().trim();
			AppState.halaman = 1;
			tableManager.fetch();
		}, 700);
	});

	/* ---------------------------------------------
		 (Opsional - tidak aktif)
		 Fomantic Search API
	---------------------------------------------- */
	// $(".ui.search").search({
	// 	source: [],
	// 	searchOnFocus: false,
	// 	onSearchQuery: function (query) {
	// 		AppState.cari = query;
	// 		AppState.halaman = 1;
	// 		tableManager.fetch();
	// 	},
	// });

	/* ---------------------------------------------
		 Inisialisasi Flyout Manager
		 → Panel samping edit/add data
	---------------------------------------------- */
	let flyoutManager = new FlyoutManager("#mainContext");

/* =========================================
   WALLCHAT MODULE
========================================= */

// POST STATUS
$(document).on("submit", "#formPost", function (e) {
    e.preventDefault();

    $.post("/wallchat/store", $(this).serialize(), function (res) {
        if (res.status) {
            location.reload();
        }
    }, "json");
});

// KOMENTAR
$(document).on("submit", ".formComment", function (e) {
    e.preventDefault();

    let parent_id = $(this).data("id");
    let content = $(this).find('input[name="content"]').val();

    $.post("/wallchat/comment", {
        parent_id: parent_id,
        content: content
    }, function (res) {
        if (res.status) {
            location.reload();
        }
    }, "json");
});

// MODAL PRIVATE MESSAGE
$(document).on("click", "#btnPrivateMessage", function () {
    $("#modalPrivateMessage").modal("show");
});

// PRIVATE MESSAGE SEND
$(document).on("submit", "#formPrivateMessage", function (e) {
    e.preventDefault();

    $.post("/wallchat/private", $(this).serialize(), function (res) {

        if (res.status) {
            $("#modalPrivateMessage").modal("hide");

            Swal.fire({
                icon: "success",
                title: "Berhasil",
                text: res.message
            });
        }

    }, "json");
});


});
