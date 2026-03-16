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
	// ELEMENT ROUTER
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
			return `
			<h4 class="ui horizontal divider header">
				<i class="${field.icon || "file"} icon"></i>
				${field.label}
			</h4>
			`;
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
			return `
			<div class="field">
				<label>${field.label}</label>
				<input type="file" name="${field.name}">
			</div>
			`;
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

		let html = `<div class="${size} fields">`;

		(group.fields || []).forEach((field) => {
			html += this.renderElement(field);
		});

		html += `</div>`;

		return html;
	}

	// ============================================================
	// DATA TABLE
	// ============================================================

	renderDataTable(field) {
		const key = field.name;
		const columns = field.columns || [];

		let header = "";

		columns.forEach((col) => {
			header += `<th>${col}</th>`;
		});

		return `
		<table class="ui celled table" name="${key}">
			<thead>
				<tr>${header}</tr>
			</thead>
			<tbody></tbody>
		</table>
		`;
	}

	// ============================================================
	// EDITABLE TABLE
	// ============================================================

	renderEditableTable(field) {
		const key = field.name;
		const title = field.title || "";

		let header = "";

		if (title) {
			header = `
			<h4 class="ui horizontal divider header">
				<i class="file alternate outline icon"></i>
				${title}
			</h4>`;
		}

		return `
		${header}

		<table class="ui celled structured table" name="${key}">

			<thead>
				<tr>

					<th>URAIAN</th>

					<th class="collapsing">

						<button
							type="button"
							class="ui mini green icon button btn-add-row"
							data-section="${key}">

							<i class="plus icon"></i>

						</button>

					</th>

				</tr>
			</thead>

			<tbody></tbody>

		</table>
		`;
	}

	// ============================================================
	// ROW BUILDER
	// ============================================================

	buildRow(section, text = "") {
		return `
		<tr>

			<td>

				<textarea
					class="doc-text"
					name="${section}[]"
					rows="2">${text}</textarea>

			</td>

			<td class="collapsing right aligned">

				<button
					type="button"
					class="ui red icon button btn-del-row">

					<i class="trash icon"></i>

				</button>

			</td>

		</tr>
		`;
	}

	// ============================================================
	// EVENTS
	// ============================================================

	bindEvents() {
		const self = this;

		this.container.off("click", ".btn-add-row");
		this.container.off("click", ".btn-del-row");

		// tambah row

		this.container.on("click", ".btn-add-row", function () {
			const section = $(this).data("section");

			const tbody = self.container.find(`table[name="${section}"] tbody`);

			tbody.append(self.buildRow(section));
		});

		// hapus row

		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});
	}

	// ============================================================
	// INIT FOMANTIC
	// ============================================================

	initFomantic() {
		this.container.find(".ui.dropdown").dropdown();

		this.container.find(".ui.calendar").calendar({
			type: "date",
		});

		this.container.find(".ui.checkbox").checkbox();
	}
}
