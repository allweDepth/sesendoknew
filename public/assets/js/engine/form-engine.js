/**
 * ============================================================
 * FORM ENGINE
 * ============================================================
 * Bertanggung jawab:
 * - Load data edit
 * - Submit form
 * - Tidak mengatur container
 * - Render elemen dari UIConfig
 * - Support dropdown, calendar, checkbox, dll
 */

class FormEngine {
	constructor(config = {}) {
		this.state = config.state;
		this.ajax = config.ajax;
		this.formSelector = config.formSelector;

		this.isInitialized = false;
	}

	/**
	 * ============================================================
	 * INIT FORM
	 * ============================================================
	 * - Aktifkan event submit
	 */
	init() {
		this.isInitialized = true;

		this.bindEvents();

		// 🔥 load semua dropdown yang punya source
		this.loadDropdownSources();
	}

	/**
	 * ============================================================
	 * LOAD DATA EDIT
	 * ============================================================
	 * - Ambil data dari server
	 * - Isi ke form
	 */
	loadData(id) {
		this.ajax.request({
			data: {
				module: this.state.module,
				action: "edit",
				tbl: this.state.tbl,
				id_row: id,
			},

			success: (res) => {
				if (!res || !res.data) return;

				this.populateForm(res.data);
			},
		});
	}

	/**
	 * ============================================================
	 * ISI FORM DENGAN DATA
	 * ============================================================
	 */
	populateForm(data) {
		Object.keys(data).forEach((key) => {
			const field = $(`${this.formSelector} [name="${key}"]`);

			if (!field.length) return;

			// Checkbox
			if (field.attr("type") === "checkbox") {
				field.prop("checked", data[key] == 1 || data[key] === true);
				return;
			}

			// Dropdown Fomantic
			if (field.closest(".ui.dropdown").length) {
				field.closest(".ui.dropdown").dropdown("set selected", data[key]);
				return;
			}

			// Default
			field.val(data[key]);
		});
	}

	/**
	 * ============================================================
	 * BIND SUBMIT
	 * ============================================================
	 */
	bindEvents() {
		$(document).off("submit", this.formSelector);
		$(document).on("submit", this.formSelector, (e) => {
			e.preventDefault();

			this.submit();
		});
	}

	/**
	 * ============================================================
	 * SUBMIT FORM
	 * ============================================================
	 */
	submit() {
		const formData = $(this.formSelector).serialize();

		this.ajax.request({
			data: formData,

			success: () => {
				$(document).trigger("form:success");
			},
		});
	}

	/**
	 * ============================================================
	 * DESTROY
	 * ============================================================
	 */
	destroy() {
		$(document).off("submit", this.formSelector);
		$(document).off("table:edit");

		this.isInitialized = false;
	}

	/* ============================================================
       ============================================================
       ================= STATIC FORM BUILDER ======================
       ============================================================
       ============================================================ */

	/**
	 * ============================================================
	 * RENDER FORM KE TARGET
	 * ============================================================
	 */
	static render(target, elements = [], instance = null) {
		const html = this.build(elements);

		target.html(html);

		target.find(".ui.dropdown").dropdown();
		target.find(".ui.checkbox").checkbox();
		target.find(".ui.calendar").calendar();

		// 🔥 Auto load dropdown dari server
		if (instance && typeof instance.loadDropdownSources === "function") {
			instance.loadDropdownSources();
		}
	}

	/**
	 * ============================================================
	 * BUILD MULTIPLE ELEMENT
	 * ============================================================
	 */
	static build(elements = []) {
		return elements.map((el) => this.element(el)).join("");
	}

	/**
	 * ============================================================
	 * BUILD SINGLE ELEMENT
	 * ============================================================
	 */
	static element(el) {
		const { tag, prop = {} } = el;

		switch (tag) {
			case "fieldAction":
				return this.fieldWrapper(this.inputAction(prop), prop);

			case "fieldCalendar":
				return this.fieldWrapper(this.calendar(prop), prop);

			case "cardProfile":
				return this.cardProfile(prop);

			case "field":
				return this.fieldWrapper(this.input(prop), prop);

			case "fieldTextarea":
				return this.fieldWrapper(
					`<textarea name="${prop.name}" ${prop.atribut || ""}></textarea>`,
					prop,
				);

			case "fieldDropdown":
				return this.fieldWrapper(this.dropdown(prop), prop);

			case "fieldCheckbox":
				return `
                    <div class="field ${prop.classField || ""}">
                        <div class="ui checkbox">
                            <input type="checkbox"
                                   name="${prop.name}"
                                   ${prop.atribut || ""}>
                            <label>${prop.label || ""}</label>
                        </div>
                    </div>
                `;

			case "fieldFile":
				return this.fieldWrapper(
					`
                    <div class="ui file input">
                        <input type="file"
                               name="${prop.name}"
                               accept="${prop.accept || ""}">
                    </div>
                    `,
					prop,
				);

			case "fields":
				return `
                    <div class="fields ${prop.classGroup || ""}">
                        ${this.build(prop.children || [])}
                    </div>
                `;

			case "inlineFields":
				return `
                    <div class="inline fields">
                        ${this.build(prop.children || [])}
                    </div>
                `;

			case "divider":
				return `
                    <h4 class="ui dividing header">
                        ${prop.label || ""}
                    </h4>
                `;

			default:
				return "";
		}
	}

	/**
	 * ============================================================
	 * FIELD WRAPPER
	 * ============================================================
	 */
	static fieldWrapper(inner, prop) {
		return `
            <div class="field ${prop.classField || ""} ${prop.width || ""}">
                ${prop.label ? `<label>${prop.label}</label>` : ""}
                ${inner}
            </div>
        `;
	}

	/**
	 * ============================================================
	 * BASIC INPUT
	 * ============================================================
	 */
	static input(prop) {
		return `
            <input type="${prop.type || "text"}"
                   name="${prop.name}"
                   ${prop.atribut || ""}>
        `;
	}

	/**
	 * ============================================================
	 * DROPDOWN
	 * ============================================================
	 */
	static dropdown(prop) {
		let staticOptions = "";

		if (prop.options && prop.options.length) {
			prop.options.forEach((opt) => {
				staticOptions += `
                    <div class="item" data-value="${opt.value}">
                        ${opt.text}
                    </div>
                `;
			});
		}

		return `
            <div class="ui selection dropdown ${prop.classInput || ""}"
                 data-source="${prop.source || ""}">
                <input type="hidden" name="${prop.name}">
                <i class="dropdown icon"></i>
                <div class="default text">${prop.placeholder || "Pilih"}</div>
                <div class="menu">
                    ${staticOptions}
                </div>
            </div>
        `;
	}

	/**
	 * ============================================================
	 * CALENDAR
	 * ============================================================
	 */
	static calendar(prop) {
		return `
            <div class="ui calendar">
                <div class="ui input left icon">
                    <i class="calendar icon"></i>
                    <input type="text"
                           name="${prop.name}"
                           placeholder="${prop.placeholder || "Pilih Tanggal"}">
                </div>
            </div>
        `;
	}

	/**
	 * ============================================================
	 * ACTION INPUT
	 * ============================================================
	 */
	static inputAction(prop) {
		return `
            <div class="ui action input">
                <input type="text"
                       name="${prop.name}"
                       placeholder="${prop.placeholder || ""}">
                <button class="ui button">
                    <i class="${prop.button?.icon || "search"} icon"></i>
                </button>
            </div>
        `;
	}

	/**
	 * ============================================================
	 * CARD PROFILE
	 * ============================================================
	 */
	static cardProfile(prop) {
		return `
            <div class="ui fluid card">
                <div class="content">
                    ${prop.title || ""}
                </div>
            </div>
        `;
	}
	/**
	 * ============================================================
	 * LOAD DROPDOWN DARI SERVER BERDASARKAN data-source
	 * ============================================================
	 */
	/**
	 * ============================================================
	 * LOAD DROPDOWN DARI SERVER BERDASARKAN data-source
	 * ============================================================
	 */
	loadDropdownSources() {
		const self = this;

		$(`${this.formSelector} .ui.dropdown[data-source]`).each(function () {
			const $dropdown = $(this);
			const source = $dropdown.data("source");

			if (!source) return;

			self.ajax.request({
				data: {
					module: self.state.module,
					action: "dropdown", // 🔥 sesuai config server kamu
					tbl: source,
				},

				success: (res) => {
					if (!res.success || !res.data) return;

					const $menu = $dropdown.find(".menu");
					$menu.empty();

					res.data.forEach((item) => {
						$menu.append(`
						<div class="item" data-value="${item.id}">
							${item.uraian}
						</div>
					`);
					});

					$dropdown.dropdown("refresh");
				},
			});
		});
	}
	/**
	 * ============================================================
	 * LOAD SEMUA DROPDOWN YANG PUNYA data-source
	 * ============================================================
	 */
	loadDropdownSources() {
		const self = this;

		$(`${this.formSelector} .ui.dropdown[data-source]`).each(function () {
			const $dropdown = $(this);
			const source = $dropdown.data("source");

			if (!source) return;

			self.ajax.request({
				data: {
					module: self.state.module,
					action: "dropdown",
					tbl: source,
				},

				success: (res) => {
					if (!res.success || !res.data) return;

					const $menu = $dropdown.find(".menu");
					$menu.empty();

					res.data.forEach((item) => {
						$menu.append(`
						<div class="item" data-value="${item.id}">
							${item.uraian}
						</div>
					`);
					});

					$dropdown.dropdown("refresh");
				},
			});
		});
	}
}
