/**
 * ============================================================
 * DYNAMIC TABLE SERVICE v4.0 ENTERPRISE EDITION
 * ============================================================
 * 
 * FITUR:
 * - Config Driven
 * - Action Registry
 * - Event Delegation Terpusat
 * - Lifecycle Clean
 * - State Internal
 * - Tidak Mengubah AJAX Lama
 */

class DynamicTableService {

    /**
     * ========================================================
     * CONSTRUCTOR
     * ========================================================
     * @param {Object} config
     */
    constructor(config = {}) {

        // Global state
        this.state = config.state;

        // Ajax service
        this.ajax = config.ajax;

        // Container selector
        this.container = config.container;

        // Nama module
        this.module = config.module;

        // Nama tabel
        this.table = config.table;

        // Kolom yang ingin ditampilkan (optional)
        this.columns = config.columns || [];

        // Registry action
        this.actions = {};

        // Data internal
        this.data = [];

        // Status aktif
        this.isInitialized = false;
    }

    /**
     * ========================================================
     * INIT
     * ========================================================
     */
    init() {

        this.isInitialized = true;

        this.registerDefaultActions();

        this.fetchData();

        this.bindEvents();
    }

    /**
     * ========================================================
     * FETCH DATA
     * ========================================================
     */
    fetchData() {

        this.ajax.request({

            data: {
                module: this.module,
                action: "list",
                tbl: this.table
            },

            success: (res) => {

                this.data = res.data || [];

                this.render();

            },

            error: () => {
                console.error("Gagal memuat data.");
            }

        });
    }

    /**
     * ========================================================
     * RENDER TABLE
     * ========================================================
     */
    render() {

        const html = `
            <table class="ui celled selectable table">
                ${this.buildHeader()}
                ${this.buildBody()}
            </table>
        `;

        $(this.container).html(html);
    }

    /**
     * ========================================================
     * BUILD HEADER
     * ========================================================
     */
    buildHeader() {

        let headerHTML = `<thead><tr>`;

        switch (this.data.length > 0) {

            case true:

                const source = this.columns.length > 0
                    ? this.columns
                    : Object.keys(this.data[0]);

                source.forEach(col => {
                    headerHTML += `<th>${col}</th>`;
                });

                headerHTML += `<th style="width:120px;">Action</th>`;
                break;

            default:
                headerHTML += `<th>Tidak ada data</th>`;
                break;
        }

        headerHTML += `</tr></thead>`;

        return headerHTML;
    }

    /**
     * ========================================================
     * BUILD BODY
     * ========================================================
     */
    buildBody() {

        let bodyHTML = `<tbody>`;

        this.data.forEach(row => {

            bodyHTML += `<tr data-id="${row.id}">`;

            const source = this.columns.length > 0
                ? this.columns
                : Object.keys(row);

            source.forEach(col => {
                bodyHTML += `<td>${row[col] ?? ""}</td>`;
            });

            bodyHTML += `
                <td>
                    <button class="ui mini primary button"
                        data-action="edit">
                        Edit
                    </button>
                    <button class="ui mini red button"
                        data-action="delete">
                        Delete
                    </button>
                </td>
            `;

            bodyHTML += `</tr>`;
        });

        bodyHTML += `</tbody>`;

        return bodyHTML;
    }

    /**
     * ========================================================
     * REGISTER DEFAULT ACTIONS
     * ========================================================
     */
    registerDefaultActions() {

        this.actions["edit"] = (id) => {

            $(document).trigger("table:edit", id);

        };

        this.actions["delete"] = (id) => {

            this.deleteRow(id);

        };
    }

    /**
     * ========================================================
     * HANDLE ACTION
     * ========================================================
     */
    handleAction(action, id) {

        switch (typeof this.actions[action]) {

            case "function":
                this.actions[action](id);
                break;

            default:
                console.warn("Action tidak terdaftar:", action);
                break;
        }
    }

    /**
     * ========================================================
     * DELETE ROW
     * ========================================================
     */
    deleteRow(id) {

        this.ajax.request({

            data: {
                module: this.module,
                action: "delete",
                tbl: this.table,
                id_row: id
            },

            success: () => {
                this.reload();
            }

        });
    }

    /**
     * ========================================================
     * RELOAD TABLE
     * ========================================================
     */
    reload() {

        this.fetchData();
    }

    /**
     * ========================================================
     * BIND EVENTS
     * ========================================================
     */
    bindEvents() {

        $(document).on("click", `${this.container} [data-action]`, (e) => {

            const action = $(e.currentTarget).data("action");

            const id = $(e.currentTarget)
                .closest("tr")
                .data("id");

            this.handleAction(action, id);

        });
    }

    /**
     * ========================================================
     * DESTROY
     * ========================================================
     */
    destroy() {

        $(document).off("click", `${this.container} [data-action]`);

        $(this.container).empty();

        this.data = [];

        this.isInitialized = false;
    }

}