// FILE: public/assets/js/engine/document_builder.js

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

		const fields = this.schema.sections || this.schema;

		this.container.append(fields.map((f) => this.renderElement(f)).join(""));

		this.initFomantic();
		DocumentBuilder.initLookupDropdown(this.data);

		this.bindEvents();
	}

	// ======================================================
	// ELEMENT ROUTER
	// ======================================================

	renderElement(field) {
		switch (field.type) {
			case "section":
				return `
					<h4 class="ui horizontal divider header">
						<i class="${field.icon || "file"} icon"></i>
						${field.label}
					</h4>
				`;

			case "fields":
				return this.renderFields(field);

			case "editable_table":
				return this.renderEditableTable(field);

			case "table":
				return this.renderDataTable(field);
			case "message":
				return UIComponents.message(field);

			case "dropdown_ajax":
				return UIComponents.lookupDropdown(field.label, field.name, field.source, field.target);

			default:
				return UIComponents.input(field.label, field.name, "text");
		}
	}

	// ======================================================
	// GROUP
	// ======================================================

	renderFields(group) {
		return `
			<div class="${group.size || "two"} fields">
				${group.fields.map((f) => this.renderElement(f)).join("")}
			</div>
		`;
	}

	// ======================================================
	// DATA TABLE (CONTENTEDITABLE)
	// ======================================================

	renderDataTable(field) {
		const key = field.name;
		const columns = field.columns || [];

		return `
		<table class="ui celled table"
			name="${key}"
			data-dropdown-target="${key}">
			<thead>
				<tr>
					${columns.map((c) => `<th>${c}</th>`).join("")}
					<th class="collapsing"></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		`;
	}

	// ======================================================
	// EDITABLE TABLE (LEGACY)
	// ======================================================

	renderEditableTable(field) {
		const key = field.name;

		const columns = field.columns || ["URAIAN", "JENIS"];

		return `
      <table class="ui celled structured table"
        name="${key}"
        data-dropdown-target="${key}">
        <thead>
          <tr>
            ${columns.map((c) => `<th>${c}</th>`).join("")}
            <th class="collapsing">
              <button type="button" class="ui mini green icon button btn-add-row" data-section="${key}">
                <i class="plus icon"></i>
              </button>
            </th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      `;
	}

	// ======================================================
	// INSERT → TABLE (CORE ENGINE)
	// ======================================================

	insertToTable(target, data) {
		const table = this.container.find(`table[name="${target}"]`);
		if (!table.length) return;

		const tbody = table.find("tbody");

		// 🔥 prevent duplicate
		if (tbody.find(`tr[data-id="${data.id}"]`).length) return;

		// 🔥 ambil kolom dari DOM (bukan schema)
		const columns = [];
		table.find("thead th").each(function () {
			const col = $(this).text().trim().toLowerCase();
			if (col) columns.push(col);
		});

		// 🔥 mapping pintar
		const map = {
			nama: data.nama || data.uraian || "",
			pangkat: data.pangkat || this.buildPangkat(data),
			nip: data.nip || "",
			jabatan: data.jabatan || "",
			jabatan_sk: data.jabatan_sk || "",
		};

		let row = `
			<tr data-id="${data.id}" data-source="${data.source}" data-section="${target}">
		`;

		columns.forEach((col) => {
			const value = map[col] ?? data[col] ?? "";

			row += `
				<td data-field="${col}" contenteditable="true">
					${value}
				</td>
			`;
		});

		row += `
			<td class="collapsing">
				<button class="ui mini red icon button btn-del-row">
					<i class="trash icon"></i>
				</button>
			</td>
		</tr>`;

		tbody.append(row);
	}

	// ======================================================
	// FORMAT ASN
	// ======================================================

	buildPangkat(data) {
		if (!data.golongan && !data.ruang) return "";

		const mapGol = {
			I: "Juru",
			II: "Pengatur",
			III: "Penata",
			IV: "Pembina",
		};

		const gol = data.golongan || "";
		const ruang = data.ruang || "";

		const nama = mapGol[gol] || "";

		return nama ? `${nama}, ${gol}/${ruang}` : `${gol}/${ruang}`;
	}

	// ======================================================
	// EVENTS
	// ======================================================

	bindEvents() {
		// delete row
		this.container.off("click", ".btn-del-row");
		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});

		// 🔥 GLOBAL EVENT DARI DropdownEngine
		this.container.off("dropdown:select");

		this.container.on("dropdown:select", (e, payload) => {
			const { target, data } = payload;
			this.insertToTable(target, data);
		});
	}

	// ======================================================
	// COLLECT JSON
	// ======================================================

	collectStructure() {
		const result = {};

		this.container.find("table").each(function () {
			const table = $(this);
			const section = table.attr("name");

			if (!section) return;

			const rows = [];

			table.find("tbody tr").each(function () {
				const obj = {};

				$(this)
					.find("td[data-field]")
					.each(function () {
						obj[$(this).data("field")] = $(this).text().trim();
					});

				rows.push(obj);
			});

			result[section] = rows;
		});

		return result;
	}

	// ======================================================
	// INIT
	// ======================================================

	initFomantic() {
		this.container.find(".ui.dropdown").dropdown();
		this.container.find(".ui.calendar").calendar({ type: "date" });
		this.container.find(".ui.checkbox").checkbox();
	}

	static initLookupDropdown(data) {
		if (!data) return;

		$(".lookup-dropdown").each(function () {
			const el = $(this);
			const source = el.data("source");

			const dataset = data[source];
			if (!dataset) return;

			el.data("dataset", dataset); // 🔥 simpan full dataset

			el.dropdown({
				values: dataset.map((row) => ({
					name: row.uraian,
					value: row.id,
				})),
			});
		});
	}
}
