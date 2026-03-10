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
			onChange: (value) => {
				this.limit = value;
				// update limit table

				this.currentPage = 1;
				// reset halaman

				this.loadData();
				// reload tabel
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

		const jns = $btn.data("jns");
		const tbl = $btn.data("tbl");
		/* =========================================
			TRACE MODE
			ambil data-req dari tombol
		========================================= */
		const req = $btn.data("req") || null;

		const configKey =
			$btn.data("config") || (window.UIConfig[tbl] && window.UIConfig[tbl][jns] ? `${tbl}.${jns}` : tbl);

		const id = $btn.data("id") || null;

		const container = $btn.data("container") || "flyout";

		if (!tbl) return;

		const state = window.app.state;

		state.setTable(tbl);

		state.action = jns;

		/* =========================================
   OVERRIDE TABLE CRUD
========================================= */

		state.req = req;

		/* =========================================
   TRACE: ambil data-req dari tombol
   ========================================= */

		state.req = req;

		const formSelector = container === "modal" ? "#form_modal" : "#form_flyout";

		// ==============================
		// DISINI INSTANCE FORM DIBUAT
		// ==============================

		this.formEngine = new FormEngine({
			state: state,

			ajax: window.Ajax,

			formSelector: formSelector,
		});

		const config = this.buildConfig(jns, configKey);

		/* =====================================================
FIX MAPPING CONTEXT
===================================================== */

		if (window.app?.state?.tbl === "mapping") {
			config.elements.forEach((el) => {
				/* =================================================
		FIELD TIPE
		================================================= */
				if (el.prop && el.prop.name === "tipe") {
					/* isi tipe dari req aktif */
					el.prop.value = window.app.state.req;
				}

				/* =================================================
		SEARCH MASTER BIAYA
		source mengikuti req aktif
		================================================= */
				if (el.tag === "search" && el.prop?.name === "master_biaya_id") {
					/* ubah source dari master_biaya menjadi req */
					el.prop.source = window.app.state.req;
				}
			});
		}

		FormEngine.render($(formSelector), config.elements, this.formEngine, config.layout || {});

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

		// Support nested key (ex: satuan.import)
		const keys = configKey.split(".");
		let config = window.UIConfig;

		keys.forEach((k) => {
			if (config) config = config[k];
		});

		if (!config) {
			console.warn("Flyout config tidak ditemukan:", configKey);
			return { elements: [] };
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
