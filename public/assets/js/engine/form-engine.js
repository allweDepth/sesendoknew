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
		console.log("FORM INIT");
		this.isInitialized = true;

		this.bindEvents();

		this.initFomanticValidation();

		this.loadDropdownSources();
		// GLOBAL HEADER FLYOUT
		this.setFlyoutHeader(this.state.tbl);
	}

	/**
	 * ============================================================
	 * LOAD DATA EDIT
	 * ============================================================
	 * - Ambil data dari server
	 * - Isi ke form
	 */
	loadData(id) {
		console.log("LOAD DATA", id);
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

			if (field.attr("type") === "checkbox") {
				field.prop("checked", data[key] == 1);
				return;
			}

			if (field.closest(".ui.dropdown").length) {
				field.closest(".ui.dropdown").dropdown("set selected", data[key]);
				return;
			}

			if (field.closest(".ui.calendar").length) {
				const calendar = field.closest(".ui.calendar");
				const type = calendar.calendar("get type");

				let value = data[key];

				// AUTO YEAR DETECT
				if (type === "year" && /^\d{4}$/.test(value)) {
					value = new Date(value, 0, 1);
				}

				calendar.calendar("set date", value);
				return;
			}

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
	/**
	 * ============================================================
	 * SUBMIT FORM
	 * ============================================================
	 */
	submit() {
		const config = UIConfig[this.state.tbl];

		// Validasi tetap jalan
		const form = $(this.formSelector);

		form.form("validate form");

		if (!form.form("is valid")) {
			return;
		}
		// normalisasi calendar kosong → null
		form.find(".ui.calendar input").each(function () {
			if ($(this).val() === "") {
				// disable supaya tidak ikut serialize
				$(this).prop("disabled", true);
			}
		});
		const hasFileInput =
			$(this.formSelector).find('input[type="file"]').length > 0;

		// ==============================
		// 🔥 IMPORT FILE (FormData)
		// ==============================
		if (hasFileInput) {
			const formElement = document.querySelector(this.formSelector);
			const formData = new FormData(formElement);

			formData.append("action", this.state.action);
			formData.append("tbl", this.state.tbl);

			this.ajax.request({
				data: formData,
				processData: false, // WAJIB
				contentType: false, // WAJIB
				success: () => {
					$(document).trigger("form:success");
				},
			});

			return;
		}

		// ==============================
		// 🔥 NORMAL SUBMIT
		// ==============================
		let formData = $(this.formSelector).serialize();

		formData += `&action=${this.state.action}`;
		formData += `&tbl=${this.state.tbl}`;

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
	static render(target, elements = [], instance = null, layout = {}) {
		const columnMap = {
			1: "one",
			2: "two",
			3: "three",
			4: "four",
		};

		const columns = layout.columns || 1;
		const columnClass = columnMap[columns] || "one";

		let html = `
    <div class="ui ${columnClass} column grid">
`;

		elements.forEach((el) => {
			// 🔥 JANGAN bungkus alert/progress/divider
			if (["alert", "progress", "divider"].includes(el.tag)) {
				html += `
        <div class="16 wide column">
            ${this.element(el)}
        </div>
    `;
				return;
			}

			const widthClass = el.prop?.width
				? `${el.prop.width} wide column`
				: "column";

			html += `
        <div class="${widthClass}">
            ${this.element(el)}
        </div>
    `;
		});

		html += `</div><div class="ui error message"></div>`;

		$(target).html(html);

		$(target).find(".ui.dropdown").dropdown();
		// 🔥 APPLY DEFAULT VALUE
		elements.forEach((el) => {
			if (el.prop?.default) {
				const field = $(target).find(`[name="${el.prop.name}"]`);
				if (field.closest(".ui.dropdown").length) {
					field
						.closest(".ui.dropdown")
						.dropdown("set selected", el.prop.default);
				} else {
					field.val(el.prop.default);
				}
			}
		});
		$(target).find(".ui.checkbox").checkbox();

		// init calendar per form
		if (instance) {
			instance.initCalendars(target);
		}
		// 🔥 WAJIB INI
		const rangeElements = elements.filter((e) => e.tag === "rangeCalendar");
		UIComponents.initRange(rangeElements);
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
		console.log("PERMISSION ENGINE:", typeof PermissionEngine);
		console.log("USER ROLE:", window.USER_ROLE);
		const { tag, prop = {} } = el;
		console.log("RENDER TAG:", tag);
		if (prop.roles && !PermissionEngine.allow(window.USER_ROLE, prop.roles)) {
			return "";
		}
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
			case "rangeCalendar":
				return UIComponents.rangeCalendar(
					prop.nameStart,
					prop.nameEnd,
					prop.label,
					prop.calendarType || "datetime",
				);
			case "divider":
				return `
                    <h4 class="ui dividing header">
                        ${prop.label || ""}
                    </h4>
                `;
			case "fieldMessage":
				return UIComponents.message(prop);
			case "alert":
				return UIComponents.alert(prop);

			case "progress":
				return UIComponents.progress(prop);
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
        <div class="field ${prop.classField || ""}">
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
	 * CALENDAR FIELD
	 * ============================================================
	 * - Membuat komponen calendar Fomantic
	 * - Menyimpan type calendar di attribute data
	 * - Agar formatter JS tahu format yang harus digunakan
	 *
	 * supported type:
	 * date | datetime | year | time
	 */
	static calendar(prop) {
		return `
        <div class="ui calendar"
             data-calendar-type="${prop.calendarType || "date"}">

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

	/**
	 * ============================================================
	 * LOAD SEMUA DROPDOWN YANG PUNYA data-source
	 * ============================================================
	 */
	loadDropdownSources() {
		console.log("LOAD DROPDOWN START");
		const self = this;

		$(`${this.formSelector} .ui.dropdown[data-source]`).each(function () {
			const $dropdown = $(this);
			const source = $dropdown.data("source");

			if (!source) return;

			self.ajax.request({
				data: {
					module: source,
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
	renderConfig(config) {
		let html = "";

		config.elements.forEach((element) => {
			html += UIComponentRegistry.render(element);
		});

		$(this.formSelector).html(html);

		UIComponents.initAll();
	}
	renderFromConfig(config) {
		let html = '<div class="ui form"><div class="ui stackable grid">';

		config.elements.forEach((element) => {
			if (element.tag === "rangeCalendar") {
				html += UIComponentRegistry.render(element);
			} else {
				html += `
                <div class="sixteen wide column">
                    ${UIComponentRegistry.render(element)}
                </div>
            `;
			}
		});

		html += "</div></div>";

		$(this.formSelector).html(html);

		UIComponents.initAll();

		const rangeNames = config.elements
			.filter((e) => e.tag === "rangeCalendar")
			.map((e) => e.prop.name);

		UIComponents.initRange(rangeNames);
	}
	/**
	 * ============================================================
	 * APPLY READONLY MODE
	 * ============================================================
	 */
	applyReadonly(isReadonly = false) {
		if (!isReadonly) return;

		const form = $(this.formSelector);

		// Disable semua input & textarea
		form.find("input, textarea").attr("disabled", true);

		// Disable dropdown fomantic
		form.find(".ui.dropdown").addClass("disabled");

		// Disable checkbox
		form.find(".ui.checkbox").addClass("disabled");
	}
	/**
	 * ============================================================
	 * BUILD FOMANTIC RULES DARI UICONFIG
	 * ============================================================
	 */
	/**
	 * ============================================================
	 * BUILD RULE VALIDATION FOMANTIC
	 * ============================================================
	 * - membaca schema dari UIConfig
	 * - membuat rule Fomantic
	 * - label diambil dari <label> jika ada
	 * - fallback dari name jika label tidak ada
	 */
	buildFomanticRules(schema = {}) {
		const fields = {};

		Object.keys(schema).forEach((name) => {
			const cfg = schema[name] || {};
			const rules = [];

			// cari field di DOM
			const field = $(`${this.formSelector} [name="${name}"]`);

			// cari label jika ada
			const labelElement = field.closest(".field").find("label");

			let label;

			if (labelElement.length) {
				label = labelElement.text().trim();
			} else {
				// fallback dari name field
				label = name
					.replace(/_/g, " ")
					.replace(/\b\w/g, (c) => c.toUpperCase());
			}

			// required rule
			if (cfg.required) {
				rules.push({
					type: "empty",
					prompt: `${label} wajib diisi`,
				});
			}

			// email rule
			if (cfg.email) {
				rules.push({
					type: "email",
					prompt: `${label} tidak valid`,
				});
			}

			// number rule
			if (cfg.number) {
				rules.push({
					type: "number",
					prompt: `${label} harus berupa angka`,
				});
			}

			fields[name] = {
				identifier: name,
				rules: rules,
			};
		});

		return fields;
	}
	/**
	 * ============================================================
	 * INIT FOMANTIC FORM VALIDATION
	 * ============================================================
	 * - membaca schema dari UIConfig
	 * - menampilkan inline error
	 * - mengisi error summary
	 * - scroll ke field error pertama
	 */
	initFomanticValidation() {
		const config = UIConfig[this.state.tbl];

		if (!config?.validation) return;

		const fields = this.buildFomanticRules(config.validation);

		$(this.formSelector).form({
			inline: true,
			on: "blur",
			fields: fields,

			/**
			 * ========================================================
			 * VALIDATION FAILED
			 * ========================================================
			 */
			onFailure: function (errors) {
				const form = $(this);

				const errorBox = form.find(".ui.error.message");

				if (errorBox.length) {
					let html = '<ul class="list">';

					errors.forEach((err) => {
						html += `<li>${err}</li>`;
					});

					html += "</ul>";

					errorBox.html(html).show();
				}

				// scroll ke field error pertama
				const firstError = form.find(".field.error").first();

				if (firstError.length) {
					$("html, body").animate(
						{
							scrollTop: firstError.offset().top - 120,
						},
						300,
					);
				}
			},

			/**
			 * ========================================================
			 * VALIDATION SUCCESS
			 * ========================================================
			 */
			onSuccess: function () {
				$(this).find(".ui.error.message").hide().empty();
			},
		});
	}
	/**
	 * ============================================================
	 * INIT CALENDAR PER FORM
	 * ============================================================
	 * - Tidak menggunakan selector global
	 * - Membaca data-calendar-type
	 * - Formatter otomatis sesuai tipe
	 */
	initCalendars(container) {
		const pad = (n) => String(n).padStart(2, "0");

		$(container)
			.find(".ui.calendar")
			.each(function () {
				const cal = $(this);

				const type = cal.data("calendar-type") || "date";

				cal.calendar({
					type: type,

					formatter: {
						date: function (date) {
							if (!date) return "";

							const year = date.getFullYear();
							const month = pad(date.getMonth() + 1);
							const day = pad(date.getDate());

							const hour = pad(date.getHours());
							const minute = pad(date.getMinutes());
							const second = pad(date.getSeconds());

							switch (type) {
								case "year":
									return `${year}`;

								case "datetime":
									return `${year}-${month}-${day} ${hour}:${minute}:${second}`;

								case "time":
									return `${hour}:${minute}:${second}`;

								default:
									return `${year}-${month}-${day}`;
							}
						},
					},
				});
			});
	}
	setFlyoutHeader(tbl) {
		const config = UIConfig?.[tbl];

		if (!config) return;

		const title = config.title || "Form Data";
		const icon = config.icon || "folder";

		$("#content_flyout").text(title);

		$("#icon_flyout").attr("class", "").addClass(`${icon} icon`);
	}
}
