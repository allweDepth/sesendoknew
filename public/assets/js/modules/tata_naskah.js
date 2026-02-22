/* ============================================================
   MODULE: TATA NASKAH DINAS
   FILE  : public/assets/js/modules/tata_naskah.js
   TUJUAN: 
   - Load kelompok → jenis → form
   - Update header modal dinamis
   - Generate nomor otomatis
   - Load ASN & Klasifikasi dropdown
   - Inisialisasi editor (untuk surat bebas)
============================================================ */

/* ============================================================
   STYLE ICON BERDASARKAN KELOMPOK
============================================================ */
const kelompokStyle = {
	A: { icon: "sitemap", color: "teal" },
	B: { icon: "mail", color: "blue" },
	C: { icon: "shield alternate", color: "purple" },
};

/* ============================================================
   READY
============================================================ */
$(document).ready(function () {
	let selectedJenisId = null;

	/* ========================================================
	   STEP 1: KLIK KELOMPOK
	======================================================== */
	$(".kelompok-card").on("click", function () {
		let id = $(this).data("id");

		$.post(
			"/tata_naskah/load_jenis",
			{ kelompok_id: id },
			function (data) {
				let grouped = {};

				data.forEach((j) => {
					if (!grouped[j.sub_kategori]) {
						grouped[j.sub_kategori] = [];
					}
					grouped[j.sub_kategori].push(j);
				});

				let html = "";

				for (let kategori in grouped) {
					html += `<div class="ui segment">
                        <h4 class="ui dividing header">${kategori || "Lainnya"}</h4>
                        <div class="ui relaxed divided list">`;

					grouped[kategori].forEach((j) => {
						html += `
                        <div class="item">
                            <a href="#"
                                class="jenis-item"
                                data-id="${j.id}"
                                data-nama="${j.nama}"
                                data-kelompok="${j.kelompok_kode}"
                                data-kelompok-nama="${j.kelompok_nama}">
                                <i class="file outline icon"></i>
                                <div class="content">
                                    <div class="header">${j.nama}</div>
                                </div>
                            </a>
                        </div>`;
					});

					html += `</div></div>`;
				}

				$("#jenis-list").html(html);
				$("#jenis-container").removeClass("hidden");
			},
			"json",
		);
	});

	/* ========================================================
	   STEP 2: KLIK JENIS NASKAH
	======================================================== */
	$(document).on("click", ".jenis-item", function (e) {
		e.preventDefault();

		let id = $(this).data("id");
		selectedJenisId = id;

		/* ==============================
		   UPDATE HEADER MODAL DINAMIS
		============================== */
		let namaJenis = $(this).data("nama");
		let kodeKelompok = $(this).data("kelompok");
		let namaKelompok = $(this).data("kelompok-nama");

		let style = kelompokStyle[kodeKelompok] || {
			icon: "file alternate",
			color: "grey",
		};

		// Ganti icon utama
		$("#icon_modal_main")
			.removeClass()
			.addClass(style.color + " " + style.icon + " icon");

		// Ganti judul + badge kelompok
		$("#content_modal").html(`
			${namaJenis}
			<div class="ui tiny ${style.color} label" style="margin-left:10px">
				${kodeKelompok} — ${namaKelompok}
			</div>
		`);

		/* ==============================
		   LOAD FORM SCHEMA
		============================== */
		$.post(
			"/tata_naskah/schema",
			{ jenis_id: id },
			function (res) {
				if (!res || !res.schema) return;

				let formSchema = res.schema;
				let html = buildForm(formSchema);

				$("#form_modal").html(html);
				/* ===============================
					PRELOAD NOMOR OTOMATIS
				=============================== */
				let nomorInput = $('input[name="nomor"]');
				if (res.nomor_auto && nomorInput.length) {
					nomorInput.val(res.nomor_auto);
				}
				$("#mainModal")
					.modal({
						autofocus: false,
						closable: false,
						transition: "fade up",
					})
					.modal("show");

				setTimeout(function () {
					$(".ui.dropdown").dropdown();

					// isi dropdown ASN
					let asnOptions = '<option value="">Pilih ASN</option>';
					res.asn.forEach((a) => {
						asnOptions += `<option value="${a.id}">${a.uraian}</option>`;
					});
					$(".asn-dropdown").html(asnOptions);

					// isi dropdown klasifikasi
					let klasifikasiOptions =
						'<option value="">Pilih Klasifikasi</option>';
					res.klasifikasi.forEach((k) => {
						klasifikasiOptions += `<option value="${k.id}">${k.uraian}</option>`;
					});
					$(".klasifikasi-dropdown").html(klasifikasiOptions);

					$(".ui.dropdown").dropdown("refresh");

					initEditor();
				}, 100);
			},
			"json",
		);
	});

	/* ========================================================
	   BUILD FORM DARI SCHEMA JSON
	======================================================== */
	function buildForm(schema) {
		let html = '<form class="ui form" id="formNaskah">';

		schema.forEach((field) => {
			html += `<div class="field">
				<label>${field.label}</label>`;

			switch (field.type) {
				case "text":
					html += `<input type="text" name="${field.name}">`;
					break;

				case "date":
					html += `<input type="date" name="${field.name}">`;
					break;

				case "auto_nomor":
					html += `
						<div class="ui action input">
							<input type="text" name="${field.name}" readonly>
							<button type="button" class="ui button generate-nomor">
								Generate
							</button>
						</div>`;
					break;

				case "dropdown_asn":
					html += `<select class="ui dropdown asn-dropdown" name="${field.name}"></select>`;
					break;

				case "dropdown_klasifikasi":
					html += `<select class="ui dropdown klasifikasi-dropdown" name="${field.name}"></select>`;
					break;

				case "editor":
					html += `
						<div id="editor-${field.name}" 
							class="quill-editor" 
							style="height:250px;"></div>
						<input type="hidden" name="${field.name}">`;
					break;
			}

			html += `</div>`;
		});

		html += `
			<button type="submit" class="ui primary button">
				Simpan Draft
			</button>
		</form>`;

		return html;
	}

	/* ========================================================
	   GENERATE NOMOR OTOMATIS
	======================================================== */
	$(document).on("click", ".generate-nomor", function () {
		let klasifikasiId = $('select[name="klasifikasi_id"]').val();

		if (!klasifikasiId) {
			alert("Pilih klasifikasi terlebih dahulu");
			return;
		}

		$.post(
			"/tata_naskah/generateNomor",
			{
				klasifikasi_id: klasifikasiId,
			},
			function (res) {
				if (res.data && res.data.nomor) {
					$('input[name="nomor"]').val(res.data.nomor);
				}
			},
			"json",
		);
	});

	/* ========================================================
	   INIT QUILL EDITOR (HANYA UNTUK FREE_EDITOR)
	======================================================== */
	function initEditor() {
		$(".quill-editor").each(function () {
			let editorId = $(this).attr("id");

			let quill = new Quill("#" + editorId, {
				theme: "snow",
				placeholder: "Tulis isi naskah...",
				modules: {
					toolbar: [
						[{ header: [1, 2, false] }],
						["bold", "italic", "underline"],
						[{ list: "ordered" }, { list: "bullet" }],
						["link"],
						["clean"],
					],
				},
			});

			// Sinkron ke hidden input
			quill.on("text-change", function () {
				let hiddenInput = $(
					'input[name="' + editorId.replace("editor-", "") + '"]',
				);

				hiddenInput.val(quill.root.innerHTML);
			});
		});
	}
});
