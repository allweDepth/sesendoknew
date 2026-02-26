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
		// INIT SIDEBAR
		this.$flyout.sidebar({
    transition: "overlay",
    closable: false,
});

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

		AppState.tbl = tbl;
		AppState.action = jns;

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

		FormEngine.render(target, config.elements);

		if (AppState.action === "edit") {
			target.prepend(`<input type="hidden" name="id">`);
		}

		// LOAD DROPDOWN AUTO
		this.loadDropdowns(target);
	}

	loadData(id) {
		this.ajax.request({
			data: {
				module: AppState.module,
				action: "edit",
				tbl: AppState.tbl,
				id_row: id,
			},
			success: (res) => {
				if (!res.success) return;

				const $form = this.getActiveForm();

				Object.keys(res.data).forEach((key) => {
					const $field = $form.find(`[name="${key}"]`);
					if (!$field.length) return;

					$field.val(res.data[key]);
				});
			},
		});
	}

	buildConfig(jenis, tbl) {
		let elements = UIConfig[AppState.module]?.[tbl] || [];

		return {
			elements: elements,
		};
	}
}
