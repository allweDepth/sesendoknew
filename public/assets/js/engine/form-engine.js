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
		this.initCascadeDropdown(); // ← aktifkan cascade dropdown
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
				module: this.state.module || this.state.tbl,
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

			// ======================================================
			// FIX: pastikan dropdown sudah memiliki item sebelum set selected
			// ======================================================
			if (field.closest(".ui.dropdown").length) {
				const dropdown = field.closest(".ui.dropdown");

				// jika dropdown belum memiliki item menu
				if (dropdown.find(".menu .item").length === 0) {
					// tunggu sebentar sampai dropdown selesai load dari AJAX
					setTimeout(() => {
						dropdown.dropdown("set selected", data[key]);
					}, 100);
				} else {
					// normal jika item sudah ada
					dropdown.dropdown("set selected", data[key]);
				}

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
	 * BIND SUBMIT EVENT
	 * ============================================================
	 * Fungsi:
	 * - mengikat submit form
	 * - menggunakan event namespace agar aman di SPA
	 * - mencegah double binding
	 */
	bindEvents() {
		const eventName = `submit.formEngine.${this.state.tbl}`;

		// hapus hanya event form ini
		$(document).off(eventName);

		// bind event submit form
		$(document).on(eventName, this.formSelector, (e) => {
			// cegah submit default browser
			e.preventDefault();

			// jalankan submit engine
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
		// ambil elemen form dari selector yang disimpan engine
		const form = $(this.formSelector);

		// jalankan validasi Fomantic UI
		form.form("validate form");

		// jika form tidak valid → hentikan proses submit
		if (!form.form("is valid")) {
			return;
		}

		// normalisasi calendar kosong → null
		form.find(".ui.calendar input").each(function () {
			// jika input kosong
			if ($(this).val() === "") {
				// disable agar tidak ikut serialize
				$(this).prop("disabled", true);
			}
		});

		// cek apakah form memiliki input file
		const hasFileInput = form.find('input[type="file"]').length > 0;

		// ==================================================
		// MODE IMPORT FILE (FormData)
		// ==================================================

		if (hasFileInput) {
			// ambil element form native
			const formElement = document.querySelector(this.formSelector);

			// buat FormData
			const formData = new FormData(formElement);

			// tambahkan action
			formData.append("action", this.state.action);

			// tambahkan nama tabel
			formData.append("tbl", this.state.tbl);

			// kirim request ajax
			this.ajax.request({
				data: formData,

				processData: false, // wajib untuk FormData

				contentType: false, // wajib untuk FormData

				success: () => {
					// tentukan tabel yang harus reload
					const reloadTable = this.state.reloadTable || this.state.tbl;

					// trigger event reload table
					$(document).trigger(`form:success.${reloadTable}`);
				},
			});

			return;
		}

		// ==================================================
		// MODE NORMAL SUBMIT
		// ==================================================

		// serialize form menjadi query string
		let formData = form.serialize();

		// tambahkan action
		formData += `&action=${this.state.action}`;

		// tambahkan nama tabel
		formData += `&tbl=${this.state.tbl}`;

		// kirim request ajax
		this.ajax.request({
			data: formData,

			success: () => {
				// tentukan tabel yang harus reload
				const reloadTable = this.state.reloadTable || this.state.tbl;

				// trigger reload table
				$(document).trigger(`form:success.${reloadTable}`);
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
	 * Fungsi:
	 * - membuat HTML form dari UIConfig
	 * - menginisialisasi komponen UI
	 * - memanggil init() otomatis jika instance tersedia
	 */
	static render(target, elements = [], instance = null, layout = {}) {
		// mapping jumlah kolom grid
		const columnMap = {
			1: "one",
			2: "two",
			3: "three",
			4: "four",
		};

		// ambil jumlah kolom dari layout
		const columns = layout.columns || 1;

		// tentukan class grid
		const columnClass = columnMap[columns] || "one";

		// html awal grid
		let html = `<div class="ui form"><div class="ui ${columnClass} column grid">`;

		// loop setiap element UIConfig
		elements.forEach((el) => {
			// element khusus tidak dibungkus kolom
			if (["alert", "progress", "divider"].includes(el.tag)) {
				html += `
			<div class="16 wide column">
				${this.element(el)}
			</div>
			`;

				return;
			}

			// jika element punya width
			const widthClass = el.prop?.width
				? `${el.prop.width} wide column`
				: "column";

			html += `
		<div class="${widthClass}">
			${this.element(el)}
		</div>
		`;
		});

		// tutup grid + error message fomantic
		html += `</div><div class="ui error message"></div></div>`;

		// render ke DOM
		$(target).html(html);

		// init dropdown fomantic
		$(target).find(".ui.dropdown").dropdown();

		/**
		 * =====================================================
		 * APPLY DEFAULT VALUE
		 * =====================================================
		 */
		elements.forEach((el) => {
			if (!el.prop?.default) return;

			const field = $(target).find(`[name="${el.prop.name}"]`);

			// ======================================================
			// FIX: pastikan dropdown memiliki item sebelum set default
			// ======================================================
			if (field.closest(".ui.dropdown").length) {
				const dropdown = field.closest(".ui.dropdown");

				// jika dropdown belum memiliki item
				if (dropdown.find(".menu .item").length === 0) {
					// tunggu dropdown diisi oleh loadDropdownSources()
					setTimeout(() => {
						dropdown.dropdown("set selected", el.prop.default);
					}, 100);
				} else {
					dropdown.dropdown("set selected", el.prop.default);
				}
			} else {
				field.val(el.prop.default);
			}
		});

		// init checkbox fomantic
		$(target).find(".ui.checkbox").checkbox();

		/**
		 * =====================================================
		 * INIT CALENDAR
		 * =====================================================
		 */
		if (instance) {
			instance.initCalendars(target);
		}

		/**
		 * =====================================================
		 * RANGE CALENDAR
		 * =====================================================
		 */
		const rangeElements = elements.filter((e) => e.tag === "rangeCalendar");

		UIComponents.initRange(rangeElements);

		/**
		 * =====================================================
		 * GLOBAL INIT FORM ENGINE
		 * =====================================================
		 * Guard penting agar init tidak dipanggil dua kali
		 * karena flyout-controller juga bisa memanggil init()
		 */
		if (instance && !instance.isInitialized) {
			instance.init();
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
	 * DROPDOWN FIELD BUILDER
	 * ============================================================
	 * Fungsi:
	 * - membuat komponen dropdown Fomantic UI
	 * - mendukung dropdown statis (options)
	 * - mendukung dropdown AJAX (data-source)
	 * - mendukung cascade dropdown (data-parent)
	 * ============================================================
	 */
	static dropdown(prop) {
		/**
		 * ======================================================
		 * STATIC OPTIONS
		 * ======================================================
		 * Digunakan jika dropdown memiliki opsi statis
		 * (tidak menggunakan AJAX)
		 */
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

		/**
		 * ======================================================
		 * SEARCH MODE
		 * ======================================================
		 * Jika prop.search = true
		 * maka aktifkan Fomantic search dropdown
		 */
		const searchClass = prop.search ? "search" : "";

		/**
		 * ======================================================
		 * RETURN HTML DROPDOWN
		 * ======================================================
		 * data-source       → module sumber dropdown
		 * data-parent       → nama field parent di form
		 * data-parent-field → nama kolom parent di database
		 */
		return `
		<div class="ui ${searchClass} selection dropdown ${prop.classInput || ""}"
			 data-source="${prop.source || ""}"
			 data-parent="${prop.parent || ""}"
			 data-parent-field="${prop.parent_field || ""}">

			<!-- VALUE YANG DIKIRIM KE SERVER -->
			<input type="hidden" name="${prop.name}">

			<!-- ICON DROPDOWN -->
			<i class="dropdown icon"></i>

			<!-- PLACEHOLDER TEXT -->
			<div class="default text">
				${prop.placeholder || "Pilih"}
			</div>

			<!-- MENU ITEM -->
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
 * Fungsi:
 * - mengambil data dropdown dari server
 * - hanya dijalankan sekali per dropdown
 * - mencegah AJAX dipanggil berulang di SPA
 */
	loadDropdownSources() {
		console.log("LOAD DROPDOWN START");

		const self = this;

		/**
		 * ======================================================
		 * LOOP SEMUA DROPDOWN YANG MEMILIKI data-source
		 * ======================================================
		 * Dropdown ini biasanya dibuat oleh UIConfig melalui
		 * FormEngine.dropdown()
		 */
		$(`${this.formSelector} .ui.dropdown[data-source]`).each(function () {
			const $dropdown = $(this);

			/**
			 * ======================================================
			 * CEK APAKAH DROPDOWN MEMILIKI PARENT
			 * ======================================================
			 * Jika memiliki parent berarti dropdown ini bagian
			 * dari CASCADE (urusan → bidang → program → kegiatan)
			 *
			 * Maka dropdown ini TIDAK BOLEH load saat init
			 */
			const parent = $dropdown.data("parent");

			if (parent) {
				return; // skip dropdown child
			}

			/**
			 * ======================================================
			 * CEK APAKAH DROPDOWN SUDAH PERNAH LOAD
			 * ======================================================
			 * Digunakan agar AJAX tidak dipanggil berulang
			 * dalam SPA
			 */
			if (
				$dropdown.data("loaded") &&
				$dropdown.find(".menu .item").length > 0
			) {
				return;
			}

			/**
			 * ======================================================
			 * AMBIL SOURCE DATA
			 * ======================================================
			 */
			const source = $dropdown.data("source");

			if (!source) return;

			/**
			 * ======================================================
			 * REQUEST DATA DROPDOWN KE SERVER
			 * ======================================================
			 */
			self.ajax.request({
				data: {
					module: source,
					action: "dropdown",
					tbl: source,
				},

				success: (res) => {
					if (!res.success || !res.data) return;

					const $menu = $dropdown.find(".menu");

					/**
					 * ======================================================
					 * KOSONGKAN MENU DROPDOWN
					 * ======================================================
					 */
					$menu.empty();

					/**
					 * ======================================================
					 * MASUKKAN ITEM DROPDOWN
					 * ======================================================
					 * Sistem backend bisa memiliki struktur berbeda
					 * sehingga label dipilih dari beberapa kemungkinan
					 */
					res.data.forEach((item) => {
						$menu.append(`
						<div class="item" data-value="${item.id}">
							${item.uraian || item.item || item.nama || ""}
						</div>
					`);
					});

					/**
					 * ======================================================
					 * REFRESH DROPDOWN FOMANTIC
					 * ======================================================
					 */
					$dropdown.dropdown("refresh");

					/**
					 * ======================================================
					 * TANDAI DROPDOWN SUDAH LOAD
					 * ======================================================
					 */
					$dropdown.data("loaded", true);
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
	/**
 * ======================================================
 * CASCADE DROPDOWN HANDLER
 * ======================================================
 * Fungsi:
 * - memonitor perubahan dropdown parent
 * - load dropdown child berdasarkan parent
 * - mencegah duplicate request
 * - aman untuk SPA (flyout berkali-kali)
 */
initCascadeDropdown() {

	const self = this;

	/**
	 * ======================================================
	 * HAPUS EVENT SEBELUMNYA
	 * ======================================================
	 * penting agar event tidak menumpuk
	 * saat flyout dibuka berkali-kali
	 */
	$(document).off("change.formCascade");

	/**
	 * ======================================================
	 * EVENT PERUBAHAN DROPDOWN
	 * ======================================================
	 */
	$(document).on(
		"change.formCascade",
		`${this.formSelector} .ui.dropdown[data-source]`,
		function () {

			const $parentDropdown = $(this);

			/**
			 * ======================================================
			 * SKIP JIKA EVENT DIPICU OLEH populateForm()
			 * ======================================================
			 */
			if ($parentDropdown.data("skip-cascade")) return;

			const parentName = $parentDropdown
				.find("input[type=hidden]")
				.attr("name");

			const parentValue = $parentDropdown
				.find("input[type=hidden]")
				.val();

			if (!parentName) return;

			/**
			 * ======================================================
			 * CARI SEMUA CHILD DROPDOWN
			 * ======================================================
			 */
			$(`${self.formSelector} .ui.dropdown[data-parent="${parentName}"]`)
			.each(function () {

				const $child = $(this);

				const source = $child.data("source");

				if (!source) return;

				/**
				 * ======================================================
				 * RESET CHILD
				 * ======================================================
				 */
				$child.dropdown("clear");

				const $menu = $child.find(".menu");

				$menu.empty();

				/**
				 * ======================================================
				 * JIKA PARENT KOSONG
				 * ======================================================
				 */
				if (!parentValue) return;

				/**
				 * ======================================================
				 * REQUEST DROPDOWN CHILD
				 * ======================================================
				 */
				self.ajax.request({

					data: {

						module: source,

						action: "dropdown",

						tbl: source,

						parent: parentName,

						parent_field: $child.data("parent-field"),

						value: parentValue
					},

					success: (res) => {

						if (!res.success || !res.data) return;

						res.data.forEach((item) => {

							$menu.append(`
								<div class="item" data-value="${item.id}">
									${item.uraian || item.item || item.nama || ""}
								</div>
							`);

						});

						/**
						 * refresh dropdown
						 */
						$child.dropdown("refresh");

					}
				});

			});
		}
	);
}
}
