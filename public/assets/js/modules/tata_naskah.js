/**
 * ============================================================
 * TATA NASKAH MODULE
 * ============================================================
 *
 * Arsitektur:
 * - 1 Module (tidak dipecah)
 * - Menu /daftar → pakai TableManager + FormEngine (CRUD)
 * - Menu /buat   → hanya bind klik & load schema
 * - Modal global → pakai #mainModal (bukan modal custom)
 * - Submit       → lewat btnSubmit global (FlyoutController)
 * - AJAX         → lewat core ajax.js (this.ajax)
 *
 * Tidak merusak:
 * - Engine CRUD
 * - FlyoutController
 * - UIConfig system
 * ============================================================
 */

class TataNaskahModule {
	constructor() {
		/* ======================================================
		   GLOBAL STATE & AJAX
		====================================================== */
		this.state = window.app.state;
		this.ajax = window.app.ajax;

		// Default table aktif (untuk daftar)
		this.state.setTable("tata_naskah");

		/* ======================================================
		   ENGINE CRUD (khusus menu daftar)
		====================================================== */
		this.tableManager = null;
		this.formEngine = null;
		this.formContainer = null;

		/* ======================================================
		   CONTAINER
		====================================================== */
		this.mainContainer = "#main-content";
		this.formContainerSelector = "#form-container";
	}

	/* ======================================================
	   INIT MODULE (ROUTE-AWARE)
	====================================================== */
	init() {
		// Selalu bind event buat
		this.bindKelompokClick();
		// this.bindPilihJenis();

		// Jika ada container table → berarti daftar
		if (document.getElementById("table-container")) {
			this.initEngine();
			this.bindCrudEvents();
		}
		console.log("TataNaskah INIT jalan");
	}

	/* ======================================================
	   INIT KHUSUS MENU BUAT
	   - Tidak menjalankan CRUD engine
	   - Tidak menyentuh FormEngine default
	   - Hanya bind event card & jenis
	====================================================== */
	initBuat() {
		this.bindKelompokClick();
		this.bindPilihJenis();
	}

	/* ======================================================
	   ENGINE CRUD (MENU DAFTAR)
	====================================================== */
	initEngine() {
		this.tableManager = new TableManager({
			state: this.state,
			ajax: this.ajax,
			container: "#table-container",
		});

		this.formContainer = new FormContainerManager({
			container: this.formContainerSelector,
		});

		this.formEngine = new FormEngine({
			state: this.state,
			ajax: this.ajax,
			formSelector: "#dynamic-form",
		});

		this.tableManager.init();
		this.formContainer.init();
		this.formEngine.init();
	}

	/* ======================================================
	   EVENT CRUD (MENU DAFTAR)
	====================================================== */
	bindCrudEvents() {
		// Tombol tambah (CRUD biasa)
		$(document).on("click", "#btn-add", () => {
			this.showAddForm();
		});

		// Setelah form sukses → reload data
		$(document).on("form:success", () => {
			if (this.tableManager) {
				this.tableManager.loadData();
			}
		});
	}

	/* ======================================================
	   MENU BUAT → KLIK KELOMPOK
	   - Load jenis berdasarkan kelompok_id
	   - Tampilkan list jenis
	====================================================== */
	bindKelompokClick() {
		$(document).on("click", ".kelompok-card", (e) => {
			const id = $(e.currentTarget).data("id");

			this.ajax.request({
				data: {
					module: "tata_naskah",
					action: "loadJenis",
					kelompok_id: id,
				},
				success: (res) => {
					if (!res.success) return;

					let html = "";

					res.data.forEach((item) => {
						html += `
							<div class="item">
								<div class="right floated content">
									<button
	class="ui primary button"
	data-ui="open-form"
	data-container="modal"
	data-jns="add"
	data-tbl="trx_naskah_dinas"
	data-config="trx_naskah_dinas.default"
	data-id="${item.id}"
>
	Pilih
</button>
								</div>
								<div class="content">
									<strong>${item.nama}</strong>
									<div class="meta">
										${item.sub_kategori ?? ""}
									</div>
								</div>
							</div>
						`;
					});

					$("#jenis-list").html(html);

					// Tampilkan container jenis
					$("#jenis-container").removeClass("hidden");

					// Optional scroll smooth
					$("html, body").animate(
						{
							scrollTop: $("#jenis-container").offset().top - 80,
						},
						300,
					);
				},
			});
		});
	}

	/* ======================================================
	   MENU BUAT → PILIH JENIS
	   - Ambil schema dari backend
	   - Render ke form_modal (GLOBAL MODAL)
	   - Tampilkan #mainModal
	====================================================== */
	bindPilihJenis() {
		$(document).on("click", ".pilih-jenis", (e) => {
			const jenisId = $(e.currentTarget).data("id");

			// Set state
			this.state.setModule("tata_naskah");
			this.state.setTable("tata_naskah");
			this.state.action = "create";

			this.ajax.request({
				data: {
					module: "tata_naskah",
					action: "loadJenis",
					tbl: "tata_naskah", // hanya untuk lolos validasi gateway
					kelompok_id: id,
				},
				success: (res) => {
					const formSelector = "#form_modal";

					// Reset form
					$(formSelector).empty();

					// Hidden jenis_id
					$(formSelector).append(`
					<input type="hidden" name="jenis_id" value="${jenisId}">
				`);

					// Normalisasi schema
					const elements = this.normalizeSchema(
						res.schema,
						res.asn,
						res.klasifikasi,
					);

					// Buat instance FormEngine khusus modal
					this.formEngine = new FormEngine({
						state: this.state,
						ajax: this.ajax,
						formSelector: formSelector,
					});

					// Render via FormEngine
					FormEngine.render(
						$(formSelector),
						elements,
						this.formEngine,
						{}, // layout optional
					);

					this.formEngine.init();

					// Pre-fill nomor otomatis jika ada
					if (res.nomor_auto) {
						$(formSelector).find('input[name="nomor"]').val(res.nomor_auto);
					}

					// Tampilkan modal global
					$("#mainModal").modal("show");
				},
			});
		});
	}

	/* ======================================================
	   FORM TAMBAH (CRUD BIASA)
	====================================================== */
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

	/* ======================================================
	   DESTROY MODULE
	   (Digunakan jika SPA navigation / reload partial)
	====================================================== */
	destroy() {
		if (this.tableManager) this.tableManager.destroy();
		if (this.formEngine) this.formEngine.destroy();
		if (this.formContainer) this.formContainer.destroy();

		$(document).off("click", "#btn-add");
		$(document).off("form:success");
		$(document).off("click", ".kelompok-card");
		$(document).off("click", ".pilih-jenis");

		$(this.mainContainer).empty();
	}
	/**
	 * ======================================================
	 * NORMALIZE BACKEND SCHEMA → UIConfig FORMAT
	 * ======================================================
	 * Mengubah schema backend menjadi format
	 * yang dipahami oleh FormEngine.render()
	 */
	normalizeSchema(schema, asn, klasifikasi) {
		let elements = [];

		// Field nomor otomatis
		elements.push({
			type: "text",
			prop: {
				name: "nomor",
				label: "Nomor",
				required: true,
			},
		});

		// Dropdown klasifikasi (dari backend)
		if (Array.isArray(klasifikasi)) {
			elements.push({
				type: "dropdown",
				prop: {
					name: "klasifikasi_id",
					label: "Klasifikasi",
					options: klasifikasi.map((k) => ({
						value: k.id,
						text: k.uraian,
					})),
				},
			});
		}

		// Render field schema dinamis
		if (Array.isArray(schema)) {
			schema.forEach((field) => {
				elements.push({
					type: field.type || "text",
					prop: {
						name: field.name,
						label: field.label,
						required: field.required || false,
					},
				});
			});
		}

		return elements;
	}
}
