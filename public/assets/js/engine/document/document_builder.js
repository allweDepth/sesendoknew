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

		const container = this.container;

		const fields = this.schema.sections || this.schema;

		fields.forEach((field) => {
			switch (field.type) {
				// ===============================
				// MESSAGE
				// ===============================

				case "message":
					container.append(UIComponents.message(field));
					break;

				// ===============================
				// SECTION
				// ===============================

				case "section":
					container.append(this.renderSection(field));
					break;

				// ===============================
				// AUTO NOMOR
				// ===============================

				case "auto_nomor":
					container.append(this.renderAutoNomor(field));
					break;

				// ===============================
				// GROUP FIELDS
				// ===============================

				case "fields":
					container.append(this.renderFields(field));
					break;

				// ===============================
				// EDITABLE TABLE
				// ===============================

				case "editable_table":
					container.append(this.renderEditableTable(field));
					break;

				// ===============================
				// TABLE
				// ===============================

				case "table":
					container.append(this.renderDataTable(field));
					break;

				// ===============================
				// DROPDOWN AJAX
				// ===============================

				case "dropdown_ajax":
					container.append(UIComponents.lookupDropdown(field.label, field.name, field.source));
					break;

				// ===============================
				// TOGGLE
				// ===============================

				case "toggle":
					container.append(UIComponents.toggle(field.label, field.name));
					break;

				// ===============================
				// TEXTAREA
				// ===============================

				case "textarea":
					container.append(UIComponents.textarea(field.label, field.name));
					break;

				// ===============================
				// CALENDAR
				// ===============================

				case "calendar":
				case "date":
					container.append(UIComponents.calendar(field.label, field.name));

					break;

				// ===============================
				// FILE
				// ===============================

				case "file":
					container.append(`
						<div class="field">
							<label>${field.label}</label>
							<input type="file" name="${field.name}">
						</div>
					`);

					break;

				// ===============================
				// DEFAULT INPUT
				// ===============================

				default:
					container.append(
						UIComponents.input(field.label, field.name, field.type === "input" ? "text" : field.type || "text"),
					);
			}
		});

		this.initFomantic();

		this.bindEvents();
	}

	// ============================================================
	// GROUP FIELD RENDER
	// ============================================================

	renderFields(group) {
		let html = `<div class="${group.size || "two"} fields">`;

		(group.fields || []).forEach((field) => {
			html += this.renderSingleField(field);
		});

		html += `</div>`;

		return html;
	}

	// ============================================================
	// SINGLE FIELD
	// ============================================================

	renderSingleField(field) {
		switch (field.type) {
			case "calendar":
			case "date":
				return UIComponents.calendar(field.label, field.name);

			case "textarea":
				return UIComponents.textarea(field.label, field.name);

			case "dropdown_ajax":
				return UIComponents.lookupDropdown(field.label, field.name, field.source);

			case "toggle":
				return UIComponents.toggle(field.label, field.name);

			case "file":
				return `
					<div class="field">
						<label>${field.label}</label>
						<input type="file" name="${field.name}">
					</div>
				`;

			default:
				return UIComponents.input(field.label, field.name, field.type === "input" ? "text" : field.type || "text");
		}
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
					<tr>
						${header}
					</tr>
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
	// SECTION
	// ============================================================

	renderSection(section) {
		return `
			<h4 class="ui horizontal divider header">
				<i class="${section.icon || "file"} icon"></i>
				${section.label}
			</h4>
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

		// tambah baris

		this.container.on("click", ".btn-add-row", function () {
			const section = $(this).data("section");

			const tbody = self.container.find(`table[name="${section}"] tbody`);

			tbody.append(self.buildRow(section));
		});

		// hapus baris

		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});
	}

	// ============================================================
	// AUTO NOMOR
	// ============================================================

	renderAutoNomor(field) {
		return UIExtensions.renderAutoNumber({
			label: field.label,

			name: field.name,

			value: this.data.nomor_auto,
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
