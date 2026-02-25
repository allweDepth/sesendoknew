class TableManager {

	// ==========================================
	// CONSTRUCTOR
	// ==========================================
	constructor(config = {}) {

		// State global (module & tabel aktif)
		this.state = config.state;

		// Buat instance AjaxEngine dengan endpoint /dynamic
		// Semua request CRUD akan lewat sini
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		// Selector tbody tempat data ditampilkan
		this.tbody = config.tbody;

		// Selector pagination container
		this.pagination = config.pagination;

		// Halaman aktif (default 1)
		this.currentPage = 1;

		// Jumlah data per halaman
		this.limit = config.limit || 10;

		// Total data dari server
		this.totalRows = 0;

		// Total halaman hasil perhitungan
		this.totalPages = 0;

		// Sorting field
		this.sortBy = null;

		// Sorting direction
		this.sortDir = "asc";

		// Keyword pencarian
		this.searchQuery = "";

		// Timeout untuk debounce search
		this.searchTimeout = null;

		// Data hasil response server
		this.data = [];
	}

	// ==========================================
	// INIT TABLE
	// ==========================================
	init() {

		// Pasang semua event listener
		this.bindEvents();

		// Ambil data pertama kali
		this.fetchData();
	}

	// ==========================================
	// AMBIL DATA DARI SERVER
	// ==========================================
	fetchData() {

		// Tampilkan loader sebelum request
		this.renderLoader();

		// Kirim request ke /dynamic
		this.ajax.request({

			method: "POST", // Gunakan POST

			data: {
				module: this.state.module,      // Nama module (renstra, dll)
				action: "list",                 // Action list (default listing)
				tbl: this.state.tbl,            // Nama tabel
				page: this.currentPage,         // Halaman aktif
				limit: this.limit,              // Limit per halaman
				search: this.searchQuery,       // Keyword pencarian
				sort_by: this.sortBy,           // Field sorting
				sort_dir: this.sortDir          // Arah sorting
			},

			success: (res) => {

				// Simpan data hasil response
				this.data = res.data || [];

				// Proses pagination dari server
				this.handlePagination(res.pagination || {});

				// Render ulang tabel
				this.renderBody();

				// Render ulang pagination
				this.renderPagination();
			}
		});
	}

	// ==========================================
	// PROSES DATA PAGINATION
	// ==========================================
	handlePagination(pagination) {

		// Total data
		this.totalRows = pagination.total || 0;

		// Halaman aktif dari server
		this.currentPage = pagination.page || 1;

		// Limit dari server
		this.limit = pagination.limit || this.limit;

		// Hitung total halaman
		this.totalPages = Math.ceil(this.totalRows / this.limit);
	}

	// ==========================================
	// TAMPILKAN LOADER
	// ==========================================
	renderLoader() {

		// Isi tbody dengan loader
		$(this.tbody).html(`
			<tr>
				<td colspan="100%">
					<div class="ui active inline loader"></div>
				</td>
			</tr>
		`);
	}

	// ==========================================
	// RENDER ISI TABEL
	// ==========================================
	renderBody() {

		// Jika tidak ada data
		if (this.data.length === 0) {

			$(this.tbody).html(`
				<tr>
					<td colspan="100%" class="center aligned">
						Tidak ada data
					</td>
				</tr>
			`);

			return;
		}

		let html = "";

		// Loop setiap row dari server
		this.data.forEach((row) => {

			// Buat baris tabel
			html += `<tr data-id="${row.id}">`;

			// Loop setiap kolom
			Object.values(row).forEach((val) => {
				html += `<td>${val ?? ""}</td>`;
			});

			// Tambahkan tombol aksi
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

			html += `</tr>`;
		});

		// Masukkan ke tbody
		$(this.tbody).html(html);
	}

	// ==========================================
	// RENDER PAGINATION
	// ==========================================
	renderPagination() {

		// Jika hanya 1 halaman, kosongkan
		if (this.totalPages <= 1) {
			$(this.pagination).html("");
			return;
		}

		let html = `<div class="ui pagination menu">`;

		// Loop jumlah halaman
		for (let i = 1; i <= this.totalPages; i++) {

			html += `
				<a class="item ${i === this.currentPage ? "active" : ""}"
				   data-page="${i}">
				   ${i}
				</a>
			`;
		}

		html += `</div>`;

		// Tampilkan pagination
		$(this.pagination).html(html);
	}

	// ==========================================
	// BIND EVENT
	// ==========================================
	bindEvents() {

		// Event klik pagination
		$(document).on("click", `${this.pagination} [data-page]`, (e) => {

			const page = parseInt($(e.currentTarget).data("page"));

			this.changePage(page);
		});

		// Event klik tombol edit/delete
		$(document).on("click", `${this.tbody} [data-action]`, (e) => {

			const action = $(e.currentTarget).data("action");

			const id = $(e.currentTarget).closest("tr").data("id");

			this.handleAction(action, id);
		});
	}

	// ==========================================
	// PINDAH HALAMAN
	// ==========================================
	changePage(page) {

		// Validasi range halaman
		if (page < 1 || page > this.totalPages) return;

		// Update halaman aktif
		this.currentPage = page;

		// Ambil data ulang
		this.fetchData();
	}

	// ==========================================
	// HANDLE AKSI EDIT / DELETE
	// ==========================================
	handleAction(action, id) {

		if (action === "edit") {

			// Trigger event global edit
			$(document).trigger("table:edit", id);
		}

		if (action === "delete") {

			// Jalankan delete
			this.deleteRow(id);
		}
	}

	// ==========================================
	// DELETE DATA
	// ==========================================
	deleteRow(id) {

		this.ajax.request({

			method: "POST",

			data: {
				module: this.state.module,
				action: "delete",   // Action delete
				tbl: this.state.tbl,
				id_row: id
			},

			success: () => {

				// Reload data setelah delete
				this.fetchData();
			}
		});
	}

	// ==========================================
	// DESTROY TABLE
	// ==========================================
	destroy() {

		// Hapus semua event listener
		$(document).off("click", `${this.pagination} [data-page]`);
		$(document).off("click", `${this.tbody} [data-action]`);

		// Kosongkan DOM
		$(this.tbody).empty();
		$(this.pagination).empty();
	}
}