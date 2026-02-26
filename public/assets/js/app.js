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

	let moduleName = null;

	switch (true) {
		case url.startsWith("/renstra"):
			moduleName = "renstra";
			break;

		case url.startsWith("/referensi"):
			moduleName = "referensi";
			break;

		case url.startsWith("/kepegawaian"):
			moduleName = "kepegawaian";
			break;

		case url.startsWith("/standar_harga"):
			moduleName = "standar-harga";
			break;

		case url.startsWith("/pengaturan"):
			moduleName = "pengaturan";
			break;

		case url.startsWith("/tata_naskah"):
			moduleName = "tata_naskah";
			break;

		default:
			console.warn("Module tidak dikenali:", url);
			return;
	}

	this.loadedModules = this.loadedModules || [];

	// Kalau sudah pernah load
	if (this.loadedModules.includes(moduleName)) {
		this.initModuleInstance(moduleName);
		return;
	}

	/* ====================================================
	   🔥 LOAD DEPENDENCY KHUSUS UNTUK TATA NASKAH
	==================================================== */
	if (moduleName === "tata_naskah") {

		if (!this.loadedModules.includes("document_schema")) {
			const schemaScript = document.createElement("script");
			schemaScript.src = "/assets/js/engine/document/document_schema.js";
			document.body.appendChild(schemaScript);
			this.loadedModules.push("document_schema");
		}

		if (!this.loadedModules.includes("document_builder")) {
			const builderScript = document.createElement("script");
			builderScript.src = "/assets/js/engine/document/document_builder.js";
			document.body.appendChild(builderScript);
			this.loadedModules.push("document_builder");
		}
	}

	/* ====================================================
	   🔥 LOAD MODULE UTAMA
	==================================================== */
	const script = document.createElement("script");
	script.src = `/assets/js/modules/${moduleName}.js`;
	script.defer = true;

	script.onload = () => {
		this.loadedModules.push(moduleName);
		this.initModuleInstance(moduleName);
	};

	document.body.appendChild(script);
}
	initModuleInstance(moduleName) {
		switch (moduleName) {
			case "renstra":
				if (typeof RenstraModule === "function") new RenstraModule().init();
				break;

			case "referensi":
				if (typeof ReferensiModule === "function") new ReferensiModule().init();
				break;

			case "kepegawaian":
				if (typeof KepegawaianModule === "function")
					new KepegawaianModule().init();
				break;

			case "standar-harga":
				if (typeof StandarHargaModule === "function")
					new StandarHargaModule().init();
				break;

			case "pengaturan":
				if (typeof PengaturanModule === "function")
					new PengaturanModule().init();
				break;

			case "tata_naskah":
				if (typeof TataNaskahModule === "function")
					new TataNaskahModule().init();
				break;
		}
	}
}
// ======================================================
// GLOBAL SINGLETON (WAJIB ADA SATU SAJA)
// ======================================================
window.Ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
