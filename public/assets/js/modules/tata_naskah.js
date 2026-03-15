/**
 * ============================================================
 * TATA NASKAH MODULE
 * ============================================================
 * Module untuk manajemen tata naskah
 * Tidak mengatur AJAX langsung
 * Tidak mengatur DOM langsung selain container utama
 */

class TataNaskahModule {
	constructor() {
		// Ambil state dan ajax dari global App
		this.state = window.app.state;
		this.ajax = window.app.ajax;

		// Set table aktif default
		this.state.setTable("tata_naskah");

		// Inisialisasi engine
		this.tableManager = null;
		this.formEngine = null;
		this.formContainer = null;

		// Container utama
		this.mainContainer = "#main-content";
		this.formContainerSelector = "#form-container";
	}

	/**
	 * INIT MODULE
	 */
	init() {
		const path = window.location.pathname;

		// jika halaman utama tata_naskah
		if (path === "/tata_naskah") {
			this.renderLayout();
		}

		this.initEngine();

		this.bindEvents();
	}

	/**
	 * RENDER LAYOUT DASAR
	 */
	renderLayout() {
		const html = `
            <div class="ui segment">
                <h3 class="ui header">Tata Naskah</h3>
                <button class="ui primary button" id="btn-add">
                    Tambah Data
                </button>
                <div id="table-container"></div>
                <div id="form-container" style="display:none;"></div>
            </div>
        `;

		$(this.mainContainer).html(html);
	}

	/**
	 * INIT ENGINE
	 */
	initEngine() {
		// Table manager
		this.tableManager = new TableManager({
			state: this.state,
			ajax: this.ajax,
			container: "#table-container",
		});

		// Form container
		this.formContainer = new FormContainerManager({
			container: this.formContainerSelector,
		});

		// Form engine
		this.formEngine = new FormEngine({
			state: this.state,
			ajax: this.ajax,
			formSelector: "#dynamic-form",
		});

		// Jalankan semuanya
		this.tableManager.init();
		this.formContainer.init();
		this.formEngine.init();
	}

	/**
	 * BIND EVENTS
	 */
	bindEvents() {
		$(document).on("click", "#btn-add", () => {
			this.showAddForm();
		});

		$(document).on("form:success", () => {
			this.tableManager.loadData();
		});
	}

	/**
	 * SHOW FORM TAMBAH
	 */
	showAddForm() {
		const html = `
            <form id="dynamic-form" class="ui form">
                <div class="field">
                    <label>Judul</label>
                    <input type="text" name="judul">
                </div>
                <button class="ui green button" type="submit">
                    Simpan
                </button>
            </form>
        `;

		this.formContainer.show(html);
	}

	/**
	 * DESTROY MODULE
	 */
	destroy() {
		this.tableManager.destroy();
		this.formEngine.destroy();
		this.formContainer.destroy();

		$(document).off("click", "#btn-add");
		$(document).off("form:success");

		$(this.mainContainer).empty();
	}
}
