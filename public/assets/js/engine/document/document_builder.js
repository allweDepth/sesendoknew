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

		return `
		<table class="ui celled table" 
			name="${key}" 
			data-columns='${JSON.stringify(columns)}'> <!-- 🔥 simpan kolom asli dari schema -->
			<thead>
			<tr>
				${columns.map((c) => `<th>${c}</th>`).join("")}
				<th class="collapsing">Aksi</th> <!-- 🔥 kolom aksi -->
			</tr>
			</thead>
			<tbody></tbody>
		</table>`;
	}

	renderEditableTable(field) {
		let key = field.name;
		let columns = field.columns || ["URAIAN", "JENIS"];

		return `
	<table class="ui celled structured table" 
		name="${key}" 
		data-columns='${JSON.stringify(columns)}'>
		<thead>
			<tr>
				${columns.map((c) => `<th>${c}</th>`).join("")}
				<th class="collapsing">Aksi</th> <!-- 🔥 header aksi -->
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
		this.container.off("dropdown:select");

		this.container.on("dropdown:select", (e, payload) => {
			const { target, data, name } = payload;

			// AUTO HEADER
			if (name === "pemberi_tgs") {
				this.container.find('[name="jbt_pemberi_tgs"]').val(data.jabatan || "");
				this.container.find('[name="pangkat_pemberi_tgs"]').val(data.pangkat || "");
			}

			// INSERT TABLE
			if (target) {
				this.insertToTable(target, data);
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
		let cells = [];

		cells.push(`<td contenteditable="true" data-key="uraian"></td>`); // editable cell

		if (columns >= 2) {
			cells.push(`
			<td>
				<div class="ui mini floating dropdown icon button doc-type">
					<i class="bars icon"></i>
					<div class="menu">
						<div class="item" data-value="paragraph">Paragraf</div>
						<div class="item" data-value="list">List</div>
						<div class="item" data-value="numbered">Numbered</div>
					</div>
				</div>
			</td>`);
		}

		cells.push(`
		<td class="collapsing">
			<button type="button" class="ui mini red icon button btn-del-row">
				<i class="trash icon"></i>
			</button>
		</td>`);

		return `<tr>${cells.join("")}</tr>`;
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

		// TABLE → JSON
		this.container.find("table[name]").each(function () {
			let tableName = $(this).attr("name");
			let rows = [];

			$(this)
				.find("tbody tr")
				.each(function () {
					let row = {};

					$(this)
						.find("td[data-key]")
						.each(function () {
							let key = $(this).data("key");
							let value = $(this).text().trim();

							row[key] = value;
						});

					if (Object.keys(row).length) {
						rows.push(row);
					}
				});

			result[tableName] = rows;
		});

		// FIELD BIASA
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
