class KontrakModule extends BaseCrudModule {
	// // WAJIB extend BaseCrudModule
	constructor() {
		super({
			moduleName: "kontrak",
			menuItems: [],
			useMenu: false,

			// // tbl default agar bisa dipakai internal
			defaultTable: {
				tbl: "kontrak",
				// req: "kontrak_neo",
			},
		});
	}

	init() {
		super.init();

		// // trigger manual tanpa menu & tanpa URL
		if (!this.currentTable) {
			this.loadTable("kontrak", "kontrak");
		}
	}
}
