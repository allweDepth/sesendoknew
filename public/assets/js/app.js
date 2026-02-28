class App {
	constructor() {
		this.state = new AppState();
		this.ajax = window.Ajax;
		this.toast = ToastEngine;
		this.dialog = window.DialogEngine || null;
		this.loader = window.PageLoader || null;
		this.router = new SpaRouter(); // 🔥 INI WAJIB
		this.user = window.app.user;
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
			case !!document.getElementById("mappingMenu"):
				if (typeof MappingModule === "function") {
					new MappingModule().init();
				}
				break;

			case !!document.getElementById("pengaturanPage"):
				if (typeof PengaturanModule === "function") {
					new PengaturanModule().init();
				}
				break;
			case window.location.pathname === "/tata_naskah":
				if (typeof TataNaskahModule === "function") {
					new TataNaskahModule().init();
				}
				break;
			default:
				break;
		}
	}
	loadModule(url) {
		// ============================================
		// Normalisasi URL (hapus query string)
		// ============================================
		const cleanUrl = url.split("?")[0];

		// Ambil segment pertama
		const firstSegment = "/" + cleanUrl.split("/")[1];

		const moduleName = window.appModuleMap
			? window.appModuleMap[firstSegment]
			: null;

		if (!moduleName) {
			console.warn("Module tidak dikenali:", url);
			return;
		}

		this.loadedModules = this.loadedModules || [];

		if (this.loadedModules.includes(moduleName)) {
			this.initModuleInstance(moduleName);
			return;
		}

		// Dependency khusus Tata Naskah
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

		const script = document.createElement("script");
		script.src = `/assets/js/modules/${moduleName}.js`;
		script.defer = true;

		script.onload = () => {
			this.loadedModules.push(moduleName);
			this.initModuleInstance(moduleName);
		};

		script.onerror = () => {
			console.error("Gagal load module:", moduleName);
		};

		document.body.appendChild(script);
	}
	initModuleInstance(moduleName) {
		// 🔥 Destroy module lama dulu
		if (this.activeModule && typeof this.activeModule.destroy === "function") {
			this.activeModule.destroy();
		}
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
				if (typeof TataNaskahModule === "function") {
					this.activeModule = new TataNaskahModule();
					this.activeModule.init();
				}
				break;
			case "mapping":
				if (typeof MappingModule === "function") new MappingModule().init();
				break;
		}
	}
}
window.appModuleMap = {
	"/renstra": "renstra",
	"/referensi": "referensi",
	"/kepegawaian": "kepegawaian",
	"/standar_harga": "standar-harga",
	"/pengaturan": "pengaturan",
	"/tata_naskah": "tata_naskah", // 🔥 WAJIB ADA
	"/mapping": "mapping",
};
// ======================================================
// GLOBAL SINGLETON (WAJIB ADA SATU SAJA)
// ======================================================
window.Ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
