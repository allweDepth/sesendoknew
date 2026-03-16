class DropdownEngine {
	constructor(container) {
		this.container = container || $("body");
	}

	init() {
		this.bindDropdownInsert();
	}

	bindDropdownInsert() {
		const self = this;

		this.container.off("change", ".lookup-dropdown");

		this.container.on("change", ".lookup-dropdown", function () {
			const dropdown = $(this);

			const value = dropdown.dropdown("get value");
			const text = dropdown.dropdown("get text");

			if (!value) return;

			const target = dropdown.data("target-table");

			if (!target) return;

			const data = {
				id: value,
				nama: text,
				source: dropdown.data("source") || "",
			};

			self.insertToTable(target, data);
		});
	}

	insertToTable(target, data) {
		const table = this.container.find('table[data-dropdown-target="' + target + '"]');

		if (!table.length) return;

		const tbody = table.find("tbody");

		const headers = table.find("thead th");

		let cols = "";

		headers.each(function () {
			const field = $(this).data("field");

			if (field) {
				const value = data[field] || "";

				cols += `
				<td>
					<input type="hidden" name="${target}_${field}[]" value="${value}">
					${value}
				</td>
				`;
			} else {
				cols += `<td></td>`;
			}
		});

		const row = `
		<tr data-id="${data.id}" data-source="${data.source}">
			${cols}
			<td class="collapsing">
				<button type="button" class="ui mini red icon button btn-del-row">
					<i class="trash icon"></i>
				</button>
			</td>
		</tr>
		`;

		tbody.append(row);
	}
}
