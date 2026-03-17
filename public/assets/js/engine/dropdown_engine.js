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

			const alias = {
				penandatangan: "asn",
				asn: "asn",
			};

			const real = self.data[source] ? source : alias[source];
			const dataset = self.data[real] || [];
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

					$(document).trigger("dropdown:select", {
						target: el.attr("data-target-table") || el.attr("data-target"),
						data: selected,
						name: el.find("input[type=hidden]").attr("name"),
					});
				},
			});

			el.addClass("initialized");
		});
	}
}
