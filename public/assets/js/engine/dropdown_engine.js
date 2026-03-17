class DropdownEngine {
	constructor(container, data) {
		this.container = container;
		this.data = data;
	}

	init() {
		this.bind();
	}

	bind() {
		const self = this;

		this.container.find(".lookup-dropdown").each(function () {
			const el = $(this);
			const source = el.data("source");

			const dataset = self.data[source] || [];
			if (!dataset.length) return;

			el.data("dataset", dataset);

			if (el.hasClass("initialized")) {
				el.dropdown("destroy");
			}

			el.dropdown({
				values: dataset.map((row) => ({
					name: row.uraian,
					value: row.id,
				})),

				onChange: function (value, text) {
					if (!value) return;

					const selected = dataset.find((r) => r.id == value) || {};

					self.container.trigger("dropdown:select", {
						target: el.data("target-table"),
						data: selected,
						name: el.find("input[type=hidden]").attr("name"),
					});
				},
			});

			el.addClass("initialized");
		});
	}
}
