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
	module: "", // Module aktif (referensi, renstra, dll)
	action: "", // add / edit / detail  <-- TAMBAH INI
	tbl: "", // Tabel aktif
	cari: "", // Keyword pencarian
	currentMenu: "", // Tracking menu sebelumnya
	serverSources: [], // 🔥 @note daftar dropdown yang boleh fetch server
	primaryKey: "id",
	page: "", // untuk antisipasi seperti 1 halaman mempunyai banyak menu
};
/* =========================================================
   GLOBAL TOAST ENGINE
========================================================= */

const ToastEngine = {
	show({ success = true, message = "" }) {
		if (!message) return;

		let maxToast = 5;

		// 🔥 batasi maksimal 3 toast
		let existing = $("#toastContainer .ui.toast");

		if (existing.length >= maxToast) {
			existing.first().remove();
		}

		$("#toastContainer").toast({
			message: message,
			class: success ? "success" : "error",
			displayTime: 3000,
			showProgress: "bottom",
			context: "#toastContainer",
			position: "top right",
		});
	},
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
				// 🔥 AUTO TOAST GLOBAL
				if (res && typeof res.success !== "undefined") {
					ToastEngine.show({
						success: res.success,
						message: res.message || "",
					});
				}

				if (success) success(res);
			},
			error: function (xhr) {
				let response = xhr.responseJSON;

				if (response) {
					ToastEngine.show({
						success: false,
						message: response.message || "Terjadi kesalahan",
					});

					if (error) error(response);
				} else {
					ToastEngine.show({
						success: false,
						message: "Server error",
					});
				}
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
	// DEFAULT GLOBAL
	default: {
		roles: {
			super_admin: ["edit", "delete"],
			admin_wilayah: ["edit", "delete"],
			admin_opd: ["edit"],
			editor: ["edit"],
			viewer: [],
		},
	},

	// ===============================
	// REFERENSI
	// ===============================
	referensi: {
		urusan: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		bidang: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		program: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		kegiatan: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		sub_kegiatan: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},
	},

	// ===============================
	// KEPEGAWAIAN
	// ===============================
	kepegawaian: {
		asn: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: [],
				admin_opd: ["edit"],
				editor: ["edit"],
				viewer: [],
			},
		},
	},

	// ===============================
	// RENSTRA
	// ===============================
	renstra: {
		renstra_neo: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		misi_renstra_neo: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
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
		// -------------------------------------------------
		// Inisialisasi engine AJAX
		// Semua request ke backend melalui endpoint dynamic
		// -------------------------------------------------
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
	}

	/* =====================================================
	   LOAD TABLE
	   -----------------------------------------------------
	   Dipanggil saat klik menu
	   - Reset halaman jika pindah menu
	   - Set module & table aktif
	   - Render header (sekali saja)
	   - Lakukan fetch data
	===================================================== */
	load(module, tbl) {
		// Reset halaman jika user pindah menu
		if (AppState.currentMenu !== tbl) {
			AppState.halaman = 1;
		}

		// Simpan state aktif
		AppState.module = module;
		AppState.tbl = tbl;
		AppState.currentMenu = tbl;

		// Render header tabel
		this.renderHeader();

		// Ambil data dari backend
		this.fetch();
	}

	/* =====================================================
	   FETCH DATA
	   -----------------------------------------------------
	   Kirim parameter ke backend:
	   - module
	   - action (list)
	   - tbl
	   - halaman
	   - rows
	   - cari
	===================================================== */
	fetch() {
		// Sinkronisasi jumlah row dari dropdown Fomantic
		if ($("#countRow").length) {
			let value = $("#countRow").dropdown("get value");
			AppState.rows = parseInt(value) || AppState.rows;
		}

		this.ajax.request({
			data: {
				module: AppState.module,
				action: "list",
				tbl: AppState.tbl,
				halaman: AppState.halaman,
				rows: AppState.rows,
				cari: AppState.cari,
			},
			success: (res) => {
				// Validasi response
				if (!res || res.success !== true) {
					console.warn(res?.message || "Response tidak valid");
					this.renderTable([]);
					this.renderFooterStatic(); // 🔥 jika gagal → tetap buat footer
					return;
				}

				let rows = Array.isArray(res.data) ? res.data : [];
				let meta = res.meta || {};

				// Simpan primary key dari backend
				AppState.primaryKey = meta.primary_key || "id";

				// Render isi tabel
				this.renderTable(rows);

				// 🔥 LOGIC BARU:
				// Jika data kosong → render footer
				// Jika ada data → render pagination
				if (!rows.length) {
					this.renderFooterStatic();
				} else {
					this.renderPagination({
						total: meta.total || 0,
						limit: meta.limit || AppState.rows,
						page: meta.page || AppState.halaman,
					});
				}
			},
		});
	}

	/* =====================================================
	   RENDER TBODY
	   -----------------------------------------------------
	   Mengisi isi tabel berdasarkan data backend
	===================================================== */
	renderTable(rows) {
		let $tbody = $(`tbody[name="tabel_${AppState.tbl}"]`);

		// Fallback jika tidak ketemu
		if (!$tbody.length) {
			$tbody = $('tbody[name^="tabel_"]').first();
		}

		if (!$tbody.length) {
			ToastEngine.show({
				success: false,
				message: "Struktur tabel belum siap atau belum dikonfigurasi.",
			});
			return;
		}

		let html = "";

		// Jika tidak ada data
		if (!rows.length) {
			html = `<tr>
				<td colspan="100%" class="center aligned">
					<div class="ui placeholder segment">
				<div class="ui icon header">
					<i class="inbox icon"></i>
					Belum ada data tersedia
				</div>
				<p>Tambahkan data baru untuk mulai mengisi tabel ini.</p>
			</div>
				</td>
			</tr>`;
			$tbody.html(html);
			return;
		}

		// Ambil konfigurasi field dari UIConfig
		let elements =
			UIConfig[AppState.module]?.[AppState.tbl] ||
			UIConfig[AppState.page]?.[AppState.tbl] ||
			[];
		let fields = elements.filter((e) => e.prop?.name && !e.prop.non_data);

		// Loop setiap baris data
		rows.forEach((row) => {
			html += "<tr>";

			// Loop setiap kolom field
			fields.forEach((field) => {
				let key = field.prop.name;
				html += `<td>${row[key] ?? ""}</td>`;
			});

			// Kolom aksi
			html += `<td class="collapsing">
						${this.buildActionButtons(row)}
					</td>`;

			html += "</tr>";
		});

		$tbody.html(html);
	}

	/* =====================================================
	   RENDER FOOTER STATIC
	   -----------------------------------------------------
	   Digunakan jika tidak ada data
	   Jumlah kolom disamakan dengan header
	===================================================== */
	renderFooterStatic() {
		// Ambil table aktif
		let $tbody = $(`tbody[name="tabel_${AppState.tbl}"]`);
		if (!$tbody.length) {
			$tbody = $('tbody[name^="tabel_"]').first();
		}
		if (!$tbody.length) return;

		let $table = $tbody.closest("table");

		// 🔥 Hitung kolom dari HEADER (bukan body)
		let headerCount = $table.find("thead tr:first th").length;

		if (!headerCount) return;

		let tfootHtml = "<tr>";

		// Buat jumlah <td> sesuai jumlah <th>
		for (let i = 0; i < headerCount; i++) {
			tfootHtml += `<td></td>`;
		}

		tfootHtml += "</tr>";

		// Jika belum ada tfoot → buat
		if (!$table.find("tfoot").length) {
			$table.append("<tfoot></tfoot>");
		}

		$table.find("tfoot").html(tfootHtml);

		// Kosongkan pagination
		$(`div[name="pagination_${AppState.module}"]`).html("");
	}

	/* =====================================================
	   RENDER PAGINATION
	   -----------------------------------------------------
	   ⚠️ LOGIC ASLI TIDAK DIUBAH
	===================================================== */
	renderPagination(meta) {
		let target = `div[name="pagination_${AppState.module}"]`;

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
		// 🔥 Samakan colspan footer dengan jumlah header
		let $tbody = $(`tbody[name="tabel_${AppState.tbl}"]`);
		if ($tbody.length) {
			let $table = $tbody.closest("table");
			let headerCount = $table.find("thead tr:first th").length;

			if (headerCount) {
				$table.find("tfoot td").attr("colspan", headerCount);
			}
		}
	}

	/* =====================================================
	   BUILD ACTION BUTTONS
	   -----------------------------------------------------
	   Menentukan tombol edit/delete sesuai role
	===================================================== */
	buildActionButtons(row) {
		let module = AppState.module;
		let tbl = AppState.tbl;
		let role = AppState.role;

		let moduleConfig = ActionConfig[module]?.[tbl];
		let defaultConfig = ActionConfig.default;

		let buttons = [];

		if (moduleConfig?.roles?.[role]) {
			buttons = moduleConfig.roles[role];
		} else if (defaultConfig?.roles?.[role]) {
			buttons = defaultConfig.roles[role];
		}

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
					data-module="${module}"
					data-id="${row[AppState.primaryKey]}"> 
					<i class="edit outline blue icon"></i>
				</button>`;
			}

			if (btn === "delete") {
				html += `
				<button class="ui red button"
					data-ui="delete-row"
					data-jns="delete"
					data-tbl="${tbl}"
					data-id="${row[AppState.primaryKey]}">
					<i class="trash alternate outline red icon"></i>
				</button>`;
			}
		});

		html += `</div>`;
		return html;
	}

	/* =====================================================
	   RENDER HEADER
	   -----------------------------------------------------
	   Membuat thead berdasarkan UIConfig
	   Dieksekusi sekali saat load
	===================================================== */
	renderHeader() {
		let $tbody = $(`tbody[name="tabel_${AppState.tbl}"]`);
		if (!$tbody.length) {
			$tbody = $('tbody[name^="tabel_"]').first();
		}

		if (!$tbody.length) return;

		let $table = $tbody.closest("table");

		let elements =
			UIConfig[AppState.module]?.[AppState.tbl] ||
			UIConfig[AppState.page]?.[AppState.tbl] ||
			[];
		let fields = elements.filter((el) => el.prop?.name && !el.prop.non_data);

		let theadHtml = "<tr>";

		fields.forEach((field) => {
			theadHtml += `<th>${field.prop.label || field.prop.name}</th>`;
		});

		theadHtml += `<th class="collapsing">Aksi</th></tr>`;

		$table.find("thead").html(theadHtml);
	}
}
/* =========================================================
	 FORM ENGINE PRO - FIELD VARIATIONS (FOMANTIC STYLE)@note
========================================================= */

class FormEngine {
	/* ============================
		 Render Form ke Container
	============================ */
	static render(container, elements = []) {
		let html = this.build(elements);

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
			case "fieldAction":
				return this.fieldWrapper(this.inputAction(prop), prop);
			/* ===== SINGLE FIELD ===== */
			case "fieldCalendar":
				return this.fieldWrapper(this.calendar(prop), prop);
			case "cardProfile":
				return this.cardProfile(prop);

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
			case "fieldFile":
				return this.fieldWrapper(
					`
        <div class="ui file input">
            <input type="file"
                   name="${prop.name}"
                   accept="${prop.accept || ""}">
        </div>
    `,
					prop,
				);

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
	//Kalender
	static calendar(prop) {
		let attrs = "";

		if (prop.atribut) {
			attrs += prop.atribut;
		}

		return `
    <div class="ui calendar" ${attrs}>
        <div class="ui input left icon ${prop.classInput || ""}">
            <i class="calendar icon"></i>
            <input 
                type="text"
                name="${prop.name}"
                data-type="${prop.calendarType || "date"}"
                placeholder="${prop.placeholder || "Pilih Tanggal"}"
                ${prop.readonly ? "readonly" : ""}
            >
        </div>
    </div>
    `;
	}
	//CARD
	static cardProfile(prop) {
		let defaultImage = AppConfig.baseUrl + "assets/img/avatar/default.jpeg";

		let imagePath = prop?.image?.trim() ? prop.image : defaultImage;

		return `
	<div class="ui special fluid card">

		<div class="content">
			<div class="right floated meta">${prop.meta || ""}</div>
			<img class="ui avatar image"
			     src="${defaultImage}"
			     onerror="imgsrc(this)">
			${prop.title || ""}
		</div>

		<div class="blurring dimmable image">
			<div class="ui dimmer">
				<div class="content">
					<div class="center">
						<button class="ui inverted icon button"
							name="direct"
							type="button"
							jns="upload"
							tbl="${prop.table}"
							id_row="${prop.id_row || ""}"
							dok="${prop.dokumen}"
							accept="${prop.accept}">
							<i class="file icon"></i>
							Upload File
						</button>
					</div>
				</div>
			</div>
			<img src="${defaultImage}" onerror="imgsrc(this)">
		</div>

		<div class="content">
			<span class="right floated">
				<i class="heart outline like icon"></i> likes
			</span>
			<i class="comment icon"></i> comments
		</div>

	</div>
	`;
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
	static inputAction(prop) {
		return `
        <div class="ui action input ${prop.classInput || ""}">
            <input type="text"
                   name="${prop.name}"
                   placeholder="${prop.placeholder || ""}">
            <button class="ui ${prop.button?.class || ""} icon button"
                ${Object.entries(prop.button?.attr || {})
									.map(([k, v]) => `${k}="${v}"`)
									.join(" ")}>
                <i class="${prop.button?.icon || "search"} icon"></i>
            </button>
        </div>
    `;
	}
	/* ============================
		 Dropdown
	============================ */
	static dropdown(prop) {
		let staticOptions = "";

		if (prop.options && prop.options.length) {
			prop.options.forEach((opt) => {
				staticOptions += `
				<div class="item" data-value="${opt.value}">
					${opt.text}
				</div>
			`;
			});
		}

		// ===============================
		// BUILD CLASS DINAMIS
		// ===============================
		let classes = "ui";

		if (prop.search) classes += " search";
		if (prop.multiple) classes += " multiple";
		if (prop.clearable) classes += " clearable";
		if (prop.disabled) classes += " disabled";

		classes += " selection dropdown";

		return `
		<div class="${classes} ${prop.classInput || ""}"
			data-source="${prop.source || ""}"
			data-parent="${prop.parent || ""}">
			 
			<input type="hidden" name="${prop.name}">
			<i class="dropdown icon"></i>
			<div class="default text">${prop.placeholder || "Pilih"}</div>
			<div class="menu">
				${staticOptions}
			</div>
		</div>
	`;
	}

	/* ============================
		 Init Fomantic Components
	============================ */
	static init() {
		// ==============================
		// DROPDOWN
		// ==============================
		$(".ui.dropdown").dropdown();

		// ==============================
		// CHECKBOX
		// ==============================
		$(".ui.checkbox").checkbox();

		// ==============================
		// DIMMER
		// ==============================
		$(".ui.card .image").dimmer({
			on: "hover",
		});

		// ==============================
		// CALENDAR DEFAULT INIT
		// ==============================
		$(".ui.calendar").each(function () {
			let $calendar = $(this);
			let type = $calendar.find("input").attr("data-type");

			$calendar.calendar({
				type: type || "date",
				formatter: {
					date: function (date) {
						if (!date) return "";

						// jika type year → tampilkan tahun saja
						if (type === "year") {
							return date.getFullYear();
						}

						return date.toLocaleDateString();
					},
				},
			});
		});

		// ==============================
		// RANGE CALENDAR AUTO LINK
		// ==============================
		let groups = {};

		$(".ui.calendar[data-group]").each(function () {
			let $calendar = $(this);
			let group = $calendar.data("group");
			let range = $calendar.data("range"); // start / end

			if (!groups[group]) {
				groups[group] = {};
			}

			groups[group][range] = $calendar;
		});

		Object.keys(groups).forEach((group) => {
			let start = groups[group]["start"];
			let end = groups[group]["end"];

			if (start && end) {
				let startType = start.find("input").data("type") || "date";
				let endType = end.find("input").data("type") || "date";

				start.calendar("destroy");
				end.calendar("destroy");

				start.calendar({
					type: startType,
					endCalendar: end,
					formatter: {
						date: function (date) {
							if (!date) return "";
							return startType === "year"
								? date.getFullYear()
								: date.toLocaleDateString();
						},
					},
				});

				end.calendar({
					type: endType,
					startCalendar: start,
					formatter: {
						date: function (date) {
							if (!date) return "";
							return endType === "year"
								? date.getFullYear()
								: date.toLocaleDateString();
						},
					},
				});
			}
		});
	}
}
/* =========================================================
   ROLE ENGINE
========================================================= */

const RoleConfig = {
	admin_opd: {
		canAdd: true,
		canEdit: true,
		canDelete: true,
		canUpload: true,
	},
	super_admin: {
		canAdd: true,
		canEdit: true,
		canDelete: true,
		canUpload: true,
	},
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
AppState.role = window.USER_ROLE || "viewer";
// ======================================================
// 🔥 UI CONFIG (FINAL CLEAN VERSION) @note UIConfig
// ------------------------------------------------------
// - Dynamic dropdown → pakai prop.source
// - Static dropdown → pakai prop.options
// - Atribut HTML → pakai prop.atribut
// - Tidak ada data-server lagi
// ======================================================

const UIConfig = {
	/* ======================================================
       PENGATURAN
    ====================================================== */
	pengaturan: {
		periode_rpjmd: [
			{
				tag: "fieldCalendar",
				prop: {
					label: "Periode Mulai",
					name: "periode_mulai",
					calendarType: "year",
					atribut: `data-group="rpjmd" data-range="start"`,
				},
			},
			{
				tag: "fieldCalendar",
				prop: {
					label: "Periode Selesai",
					name: "periode_selesai",
					calendarType: "year",
					atribut: `data-group="rpjmd" data-range="end"`,
				},
			},
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
			{ tag: "fieldCheckbox", prop: { label: "Aktif", name: "status_aktif" } },
		],
	},

	/* ======================================================
       RENSTRA
    ====================================================== */
	renstra: {
		/* ==============================
       1. RENSTRA
    ============================== */
		renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Periode RPJMD",
					name: "periode_id",
					source: "periode_rpjmd",
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Visi", name: "visi", atribut: `rows="3"` },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Status", name: "status" },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Kunci", name: "kunci" },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Setujui", name: "setujui" },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       2. MISI
    ============================== */
		misi_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Pilih Visi",
					name: "renstra_id",
					source: "renstra_neo",
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Nama Misi", name: "nama_misi", atribut: `rows="2"` },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       3. TUJUAN
    ============================== */
		tujuan_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: { label: "Misi", name: "misi_id", source: "misi_renstra_neo" },
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Nama Tujuan",
					name: "nama_tujuan",
					atribut: `rows="2"`,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       4. SASARAN
    ============================== */
		sasaran_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Tujuan",
					name: "tujuan_id",
					source: "tujuan_renstra_neo",
				},
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Nama Sasaran",
					name: "nama_sasaran",
					atribut: `rows="2"`,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       5. INDIKATOR SASARAN
    ============================== */
		indikator_sasaran_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sasaran",
					name: "sasaran_id",
					source: "sasaran_renstra_neo",
				},
			},
			{
				tag: "field",
				prop: { label: "Nama Indikator", name: "nama_indikator" },
			},
			{ tag: "field", prop: { label: "Satuan", name: "satuan" } },
			{ tag: "field", prop: { label: "Baseline", name: "baseline" } },
			{ tag: "field", prop: { label: "Target T1", name: "target_t1" } },
			{ tag: "field", prop: { label: "Target T2", name: "target_t2" } },
			{ tag: "field", prop: { label: "Target T3", name: "target_t3" } },
			{ tag: "field", prop: { label: "Target T4", name: "target_t4" } },
			{ tag: "field", prop: { label: "Target T5", name: "target_t5" } },
			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       6. PROGRAM
    ============================== */
		program_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sasaran",
					name: "sasaran_id",
					source: "sasaran_renstra_neo",
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program (Permendagri)",
					name: "kode_program",
					source: "program",
					searchable: true,
					dropdownClass: "search selection scrolling",
				},
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: `rows="2"`,
				},
			},
		],

		/* ==============================
       7. INDIKATOR PROGRAM
    ============================== */
		indikator_program_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "program_id",
					source: "program_renstra_neo",
				},
			},
			{
				tag: "field",
				prop: { label: "Nama Indikator", name: "nama_indikator" },
			},
			{ tag: "field", prop: { label: "Satuan", name: "satuan" } },
			{ tag: "field", prop: { label: "Baseline", name: "baseline" } },
			{ tag: "field", prop: { label: "Target T1", name: "target_t1" } },
			{ tag: "field", prop: { label: "Target T2", name: "target_t2" } },
			{ tag: "field", prop: { label: "Target T3", name: "target_t3" } },
			{ tag: "field", prop: { label: "Target T4", name: "target_t4" } },
			{ tag: "field", prop: { label: "Target T5", name: "target_t5" } },
			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       8. KEGIATAN
    ============================== */
		kegiatan_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "program_id",
					source: "program_renstra_neo",
				},
			},
			{ tag: "field", prop: { label: "Kode Kegiatan", name: "kode_kegiatan" } },
			{
				tag: "fieldTextarea",
				prop: {
					label: "Nama Kegiatan",
					name: "nama_kegiatan",
					atribut: `rows="2"`,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       9. SUB KEGIATAN
    ============================== */
		sub_kegiatan_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kegiatan",
					name: "kegiatan_renstra_id",
					source: "kegiatan_renstra_neo",
				},
			},
			{
				tag: "field",
				prop: { label: "Master Sub Kegiatan", name: "master_sub_kegiatan_id" },
			},
			{ tag: "field", prop: { label: "Lokasi", name: "lokasi" } },
			{
				tag: "field",
				prop: { label: "Kelompok Sasaran", name: "kelompok_sasaran" },
			},
			{ tag: "field", prop: { label: "Baseline", name: "baseline" } },

			{ tag: "field", prop: { label: "Target T1", name: "target_t1" } },
			{ tag: "field", prop: { label: "Anggaran T1", name: "anggaran_t1" } },

			{ tag: "field", prop: { label: "Target T2", name: "target_t2" } },
			{ tag: "field", prop: { label: "Anggaran T2", name: "anggaran_t2" } },

			{ tag: "field", prop: { label: "Target T3", name: "target_t3" } },
			{ tag: "field", prop: { label: "Anggaran T3", name: "anggaran_t3" } },

			{ tag: "field", prop: { label: "Target T4", name: "target_t4" } },
			{ tag: "field", prop: { label: "Anggaran T4", name: "anggaran_t4" } },

			{ tag: "field", prop: { label: "Target T5", name: "target_t5" } },
			{ tag: "field", prop: { label: "Anggaran T5", name: "anggaran_t5" } },

			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir" } },

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},

	/* ======================================================
       REFERENSI
    ====================================================== */
	referensi: {
		urusan: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		bidang: [
			{
				tag: "fieldDropdown",
				prop: { label: "Urusan", name: "kode_urusan", source: "urusan" },
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		program: [
			{
				tag: "fieldDropdown",
				prop: { label: "Bidang", name: "kode_bidang", source: "bidang" },
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		kegiatan: [
			{
				tag: "fieldDropdown",
				prop: { label: "Program", name: "kode_program", source: "program" },
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		sub_kegiatan: [
			{
				tag: "fieldDropdown",
				prop: { label: "Kegiatan", name: "kode_kegiatan", source: "kegiatan" },
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		akun: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		satuan: [
			{ tag: "field", prop: { label: "Value", name: "value" } },
			{ tag: "field", prop: { label: "Item", name: "item" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		organisasi: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Alamat", name: "alamat" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		wilayah: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Status", name: "status" } },
		],

		peraturan: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{ tag: "field", prop: { label: "Judul", name: "judul" } },
			{ tag: "field", prop: { label: "Status", name: "status" } },
		],

		rekanan: [
			// ===============================
			// DATA PERUSAHAAN
			// ===============================
			{
				tag: "divider",
				prop: { label: "Data Perusahaan" },
			},

			{
				tag: "field",
				prop: {
					label: "Nama Perusahaan",
					name: "nama_perusahaan",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "NPWP", name: "npwp" } },

			{ tag: "field", prop: { label: "Email", name: "email" } },

			{
				tag: "fieldTextarea",
				prop: {
					label: "Alamat Perusahaan",
					name: "alamat",
					atribut: `rows="2"`,
				},
			},

			// ===============================
			// DATA REKENING
			// ===============================
			{
				tag: "divider",
				prop: { label: "Data Rekening" },
			},

			{ tag: "field", prop: { label: "Nomor Rekening", name: "no_rekening" } },

			{ tag: "field", prop: { label: "Bank", name: "bank_rekening" } },

			{
				tag: "field",
				prop: { label: "Atas Nama Rekening", name: "atas_nama_rekening" },
			},

			// ===============================
			// DATA DIREKTUR
			// ===============================
			{
				tag: "divider",
				prop: { label: "Data Direktur" },
			},

			{ tag: "field", prop: { label: "Nama Direktur", name: "direktur" } },

			{ tag: "field", prop: { label: "Jabatan", name: "jabatan" } },

			{ tag: "field", prop: { label: "No KTP", name: "no_ktp" } },

			{
				tag: "fieldTextarea",
				prop: {
					label: "Alamat Direktur",
					name: "alamat_dir",
					atribut: `rows="2"`,
				},
			},

			// ===============================
			// DATA AKTA & NOTARIS
			// ===============================
			{
				tag: "divider",
				prop: { label: "Akta Pendirian" },
			},

			{
				tag: "field",
				prop: { label: "No Akta Pendirian", name: "no_akta_pendirian" },
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Akta Pendirian",
					name: "tgl_akta_pendirian",
					calendarType: "date",
				},
			},

			{
				tag: "fields",
				prop: {
					classGroup: "two",
					children: [
						{
							tag: "field",
							prop: {
								label: "Nama Notaris Pendirian",
								name: "nama_notaris_pendirian",
							},
						},
						{
							tag: "field",
							prop: {
								label: "Lokasi Notaris Pendirian",
								name: "lokasi_notaris_pendirian",
							},
						},
					],
				},
			},

			{
				tag: "divider",
				prop: { label: "Akta Perubahan" },
			},

			{
				tag: "field",
				prop: { label: "No Akta Perubahan", name: "no_akta_perubahan" },
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Akta Perubahan",
					name: "tgl_akta_perubahan",
					calendarType: "date",
				},
			},

			{
				tag: "fields",
				prop: {
					classGroup: "two",
					children: [
						{
							tag: "field",
							prop: {
								label: "Nama Notaris Perubahan",
								name: "nama_notaris_perubahan",
							},
						},
						{
							tag: "field",
							prop: {
								label: "Lokasi Notaris Perubahan",
								name: "lokasi_notaris_perubahan",
							},
						},
					],
				},
			},

			// ===============================
			// DATA LAIN
			// ===============================
			{
				tag: "divider",
				prop: { label: "Informasi Tambahan" },
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Data Lain", name: "data_lain", atribut: `rows="2"` },
			},

			{
				tag: "fieldFile",
				prop: { label: "Upload Dokumen", name: "file" },
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Non Aktif", name: "disable" },
			},
		],

		sumber_dana: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		mapping: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Uraian Aset", name: "uraian_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Akun", name: "uraian_akun" } },
		],

		aset: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
		],
	},

	/* ======================================================
       STANDAR HARGA
    ====================================================== */
	standar_harga: {
		sbu: [
			{
				tag: "field",
				prop: { label: "Kode Aset", name: "kd_aset", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Kode Akun", name: "kd_akun", classField: "required" },
			},
			{
				tag: "field",
				prop: {
					label: "Uraian Barang",
					name: "uraian_barang",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],

		ssh: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Barang", name: "uraian_barang" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],

		asb: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Barang", name: "uraian_barang" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],

		hspk: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Barang", name: "uraian_barang" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],
	},

	/* ======================================================
       KEPEGAWAIAN
    ====================================================== */
	kepegawaian: {
		asn: [
			{
				tag: "cardProfile",
				prop: {
					title: "Aparatur Sipil Negara (ASN)",
					meta: "14h",
					image: "img/avatar/default.jpeg",
					table: "asn",
					id_row: 1,
					dokumen: "file_photo",
					accept: ".jpg,.png,.jpeg",
				},
			},

			{
				tag: "field",
				prop: {
					label: "Nama Lengkap (tanpa gelar)",
					name: "nama",
					placeholder: "Nama Lengkap (tanpa gelar)",
					classField: "required",
				},
			},

			{
				tag: "fieldAction",
				prop: {
					label: "Nomor Induk Pegawai",
					name: "nip",
					placeholder: "NIP",
					button: {
						icon: "search",
						class: "teal",
						attr: {
							jns: "get_data",
							tbl: "asn",
							klm: "nip",
						},
					},
					classField: "required",
				},
			},

			{
				tag: "field",
				prop: {
					label: "Gelar",
					name: "gelar",
					placeholder: "Gelar di belakang nama",
					non_data: true,
				},
			},

			{
				tag: "field",
				prop: {
					label: "Gelar Depan Nama",
					name: "gelar_depan",
					placeholder: "Gelar di depan nama",
					non_data: true,
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Kelompok Jabatan",
					name: "kelompok",
					classField: "required",
					options: [
						{ value: "1", text: "Kepala OPD" },
						{ value: "2", text: "Sekretaris" },
						{ value: "3", text: "Kepala Bidang" },
						{ value: "4", text: "ASN" },
						{ value: "5", text: "non ASN" },
					],
				},
			},

			{
				tag: "field",
				prop: {
					label: "Jabatan",
					name: "jabatan",
					placeholder: "Jabatan...",
				},
			},

			{
				tag: "field",
				prop: {
					label: "Tempat Lahir",
					name: "t4_lahir",
					placeholder: "Tempat lahir",
					classField: "required",
				},
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Lahir",
					name: "tgl_lahir",
					calendarType: "date",
					readonly: true,
					classField: "required",
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Golongan",
					name: "golongan",
					options: [
						{ value: "1", text: "I" },
						{ value: "2", text: "II" },
						{ value: "3", text: "III" },
						{ value: "4", text: "IV" },
					],
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Ruang",
					name: "ruang",
					options: [
						{ value: "a", text: "a" },
						{ value: "b", text: "b" },
						{ value: "c", text: "c" },
						{ value: "d", text: "d" },
						{ value: "e", text: "e" },
					],
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Jenis Kepegawaian",
					name: "jenis_kepeg",
					options: [
						{ value: "pnsp", text: "ASN pusat" },
						{ value: "pnsd1", text: "ASN Provinsi" },
						{ value: "pnsd2", text: "ASN Kabupaten/Kota" },
						{ value: "pnsp_dpb1", text: "ASN Pusat diperbantukan Provinsi" },
						{ value: "pnsp_dpb2", text: "ASN Pusat diperbantukan Kab./Kota" },
						{ value: "pnsp_dpk1", text: "ASN Pusat dipekerjakan Provinsi" },
						{ value: "pnsp_dpk2", text: "ASN Pusat dipekerjakan Kab./Kota" },
						{ value: "pnsd_dpb_pusat", text: "ASN Daerah diperbantukan Pusat" },
						{ value: "pnsd_dpk_pusat", text: "ASN Daerah dipekerjakan Pusat" },
						{ value: "swasta", text: "Swasta" },
					],
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Status Kepegawaian",
					name: "status_kepeg",
					options: [
						{ value: "capeg", text: "Calon Pegawai" },
						{ value: "peg_tetap", text: "ASN/Pegawai tetap" },
						{ value: "mpp", text: "Masa Persiapan Pensiun" },
						{ value: "pen_uang_tunggu", text: "Pensiunan" },
						{ value: "peg_seorsing", text: "Pegawai Seorsing" },
						{ value: "cuti", text: "Cuti" },
						{ value: "peg_sementara", text: "Pegawai Sementara" },
						{ value: "peg_bulanan", text: "Pegawai Bulanan" },
					],
				},
			},

			{ tag: "field", prop: { label: "Nomor KTP", name: "no_ktp" } },
			{ tag: "field", prop: { label: "NPWP", name: "npwp" } },
			{ tag: "field", prop: { label: "Alamat", name: "alamat" } },
			{ tag: "field", prop: { label: "Kontak Person", name: "kontak_person" } },
			{ tag: "field", prop: { label: "Email", name: "email" } },

			{
				tag: "fieldDropdown",
				prop: {
					label: "Agama",
					name: "agama",
					options: [
						{ value: "islam", text: "Islam" },
						{ value: "kristen", text: "Kristen" },
						{ value: "katolik", text: "Katolik" },
						{ value: "protestan", text: "Protestan" },
						{ value: "hindu", text: "Hindu" },
						{ value: "budha", text: "Budha" },
						{ value: "konghucu", text: "Konghucu" },
						{ value: "kepercayaan", text: "Kepercayaan Tuhan YME." },
					],
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Kelamin",
					name: "kelamin",
					classField: "required",
					options: [
						{ value: "pria", text: "Pria" },
						{ value: "wanita", text: "Wanita" },
					],
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Status",
					name: "status",
					options: [
						{ value: "menikah", text: "Menikah" },
						{ value: "janda-duda", text: "Duda-Janda" },
						{ value: "lajang", text: "Lajang" },
					],
				},
			},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: `rows="2"`,
				},
			},

			{
				tag: "fieldCheckbox",
				prop: {
					label: "Non Aktif",
					name: "disable",
				},
			},
		],
	},
	tata_naskah: {
		/* ======================================================
       1️⃣ SK (MENIMBANG, MENGINGAT, MENETAPKAN)
    ====================================================== */
		sk: [
			// =========================
			// NOMOR & TANGGAL
			// =========================
			{
				tag: "fields",
				prop: {
					classGroup: "three",
					children: [
						{
							tag: "field",
							prop: {
								label: "Nomor Surat",
								name: "nomor",
								classField: "required",
							},
						},

						{
							tag: "fieldCalendar",
							prop: {
								label: "Tanggal Surat",
								name: "tgl_surat_dibuat",
								calendarType: "date",
								classField: "required",
							},
						},

						{
							tag: "fieldFile",
							prop: {
								label: "File Dokumen",
								name: "file",
								accept: ".jpg,.jpeg,.png,.pdf,.docx",
							},
						},
					],
				},
			},

			// =========================
			// TENTANG
			// =========================
			{
				tag: "fieldTextarea",
				prop: {
					label: "Tentang",
					name: "tentang",
					atribut: 'rows="2"',
					classField: "required",
				},
			},

			// =========================
			// PEMBERI TUGAS
			// =========================
			{
				tag: "fields",
				prop: {
					classGroup: "three",
					children: [
						{
							tag: "fieldDropdown",
							prop: {
								label: "Pemberi Tugas",
								name: "pemberi_tgs",
								source: "asn", // ← ini penting
								search: true,
								clearable: true,
								classField: "required",
							},
						},

						{
							tag: "field",
							prop: {
								label: "Jabatan Pemberi Tugas",
								name: "jbt_pemberi_tgs",
								classField: "required",
							},
						},

						{
							tag: "field",
							prop: {
								label: "Pangkat Pemberi Tugas",
								name: "pangkat_pemberi_tgs",
								classField: "required",
							},
						},
					],
				},
			},

			// =========================
			// ASN DITUGASKAN (DROPDOWN)
			// =========================
			{
				tag: "fieldDropdown",
				prop: {
					label: "Nama ASN ditugaskan",
					name: "asn",
					source: "asn",
					search: true,
					clearable: true,
				},
			},

			{
				tag: "fieldCheckbox",
				prop: {
					label: "Lampiran SK bentuk tabel",
					name: "bentuk_lampiran",
				},
			},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: 'rows="2"',
				},
			},

			{
				tag: "fieldCheckbox",
				prop: {
					label: "Non Aktif",
					name: "disable",
				},
			},
		],

		/* ======================================================
       2️⃣ SURAT INTERNAL
    ====================================================== */
		surat_internal: [
			{
				tag: "field",
				prop: { label: "Nomor", name: "nomor", atribut: "readonly" },
			},

			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},

			{
				tag: "field",
				prop: { label: "Kepada", name: "kepada", classField: "required" },
			},

			{ tag: "field", prop: { label: "Dari", name: "dari" } },

			{
				tag: "fieldTextarea",
				prop: { label: "Perihal", name: "perihal", atribut: 'rows="2"' },
			},

			{ tag: "divider", prop: { label: "ISI NASKAH" } },

			{
				tag: "fieldTextarea",
				prop: { label: "", name: "isi", atribut: 'rows="8"' },
			},
		],

		/* ======================================================
       3️⃣ SURAT BEBAS (FREE EDITOR)
    ====================================================== */
		surat_bebas: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },

			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},

			{ tag: "field", prop: { label: "Tujuan", name: "tujuan" } },

			{
				tag: "fieldTextarea",
				prop: { label: "Perihal", name: "perihal", atribut: 'rows="2"' },
			},

			{ tag: "divider", prop: { label: "ISI SURAT" } },

			{
				tag: "fieldTextarea",
				prop: { label: "", name: "isi", atribut: 'rows="10"' },
			},
		],

		/* ======================================================
       4️⃣ AKTA / PERJANJIAN / BERITA ACARA
    ====================================================== */
		akta: [
			{ tag: "field", prop: { label: "Nomor Dokumen", name: "nomor" } },

			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Judul / Tentang",
					name: "tentang",
					atribut: 'rows="2"',
				},
			},

			{ tag: "divider", prop: { label: "PIHAK TERLIBAT" } },

			{
				tag: "fieldTextarea",
				prop: {
					label: "Pihak Pertama",
					name: "pihak_pertama",
					atribut: 'rows="2"',
				},
			},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Pihak Kedua",
					name: "pihak_kedua",
					atribut: 'rows="2"',
				},
			},

			{ tag: "divider", prop: { label: "ISI PERJANJIAN" } },

			{
				tag: "fieldTextarea",
				prop: { label: "", name: "isi", atribut: 'rows="8"' },
			},
		],

		/* ======================================================
       5️⃣ SURAT KETERANGAN / SERTIFIKAT
    ====================================================== */
		surat_keterangan: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },

			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},

			{
				tag: "field",
				prop: {
					label: "Nama Penerima",
					name: "nama_penerima",
					classField: "required",
				},
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: 'rows="4"' },
			},
		],

		/* ======================================================
       6️⃣ LAPORAN / NOTULA
    ====================================================== */
		laporan: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },

			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Judul Laporan", name: "judul", atribut: 'rows="2"' },
			},

			{ tag: "divider", prop: { label: "ISI LAPORAN" } },

			{
				tag: "fieldTextarea",
				prop: { label: "", name: "isi", atribut: 'rows="10"' },
			},
		],

		/* ======================================================
       7️⃣ TELAAH STAF
    ====================================================== */
		telaah_staf: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },

			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Permasalahan",
					name: "permasalahan",
					atribut: 'rows="3"',
				},
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Analisis", name: "analisis", atribut: 'rows="5"' },
			},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Rekomendasi",
					name: "rekomendasi",
					atribut: 'rows="3"',
				},
			},
		],
	},
};

/* =========================================================
   FORM CONTAINER MANAGER
   Bisa tampil di:
   - Flyout
   - Modal
========================================================= */

class FormContainerManager {
	// @note FormContainerManager
	constructor() {
		this.$flyout = $("#mainContext .sidebarkanan");
		this.$modal = $("#mainModal");

		this.activeContainer = "flyout";

		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		// =============================
		// 🔥 PLUGIN SYSTEM (TAMBAHAN)
		// =============================
		this.plugins = {};
		this.beforeSavePlugins = {};

		this.initContainers();
	}

	// =============================
	// 🔥 REGISTER PLUGIN
	// =============================
	registerPlugin(key, callback) {
		this.plugins[key] = callback;
	}

	registerBeforeSave(key, callback) {
		this.beforeSavePlugins[key] = callback;
	}
	collectDocumentStructure() {
		let data = {};

		this.getActiveForm()
			.find("table[name]")
			.each(function () {
				let section = $(this).attr("name");

				data[section] = [];

				$(this)
					.find("tbody tr")
					.each(function () {
						let text = $(this).find(".doc-text").val();

						let type =
							$(this).find(".doc-type").dropdown("get value") || "paragraph";

						if (!text) return;

						data[section].push({
							type: type,
							text: text,
						});
					});
			});

		return data;
	}
	runPlugins() {
		const exactKey = `${AppState.module}.${AppState.tbl}`;
		const wildcardKey = `${AppState.module}.*`;

		const payload = {
			container: this.getActiveForm(),
			state: AppState,
			manager: this,
		};

		if (this.plugins[exactKey]) {
			this.plugins[exactKey](payload);
		}

		if (this.plugins[wildcardKey]) {
			this.plugins[wildcardKey](payload);
		}
	}

	runBeforeSave(formData) {
		const exactKey = `${AppState.jenis}.${AppState.tbl}`;
		const wildcardKey = `${AppState.jenis}.*`;

		if (this.beforeSavePlugins[exactKey]) {
			this.beforeSavePlugins[exactKey](formData, AppState);
		}

		if (this.beforeSavePlugins[wildcardKey]) {
			this.beforeSavePlugins[wildcardKey](formData, AppState);
		}
	}

	/* --------------------------------------------- */
	initContainers() {
		if (this.$flyout.length && !this.$flyout.data("module-sidebar")) {
			this.$flyout.sidebar({
				context: $("#mainContext"),
				transition: "push",
				closable: false,
				scrollLock: true,
			});
		}

		if (this.$modal.length && !this.$modal.data("module-modal")) {
			this.$modal.modal({ closable: false });
		}

		this.bindEvents();
	}

	/* --------------------------------------------- */
	bindEvents() {
		const self = this;

		$(document).on("click", '[data-ui="open-form"]', function (e) {
			e.preventDefault();
			self.open($(this));
		});

		$(document).on("click", ".btnSubmit", function () {
			let $form = self.getActiveForm();
			$form.submit();
		});

		$(document).on("click", ".btnFlyoutClose", function () {
			self.$flyout.sidebar("hide");
		});

		$(document).on("click", ".sidebarkanan .close.icon", function () {
			self.$flyout.sidebar("hide");
		});
	}

	getActiveForm() {
		return this.activeContainer === "modal"
			? $("#form_modal")
			: $("#form_flyout");
	}

	// =============================
	// LOAD DROPDOWN (TIDAK DIUBAH)
	// =============================
	loadDropdowns(containerSelector) {
		const self = this;

		// =====================================================
		// 🔥 LOOP SEMUA DROPDOWN YANG PUNYA data-source
		// -----------------------------------------------------
		// - Kalau tidak punya source → skip (static dropdown aman)
		// - Kalau punya source → fetch
		// =====================================================

		$(containerSelector)
			.find(".ui.dropdown[data-source]")
			.each(function () {
				let $dropdown = $(this);
				let source = $dropdown.data("source");

				// 🔥 Skip jika tidak ada source (dropdown manual)
				if (!source) return;

				// 🔥 Pastikan source memang terdeteksi dari UIConfig
				if (!AppState.serverSources.includes(source)) return;

				// =====================================================
				// 🔥 FETCH DROPDOWN DATA
				// =====================================================
				self.fetchDropdown($dropdown, source);

				// =====================================================
				// 🔥 SUPPORT PARENT DEPENDENCY (CASCADING)
				// =====================================================
				let parentName = $dropdown.data("parent");

				if (parentName) {
					$(containerSelector).on(
						"change",
						`[name="${parentName}"]`,
						function () {
							let parentValue = $(this).val();
							self.fetchDropdown($dropdown, source, parentValue);
						},
					);
				}
			});
	}

	fetchDropdown($dropdown, source, parentValue = null) {
		let payload = {
			module: AppState.module,
			action: "dropdown",
			source: source,
		};

		if (parentValue) {
			payload.parent_value = parentValue;
		}

		this.ajax.request({
			data: payload,
			success: function (res) {
				if (!res.success) return;

				let menu = $dropdown.find(".menu");
				menu.empty();

				res.data.forEach((item) => {
					menu.append(`
						<div class="item" data-value="${item.id}">
							${item.uraian}
						</div>
					`);
				});

				$dropdown.dropdown("refresh");
			},
		});
	}

	/* --------------------------------------------- */
	open($btn) {
		// 🔥 FORCE RENSTRA MODULE
		if (window.location.pathname.replace(/^\/+/g, "") === "renstra") {
			AppState.module = "renstra";
		}
		const jenisMode = $btn.data("jns");
		const tbl = $btn.attr("data-tbl") || AppState.tbl;

		// ==============================
		// 🔥 VALIDASI WAJIB
		// ==============================
		if (!tbl) {
			console.error("TBL tidak ditemukan");
			return;
		}

		// ==============================
		// 🔥 SET JENIS
		// ==============================
		// ==================================
		// 🔥 PASTIKAN MODULE TIDAK KACAU
		// ==================================
		let moduleFromBtn = $btn.attr("data-module");

		// Jika tombol punya data-module → pakai itu
		if (moduleFromBtn) {
			AppState.module = moduleFromBtn;
		}
		// Jika tidak, dan belum ada module → ambil dari URL
		else if (!AppState.module) {
			AppState.module = window.location.pathname.replace(/^\/+/g, "");
		}

		// 🔥 JANGAN pernah override module jika sudah ada

		AppState.tbl = tbl;
		AppState.action = jenisMode;

		// ==============================
		// 🔥 DEBUG
		// ==============================
		console.log("OPEN FORM:");
		console.log("MODULE:", AppState.module);
		console.log("TBL:", AppState.tbl);

		// ==============================
		// 🔥 BUILD CONFIG
		// ==============================
		let config = this.buildConfig(jenisMode, tbl);
		if (!config.elements.length && config.type !== "document") {
			ToastEngine.show({
				success: false,
				message: "Form belum dikonfigurasi. Hubungi administrator.",
			});
			console.warn("UIConfig missing:", AppState.module, tbl);
			return;
		}

		// ==============================
		// 🔥 DETECT DROPDOWN
		// ==============================
		AppState.serverSources = config.elements
			.filter((el) => el.tag === "fieldDropdown" && el.prop?.source)
			.map((el) => el.prop.source);

		AppState.serverSources = [...new Set(AppState.serverSources)];

		const container = $btn.data("container") || "flyout";
		this.activeContainer = container;

		this.render(config, container);
		this.show(container);
		// ==============================
		// 🔥 AUTO LOAD DATA SAAT EDIT
		// ==============================
		if (jenisMode === "edit") {
			const idRow = $btn.data("id");
			if (idRow) {
				this.loadData(idRow, container);
			}
		}
	}

	/* --------------------------------------------- */
	render(config, container) {
		$("#form_modal").empty();
		$("#form_flyout").empty();

		let target;

		if (container === "modal") {
			target = "#form_modal";
			$("#icon_modal").attr("class", config.icon);
			$("#content_modal").text(config.headerTitle || config.header);
		} else {
			target = "#form_flyout";
			$("#icon_flyout").attr("class", config.icon);
			$("#content_flyout").text(config.headerTitle || config.header);
		}

		// ===================================================
		// 🔥 KHUSUS DOCUMENT ENGINE
		// ===================================================
		if (config.type === "document") {
			FormEngine.render(target, config.elements);

			let builder = new DocumentBuilder($(target), AppState.tbl);
			builder.render();

			// 🔥 JANGAN VALIDASI DENGAN UIConfig RULES
			// this.initValidation(target);

			this.loadDropdowns(target);
			this.runPlugins();
			return;
		}

		// ===================================================
		// DEFAULT FORM (CRUD NORMAL)
		// ===================================================
		FormEngine.render(target, config.elements);
		this.initValidation(target);
		this.loadDropdowns(target);

		if (AppState.action === "edit") {
			$(target).prepend(`<input type="hidden" name="id">`);
		}

		this.runPlugins();
	}

	initValidation(target) {
		const $form = $(target);
		if (!$form.length) return;

		let rules = {};

		let elements =
			UIConfig[AppState.module]?.[AppState.tbl] ||
			UIConfig[AppState.page]?.[AppState.tbl] ||
			[];

		// ==============================
		// 🔥 FLATTEN ELEMENTS (AMBIL CHILDREN)
		// ==============================
		function flatten(arr, result = []) {
			arr.forEach((el) => {
				if (el.tag === "fields" || el.tag === "inlineFields") {
					if (el.prop?.children) {
						flatten(el.prop.children, result);
					}
				} else {
					result.push(el);
				}
			});
			return result;
		}

		let flatElements = flatten(elements);

		flatElements.forEach((el) => {

	if (!el.prop || !el.prop.name) return;

	// 🔥 SKIP VALIDATION JIKA validate:false
	if (el.prop.validate === false) return;

	let name = el.prop.name;

	if (!$form.find(`[name="${name}"]`).length) return;

	let fieldRules = [];

	if (el.prop.classField?.includes("required")) {
		fieldRules.push({
			type: "empty",
			prompt: (el.prop.label || name) + " wajib diisi",
		});
	}

	if (name === "email") {
		fieldRules.push({
			type: "email",
			prompt: "Format email tidak valid",
		});
	}

	if (el.prop.atribut?.includes('type="number"')) {
		fieldRules.push({
			type: "number",
			prompt: (el.prop.label || name) + " harus berupa angka",
		});
	}

	if (fieldRules.length) {
		rules[name] = {
			identifier: name,
			rules: fieldRules,
		};
	}
});

		$form.form("destroy");
		$form.removeData("module-form");

		$form.form({
			inline: true,
			on: "blur",
			fields: rules,
			onSuccess: (event) => {
				event.preventDefault();
				this.save();
			},
		});
	}

	show(container) {
		container === "modal"
			? this.$modal.modal("show")
			: this.$flyout.sidebar("show");
	}

	hide(container) {
		container === "modal"
			? this.$modal.modal("hide")
			: this.$flyout.sidebar("hide");
	}

	loadData(idRow, container) {
		this.ajax.request({
			data: {
				module: AppState.module,
				action: "edit",
				tbl: AppState.tbl,
				id_row: idRow,
			},
			success: (res) => {
				if (!res.success || !res.data) return;

				let $formTarget =
					container === "modal" ? $("#form_modal") : $("#form_flyout");

				Object.keys(res.data).forEach((key) => {
					let $field = $formTarget.find(`[name="${key}"]`);
					if (!$field.length) return;

					if ($field.closest(".ui.dropdown").length) {
						$field
							.closest(".ui.dropdown")
							.dropdown("set selected", res.data[key]);
					} else if ($field.attr("type") === "checkbox") {
						res.data[key] == 1
							? $field.closest(".ui.checkbox").checkbox("check")
							: $field.closest(".ui.checkbox").checkbox("uncheck");
					} else if ($field.closest(".ui.calendar").length) {
						let value = res.data[key];
						if (!value) return;

						let $calendar = $field.closest(".ui.calendar");
						let type = $field.data("type") || "date";

						let dateObj;

						if (type === "year") {
							// ambil 4 digit tahun
							let year = String(value).substring(0, 4);
							dateObj = new Date(parseInt(year), 0, 1);
						} else {
							// parse YYYY-MM-DD (format SQL)
							if (typeof value === "string" && value.includes("-")) {
								let parts = value.split("-");
								dateObj = new Date(parts[0], parts[1] - 1, parts[2] || 1);
							} else {
								dateObj = new Date(value);
							}
						}

						if (!isNaN(dateObj.getTime())) {
							$calendar.calendar("set date", dateObj);
						}
					} else if ($field.attr("type") !== "file") {
						$field.val(res.data[key]);
					}
				});
				// ==========================================
				// 🔥 LOAD STRUKTUR JSON UNTUK tata_naskah
				// ==========================================
				if (AppState.module === "tata_naskah" && res.data.struktur_json) {
					let struktur = {};

					try {
						struktur = JSON.parse(res.data.struktur_json);
					} catch (e) {
						console.error("JSON struktur rusak");
					}

					Object.keys(struktur).forEach((section) => {
						struktur[section].forEach((item) => {
							let text = item.text || "";
							let type = item.type || "paragraph";

							let builder = new DocumentBuilder($formTarget, AppState.tbl);

							let row = builder.buildRow(section, text, type);

							$formTarget.find(`table[name="${section}"] tbody`).append(row);
						});
					});

					$formTarget.find(".ui.dropdown").dropdown();
				}
			},
		});
	}

	buildConfig(jenis, tbl) {
		// ===============================
		// 🔥 MODE IMPORT XLSX
		// ===============================
		if (jenis === "import_xlsx") {
			return {
				icon: "upload icon",
				header: "Import Excel",
				elements: [
					{
						tag: "fieldFile",
						prop: {
							label: "Upload File Excel",
							name: "file_import",
							accept: ".xlsx,.xls",
							classField: "required",
						},
					},
				],
			};
		}

		// ===============================
		// DEFAULT MODE (add/edit/detail)
		// ===============================
		let config = {
			icon:
				jenis === "add"
					? "plus icon"
					: jenis === "edit"
						? "edit icon"
						: "eye icon",

			header:
				jenis === "add"
					? "Tambah Data"
					: jenis === "edit"
						? "Edit Data"
						: "Detail Data",

			elements:
				UIConfig[AppState.module]?.[tbl] ||
				UIConfig[AppState.page]?.[tbl] ||
				[],
		};
		// 🔥 DETECT DOCUMENT MODULE
		if (AppState.module === "tata_naskah") {
			return {
				type: "document",
				icon: "file alternate outline icon",
				headerTitle: "Dokumen Naskah",
				elements: UIConfig[AppState.module]?.[tbl] || [],
			};
		}
		return config;
	}

	/* --------------------------------------------- */
	save() {
		// ===============================
		// 🔥 MODE IMPORT XLSX
		// ===============================
		if (AppState.action === "import_xlsx") {
			let $formTarget = this.getActiveForm();
			let formElement = $formTarget[0];

			let formData = new FormData();

			let fileInput = $formTarget.find('[name="file_import"]')[0];

			if (!fileInput.files.length) {
				alert("File belum dipilih");
				return;
			}

			// 🔥 SINKRON DENGAN BACKEND
			formData.append("file", fileInput.files[0]);
			formData.append("tabel", AppState.tbl);

			this.ajax.request({
				url: AppConfig.apiUrl + "dynamic/import",
				method: "POST",
				data: formData,
				processData: false,
				contentType: false,
				success: (res) => {
					if (res.success) {
						this.hide(this.activeContainer);
						new TableManager().fetch();
					} else {
						alert(res.error || "Import gagal");
					}
				},
			});

			return; // 🔥 hentikan save normal
		}

		// ===============================
		// 🔥 SAVE NORMAL (ADD / EDIT)
		// ===============================
		let $formTarget = this.getActiveForm();
		let formElement = $formTarget[0];

		if (!formElement) return;

		let formData = new FormData(formElement);

		// =====================================
		// 🔥 KHUSUS tata_naskah → KIRIM STRUKTUR
		// =====================================
		if (AppState.module === "tata_naskah") {
			let struktur = this.collectDocumentStructure();
			formData.append("struktur_json", JSON.stringify(struktur));
		}

		formData.append("module", AppState.module);
		formData.append("action", AppState.action);
		formData.append("tbl", AppState.tbl);

		this.ajax.request({
			url: AppConfig.apiUrl + "dynamic",
			method: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: (res) => {
				if (res.success) {
					this.hide(this.activeContainer);
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
	//@note $ready
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
	const $sidebarUtama = $(".sidebarutama");

	/* ---------------------------------------------
	   Ambil parameter URL (?tbl=...)
	---------------------------------------------- */
	const params = new URLSearchParams(window.location.search);
	const tblFromUrl = params.get("tbl");

	/* ---------------------------------------------
	   Ambil path aktif
	---------------------------------------------- */
	const currentPath = window.location.pathname.replace(/^\/+/g, "");

	// 🔥 SINKRONKAN PAGE GLOBAL
	AppState.page = currentPath;

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
	   Inisialisasi Sidebar Fomantic
	---------------------------------------------- */
	$sidebarUtama.sidebar({
		context: $context,
		transition: "push",
	});

	/* ---------------------------------------------
	   Toggle Sidebar Button
	---------------------------------------------- */
	$("#toggleSidebar").on("click", function () {
		$sidebarUtama.sidebar("toggle");
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
					module: AppState.module,
					action: "delete",
					tbl: tbl,
					id_row: id,
				},
				function () {
					tableManager.fetch();
				},
			);
		});
	});
	// export excel
	$(document).on("click", 'button[data-action="export"]', function (e) {
		console.log("EXPORT CLICKED");

		e.preventDefault();

		const table = $(this).data("tbl");

		window.location.href = AppConfig.apiUrl + "export?tabel=" + table;
	});

	/* ---------------------------------------------
	   Load Profil jika halaman profil
	---------------------------------------------- */
	if (window.location.pathname === "/profil") {
		loadProfil();
	}
	// ==============================
	// RENSTRA TAB SWITCH (BERSIH)
	// ==============================

	$(document).on("click", "#renstraMenu .item", function (e) {
		e.preventDefault();
		let tbl = $(this).data("tbl");
		console.log("TAB CLICK:", tbl);
		// aktifkan menu
		$("#renstraMenu .item").removeClass("active");
		$(this).addClass("active");

		// ubah judul
		$("#judulTabel").text($(this).text().toUpperCase());

		// ubah tombol
		$("#btnTambah").attr("data-tbl", tbl);
		$("#btnImport").attr("data-tbl", tbl);
		$("#btnExport").attr("data-tbl", tbl);

		// =============================================
		// 🔥 Tidak perlu set data-server lagi
		// Dropdown akan auto detect saat open form
		// =============================================
		// 🔥 pakai engine global
		tableManager.load("renstra", tbl);
	});
	// ==============================
	// INIT DEFAULT RENSTRA TAB
	// ==============================

	let defaultTbl = $("#renstraMenu .item.active").data("tbl");

	// ==============================
	// INIT DEFAULT RENSTRA TAB (FINAL STABLE)
	// ==============================
	if (currentPath === "renstra" && !AppState.currentMenu) {
		let $activeTab = $("#renstraMenu .item.active");

		if (!$activeTab.length) {
			$activeTab = $("#renstraMenu .item").first();
			$activeTab.addClass("active");
		}

		let defaultTbl = $activeTab.data("tbl");

		console.log("INIT DEFAULT RENSTRA:", defaultTbl);

		// 🔥 SINKRONISASI STATE GLOBAL
		AppState.tbl = defaultTbl;
		AppState.currentMenu = defaultTbl;
		// 🔥 Update tombol
		$("#btnTambah").attr("data-tbl", defaultTbl);
		$("#btnImport").attr("data-tbl", defaultTbl);
		$("#btnExport").attr("data-tbl", defaultTbl);

		// 🔥 Load tabel pertama kali
		tableManager.load("renstra", defaultTbl);
	}
	// ==============================
	// INIT DEFAULT PENGATURAN
	// ==============================

	if (currentPath === "pengaturan" && !AppState.tbl) {
		tableManager.load("pengaturan", "periode_rpjmd");
	}
	//pengaturan menu
	$("#rpjmd_mulai_calendar").calendar({
		type: "year",
		endCalendar: $("#rpjmd_selesai_calendar"),
	});

	$("#rpjmd_selesai_calendar").calendar({
		type: "year",
		startCalendar: $("#rpjmd_mulai_calendar"),
	});
});
