class KontrakModule {
	init() {
		if (!window.app) return;

		// // reset state
		window.app.resetState();

		// // pakai logical name (tanpa _neo)
		window.app.tbl = "kontrak";
		window.app.state.tbl = "kontrak";

		// // trigger engine table
		if (typeof TableManager === "function") {
			new TableManager().init();
		}
	}
}
