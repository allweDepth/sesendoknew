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
		$(document).on("click", '[data-ui="open-form"]', function (e) {
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

	const jns = $btn.data("jns");
	const tbl = $btn.data("tbl");
	const id = $btn.data("id") || null;
	const container = $btn.data("container") || "flyout";

	if (!tbl) return;

	const state = window.app.state;
	state.setTable(tbl);
  state.action = jns;

	const formSelector =
		container === "modal"
			? "#form_modal"
			: "#form_flyout";

	// 🔥 BUAT INSTANCE DI SINI
	this.formEngine = new FormEngine({
		state: state,
		ajax: window.Ajax,
		formSelector: formSelector
	});

	this.formEngine.init();

	// 🔥 Render pakai instance
	FormEngine.render(
		$(formSelector),
		this.buildConfig(jns, tbl).elements,
		this.formEngine
	);

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
		const module = window.app.state.module;

		let elements =
			window.UIConfig?.[module]?.[tbl] || window.UIConfig?.[tbl] || [];

		return {
			elements: elements,
		};
	}
}
