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
		if (!this.schema) return; // stop kalau tidak ada schema

		this.container.empty(); // reset container

		let fields = this.schema.sections || this.schema; // ambil sections
		let html = [];

		fields.forEach((field) => {
			let part = this.renderElement(field); // render per field
			if (part) html.push(part);
		});

		this.container.append(html.join("")); // inject ke DOM

		this.initFomantic(); // init UI

		// init dropdown engine (WAJIB)
		if (window.dropdownEngine) {
			window.dropdownEngine.init();
		}

		this.bindEvents(); // bind event
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
				return UIComponents.calendar(field.label, field.name);

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

		return `
	<table class="ui celled structured table" 
		name="${key}" 
		data-type="editable_table"
		data-columns='${JSON.stringify(columns)}'>
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
	</table>`;
	}

	// ======================================================
	// EVENTS
	// ======================================================

	bindEvents() {
		let self = this;

		// ADD ROW
		this.container.off("click", ".btn-add-row");
		this.container.on("click", ".btn-add-row", function () {
			let section = $(this).data("section");
			let table = self.container.find(`table[name="${section}"]`);
			let cols = table.data("columns")?.length || 2; // 🔥 pakai schema, bukan DOM

			table.find("tbody").append(self.buildRow(section, cols));
			self.initFomantic();
		});

		// DELETE ROW
		this.container.off("click", ".btn-del-row");
		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});

		// DROPDOWN SELECT
		$(document).off("dropdown:select.documentBuilder"); // 🔥 namespace biar aman

		$(document).on("dropdown:select.documentBuilder", (e, payload) => {
			const { target, data, name } = payload;

			// AUTO HEADER
			if (name === "penandatangan") {
				this.container.find('[name="jbt_pemberi_tgs"]').val(data.jabatan || "");
				this.container.find('[name="pangkat_pemberi_tgs"]').val(data.pangkat || "");
			}

			// INSERT TABLE
			if (target) {
				this.insertToTable(target, data);
			}
		});
		// 🔥 HANDLE FORMAT DROPDOWN
		// 🔥 INLINE TOOLBAR HANDLER
		this.container.off("click", ".doc-toolbar button");

		this.container.on("click", ".doc-toolbar button", function () {
			let btn = $(this);
			let tr = btn.closest("tr");
			let toolbar = btn.closest(".doc-toolbar");

			// 🔥 tentukan group (type / align / format)
			let groupKey = btn.data("type") ? "type" : btn.data("align") ? "align" : "format";

			// reset active dalam group
			toolbar.find(`button[data-${groupKey}]`).removeClass("active");
			btn.addClass("active");

			// TYPE
			let type = btn.data("type");
			if (type) {
				tr.attr("data-type", type);
			}

			// ALIGN
			let align = btn.data("align");
			if (align) {
				tr.attr("data-align", align);
				tr.find(".doc-editor").css("text-align", align);
			}

			// FORMAT LABEL
			let format = btn.data("format");
			if (format === "label") {
				let editor = tr.find(".doc-editor");
				let val = editor.text().trim();

				if (val && !val.includes(":")) {
					editor.text(val + " : ");
				}
			}
		});
	}

	// ======================================================
	// INSERT TABLE (FIX FINAL - SCHEMA DRIVEN)
	// ======================================================

	insertToTable(target, data) {
		let table = this.container.find(`table[name="${target}"]`);
		if (!table.length) return;

		let tbody = table.find("tbody");

		let id = data.id || "";
		if (id && tbody.find(`tr[data-id="${id}"]`).length > 0) return; // prevent duplicate

		let columns = table.data("columns") || []; // 🔥 ambil dari schema, bukan header

		let row = `<tr data-id="${id}">`;

		columns.forEach((col) => {
			let key = col.toLowerCase().replace(/\s+/g, "_"); // 🔥 normalisasi key dari schema
			let value = data[key] || ""; // 🔥 langsung mapping tanpa normalize

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

	buildRow(section, columns) {
		let table = this.container.find(`table[name="${section}"]`);
		let cols = table.data("columns") || [];

		let row = `<tr data-type="paragraph" data-align="left">`; // default
		let cells = [];

		cols.forEach((col, i) => {
			let key = col.toLowerCase().replace(/\s+/g, "_");

			if (i === 0) {
				// 🔥 CELL UTAMA + INLINE TOOLBAR
				cells.push(`
			<td data-key="${key}" class="doc-cell">

	<div class="doc-editor" contenteditable="true"></div>

	<!-- 🔥 TOOLBAR FLOAT -->
	<div class="doc-toolbar">
		<button type="button" class="ui icon button" data-type="paragraph">
			<i class="align left icon"></i>
		</button>
		<button type="button" class="ui icon button" data-type="list">
			<i class="list ul icon"></i>
		</button>
		<button type="button" class="ui icon button" data-type="numbered">
			<i class="list ol icon"></i>
		</button>

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

		<button type="button" class="ui icon button" data-format="label">
			<i class="tag icon"></i>
		</button>
	</div>

</td>`);
			} else {
				// kolom lain tetap editable biasa
				cells.push(`<td contenteditable="true" data-key="${key}"></td>`);
			}
		});

		// tombol delete
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
		this.container.find(".ui.calendar").calendar({ type: "date" });
		this.container.find(".ui.checkbox").checkbox();
	}

	// ======================================================
	// COLLECT DATA
	// ======================================================

	collectStructure() {
		let result = {};

		// =========================
		// TABLE → JSON (DINAMIS)
		// =========================
		this.container.find("table[name]").each(function () {
			let tableName = $(this).attr("name"); // nama tabel dari schema
			let type = $(this).data("type"); // 🔥 FIX: ambil tipe tabel
			let rows = [];

			$(this)
				.find("tbody tr")
				.each(function () {
					let row = {};
					let tr = $(this);

					// 🔥 ambil metadata row (dari dropdown)
					let rowType = tr.attr("data-type");
					let rowAlign = tr.attr("data-align");

					$(this)
						.find("td[data-key]")
						.each(function () {
							let key = $(this).data("key");
							let value;

							// 🔥 FIX: ambil dari editor kalau ada
							let editor = $(this).find(".doc-editor");

							if (editor.length) {
								value = editor.text().trim();
							} else {
								value = $(this).text().trim();
							}

							if (type === "editable_table") {
								if (key === "uraian") {
									row.text = value; // isi utama
								} else {
									row[key] = value; // fallback (future-proof)
								}
							} else {
								row[key] = value;
							}
						});

					// 🔥 FINALIZE editable_table
					if (type === "editable_table") {
						row.type = rowType || row.type || "paragraph"; // prioritas dropdown
						row.text = row.text || "";

						if (rowAlign) {
							row.align = rowAlign; // simpan align
						}
					}

					if (Object.keys(row).length) {
						rows.push(row);
					}
				});

			// 🔥 NORMALISASI OUTPUT
			if (type === "editable_table") {
				result[tableName] = rows.map((r) => ({
					type: r.type || "paragraph",
					text: r.text || "",
					...(r.align ? { align: r.align } : {}), // 🔥 FIX: jangan hilangkan align
				}));
			} else {
				result[tableName] = rows;
			}
		});

		// =========================
		// FIELD BIASA (TIDAK DIUBAH)
		// =========================
		this.container
			.find("input, textarea, select")
			.not("table input, table textarea")
			.each(function () {
				let name = $(this).attr("name");
				if (!name) return;

				result[name] = $(this).val();
			});

		return result;
	}
}
