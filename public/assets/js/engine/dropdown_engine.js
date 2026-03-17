// FILE: public/assets/js/engine/dropdown_engine.js

class DropdownEngine {
	constructor(container) {
		this.container = container || $("body");
	}

	init() {
		this.bindDropdownInsert();
	}

	bindDropdownInsert() {
		const self = this;

		this.container.find(".lookup-dropdown").each(function () {
			const dropdown = $(this);

			// destroy dulu kalau sudah pernah init
			if (dropdown.hasClass("initialized")) {
				dropdown.dropdown("destroy");
			}

			dropdown.dropdown({
				onChange: function (value, text) {
					if (!value) return;

					const target = dropdown.data("target-table");
					if (!target) return;

					// 🔥 AMBIL DARI MEMORY (INI KUNCI)
					const dataset = dropdown.data("dataset") || [];
					const selected = dataset.find((row) => row.id == value) || {};

					const data = {
						id: value,
						nama: text,
						source: dropdown.data("source") || "",
						...selected,
					};

					self.container.trigger("dropdown:select", {
						target: target,
						data: data,
					});
				},
			});

			dropdown.addClass("initialized");
		});
	}
}
