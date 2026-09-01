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
		this.currentId = null; // // menyimpan id saat edit
		this.state.setTable("trx_naskah_dinas"); // set tabel agar TableManager tahu tabel yang dipakai

		this.mainContainer = '[name="tabel_trx_naskah_dinas"]';

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
		const path = window.appRoutePath ? window.appRoutePath(window.location.pathname) : window.location.pathname;

		this.bindEvents();

		// =========================
		// HALAMAN DAFTAR
		// =========================

		if (path === "/tata_naskah" || path === "/tata_naskah/dokumen") {
			return;
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
			// 🔥 override selector pagination
			pagination: 'div[name="pagination_tata_naskah"]', //override manual agar cocok DOM lama
		});

		this.formContainer = new FormContainerManager({
			container: this.formContainerSelector,
		});

		// =====================================
		// FIX: HAPUS FormEngine (CONFLICT SOURCE)
		// =====================================
		// this.formEngine = new FormEngine({
		// 	state: this.state,
		// 	formSelector: "#form_modal",
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
		$(document).off("submit", "#form_modal"); // 🔥 FIX
		$(document).off("input.naskahSearch", "#jenisNaskahSearch, #searchTataNaskah");
		$(document).on("input.naskahSearch", "#jenisNaskahSearch", e=>{const q=String(e.target.value||"").toLowerCase();$("#jenis-list .item").each(function(){$(this).toggle($(this).text().toLowerCase().includes(q));});});
		$(document).on("input.naskahSearch", "#searchTataNaskah", e=>{const q=String(e.target.value||"").toLowerCase();$('[name="tabel_trx_naskah_dinas"] tr[data-id]').each(function(){$(this).toggle($(this).text().toLowerCase().includes(q));});});

		// =====================================
		// 🔥 FIX: HUBUNGKAN .btnSubmit KE FORM MODAL
		// =====================================
		$(document).off("click", ".btnSubmit"); // // cegah double binding

		$(document).on("click", ".btnSubmit", (e) => {
			const btn = $(e.currentTarget); // // ambil button

			// =====================================
			// VALIDASI: HARUS DI DALAM MODAL INI
			// =====================================
			const modal = btn.closest("#mainModal"); // // scope modal

			if (!modal.length) return; // // bukan modal ini → skip

			const form = modal.find("#form_modal"); // // ambil form modal

			if (!form.length) return; // // safety

			// =====================================
			// TRIGGER SUBMIT (INI YANG HILANG)
			// =====================================
			form.trigger("submit"); // // gunakan jQuery event agar tidak double
		});

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
		$(document).on(`form:success.${this.state.tbl}.table`, () => {
			this.tableManager.fetchData();
		});

		$(document).on("click", ".kelompok-card", (e) => {
			const kelompokId = $(e.currentTarget).data("id");
			$("[data-naskah-step=1]").removeClass("active").addClass("completed");$("[data-naskah-step=2]").removeClass("disabled").addClass("active");
			this.loadJenis(kelompokId);
		});

		$(document).on("click", ".btn-open-naskah", (e) => {
			const jenisId = $(e.currentTarget).data("jenis-id");
			if (!jenisId) return;

			$("[data-naskah-step=2]").removeClass("active").addClass("completed");$("[data-naskah-step=3]").removeClass("disabled").addClass("active");this.loadSchema(jenisId);
		});

		// =====================================
		// 🔥 FIX: SUBMIT VIA DocumentBuilder
		// =====================================
		$(document).on("submit", "#form_modal", (e) => {
			e.preventDefault(); // // cegah reload

			console.log("SUBMIT TRIGGERED"); // // TRACE WAJIB
			// =====================================
			// TAMBAH VALIDATION ENGINE (MANUAL)
			// =====================================
			const form = $("#form_modal");

			// ======================================================
			// TRIGGER FOMANTIC VALIDATION
			// ======================================================
			form.form("validate form");

			if (!form.form("is valid")) {
				return; // stop submit
			}

			const builder = window.documentBuilder; // // ambil instance builder global

			if (!builder) {
				console.error("documentBuilder tidak ditemukan"); // // logging jelas
				return; // // stop karena memang wajib
			}

			const payload = builder.collectStructure(); // // ambil struktur form
			// 🔥 WAJIB STRINGIFY DI SINI
			const finalPayload = {
				struktur_json: JSON.stringify(payload),
			};
			window.Ajax.request({
				url: AppConfig.apiUrl + "dynamic",
				method: "POST",
				data: {
					action: this.currentId !== null ? "edit_json" : "add_json", // // STRICT CHECK
					id_row: this.currentId !== null ? this.currentId : "", // // hindari null
					tbl: this.state.tbl,
					...finalPayload,
				},
				success: (res) => {
					if (res.success) {
						Toast.success("Data berhasil disimpan");
						$(document).trigger(`form:success.${this.state.tbl}.table`); // // sesuai TableManager listener
					} else {
						Toast.error(res.message || "Gagal");
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
                        data-ui="open-form"
                        data-container="modal"
                        data-module="tata_naskah"
                        data-jenis-id="${j.id}"
                        data-kode-form="${j.kode_form}" 
                        data-tbl="trx_naskah_dinas"
                        data-action="add_json"
                        data-nama="${j.nama}"
                        data-kelompok="${j.kelompok_kode}"
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
		if (id) {
			this.currentId = id; // // SET LANGSUNG JIKA EDIT
		} else {
			this.currentId = null; // // hanya reset saat add
		}
		// =====================================
		// EDIT MODE
		// =====================================
		if (id) {
			window.Ajax.request({
				url: AppConfig.apiUrl + "dynamic",
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
					// 🔥 FIX: AMBIL SCHEMA PRIORITAS CACHE
					// =====================================
					let schema = null;

					if (resData.data?._read?.cache_schema_naskah?.length) {
						try {
							schema = JSON.parse(resData.data._read.cache_schema_naskah[0].schema_json);
						} catch (e) {
							console.error("Schema parse error", e);
						}
					}

					// =====================================
					// 🔥 FALLBACK KE ENDPOINT (JIKA TIDAK ADA CACHE)
					// =====================================
					if (!schema) {
						this.ajaxSchema.request({
							data: { jenis_id: data.jenis_id },
							success: (res) => {
								if (res.error) return;

								let fallbackSchema = res.schema;
								if (!fallbackSchema) return;

								this.renderForm(fallbackSchema, data, res, null, resData.data?._read);
							},
						});

						return; // 🔥 STOP di sini (jangan lanjut)
					}

					// =====================================
					// 🔥 LANGSUNG RENDER (TANPA HIT ENDPOINT)
					// =====================================
					this.renderForm(schema, data, resData, null, resData.data?._read);
				},
			});

			return;
		}

		// =====================================
		// ADD MODE
		// =====================================
		this.ajaxSchema.request({
			data: { jenis_id: jenisId },

			success: (res) => {
				if (res.error) {
					Toast.error(res.error);
					return;
				}

				let schema = res.schema;
				if (!schema) return;

				// =====================================
				// 🔥 RENDER ADD
				// =====================================
				this.renderForm(schema, {}, res, jenisId, res._read);
			},
		});
	}
	renderForm(schema, data = {}, res = {}, jenisId = null, extra = {}) {
		this.currentId = data?.id || null;

		this.formContainer.show("");

		setTimeout(() => {
			$("#mainModal").modal("show");
		}, 0);

		const container = $(this.formContainerSelector);

		if (window.documentBuilder) {
			window.documentBuilder.destroy?.();
		}

		$("#form_modal").html("");

		window.documentBuilder = new DocumentBuilder($("#form_modal"));

		if (Array.isArray(schema)) {
			schema = { sections: schema };
		}

		if (data.struktur_json) {
			let json = data.struktur_json;

			if (typeof json === "string") {
				json = JSON.parse(json);
			}

			if (json.struktur_json) {
				json = json.struktur_json;
			}

			data = {
				...data,
				...json,
			};
		}

		window.documentBuilder.schema = schema;
		window.documentBuilder.data = data;

		window.documentBuilder.render();

		// ======================================================
		// 🔥 FIX WAJIB: TAMBAHKAN ERROR MESSAGE CONTAINER
		// ======================================================
		if (!$("#form_modal").find(".ui.error.message").length) {
			$("#form_modal").append(`
			<div class="ui error message"></div> 
		`);
		}

		const asnData = extra?.db_asn_pemda_neo || res?.db_asn_pemda_neo || res?.asn || res?.data_asn || [];

		if (window.dropdownEngine) {
			window.dropdownEngine.destroy?.();
			window.dropdownEngine = null;
		}

		window.dropdownEngine = new DropdownEngine($("#form_modal"), {
			...res,
			asn: asnData,
			penandatangan: asnData,
			db_asn_pemda_neo: asnData,
			data: {},
		});

		window.dropdownEngine.init();

		Object.entries(data).forEach(([key, val]) => {
			if (typeof val === "object") return;

			const dropdown = container.find(`.ui.dropdown:has(input[name="${key}"])`);

			if (dropdown.length) {
				dropdown.dropdown("set selected", val);
				return;
			}

			const input = container.find(`[name="${key}"]`);
			if (!input.length) return;

			input.val(val);
		});

		container.find("select").trigger("change");
		container.find("input, textarea").trigger("input");
		this.enhanceForm(this.currentId!==null);

		if (jenisId) {
			$("#form_modal").find('input[name="jenis_id"]').remove();
			$("#form_modal").append(`
			<input type="hidden" name="jenis_id" value="${jenisId}">
		`);
		}
		// ======================================================
		// 🔥 INIT FOMANTIC VALIDATION (FINAL STABLE)
		// ======================================================
		const form = $("#form_modal");

		// =====================================
		// 🔥 DESTROY DULU (WAJIB BIAR TIDAK DOUBLE)
		// =====================================
		form.form("destroy");

		// =====================================
		// AMBIL CONFIG
		// =====================================
		const config = UIConfig[this.state.req] || UIConfig[this.state.tbl] || {};
		const validationConfig = config.validation || {};

		const fields = {};

		// =====================================
		// 🔥 LOOP UIConfig → FILTER DOM NYATA
		// =====================================
		Object.keys(validationConfig).forEach((name) => {
			const input = form.find(`[name="${name}"]`);

			// ❗ WAJIB: skip jika tidak ada di DOM
			if (!input.length) return;

			const fieldWrapper = input.closest(".field");
			const label = fieldWrapper.find("label").text() || name;

			const rules = [];

			// required
			if (validationConfig[name].required) {
				rules.push({
					type: "empty",
					prompt: `${label} wajib diisi`,
				});
			}

			// OPTIONAL: extend rule lain kalau ada
			if (validationConfig[name].type === "number") {
				rules.push({
					type: "number",
					prompt: `${label} harus angka`,
				});
			}

			if (validationConfig[name].type === "email") {
				rules.push({
					type: "email",
					prompt: `${label} tidak valid`,
				});
			}

			fields[name] = {
				identifier: name,
				rules: rules,
			};
		});

		// =====================================
		// 🔥 JIKA KOSONG → JANGAN INIT
		// =====================================
		if (!Object.keys(fields).length) {
			console.warn("VALIDATION SKIP: tidak ada field cocok di DOM");
			return;
		}

		// =====================================
		// 🔥 INIT FOMANTIC
		// =====================================
		form.form({
			inline: true,
			on: "blur",
			fields: fields,

			onFailure: function (errors) {
				const box = form.find(".ui.error.message");

				let html = "<ul class='list'>";
				errors.forEach((e) => {
					html += `<li>${e}</li>`;
				});
				html += "</ul>";

				box.html(html).show();

				return false;
			},

			onSuccess: function (event) {
				// CEK event karena bisa undefined saat dipanggil via form.form("validate form")
				if (event) {
					event.preventDefault(); // hanya jalan jika event ada
				}

				form.find(".ui.error.message").hide().empty(); // bersihkan error UI

				return false; // tetap cegah submit default
			},
		});
	}
	enhanceForm(isEdit=false) {
		const form=$("#form_modal");if(!form.length)return;
		form.find(".naskah-form-guide").remove();form.prepend(`<div class="ui info message naskah-form-guide"><div class="header">${isEdit?'Edit':'Buat'} Naskah Dinas</div><p>Field bertanda wajib harus dilengkapi. Kemajuan pengisian: <b data-naskah-progress>0%</b></p><div class="ui tiny indicating progress"><div class="bar"></div></div></div>`);
		const update=()=>{const required=form.find('.required.field:visible').filter(function(){return $(this).find('input,textarea,select').length;});let filled=0;required.each(function(){const field=$(this).find('input[type!=hidden],textarea,select').first();if(String(field.val()||'').trim()!=='')filled++;});const percent=required.length?Math.round(filled/required.length*100):100;form.find('[data-naskah-progress]').text(`${percent}%`);form.find('.naskah-form-guide .progress').progress({percent});};
		form.off('input.naskahProgress change.naskahProgress').on('input.naskahProgress change.naskahProgress','input,textarea,select',update);update();
	}

	/**
	 * SHOW FORM TAMBAH
	 */
	showAddForm() {
		const html = `
       
            <div class="field">
                <label>Judul</label>
                <input type="text" name="judul">
            </div>
           
      
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
