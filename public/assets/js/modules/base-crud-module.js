class BaseCrudModule {
	constructor(config = {}) {
		this.moduleName = config.moduleName;
		this.useMenu = config.useMenu === true;
		// default false, hanya true jika dikirim
		this.useMenu = config.useMenu !== false; // default true

		this.container = "#main-content";

		this.state = window.app.state;
		this.ajax = window.app.ajax;

		this.tableManager = null;
	}

	init() {
    this.renderLayout();

    if (this.useMenu) {
        this.initMenu();
    }

    this.loadTable(this.menuItems[0]?.tbl);
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
               data-tbl="${item.tbl}">
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
${this.useMenu ? `
<div class="ui secondary pointing menu" id="crudMenu">
    ${menuHtml}
</div>
` : ``}
`
								: ``
						}

            <!-- 🔥 TABEL WAJIB ADA -->
            <div id="crud-table-container">

                <table class="ui celled striped table">
                    <thead></thead>
                    <tbody></tbody>
                </table>

                <div class="pagination-wrapper"></div>

            </div>

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
			const $item = $(e.currentTarget);

			$("#crudMenu .item").removeClass("active");
			$item.addClass("active");

			const tbl = $item.data("tbl");

			this.loadTable(tbl);
		});
	}

	/**
	 * ========================================================
	 * LOAD TABLE
	 * ========================================================
	 */
	loadTable(tbl) {
		// Set state module & tabel aktif
		this.state.setModule(this.moduleName);
		this.state.setTable(tbl);

		// Destroy instance lama jika ada
		if (this.tableManager) {
			this.tableManager.destroy();
		}

		// ======================================================
		// 🔥 RENDER TABLE LAYOUT DULU
		// ======================================================
		$("#crud-table-container").html(`
        <div class="table-wrapper">
            <table class="ui very compact celled striped table">
                <thead>
                   
                </thead>
                <tbody id="crud-tbody"></tbody>
            </table>
        </div>
        <div id="crud-pagination"></div>
    `);

		// ======================================================
		// 🔥 INISIALISASI TABLE MANAGER
		// ======================================================
		this.tableManager = new TableManager({
			state: this.state,
			tbody: "#crud-tbody",
			pagination: "#crud-pagination",
		});

		this.tableManager.init();
	}
}
