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

	// public/assets/js/modules/base-crud-module.js

	// public/assets/js/modules/base-crud-module.js

	init() {
		// ====================================================
		// RENDER LAYOUT
		// ====================================================
		this.renderLayout(); // tampilkan layout modul
		this.bindExport();

		// ====================================================
		// INISIALISASI MENU
		// ====================================================
		if (this.useMenu) {
			this.initMenu(); // aktifkan event menu
		}

		// ====================================================
		// AMBIL PARAMETER URL
		// ====================================================
		const urlParams = new URLSearchParams(window.location.search);

		const tblFromUrl = urlParams.get("tbl");

		// 🔥 TAMBAHAN INI
		const reqFromUrl = urlParams.get("req");

		if (tblFromUrl) {
			const item = this.menuItems?.find((m) => m.tbl === tblFromUrl) || null;

			// PRIORITAS:
			// 1 req dari URL
			// 2 req dari menuItems
			// 3 req dari state

			const req = reqFromUrl ?? item?.req ?? this.state.req ?? null;

			this.loadTable(tblFromUrl, req);
		}

		// ====================================================
		// JIKA URL TIDAK MEMILIKI TBL
		// ====================================================
		else if (this.menuItems.length > 0) {
			// ambil menu pertama
			const firstMenu = this.menuItems[0];

			// tbl dari menu pertama
			const tbl = firstMenu.tbl;

			// req dari menu pertama jika ada
			const req = firstMenu.req ?? this.state.req ?? null;

			// load tabel default
			this.loadTable(tbl, req);
		}
	}

	bindExport() {
		const eventName = `click.${this.moduleName}Export`;

		$(document)
			.off(eventName, "#crud-table-container [data-action='export']")
			.on(eventName, "#crud-table-container [data-action='export']", (e) => {
				e.preventDefault();

				const $button = $(e.currentTarget);
				const tbl = $button.data("tbl") || this.state.tbl;
				const req = $button.data("req") || this.state.req;

				if (!tbl) {
					Toast.error("Tabel export tidak ditemukan");
					return;
				}

				const params = new URLSearchParams({ tabel: tbl });
				if (req) params.set("req", req);

				const exportUrl = `/export?${params.toString()}`;
				window.location.href = window.appUrl ? window.appUrl(exportUrl) : exportUrl;
			});
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
              ${item.req !== undefined ? `data-req="${item.req}"` : ``}>
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
			const req = $item.data("req") || null; // jangan pakai ?? null

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
		this.state.setTable(tbl);

		// JANGAN override jika req undefined
		if (req !== undefined) {
			// // cek explicit undefined
			this.state.req = req; // // hanya set jika ada nilai
		}

		window.app = window.app || {};
		window.app.state = this.state;

		const basePath = window.location.pathname;
		const url = req ? `${basePath}?tbl=${tbl}&req=${req}` : `${basePath}?tbl=${tbl}`;

		window.history.replaceState(null, "", url);

		// ====================================================
		// DESTROY TABLE MANAGER LAMA
		// ====================================================
		if (this.tableManager && typeof this.tableManager.destroy === "function") {
			this.tableManager.destroy(); // hapus event lama
		}

		// ====================================================
		// RENDER HEADER TABEL
		// ====================================================
		const title = this.formatTitle(tbl); // format nama tabel

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

		this.tableManager = TableManager.get({
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

		// this.tableManager.init();
		// memulai load tabel
	}
	buildActionButtons(tbl) {
		const importType = "import";
		const importIcon = "upload";
		// ambil req dari state
		const req = this.state.req;

		// buat atribut req hanya jika ada nilainya
		const reqAttr = req ? `data-req="${req}"` : "";

		return `
        <div class="ui right floated basic icon buttons" style="margin-top:10px;">

            <button class="ui button"
                data-ui="open-form"
								data-container="flyout"
								data-action="add"
								data-tbl="${tbl}"
                ${reqAttr}>
                <i class="plus icon"></i>
            </button>

            <button class="ui button"
                data-ui="open-form"
								data-container="flyout"
								data-action="${importType}"
								data-tbl="${tbl}"
                ${reqAttr}>
                <i class="${importIcon} icon"></i>
            </button>

            <button class="ui icon button"
                data-action="export"
                data-tbl="${tbl}"
                ${reqAttr}>
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
