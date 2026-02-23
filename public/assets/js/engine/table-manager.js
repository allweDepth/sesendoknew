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
				data-action="delete"
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