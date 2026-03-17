class DocumentBuilder {
	constructor(container, type) {
		this.container = container;
		this.type = type;

		this.schema = null;
		this.data = {};
	}

	// ======================================================
	// RENDER DOCUMENT
	// ======================================================

	render() {
		if (!this.schema) return;

		this.container.empty();

		var fields = this.schema.sections || this.schema;
		var html = [];

		fields.forEach((field) => {
			var part = this.renderElement(field);

			if (part) {
				html.push(part);
			}
		});

		this.container.append(html.join(""));

		this.initFomantic();
		DocumentBuilder.initLookupDropdown(this.data);

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
				return [
					'<h4 class="ui horizontal divider header">',
					'<i class="' + (field.icon || "file") + ' icon"></i>',
					field.label,
					"</h4>",
				].join("");

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
				// FIX: hanya dropdown tertentu yang punya target table

				if (field.target) {
					return UIComponents.lookupDropdown(field.label, field.name, field.source, field.target);
				}

				return UIComponents.lookupDropdown(field.label, field.name, field.source);

			case "toggle":
				return UIComponents.toggle(field.label, field.name);

			case "textarea":
				return UIComponents.textarea(field.label, field.name);

			case "calendar":
			case "date":
				return UIComponents.calendar(field.label, field.name);

			case "file":
				return [
					'<div class="field">',
					"<label>" + field.label + "</label>",
					'<input type="file" name="' + field.name + '">',
					"</div>",
				].join("");

			default:
				return UIComponents.input(field.label, field.name, field.type === "input" ? "text" : field.type);
		}
	}

	// ======================================================
	// GROUP FIELDS
	// ======================================================

	renderFields(group) {
		var size = group.size || "two";
		var parts = [];

		parts.push('<div class="' + size + ' fields">');
		(group.fields || []).forEach((field) => {
			parts.push(this.renderElement(field));
		});

		parts.push("</div>");

		return parts.join("");
	}

	// ======================================================
	// DATA TABLE
	// ======================================================

	renderDataTable(field) {
		var key = field.name;
		var columns = field.columns || [];

		var th = [];

		columns.forEach((c) => {
			th.push("<th>" + c + "</th>");
		});

		return `
      <table 
        class="ui celled table"
        name="${key}"
        data-dropdown-target="${key}"
        data-columns="${columns.length}">
        <thead>
        <tr>
        ${th.join("")}
        </tr>
        </thead>

        <tbody></tbody>

      </table>
      `;
	}

	// ======================================================
	// EDITABLE TABLE
	// ======================================================

	renderEditableTable(field) {
		var key = field.name;

		var columns = field.columns || ["URAIAN", "JENIS"];

		var th = [];

		columns.forEach((c) => {
			if (c === "JENIS") {
				th.push('<th class="collapsing">' + c + "</th>");
			} else {
				th.push("<th>" + c + "</th>");
			}
		});

		th.push(
			'<th class="collapsing">' +
				'<button type="button" class="ui mini green icon button btn-add-row" data-section="' +
				key +
				'">' +
				'<i class="plus icon"></i>' +
				"</button>" +
				"</th>",
		);

		return `
        <table class="ui celled structured table"
          name="${key}"
          data-dropdown-target="${key}"
          data-columns="${columns.length}"> 
          <thead>
          <tr>
          ${th.join("")}
          </tr>
          </thead>
          <tbody></tbody>
        </table>
        `;
	}

	// ======================================================
	// ROW BUILDER
	// ======================================================

	buildRow(section, columns) {
		var td = [];

		td.push('<textarea class="doc-text" name="' + section + '[]" rows="2"></textarea>');

		if (columns >= 2) {
			td.push(
				'<div class="ui mini floating dropdown icon button doc-type">' +
					'<i class="bars icon"></i>' +
					'<div class="menu">' +
					'<div class="item" data-value="paragraph">Paragraf</div>' +
					'<div class="item" data-value="list">List</div>' +
					'<div class="item" data-value="numbered">Numbered</div>' +
					"</div>" +
					"</div>",
			);
		}

		var cells = [];

		td.forEach((content) => {
			cells.push("<td>" + content + "</td>");
		});

		cells.push(
			'<td class="collapsing">' +
				'<button type="button" class="ui mini red icon button btn-del-row">' +
				'<i class="trash icon"></i>' +
				"</button>" +
				"</td>",
		);

		return ["<tr>", cells.join(""), "</tr>"].join("");
	}

	// ======================================================
	// EVENTS
	// ======================================================

	bindEvents() {
		var self = this;

		this.container.off("click", ".btn-add-row");

		this.container.on("click", ".btn-add-row", function () {
			var section = $(this).data("section");

			var table = self.container.find('table[name="' + section + '"]');

			var tbody = table.find("tbody");

			var cols = parseInt(table.data("columns") || 2);

			tbody.append(self.buildRow(section, cols));

			self.initFomantic();
		});

		this.container.off("click", ".btn-del-row");

		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});

		// =====================================
		// INIT DROPDOWN ENGINE
		// =====================================

		if (!this.dropdownEngine) {
			this.dropdownEngine = new DropdownEngine(this.container);

			this.dropdownEngine.init();
		}
		// =====================================
		// DROPDOWN SELECT (GLOBAL FIX)
		// =====================================
		this.container.off("change", ".lookup-dropdown");

		this.container.on("change", ".lookup-dropdown", function () {
			const dropdown = $(this);

			const value = dropdown.find("input[type=hidden]").val();

			if (!value) return;

			const dataset = dropdown.data("dataset") || [];

			const selected = dataset.find((row) => row.id == value) || {};

			const name = dropdown.find("input[type=hidden]").attr("name");

			const form = dropdown.closest("form");

			// =====================================
			// AUTO HEADER (Pemberi Tugas)
			// =====================================
			if (name === "pemberi_tgs") {
				form.find('[name="jbt_pemberi_tgs"]').val(selected.jabatan || "");
				form.find('[name="pangkat_pemberi_tgs"]').val(selected.pangkat || "");
			}

			// =====================================
			// AUTO INSERT TABLE (JIKA ADA TARGET)
			// =====================================
			const target = dropdown.data("target-table");

			if (target && selected.id) {
				if (window.documentBuilder) {
					window.documentBuilder.insertToTable(target, selected);
				}
			}
		});
	}
	// ======================================================
	// COLLECT STRUCTURE JSON
	// ======================================================

	collectStructure() {
		// ambil seluruh editable_table

		var result = {}; // object final

		this.container.find("table").each(function () {
			// loop semua table

			var table = $(this); // table

			var section = table.attr("name"); // nama section

			if (!section) return; // skip jika tidak ada

			var rows = []; // array row

			table.find("tbody tr").each(function () {
				// loop row

				var row = $(this);

				// FIX: support dropdown row
				var text = row.find(".doc-text").val() || row.find("input[type=hidden]").val() || row.text().trim() || "";

				var type = row.find(".doc-type").dropdown("get value") || row.data("source") || "paragraph";

				rows.push({
					// push ke array
					type: type,
					text: text,
				});
			});

			result[section] = rows; // assign ke section
		});

		return result; // return json
	}
	// ======================================================
	// INIT FOMANTIC
	// ======================================================

	initFomantic() {
		this.container.find(".ui.dropdown").dropdown();

		this.container.find(".doc-type").dropdown();

		this.container.find(".ui.calendar").calendar({
			type: "date",
		});

		this.container.find(".ui.checkbox").checkbox();
	}

	// ======================================================
	// LOOKUP DROPDOWN
	// ======================================================

	static initLookupDropdown(data) {
		if (!data) return;

		var alias = {
			pemberi_tgs: "asn",
			asn: "asn",
		};

		$(".lookup-dropdown").each(function () {
			var el = $(this);

			var source = el.data("source");
			if (!source) return;

			var real = source;

			if (!data[source] && alias[source]) {
				real = alias[source];
			}

			var dataset = data[real];
			if (!dataset) return;

			// 🔥 SIMPAN DATASET (INI KUNCI)
			el.data("dataset", dataset);

			var values = [];

			dataset.forEach((row) => {
				values.push({
					name: row.uraian,
					value: row.id,
				});
			});

			el.dropdown({
				values: values,
			});
		});
	}
	// ==============================
	// INSERT DROPDOWN → TABLE (GENERIC)
	// ==============================

	insertToTable(target, data) {
		// ==============================
		// AMBIL TABLE TARGET
		// ==============================

		const table = this.container.find(`table[name="${target}"]`);

		if (!table.length) return;

		const tbody = table.find("tbody");

		// ==============================
		// AMBIL KOLOM DARI THEAD
		// ==============================

		const columns = [];

		table.find("thead th").each(function () {
			columns.push($(this).text().trim().toLowerCase());
		});

		// ==============================
		// BUILD ROW
		// ==============================

		let row = "<tr>";

		columns.forEach((col) => {
			// mapping otomatis berdasarkan nama kolom
			const value = data[col] ?? "";

			row += `
			<td>
				<input 
					type="text" 
					name="${target}_${col}[]" 
					value="${value}" 
					data-field="${col}"
				/>
			</td>
		`;
		});

		row += `
		<td>
			<button type="button" class="ui red mini button btn-remove-row">
				<i class="trash icon"></i>
			</button>
		</td>
	</tr>`;

		tbody.append(row);
	}
}
