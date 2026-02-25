/**
 * ============================================================
 * TABLE MANAGER
 * ============================================================
 * Bertanggung jawab atas:
 * - Load data tabel via AJAX
 * - Render tabel
 * - Handle event klik edit / delete
 * - Tidak mengatur form
 */

class TableManager {

    /**
     * Constructor
     * @param {Object} config - konfigurasi tabel
     */
    constructor(config = {}) {

        // Simpan referensi state global
        this.state = config.state;

        // Simpan referensi ajax service
        this.ajax = config.ajax;

        // Container tabel
        this.container = config.container;

        // Data terakhir yang dimuat
        this.currentData = [];

        // Status aktif
        this.isInitialized = false;
    }

    /**
     * INIT TABLE
     */
    init() {

        // Tandai sudah aktif
        this.isInitialized = true;

        // Load data awal
        this.loadData();

        // Bind event klik
        this.bindEvents();
    }

    /**
     * LOAD DATA DARI SERVER
     */
    loadData() {

        this.ajax.request({

            data: {
                module: this.state.module,
                action: "list",
                tbl: this.state.tbl
            },

            success: (res) => {

                // Simpan data ke memory
                this.currentData = res.data;

                // Render tabel
                this.render();

            },

            error: () => {
                console.error("Gagal load tabel.");
            }

        });

    }

    /**
     * RENDER TABLE KE DOM
     */
    render() {

        // Pastikan container ada
        switch (typeof this.container) {

            case "string":
                $(this.container).html(this.buildHTML());
                break;

            case "object":
                $(this.container).html(this.buildHTML());
                break;

            default:
                console.warn("Container tidak valid.");
                break;
        }

    }

    /**
     * BANGUN HTML TABLE
     */
    buildHTML() {

        let html = `<table class="ui celled table">`;

        html += `<thead><tr>`;

        switch (this.currentData.length > 0) {

            case true:
                Object.keys(this.currentData[0]).forEach(key => {
                    html += `<th>${key}</th>`;
                });
                html += `<th>Aksi</th>`;
                break;

            default:
                html += `<th>Tidak ada data</th>`;
                break;
        }

        html += `</tr></thead><tbody>`;

        this.currentData.forEach(row => {

            html += `<tr data-id="${row.id}">`;

            Object.values(row).forEach(val => {
                html += `<td>${val}</td>`;
            });

            html += `
                <td>
                    <button class="ui mini button edit-btn">Edit</button>
                    <button class="ui mini red button delete-btn">Delete</button>
                </td>
            `;

            html += `</tr>`;
        });

        html += `</tbody></table>`;

        return html;
    }

    /**
     * BIND EVENTS TABLE
     */
    bindEvents() {

        $(document).on("click", ".edit-btn", (e) => {

            const id = $(e.target).closest("tr").data("id");

            switch (typeof id) {

                case "number":
                case "string":
                    this.state.setActiveRow(id);
                    $(document).trigger("table:edit", id);
                    break;

                default:
                    break;
            }

        });

        $(document).on("click", ".delete-btn", (e) => {

            const id = $(e.target).closest("tr").data("id");

            this.deleteRow(id);

        });

    }

    /**
     * DELETE ROW
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
                this.loadData();
            }

        });

    }

    /**
     * DESTROY
     */
    destroy() {

        $(document).off("click", ".edit-btn");
        $(document).off("click", ".delete-btn");

        this.isInitialized = false;
    }

}