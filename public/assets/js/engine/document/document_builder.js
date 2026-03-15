class DocumentBuilder {
	constructor(container, type) {
		this.container = container;
		this.type = type;

		this.schema = null;
		// ======================================================
		// SECTION CONTAINER
		// ======================================================
		this.sectionContainer = null;
		// FIX: container data schema
		this.data = {};
	}

	render() {
		if (!this.schema) return;

		// ======================================================
		// CLEAR CONTAINER
		// ======================================================
		this.container.empty();
		// ======================================================
		// CONTAINER FIELD
		// ======================================================
		let fieldContainer = this.container.find("#doc-fields");

		if (!fieldContainer.length) {
			this.container.append(`<div id="doc-fields"></div>`);
			fieldContainer = this.container.find("#doc-fields");
		}

		fieldContainer.empty();
		// ======================================================
		// BUAT CONTAINER SECTION
		// ======================================================
		this.sectionContainer = this.container.find("#doc-sections");

		if (!this.sectionContainer.length) {
			this.container.append(`<div id="doc-sections"></div>`);

			this.sectionContainer = this.container.find("#doc-sections");
		}

		this.sectionContainer.empty();
		let fields = this.schema.sections || this.schema;

		fields.forEach((field) => {
			// ==================================================
			// SECTION BUILDER
			// ==================================================
			if (field.type === "section") {
				this.renderSection(field);
				return;
			}

			// ==================================================
			// AUTO NOMOR (extension)
			// ==================================================
			if (field.type === "auto_nomor") {
				this.renderAutoNomor(field);
				return;
			}

			// ==================================================
			// MAPPING TYPE → UI REGISTRY TAG
			// ==================================================
			let tag = "field";

			if (field.type === "date") tag = "calendar";
			if (field.type === "dropdown") tag = "dropdown";
			if (field.type === "textarea") tag = "textarea";
			// ==================================================
			// NORMALISASI PROP AGAR SESUAI UIComponentRegistry
			// ==================================================
			const prop = {
				label: field.label || "",
				name: field.name || "",
				type: field.type || "text",
				value: field.value || "",
				options: field.options || [],
			};

			const html = UIComponentRegistry.render({
				tag: tag,
				prop: {
					prop: prop,
				},
			});

			fieldContainer.append(html);
		});

		// ======================================================
		// INIT SEMUA FOMANTIC COMPONENT
		// ======================================================
		UIComponents.initAll();

		this.bindEvents();
	}

	renderSection(section) {
		// FIX: gunakan name jika key tidak ada
		let key = section.key || section.name;
		let html = `
			<h4 class="ui horizontal divider header">
				<i class="feather alternate icon"></i> ${section.label}
			</h4>

			<table class="ui celled structured table" name="${key}">
				<thead>
					<tr>
						<th>URAIAN</th>
						<th class="collapsing">
							<button type="button"
								class="ui green icon mini button btn-add-row"
								data-section="${section.key || section.name}">
								<i class="plus icon"></i>
							</button>
						</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		`;

		this.sectionContainer.append(html);
	}

	buildRow(section, text = "", type = "paragraph") {
		return `

<tr>

<td>

<textarea 
	class="doc-text"
	name="${section}[]"
	rows="2">${text}</textarea>

</td>

<td class="collapsing right aligned">

<div class="ui icon mini buttons">

<div class="ui floating dropdown icon button upward doc-type"
	data-value="${type}">

<i class="wrench icon"></i>

<div class="menu">

<div class="item" data-value="paragraph">
<i class="align left icon"></i> Paragraf
</div>

<div class="item" data-value="list">
<i class="list icon"></i> List
</div>

<div class="item" data-value="numbered">
<i class="ordered list icon"></i> Numbered
</div>

</div>

</div>

<button type="button"
	class="ui red icon button btn-del-row">
<i class="trash alternate outline icon"></i>
</button>

</div>

</td>

</tr>

`;
	}

	bindEvents() {
		const self = this;

		// 🔥 HAPUS EVENT LAMA DULU
		this.container.off("click", ".btn-add-row");
		this.container.off("click", ".btn-del-row");

		// ADD ROW
		this.container.on("click", ".btn-add-row", function () {
			let section = $(this).data("section");
			let row = self.buildRow(section);
			let $tbody = self.container.find(`table[name="${section}"] tbody`);

			$tbody.append(row);
			$tbody.find(".ui.dropdown").dropdown();
		});

		// DELETE ROW
		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});
	}

	renderAutoNomor(field) {
		const html = UIExtensions.renderAutoNumber({
			label: field.label,
			name: field.name,
			value: this.data.nomor_auto,
		});

		this.container.find("#doc-fields").append(html);
	}
	renderText(field) {
		const html = UIComponentRegistry.render({
			tag: "field",
			prop: {
				label: field.label,
				name: field.name,
				type: "text",
				value: field.value || "",
			},
		});

		this.container.append(html);
	}
	renderDate(field) {
		const html = UIComponentRegistry.render({
			tag: "calendar",
			prop: {
				label: field.label,
				name: field.name,
			},
		});

		this.container.append(html);
	}
}
