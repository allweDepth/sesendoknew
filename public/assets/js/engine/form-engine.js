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
		this.state = config.state; // state table
		this.ajax = config.ajax; // ajax helper
		this.formSelector = config.formSelector;

		this.isInitialized = false;

		this.isPopulating = false; // flag global untuk populate mode
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
		/* re-init komponen UI setelah render form */
		UIComponents.initSearch();
		UIComponents.initLookupDropdown();
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
				action: "edit",
				tbl: this.state.tbl,
				id_row: id,
				req: this.state.req,
			},

			success: (res) => {
				if (!res || !res.data) return;

				this.populateForm(res.data);
				this.loadDropdownSources();
			},
		});
	}

	/**
	 * ============================================================
	 * POPULATE FORM DARI DATA EDIT
	 * ============================================================
	 * Fungsi:
	 * - mengisi field form berdasarkan response server
	 * - mendukung checkbox
	 * - mendukung dropdown fomantic
	 * - mendukung calendar
	 * - mencegah cascade dropdown saat populate
	 */
	populateForm(data) {
		// =========================================================
		// AKTIFKAN MODE POPULATE
		// =========================================================
		// selama populate berlangsung cascade dropdown dimatikan
		this.isPopulating = true;

		// =========================================================
		// LOOP SEMUA FIELD DATA
		// =========================================================
		Object.keys(data).forEach((key) => {
			// cari field berdasarkan name
			const field = $(`${this.formSelector} [name="${key}"]`);

			// jika field tidak ada di form maka skip
			if (!field.length) return;

			/**
			 * =====================================================
			 * HANDLE CHECKBOX
			 * =====================================================
			 */
			if (field.attr("type") === "checkbox") {
				// jika nilai 1 maka checkbox checked
				field.prop("checked", data[key] == 1);

				return;
			}

			/**
			 * =====================================================
			 * HANDLE DROPDOWN FOMANTIC UI
			 * =====================================================
			 */
			if (field.closest(".ui.dropdown").length) {
				// ambil container dropdown
				const dropdown = field.closest(".ui.dropdown");

				// ==================================================
				// AKTIFKAN FLAG SKIP CASCADE
				// ==================================================
				// aktifkan flag skip cascade
				dropdown.data("skip-cascade", true);

				const value = data[key];

				const hiddenInput = dropdown.find("input[type=hidden]"); // // ambil hidden input

				hiddenInput.val(value); // // set value awal

				// ==================================================
				// FORCE SYNC SET SELECTED (WAJIB)
				// ==================================================
				setTimeout(() => {
					dropdown.dropdown("set selected", value); // // paksa sync setelah item tersedia
				}, 0);

				// hapus flag setelah populate selesai
				setTimeout(() => {
					//
					dropdown.removeData("skip-cascade"); //
				}, 100);

				return;
			}

			/**
			 * =====================================================
			 * HANDLE CALENDAR FOMANTIC UI
			 * =====================================================
			 */
			if (field.closest(".ui.calendar").length) {
				const calendar = field.closest(".ui.calendar");

				const type = calendar.calendar("get type");

				let value = data[key];

				// ==================================================
				// KONVERSI YEAR KE DATE
				// ==================================================
				if (type === "year" && /^\d{4}$/.test(value)) {
					value = new Date(value, 0, 1);
				}

				calendar.calendar("set date", value);

				return;
			}

			/* =====================================================
CEK FIELD READONLY
agar tidak diubah saat populate
===================================================== */
			// jika readonly tetap isi value tetapi jangan ubah readonly
			if (field.is("[readonly]")) {
				field.val(data[key]); // isi data dari server
				return; // tidak perlu proses lain
			}

			/* =====================================================
SET VALUE NORMAL
===================================================== */
			field.val(data[key]);
		});

		// =========================================================
		// MATIKAN MODE POPULATE
		// =========================================================
		// setelah populate selesai cascade dropdown boleh aktif lagi
		setTimeout(() => {
			this.isPopulating = false;
		}, 200);
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

		const form = $(this.formSelector); // ambil form langsung

		// ======================================================
		// HAPUS EVENT LAMA
		// ======================================================

		form.off(eventName);

		// ======================================================
		// BIND EVENT SUBMIT LANGSUNG KE FORM
		// ======================================================

		form.on(eventName, (e) => {
			e.preventDefault(); // cegah submit browser
			e.stopImmediatePropagation(); // cegah handler lain

			this.submit(); // jalankan engine

			return false; // jaga-jaga
		});
	}

	//  * ============================================================
	//  * SUBMIT FORM
	//  * ============================================================

	submit(e) {
		if (e) {
			e.preventDefault(); // // cegah submit default browser
			e.stopImmediatePropagation(); // // cegah event lain
		}

		// ======================================================
		// BLOK JIKA VALIDATION TIDAK TERDEFINISI
		// ======================================================
		if (this.validationDisabled === true) {
			Toast.error("Validation belum dikonfigurasi pada UIConfig"); // // tampilkan toast error

			this.isSubmitting = false; // // reset flag submit
			return; // // hentikan submit
		}

		// ======================================================
		// CEGAH DOUBLE SUBMIT GLOBAL
		// ======================================================
		if (this.isSubmitting === true) {
			return; // // jika sedang submit, hentikan
		}

		// ======================================================
		// AKTIFKAN LOCK SEBELUM VALIDASI
		// ======================================================
		this.isSubmitting = true; // // kunci submit

		const form = $(this.formSelector); // // ambil form

		// ======================================================
		// GUARD FORM SELECTOR (WAJIB)
		// ======================================================
		if (!form.length) {
			console.error("Form selector tidak ditemukan:", this.formSelector); // // debug
			this.isSubmitting = false; // // reset flag
			return;
		}

		// ======================================================
		// VALIDASI FOMANTIC (SINGLE ENGINE)
		// ======================================================
		form.form("validate form"); // // trigger validasi

		if (!form.form("is valid")) {
			this.isSubmitting = false; // // reset jika tidak valid
			return; // // stop submit
		}

		// ======================================================
		// NORMALISASI CALENDAR KOSONG
		// ======================================================
		const disabledCalendars = []; // // simpan input calendar kosong

		form.find(".ui.calendar input").each(function () {
			if ($(this).val() === "") {
				// ==================================================
				// JANGAN DISABLE → BIAR VALIDATION KONSISTEN
				// ==================================================
				// $(this).prop("disabled", true); // ❌ hapus ini

				$(this).val(""); // // pastikan tetap kosong tapi terkirim
			}
		});

		// ======================================================
		// GUNAKAN FORM DATA UNTUK SEMUA MODE
		// ======================================================
		const formElement = document.querySelector(this.formSelector); // // ambil DOM form
		const formData = new FormData(formElement); // // selalu FormData

		// ======================================================
		// TAMBAH STRUKTUR JSON (DOCUMENT BUILDER)
		// ======================================================
		if (window.documentBuilder) {
			const struktur = window.documentBuilder.collectStructure(); // // ambil struktur

			if (Object.keys(struktur).length) {
				formData.append("struktur_json", JSON.stringify(struktur)); // // append JSON
			}
		}

		// ======================================================
		// TAMBAH PARAM CORE
		// ======================================================
		formData.append("action", this.state.action); // // action backend
		formData.append("tbl", this.state.tbl); // // nama tabel

		// ======================================================
		// TAMBAH REQ JIKA ADA
		// ======================================================
		if (this.state.req !== null && this.state.req !== undefined) {
			formData.append("req", this.state.req); // // request tambahan
		}

		// ======================================================
		// FORWARD SEMUA STATE TAMBAHAN
		// ======================================================
		Object.keys(this.state).forEach((key) => {
			if (["action", "tbl", "req"].includes(key)) return; // // skip core

			const val = this.state[key];

			if (val !== null && val !== undefined && typeof val !== "function") {
				formData.append(key, val); // // append state tambahan
			}
		});

		// ======================================================
		// AJAX REQUEST
		// ======================================================
		this.ajax.request({
			data: formData, // // kirim FormData

			processData: false, // // WAJIB untuk FormData
			contentType: false, // // WAJIB untuk FormData

			success: () => {
				this.isSubmitting = false; // // reset flag submit

				// ==================================================
				// AKTIFKAN KEMBALI CALENDAR YANG TADI DISABLE
				// ==================================================
				disabledCalendars.forEach((el) => {
					$(el).prop("disabled", false); // // restore input
				});

				const reloadTable = this.state.reloadTable || this.state.tbl; // // tentukan table reload

				$(document).trigger(`form:success.${reloadTable}.table`); // // trigger refresh table
			},

			error: () => {
				this.isSubmitting = false; // // reset flag submit

				// ==================================================
				// RESTORE CALENDAR JUGA SAAT ERROR
				// ==================================================
				disabledCalendars.forEach((el) => {
					$(el).prop("disabled", false); // // restore input
				});
			},
		});
	}

	/**
	 * ============================================================
	 * DESTROY
	 * ============================================================
	 */
	destroy() {
		// ==========================================================
		// HAPUS SEMUA EVENT SUBMIT YANG MEMILIKI NAMESPACE formEngine
		// ==========================================================
		// ini wajib agar event submit tidak terdaftar lebih dari sekali
		$(document).off(`submit.formEngine.${this.state.tbl}`, this.formSelector);

		// ==========================================================
		// HAPUS EVENT EDIT TABLE
		// ==========================================================
		$(document).off("table:edit");

		// ==========================================================
		// RESET STATUS ENGINE
		// ==========================================================
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
		let html = `<div class="ui ${columnClass} column grid">`; // ✅ hilangkan wrapper

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
			const widthClass = el.prop?.width ? `${el.prop.width} wide column` : "column";

			html += `
		<div class="${widthClass}">
			${this.element(el)}
		</div>
		`;
		});

		// tutup grid + error message fomantic
		html += `</div><div class="ui error message"></div>`; // ✅ tanpa wrapper form

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
				return this.fieldWrapper(`<textarea name="${prop.name}" ${prop.atribut || ""}></textarea>`, prop);

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
				return UIComponents.rangeCalendar(prop.nameStart, prop.nameEnd, prop.label, prop.calendarType || "datetime");
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
			/* ======================================================
SEARCH FIELD (FOMANTIC SEARCH)
====================================================== */

			case "search":
				return UIComponents.search(prop.label, prop.name, prop.source);

			/* ======================================================
			SEARCH DROPDOWN
			====================================================== */

			case "searchDropdown":
				return UIComponents.searchDropdown(prop.label, prop.name);
			case "lookupDropdown":
				return UIComponents.lookupDropdown(prop.label, prop.name, prop.source);
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
		 * data-source
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
	/**
	 * ============================================================
	 * LOAD DROPDOWN DARI SERVER BERDASARKAN data-source
	 * ============================================================
	 * Fungsi:
	 * - load dropdown root (tanpa parent)
	 * - mencegah duplicate AJAX
	 * - aman untuk SPA
	 */
	loadDropdownSources() {
		const self = this; // simpan referensi instance

		$(`${self.formSelector} .ui.dropdown[data-source]`).each(function () {
			const $dropdown = $(this); // dropdown saat ini

			const parent = $dropdown.data("parent"); // ambil parent dropdown

			// ======================================================
			// PERBAIKAN ENGINE
			// ======================================================
			// child dropdown hanya skip jika parent belum dipilih
			// tetapi tetap boleh load saat ADD untuk root dropdown

			if (parent) {
				const parentField = `${self.formSelector} [name="${parent}"]`;
				const parentValue = $(parentField).val();

				if (!parentValue && !self.isPopulating) {
					return;
				}
			}

			// ======================================================
			// GUARD SUDAH LOAD
			// ======================================================

			if ($dropdown.data("loaded") === true && !self.isPopulating) return; // sudah load

			// ======================================================
			// GUARD SEDANG LOADING
			// ======================================================

			if ($dropdown.data("loading") === true) return; // sedang loading

			// ======================================================
			// SET STATUS LOADING
			// ======================================================

			$dropdown.data("loading", true); // tandai loading

			const source = $dropdown.data("source"); // nama tabel dropdown

			if (!source) {
				$dropdown.data("loading", false); // reset loading
				return;
			}

			const currentValue = $dropdown.find("input[type='hidden']").val();

			let requestValue = currentValue; // ambil value edit
			if (parent) {
				const parentField = `${self.formSelector} [name="${parent}"]`;
				requestValue = $(parentField).val();
			}

			// ======================================================
			// REQUEST DROPDOWN
			// ======================================================

			self.ajax.request({
				data: {
					action: "dropdown", // action backend

					tbl: source, // nama tabel

					value: requestValue, // value edit
				},

				success: (res) => {
					const $menu = $dropdown.find(".menu"); // menu dropdown

					$menu.empty(); // kosongkan menu

					if (res && res.success && res.data) {
						res.data.forEach((item) => {
							$menu.append(`
							<div class="item" data-value="${item.value}">
								${item.text}
							</div>
						`);
						});
					}

					$dropdown.dropdown("refresh");

					if (currentValue) {
						setTimeout(() => {
							$dropdown.dropdown("set selected", currentValue);
						}, 0);
					}

					$dropdown.data("loaded", true); // tandai loaded

					$dropdown.data("loading", false); // reset loading
				},

				error: () => {
					$dropdown.data("loading", false); // reset loading
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

		const rangeNames = config.elements.filter((e) => e.tag === "rangeCalendar").map((e) => e.prop.name);

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
		const fields = {}; // // container rule fomantic

		Object.keys(schema).forEach((name) => {
			const cfg = schema[name] || {}; // // config field
			const rules = []; // // list rule

			// ======================================================
			// AMBIL FIELD DOM
			// ======================================================
			const field = $(`${this.formSelector} [name="${name}"]`); // // cari input berdasarkan name

			if (!field.length) return; // // skip jika tidak ada di DOM

			// ======================================================
			// AMBIL LABEL
			// ======================================================
			const labelElement = field.closest(".field").find("label"); // // cari label terdekat

			let label;

			if (labelElement.length) {
				label = labelElement.text().trim(); // // gunakan label asli
			} else {
				label = name.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()); // // fallback
			}

			// ======================================================
			// DETEKSI TIPE FIELD
			// ======================================================
			const isDropdown = field.closest(".ui.dropdown").length > 0; // // dropdown fomantic
			const isCalendar = field.closest(".ui.calendar").length > 0; // // calendar fomantic

			// ======================================================
			// REQUIRED RULE (PERBAIKAN DROPDOWN & CALENDAR)
			// ======================================================
			if (cfg.required) {
				if (isDropdown) {
					// ==================================================
					// DROPDOWN → VALIDASI HARUS CEK HIDDEN INPUT VALUE
					// ==================================================
					rules.push({
						type: "empty",
						prompt: `${label} wajib dipilih`,
					});
				} else if (isCalendar) {
					// ==================================================
					// CALENDAR → PAKAI REGEX AGAR TIDAK LOLOS STRING KOSONG
					// ==================================================
					rules.push({
						type: "regExp[/^.+$/]",
						prompt: `${label} wajib diisi`,
					});
				} else {
					// ==================================================
					// FIELD NORMAL
					// ==================================================
					rules.push({
						type: "empty",
						prompt: `${label} wajib diisi`,
					});
				}
			}

			// ======================================================
			// EMAIL RULE
			// ======================================================
			if (cfg.email) {
				rules.push({
					type: "email",
					prompt: `${label} tidak valid`,
				});
			}

			// ======================================================
			// NUMBER RULE
			// ======================================================
			if (cfg.number) {
				rules.push({
					type: "number",
					prompt: `${label} harus berupa angka`,
				});
			}

			// ======================================================
			// FINAL ASSIGN
			// ======================================================
			fields[name] = {
				identifier: name, // // mapping ke name input
				rules: rules, // // daftar rule
			};
		});

		return fields; // // return ke fomantic form()
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
		// ============================================================
		// AMBIL KONFIG VALIDASI DARI UICONFIG
		// ============================================================

		const configKey = this.state.req || this.state.tbl;

		// fallback jika tidak ada config
		const config =
			UIConfig[configKey] ||
			UIConfig[this.state.tbl] ||
			UIConfig[this.state.req] || // ← TAMBAHAN WAJIB
			{};

		// jika tidak ada schema validation → hentikan
		// ============================================================
		// VALIDATION WAJIB ADA
		// ============================================================
		if (!config?.validation) {
			Toast.error(`Validation tidak ditemukan untuk ${configKey}`); // // tampilkan error ke user

			this.validationDisabled = true; // // blok submit

			return; // // hentikan init
		}

		// ============================================================
		// BANGUN RULE VALIDATION
		// ============================================================
		const fields = this.buildFomanticRules(config.validation);

		// ============================================================
		// INISIALISASI VALIDATION FOMANTIC
		// ============================================================
		$(this.formSelector).form({
			// validasi inline
			inline: true,

			// trigger validasi saat blur
			on: "blur",

			// rule field
			fields: fields,

			// ========================================================
			// VALIDATION FAILED
			// ========================================================
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

				// hentikan submit browser
				return false;
			},

			// ========================================================
			// VALIDATION SUCCESS
			// ========================================================
			onSuccess: function (event) {
				// ================================================
				// BLOK SUBMIT DEFAULT BROWSER
				// ================================================
				event.preventDefault();

				// sembunyikan error summary
				$(this).find(".ui.error.message").hide().empty();

				// return false wajib agar browser tidak submit ulang
				return false;
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
	setFlyoutHeader() {
		const configKey = this.state.req || this.state.tbl;
		const config = UIConfig?.[configKey];
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
	 * - aman untuk SPA
	 */

	initCascadeDropdown() {
		const self = this;

		// ======================================================
		// EVENT NAMESPACE UNIK PER TABLE
		// ======================================================
		const eventName = `change.formCascade.${this.state.tbl}`;

		// ======================================================
		// HAPUS EVENT LAMA
		// ======================================================
		$(document).off(eventName, `${this.formSelector} .ui.dropdown[data-source]`);

		// ======================================================
		// BIND EVENT BARU
		// ======================================================
		$(document).on(eventName, `${this.formSelector} .ui.dropdown[data-source] input[type=hidden]`, function () {
			// ==================================================
			// GUARD POPULATE MODE
			// ==================================================
			if (self.isPopulating) return;

			const $parentDropdown = $(this).closest(".ui.dropdown");

			// ==================================================
			// CEK SKIP CASCADE FLAG
			// ==================================================
			if ($parentDropdown.data("skip-cascade")) return;

			// ==================================================
			// AMBIL FIELD NAME
			// ==================================================
			const parentName = $parentDropdown.find("input[type=hidden]").attr("name");

			if (!parentName) return;

			// ==================================================
			// AMBIL VALUE PARENT
			// ==================================================
			const parentValue = $parentDropdown.find("input[type=hidden]").val();

			if (!parentValue) return;

			// ==================================================
			// CARI DROPDOWN CHILD
			// ==================================================
			$(`${self.formSelector} .ui.dropdown[data-parent="${parentName}"]`).each(function () {
				const $child = $(this);

				const source = $child.data("source");

				if (!source) return;

				const $menu = $child.find(".menu");

				// ==================================================
				// RESET CHILD
				// ==================================================
				$menu.empty();

				$child.dropdown("clear");
				$child.data("loaded", false);
				$child.data("loading", false);
				// ==================================================
				// REQUEST DATA CHILD
				// ==================================================
				self.ajax.request({
					data: {
						action: "dropdown",
						tbl: source,
						parent: parentName,
						parent_field: $child.data("parent-field"),
						parent_value: parentValue,
					},

					success: (res) => {
						if (!res.success || !res.data) return;

						res.data.forEach((item) => {
							$menu.append(`
								<div class="item" data-value="${item.value}">
									${item.text}
								</div>
							`);
						});

						// ==================================================
						// REFRESH DROPDOWN
						// ==================================================
						$child.dropdown("refresh");
					},
				});
			});
		});
	}
}
