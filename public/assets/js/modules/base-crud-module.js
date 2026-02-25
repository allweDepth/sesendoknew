class BaseCrudModule {

    constructor(config = {}) {

        this.moduleName = config.moduleName; // renstra / referensi
        this.menuItems = config.menuItems || [];
        this.container = "#main-content";

        this.state = window.app.state;
        this.ajax = window.app.ajax;

        this.tableManager = null;
    }

    init() {
        this.renderLayout();
        this.initMenu();
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

                <div class="ui secondary pointing menu" id="crudMenu">
                    ${menuHtml}
                </div>

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

        this.state.setModule(this.moduleName);
        this.state.setTable(tbl);

        if (this.tableManager) {
            this.tableManager.destroy();
        }

        this.tableManager = new TableManager({
            state: this.state,
            ajax: this.ajax,
            container: "#crud-table-container"
        });

        this.tableManager.init();
    }

}