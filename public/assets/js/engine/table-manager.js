class TableManager {
	// ==========================================================
	// CONSTRUCTOR
	// ==========================================================
	constructor(config = {}) {
		// State global (module & tabel aktif)
		this.state = config.state;

		// Instance AjaxEngine → semua request CRUD lewat sini
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		// Selector tbody tempat data ditampilkan
		this.tbody = config.tbody;

		// Selector pagination container
		this.pagination = config.pagination;

		// State pagination
		this.currentPage = 1;
		this.limit = config.limit || 10;
		this.totalRows = 0;
		this.totalPages = 0;

		// Sorting
		this.sortBy = null;
		this.sortDir = "asc";

		// Search
		this.searchQuery = "";

		// Data hasil response server
		this.data = [];
	}

	// ==========================================================
	// INIT TABLE
	// ==========================================================
	init() {
		this.bindEvents();
		this.fetchData();
	}

	// ==========================================================
	// FETCH DATA DARI SERVER
	// ==========================================================
	fetchData() {
		this.renderLoader();

		this.ajax.request({
			method: "POST",

			data: {
				module: this.state.module,
				action: "list",
				tbl: this.state.tbl,
				page: this.currentPage,
				limit: this.limit,
				search: this.searchQuery,
				sort_by: this.sortBy,
				sort_dir: this.sortDir,
			},

			success: (res) => {
				// Simpan data
				this.data = res.data || [];

				// Proses pagination dari backend (meta)
				this.handlePagination(res.meta || {});

				// Render ulang header dan body
				this.renderHeader();
				this.renderBody();
				this.renderPagination();
			},
		});
	}

	// ==========================================================
	// HANDLE PAGINATION META
	// ==========================================================
	handlePagination(meta) {
		this.totalRows = meta.total || 0;
		this.currentPage = meta.page || 1;
		this.limit = meta.limit || this.limit;

		this.totalPages = Math.ceil(this.totalRows / this.limit);
	}

	// ==========================================================
	// RENDER LOADER
	// ==========================================================
	renderLoader() {
		$(this.tbody).html(`
			<tr>
				<td colspan="100%">
					<div class="ui active inline loader"></div>
				</td>
			</tr>
		`);
	}

	// ==========================================================
	// AMBIL KOLOM DARI UI CONFIG
	// ==========================================================
	getColumnsFromConfig() {
		// Pastikan UIConfig ada
		if (!window.UIConfig) return null;

		const module = this.state.module;
		const tbl = this.state.tbl;

		const config = window.UIConfig[module]?.[tbl];
		if (!config) return null;

		return config
			.filter((item) => {
				// Harus punya name
				if (!item.prop?.name) return false;

				// 🔥 Auto-hide id
				if (item.prop.name === "id") return false;

				// Jika explicitly disembunyikan
				if (item.prop.table === false) return false;

				return true;
			})
			.map((item) => ({
				key: item.prop.name,
				label: item.prop.label || item.prop.name,
				format: item.prop.format || null,
			}));
	}

	// ==========================================================
	// RENDER HEADER TABEL
	// ==========================================================
	renderHeader() {
		const columns = this.getColumnsFromConfig();
		if (!columns) return;

		let html = "<tr>";

		columns.forEach((col) => {
			html += `<th>${col.label}</th>`;
		});

		html += "<th class='collapsing'>Aksi</th>";
		html += "</tr>";

		// Cari thead terdekat
		$(this.tbody).closest("table").find("thead").html(html);
	}

	// ==========================================================
	// FORMAT VALUE BERDASARKAN TIPE
	// ==========================================================
	formatValue(value, format) {
		if (value == null) return "";

		// FORMAT CURRENCY
		if (format === "currency") {
			return new Intl.NumberFormat("id-ID", {
				style: "currency",
				currency: "IDR",
				minimumFractionDigits: 0,
			}).format(value);
		}

		// FORMAT STATUS BADGE
		if (format === "status") {
			if (value == 1 || value === "aktif")
				return `<div class="ui green basic label">Aktif</div>`;

			if (value == 0 || value === "nonaktif")
				return `<div class="ui red basic label">Non Aktif</div>`;

			return `<div class="ui grey basic label">${value}</div>`;
		}

		return value;
	}

	// ==========================================================
	// RENDER BODY TABEL
	// ==========================================================
	renderBody() {
		// Jika kosong
		if (this.data.length === 0) {
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

			if (columns) {
				columns.forEach((col) => {
					let value = row[col.key];
					value = this.formatValue(value, col.format);

					html += `<td>${value ?? ""}</td>`;
				});
			} else {
				// fallback jika config tidak ada
				Object.values(row).forEach((val) => {
					html += `<td>${val ?? ""}</td>`;
				});
			}

			// Tombol aksi
			html += `
				<td class="collapsing">
					<div class="ui mini basic icon buttons">
						<button class="ui button" data-action="edit">
							<i class="edit icon"></i>
						</button>
						<button class="ui button" data-action="delete">
							<i class="trash icon"></i>
						</button>
					</div>
				</td>
			`;

			html += "</tr>";
		});

		$(this.tbody).html(html);
	}

	// ==========================================================
	// RENDER PAGINATION
	// ==========================================================
	renderPagination() {
		if (this.totalPages <= 1) {
			$(this.pagination).html("");
			return;
		}

		let html = `<div class="ui pagination menu">`;

		for (let i = 1; i <= this.totalPages; i++) {
			html += `
				<a class="item ${i === this.currentPage ? "active" : ""}"
				   data-page="${i}">
				   ${i}
				</a>
			`;
		}

		html += `</div>`;

		$(this.pagination).html(html);
	}

	// ==========================================================
	// EVENT LISTENER
	// ==========================================================
	bindEvents() {
		// Pagination
		$(document).on("click", `${this.pagination} [data-page]`, (e) => {
			const page = parseInt($(e.currentTarget).data("page"));
			this.changePage(page);
		});

		// Edit / Delete
		$(document).on("click", `${this.tbody} [data-action]`, (e) => {
			const action = $(e.currentTarget).data("action");
			const id = $(e.currentTarget).closest("tr").data("id");

			this.handleAction(action, id);
		});
	}

	// ==========================================================
	// CHANGE PAGE
	// ==========================================================
	changePage(page) {
		if (page < 1 || page > this.totalPages) return;

		this.currentPage = page;
		this.fetchData();
	}

	// ==========================================================
	// HANDLE ACTION
	// ==========================================================
	handleAction(action, id) {
		if (action === "edit") {
			$(document).trigger("table:edit", id);
		}

		if (action === "delete") {
			this.deleteRow(id);
		}
	}

	// ==========================================================
	// DELETE DATA
	// ==========================================================
	deleteRow(id) {
		// Ambil data row untuk pesan dinamis (optional)
		const rowData = this.data.find((r) => r.id == id);

		const label = rowData?.nama_misi || rowData?.nama || "data ini";

		DialogEngine.show({
			title: "Konfirmasi Hapus",
			message: `Yakin ingin menghapus <b>${label}</b>?`,
			icon: "trash alternate red",
			approveText: "Ya, Hapus",
			cancelText: "Batal",
			onApprove: () => {
				// Return Promise agar loading state bekerja
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
						error: () => {
							reject();
						},
					});
				});
			},
		});
	}

	// ==========================================================
	// DESTROY
	// ==========================================================
	destroy() {
		$(document).off("click", `${this.pagination} [data-page]`);
		$(document).off("click", `${this.tbody} [data-action]`);

		$(this.tbody).empty();
		$(this.pagination).empty();
	}
}
