/* ============================================================
   MODULE: TATA NASKAH DINAS
   FULLY INTEGRATED WITH MASTER ENGINE (app.js)
============================================================ */

$(document).ready(function () {
	/* ========================================================
       STYLE ICON KELOMPOK
    ======================================================== */
	const kelompokStyle = {
		A: { icon: "sitemap", color: "teal" },
		B: { icon: "mail", color: "blue" },
		C: { icon: "shield alternate", color: "purple" },
	};

	/* ========================================================
       CLICK KELOMPOK → LOAD JENIS (VIA AjaxEngine)
    ======================================================== */
	// const ajaxJenis = new AjaxEngine("/tata_naskah/load_jenis");
	const ajaxJenis = new AjaxEngine(
    AppConfig.apiUrl + "tata_naskah/load_jenis"
);

	$(document).on("click", ".kelompok-card", function () {
		let kelompokId = $(this).data("id");

		ajaxJenis.request({
			data: { kelompok_id: kelompokId },
			success: function (res) {
				let data;

				// Jika backend return array langsung
				if (Array.isArray(res)) {
					data = res;
				}
				// Jika backend return format engine
				else if (res.success && res.data) {
					data = res.data;
				}

				if (!data || !data.length) return;

				renderJenis(data);
			},
		});
	});

	/* ========================================================
       RENDER LIST JENIS
    ======================================================== */
	function renderJenis(data) {
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
                    <h4 class="ui dividing header">${kategori || "Lainnya"}</h4>
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
                            data-jns="add"
                            data-tbl="${j.kode_form}"
														 data-jenis-id="${j.id}"
                            data-kelompok="${j.kelompok_kode}"
                            data-kelompok-nama="${j.kelompok_nama}"
                            data-nama="${j.nama}">
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

	/* ========================================================
       UPDATE HEADER MODAL SAAT OPEN FORM
       (TIDAK override engine)
    ======================================================== */
	$(document).on("click", ".btn-open-naskah", function () {
		let namaJenis = $(this).data("nama");
		let kodeKelompok = $(this).data("kelompok");
		let namaKelompok = $(this).data("kelompok-nama");

		let style = kelompokStyle[kodeKelompok] || {
			icon: "file alternate",
			color: "grey",
		};

		$("#icon_modal").attr("class", style.color + " " + style.icon + " icon");

		$("#content_modal").html(`
            ${namaJenis}
            <div class="ui tiny ${style.color} label" style="margin-left:10px">
                ${kodeKelompok} — ${namaKelompok}
            </div>
        `);
	});

	/* ========================================================
       PLUGIN: STRUCTURED SK (DINAMIS SECTION)
       DIINTEGRASIKAN KE FormContainerManager
    ======================================================== */

	if (window.formContainerManager) {
		window.formContainerManager.registerPlugin(
			"tata_naskah.sk",
			function ({ container }) {
				function addRow(group) {
					let row = `
                    <div class="ui fluid action input dynamic-row">
                        <input type="text" name="${group}[]">
                        <button type="button"
                                class="ui red icon button remove-row">
                            <i class="trash icon"></i>
                        </button>
                    </div>
                `;

					container.find(`.dynamic-${group}`).append(row);
				}

				addRow("menimbang");
				addRow("mengingat");
				addRow("menetapkan");

				container.on("click", ".remove-row", function () {
					$(this).closest(".dynamic-row").remove();
				});
			},
		);
	}
});
