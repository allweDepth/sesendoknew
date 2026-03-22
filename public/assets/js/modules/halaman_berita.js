class HalamanBeritaModule extends BaseCrudModule {
	constructor() {
		super({
			moduleName: "halaman_berita",
			menuItems: [],
			useMenu: false,

			// FIX: sesuaikan dengan backend endpoint
			defaultTable: {
				tbl: "halaman_berita",
				// req: "halaman_berita", // pastikan sesuai service
			},
		});
	}

	init() {
		super.init();

		// FIX: auto load tanpa menu
		if (!this.currentTable) {
			this.loadTable("halaman_berita");
		}
	}
}

// FIX: WAJIB instance, karena app.js tidak new otomatis
// export default new HalamanBeritaModule();
