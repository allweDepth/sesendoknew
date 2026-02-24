class DocumentBuilder {
	constructor(container, type) {
		this.container = container;
		this.type = type;
		this.schema = DocumentSchema[type] || null;
	}

	render() {
		if (!this.schema) return;
		// 🔥 CLEAR supaya tidak double
		this.container.empty();
		this.schema.sections.forEach((section) => {
			this.renderSection(section);
		});

		this.bindEvents();
	}

	renderSection(section) {
		let html = `
			<h4 class="ui horizontal divider header">
				<i class="feather alternate icon"></i> ${section.label}
			</h4>

			<table class="ui celled structured table" name="${section.key}">
				<thead>
					<tr>
						<th>URAIAN</th>
						<th class="collapsing">
							<button type="button"
								class="ui green icon mini button btn-add-row"
								data-section="${section.key}">
								<i class="plus icon"></i>
							</button>
						</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		`;

		this.container.append(html);
	}

	buildRow(section, text = "", type = "paragraph") {
		return `
	<tr>

		<td>

			<textarea class="doc-text"
	rows="2"
	data-ignore-validation="true">${text}</textarea>

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
}
