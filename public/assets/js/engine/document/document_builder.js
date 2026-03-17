class DocumentBuilder {
	constructor(container, type) {
		this.container = container;
		this.type = type;

		this.schema = null;
		this.data = {};
	}

	// ======================================================
	// RENDER
	// ======================================================

	render() {
		if (!this.schema) return;

		this.container.empty();

		let fields = this.schema.sections || this.schema;
		let html = [];

		fields.forEach((field) => {
			let part = this.renderElement(field);
			if (part) html.push(part);
		});

		this.container.append(html.join(""));

		this.initFomantic();
		this.bindEvents();
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
		<table class="ui celled table" name="${key}" data-columns="${columns.length}">
			<thead>
				<tr>${columns.map((c) => `<th>${c}</th>`).join("")}</tr>
			</thead>
			<tbody></tbody>
		</table>`;
	}

	renderEditableTable(field) {
		let key = field.name;
		let columns = field.columns || ["URAIAN", "JENIS"];

		let th = columns.map((c) => (c === "JENIS" ? `<th class="collapsing">${c}</th>` : `<th>${c}</th>`));

		th.push(`
			<th class="collapsing">
				<button class="ui mini green icon button btn-add-row" data-section="${key}">
					<i class="plus icon"></i>
				</button>
			</th>`);

		return `
		<table class="ui celled structured table" name="${key}" data-columns="${columns.length}">
			<thead><tr>${th.join("")}</tr></thead>
			<tbody></tbody>
		</table>`;
	}

	// ======================================================
	// ROW BUILDER (LEGACY TETAP ADA)
	// ======================================================

	buildRow(section, columns) {
		let cells = [];

		cells.push(`<td><textarea class="doc-text" name="${section}[]" rows="2"></textarea></td>`);

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
				<button class="ui mini red icon button btn-del-row">
					<i class="trash icon"></i>
				</button>
			</td>`);

		return `<tr>${cells.join("")}</tr>`;
	}

	// ======================================================
	// EVENTS (FINAL - TERIMA EVENT SAJA)
	// ======================================================

	bindEvents() {
		let self = this;

		// ADD ROW
		this.container.off("click", ".btn-add-row");
		this.container.on("click", ".btn-add-row", function () {
			let section = $(this).data("section");
			let table = self.container.find(`table[name="${section}"]`);
			let tbody = table.find("tbody");
			let cols = parseInt(table.data("columns") || 2);

			tbody.append(self.buildRow(section, cols));
			self.initFomantic();
		});

		// DELETE ROW
		this.container.off("click", ".btn-del-row");
		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});

		// 🔥 TERIMA EVENT DARI DropdownEngine
		this.container.off("dropdown:select");

		this.container.on("dropdown:select", (e, payload) => {
			const { target, data, name } = payload;

			// HEADER AUTO
			if (name === "pemberi_tgs") {
				this.container.find('[name="jbt_pemberi_tgs"]').val(data.jabatan || "");
				this.container.find('[name="pangkat_pemberi_tgs"]').val(data.pangkat || "");
			}

			// TABLE AUTO
			if (target) {
				this.insertToTable(target, data);
			}
		});
	}

	// ======================================================
	// INSERT TABLE (TETAP SUPPORT SEMUA MODE)
	// ======================================================

	insertToTable(target, data) {
		let table = this.container.find(`table[name="${target}"]`);
		if (!table.length) return;

		let tbody = table.find("tbody");

		if (tbody.find(`tr[data-id="${data.id}"]`).length) return;

		let columns = [];
		table.find("thead th").each(function () {
			let col = $(this).text().trim().toLowerCase();
			if (col) columns.push(col);
		});

		const map = {
			nama: data.nama || data.uraian || "",
			pangkat: data.pangkat || "",
			nip: data.nip || "",
			jabatan: data.jabatan || "",
			jabatan_sk: data.jabatan_sk || "",
		};

		let row = `<tr data-id="${data.id}">`;

		columns.forEach((col, i) => {
			let value = map[col] ?? "";

			if (columns.length > 2) {
				row += `<td><input type="text" name="${target}_${col}[]" value="${value}"></td>`;
			} else {
				row +=
					i === 0
						? `<td><textarea class="doc-text">${value}</textarea></td>`
						: `<td><div class="ui mini dropdown doc-type"></div></td>`;
			}
		});

		row += `
			<td class="collapsing">
				<button class="ui mini red icon button btn-del-row">
					<i class="trash icon"></i>
				</button>
			</td>
		</tr>`;

		tbody.append(row);
		this.initFomantic();
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
}
