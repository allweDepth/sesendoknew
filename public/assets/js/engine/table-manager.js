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
		this.requestId = 0;
		// simpan state modul
		this.state = config.state; // referensi state global
		this.tbl = this.state.tbl; // snapshot tabel saat instance dibuat
		// jangan ubah state di constructor
		this.req = null; // jangan snapshot state.req agar selalu membaca state terbaru
		// ajax engine
		this.ajax = window.Ajax;

		// flag agar init tidak double
		this.initialized = false;

		// selector tbody tabel
		this.tbody = config.tbody || `tbody[name="tabel_${this.state.tbl}"]`; // gunakan selector string

		// selector pagination
		this.pagination = config.pagination || `div[name="pagination_${this.state.tbl}"]`; // gunakan selector string

		// pagination state
		this.currentPage = 1;
		this.limit = config.limit || 10;
		this.totalRows = 0;
		this.totalPages = 0;

		// sorting state
		this.sortBy = null;
		this.sortDir = "asc";

		// search state
		this.searchQuery = "";
		this.searchTimer = null;
		this.pendingRequest = null;

		// data hasil fetch
		this.data = [];

		// primary key default
		this.primaryKey = "id";
		// FIX: SIMPAN MODE untuk menentukan flyout atau modal yang digunakan
		// contohnya tata naskah menggunakan
		// initEngine() {
		// this.tableManager = new TableManager({
		// 	state: this.state,
		// 	container: "#table-container", // DOM container
		// 	mode: "modal", // // FIX: pisahkan mode
		// });
		this.mode = config.mode || "flyout"; //
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
		this.setToolbarAvailability($(this.tbody).closest("table").length > 0);

		// bind event table
		this.bindEvents();

		// load data pertama
		this.fetchData();

		// event reload dari form
		const reloadEvent = `form:success.${this.tbl}.table`;

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
		this.requestId++;
		const currentRequest = this.requestId;
		this.syncLimitFromNavbar();
		this.renderLoader();

		/* =====================================================
   BUILD PAYLOAD REQUEST
===================================================== */
		/* =====================================================
AMBIL LIMIT TERBARU DARI NAVBAR
===================================================== */

		// const limitNavbar = $("#countRow").dropdown("get value");
		// // membaca jumlah row dari dropdown navbar

		// this.limit = limitNavbar || this.limit;
		// jika navbar kosong gunakan limit lama
		let payload = {
			action: "list",
			tbl: this.state.tbl,
			halaman: this.currentPage,
			rows: this.limit,
			cari: this.searchQuery,
			sort_by: this.sortBy,
			sort_dir: this.sortDir,
		};

		// kirim req jika ada
		if (this.state.req) {
			payload.req = this.state.req;
		}
		console.log("TableManager fetchData req:", this.state.req);
		//=====================================================
		// REQ TABLE OVERRIDE
		//=====================================================

		// const reqTable = this.state.req;
		// tidak diperlukan karena sudah memakai this.state.req di atas

		// if (reqTable !== null && reqTable !== undefined && reqTable !== "") {
		//     payload.req = reqTable;
		// }
		// blok ini duplikat dan menyebabkan kebingungan

		//=====================================================
		//AJAX REQUEST
		//===================================================== */
		// console.log("Payload:", payload);
		if (this.pendingRequest && this.pendingRequest.readyState !== 4) this.pendingRequest.abort();
		this.pendingRequest = this.ajax.request({
			method: "POST",
			data: payload,
			success: (res) => {
				if (currentRequest !== this.requestId) return;

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
			complete: () => { this.pendingRequest = null; },
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
		// Gunakan req jika ada, fallback ke tbl
		const configKey = this.state.req || this.state.tbl;
		const config = window.UIConfig?.[configKey];
		if (!config || !config.form?.elements) return [];

		const availableKeys = new Set(Object.keys(this.data[0] || {}));
		const candidates = config.form.elements
			.filter(
				(item) =>
					item.prop?.name &&
					item.prop?.table !== false &&
					(!availableKeys.size || availableKeys.has(item.prop.name)) &&
					!["divider", "header", "fieldHidden", "fieldCustom"].includes(item.tag),
			)
			.map((item) => ({
				key: item.prop.name,
				label: item.prop.label || item.prop.name,
				format: item.prop.format || (/(nilai|jumlah|total|pagu|harga|anggaran)/i.test(item.prop.name) ? "currency" : null),
				priority: Number(item.prop.tablePriority || 0),
				width: item.prop.tableWidth || this.inferColumnWidth(item.prop.name),
			}));

		const configuredMaximum = Number(config.table?.maxColumns || config.tableMaxColumns || 7);
		if (candidates.length <= configuredMaximum) return candidates;
		const score = (column, index) => {
			const key = column.key.toLowerCase();
			let value = column.priority * 1000 - index;
			if (/^(nama|judul|uraian|kode|nomor|npwp|nip)/.test(key)) value += 180;
			if (/(nilai|jumlah|total|pagu|tanggal|status|tahun|tahap)/.test(key)) value += 100;
			if (/(file|upload|alamat_dir|notaris|akta|ktp|rekening|keterangan|disable|setujui|kunci)/.test(key)) value -= 170;
			return value;
		};
		const selected = candidates
			.map((column, index) => ({ column, index, score: score(column, index) }))
			.sort((a, b) => b.score - a.score)
			.slice(0, configuredMaximum)
			.sort((a, b) => a.index - b.index)
			.map(entry => entry.column);
		return selected;
	}

	inferColumnWidth(key) {
		key = String(key || "").toLowerCase();
		if (/(uraian|nama|alamat|keterangan|nomor_kontrak|nomor_spmk)/.test(key)) return "wide";
		if (/(nilai|jumlah|total|pagu|harga)/.test(key)) return "money";
		if (/(tanggal|periode|tahun)/.test(key)) return "date";
		if (/(status|tahap|kode|npwp|nip)/.test(key)) return "compact";
		return "normal";
	}

	/* =====================================================
			RENDER HEADER
		===================================================== */
	renderHeader() {
		const columns = this.getColumnsFromConfig();
		if (!columns.length) return;

		let html = "<tr>";

		columns.forEach((col) => {
			const active = this.sortBy === col.key;
			const direction = active ? this.sortDir : "none";
			html += `<th class="table-sortable table-col-${col.width} ${active ? `sorted ${this.sortDir === "asc" ? "ascending" : "descending"}` : ""}" data-sort-key="${this.escapeHtml(col.key)}" aria-sort="${direction === "none" ? "none" : direction === "asc" ? "ascending" : "descending"}" tabindex="0">${this.escapeHtml(col.label)}<i class="sort icon" aria-hidden="true"></i></th>`;
		});

		html += "<th class='collapsing'>Aksi</th></tr>";

		$(this.tbody).closest("table").attr("data-managed-table", this.tbl).addClass("sortable modern-data-table").find("thead").html(html);
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

		if (format === "excerpt") {
			const parser = document.createElement("div");
			parser.innerHTML = String(value);
			const plainText = (parser.textContent || "").replace(/\s+/g, " ").trim();
			const limit = 90;
			const excerpt = plainText.length > limit
				? `${plainText.slice(0, limit).trimEnd()}\u2026`
				: plainText;
			const safeText = $("<div>").text(excerpt || "Belum ada konten").html();
			return `<div class="table-content-excerpt" title="Cuplikan konten">${safeText}</div>`;
		}

		if (format === "currency") {
			return new Intl.NumberFormat("id-ID", {
				style: "currency",
				currency: "IDR",
				minimumFractionDigits: 0,
				maximumFractionDigits: 2,
			}).format(value);
		}

		if (format === "status") {
			if (value == 1 || value === "aktif") return `<div class="ui green basic label">Aktif</div>`;
			if (value == 0 || value === "nonaktif") return `<div class="ui red basic label">Non Aktif</div>`;
		}

		return this.escapeHtml(String(value));
	}

	escapeHtml(value) {
		return $("<div>").text(String(value ?? "")).html();
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
			// =====================================================
			// DEFAULT HARDCODE (TETAP ADA)
			// =====================================================
			console.log("tbl pembentuk rows : " + this.state.tbl);
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
				case "trx_naskah_dinas":
					btnExtra += `
        <button type="button" class="ui teal button"
    data-action="export_pdf"
    data-tbl="${this.state.tbl}">
    <i class="file pdf icon"></i>
</button>
    `;
					break;
				default:
					break;
			}
			// =====================================================
			// 🔥 NEW: GLOBAL INJECTION HOOK
			// =====================================================
			// cek apakah ada injector global
			if (window.TableRowInjector && typeof window.TableRowInjector === "function") {
				btnExtra +=
					window.TableRowInjector({
						row: row, // data row
						tbl: this.state.tbl, // nama tabel
						id: id, // primary key
						state: this.state, // state global
					}) || "";
			}
			columns.forEach((col) => {
				let value = row[col.key] ?? "";
				value = this.formatValue(value, col.format);
				html += `<td class="table-col-${col.width}" data-label="${this.escapeHtml(col.label)}">${value}</td>`;
			});
			// 🔥 FIX: hanya untuk tata_naskah + edit
			let customAttr = "";

			let action = "edit";

			if (this.state.tbl === "trx_naskah_dinas") {
				customAttr = 'data-custom-form="true"';
				action = "edit_json"; // 🔥 FIX
			}
			const mutationPolicy = window.moduleMutationPolicy?.(this.state.tbl, this.state.req);
			const editButton = mutationPolicy && !mutationPolicy.edit ? "" : `<button class="ui button"
							data-ui="open-form" data-action="${action}" data-tbl="${this.state.tbl}" ${customAttr}
							${this.state.req ? `data-req="${this.state.req}"` : ``} ${this.mode === "modal" ? `data-container="modal"` : ""}
							data-id="${id}"><i class="blue edit icon"></i></button>`;
			const deleteButton = mutationPolicy && !mutationPolicy.delete ? "" : `<button class="ui red button" data-action="delete" ${this.state.req ? `data-req="${this.state.req}"` : ``}><i class="red trash icon"></i></button>`;
			html += `
					<td class="collapsing">
						<div class="ui mini basic icon buttons">
							${editButton}
							${btnExtra}
							${deleteButton}
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
			.off(`click.tablePagination.${this.state.tbl}`)
			.on(`click.tablePagination.${this.state.tbl}`, "[data-page]", (e) => {
				const page = parseInt($(e.currentTarget).data("page"));

				if (!page || page === this.currentPage) return;

				this.changePage(page);
			});

		// =====================================================
		// TABLE ACTION EVENT
		// =====================================================

		const actionEvent = `click.tableAction.${this.tbl}`;

		$(document).off(actionEvent);

		$(document).on(actionEvent, `${this.tbody} [data-action]`, (e) => {
			const action = $(e.currentTarget).data("action");

			const id = $(e.currentTarget).closest("tr").data("id");

			this.handleAction(action, id, e.currentTarget);
		});

		const sortSelector = `table[data-managed-table="${this.tbl}"] thead [data-sort-key]`;
		$(document)
			.off(`click.tableSort.${this.tbl} keydown.tableSort.${this.tbl}`, sortSelector)
			.on(`click.tableSort.${this.tbl}`, sortSelector, e => this.changeSort($(e.currentTarget).data("sort-key")))
			.on(`keydown.tableSort.${this.tbl}`, sortSelector, e => {
				if (!["Enter", " "].includes(e.key)) return;
				e.preventDefault(); this.changeSort($(e.currentTarget).data("sort-key"));
			});

		// =====================================================
		// SEARCH EVENT
		// =====================================================

		const namespace = `.tableToolbar.${this.tbl}`;
		$(document).off(namespace);
		const runSearch = (immediate = false) => {
			clearTimeout(this.searchTimer);
			const execute = () => {
				this.searchQuery = String($("#cari_data").val() || '').trim();
				this.currentPage = 1;
				this.fetchData();
			};
			if (immediate) execute(); else this.searchTimer = setTimeout(execute, 450);
		};
		$(document)
			.on(`input${namespace}`, "#cari_data", () => runSearch(false))
			.on(`keydown${namespace}`, "#cari_data", e => { if (e.key === 'Enter') { e.preventDefault(); runSearch(true); } })
			.on(`click${namespace}`, ".cari_data .search.icon", () => runSearch(true))
			.on(`change${namespace}`, "#countRow input[name=countRow]", () => {
				this.syncLimitFromNavbar();
				this.currentPage = 1;
				this.fetchData();
			});
	}

	changeSort(key) {
		if (!this.getColumnsFromConfig().some(column => column.key === key)) return;
		this.sortDir = this.sortBy === key && this.sortDir === "asc" ? "desc" : "asc";
		this.sortBy = key;
		this.currentPage = 1;
		this.fetchData();
	}

	setToolbarAvailability(active) {
		const input = $("#cari_data");
		input.prop("disabled", !active)
			.attr("aria-disabled", active ? "false" : "true")
			.attr("placeholder", active ? "Cari data pada tabel…" : "Pencarian tidak tersedia");
		input.closest(".ui.input").toggleClass("disabled", !active);
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
	handleAction(action, id, el = null) {
		// tambahkan el

		switch (action) {
			case "delete":
				this.deleteRow(id);
				break;

			case "akta":
				this.openAkta(id);
				break;

			// =====================================================
			// EXPORT PDF
			// =====================================================
			case "export_pdf":
				this.exportPdf(id, el); // gunakan el dari parameter
				break;
		}
	}
	// =====================================================
	// EXPORT PDF
	// =====================================================
	exportPdf(id, el = null) {
		// =====================================================
		// VALIDASI ID
		// =====================================================
		if (!id) return; // stop jika id kosong

		// =====================================================
		// PRIORITAS data-tbl dari button
		// =====================================================
		let tbl = this.state.tbl; // default

		if (el) {
			const btnTbl = $(el).data("tbl"); // baca attribute
			if (btnTbl) tbl = btnTbl; // override jika ada
		}

		// =====================================================
		// OPEN PDF
		// =====================================================
		const path = `/tata_naskah/generate_pdf?tbl=${encodeURIComponent(tbl)}&id=${encodeURIComponent(id)}`;
		window.open(window.appUrl ? window.appUrl(path) : path, "_blank");
	}
	openAkta(id) {
		const form = new FormEngine({
			formSelector: "#form_flyout",
			state: Object.assign(
				{},
				{
					tbl: "rekanan_akta",
					action: "insert",
					reloadTable: "rekanan",
				},
			),
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
							tbl: req || this.tbl,
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

		// hapus event pagination dari container yang benar
		$(this.pagination).off(`click.tablePagination.${this.state.tbl}`);

		// hapus event global
		$(document).off(`click.tableAction.${this.state.tbl}`);

		$(document).off(`.tableToolbar.${this.tbl}`);
		$(document).off(`.tableSort.${this.tbl}`);
		clearTimeout(this.searchTimer);
		if (this.pendingRequest && this.pendingRequest.readyState !== 4) this.pendingRequest.abort();
		this.setToolbarAvailability(false);

		// bersihkan DOM
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
}
