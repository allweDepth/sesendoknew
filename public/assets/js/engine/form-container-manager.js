/* =========================================================
   FORM CONTAINER MANAGER
   Bisa tampil di:
   - Flyout
   - Modal
========================================================= */

class FormContainerManager {
	// @note FormContainerManager
	constructor() {
		this.$flyout = $("#mainContext .sidebarkanan");
		this.$modal = $("#mainModal");

		this.activeContainer = "flyout";

		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		// =============================
		// 🔥 PLUGIN SYSTEM (TAMBAHAN)
		// =============================
		this.plugins = {};
		this.beforeSavePlugins = {};

		this.initContainers();
	}

	// =============================
	// 🔥 REGISTER PLUGIN
	// =============================
	registerPlugin(key, callback) {
		this.plugins[key] = callback;
	}

	registerBeforeSave(key, callback) {
		this.beforeSavePlugins[key] = callback;
	}
	collectDocumentStructure() {
		let data = {};

		this.getActiveForm()
			.find("table[name]")
			.each(function () {
				let section = $(this).attr("name");

				data[section] = [];

				$(this)
					.find("tbody tr")
					.each(function () {
						let text = $(this).find(".doc-text").val();

						let type =
							$(this).find(".doc-type").dropdown("get value") || "paragraph";

						if (!text) return;

						data[section].push({
							type: type,
							text: text,
						});
					});
			});

		return data;
	}
	runPlugins() {
		const exactKey = `${AppState.module}.${AppState.tbl}`;
		const wildcardKey = `${AppState.module}.*`;

		const payload = {
			container: this.getActiveForm(),
			state: AppState,
			manager: this,
		};

		if (this.plugins[exactKey]) {
			this.plugins[exactKey](payload);
		}

		if (this.plugins[wildcardKey]) {
			this.plugins[wildcardKey](payload);
		}
	}

	runBeforeSave(formData) {
		const exactKey = `${AppState.jenis}.${AppState.tbl}`;
		const wildcardKey = `${AppState.jenis}.*`;

		if (this.beforeSavePlugins[exactKey]) {
			this.beforeSavePlugins[exactKey](formData, AppState);
		}

		if (this.beforeSavePlugins[wildcardKey]) {
			this.beforeSavePlugins[wildcardKey](formData, AppState);
		}
	}

	/* --------------------------------------------- */
	initContainers() {
		if (this.$flyout.length && !this.$flyout.data("module-sidebar")) {
			this.$flyout.sidebar({
				context: $("#mainContext"),
				transition: "push",
				closable: false,
				scrollLock: true,
			});
		}

		if (this.$modal.length && !this.$modal.data("module-modal")) {
			this.$modal.modal({ closable: false });
		}

		this.bindEvents();
	}

	/* --------------------------------------------- */
	bindEvents() {
		const self = this;

		$(document).on("click", '[data-ui="open-form"]', function (e) {
			e.preventDefault();
			self.open($(this));
		});

		$(document).on("click", ".btnSubmit", function () {
			let $form = self.getActiveForm();
			$form.submit();
		});

		$(document).on("click", ".btnFlyoutClose", function () {
			self.$flyout.sidebar("hide");
		});

		$(document).on("click", ".sidebarkanan .close.icon", function () {
			self.$flyout.sidebar("hide");
		});
	}

	getActiveForm() {
		return this.activeContainer === "modal"
			? $("#form_modal")
			: $("#form_flyout");
	}

	// =============================
	// LOAD DROPDOWN (TIDAK DIUBAH)
	// =============================
	loadDropdowns(containerSelector) {
		const self = this;

		// =====================================================
		// 🔥 LOOP SEMUA DROPDOWN YANG PUNYA data-source
		// -----------------------------------------------------
		// - Kalau tidak punya source → skip (static dropdown aman)
		// - Kalau punya source → fetch
		// =====================================================

		$(containerSelector)
			.find(".ui.dropdown[data-source]")
			.each(function () {
				let $dropdown = $(this);
				let source = $dropdown.data("source");

				// 🔥 Skip jika tidak ada source (dropdown manual)
				if (!source) return;

				// 🔥 Pastikan source memang terdeteksi dari UIConfig
				if (!AppState.serverSources.includes(source)) return;

				// =====================================================
				// 🔥 FETCH DROPDOWN DATA
				// =====================================================
				self.fetchDropdown($dropdown, source);

				// =====================================================
				// 🔥 SUPPORT PARENT DEPENDENCY (CASCADING)
				// =====================================================
				let parentName = $dropdown.data("parent");

				if (parentName) {
					$(containerSelector).on(
						"change",
						`[name="${parentName}"]`,
						function () {
							let parentValue = $(this).val();
							self.fetchDropdown($dropdown, source, parentValue);
						},
					);
				}
			});
	}

	fetchDropdown($dropdown, source, parentValue = null) {
		let payload = {
			module: AppState.module,
			action: "dropdown",
			source: source,
		};

		if (parentValue) {
			payload.parent_value = parentValue;
		}

		this.ajax.request({
			data: payload,
			success: function (res) {
				if (!res.success) return;

				let menu = $dropdown.find(".menu");
				menu.empty();

				res.data.forEach((item) => {
					menu.append(`
						<div class="item" data-value="${item.id}">
							${item.uraian}
						</div>
					`);
				});

				$dropdown.dropdown("refresh");
			},
		});
	}

	/* --------------------------------------------- */
	open($btn) {
		// 🔥 FORCE RENSTRA MODULE
		if (window.location.pathname.replace(/^\/+/g, "") === "renstra") {
			AppState.module = "renstra";
		}
		const jenisMode = $btn.data("jns");
		const tbl = $btn.attr("data-tbl") || AppState.tbl;

		// ==============================
		// 🔥 VALIDASI WAJIB
		// ==============================
		if (!tbl) {
			console.error("TBL tidak ditemukan");
			return;
		}

		// ==============================
		// 🔥 SET JENIS
		// ==============================
		// ==================================
		// 🔥 PASTIKAN MODULE TIDAK KACAU
		// ==================================
		let moduleFromBtn = $btn.attr("data-module");

		// Jika tombol punya data-module → pakai itu
		if (moduleFromBtn) {
			AppState.module = moduleFromBtn;
		}
		// Jika tidak, dan belum ada module → ambil dari URL
		else if (!AppState.module) {
			AppState.module = window.location.pathname.replace(/^\/+/g, "");
		}

		// 🔥 JANGAN pernah override module jika sudah ada

		AppState.tbl = tbl;
		AppState.action = jenisMode;

		// ==============================
		// 🔥 DEBUG
		// ==============================
		console.log("OPEN FORM:");
		console.log("MODULE:", AppState.module);
		console.log("TBL:", AppState.tbl);

		// ==============================
		// 🔥 BUILD CONFIG
		// ==============================
		let config = this.buildConfig(jenisMode, tbl);
		if (!config.elements.length && config.type !== "document") {
			ToastEngine.show({
				success: false,
				message: "Form belum dikonfigurasi. Hubungi administrator.",
			});
			console.warn("UIConfig missing:", AppState.module, tbl);
			return;
		}

		// ==============================
		// 🔥 DETECT DROPDOWN
		// ==============================
		AppState.serverSources = config.elements
			.filter((el) => el.tag === "fieldDropdown" && el.prop?.source)
			.map((el) => el.prop.source);

		AppState.serverSources = [...new Set(AppState.serverSources)];

		const container = $btn.data("container") || "flyout";
		this.activeContainer = container;

		this.render(config, container);
		this.show(container);
		// ==============================
		// 🔥 AUTO LOAD DATA SAAT EDIT
		// ==============================
		if (jenisMode === "edit") {
			const idRow = $btn.data("id");
			if (idRow) {
				this.loadData(idRow, container);
			}
		}
	}

	/* --------------------------------------------- */
	render(config, container) {
		$("#form_modal").empty();
		$("#form_flyout").empty();

		let target;

		if (container === "modal") {
			target = "#form_modal";
			$("#icon_modal").attr("class", config.icon);
			$("#content_modal").text(config.headerTitle || config.header);
		} else {
			target = "#form_flyout";
			$("#icon_flyout").attr("class", config.icon);
			$("#content_flyout").text(config.headerTitle || config.header);
		}

		// ===================================================
		// 🔥 KHUSUS DOCUMENT ENGINE
		// ===================================================
		if (config.type === "document") {
			FormEngine.render(target, config.elements);

			let builder = new DocumentBuilder($(target), AppState.tbl);
			builder.render();

			// 🔥 JANGAN VALIDASI DENGAN UIConfig RULES
			// this.initValidation(target);

			this.loadDropdowns(target);
			this.runPlugins();
			return;
		}

		// ===================================================
		// DEFAULT FORM (CRUD NORMAL)
		// ===================================================
		FormEngine.render(target, config.elements);
		this.initValidation(target);
		this.loadDropdowns(target);

		if (AppState.action === "edit") {
			$(target).prepend(`<input type="hidden" name="id">`);
		}

		this.runPlugins();
	}

	initValidation(target) {
		const $form = $(target);
		if (!$form.length) return;

		let rules = {};

		let elements =
			UIConfig[AppState.module]?.[AppState.tbl] ||
			UIConfig[AppState.page]?.[AppState.tbl] ||
			[];

		// ==============================
		// 🔥 FLATTEN ELEMENTS (AMBIL CHILDREN)
		// ==============================
		function flatten(arr, result = []) {
			arr.forEach((el) => {
				if (el.tag === "fields" || el.tag === "inlineFields") {
					if (el.prop?.children) {
						flatten(el.prop.children, result);
					}
				} else {
					result.push(el);
				}
			});
			return result;
		}

		let flatElements = flatten(elements);

		flatElements.forEach((el) => {
			if (!el.prop || !el.prop.name) return;

			// 🔥 SKIP VALIDATION JIKA validate:false
			if (el.prop.validate === false) return;

			let name = el.prop.name;

			if (!$form.find(`[name="${name}"]`).length) return;

			let fieldRules = [];

			if (el.prop.classField?.includes("required")) {
				fieldRules.push({
					type: "empty",
					prompt: (el.prop.label || name) + " wajib diisi",
				});
			}

			if (name === "email") {
				fieldRules.push({
					type: "email",
					prompt: "Format email tidak valid",
				});
			}

			if (el.prop.atribut?.includes('type="number"')) {
				fieldRules.push({
					type: "number",
					prompt: (el.prop.label || name) + " harus berupa angka",
				});
			}

			if (fieldRules.length) {
				rules[name] = {
					identifier: name,
					rules: fieldRules,
				};
			}
		});

		$form.form("destroy");
		$form.removeData("module-form");

		$form.form({
			inline: true,
			on: "blur",
			fields: rules,
			onSuccess: (event) => {
				event.preventDefault();
				this.save();
			},
		});
	}

	show(container) {
		container === "modal"
			? this.$modal.modal("show")
			: this.$flyout.sidebar("show");
	}

	hide(container) {
		container === "modal"
			? this.$modal.modal("hide")
			: this.$flyout.sidebar("hide");
	}

	loadData(idRow, container) {
		this.ajax.request({
			data: {
				module: AppState.module,
				action: "edit",
				tbl: AppState.tbl,
				id_row: idRow,
			},
			success: (res) => {
				if (!res.success || !res.data) return;

				let $formTarget =
					container === "modal" ? $("#form_modal") : $("#form_flyout");

				Object.keys(res.data).forEach((key) => {
					let $field = $formTarget.find(`[name="${key}"]`);
					if (!$field.length) return;

					if ($field.closest(".ui.dropdown").length) {
						$field
							.closest(".ui.dropdown")
							.dropdown("set selected", res.data[key]);
					} else if ($field.attr("type") === "checkbox") {
						res.data[key] == 1
							? $field.closest(".ui.checkbox").checkbox("check")
							: $field.closest(".ui.checkbox").checkbox("uncheck");
					} else if ($field.closest(".ui.calendar").length) {
						let value = res.data[key];
						if (!value) return;

						let $calendar = $field.closest(".ui.calendar");
						let type = $field.data("type") || "date";

						let dateObj;

						if (type === "year") {
							// ambil 4 digit tahun
							let year = String(value).substring(0, 4);
							dateObj = new Date(parseInt(year), 0, 1);
						} else {
							// parse YYYY-MM-DD (format SQL)
							if (typeof value === "string" && value.includes("-")) {
								let parts = value.split("-");
								dateObj = new Date(parts[0], parts[1] - 1, parts[2] || 1);
							} else {
								dateObj = new Date(value);
							}
						}

						if (!isNaN(dateObj.getTime())) {
							$calendar.calendar("set date", dateObj);
						}
					} else if ($field.attr("type") !== "file") {
						$field.val(res.data[key]);
					}
				});
				// ==========================================
				// 🔥 LOAD STRUKTUR JSON UNTUK tata_naskah
				// ==========================================
				if (AppState.module === "tata_naskah" && res.data.struktur_json) {
					let struktur = {};

					try {
						struktur = JSON.parse(res.data.struktur_json);
					} catch (e) {
						console.error("JSON struktur rusak");
					}

					Object.keys(struktur).forEach((section) => {
						struktur[section].forEach((item) => {
							let text = item.text || "";
							let type = item.type || "paragraph";

							let builder = new DocumentBuilder($formTarget, AppState.tbl);

							let row = builder.buildRow(section, text, type);

							$formTarget.find(`table[name="${section}"] tbody`).append(row);
						});
					});

					$formTarget.find(".ui.dropdown").dropdown();
				}
			},
		});
	}

	buildConfig(jenis, tbl) {
		// ===============================
		// 🔥 MODE IMPORT XLSX
		// ===============================
		if (["import_xlsx", "import_struktur"].includes(jenis)) {
			const isStruktur = jenis === "import_struktur";
			return {
				title: isStruktur ? "Import Struktur Nasional" : "Import Data XLSX",
				fields: [
					{
						type: "file",
						name: "file",
						accept: ".xlsx",
					},
				],
				extra: {
					mode: jenis,
				},
				icon: "upload icon",
				header: "Import Excel",
				elements: [
					{
						tag: "fieldFile",
						prop: {
							label: "Upload File Excel",
							name: "file_import",
							accept: ".xlsx,.xls",
							classField: "required",
						},
					},
					{
						tag: "fieldDropdown",
						prop: {
							label: "Jumlah Header",
							name: "jml_header",
							classField: "required",
							options: [
								{ value: "0", text: "0 Baris Header" },
								{
									value: "1",
									text: "1 Baris Header",
									class: " active selected",
								}, // @audit
								{ value: "2", text: "2 Baris Header" },
								{ value: "3", text: "3 Baris Header" },
								{ value: "4", text: "4 Baris Header" },
								{ value: "5", text: "5 Baris Header" },
							],
						},
					},
				],
			};
		}

		// ===============================
		// DEFAULT MODE (add/edit/detail)
		// ===============================
		let config = {
			icon:
				jenis === "add"
					? "plus icon"
					: jenis === "edit"
						? "edit icon"
						: "eye icon",

			header:
				jenis === "add"
					? "Tambah Data"
					: jenis === "edit"
						? "Edit Data"
						: "Detail Data",

			elements:
				UIConfig[AppState.module]?.[tbl] ||
				UIConfig[AppState.page]?.[tbl] ||
				[],
		};
		// 🔥 DETECT DOCUMENT MODULE
		if (AppState.module === "tata_naskah") {
			return {
				type: "document",
				icon: "file alternate outline icon",
				headerTitle: "Dokumen Naskah",
				elements: UIConfig[AppState.module]?.[tbl] || [],
			};
		}
		return config;
	}

	/* --------------------------------------------- */
	save() {
		// ===============================
		// 🔥 MODE IMPORT XLSX
		// ===============================
		if (
			AppState.action === "import_xlsx" ||
			AppState.action === "import_struktur"
		) {
			let $formTarget = this.getActiveForm();
			let formElement = $formTarget[0];

			let fileInput = $formTarget.find('[name="file_import"]')[0];

			if (!fileInput || !fileInput.files.length) {
				alert("File belum dipilih");
				return;
			}

			// 🔥 Ambil semua field dari form (termasuk jml_header)
			let formData = new FormData(formElement);

			// Pastikan file override ke name yang benar
			formData.set("file", fileInput.files[0]);

			if (AppState.action === "import_struktur") {
				formData.set("jns", "import_struktur");
			}

			if (AppState.action === "import_xlsx") {
				formData.set("tabel", AppState.tbl);
			}

			this.ajax.request({
				url: AppConfig.apiUrl + "import",
				method: "POST",
				data: formData,
				processData: false,
				contentType: false,
				success: (res) => {
					if (res.success) {
						this.hide(this.activeContainer);
						new TableManager().fetch();
					} else {
						alert(res.error || "Import gagal");
					}
				},
			});

			return;
		}

		// ===============================
		// 🔥 SAVE NORMAL (ADD / EDIT)
		// ===============================
		let $formTarget = this.getActiveForm();
		let formElement = $formTarget[0];

		if (!formElement) return;

		let formData = new FormData(formElement);

		// =====================================
		// 🔥 KHUSUS tata_naskah → KIRIM STRUKTUR
		// =====================================
		if (AppState.module === "tata_naskah") {
			let struktur = this.collectDocumentStructure();
			formData.append("struktur_json", JSON.stringify(struktur));
		}

		formData.append("module", AppState.module);
		formData.append("action", AppState.action);
		formData.append("tbl", AppState.tbl);

		this.ajax.request({
			url: AppConfig.apiUrl + "dynamic",
			method: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: (res) => {
				if (res.success) {
					this.hide(this.activeContainer);
					new TableManager().fetch();
				}
			},
		});
	}
}
