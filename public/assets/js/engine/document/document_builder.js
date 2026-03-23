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

				// 🔥 INIT UI DI ROW BARU
				row.find(".ui.dropdown").dropdown(); // .
				row.find(".ui.checkbox").checkbox(); // .
			});
		});
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

			// =====================================
			// FIX: init hanya elemen baru, bukan semua
			// =====================================
			let newRow = table.find("tbody tr:last");

			// init checkbox saja jika ada
			newRow.find(".ui.checkbox").checkbox();

			// JANGAN re-init dropdown global
			// self.initFomantic(); ❌ HAPUS
		});

		// DELETE ROW
		this.container.off("click", ".btn-del-row");
		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});

		// DROPDOWN SELECT
		// =====================================
		// FIX: scope ke container, bukan document
		// =====================================
		// FIX: scope ke container instance
		// =====================================
		// FIX: HARUS listen dari document
		// =====================================
		$(document).off("dropdown:select.documentBuilder");

		$(document).on("dropdown:select.documentBuilder", (e, payload) => {
			const { target, data, name } = payload;

			// AUTO HEADER
			if (name === "penandatangan") {
				this.container.find('[name="jbt_pemberi_tgs"]').val(data.jabatan || "");
				this.container.find('[name="pangkat_pemberi_tgs"]').val(data.pangkat || "");
			}

			// INSERT TABLE
			// =====================================
			// FIX: hanya gunakan payload + DOM
			// =====================================
			let finalTarget = target; // // FIX

			// cari target dari atribut form
			if (!finalTarget) {
				const input = this.container.find(`[name="${name}"]`);

				finalTarget = input.data("target-table"); // // FIX
			}

			// fallback manual khusus ASN
			if (!finalTarget && name === "asn") {
				finalTarget = "nama_ditugaskan"; // // FIX HARD GUARANTEE
			}

			if (finalTarget) {
				this.insertToTable(finalTarget, data);
			}
		});
		// 🔥 HANDLE FORMAT DROPDOWN
		// 🔥 INLINE TOOLBAR HANDLER
		this.container.off("click", ".doc-toolbar button");

		this.container.on("click", ".doc-toolbar button", function () {
			let btn = $(this);
			let tr = btn.closest("tr");
			let toolbar = btn.closest(".doc-toolbar");
			let editor = tr.find(".doc-editor");

			// =====================================
			// 🔥 STYLE (BOLD / ITALIC / UNDERLINE)
			// =====================================
			let style = btn.data("style");

			if (style) {
				editor.focus();

				switch (style) {
					case "bold":
						document.execCommand("bold");
						break;
					case "italic":
						document.execCommand("italic");
						break;
					case "underline":
						document.execCommand("underline");
						break;
				}

				btn.toggleClass("active"); // toggle, bukan reset group
				return; // 🔥 STOP supaya tidak kena logic bawah
			}

			// =====================================
			// 🔥 GROUP LOGIC (TYPE / ALIGN / FORMAT)
			// =====================================
			let groupKey = btn.data("type") ? "type" : btn.data("align") ? "align" : btn.data("format") ? "format" : null;

			if (groupKey) {
				toolbar.find(`button[data-${groupKey}]`).removeClass("active");
				btn.addClass("active");
			}

			// =====================================
			// TYPE
			// =====================================
			let type = btn.data("type");
			if (type) {
				tr.attr("data-type", type);
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
			// FORMAT LABEL
			// =====================================
			let format = btn.data("format");
			if (format === "label") {
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
		let id = data.id; // FIX: HARUS FIX ID DARI DB
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
				if (key === "nama") value = data.uraian;
				else if (key === "pangkat") value = (data.golongan || "") + (data.ruang ? ", " + data.ruang : "");
				else value = data[key] ?? "";
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

          <!-- TYPE -->
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

          <!-- ALIGN -->
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

          <!-- TEXT FORMAT -->
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

          <!-- SPECIAL -->
          <div class="btn-group">
            <button type="button" class="ui icon button" data-format="label">
              <i class="tag icon"></i>
            </button>
          </div>

        </div>
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
		// this.container.find(".ui.calendar").calendar({ type: "date" });
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
					// =====================================
					// FIX: simpan identity row
					// =====================================
					let rowId = tr.attr("data-id"); // // FIX
					if (rowId) {
						row._id = rowId; // // FIX
					}
					if (Object.keys(row).length) {
						rows.push(row);
					}
				});

			// 🔥 NORMALISASI OUTPUT
			if (type === "editable_table") {
				result[tableName] = rows.map((r) => ({
					// =====================================
					// FIX: preserve identity
					// =====================================
					...(r._id ? { _id: r._id } : {}), // // FIX

					type: r.type || "paragraph",
					text: r.text || "",
					...(r.align ? { align: r.align } : {}),
				}));
			} else {
				result[tableName] = rows;
			}
		});
		// =========================
		// FIELD BIASA (SCHEMA DRIVEN)
		// =========================
		this.container
			.find("input, textarea, select")
			.not("table input, table textarea")
			.each((i, el) => {
				let name = $(el).attr("name");
				if (!name) return;

				// 🔥 FIX: JANGAN IZINKAN FIELD SYSTEM OVERRIDE CORE REQUEST
				if (name === "action") return; // // cegah override action

				// =====================================
				// FIX: ambil schema fields
				// =====================================
				let schemaFields = [];

				// =====================================
				// FIX: flatten semua field schema
				// =====================================
				const extractFields = (items) => {
					items.forEach((f) => {
						if (f.name) {
							schemaFields.push(f); // // FIX
						}
						if (Array.isArray(f.fields)) {
							extractFields(f.fields); // // FIX recursive
						}
					});
				};

				if (this.schema) {
					let root = this.schema.sections || this.schema;
					if (Array.isArray(root)) {
						extractFields(root); // // FIX
					}
				}

				// =====================================
				// FIX: hanya ambil field yg ada di schema
				// =====================================
				const fieldDef = schemaFields.find((f) => f.name === name);

				// =====================================
				// FIX: allow hidden/system field
				// =====================================
				if (!fieldDef) {
					if ($(el).attr("type") === "hidden") {
						result[name] = $(el).val(); // // FIX
					}
					return;
				}

				// =====================================
				// FIX: skip dropdown_ajax yang punya target
				// =====================================
				if (fieldDef.type === "dropdown_ajax" && fieldDef.target) {
					return; // // FIX
				}

				let value = $(el).val();

				// =====================================
				// FIX: DETEKSI BERDASARKAN SCHEMA
				// =====================================
				if (fieldDef.type === "calendar" || fieldDef.type === "date") {
					value = UIComponents.toISODateTime(value); // // FIX
				}

				result[name] = value;
			});

		// =====================================
		// FIX: pisahkan struktur dari root
		// =====================================
		// =====================================
		// FIX: HINDARI NESTED struktur_json
		// =====================================

		// pastikan tidak ada struktur_json di dalam result
		// 🔥 AMBIL LANGSUNG ROOT TANPA WRAP LAGI
		return result;
	}
}
