/**
 * ============================================================
 * TATA NASKAH MODULE
 * ============================================================
 * Module untuk manajemen tata naskah
 * Tidak mengatur AJAX langsung
 * Tidak mengatur DOM langsung selain container utama
 */
class TataNaskahModule {
	constructor() {
		this.state = window.app.state; // ambil global state

		this.state.setTable("trx_naskah_dinas"); // set tabel agar TableManager tahu tabel yang dipakai

		this.mainContainer = "#form_modal";

		this.formContainerSelector = "#form_modal";

		this.tableManager = null;
		this.formEngine = null;
		this.formContainer = null;

		// Ajax khusus endpoint
		this.ajaxJenis = new AjaxEngine(AppConfig.apiUrl + "tata_naskah/jenis");
		this.ajaxSchema = new AjaxEngine(AppConfig.apiUrl + "tata_naskah/schema");
		// 🔥 FIX: REGISTER MODULE GLOBAL
		if (!window.app.modules) {
			window.app.modules = {};
		}
		window.app.modules.tata_naskah = this;
	}

	/**
	 * INIT MODULE
	 */
	init() {
		const path = window.location.pathname;

		this.bindEvents();

		// =========================
		// HALAMAN DAFTAR
		// =========================

		if (path === "/tata_naskah") {
			this.renderLayout();

			this.initTableEngine();
		}

		// =========================
		// HALAMAN BUAT
		// =========================

		if (path === "/tata_naskah/daftar") {
			this.renderLayout();
			// halaman daftar naskah
			this.initEngine();
		}
		if (path === "/tata_naskah/buat") {
			// halaman daftar naskah
			this.initEngine();
		}
	}

	/**
	 * RENDER LAYOUT
	 */
	renderLayout() {
		const html = `
        <div class="ui segment">

            <h3 class="ui header">Tata Naskah</h3>

            <button class="ui primary button" id="btn-add">
                Tambah Data
            </button>

            <div id="table-container"></div>

            <div id="form-container" style="display:none;"></div>

        </div>
        `;

		$(this.mainContainer).html(html);
	}

	/**
	 * INIT ENGINE
	 */
	initEngine() {
		this.tableManager = new TableManager({
			state: this.state,
			container: "#table-container",
			mode: "modal",
		});

		this.formContainer = new FormContainerManager({
			container: this.formContainerSelector,
		});

		// =====================================
		// FIX: HAPUS FormEngine (CONFLICT SOURCE)
		// =====================================
		// this.formEngine = new FormEngine({
		// 	state: this.state,
		// 	formSelector: "#dynamic-form",
		// });

		this.tableManager.init();
		this.formContainer.init();

		// =====================================
		// FIX: TIDAK ADA FormEngine.init()
		// =====================================
	}

	/**
	 * BIND EVENTS
	 */
	bindEvents() {
		$(document).off("click", "#btn-add");
		$(document).off("form:success");
		$(document).off("click", ".kelompok-card");
		$(document).off("click", ".btn-open-naskah");
		$(document).off("click", ".btn-edit-row");
		$(document).off("submit", "#dynamic-form"); // 🔥 FIX

		// =====================================
		// EDIT
		// =====================================
		$(document).on("click", ".btn-edit-row", (e) => {
			const row = $(e.currentTarget).closest("tr");
			const id = row.data("id");
			if (!id) return;

			this.loadSchema(null, id);
		});

		// =====================================
		// ADD (WAJIB KE JENIS, BUKAN FORM KOSONG)
		// =====================================
		$(document).on("click", "#btn-add", () => {
			// =====================================
			// FIX: JANGAN pakai showAddForm()
			// =====================================
			$("#jenis-container").removeClass("hidden");
		});

		// =====================================
		// REFRESH TABLE
		// =====================================
		$(document).on("form:success", () => {
			this.tableManager.loadData();
		});

		$(document).on("click", ".kelompok-card", (e) => {
			const kelompokId = $(e.currentTarget).data("id");
			this.loadJenis(kelompokId);
		});

		$(document).on("click", ".btn-open-naskah", (e) => {
			const jenisId = $(e.currentTarget).data("jenis-id");
			if (!jenisId) return;

			this.loadSchema(jenisId);
		});

		// =====================================
		// 🔥 FIX: SUBMIT VIA DocumentBuilder
		// =====================================
		$(document).on("submit", "#dynamic-form", (e) => {
			e.preventDefault();

			const builder = window.documentBuilder;
			if (!builder) return;

			const payload = builder.collectStructure();

			window.Ajax.request({
				method: "POST",
				data: {
					action: "save",
					tbl: this.state.tbl,
					...payload,
				},
				success: (res) => {
					if (res.success) {
						Toast.show("success", "Data berhasil disimpan");
						$(document).trigger("form:success");
					} else {
						Toast.show("error", res.message || "Gagal");
					}
				},
			});
		});
	}

	/**
	 * LOAD JENIS NASKAH
	 */
	loadJenis(kelompokId) {
		this.ajaxJenis.request({
			data: {
				kelompok_id: kelompokId,
			},
			success: (res) => {
				let data;

				if (Array.isArray(res)) {
					data = res;
				} else if (res.success && res.data) {
					data = res.data;
				}

				if (!data || !data.length) return;

				this.renderJenis(data);
			},
		});
	}

	/**
	 * RENDER JENIS NASKAH
	 */
	renderJenis(data) {
		let grouped = {};

		data.forEach((j) => {
			if (!grouped[j.sub_kategori]) {
				grouped[j.sub_kategori] = [];
			}

			grouped[j.sub_kategori].push(j);
		});

		let html = "";

		for (let kategori in grouped) {
			html += `
            <div class="ui segment">
                <h4 class="ui dividing header">
                    ${kategori || "Lainnya"}
                </h4>
                <div class="ui relaxed divided list">
            `;
			grouped[kategori].forEach((j) => {
				html += `
                <div class="item">
                    <button
                        class="ui fluid basic button btn-open-naskah"
                        data-ui="open-form" // memanggil UI engine
                        data-container="modal" // target container modal
                        data-module="tata_naskah" // module aktif
                        data-jenis-id="${j.id}" // id jenis
                        data-kode-form="${j.kode_form}" // tipe dokumen
                        data-tbl="trx_naskah_dinas"
                        data-action="add"
                        data-nama="${j.nama}" // nama dokumen
                        data-kelompok="${j.kelompok_kode}" // kelompok
                        data-kelompok-nama="${j.kelompok_nama}" // label >
                        ${j.nama}
                    </button>
                </div>
                `;
			});

			html += `</div></div>`;
		}

		$("#jenis-list").html(html);

		$("#jenis-container").removeClass("hidden");
	}

	/**
	 * LOAD SCHEMA FORM
	 */
	loadSchema(jenisId, id = null) {
		// =====================================
		// EDIT MODE
		// =====================================
		if (id) {
			window.Ajax.request({
				method: "POST",
				data: {
					action: "edit",
					tbl: this.state.tbl,
					id_row: id,
				},
				success: (resData) => {
					if (!resData.success) return;

					const data = resData.data;

					// =====================================
					// LOAD SCHEMA BERDASARKAN jenis_id
					// =====================================
					this.ajaxSchema.request({
						data: { jenis_id: data.jenis_id },

						success: (res) => {
							if (res.error) {
								Toast.show("error", res.error);
								return;
							}

							// =====================================
							// FIX: SCHEMA RESOLVE (MULTI SOURCE)
							// =====================================
							let schema = res.schema;

							// fallback jika tidak ada
							if (!schema && resData.data?._read?.cache_schema_naskah?.length) {
								try {
									schema = JSON.parse(resData.data._read.cache_schema_naskah[0].schema_json);
								} catch (e) {
									console.error("Schema parse error", e);
								}
							}

							if (!schema) return;

							// =====================================
							// RENDER FORM
							// =====================================
							this.formContainer.show("");

							const container = $(this.formContainerSelector);

							window.documentBuilder = new DocumentBuilder($("#form_modal"));

							// =====================================
							// FIX: normalisasi schema agar dikenali builder
							// =====================================
							window.documentBuilder.schema = Array.isArray(schema)
								? { fields: schema } // // FIX
								: schema;
							window.documentBuilder.data = data; // FIX (bukan res)

							window.documentBuilder.render();

							// =====================================
							// FIX: INJECT DATA FIELD BY FIELD
							// =====================================
							Object.entries(data).forEach(([key, val]) => {
								// skip object kompleks (ditangani builder)
								if (typeof val === "object") return;

								const el = container.find(`[name="${key}"]`);

								if (!el.length) return;

								el.val(val);
							});

							// =====================================
							// FIX: HANDLE JSON FIELD (struktur_json)
							// =====================================
							if (data.struktur_json) {
								try {
									const json =
										typeof data.struktur_json === "string" ? JSON.parse(data.struktur_json) : data.struktur_json;

									// inject ke builder jika dipakai
									window.documentBuilder.data = {
										...window.documentBuilder.data,
										...json,
									};
								} catch (e) {
									console.warn("JSON parse gagal", e);
								}
							}

							// =====================================
							// TRIGGER UI UPDATE
							// =====================================
							container.find("select").trigger("change");
							container.find("input, textarea").trigger("input");

							// =====================================
							// DROPDOWN ENGINE
							// =====================================
							if (!window.dropdownEngine) {
								window.dropdownEngine = new DropdownEngine($("#form_modal"), {
									...res,
									data: resData.data, // FIX: agar source dropdown punya data
								});
								window.dropdownEngine.init();
							}
						},
					});
				},
			});

			return;
		}

		// =====================================
		// ADD MODE (TETAP + DISERAGAMKAN)
		// =====================================
		this.ajaxSchema.request({
			data: { jenis_id: jenisId },

			success: (res) => {
				if (res.error) {
					Toast.show("error", res.error);
					return;
				}

				let schema = res.schema;

				if (!schema) return;

				this.formContainer.show("");

				const container = $(this.formContainerSelector);

				window.documentBuilder = new DocumentBuilder($("#form_modal"));

				window.documentBuilder.schema = schema;
				window.documentBuilder.data = {}; // FIX: kosong

				window.documentBuilder.render();

				// =====================================
				// FIX: inject jenis_id (hidden)
				// =====================================
				if (jenisId) {
					const hidden = `<input type="hidden" name="jenis_id" value="${jenisId}">`;
					$("#form_modal").append(hidden);
				}

				// =====================================
				// DROPDOWN ENGINE
				// =====================================
				if (!window.dropdownEngine) {
					window.dropdownEngine = new DropdownEngine($("#form_modal"), res);
					window.dropdownEngine.init();
				}
			},
		});
	}

	/**
	 * SHOW FORM TAMBAH
	 */
	showAddForm() {
		const html = `
        <form id="dynamic-form" class="ui form">
            <div class="field">
                <label>Judul</label>
                <input type="text" name="judul">
            </div>
            <button class="ui green button" type="submit">
                Simpan
            </button>
        </form>
        `;
		this.formContainer.show(html);
	}
	/**
	 * DESTROY MODULE
	 */
	destroy() {
		this.tableManager?.destroy();
		this.formEngine?.destroy();
		this.formContainer?.destroy();

		$(document).off("click", "#btn-add");
		$(document).off("click", ".kelompok-card");
		$(document).off("click", ".btn-open-naskah");
		$(document).off("form:success");

		$(this.mainContainer).empty();
	}
}
