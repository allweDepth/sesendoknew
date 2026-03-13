/* =========================================================
   FLYOUT CONTROLLER (BARU - CLEAN)
   Bertugas:
   - Handle open-form
   - Handle modal / flyout
   - Auto load UIConfig
========================================================= */

class FlyoutController {
	constructor() {
		this.$flyout = null;
		this.$modal = null;
		this.activeContainer = "flyout";
		this.ajax = window.Ajax;
		console.log("FlyoutController CREATED");
		$(document).ready(() => {
			this.$flyout = $("#mainContext .sidebarkanan");
			this.$modal = $("#mainModal");
			this.init();
		});
	}

	init() {
		this.$modal.modal({
			closable: false,
			allowMultiple: true,
			observeChanges: true,
		});

		this.bindEvents();
	}

	bindEvents() {
		const self = this;

		// GLOBAL OPEN FORM
		// =======================================
		// OPEN FORM (ANTI DOUBLE BINDING)
		// =======================================

		$(document).off("click.openForm");

		$(document).on("click.openForm", '[data-ui="open-form"]', function (e) {
			e.preventDefault();
			self.open($(this));
		});

		// SUBMIT BUTTON
		$(document).on("click", ".btnSubmit", function () {
			self.getActiveForm().submit();
		});

		// CLOSE FLYOUT
		$(document).on("click", ".btnFlyoutClose", function () {
			self.$flyout.sidebar("hide");
		});
		/* =====================================================
LISTENER LIMIT NAVBAR
===================================================== */

		$("#countRow").dropdown({
			onChange: () => {
				if (window.tableManager) {
					window.tableManager.currentPage = 1;
					window.tableManager.fetchData();
				}
			},
		});
	}

	getActiveForm() {
		return this.activeContainer === "modal" ? $("#form_modal") : $("#form_flyout");
	}

	open($btn) {
		console.log("OPEN START FLYCONTRILLER", Date.now());

		// ============================================
		// GUARD DOUBLE OPEN
		// ============================================

		if (this.isOpening) return;

		this.isOpening = true;

		setTimeout(() => {
			this.isOpening = false;
		}, 300);

		const action = $btn.data("action");
		const tbl = $btn.data("tbl");
		/* =========================================
			TRACE MODE
			ambil data-req dari tombol
		========================================= */
		/* =========================================
AMBIL req DARI BUTTON JIKA ADA
JIKA TIDAK ADA → PERTAHANKAN STATE MENU
========================================= */

		const state = window.app.state;

		/* =========================================
AMBIL req DARI BUTTON
========================================= */

		state.setTable(tbl);
		state.action = action;

		// ======================================================
		// FLYOUT CONTROLLER TIDAK BOLEH MENGUBAH req
		// req hanya dikontrol oleh menu (BaseCrudModule)
		// ======================================================

		// ambil req dari tombol jika ada
		const req = $btn.data("req");

		// ======================================================
		// JIKA TOMBOL MEMILIKI data-req
		// ======================================================
		// req tidak boleh diubah flyout
		// state.req = req;
		// if (req !== undefined && req !== null) {
		// 	state.req = req; // gunakan req dari tombol
		// }

		/* =========================================
UPDATE req HANYA JIKA ADA
========================================= */

		// gunakan req sebagai module UI jika ada
		let configKey = $btn.data("config") || state.req || tbl;

		if (!configKey) {
			console.warn("Module UI tidak ditemukan untuk:", tbl);
			return;
		}

		const id = $btn.data("id") || null;

		const container = $btn.data("container") || "flyout";

		if (!tbl) return;

		const formSelector = container === "modal" ? "#form_modal" : "#form_flyout";

		// ==============================
		// DISINI INSTANCE FORM DIBUAT
		// ==============================

		this.formEngine = new FormEngine({
			state: state,

			ajax: window.Ajax,

			formSelector: formSelector,
		});

		const config = this.buildConfig(action, configKey);

		/* =========================================
ISI FIELD TIPE UNTUK MODULE MAPPING
========================================= */

		FormEngine.render($(formSelector), config.elements, this.formEngine, config.layout || {});

		/* isi tipe dari state menu */

		/* =========================================================
   AUTO PRIMARY KEY FIELD (ID_ROW)
   ---------------------------------------------------------
   Jika tombol memiliki:
   data-jns="edit"
   data-id="xx"

   maka otomatis dibuat:

   <input type="hidden" name="id_row">

   agar submit edit memiliki primary key
========================================================= */

		if (jns === "edit" && id) {
			const $form = $(formSelector);

			// cek apakah field id_row sudah ada
			const hasField = $form.find('[name="id_row"]').length;

			// jika belum ada maka buat
			if (!hasField) {
				$form.prepend(`
			<input type="hidden"
			       name="id_row"
			       value="${id}">
		`);
			} else {
				// jika sudah ada → update value
				$form.find('[name="id_row"]').val(id);
			}
		}

		if (container === "modal") {
			$("#mainModal").modal("show");
		} else {
			$(".sidebarkanan").sidebar("show");
		}

		if (jns === "edit" && id) {
			this.formEngine.loadData(id);
		}
	}
	buildConfig(jenis, configKey) {
		if (!window.UIConfig) return { elements: [] };

		const keys = configKey.split(".");
		let config = window.UIConfig;

		keys.forEach((k) => {
			if (config) config = config[k];
		});

		if (!config) {
			console.warn("Flyout config tidak ditemukan:", configKey);
			return { elements: [] };
		}

		// =====================================================
		// SUPPORT IMPORT FACTORY
		// =====================================================
		if (jenis === "import" && config.import) {
			config = config.import;
		}

		if (config.form && Array.isArray(config.form.elements)) {
			return {
				elements: config.form.elements,
				layout: config.layout || {},
				validation: config.validation || {},
			};
		}

		return { elements: [] };
	}
}
