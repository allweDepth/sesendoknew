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
		/**
		 * tombol tambah
		 */
		$(document).on("click", "#btn-add", () => {
			this.showAddForm();
		});

		/**
		 * reload table setelah form submit
		 */
		$(document).on("form:success", () => {
			this.tableManager.loadData();
		});

		/**
		 * klik kelompok naskah
		 */
		$(document).on("click", ".kelompok-card", (e) => {
			const kelompokId = $(e.currentTarget).data("id");

			this.loadJenis(kelompokId);
		});

		/**
		 * klik jenis naskah
		 */
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
                        data-tbl="${j.kode_form}" // tipe dokumen
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
	loadSchema(jenisId) {
		this.ajaxSchema.request({
			data: { jenis_id: jenisId },

			success: (res) => {
				if (res.error) {
					Toast.show("error", res.error);
					return;
				}

				if (!res.schema) return;

				// this.formContainer.render({
				// 	schema: res.schema,
				// 	asn: res.asn || [],
				// 	klasifikasi: res.klasifikasi || [],
				// 	nomor_auto: res.nomor_auto || null,
				// });
				// tampilkan container dulu
				this.formContainer.show(""); // menampilkan container form

				// ambil container DOM
				const container = $(this.formContainerSelector); // ambil element container

				// ambil tipe dokumen
				const type = res.schema.kode_form || "sk"; // ambil kode form jika ada

				// buat builder dokumen
				const builder = new DocumentBuilder(container, type); // buat builder

				builder.schema = res.schema; // ambil schema dari database

				// normalisasi struktur schema
				if (!builder.schema.sections) {
					// jika sections tidak ada

					builder.schema = {
						// buat struktur baru
						sections: builder.schema, // jadikan array schema sebagai sections
					};
				}

				builder.render(); // render builder
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
