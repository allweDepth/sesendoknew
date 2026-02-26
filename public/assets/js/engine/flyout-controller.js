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
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

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

		window.app.state.setTable(tbl);
		window.app.state.action = jns;

		this.activeContainer = container;

		const config = this.buildConfig(jns, tbl);

		if (!config.elements.length) {
			ToastEngine.show({
				success: false,
				message: "UIConfig belum tersedia",
			});
			return;
		}

		this.render(config);

		if (container === "modal") {
			this.$modal.modal("show");
		} else {
			this.$flyout.sidebar("show");
		}

		if (jns === "edit" && id) {
			this.loadData(id);
		}
	}

	render(config) {
		const target = this.getActiveForm();
		target.empty();

		// 🔥 PAKAI state yang benar
		const action = window.app.state.action;

		// Kalau FormEngine.render memang ada di sistem kamu
		if (typeof FormEngine.render === "function") {
			FormEngine.render(target, config.elements);
		} else {
			// fallback minimal kalau tidak ada
			config.elements.forEach((el) => {
				if (!el.prop?.name) return;

				target.append(`
				<div class="field">
					<label>${el.prop.label || el.prop.name}</label>
					<input type="text" name="${el.prop.name}">
				</div>
			`);
			});
		}

		// 🔥 PERBAIKI INI
		if (action === "edit") {
			target.prepend(`<input type="hidden" name="id">`);
		}

		// tetap jalankan dropdown loader kalau ada
		if (typeof this.loadDropdowns === "function") {
			this.loadDropdowns(target);
		}
	}

	loadData(id) {

	this.ajax.request({
		method: "POST",
		data: {
			module: window.app.state.module,
			action: "edit",
			tbl: window.app.state.tbl,
			id_row: id
		},
		success: (res) => {

			if (!res.success) return;

			const formSelector =
				this.activeContainer === "modal"
					? "#form_modal"
					: "#form_flyout";

			Object.keys(res.data).forEach((key) => {
				$(`${formSelector} [name="${key}"]`).val(res.data[key]);
			});
		}
	});
}

	buildConfig(jenis, tbl) {
		const module = window.app.state.module;

		let elements =
			window.UIConfig?.[module]?.[tbl] || window.UIConfig?.[tbl] || [];

		return {
			elements: elements,
		};
	}
}
