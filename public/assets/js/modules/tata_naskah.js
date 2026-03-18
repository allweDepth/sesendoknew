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
			container: "#table-container", // DOM container
			mode: "modal", // // FIX: pisahkan mode
		});

		this.formContainer = new FormContainerManager({
			container: this.formContainerSelector,
		});

		this.formEngine = new FormEngine({
			state: this.state,
			formSelector: "#dynamic-form",
		});

		this.tableManager.init();
		this.formContainer.init();
		this.formEngine.init();
	}

	/**
	 * BIND EVENTS
	 */
	bindEvents() {
		// ======================================================
		// FIX: HAPUS EVENT LAMA AGAR TIDAK DUPLIKASI
		// ======================================================

		$(document).off("click", "#btn-add"); // hapus handler lama
		$(document).off("form:success"); // hapus handler lama
		$(document).off("click", ".kelompok-card"); // hapus handler lama
		$(document).off("click", ".btn-open-naskah"); // hapus handler lama
		$(document).off("click", ".btn-edit-row");
		// =====================================
		// FIX: EDIT ROW → MODAL (KHUSUS MODULE)
		// =====================================
		$(document).on("click", ".btn-edit-row", (e) => {
			const row = $(e.currentTarget).closest("tr");
			const id = row.data("id");

			if (!id) return;

			// reuse schema loader (flow yang sudah ada)
			this.loadSchema(null, id); // // FIX: kirim id sebagai edit
		});
		// ======================================================
		// BIND EVENT BARU
		// ======================================================

		$(document).on("click", "#btn-add", () => {
			this.showAddForm();
		});

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
		// FIX: EDIT MODE → ambil data dulu
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
					// lalu ambil schema
					// =====================================
					this.ajaxSchema.request({
						data: { jenis_id: data.jenis_id },

						success: (res) => {
							if (res.error) {
								Toast.show("error", res.error);
								return;
							}

							if (!res.schema) return;

							// tampilkan container
							this.formContainer.show("");

							const container = $(this.formContainerSelector);

							const type = res.schema.kode_form || "sk";

							window.documentBuilder = new DocumentBuilder($("#form_modal"));
							window.documentBuilder.schema = res.schema;
							window.documentBuilder.data = res;

							window.documentBuilder.render();

							// =====================================
							// FIX: inject DATA ke form (INI KUNCI)
							// =====================================
							Object.entries(data).forEach(([key, val]) => {
								const el = container.find(`[name="${key}"]`);

								if (!el.length) return;

								el.val(val);
							});

							// trigger dropdown
							container.find("select").trigger("change");

							// dropdown engine
							if (!window.dropdownEngine) {
								window.dropdownEngine = new DropdownEngine($("#form_modal"), res);
								window.dropdownEngine.init();
							}
						},
					});
				},
			});

			return;
		}

		// =====================================
		// ADD MODE (TIDAK DIUBAH)
		// =====================================
		this.ajaxSchema.request({
			data: { jenis_id: jenisId },

			success: (res) => {
				if (res.error) {
					Toast.show("error", res.error);
					return;
				}

				if (!res.schema) return;

				this.formContainer.show("");

				const container = $(this.formContainerSelector);

				const type = res.schema.kode_form || "sk";

				window.documentBuilder = new DocumentBuilder($("#form_modal"));
				window.documentBuilder.schema = res.schema;
				window.documentBuilder.data = res;
				window.documentBuilder.render();

				// tetap inject jenis_id (punya kamu)
				if (jenisId) {
					const hidden = `<input type="hidden" name="jenis_id" value="${jenisId}">`;
					$("#form_modal").append(hidden);
				}

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
