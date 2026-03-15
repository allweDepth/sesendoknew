/**
 * ============================================================
 * DOCUMENT BUILDER
 * ============================================================
 * Builder dokumen tata naskah dinamis
 * mendukung:
 * - field dinamis
 * - section dinamis
 * - auto nomor
 * - fomantic ui
 */

class DocumentBuilder {
	constructor(container, type) {
		this.container = container;
		this.type = type;

		this.schema = null;
		this.sectionContainer = null;

		this.data = {};
	}

	/* ============================================================
	   RENDER DOCUMENT
	============================================================ */

	render() {
		if (!this.schema) return;

		this.container.empty();

		this.container.append(`
			<div id="doc-fields"></div>
			<div id="doc-sections"></div>
		`);

		const fieldContainer = this.container.find("#doc-fields");

		this.sectionContainer = this.container.find("#doc-sections");

		const fields = this.schema.sections || this.schema;

		fields.forEach((field) => {
			/* ===============================
			   SECTION
			================================ */

			if (field.type === "section") {
				this.renderSection(field);
				return;
			}

			/* ===============================
			   AUTO NOMOR
			================================ */

			if (field.type === "auto_nomor") {
				this.renderAutoNomor(field);
				return;
			}

			/* ===============================
			   FIELD NORMAL
			================================ */

			let tag = "field";

			if (field.type === "date") tag = "calendar";
			if (field.type === "dropdown") tag = "dropdown";
			if (field.type === "textarea") tag = "textarea";

			const prop = {
				label: field.label || "",
				name: field.name || "",
				type: field.type || "text",
				value: field.value || "",
				options: field.options || [],
			};

			const html = UIComponentRegistry.render({
				tag: tag,
				prop: { prop },
			});

			fieldContainer.append(html);
		});

		this.initFomantic();

		this.bindEvents();
	}

	/* ============================================================
	   SECTION BUILDER
	============================================================ */

	renderSection(section) {
		const key = section.key || section.name;

		const html = `
		<h4 class="ui horizontal divider header">
			<i class="file alternate outline icon"></i>
			${section.label}
		</h4>

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

		this.sectionContainer.append(html);
	}

	/* ============================================================
	   ROW BUILDER
	============================================================ */

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

				<div class="ui mini icon buttons">

					<div 
						class="ui floating dropdown icon button upward doc-type"
						data-value="${type}">

						<i class="wrench icon"></i>

						<div class="menu">

							<div class="item" data-value="paragraph">
								<i class="align left icon"></i>
								Paragraf
							</div>

							<div class="item" data-value="list">
								<i class="list icon"></i>
								List
							</div>

							<div class="item" data-value="numbered">
								<i class="ordered list icon"></i>
								Numbered
							</div>

						</div>

					</div>

					<button 
						type="button"
						class="ui red icon button btn-del-row">

						<i class="trash icon"></i>

					</button>

				</div>

			</td>

		</tr>
		`;
	}

	/* ============================================================
	   EVENT BINDING
	============================================================ */

	bindEvents() {
		const self = this;

		this.container.off("click", ".btn-add-row");
		this.container.off("click", ".btn-del-row");

		/* ===============================
		   ADD ROW
		================================ */

		this.container.on("click", ".btn-add-row", function () {
			const section = $(this).data("section");

			const row = self.buildRow(section);

			const tbody = self.container.find(`table[name="${section}"] tbody`);

			tbody.append(row);

			self.initFomantic();
		});

		/* ===============================
		   DELETE ROW
		================================ */

		this.container.on("click", ".btn-del-row", function () {
			$(this).closest("tr").remove();
		});
	}

	/* ============================================================
	   AUTO NOMOR
	============================================================ */

	renderAutoNomor(field) {
		const html = UIExtensions.renderAutoNumber({
			label: field.label,
			name: field.name,
			value: this.data.nomor_auto,
		});

		this.container.find("#doc-fields").append(html);
	}

	/* ============================================================
	   INIT FOMANTIC
	============================================================ */

	initFomantic() {
		this.container.find(".ui.dropdown").dropdown();

		this.container.find(".ui.calendar").calendar({
			type: "date",
		});

		this.container.find(".ui.checkbox").checkbox();
	}
}
