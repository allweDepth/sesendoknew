/* =========================================================
		TABLE MANAGER
		---------------------------------------------------------
		TANGGUNG JAWAB:
		→ Mengelola lifecycle tabel dynamic
				- Load tabel berdasarkan module & tbl
				- Fetch data dari backend
				- Render header
				- Render body
				- Render pagination / footer
		---------------------------------------------------------
		CATATAN:
		- Tidak menyentuh Form
		- Tidak menyentuh Modal
		- Tidak menyentuh Router
		- Hanya fokus tabel
	========================================================= */

class TableManager {
	constructor() {
		/* ---------------------------------------------
				AJAX ENGINE
				Semua komunikasi backend lewat endpoint dynamic
			--------------------------------------------- */
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
	}

	/* =====================================================
			LOAD TABLE
			-----------------------------------------------------
			Dipanggil saat:
			- Klik menu
			- Tab switching
			- SPA refresh
			-----------------------------------------------------
			Tugas:
			- Set AppState
			- Reset halaman jika pindah tabel
			- Render header
			- Fetch data
		===================================================== */
	load(module, tbl) {
		// Reset halaman jika user pindah tabel
		if (AppState.currentMenu !== tbl) {
			AppState.halaman = 1;
		}

		// Sinkronisasi state global
		AppState.module = module;
		AppState.tbl = tbl;
		AppState.currentMenu = tbl;

		// Render ulang header
		this.renderHeader();

		// Ambil data
		this.fetch();
	}

	/* =====================================================
			FETCH DATA
			-----------------------------------------------------
			Kirim parameter:
			- module
			- action:list
			- tbl
			- halaman
			- rows
			- cari
		===================================================== */
	fetch() {
		/* ---------------------------------------------
				Sinkronisasi jumlah row dari dropdown
			--------------------------------------------- */
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
				/* -----------------------------------------
						VALIDASI RESPONSE
					----------------------------------------- */
				if (!res || res.success !== true) {
					console.warn(res?.message || "Response tidak valid");
					this.renderTable([]);
					this.renderFooterStatic();
					return;
				}

				let rows = Array.isArray(res.data) ? res.data : [];
				let meta = res.meta || {};

				// Simpan primary key global
				AppState.primaryKey = meta.primary_key || "id";

				// Render body
				this.renderTable(rows);

				// Tentukan pagination atau footer kosong
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
			HELPER: GET TBODY AKTIF
			-----------------------------------------------------
			Menghindari duplikasi fallback selector
		===================================================== */
	getActiveTbody() {
		let $tbody = $(`tbody[name="tabel_${AppState.tbl}"]`);

		// fallback universal
		if (!$tbody.length) {
			$tbody = $('tbody[name^="tabel_"]').first();
		}

		return $tbody;
	}

	/* =====================================================
			RENDER TABLE BODY
		===================================================== */
	renderTable(rows) {
		const $tbody = this.getActiveTbody();

		if (!$tbody.length) {
			ToastEngine.show({
				success: false,
				message: "Struktur tabel belum siap atau belum dikonfigurasi.",
			});
			return;
		}

		/* ---------------------------------------------
				Jika data kosong → tampilkan placeholder
			--------------------------------------------- */
		if (!rows.length) {
			$tbody.html(`
					<tr>
						<td colspan="100%" class="center aligned">
							<div class="ui placeholder segment">
								<div class="ui icon header">
									<i class="inbox icon"></i>
									Belum ada data tersedia
								</div>
								<p>Tambahkan data baru untuk mulai mengisi tabel ini.</p>
							</div>
						</td>
					</tr>
				`);

			return;
		}

		/* ---------------------------------------------
				Ambil field dari UIConfig
			--------------------------------------------- */
		let elements =
			UIConfig[AppState.module]?.[AppState.tbl] ||
			UIConfig[AppState.page]?.[AppState.tbl] ||
			[];

		let fields = elements.filter((e) => e.prop?.name && !e.prop.non_data);

		/* ---------------------------------------------
				Generate HTML row
			--------------------------------------------- */
		let html = "";

		rows.forEach((row) => {
			html += "<tr>";

			// Kolom data
			fields.forEach((field) => {
				let key = field.prop.name;
				html += `<td>${row[key] ?? ""}</td>`;
			});

			// Kolom aksi
			html += `
					<td class="collapsing">
						${this.buildActionButtons(row)}
					</td>
				`;

			html += "</tr>";
		});

		$tbody.html(html);
	}

	/* =====================================================
			RENDER FOOTER STATIC
			-----------------------------------------------------
			Digunakan saat:
			- Data kosong
			- Response gagal
		===================================================== */
	renderFooterStatic() {
		const $tbody = this.getActiveTbody();
		if (!$tbody.length) return;

		const $table = $tbody.closest("table");

		let headerCount = $table.find("thead tr:first th").length;
		if (!headerCount) return;

		let tfootHtml = "<tr>";

		for (let i = 0; i < headerCount; i++) {
			tfootHtml += "<td></td>";
		}

		tfootHtml += "</tr>";

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
			Tidak mengubah logic asli
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
			html += `
					<a class="item" data-page="1">
						<i class="angle double left chevron icon"></i>
					</a>
					<a class="item" data-page="${currentPage - 1}">
						<i class="angle left icon"></i>
					</a>
				`;
		}

		let start = Math.max(1, currentPage - 1);
		let end = Math.min(totalPage, currentPage + 1);

		for (let i = start; i <= end; i++) {
			let active = i === currentPage ? "active" : "";
			html += `<a class="item ${active}" data-page="${i}">${i}</a>`;
		}

		if (currentPage < totalPage) {
			html += `
					<a class="item" data-page="${currentPage + 1}">
						<i class="angle right icon"></i>
					</a>
					<a class="item" data-page="${totalPage}">
						<i class="angle double right chevron icon"></i>
					</a>
				`;
		}

		html += "</div>";

		$(target).html(html);

		// Samakan colspan footer
		const $tbody = this.getActiveTbody();
		if ($tbody.length) {
			const $table = $tbody.closest("table");
			let headerCount = $table.find("thead tr:first th").length;

			if (headerCount) {
				$table.find("tfoot td").attr("colspan", headerCount);
			}
		}
	}

	/* =====================================================
			BUILD ACTION BUTTONS
			-----------------------------------------------------
			Menentukan tombol berdasarkan:
			- Module
			- Table
			- Role
		===================================================== */
	buildActionButtons(row) {
		let module = AppState.module;
		let tbl = AppState.tbl;
		let role = AppState.role;

		let moduleConfig = ActionConfig[module]?.[tbl];
		let defaultConfig = ActionConfig.default;

		let buttons =
			moduleConfig?.roles?.[role] ??
			moduleConfig?.roles?.default ??
			defaultConfig?.roles?.[role] ??
			[];

		if (!buttons.length) return "";

		let html = `<div class="ui icon basic mini buttons">`;

		buttons.forEach((btn) => {
			/* -----------------------------------------
					String button (edit/delete)
				----------------------------------------- */
			if (typeof btn === "string") {
				switch (btn) {
					case "edit":
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
						break;

					case "delete":
						html += `
								<button class="ui red button"
									data-action="delete"
									data-tbl="${tbl}"
									data-id="${row[AppState.primaryKey]}">
									<i class="trash alternate outline icon"></i>
								</button>`;
						break;
				}
			}

			/* -----------------------------------------
					Custom button object
				----------------------------------------- */
			if (typeof btn === "object") {
				let extraAttr = "";

				if (btn.container) {
					extraAttr += ` data-container="${btn.container}"`;
				}

				if (btn.ui) {
					extraAttr += ` data-ui="${btn.ui}"`;
				}

				html += `
						<button class="ui ${btn.color || "grey"} button"
							data-action="${btn.action}"
							data-module="${module}"
							data-tbl="${tbl}"
							data-id="${row[AppState.primaryKey]}"
							${extraAttr}>
							<i class="${btn.icon || "circle"} icon"></i>
						</button>`;
			}
		});

		html += "</div>";

		return html;
	}

	/* =====================================================
			RENDER HEADER
			-----------------------------------------------------
			Membuat <thead> berdasarkan UIConfig
		===================================================== */
	renderHeader() {
		const $tbody = this.getActiveTbody();
		if (!$tbody.length) return;

		const $table = $tbody.closest("table");

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
