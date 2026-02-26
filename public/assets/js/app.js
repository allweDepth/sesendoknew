class App {
	constructor() {
		this.state = new AppState();
		this.ajax = new AjaxEngine();
		this.toast = ToastEngine;
		this.dialog = window.DialogEngine || null;
		this.loader = window.PageLoader || null;
		this.router = new SpaRouter(); // 🔥 INI WAJIB
	}

	init() {
		window.app = this;
		console.log("App initialized.");
	}

	// Re-init halaman setelah inject
	initPage() {
		// Auto init module berdasarkan DOM
		switch (true) {
			case !!document.getElementById("renstraMenu"):
				if (typeof RenstraModule === "function") {
					new RenstraModule().init();
				}
				break;

			case !!document.getElementById("pengaturanPage"):
				if (typeof PengaturanModule === "function") {
					new PengaturanModule().init();
				}
				break;

			default:
				break;
		}
	}
	loadModule(url) {
    switch (true) {

        case url.startsWith("/renstra"):
            new RenstraModule().init();
            break;

        case url.startsWith("/referensi"):
            new ReferensiModule().init();
            break;

        case url.startsWith("/kepegawaian"):
            new KepegawaianModule().init();
            break;

        case url.startsWith("/standar_harga"):   // 🔥 TAMBAHKAN INI
            new StandarHargaModule().init();
            break;

        case url.startsWith("/pengaturan"):      // kalau ada
            new PengaturanModule().init();
            break;

        case url.startsWith("/tata_naskah"):     // kalau ada
            new TataNaskahModule().init();
            break;

        default:
            console.warn("Module tidak dikenali:", url);
            break;
    }
}
}
// ======================================================
// GLOBAL SINGLETON (WAJIB ADA SATU SAJA)
// ======================================================
window.Ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
