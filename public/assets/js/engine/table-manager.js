/**
 * ============================================================
 * TABLE MANAGER v3.0 ENTERPRISE
 * ============================================================
 * 
 * FITUR:
 * - Server-side pagination
 * - Server-side sorting
 * - Server-side search (debounce)
 * - Limit selector
 * - Internal state management
 */

class TableManager {

    /**
     * ========================================================
     * CONSTRUCTOR
     * ========================================================
     */
    constructor(config = {}) {

        // Global state
        this.state = config.state;

        // Ajax service
        this.ajax = config.ajax;

        // Container utama
        this.container = config.container;

        // Pagination
        this.currentPage = 1;
        this.limit = config.limit || 10;
        this.totalRows = 0;
        this.totalPages = 0;

        // Sorting
        this.sortBy = null;
        this.sortDir = "asc";

        // Search
        this.searchQuery = "";
        this.searchTimeout = null;

        // Data aktif
        this.data = [];

        this.isInitialized = false;
    }

    /**
     * ========================================================
     * INIT
     * ========================================================
     */
    init() {

        this.isInitialized = true;

        this.bindEvents();

        this.fetchData();
    }

    /**
     * ========================================================
     * FETCH DATA
     * ========================================================
     */
    fetchData() {

        this.ajax.request({

            data: {
                module: this.state.module,
                action: "list",
                tbl: this.state.tbl,
                page: this.currentPage,
                limit: this.limit,
                search: this.searchQuery,
                sort_by: this.sortBy,
                sort_dir: this.sortDir
            },

            success: (res) => {

                this.data = res.data || [];

                this.handlePagination(res.pagination || {});

                this.render();
            }

        });
    }

    /**
     * ========================================================
     * HANDLE PAGINATION
     * ========================================================
     */
    handlePagination(pagination) {

        this.totalRows = pagination.total || 0;

        this.currentPage = pagination.page || 1;

        this.limit = pagination.limit || this.limit;

        this.totalPages = Math.ceil(this.totalRows / this.limit);
    }

    /**
     * ========================================================
     * RENDER
     * ========================================================
     */
    render() {

        const html = `
            ${this.buildToolbar()}
            <table class="ui celled sortable table">
                ${this.buildHeader()}
                ${this.buildBody()}
            </table>
            ${this.buildPagination()}
        `;

        $(this.container).html(html);
    }

    /**
     * ========================================================
     * TOOLBAR (SEARCH + LIMIT)
     * ========================================================
     */
    buildToolbar() {

        return `
            <div class="ui grid">
                <div class="eight wide column">
                    <div class="ui input fluid">
                        <input type="text"
                               placeholder="Search..."
                               id="table-search"
                               value="${this.searchQuery}">
                    </div>
                </div>
                <div class="four wide column right aligned">
                    <select class="ui dropdown"
                            id="table-limit">
                        <option value="5">5</option>
                        <option value="10" ${this.limit === 10 ? "selected" : ""}>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
            <br>
        `;
    }

    /**
     * ========================================================
     * HEADER (SORTABLE)
     * ========================================================
     */
    buildHeader() {

        let html = `<thead><tr>`;

        switch (this.data.length > 0) {

            case true:

                Object.keys(this.data[0]).forEach(key => {

                    const active = this.sortBy === key
                        ? this.sortDir
                        : "";

                    html += `
                        <th data-sort="${key}">
                            ${key}
                            ${active === "asc" ? "▲" : ""}
                            ${active === "desc" ? "▼" : ""}
                        </th>
                    `;
                });

                html += `<th>Aksi</th>`;
                break;

            default:
                html += `<th>Tidak ada data</th>`;
                break;
        }

        html += `</tr></thead>`;

        return html;
    }

    /**
     * ========================================================
     * BODY
     * ========================================================
     */
    buildBody() {

        let html = `<tbody>`;

        this.data.forEach(row => {

            html += `<tr data-id="${row.id}">`;

            Object.values(row).forEach(val => {
                html += `<td>${val ?? ""}</td>`;
            });

            html += `
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

            html += `</tr>`;
        });

        html += `</tbody>`;

        return html;
    }

    /**
     * ========================================================
     * PAGINATION
     * ========================================================
     */
    buildPagination() {

        switch (this.totalPages > 1) {

            case false:
                return "";

            default:
                break;
        }

        let html = `<div class="ui pagination menu">`;

        for (let i = 1; i <= this.totalPages; i++) {

            html += `
                <a class="item ${i === this.currentPage ? "active" : ""}"
                   data-page="${i}">
                   ${i}
                </a>
            `;
        }

        html += `</div>`;

        return html;
    }

    /**
     * ========================================================
     * BIND EVENTS
     * ========================================================
     */
    bindEvents() {

        // Sorting
        $(document).on("click", `${this.container} th[data-sort]`, (e) => {

            const field = $(e.currentTarget).data("sort");

            this.handleSort(field);
        });

        // Pagination
        $(document).on("click", `${this.container} [data-page]`, (e) => {

            const page = parseInt($(e.currentTarget).data("page"));

            this.changePage(page);
        });

        // Search (debounce)
        $(document).on("input", "#table-search", (e) => {

            const value = e.target.value;

            this.handleSearch(value);
        });

        // Limit change
        $(document).on("change", "#table-limit", (e) => {

            this.limit = parseInt(e.target.value);

            this.currentPage = 1;

            this.fetchData();
        });

        // Action
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
     * SORT HANDLER
     * ========================================================
     */
    handleSort(field) {

        switch (this.sortBy === field) {

            case true:
                this.sortDir = this.sortDir === "asc" ? "desc" : "asc";
                break;

            default:
                this.sortBy = field;
                this.sortDir = "asc";
                break;
        }

        this.fetchData();
    }

    /**
     * ========================================================
     * SEARCH HANDLER (DEBOUNCE)
     * ========================================================
     */
    handleSearch(value) {

        clearTimeout(this.searchTimeout);

        this.searchTimeout = setTimeout(() => {

            this.searchQuery = value;

            this.currentPage = 1;

            this.fetchData();

        }, 500);
    }

    /**
     * ========================================================
     * CHANGE PAGE
     * ========================================================
     */
    changePage(page) {

        switch (true) {

            case page < 1:
            case page > this.totalPages:
                return;

            default:
                this.currentPage = page;
                this.fetchData();
                break;
        }
    }

    /**
     * ========================================================
     * ACTION HANDLER
     * ========================================================
     */
    handleAction(action, id) {

        switch (action) {

            case "edit":
                $(document).trigger("table:edit", id);
                break;

            case "delete":
                this.deleteRow(id);
                break;

            default:
                console.warn("Action tidak dikenali:", action);
                break;
        }
    }

    /**
     * ========================================================
     * DELETE
     * ========================================================
     */
    deleteRow(id) {

        this.ajax.request({

            data: {
                module: this.state.module,
                action: "delete",
                tbl: this.state.tbl,
                id_row: id
            },

            success: () => {
                this.fetchData();
            }

        });
    }

    /**
     * ========================================================
     * DESTROY
     * ========================================================
     */
    destroy() {

        $(document).off("click", `${this.container} th[data-sort]`);
        $(document).off("click", `${this.container} [data-page]`);
        $(document).off("input", "#table-search");
        $(document).off("change", "#table-limit");
        $(document).off("click", `${this.container} [data-action]`);

        $(this.container).empty();

        this.data = [];

        this.isInitialized = false;
    }

}