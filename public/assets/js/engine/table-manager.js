/* =========================================================
   TABLE MANAGER (HYBRID STABLE VERSION)
   ---------------------------------------------------------
   Engine utama untuk:
   ✔ Load data dari backend
   ✔ Render header & body tabel
   ✔ Render pagination (smart)
   ✔ Konfirmasi delete
   ✔ Format value (currency, status badge)

   Versi ini:
   - Stabil seperti versi lama (berbasis state)
   - Modular seperti versi baru
   - Tidak bergantung pada selector rapuh
========================================================= */

class TableManager {
	/* =====================================================
	   CONSTRUCTOR
	   -----------------------------------------------------
	   Inisialisasi:
	   - State module & tabel aktif
	   - AJAX engine
	   - Selector fallback otomatis
	===================================================== */
	constructor(config = {}) {
		// State global aktif (wajib ada)
		this.state = config.state;

		// Engine AJAX → semua CRUD ke /dynamic
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		// ==================================================
		// SELECTOR FALLBACK (ANTI ERROR)
		// Jika tidak dikirim dari luar, gunakan pola lama
		// ==================================================
		this.tbody = config.tbody || `tbody[name="tabel_${this.state.tbl}"]`;

		this.pagination =
			config.pagination || `div[name="pagination_${this.state.module}"]`;

		// ==================================================
		// STATE PAGINATION
		// ==================================================
		this.currentPage = 1;
		this.limit = config.limit || 10;
		this.totalRows = 0;
		this.totalPages = 0;

		// Sorting
		this.sortBy = null;
		this.sortDir = "asc";

		// Search
		this.searchQuery = "";

		// Data hasil response
		this.data = [];
	}

	/* =====================================================
	   INIT TABLE
	   -----------------------------------------------------
	   - Bind event
	   - Ambil data pertama kali
	===================================================== */
	init() {
		this.bindEvents();
		this.fetchData();
	}

	/* =====================================================
	   FETCH DATA
	   -----------------------------------------------------
	   Mengambil data dari backend
	   Parameter:
	   - module
	   - action
	   - tbl
	   - page
	   - limit
	===================================================== */
	fetchData() {
		// 🔥 Sinkronisasi limit dari navbar
		this.syncLimitFromNavbar();

		this.renderLoader();

		this.ajax.request({
			method: "POST",
			data: {
				module: this.state.module,
				action: "list",
				tbl: this.state.tbl,

				// 🔥 FIX SESUAI BACKEND
				halaman: this.currentPage,
				rows: this.limit,
				cari: this.searchQuery,

				sort_by: this.sortBy,
				sort_dir: this.sortDir,
			},
			success: (res) => {
				this.data = res.data || [];

				this.handlePagination(res.meta || {});

				this.renderHeader();
				this.renderBody();
				this.renderPagination();
			},
		});
	}

	/* =====================================================
	   HANDLE META PAGINATION
	===================================================== */
	handlePagination(meta) {
		this.totalRows = meta.total || 0;
		this.currentPage = meta.page || 1;
		this.limit = meta.limit || this.limit;

		this.totalPages = Math.ceil(this.totalRows / this.limit);
	}

	/* =====================================================
	   RENDER LOADER
	===================================================== */
	renderLoader() {
		$(this.tbody).html(`
			<tr>
				<td colspan="100%">
					<div class="ui active inline loader"></div>
				</td>
			</tr>
		`);
	}

	/* =====================================================
	   AMBIL KOLOM DARI UI CONFIG
	   -----------------------------------------------------
	   - Ambil dari UIConfig[module][tbl]
	   - Auto hide id
	   - Skip prop.table === false
	===================================================== */
	getColumnsFromConfig() {
    if (!window.UIConfig) return [];

    const module = this.state.module;
    const tbl = this.state.tbl;

    let config =
    window.UIConfig?.[module]?.[tbl] ||
    window.UIConfig?.[tbl];
    if (!config) return [];

    // 🔥 SUPPORT DUA STRUKTUR
    const columnsSource = Array.isArray(config)
        ? config
        : Array.isArray(config.columns)
            ? config.columns
            : [];

    return columnsSource
        .filter((item) => {
            if (!item.prop?.name) return false;

            if (["divider", "header", "fieldHidden", "fieldCustom"].includes(item.tag))
                return false;

            if (item.prop.visible === false)
                return false;

            return true;
        })
        .map((item) => ({
            key: item.prop.name,
            label: item.prop.label || item.prop.name,
            format: item.prop.format || null,
        }));
}

	/* =====================================================
	   RENDER HEADER
	===================================================== */
	renderHeader() {
		const columns = this.getColumnsFromConfig();
		if (!columns) return;

		let html = "<tr>";

		columns.forEach((col) => {
			html += `<th>${col.label}</th>`;
		});

		html += "<th class='collapsing'>Aksi</th></tr>";

		$(this.tbody).closest("table").find("thead").html(html);
	}

	/* =====================================================
	   FORMAT VALUE
	   -----------------------------------------------------
	   Support:
	   - currency
	   - status badge
	===================================================== */
	formatValue(value, format) {
		if (value == null) return "";

		// Currency
		if (format === "currency") {
			return new Intl.NumberFormat("id-ID", {
				style: "currency",
				currency: "IDR",
				minimumFractionDigits: 0,
			}).format(value);
		}

		// Status badge
		if (format === "status") {
			if (value == 1 || value === "aktif")
				return `<div class="ui green basic label">Aktif</div>`;

			if (value == 0 || value === "nonaktif")
				return `<div class="ui red basic label">Non Aktif</div>`;

			return `<div class="ui grey basic label">${value}</div>`;
		}

		return value;
	}

	/* =====================================================
	   RENDER BODY
	===================================================== */
	renderBody() {
		if (!this.data.length) {
			$(this.tbody).html(`
				<tr>
					<td colspan="100%" class="center aligned">
						<div class="ui info icon message">
							<i class="info circle icon"></i>
							<div class="content">
								<div class="header">Tidak Ada Data</div>
								<p>Belum terdapat data pada modul ini.</p>
							</div>
						</div>
					</td>
				</tr>
			`);

			return;
		}

		const columns = this.getColumnsFromConfig();
		let html = "";

		this.data.forEach((row) => {
			html += `<tr data-id="${row.id}">`;

			columns.forEach((col) => {
				let value = row.hasOwnProperty(col.key) ? row[col.key] : "";
				value = this.formatValue(value, col.format);

				html += `<td>${value ?? ""}</td>`;
			});

			// Tombol aksi
			html += `
				<td class="collapsing">
					<div class="ui mini basic icon buttons">
						<button class="ui button" data-action="edit">
							<i class="edit icon"></i>
						</button>
						<button class="ui red button" data-action="delete">
							<i class="trash icon"></i>
						</button>
					</div>
				</td>
			`;

			html += "</tr>";
		});

		$(this.tbody).html(html);
	}

	/* =====================================================
	   RENDER PAGINATION (SMART)
	   -----------------------------------------------------
	   - Prev
	   - Smart range
	   - Next
	===================================================== */
	renderPagination() {
		if (this.totalPages <= 1) {
			$(this.pagination).html("");
			return;
		}

		const current = this.currentPage;
		const total = this.totalPages;
		const range = 2;

		let html = `<div class="ui pagination menu">`;

		// PREV
		html += `
			<a class="icon item ${current === 1 ? "disabled" : ""}"
			   data-page="${current - 1}">
				<i class="angle left icon"></i>
			</a>
		`;

		const createItem = (page, active = false) => `
			<a class="item ${active ? "active" : ""}"
			   data-page="${page}">
			   ${page}
			</a>
		`;

		// First page
		html += createItem(1, current === 1);

		// Dots before
		if (current - range > 2) html += `<div class="disabled item">...</div>`;

		// Middle range
		for (
			let i = Math.max(2, current - range);
			i <= Math.min(total - 1, current + range);
			i++
		) {
			html += createItem(i, i === current);
		}

		// Dots after
		if (current + range < total - 1)
			html += `<div class="disabled item">...</div>`;

		// Last page
		if (total > 1) html += createItem(total, current === total);

		// NEXT
		html += `
			<a class="icon item ${current === total ? "disabled" : ""}"
			   data-page="${current + 1}">
				<i class="angle right icon"></i>
			</a>
		`;

		html += `</div>`;

		$(this.pagination).html(html);
	}

	/* =====================================================
	   EVENT BINDING
	===================================================== */
	bindEvents() {
		// ==========================================
		// PAGINATION CLICK
		// ==========================================
		$(document).off("click.tablePagination");

		$(document).on(
			"click.tablePagination",
			`${this.pagination} [data-page]`,
			(e) => {
				e.preventDefault();

				const $item = $(e.currentTarget);

				if ($item.hasClass("disabled")) return;

				const page = parseInt($item.data("page"));

				if (!page || page === this.currentPage) return;

				this.changePage(page);
			},
		);

		// ==========================================
		// EDIT / DELETE
		// ==========================================
		$(document).off("click.tableAction");

		$(document).on("click.tableAction", `${this.tbody} [data-action]`, (e) => {
			const action = $(e.currentTarget).data("action");
			const id = $(e.currentTarget).closest("tr").data("id");

			this.handleAction(action, id);
		});

		// ==========================================
		// SYNC DROPDOWN JUMLAH ROW (#countRow)
		// ==========================================
		if ($("#countRow").length) {
			// Pastikan tidak double binding
			$(document).off("change.countRow");

			$(document).on("change.countRow", "#countRow input", () => {
				// Ambil value dari dropdown Fomantic
				let value = $("#countRow").dropdown("get value");

				// Reset halaman ke 1
				this.currentPage = 1;

				// Jika ALL → ambil semua
				if (value === "all") {
					this.limit = 200;
				} else {
					this.limit = parseInt(value) || 5;
				}

				// Reload data
				this.fetchData();
			});
		}
		// ==========================================
// SEARCH DARI NAVBAR (#cari_data)
// ==========================================
$(document).off("input.tableSearch");

$(document).on("keypress.tableSearch", "#cari_data", (e) => {
    if (e.which === 13) {
        this.searchQuery = $(e.currentTarget).val();
        this.currentPage = 1;
        this.fetchData();
    }
});
	}

	/* =====================================================
	   CHANGE PAGE
	===================================================== */
	changePage(page) {
		if (page < 1 || page > this.totalPages) return;

		this.currentPage = page;
		this.fetchData();
	}

	/* =====================================================
	   HANDLE ACTION
	===================================================== */
	handleAction(action, id) {
		if (action === "edit") {
			$(document).trigger("table:edit", id);
		}

		if (action === "delete") {
			this.deleteRow(id);
		}
	}

	/* =====================================================
	   DELETE ROW (WITH CONFIRMATION)
	===================================================== */
	deleteRow(id) {
		const rowData = this.data.find((r) => r.id == id);
		const label = rowData?.nama || "data ini";

		DialogEngine.show({
			title: "Konfirmasi Hapus",
			message: `Yakin ingin menghapus <b>${label}</b>?`,
			icon: "trash red",
			approveText: "Ya, Hapus",
			cancelText: "Batal",
			onApprove: () => {
				return new Promise((resolve, reject) => {
					this.ajax.request({
						method: "POST",
						data: {
							module: this.state.module,
							action: "delete",
							tbl: this.state.tbl,
							id_row: id,
						},
						success: () => {
							this.fetchData();
							resolve();
						},
						error: reject,
					});
				});
			},
		});
	}

	/* =====================================================
	   DESTROY
	   -----------------------------------------------------
	   Membersihkan event dan DOM
	===================================================== */
	destroy() {
		$(document).off("click", `[data-page]`);
		$(document).off("click", `${this.tbody} [data-action]`);

		$(this.tbody).empty();
		$(this.pagination).empty();
	}
	/* =====================================================
   SYNC LIMIT DARI NAVBAR DROPDOWN
   -----------------------------------------------------
   Ambil value dari #countRow jika ada
===================================================== */
	syncLimitFromNavbar() {
		if ($("#countRow").length) {
			let value = $("#countRow").dropdown("get value");

			if (value === "all") {
				this.limit = 999999; // atau angka besar
			} else {
				this.limit = parseInt(value) || this.limit;
			}
		}
	}
}
