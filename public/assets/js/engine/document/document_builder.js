class DocumentBuilder {
	constructor(container, type) {
		this.container = container;
		this.type = type;

		this.schema = null;
		this.data = {};
	}

	// ============================================================
	// RENDER DOCUMENT
	// ============================================================

	render() {
		if (!this.schema) return;

		this.container.empty();

		const fields = this.schema.sections || this.schema;

		fields.forEach((field) => {
			const html = this.renderElement(field);

			if (html) {
				this.container.append(html);
			}
		});

		this.initFomantic();
		this.bindEvents();
	}

	// ============================================================
	// ROUTER
	// ============================================================

	renderElement(field) {
		const type = field.type || "input";

		const renderer = this.renderers[type];

		if (renderer) {
			return renderer.call(this, field);
		}

		return this.renderInput(field);
	}

	// ============================================================
	// RENDERERS MAP
	// ============================================================

	renderers = {
		message(field) {
			return UIComponents.message(field);
		},

		section(field) {
			return [
				'<h4 class="ui horizontal divider header">',
				`<i class="${field.icon || "file"} icon"></i>`,
				field.label,
				"</h4>",
			].join("");
		},

		auto_nomor(field) {
			return UIExtensions.renderAutoNumber({
				label: field.label,
				name: field.name,
				value: this.data.nomor_auto,
			});
		},

		fields(field) {
			return this.renderFields(field);
		},

		editable_table(field) {
			return this.renderEditableTable(field);
		},

		table(field) {
			return this.renderDataTable(field);
		},

		dropdown_ajax(field) {
			return UIComponents.lookupDropdown(field.label, field.name, field.source);
		},

		toggle(field) {
			return UIComponents.toggle(field.label, field.name);
		},

		textarea(field) {
			return UIComponents.textarea(field.label, field.name);
		},

		calendar(field) {
			return UIComponents.calendar(field.label, field.name);
		},

		date(field) {
			return UIComponents.calendar(field.label, field.name);
		},

		file(field) {
			return [
				'<div class="field">',
				`<label>${field.label}</label>`,
				`<input type="file" name="${field.name}">`,
				"</div>",
			].join("");
		},
	};

	// ============================================================
	// DEFAULT INPUT
	// ============================================================

	renderInput(field) {
		const type = field.type === "input" ? "text" : field.type || "text";

		return UIComponents.input(field.label, field.name, type);
	}

	// ============================================================
	// GROUP FIELDS
	// ============================================================

	renderFields(group) {
		const size = group.size || "two";

		const parts = [`<div class="${size} fields">`];

		(group.fields || []).forEach((field) => {
			parts.push(this.renderElement(field));
		});

		parts.push(`</div>`);

		return parts.join("");
	}

	// ============================================================
	// DATA TABLE
	// ============================================================

	renderDataTable(field) {
		const key = field.name;
		const columns = field.columns || [];

		let header = [];

		columns.forEach((col) => {
			header.push(`<th>${col}</th>`);
		});

		return [
			`<table class="ui celled table" name="${key}">`,
			"<thead>",
			"<tr>",
			header.join(""),
			"</tr>",
			"</thead>",
			"<tbody></tbody>",
			"</table>",
		].join("");
	}

	// ============================================================
	// EDITABLE TABLE
	// ============================================================

	renderEditableTable(field) {
		const key = field.name;
		const title = field.title || "";

		let header = "";

		if (title) {
			header = [
				'<h4 class="ui horizontal divider header">',
				'<i class="file alternate outline icon"></i>',
				title,
				"</h4>",
			].join("");
		}

		return [
			header,
			`<table class="ui celled structured table" name="${key}">`,
			"<thead>",
			"<tr>",
			"<th>URAIAN</th>",
			'<th class="collapsing">',
			`<button type="button" class="ui mini green icon button btn-add-row" data-section="${key}">`,
			'<i class="plus icon"></i>',
			"</button>",
			"</th>",
			"</tr>",
			"</thead>",
			"<tbody></tbody>",
			"</table>",
		].join("");
	}

	// ============================================================
	// BUILD ROW
	// ============================================================

	buildRow(section, text = "", type = "paragraph") {
		return [
			"<tr>",
			"<td>",
			`<textarea class="doc-text" name="${section}[]" rows="2">${text}</textarea>`,
			"</td>",
			'<td class="collapsing right aligned">',
			'<div class="ui mini icon buttons">',

			`<div class="ui floating dropdown icon button upward doc-type" data-value="${type}">`,
			'<i class="wrench icon"></i>',
			'<div class="menu">',
			'<div class="item" data-value="paragraph">Paragraf</div>',
			'<div class="item" data-value="list">List</div>',
			'<div class="item" data-value="numbered">Numbered</div>',
			"</div>",
			"</div>",

			'<button type="button" class="ui red icon button btn-del-row">',
			'<i class="trash icon"></i>',
			"</button>",

			"</div>",
			"</td>",
			"</tr>",
		].join("");
	}

	// ============================================================
	// EVENTS
	// ============================================================

	bindEvents() {
		const self = this;

		this.container.off("click", ".btn-add-row");
		this.container.off("click", ".btn-del-row");

		// add row

		this.container.on("click", ".btn-add-row", function () {
			const section = $(this).data("section");

			const tbody = self.container.find(`table[name="${section}"] tbody`);

			const index = tbody.children().length + 1;

			const text = self.autoNumber(section, index);

			tbody.append(self.buildRow(section, text));

			self.initFomantic();
		});

		// delete row

		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});
	}

	// ============================================================
	// AUTO NUMBERING
	// ============================================================

	autoNumber(section, index) {
		if (section === "menimbang") {
			return String.fromCharCode(96 + index) + ". ";
		}

		if (section === "mengingat") {
			return index + ". ";
		}

		return "";
	}

	// ============================================================
	// INIT FOMANTIC
	// ============================================================

	initFomantic() {
		this.container.find(".ui.dropdown").dropdown();

		this.container.find(".doc-type").dropdown();

		this.container.find(".ui.calendar").calendar({
			type: "date",
		});

		this.container.find(".ui.checkbox").checkbox();
	}
	static initLookupDropdown() {
		$(".lookup-dropdown").each(function () {
			const el = $(this);
			const source = el.data("source");

			// ==============================
			// LOCAL DATA (ASN dari schema)
			// ==============================

			if (window.lookupData && window.lookupData[source]) {
				const items = window.lookupData[source].map((row) => ({
					name: row.uraian,
					value: row.id,
				}));

				el.dropdown({
					values: items,
				});

				return;
			}

			// ==============================
			// FALLBACK API
			// ==============================

			el.dropdown({
				apiSettings: {
					url: "/dynamic",
				},
			});
		});
	}
}
