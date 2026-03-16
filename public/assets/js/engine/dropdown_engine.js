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
		const table = this.container.find(`table[data-dropdown-target="${target}"]`);

		if (!table.length) return;

		const tbody = table.find("tbody");

		// FIX: gunakan data-columns yang dipakai DocumentBuilder
		const colCount = parseInt(table.data("columns") || 1);

		let cols = "";

		for (let i = 0; i < colCount; i++) {
			if (i === 0) {
				const value = data.nama || "";

				cols += `
            <td>
              <input type="hidden" name="${target}_nama[]" value="${value}">
              ${value}
            </td>
            `;
			} else {
				cols += `<td></td>`;
			}
		}

		const row = `
            <tr 
              data-id="${data.id}" 
              data-source="${data.source}" 
              data-section="${target}">
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
