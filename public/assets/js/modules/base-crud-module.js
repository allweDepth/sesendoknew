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
			this.loadTable(tblFromUrl);
		} else if (this.menuItems.length > 0) {
			this.loadTable(this.menuItems[0].tbl);
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
		// 🔥 FLAT STRUCTURE
		this.state.setModule(tbl);
		this.state.setTable(tbl);

		if (this.tableManager) {
			this.tableManager.destroy();
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

		this.tableManager = new TableManager({
			state: this.state,
			tbody: "#crud-tbody",
			pagination: "#crud-pagination",
		});

		this.tableManager.init();
	}
	buildActionButtons(tbl) {
		let importType = "import";
		let importIcon = "upload";

		switch (this.moduleName) {
			case "referensi":
				const strukturList = [
					"urusan",
					"bidang",
					"program",
					"kegiatan",
					"sub_kegiatan",
				];

				if (strukturList.includes(tbl)) {
					importType = "import_struktur";
					importIcon = "sitemap";
				}
				break;
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
