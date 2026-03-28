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
			// ================================
			// FIX: CEGAH MODAL TUTUP SAAT SUBMIT
			// ================================
			onApprove: function () {
				return false; //wajib: blok auto close
			},
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
			// 🔥 FIX: BYPASS CUSTOM MODULE
			if ($(this).data("custom-form") === true) {
				// 🔥 FIX: trigger manual ke module
				const id = $(this).data("id");
				const moduleName = $(this).data("tbl");

				// khusus tata_naskah
				if (moduleName === "trx_naskah_dinas" && id) {
					// cari instance module
					if (window.app?.modules?.tata_naskah) {
						window.app.modules.tata_naskah.loadSchema(null, id);
					}
				}

				return;
			}

			e.preventDefault();
			self.open($(this));
		});

		// ======================================================
		// SUBMIT BUTTON
		// ======================================================

		// sebelum
		// self.getActiveForm().submit();

		$(document).on("click", ".btnSubmit", function (e) {
			// intercept klik tombol

			e.preventDefault(); // cegah submit native

			const form = self.getActiveForm(); // ambil form aktif

			form.trigger("submit"); // trigger submit event jQuery agar FormEngine menangkap
		});

		// CLOSE FLYOUT
		$(document).on("click", ".btnFlyoutClose", function () {
			self.$flyout.sidebar("hide");
		});
		//=====================================================
		// LISTENER LIMIT NAVBAR
		// =====================================================

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

		// =========================================
		// AMBIL req DARI BUTTON
		// =========================================

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

		//=========================================
		// UPDATE req HANYA JIKA ADA
		// ========================================= */

		// gunakan req sebagai module UI jika ada
		let configKey;

		if (action === "import") {
			configKey = tbl; // import selalu mengikuti tabel
		} else {
			configKey = $btn.data("config") || req || state.req || tbl;
		}

		if (!configKey) {
			console.warn("Module UI tidak ditemukan untuk:", tbl);
			return;
		}

		let id = $btn.data("id") || null; // // ambil dari button

		if (!id) {
			// // jika tidak ada di button
			id = $btn.closest("tr").data("id") || null; // // fallback ke <tr>
		}

		const container = $btn.data("container") || "flyout";

		if (!tbl) return;

		const formSelector = container === "modal" ? "#form_modal" : "#form_flyout";

		// ==============================
		// DISINI INSTANCE FORM DIBUAT
		// ==============================
		// state.id = id; // // 🔥 MASUKKAN ID KE STATE GLOBAL
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
   data-action="edit"
   data-id="xx"

   maka otomatis dibuat:

   <input type="hidden" name="id_row">

   agar submit edit memiliki primary key
========================================================= */

		if ((action === "edit" || action === "edit_json") && id) {
			// // include edit_json
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
			this.activeContainer = "modal"; // // FIX: set container aktif
			$("#mainModal").modal("show");
		} else {
			this.activeContainer = "flyout"; // // FIX: set container aktif
			$(".sidebarkanan").sidebar("show");
		}

		if (action === "edit" && id) {
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
