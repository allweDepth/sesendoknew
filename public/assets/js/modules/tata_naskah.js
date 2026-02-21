function loadKlasifikasi() {
	$.post(
		"/dynamic",
		{
			tbl: "ref_klasifikasi_keamanan",
			action: "dropdown",
		},
		function (res) {
			let data = res.data || [];
			let options = '<option value="">Pilih Klasifikasi</option>';

			data.forEach((d) => {
				options += `<option value="${d.id}">
                                ${d.uraian}
                            </option>`;
			});

			$(".klasifikasi-dropdown").html(options);
			$(".klasifikasi-dropdown").dropdown("refresh");
		},
		"json",
	);
}
const kelompokStyle = {
	A: { icon: "sitemap", color: "teal" },
	B: { icon: "mail", color: "blue" },
	C: { icon: "shield alternate", color: "purple" },
};
$(document).ready(function () {
	let selectedJenisId = null;
	$(".kelompok-card").on("click", function () {
		let id = $(this).data("id");

		$.post("/tata_naskah/load_jenis", { kelompok_id: id }, function (res) {
			let data = JSON.parse(res);

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
                </div>
            `;
				});

				html += `</div></div>`;
			}

			$("#jenis-list").html(html);
			$("#jenis-container").removeClass("hidden");
		});
	});

	$(document).on("click", ".jenis-item", function (e) {
		e.preventDefault();

		let id = $(this).data("id");
		selectedJenisId = id;

		// ===============================
		// 🔥 TAMBAHKAN BAGIAN INI
		// ===============================

		let namaJenis = $(this).data("nama");
		let kodeKelompok = $(this).data("kelompok");
		let namaKelompok = $(this).data("kelompok-nama");

		let style = kelompokStyle[kodeKelompok] || {
			icon: "file alternate",
			color: "grey",
		};

		// Ubah icon
		$("#icon_modal_main")
			.removeClass()
			.addClass(style.color + " " + style.icon + " icon");

		// Ubah header + badge
		$("#content_modal").html(`
        ${namaJenis}
        <div class="ui tiny ${style.color} label" style="margin-left:10px">
            ${kodeKelompok} — ${namaKelompok}
        </div>
    `);

		// ===============================
		// LANJUT LOAD FORM
		// ===============================

		$.post(
			"/tata_naskah/load_form",
			{ jenis_id: id },
			function (schema) {
				if (!schema) return;

				let formSchema = schema;
				let html = buildForm(formSchema);

				$("#form_modal").html(html);

				$("#mainModal")
					.modal({
						autofocus: false,
						closable: false,
						transition: "fade up",
					})
					.modal("show");

				setTimeout(function () {
					$(".ui.dropdown").dropdown();
					loadASN();
					loadKlasifikasi();
					initEditor();
				}, 200);
			},
			"json",
		);
	});

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
					html += `<div class="ui action input">
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
                        <input type="hidden" name="${field.name}">
                    `;

					break;
			}

			html += `</div>`;
		});

		html += `<button class="ui primary button">
                Simpan Draft
             </button>`;

		html += "</form>";

		return html;
	}

	function loadASN() {
		$.post(
			"/dynamic",
			{
				tbl: "asn",
				action: "dropdown",
			},
			function (res) {
				let data = res.data || [];
				let options = '<option value="">Pilih ASN</option>';

				data.forEach((d) => {
					options += `<option value="${d.id}">
                                ${d.uraian}
                            </option>`;
				});

				$(".asn-dropdown").html(options);
				$(".asn-dropdown").dropdown("refresh");
			},
			"json",
		);
	}
	$(document).on("click", ".generate-nomor", function () {
		$.post(
			"/tata_naskah/generate_nomor",
			{
				jenis_id: selectedJenisId,
			},
			function (res) {
				$('input[name="nomor"]').val(res.nomor);
			},
			"json",
		);
	});
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

			quill.on("text-change", function () {
				let hiddenInput = $(
					'input[name="' + editorId.replace("editor-", "") + '"]',
				);
				hiddenInput.val(quill.root.innerHTML);
			});
		});
	}
});
