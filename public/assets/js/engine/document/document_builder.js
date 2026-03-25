class DocumentBuilder {
	constructor(container, type) {
		this.container = container; // container utama form
		this.type = type; // tipe dokumen

		this.schema = null; // schema dari backend
		this.data = {}; // data tambahan dari backend
	}

	// ======================================================
	// RENDER
	// ======================================================

	render() {
		if (!this.schema) return;

		this.container.off(".documentBuilder");
		this.container.empty();

		// =====================================
		// FIX: VALIDASI ARRAY (KRITIS)
		// =====================================
		let fields = Array.isArray(this.schema) ? this.schema : this.schema.sections || [];

		if (!Array.isArray(fields)) {
			console.warn("Schema invalid:", this.schema);
			return;
		}

		let html = [];

		fields.forEach((field) => {
			let part = this.renderElement(field);
			if (part) html.push(part);
		});

		// =====================================
		// FIX: WRAP FORM (WAJIB UNTUK SUBMIT)
		// =====================================
		this.container.append(`
		
			${html.join("")}
			<div class="ui divider"></div>
			<button class="ui green button" type="submit">
				<i class="check icon"></i> Simpan
			</button>
		
    
	`);

		this.bindEvents();
		this.initFomantic();
		UIComponents.initCalendar(this.container);

		// 🔥 inject dulu
		this.injectStructureData();

		// 🔥 baru init dropdown
		if (window.dropdownEngine) {
			window.dropdownEngine.init();
			// 🔥 APPLY VALUE DROPDOWN DARI DATA
			Object.entries(this.data || {}).forEach(([key, val]) => {
				let el = this.container.find(`[name="${key}"]`);
				if (!el.length) return;

				if (el.hasClass("ui dropdown")) {
					el.dropdown("set selected", val); // .
				}
			});
		}
	}
	injectStructureData() {
		let self = this;

		Object.entries(this.data || {}).forEach(([section, rows]) => {
			let table = self.container.find(`table[name="${section}"]`);
			if (!table.length) return;

			if (!Array.isArray(rows)) return;

			let cols = table.data("columns") || [];

			rows.forEach((rowData) => {
				let row = $(self.buildRow(section, cols.length));

				// 🔥 FIX FINAL: paksa ID jika tidak ada
				let forcedId = rowData.id ?? rowData._id ?? rowData.nip;

				if (forcedId) {
					row.attr("data-id", forcedId);
				}

				// 🔥 TYPE
				if (rowData.type) {
					row.attr("data-type", rowData.type);
				}

				// 🔥 ALIGN
				if (rowData.align) {
					row.attr("data-align", rowData.align);
					row.find(".doc-editor").css("text-align", rowData.align);
				}

				// 🔥 TEXT (editable_table)
				if (rowData.text !== undefined) {
					row.find(".doc-editor").html(rowData.text || "");
				}

				// 🔥 TABLE NORMAL (nama_ditugaskan)
				Object.entries(rowData).forEach(([key, val]) => {
					let cell = row.find(`[data-key="${key}"]`);
					if (!cell.length) return;

					if (cell.find(".doc-editor").length) {
						cell.find(".doc-editor").html(val || "");
					} else {
						cell.text(val || "");
					}
				});

				table.find("tbody").append(row);
				// 🔥 SYNC TOOLBAR STATE (INI KUNCI)
				this.syncToolbarState(row, rowData);

				// 🔥 INIT UI DI ROW BARU
				row.find(".ui.dropdown").dropdown(); // .
				row.find(".ui.checkbox").checkbox(); // .
			});
		});
	}
	syncToolbarState(row, rowData) {
		// =========================
		// TYPE
		// =========================
		let type = rowData.type || "paragraph";

		row.find(`[data-type="${type}"]`).addClass("active");

		// =========================
		// ALIGN
		// =========================
		let align = rowData.align || "justify";

		row.find(`[data-align="${align}"]`).addClass("active");

		// =========================
		// STYLE (ARRAY)
		// =========================
		if (Array.isArray(rowData.style)) {
			rowData.style.forEach((s) => {
				row.find(`[data-style="${s}"]`).addClass("active");
			});
		}

		// =========================
		// FORMAT
		// =========================
		if (rowData.format) {
			row.find(`[data-format="${rowData.format}"]`).addClass("active");
		}
	}
	// ======================================================
	// ELEMENT ROUTER
	// ======================================================

	renderElement(field) {
		switch (field.type) {
			case "message":
				return UIComponents.message(field);

			case "section":
				return `
				<h4 class="ui horizontal divider header">
					<i class="${field.icon || "file"} icon"></i>
					${field.label}
				</h4>`;

			case "auto_nomor":
				return UIExtensions.renderAutoNumber({
					label: field.label,
					name: field.name,
					value: this.data.nomor_auto,
				});

			case "fields":
				return this.renderFields(field);

			case "editable_table":
				return this.renderEditableTable(field);

			case "table":
				return this.renderDataTable(field);

			case "dropdown_ajax":
				return field.target
					? UIComponents.lookupDropdown(field.label, field.name, field.source, field.target)
					: UIComponents.lookupDropdown(field.label, field.name, field.source);

			case "toggle":
				return UIComponents.toggle(field.label, field.name);

			case "textarea":
				return UIComponents.textarea(field.label, field.name);

			case "calendar":
			case "date":
				return UIComponents.calendar(field.label, field.name, field.calendarType || field.prop?.calendarType);

			default:
				return UIComponents.input(field.label, field.name, "text");
		}
	}

	renderFields(group) {
		let parts = [];
		parts.push(`<div class="${group.size || "two"} fields">`);

		(group.fields || []).forEach((f) => {
			parts.push(this.renderElement(f));
		});

		parts.push("</div>");

		return parts.join("");
	}

	// ======================================================
	// TABLE
	// ======================================================

	renderDataTable(field) {
		let key = field.name;
		let columns = field.columns || [];

		let showAction = this.hasActionColumn(key); // cek apakah ada kolom aksi

		return `
	<table class="ui celled table" 
		name="${key}" 
		data-type="table" // 🔥 FIX: tandai ini tabel biasa
		data-columns='${JSON.stringify(columns)}'>
		<thead>
		<tr>
			${columns.map((c) => `<th>${c}</th>`).join("")}
			${showAction ? `<th class="collapsing">Aksi</th>` : ""}
		</tr>
		</thead>
		<tbody></tbody>
	</table>`;
	}
	// 🔥 tentukan tabel mana yang punya aksi
	hasActionColumn(tableName) {
		return ["nama_ditugaskan"].includes(tableName); // bisa tambah lagi nanti
	}
	renderEditableTable(field) {
		let key = field.name;
		let columns = field.columns || ["URAIAN"];

		let showAction = this.hasActionColumn(key);
		// 🔥 ambil label jika ada, fallback ke name
		let label = field.label || key; // tampil ke user
		return `
<div class="doc-table-block"> <!-- // wrapper supaya jelas section -->
	<div class="doc-table-title" 
	style="
		display:flex; 
		justify-content:flex-end; 
		align-items:center;
		margin-top:12px; // // jarak dari atas
	">
	<span style="font-size:11px; opacity:.5;">${label}</span>
</div>

	<table class="ui celled structured table" 
		name="${key}" 
		data-type="editable_table"
		data-columns='${JSON.stringify(columns)}' style="margin-top:0;">
		<thead>
			<tr>
				${columns.map((c) => `<th>${c}</th>`).join("")}
				${showAction ? `<th class="collapsing">Aksi</th>` : ""}
				<th class="collapsing">
					<button type="button" class="ui mini green icon button btn-add-row" data-section="${key}">
						<i class="plus icon"></i>
					</button>
				</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table></div>`;
	}

	// ======================================================
	// EVENTS
	// ======================================================

	// ======================================================
	// EVENTS (FINAL FIX - TANPA execCommand)
	// ======================================================
	bindEvents() {
		let self = this;

		// ==============================
		// ADD ROW
		// ==============================
		this.container.off("click", ".btn-add-row");
		this.container.on("click", ".btn-add-row", function () {
			let section = $(this).data("section");
			let table = self.container.find(`table[name="${section}"]`);
			let cols = table.data("columns")?.length || 2;

			table.find("tbody").append(self.buildRow(section, cols));

			let newRow = table.find("tbody tr:last");
			newRow.find(".ui.checkbox").checkbox();
		});

		// ==============================
		// DELETE ROW
		// ==============================
		this.container.off("click", ".btn-del-row");
		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});

		// ==============================
		// DROPDOWN SELECT
		// ==============================
		$(document).off("dropdown:select.documentBuilder");

		$(document).on("dropdown:select.documentBuilder", (e, payload) => {
			const { target, data, name } = payload;

			if (name === "penandatangan") {
				this.container.find('[name="jbt_pemberi_tgs"]').val(data.jabatan || "");
				this.container.find('[name="pangkat_pemberi_tgs"]').val(data.pangkat || "");
			}

			let finalTarget = target;

			if (!finalTarget) {
				const input = this.container.find(`[name="${name}"]`);
				finalTarget = input.data("target-table");
			}

			if (!finalTarget && name === "asn") {
				finalTarget = "nama_ditugaskan";
			}

			if (finalTarget) {
				this.insertToTable(finalTarget, data);
			}
		});

		// ==============================
		// TOOLBAR HANDLER (NO execCommand)
		// ==============================
		this.container.off("click", ".doc-toolbar button");

		this.container.on("click", ".doc-toolbar button", function () {
			let btn = $(this);
			let tr = btn.closest("tr");
			let toolbar = btn.closest(".doc-toolbar");
			let editor = tr.find(".doc-editor");

			editor.focus();

			// =====================================
			// 🔥 HELPER RANGE API
			// =====================================
			function wrapSelection(tag) {
				let sel = window.getSelection();
				if (!sel.rangeCount) return;

				let range = sel.getRangeAt(0);
				let content = range.extractContents();

				let el = document.createElement(tag);
				el.appendChild(content);

				range.insertNode(el);

				sel.removeAllRanges();
				let newRange = document.createRange();
				newRange.selectNodeContents(el);
				sel.addRange(newRange);
			}

			// =====================================
			// STYLE (MODERN)
			// =====================================
			let style = btn.data("style");

			if (style) {
				let current = tr.attr("data-style") || ""; // // ambil existing
				let arr = current ? current.split(",") : []; // // jadi array

				if (arr.includes(style)) {
					arr = arr.filter((s) => s !== style); // // toggle OFF
				} else {
					arr.push(style); // // toggle ON
				}

				tr.attr("data-style", arr.join(",")); // // simpan ke TR
				switch (style) {
					case "bold":
						wrapSelection("strong"); // ✅
						break;

					case "italic":
						wrapSelection("em"); // ✅
						break;

					case "underline":
						wrapSelection("u"); // ✅
						break;
				}

				btn.toggleClass("active");
				return;
			}

			// =====================================
			// GROUP BUTTON
			// =====================================
			let groupKey = btn.data("type") ? "type" : btn.data("align") ? "align" : btn.data("format") ? "format" : null;

			if (groupKey) {
				toolbar.find(`button[data-${groupKey}]`).removeClass("active");
				btn.addClass("active");
			}

			// =====================================
			// TYPE (REAL RENDER)
			// =====================================
			let type = btn.data("type");

			if (type) {
				tr.attr("data-type", type);

				// let html = editor.html().trim();

				// if (type === "list") {
				// 	editor.html(`<ul><li>${html}</li></ul>`);
				// }

				// if (type === "numbered") {
				// 	editor.html(`<ol><li>${html}</li></ol>`);
				// }

				// if (type === "paragraph") {
				// 	editor.html(editor.text());
				// }
			}

			// =====================================
			// ALIGN
			// =====================================
			let align = btn.data("align");

			if (align) {
				tr.attr("data-align", align);
				editor.css("text-align", align);
			}

			// =====================================
			// FORMAT LABEL (MANUAL BUTTON)
			// =====================================
			let format = btn.data("format");

			if (format === "label") {
				let val = editor.text().trim();
				tr.attr("data-format", "label"); // // SIMPAN KE TR
				// if (val && !val.includes(":")) {
				// 	let parts = val.split(" ");

				// 	if (parts.length >= 2) {
				// 		let label = parts.shift();
				// 		let rest = parts.join(" ");

				// 		editor.html(`<strong>${label}</strong> : ${rest}`);
				// 	} else {
				// 		editor.html(val + " : ");
				// 	}
				// }
			}
		});

		// =====================================
		// AUTO FORMAT "NAMA : ISI" (GLOBAL)
		// =====================================
		this.container.off("blur", ".doc-editor");

		this.container.on("blur", ".doc-editor", function () {
			let el = $(this);
			let text = el.text().trim();

			if (text.includes(":")) return;

			let parts = text.split(" ");

			if (parts.length >= 2) {
				let label = parts.shift();
				let rest = parts.join(" ");

				el.html(`<strong>${label}</strong> : ${rest}`);
			}
		});
	}

	// ======================================================
	// INSERT TABLE (FIX FINAL - SCHEMA DRIVEN)
	// ======================================================

	insertToTable(target, data) {
		let table = this.container.find(`table[name="${target}"]`);

		// =====================================
		// FIX: fallback cari semua table editable
		// =====================================
		if (!table.length) {
			table = this.container.find(`table[data-type="editable_table"]`);
		}

		// HARD STOP kalau tetap tidak ada
		if (!table.length) return;

		let tbody = table.find("tbody");

		// =====================================
		// FIX: fallback unique key
		// =====================================
		// =====================================
		// FIX: pastikan id selalu ada
		// =====================================
		let id = data.nip ?? data.id ?? data._id; // FIX: HARUS FIX ID DARI DB
		if (!id) return; // HARD STOP kalau tidak ada id

		if (tbody.find(`tr[data-id="${id}"]`).length > 0) return;

		let columns = table.data("columns") || []; // 🔥 ambil dari schema, bukan header

		let row = `<tr data-id="${id || Date.now()}">`; // // FIX

		columns.forEach((col) => {
			let key = col.toLowerCase().replace(/\s+/g, "_"); // 🔥 normalisasi key dari schema
			// =====================================
			// FIX: fallback mapping
			// =====================================
			let value;

			// FIX KHUSUS ASN
			if (target === "nama_ditugaskan") {
				if (key === "nama") {
					// 🔥 jika masih ada field pecahan → paksa rakit ulang
					if (data.gelar_depan || data.gelar) {
						const depan = data.gelar_depan ? data.gelar_depan + " " : "";
						const nama = data.nama ?? data.uraian ?? "";
						const belakang = data.gelar ? ", " + data.gelar : "";
						value = (depan + nama + belakang).trim();
					} else {
						value = data.nama ?? data.uraian ?? "";
					}
				} else if (key === "pangkat") {
					// 🔥 PRIORITAS DATA SERVER
					if (data.pangkat) {
						value = data.pangkat;
					} else {
						value = this.convertPangkat(data.golongan, data.ruang);
					}
				} else {
					// 🔥 INI YANG HILANG → PENYEBAB UNDEFINED
					value = data[key] ?? data[col] ?? data.text ?? "";
				}
			} else {
				value = data[key] ?? data[col] ?? data.nama ?? data.text ?? "";
			}

			row += `
			<td contenteditable="true" data-key="${key}">
				${value}
			</td>`;
		});

		row += `
		<td class="collapsing">
			<button type="button" class="ui mini red icon button btn-del-row">
				<i class="trash icon"></i>
			</button>
		</td>
		</tr>`;

		tbody.append(row);
	}

	// ======================================================
	// LEGACY ROW
	// ======================================================

	// ======================================================
	// BUILD ROW (FINAL FIX TATA NASKAH)
	// ======================================================
	buildRow(section, columns) {
		let table = this.container.find(`table[name="${section}"]`);
		let cols = table.data("columns") || [];

		let row = `<tr data-type="paragraph" data-align="justify">`; // ✅ default resmi
		let cells = [];

		cols.forEach((col, i) => {
			let key = col.toLowerCase().replace(/\s+/g, "_");

			if (i === 0) {
				cells.push(`
			<td data-key="${key}" class="doc-cell">
				<div class="doc-editor" contenteditable="true" style="text-align: justify;"></div>
				<div class="doc-toolbar">

					<div class="btn-group">
						<button type="button" class="ui icon button" data-type="paragraph">
							<i class="align left icon"></i>
						</button>
						<button type="button" class="ui icon button" data-type="list">
							<i class="list ul icon"></i>
						</button>
						<button type="button" class="ui icon button" data-type="numbered">
							<i class="list ol icon"></i>
						</button>
					</div>

					<div class="divider"></div>

					<div class="btn-group">
	<button type="button" class="ui icon button" data-align="left">
		<i class="align left icon"></i>
	</button>
	<button type="button" class="ui icon button" data-align="center">
		<i class="align center icon"></i>
	</button>
	<button type="button" class="ui icon button" data-align="right">
		<i class="align right icon"></i>
	</button>
	<button type="button" class="ui icon button" data-align="justify">
		<i class="align justify icon"></i>
	</button>
</div>

					<div class="divider"></div>

					<div class="btn-group">
	<button type="button" class="ui icon button" data-style="bold">
		<i class="bold icon"></i>
	</button>
	<button type="button" class="ui icon button" data-style="italic">
		<i class="italic icon"></i>
	</button>
	<button type="button" class="ui icon button" data-style="underline">
		<i class="underline icon"></i>
	</button>
</div>

					<div class="divider"></div>

					<div class="btn-group">
	<button type="button" class="ui icon button" data-format="label">
		<i class="tag icon"></i>
	</button>
</div>

				</div>
			</td>
			`);
			} else {
				cells.push(`<td contenteditable="true" data-key="${key}"></td>`);
			}
		});

		cells.push(`
	<td class="collapsing">
		<button type="button" class="ui mini red icon button btn-del-row">
			<i class="trash icon"></i>
		</button>
	</td>`);

		row += cells.join("") + "</tr>";

		return row;
	}

	// ======================================================
	// INIT UI
	// ======================================================

	initFomantic() {
		this.container.find(".ui.dropdown").dropdown();
		this.container.find(".doc-type").dropdown();
		// this.container.find(".ui.calendar").calendar({ type: "date" });
		this.container.find(".ui.checkbox").checkbox();
	}

	// ======================================================
	// COLLECT DATA (FIX FINAL - PRESERVE FORMAT)
	// ======================================================
	collectStructure() {
		let result = {};

		this.container.find("table[name]").each(function () {
			let tableName = $(this).attr("name");
			let type = $(this).data("type");
			let rows = [];

			$(this)
				.find("tbody tr")
				.each(function () {
					let row = {};
					let tr = $(this);

					let rowType = tr.attr("data-type"); // // ambil type
					let rowAlign = tr.attr("data-align"); // // ambil align
					let rowStyle = tr.attr("data-style"); // // ambil style
					let rowFormat = tr.attr("data-format"); // // ambil format

					$(this)
						.find("td[data-key]")
						.each(function () {
							let key = $(this).data("key");
							let value;

							let editor = $(this).find(".doc-editor");

							if (editor.length) {
								value = editor.html().trim(); // ✅ FIX: preserve HTML
							} else {
								value = $(this).text().trim();
							}

							if (type === "editable_table") {
								if (key === "uraian") {
									row.text = value;
								} else {
									row[key] = value;
								}
							} else {
								row[key] = value;
							}
						});

					if (type === "editable_table") {
						let rowStyle = tr.attr("data-style"); // // AMBIL STYLE
						let rowFormat = tr.attr("data-format"); // // AMBIL FORMAT

						row.type = rowType || "paragraph";
						row.text = row.text || "";

						if (rowAlign) {
							row.align = rowAlign;
						}

						if (rowStyle) {
							row.style = rowStyle.split(","); // // jadi array
						}

						if (rowFormat) {
							row.format = rowFormat;
						}
					}

					let rowId = tr.attr("data-id");
					if (rowId) {
						row._id = rowId;
					}

					if (Object.keys(row).length) {
						rows.push(row);
					}
				});

			if (type === "editable_table") {
				result[tableName] = rows.map((r) => ({
					...(r._id ? { _id: r._id } : {}), // // id tetap
					type: r.type || "paragraph", // // type tetap
					text: r.text || "", // // text tetap
					...(r.align ? { align: r.align } : {}), // // align tetap
					...(r.style ? { style: r.style } : {}), // // TAMBAH STYLE
					...(r.format ? { format: r.format } : {}), // // TAMBAH FORMAT
				}));
			} else {
				result[tableName] = rows;
			}
		});

		return result;
	}
	// helper konversi pangkat ASN (mirror backend PHP)
	convertPangkat(golongan, ruang) {
		const map = {
			// Golongan I
			"I/a": "Juru Muda",
			"I/b": "Juru Muda Tingkat I",
			"I/c": "Juru",
			"I/d": "Juru Tingkat I",

			// Golongan II
			"II/a": "Pengatur Muda",
			"II/b": "Pengatur Muda Tingkat I",
			"II/c": "Pengatur",
			"II/d": "Pengatur Tingkat I",

			// Golongan III
			"III/a": "Penata Muda",
			"III/b": "Penata Muda Tingkat I",
			"III/c": "Penata",
			"III/d": "Penata Tingkat I",

			// Golongan IV
			"IV/a": "Pembina",
			"IV/b": "Pembina Tingkat I",
			"IV/c": "Pembina Utama Muda",
			"IV/d": "Pembina Utama Madya",
			"IV/e": "Pembina Utama",
		};

		const romanMap = {
			1: "I",
			2: "II",
			3: "III",
			4: "IV",
		};

		let gol = String(golongan || "")
			.trim()
			.toUpperCase();
		let ru = String(ruang || "")
			.trim()
			.toLowerCase();

		// angka → romawi
		if (!isNaN(gol) && romanMap[parseInt(gol)]) {
			gol = romanMap[parseInt(gol)];
		}

		const key = gol + "/" + ru;

		if (map[key]) {
			return map[key] + ", " + key;
		}

		return key;
	}
}
