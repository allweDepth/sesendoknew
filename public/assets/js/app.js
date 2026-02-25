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
		// ==============================
		// NORMALISASI URL
		// ==============================
		let page = null;

		// Jika format /?page=renstra
		if (url.includes("?page=")) {
			const parsed = new URL(url, window.location.origin);
			page = parsed.searchParams.get("page");
		}

		// Jika format /renstra
		else if (url.startsWith("/")) {
			page = url.replace("/", "");
		}

		switch (page) {
			case "renstra":
				new RenstraModule().init();
				break;

			case "referensi":
				new ReferensiModule().init();
				break;

			case "kepegawaian":
				new KepegawaianModule().init();
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