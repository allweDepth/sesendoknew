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
		processData = true,
		contentType = "application/x-www-form-urlencoded; charset=UTF-8",
	}) {
		return $.ajax({
			type: method,
			url: url,
			data: data,
			dataType: "json",
			processData: processData,
			contentType: contentType,
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
				data-ui="open-form"
				data-container="flyout"
				data-jns="edit"
				data-tbl="${tbl}"
				data-id="${row.id}">
				<i class="edit outline blue icon"></i>
			</button>
			`;
			}

			if (btn === "delete") {
				html += `
			<button class="ui red button"
				data-ui="delete-row"
				data-jns="delete"
				data-tbl="${tbl}"
				data-id="${row.id}">
				<i class="trash alternate outline red icon"></i>
			</button>
			`;
			}

			if (btn === "detail") {
				html += `
			<button class="ui green button"
				data-ui="detail-row"
				data-container="flyout"
				data-jns="detail"
				data-tbl="${tbl}"
				data-id="${row.id}">
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
			if (el.prop?.role) {
				if (!el.prop.role.includes(AppState.role)) return;
			}
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
   ROLE ENGINE
========================================================= */

const RoleConfig = {
	admin: {
		canAdd: true,
		canEdit: true,
		canDelete: true,
		canUpload: true,
	},
	editor: {
		canAdd: true,
		canEdit: true,
		canDelete: false,
		canUpload: true,
	},
	viewer: {
		canAdd: false,
		canEdit: false,
		canDelete: false,
		canUpload: false,
	},
};

// Simulasi role login
AppState.role = "admin";
/* =========================================================
   UI BUILDER CONFIG
	 BUAT FIELD UNTUK FORM sesuai tbl
========================================================= */

const UIConfig = {
	//@note
	referensi: {
		satuan: [
			{
				tag: "field",
				prop: {
					label: "Nama Satuan",
					name: "nama_satuan",
					classField: "required",
					atribut: `placeholder="Masukkan satuan"`,
					role: ["admin", "editor"],
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Status",
					name: "status",
					options: [
						{ value: "1", text: "Aktif" },
						{ value: "0", text: "Non Aktif" },
					],
					role: ["admin"],
				},
			},
			{
				tag: "field",
				prop: {
					label: "Upload File",
					name: "file_dokumen",
					type: "file",
					role: ["admin"],
				},
			},
		],
	},
};
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

	/* ---------------------------------------------/
		 Inisialisasi Flyout Fomantic
	---------------------------------------------- */
	init() {
		this.$flyout.flyout({
			context: this.$context,
			transition: "overlay",
			dimPage: false,
			closable: false,
		});

		this.bindEvents();
	}

	/* ---------------------------------------------
		 Event Binding
	---------------------------------------------- */
	bindEvents() {
		// Open Form (Add / Edit / Detail)
		$(document).on("click", '[data-ui="open-form"]', (e) => {
			e.preventDefault();
			this.open($(e.currentTarget));
		});

		// Close Flyout
		$(document).on("click", ".btnFlyoutClose, .close.icon", () => {
			this.hide();
		});
	}

	/* ---------------------------------------------
		 Open Flyout
	---------------------------------------------- */
	open($btn) {
		const jenis = $btn.data("jns");
		const tbl = $btn.data("tbl");
		const idRow = $btn.data("id");

		AppState.mode = jenis;

		if (tbl) {
			AppState.tbl = tbl;
		}

		let config = this.buildConfig(jenis, AppState.tbl);

		this.render(config);

		if ((jenis === "edit" || jenis === "detail") && idRow) {
			this.loadData(idRow);
		} else {
			this.show();
		}
	}
	//========
	//========
	save() {
		let form = $("#form_flyout form")[0];
		let formData = new FormData(form);

		formData.append("jenis", AppState.mode);
		formData.append("tbl", AppState.tbl);

		this.ajax.request({
			url: AppConfig.apiUrl + "dynamic/save",
			method: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: (res) => {
				if (res.success) {
					this.hide();
					new TableManager().fetch();
				}
			},
		});
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

		let role = RoleConfig[AppState.role];

		if (jenis === "add" && !role.canAdd) return config;
		if (jenis === "edit" && !role.canEdit) return config;

		config.header =
			jenis === "add"
				? "Tambah Data"
				: jenis === "edit"
					? "Edit Data"
					: "Detail Data";

		config.icon =
			jenis === "add"
				? "plus icon"
				: jenis === "edit"
					? "edit icon"
					: "eye icon";

		if (UIConfig[AppState.jenis]?.[tbl]) {
			config.elements = UIConfig[AppState.jenis][tbl];
		}

		// Tambahkan tombol submit
		config.elements.push({
			tag: "divider",
			prop: { label: "" },
		});

		config.elements.push({
			tag: "field",
			prop: {
				name: "_submit",
				atribut: `
				type="button"
				class="ui primary button btnSubmit"
				value="Simpan"
			`,
			},
		});

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
   FORM CONTAINER MANAGER
   Bisa tampil di:
   - Flyout
   - Modal
========================================================= */

class FormContainerManager {
	constructor() {
		this.$flyout = $("#mainContext").children(".ui.flyout");
		this.$modal = $("#mainModal");

		this.activeContainer = "flyout";

		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		this.initContainers(); // <-- TAMBAHKAN INI

		
	}
initContainers() {

    // INIT FLYOUT HANYA SEKALI
    this.$flyout.flyout({
        context: this.$context,
        transition: "overlay",
        dimPage: false,
        closable: false,
    });

    this.bindEvents();

    // Modal biasanya sudah aman, tapi boleh guard juga
    if (this.$modal.length && !this.$modal.data('module-modal')) {
        this.$modal.modal({
            closable: false
        });
    }
}
	/* --------------------------------------------- */
	bindEvents() {
		$(document).on("click", '[data-ui="open-form"]', (e) => {
			e.preventDefault();
			this.open($(e.currentTarget));
		});

		$(document).on("click", ".btnSubmit", () => {
			this.save();
		});
	}
	getActiveForm() {
		if (this.activeContainer === "modal") {
			return $("#form_modal");
		}
		return $("#form_flyout");
	}
	/* --------------------------------------------- */
	open($btn) {
		const jenis = $btn.data("jns");
		const tbl = $btn.data("tbl");
		const container = $btn.data("container") || "flyout";
		const idRow = $btn.data("id");

		this.activeContainer = container; // <-- TAMBAHAN

		AppState.mode = jenis;
		AppState.tbl = tbl;

		let config = this.buildConfig(jenis, tbl);

		this.render(config, container);

		if ((jenis === "edit" || jenis === "detail") && idRow) {
			this.loadData(idRow, container);
		}

		this.show(container);
	}

	/* --------------------------------------------- */
	render(config, container) {
		$("#form_modal").empty();
		$("#form_flyout").empty();
		if (container === "modal") {
			$("#icon_modal").attr("class", config.icon);
			$("#content_modal").text(config.header);
			FormEngine.render("#form_modal", config.elements);
		} else {
			$("#icon_flyout").attr("class", config.icon);
			$("#content_flyout").text(config.header);
			FormEngine.render("#form_flyout", config.elements);
		}
	}

	/* --------------------------------------------- */
	show(container) {
		if (container === "modal") {
			this.$modal.modal("show");
		} else {
			this.$flyout.flyout("show");
		}
	}

	/* --------------------------------------------- */
	hide(container) {
		if (container === "modal") {
			this.$modal.modal("hide");
		} else {
			this.$flyout.flyout("hide");
		}
	}
	/* --------------------------------------------- */
	loadData(idRow, container) {
		this.ajax.request({
			data: {
				jenis: "edit",
				tbl: AppState.tbl,
				id_row: idRow,
			},
			success: (res) => {
				if (res.success && res.data) {
					// tentukan target form sesuai container
					let $formTarget =
						container === "modal" ? $("#form_modal") : $("#form_flyout");

					Object.keys(res.data).forEach((key) => {
						$formTarget.find(`[name="${key}"]`).val(res.data[key]);
					});
				}
			},
		});
	}
	/* --------------------------------------------- */
	buildConfig(jenis, tbl) {
		let config = {
			icon: "folder icon",
			header: "",
			elements: [],
		};

		config.header =
			jenis === "add"
				? "Tambah Data"
				: jenis === "edit"
					? "Edit Data"
					: "Detail Data";

		config.icon =
			jenis === "add"
				? "plus icon"
				: jenis === "edit"
					? "edit icon"
					: "eye icon";

		if (UIConfig[AppState.jenis]?.[tbl]) {
			config.elements = UIConfig[AppState.jenis][tbl];
		}

		config.elements.push({
			tag: "divider",
			prop: {},
		});

		config.elements.push({
			tag: "field",
			prop: {
				name: "_submit",
				atribut: `
					type="button"
					class="ui primary button btnSubmit"
					value="Simpan"
				`,
			},
		});

		return config;
	}

	/* --------------------------------------------- */
	save() {
		let $formTarget = this.getActiveForm();
		let formElement = $formTarget[0];

		if (!formElement) return;

		let formData = new FormData(formElement);

		formData.append("jenis", AppState.mode);
		formData.append("tbl", AppState.tbl);

		this.ajax.request({
			url: AppConfig.apiUrl + "dynamic/save",
			method: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: (res) => {
				if (res.success) {
					this.hide(this.activeContainer); // <-- lebih aman
					new TableManager().fetch();
				}
			},
		});
	}
}
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

$(document).ready(function () {
	/* ---------------------------------------------
	   Inisialisasi Table Manager
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
	---------------------------------------------- */
	const params = new URLSearchParams(window.location.search);
	const tblFromUrl = params.get("tbl");

	/* ---------------------------------------------
	   Ambil path aktif
	---------------------------------------------- */
	const currentPath = window.location.pathname.replace(/^\/+/g, "");

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
	============================================= */
	$(document).on("click", '[name="menu_table"]', function (e) {
		e.preventDefault();

		let jenis = $(this).attr("jenis");
		let tbl = $(this).attr("tbl");

		tableManager.load(jenis, tbl);
	});

	/* =============================================
	   DROPDOWN COUNT ROW
	============================================= */
	$("#countRow").dropdown({
		onChange: function (value) {
			AppState.rows = parseInt(value) || 10;
			AppState.halaman = 1;
			tableManager.fetch();
		},
	});

	/* ---------------------------------------------
	   Inisialisasi dropdown & accordion
	---------------------------------------------- */
	$(".ui.dropdown").dropdown();
	$(".ui.accordion").accordion({ exclusive: false });

	/* =============================================
	   PAGINATION CLICK
	============================================= */
	$(document).on("click", '[name^="pagination_"] .item', function () {
		let page = parseInt($(this).data("page"));
		if (!page) return;

		AppState.halaman = page;
		tableManager.fetch();
	});

	/* =============================================
	   SEARCH INPUT (Debounce)
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

	/* =========================================================
	   RENSTRA PAGE MODULE (TETAP UTUH)
	========================================================= */

	function initRenstraPage() {
		if (!$(".renstra-page").length) return;

		$(".ui.dropdown").dropdown();
		$(".ui.checkbox").checkbox();

		$(".ui.calendar").calendar({
			type: "date",
			formatter: {
				date: function (date) {
					if (!date) return "";
					const day = ("0" + date.getDate()).slice(-2);
					const month = ("0" + (date.getMonth() + 1)).slice(-2);
					const year = date.getFullYear();
					return year + "-" + month + "-" + day;
				},
			},
		});

		$(".renstra-page .ui.form").form({
			fields: {
				kd_wilayah: {
					identifier: "kd_wilayah",
					rules: [{ type: "empty", prompt: "Kode Wilayah wajib diisi" }],
				},
				tahun: {
					identifier: "tahun",
					rules: [{ type: "empty", prompt: "Tahun wajib diisi" }],
				},
				tahun_renstra: {
					identifier: "tahun_renstra",
					rules: [{ type: "empty", prompt: "Tahun Renstra wajib dipilih" }],
				},
				aturan_anggaran: {
					identifier: "aturan_anggaran",
					rules: [{ type: "empty", prompt: "Aturan Anggaran wajib dipilih" }],
				},
			},
		});

		function lockSection(trigger, fields) {
			$(document).on("change", trigger, function () {
				fields.forEach((name) => {
					let field = $('[name="' + name + '"]').closest(".field");
					if ($(this).is(":checked")) {
						field.find("input, .ui.dropdown").addClass("disabled");
					} else {
						field.find("input, .ui.dropdown").removeClass("disabled");
					}
				});
			});
		}

		lockSection('input[name="kunci_renja"]', ["awal_renja", "akhir_renja"]);
		lockSection('input[name="kunci_dpa"]', ["awal_dpa", "akhir_dpa"]);
		lockSection('input[name="kunci_renstra"]', [
			"awal_renstra",
			"akhir_renstra",
		]);

		function loadDropdown(name, url) {
			$.get(
				url,
				function (res) {
					let dropdown = $('[name="' + name + '"]').closest(".ui.dropdown");
					let menu = dropdown.find(".menu");
					menu.html("");

					if (res.data) {
						res.data.forEach((item) => {
							menu.append(
								'<div class="item" data-value="' +
									item.id +
									'">' +
									item.nama +
									"</div>",
							);
						});
					}

					dropdown.dropdown("refresh");
				},
				"json",
			);
		}

		loadDropdown("aturan_anggaran", "/referensi/load?jenis=anggaran");
		loadDropdown("aturan_akun", "/referensi/load?jenis=akun");
	}

	/* ---------------------------------------------
	   Inisialisasi Flyout Manager
	---------------------------------------------- */
	// let flyoutManager = new FlyoutManager("#mainContext");
	let formContainerManager = new FormContainerManager();
	/* =========================================
	   WALLCHAT MODULE (TETAP UTUH)
	========================================= */

	$(document).on("submit", "#formPost", function (e) {
		e.preventDefault();
		$.post(
			"/wallchat/store",
			$(this).serialize(),
			function (res) {
				if (res.status) location.reload();
			},
			"json",
		);
	});

	$(document).on("submit", ".formComment", function (e) {
		e.preventDefault();

		let parent_id = $(this).data("id");
		let content = $(this).find('input[name="content"]').val();

		$.post(
			"/wallchat/comment",
			{
				parent_id: parent_id,
				content: content,
			},
			function (res) {
				if (res.status) location.reload();
			},
			"json",
		);
	});

	$(document).on("click", "#btnPrivateMessage", function () {
		$("#modalPrivateMessage").modal("show");
	});

	$(document).on("submit", "#formPrivateMessage", function (e) {
		e.preventDefault();

		$.post(
			"/wallchat/private",
			$(this).serialize(),
			function (res) {
				if (res.status) {
					$("#modalPrivateMessage").modal("hide");
					Swal.fire({
						icon: "success",
						title: "Berhasil",
						text: res.message,
					});
				}
			},
			"json",
		);
	});
	/* ---------------------------------------------
	   Handler Delete
	---------------------------------------------- */
	$(document).on("click", '[data-ui="delete-row"]', function () {
		let tbl = $(this).data("tbl");
		let id = $(this).data("id");

		Swal.fire({
			title: "Yakin hapus data?",
			icon: "warning",
			showCancelButton: true,
		}).then((result) => {
			if (!result.isConfirmed) return;

			$.post(
				AppConfig.apiUrl + "dynamic",
				{
					jenis: "delete",
					tbl: tbl,
					id_row: id,
				},
				function () {
					tableManager.fetch();
				},
			);
		});
	});
	// Delete
	$(document).on("click", '[data-ui="delete-row"]', (e) => {
		let id = $(e.currentTarget).data("id");

		if (!confirm("Yakin hapus data?")) return;

		this.ajax.request({
			data: {
				jenis: "delete",
				tbl: AppState.tbl,
				id_row: id,
			},
			success: (res) => {
				if (res.success) {
					new TableManager().fetch();
				}
			},
		});
	});
	/* ---------------------------------------------
	   Load Profil jika halaman profil
	---------------------------------------------- */
	if (window.location.pathname === "/profil") {
		loadProfil();
	}

	initRenstraPage();
});
