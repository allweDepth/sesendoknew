class BaseCrudModule {
	constructor(config = {}) {
		this.moduleName = config.moduleName;

		// 🔥 INI WAJIB ADA
		this.menuItems = Array.isArray(config.menuItems) ? config.menuItems : [];

		// 🔥 PERBAIKI LOGIKA useMenu
		this.useMenu = config.useMenu === true;

		this.container = config.container || "#main-content";

		this.state = window.app.state;
		this.ajax = window.app.ajax;

		this.tableManager = null;
	}

	init() {
		this.renderLayout();

		if (this.useMenu) {
			this.initMenu();
		}

		// 🔥 AMBIL TBL DARI URL
		const urlParams = new URLSearchParams(window.location.search);
		const tblFromUrl = urlParams.get("tbl");

		if (tblFromUrl) {
			// ======================================================
			// cari menuItems berdasarkan tbl
			// ======================================================

			const item = this.menuItems?.find((m) => m.tbl === tblFromUrl) || null;

			// ======================================================
			// default req
			// ======================================================

			let req = null;

			// ======================================================
			// prioritas 1: dari menuItems
			// ======================================================

			if (item && item.req) {
				req = item.req;
			}

			// ======================================================
			// prioritas 2: dari sidebar data-req
			// ======================================================

			if (!req) {
				const menu = document.querySelector(`a[data-spa][href*="tbl=${tblFromUrl}"]`);

				if (menu && menu.dataset.req) {
					req = menu.dataset.req;
				}
			}

			// ======================================================
			// jika keduanya tidak ada → tetap null
			// ======================================================

			this.loadTable(tblFromUrl, req);

			this.loadTable(tblFromUrl, req);
		} else if (this.menuItems.length > 0) {
			// ambil item menu pertama
			const firstMenu = this.menuItems[0];

			// tbl dari menu pertama
			const tbl = firstMenu.tbl;

			// req dari menu pertama jika ada
			const req = firstMenu.req || null;

			// load tabel dengan req
			this.loadTable(tbl, req);
		}
	}

	/**
	 * ========================================================
	 * RENDER LAYOUT GENERIC
	 * ========================================================
	 */
	renderLayout() {
		let menuHtml = "";

		this.menuItems.forEach((item, index) => {
			menuHtml += `
            <a class="item ${index === 0 ? "active" : ""}" 
              data-tbl="${item.tbl}"
              ${item.req ? `data-req="${item.req}"` : ``}>
              ${item.label}
            </a>
            `;
		});
		const html = `
        <div class="ui container">
            <h2 class="ui dividing header">
                Modul ${this.moduleName.toUpperCase()}
            </h2>
            ${
							this.useMenu
								? `
            <div class="ui secondary pointing menu" id="crudMenu">
                ${menuHtml}
            </div>
            `
								: ``
						}

            <!-- TABEL HARUS SELALU ADA -->
            <div id="crud-table-container"></div>

        </div>
    `;

		$(this.container).html(html);
	}

	/**
	 * ========================================================
	 * MENU EVENT
	 * ========================================================
	 */
	initMenu() {
		$(document).off("click", "#crudMenu .item");

		$(document).on("click", "#crudMenu .item", (e) => {
			const $item = $(e.currentTarget); // item menu yang diklik

			$("#crudMenu .item").removeClass("active"); // reset active
			$item.addClass("active"); // aktifkan menu ini

			const tbl = $item.data("tbl"); // tabel
			const req = $item.data("req") ?? null; // gunakan nullish coalescing

			/* =====================================================
RESET SEARCH FIELD SAAT TAB MENU BERUBAH
===================================================== */

			$(".ds-search .prompt").val("");
			// kosongkan text search agar query lama tidak dipakai

			$(".ds-search input[type='hidden']").val("");
			// kosongkan value id hasil search

			/* =====================================================
LANJUTKAN LOAD TABLE
===================================================== */

			this.loadTable(tbl, req);
			// load tabel sesuai tab baru
		});
	}

	/**
	 * ========================================================
	 * LOAD TABLE
	 * ========================================================
	 */
	loadTable(tbl, req = null) {
		// tabel aktif
		this.state.setTable(tbl);

		// reset request tambahan
		this.state.req = req || null;

		/* =========================================
SYNC GLOBAL STATE
agar komponen lain seperti search dapat membaca
========================================= */

		window.app = window.app || {};
		window.app.state = this.state;
		// ====================================================
		// 🔥 SYNC URL AGAR REFRESH AMAN
		// ====================================================
		// ====================================================
		// 🔥 SYNC URL AGAR REFRESH AMAN (GENERIC)
		// ====================================================
		const basePath = window.location.pathname;
		window.history.replaceState(null, "", `${basePath}?tbl=${tbl}`);
		if (this.tableManager && typeof this.tableManager.destroy === "function") {
			this.tableManager.destroy(); // hancurkan event lama
		}

		const title = this.formatTitle(tbl);

		const actionHtml = `
        ${this.buildActionButtons(tbl)}
        <div class="ui hidden divider"></div>
        <h3 class="ui dividing header">
            <i class="left align icon"></i>
            Tabel ${title}
        </h3>
    `;

		$("#crud-table-container").html(`
        ${actionHtml}

        <div class="table-wrapper">
            <table class="ui very compact celled striped table">
                <thead></thead>
                <tbody id="crud-tbody"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="100%">
                            <div id="crud-pagination"></div>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `);

		/* =============================================
BUAT INSTANCE TABLE MANAGER
============================================= */

		this.tableManager = new TableManager({
			state: this.state,
			tbody: "#crud-tbody",
			pagination: "#crud-pagination",
		});
		// membuat instance manager tabel

		/* =============================================
SIMPAN GLOBAL AGAR BISA DIAKSES UI COMPONENT
============================================= */

		window.tableManager = this.tableManager;
		// sinkronisasi dengan UIComponents.search()

		/* =============================================
INISIALISASI TABLE
============================================= */

		this.tableManager.init();
		// memulai load tabel
	}
	buildActionButtons(tbl) {
		let importType = "import";
		let importIcon = "upload";
		// gunakan tbl langsung tanpa module
		const strukturList = ["urusan", "bidang", "program", "kegiatan", "sub_kegiatan"]; // daftar tabel struktur

		if (strukturList.includes(tbl)) {
			// cek apakah tabel termasuk struktur
			importType = "import_struktur"; // ubah jenis import
			importIcon = "sitemap"; // ubah icon
		}
		return `
        <div class="ui right floated basic icon buttons" style="margin-top:10px;">

            <button class="ui button"
                data-ui="open-form"
								data-container="flyout"
								data-jns="add"
								data-tbl="${tbl}">
                <i class="plus icon"></i>
            </button>

            <button class="ui button"
                data-ui="open-form"
								data-container="flyout"
								data-jns="${importType}"
								data-tbl="${tbl}">
                <i class="${importIcon} icon"></i>
            </button>

            <button class="ui icon button"
                data-action="export"
                data-tbl="${tbl}">
                <i class="alternate download icon"></i>
            </button>

        </div>
    `;
	}

	/* 🔥 TAMBAHKAN DI SINI */
	formatTitle(text) {
		if (!text) return "";

		return text
			.replace(/_/g, " ")
			.toLowerCase()
			.replace(/\b\w/g, (char) => char.toUpperCase());
	}
}
