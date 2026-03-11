/* =========================================================
		TABLE MANAGER — FINAL STRICT SPA VERSION
		---------------------------------------------------------
		✔ Fully compliant with DynamicTableService v3.2
		✔ Strict success validation
		✔ Uses backend primary_key dynamically
		✔ No legacy fallback
		✔ Clean event binding
	========================================================= */

class TableManager {
	/* =====================================================
			CONSTRUCTOR
			-----------------------------------------------------
			Inisialisasi:
			- State action & tabel aktif
			- AJAX engine
			- Selector fallback otomatis
		===================================================== */
	static instances = {};
	constructor(config = {}) {
		// constructor instance table manager

		this.state = config.state; // simpan state global

		this.req = config.state?.req || null; // simpan req lokal

		this.ajax = window.Ajax; // engine ajax

		this.initialized = false; // flag agar init tidak dobel

		// ======================================================
		// INSTANCE REGISTRY
		// ======================================================

		const key = this.state.tbl; // gunakan nama tabel sebagai key

		if (TableManager.instances[key]) {
			// jika instance sudah ada

			return TableManager.instances[key]; // gunakan instance lama
		}

		TableManager.instances[key] = this; // simpan instance baru
	}

	/* =====================================================
			INIT TABLE
			-----------------------------------------------------
			- Bind event
			- Ambil data pertama kali
		===================================================== */
	/* ===================================================== */
	init() {
		if (this.initialized) return;

		this.initialized = true;

		// bind event table
		this.bindEvents();

		// load data pertama
		this.fetchData();

		// event reload dari form
		const reloadEvent = `form:success.${this.state.tbl}.table`;

		// hapus event lama
		$(document)
			.off(reloadEvent)
			.on(reloadEvent, () => {
				this.fetchData();
			});
	}

	/* =====================================================
			FETCH DATA
			-----------------------------------------------------
			Mengambil data dari backend
			Parameter:
			- action
			- tbl
			- page
			- limit
		===================================================== */
	/* ===================================================== */
	fetchData() {
		this.syncLimitFromNavbar();
		this.renderLoader();

		/* =====================================================
   BUILD PAYLOAD REQUEST
===================================================== */
		/* =====================================================
AMBIL LIMIT TERBARU DARI NAVBAR
===================================================== */

		const limitNavbar = $("#countRow").dropdown("get value");
		// membaca jumlah row dari dropdown navbar

		this.limit = limitNavbar || this.limit;
		// jika navbar kosong gunakan limit lama
		let payload = {
			/* aksi CRUD */
			action: "list", // aksi default list

			/* alias tabel logical */
			tbl: this.state.tbl, // tbl berasal dari global state

			/* pagination */
			halaman: this.currentPage, // halaman aktif

			rows: this.limit, // jumlah baris

			/* search */
			cari: this.searchQuery, // keyword pencarian

			/* sorting */
			sort_by: this.sortBy, // kolom sort

			sort_dir: this.sortDir, // arah sort
		};

		//=====================================================
		// REQ TABLE OVERRIDE
		//=====================================================

		const reqTable = this.req || this.state.req || window.app.req; // ambil req dari berbagai sumber state

		if (reqTable !== null && reqTable !== undefined && reqTable !== "") {
			// jika req ada

			payload.req = reqTable; // kirim req sebagai override tabel database
		}

		//=====================================================
		//AJAX REQUEST
		//===================================================== */

		this.ajax.request({
			method: "POST",
			data: payload,
			success: (res) => {
				if (!res || !res.success) {
					Toast.error(res?.message || "Gagal memuat data");
					return;
				}

				this.primaryKey = res.meta?.primary_key || "id";
				this.data = Array.isArray(res.data) ? res.data : [];

				this.handlePagination(res.meta || {});

				this.renderHeader();
				this.renderBody();
				this.renderPagination();
			},
			error: () => {
				Toast.error("Terjadi kesalahan sistem");
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
			- Ambil dari UIConfig[][tbl]
			- Auto hide id
			- Skip prop.table === false
		===================================================== */
	getColumnsFromConfig() {
		if (!window.UIConfig) return [];

		const config = window.UIConfig?.[this.state.tbl];
		if (!config || !config.form?.elements) return [];

		return config.form.elements
			.filter(
				(item) =>
					item.prop?.name &&
					item.prop?.table !== false &&
					!["divider", "header", "fieldHidden", "fieldCustom"].includes(item.tag),
			)
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
		if (!columns.length) return;

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

		if (format === "currency") {
			return new Intl.NumberFormat("id-ID", {
				style: "currency",
				currency: "IDR",
				minimumFractionDigits: 0,
			}).format(value);
		}

		if (format === "status") {
			if (value == 1 || value === "aktif") return `<div class="ui green basic label">Aktif</div>`;
			if (value == 0 || value === "nonaktif") return `<div class="ui red basic label">Non Aktif</div>`;
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
						<div class="ui info message">
							Tidak ada data
						</div>
					</td>
				</tr>
			`);
			return;
		}

		const columns = this.getColumnsFromConfig();
		let html = "";

		this.data.forEach((row) => {
			const id = row[this.primaryKey];

			const groupKey = $(this.tbody).closest("table").data("group");

			let extra = "";

			if (groupKey && row[groupKey]) {
				extra = ` data-${groupKey}="${row[groupKey]}"`;
			}

			html += `<tr data-id="${id}"${extra}>`;

			let btnExtra = "";

			switch (this.state.tbl) {
				case "rekanan":
				case "rekanan_neo":
					btnExtra = `
						<button class="ui teal button"
							data-action="akta">
							<i class="file alternate outline icon"></i>
						</button>
					`;
					break;

				default:
					break;
			}

			columns.forEach((col) => {
				let value = row[col.key] ?? "";
				value = this.formatValue(value, col.format);
				html += `<td>${value}</td>`;
			});

			html += `
					<td class="collapsing">
						<div class="ui mini basic icon buttons">
							<button class="ui button"
							data-ui="open-form"
							data-jns="edit"
							data-tbl="${this.state.tbl}"
							${this.state.req ? `data-req="${this.state.req}"` : ``}
							data-id="${id}">
								<i class="blue edit icon"></i>
							</button>

							${btnExtra}

							<button class="ui red button"
								data-action="delete"
								${this.state.req ? `data-req="${this.state.req}"` : ``}>
								<i class="red trash icon"></i>
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
		for (let i = Math.max(2, current - range); i <= Math.min(total - 1, current + range); i++) {
			html += createItem(i, i === current);
		}

		// Dots after
		if (current + range < total - 1) html += `<div class="disabled item">...</div>`;

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
		// =====================================================
		// PAGINATION EVENT
		// =====================================================

		const paginationEvent = `click.tablePagination.${this.state.tbl}`;

		$(this.pagination)
			.off("click.tablePagination")
			.on("click.tablePagination", "[data-page]", (e) => {
				const page = parseInt($(e.currentTarget).data("page"));

				if (!page || page === this.currentPage) return;

				this.changePage(page);
			});

		// =====================================================
		// TABLE ACTION EVENT
		// =====================================================

		const actionEvent = `click.tableAction.${this.state.tbl}`;

		$(document).off(actionEvent);

		$(document).on(actionEvent, `${this.tbody} [data-action]`, (e) => {
			const action = $(e.currentTarget).data("action");

			const id = $(e.currentTarget).closest("tr").data("id");

			this.handleAction(action, id);
		});

		// =====================================================
		// SEARCH EVENT
		// =====================================================

		const searchEvent = `keypress.tableSearch.${this.state.tbl}`;

		$(document).off(searchEvent);

		$(document).on(searchEvent, "#cari_data", (e) => {
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
		switch (action) {
			case "delete":
				this.deleteRow(id);
				break;

			case "akta":
				this.openAkta(id);
				break;
		}
	}
	openAkta(id) {
		const form = new FormEngine({
			formSelector: "#form_flyout",
			state: {
				tbl: "rekanan_akta",
				action: "insert",
				reloadTable: "rekanan",
			},
			ajax: this.ajax,
		});

		$("#form_flyout").empty();

		FormEngine.render("#form_flyout", UIConfig.rekanan_akta.form.elements, form, UIConfig.rekanan_akta.layout);

		$("#form_flyout [name=rekanan_id]").val(id);

		$(".sidebarkanan").sidebar("show");
	}

	/* =====================================================
			DELETE ROW (WITH CONFIRMATION)
		===================================================== */
	deleteRow(id) {
		const rowData = this.data.find((r) => r[this.primaryKey] == id);
		const label = rowData ? Object.values(rowData)[1] : "data ini";

		DialogEngine.show({
			title: "Konfirmasi Hapus",
			message: `Yakin ingin menghapus <b>${label}</b>?`,
			icon: "trash red",
			approveText: "Ya, Hapus",
			cancelText: "Batal",
			onApprove: () => {
				return new Promise((resolve, reject) => {
					const btn = $(`${this.tbody} tr[data-id="${id}"] [data-action="delete"]`);

					let req = btn.data("req") || null;

					this.ajax.request({
						method: "POST",
						data: {
							action: "delete",
							// jika ada data-req gunakan itu
							tbl: req || this.state.tbl,
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
		delete TableManager.instances[this.state.tbl];
		$(document).off(`click.tablePagination.${this.state.tbl}`);

		$(document).off(`click.tableAction.${this.state.tbl}`);

		$(document).off(`keypress.tableSearch.${this.state.tbl}`);

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
				this.limit = 999999;
			} else {
				this.limit = parseInt(value) || this.limit;
			}
		}
	}
	static get(config = {}) {
		const tbl = config.state?.tbl;

		// jika instance sudah ada → destroy dulu
		if (TableManager.instances[tbl]) {
			TableManager.instances[tbl].destroy();
		}

		const instance = new TableManager(config);

		TableManager.instances[tbl] = instance;

		instance.init();

		return instance;
	}
	// =====================================================
	// DESTROY INSTANCE
	// membersihkan event agar tidak terjadi event stacking
	// =====================================================
	destroy() {
		// hapus event reload form
		const reloadEvent = `form:success.${this.state.tbl}.table`;

		$(document).off(reloadEvent); // hapus listener reload

		// hapus semua event pagination / sorting / search
		$(document).off(`.table-${this.state.tbl}`);

		// reset flag init
		this.initialized = false;
	}
}
