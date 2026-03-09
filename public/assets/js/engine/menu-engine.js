class MenuEngine {
	static init() {
		$(document).on("click", "#crudMenu .item", function () {
			const item = $(this);

			$("#crudMenu .item").removeClass("active");
			item.addClass("active");

			const tbl = item.data("tbl") || null;
			const req = item.data("req") || null;
			const action = item.data("action") || null;

			window.app = window.app || {};
			window.app.state = window.app.state || {};

			window.app.state.tbl = tbl;
			window.app.state.req = req;
			window.app.state.action = action;

			MenuEngine.syncButtons();

			$(document).trigger("menu:changed", window.app.state);
		});

		MenuEngine.initDefault();
	}

	static initDefault() {
		const first = $("#crudMenu .item.active");

		if (!first.length) return;

		window.app.state.tbl = first.data("tbl") || null;
		window.app.state.req = first.data("req") || null;
		window.app.state.action = first.data("action") || null;

		MenuEngine.syncButtons();

		$(document).trigger("menu:changed", window.app.state);
	}

	static syncButtons() {
		const tbl = window.app.state.tbl;
		const req = window.app.state.req;
		const action = window.app.state.action;

		$("[data-ui]").each(function () {
			const btn = $(this);

			if (tbl) btn.attr("data-tbl", tbl);
			if (req) btn.attr("data-req", req);
			if (action) btn.attr("data-action", action);
		});
	}
}
