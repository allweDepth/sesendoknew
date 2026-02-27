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
	}

	getActiveForm() {
		return this.activeContainer === "modal"
			? $("#form_modal")
			: $("#form_flyout");
	}

	open($btn) {
		console.log("OPEN START FLYCONTRILLER", Date.now());
		const jns = $btn.data("jns");
		const tbl = $btn.data("tbl");
		const id = $btn.data("id") || null;
		const container = $btn.data("container") || "flyout";

		if (!tbl) return;

		const state = window.app.state;
		state.setModule(tbl);
		state.setTable(tbl);
		state.action = jns;

		const formSelector = container === "modal" ? "#form_modal" : "#form_flyout";

		// 🔥 BUAT INSTANCE DI SINI
		this.formEngine = new FormEngine({
			state: state,
			ajax: window.Ajax,
			formSelector: formSelector,
		});
		// 🔥 Render pakai instance/ 🔥 RENDER DULU
		const config = this.buildConfig(jns, tbl);

		FormEngine.render(
			$(formSelector),
			config.elements,
			this.formEngine,
			config.layout || {},
		);
		this.formEngine.init();
		// Show container
		if (container === "modal") {
			$("#mainModal").modal("show");
		} else {
			$(".sidebarkanan").sidebar("show");
		}

		// 🔥 Load edit data
		if (jns === "edit" && id) {
			this.formEngine.loadData(id);
		}
	}

	// render(config) {
	// 	const target = this.getActiveForm();
	// 	target.empty();

	// 	// 🔥 PAKAI state yang benar
	// 	const action = window.app.state.action;

	// 	// Kalau FormEngine.render memang ada di sistem kamu
	// 	if (typeof FormEngine.render === "function") {
	// 		FormEngine.render(target, config.elements, formEngineInstance);
	// 	} else {
	// 		// fallback minimal kalau tidak ada
	// 		config.elements.forEach((el) => {
	// 			if (!el.prop?.name) return;

	// 			target.append(`
	// 			<div class="field">
	// 				<label>${el.prop.label || el.prop.name}</label>
	// 				<input type="text" name="${el.prop.name}">
	// 			</div>
	// 		`);
	// 		});
	// 	}

	// 	// 🔥 PERBAIKI INI
	// 	if (action === "edit") {
	// 		target.prepend(`<input type="hidden" name="id">`);
	// 	}

	// 	// tetap jalankan dropdown loader kalau ada
	// 	if (typeof this.loadDropdowns === "function") {
	// 		this.loadDropdowns(target);
	// 	}
	// }

	buildConfig(jenis, tbl) {
		if (!window.UIConfig) return { elements: [] };

		let config = window.UIConfig[tbl];

		// 🔥 FALLBACK UNTUK STRUKTUR NESTED LAMA
		if (!config) {
			Object.keys(window.UIConfig).forEach((parentKey) => {
				const parent = window.UIConfig[parentKey];
				if (parent && parent[tbl]) {
					config = parent[tbl];
				}
			});
		}

		if (!config) {
			console.warn("Flyout config tidak ditemukan:", tbl);
			return { elements: [] };
		}

		// 🔥 STRUCTURE BARU (FLAT)
		if (config.form && Array.isArray(config.form.elements)) {
			return {
				elements: config.form.elements,
				layout: config.layout || {},
				validation: config.validation || {},
			};
		}

		// 🔥 STRUCTURE LAMA (ARRAY)
		if (Array.isArray(config)) {
			return { elements: config };
		}

		return { elements: [] };
	}
}
