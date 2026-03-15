class App {
	// class utama aplikasi SPA

	constructor() {
		// constructor dijalankan saat instance App dibuat

		this.state = new AppState(); // membuat instance AppState untuk menyimpan state global
		this.ajax = window.Ajax; // mengambil engine ajax global
		this.toast = Toast; // mengambil engine toast notification
		this.dialog = window.DialogEngine || null; // engine dialog jika tersedia
		this.loader = window.PageLoader || null; // engine loader jika tersedia
		this.router = new SpaRouter(); // router SPA wajib ada
		this.user = window.app.user; // mengambil data user dari global sebelumnya

		this.tbl = null; // logical table aktif
		this.req = null; // nama tabel server jika override
		this.action = null; // action CRUD aktif
		this.id = null; // id record yang sedang diproses
	}

	init() {
		// fungsi inisialisasi aplikasi

		window.app = this; // menjadikan instance App sebagai singleton global
		console.log("App initialized."); // log bahwa app berhasil diinisialisasi
	}

	// ======================================================
	// RESET GLOBAL STATE
	// ======================================================

	resetState() {
		// fungsi reset state saat menu berubah

		this.tbl = null; // reset logical table
		this.req = null; // reset server table
		this.action = null; // reset action
		this.id = null; // reset id record

		if (this.state) {
			// jika AppState digunakan
			this.state.tbl = null; // reset state tbl
			this.state.req = null; // reset state req
			this.state.action = null; // reset state action
			this.state.id = null; // reset state id
		}
	}

	// ======================================================
	// SYNC URL PARAMETER KE STATE
	// ======================================================

	syncStateFromUrl(url) {
		// fungsi untuk membaca parameter URL

		const query = url.split("?")[1]; // ambil query string

		if (!query) return; // jika tidak ada query maka keluar

		const params = new URLSearchParams(query); // parsing query

		this.tbl = params.get("tbl");
		this.req = params.get("req");
		this.action = params.get("action");
		this.id = params.get("id");

		if (this.state) {
			if (this.tbl !== null) {
				this.state.tbl = this.tbl;
			}

			// 🔥 JANGAN TIMPA req JIKA TIDAK ADA DI URL
			if (this.req !== null) {
				this.state.req = this.req;
			}

			if (this.action !== null) {
				this.state.action = this.action;
			}

			if (this.id !== null) {
				this.state.id = this.id;
			}
		}
	}

	// Re-init halaman setelah inject
	initPage() {
		// fungsi untuk re-inisialisasi halaman setelah inject SPA

		// Auto init module berdasarkan DOM
		switch (
			true // switch berdasarkan kondisi
		) {
			case !!document.getElementById("renstraMenu"): // jika halaman renstra aktif
				if (typeof RenstraModule === "function") {
					// cek apakah module tersedia
					new RenstraModule().init(); // jalankan module
				}
				break; // keluar dari switch

			case !!document.getElementById("mappingMenu"): // jika halaman mapping aktif
				if (typeof MappingModule === "function") {
					// cek module
					new MappingModule().init(); // jalankan module
				}
				break; // keluar dari switch

			case !!document.getElementById("pengaturanPage"): // jika halaman pengaturan aktif
				if (typeof PengaturanModule === "function") {
					// cek module
					new PengaturanModule().init(); // jalankan module
				}
				break; // keluar dari switch

			case window.location.pathname.startsWith("/tata_naskah"): // jika halaman tata naskah
				if (typeof TataNaskahModule === "function") {
					// cek module
					new TataNaskahModule().init(); // jalankan module
				}
				break; // keluar dari switch

			case window.location.pathname === "/reset_tabel": // perbaikan kondisi reset tabel
				if (typeof ResetTabelModule === "function")
					// cek module
					new ResetTabelModule().init(); // jalankan module
				break; // keluar dari switch
			case !!document.getElementById("profilPage"):
				if (typeof ProfilModule === "function") new ProfilModule().init();
				break;
			case "wallchat":
				if (typeof WallchatModule === "function") new WallchatModule().init();
				break;
			default: // jika tidak ada kondisi cocok
				break; // tidak melakukan apa apa
		}
	}

	loadModule(url) {
		// fungsi untuk memuat module berdasarkan URL
		// ============================================
		// SYNC STATE DARI URL
		// ============================================

		this.syncStateFromUrl(url); // isi state berdasarkan URL

		// ============================================
		// Normalisasi URL (hapus query string)
		// ============================================

		const cleanUrl = url.split("?")[0]; // hapus query string

		// Ambil segment pertama
		const firstSegment = "/" + cleanUrl.split("/")[1]; // ambil segment pertama path

		const moduleName = window.appModuleMap ? window.appModuleMap[firstSegment] : null; // cari nama module dari map

		if (!moduleName) {
			// jika module tidak ditemukan
			console.warn("Module tidak dikenali:", url); // tampilkan warning
			return; // hentikan proses
		}

		this.loadedModules = this.loadedModules || []; // array module yang sudah diload

		if (this.loadedModules.includes(moduleName)) {
			// jika module sudah pernah diload
			this.initModuleInstance(moduleName); // langsung jalankan instance
			return; // tidak perlu load script lagi
		}

		// Dependency khusus Tata Naskah
		if (moduleName === "tata_naskah") {
			// jika module tata naskah

			if (!this.loadedModules.includes("document_schema")) {
				// cek schema belum dimuat

				const schemaScript = document.createElement("script"); // buat element script
				schemaScript.src = "/assets/js/engine/document/document_schema.js"; // path schema
				document.body.appendChild(schemaScript); // inject script
				this.loadedModules.push("document_schema"); // tandai sudah dimuat
			}

			if (!this.loadedModules.includes("document_builder")) {
				// cek builder belum dimuat

				const builderScript = document.createElement("script"); // buat element script
				builderScript.src = "/assets/js/engine/document/document_builder.js"; // path builder
				document.body.appendChild(builderScript); // inject script
				this.loadedModules.push("document_builder"); // tandai sudah dimuat
			}
		}

		const script = document.createElement("script"); // buat script module

		script.src = `/assets/js/modules/${moduleName}.js`; // set path module

		script.defer = true; // gunakan defer

		script.onload = () => {
			// ketika script selesai load

			this.loadedModules.push(moduleName); // tandai module sudah dimuat
			this.initModuleInstance(moduleName); // jalankan instance module
		};

		script.onerror = () => {
			// jika script gagal load
			console.error("Gagal load module:", moduleName); // tampilkan error
		};

		document.body.appendChild(script); // inject script ke body
	}

	initModuleInstance(moduleName) {
		// membuat instance module berdasarkan nama

		switch (
			moduleName // switch module
		) {
			case "renstra": // module renstra
				if (typeof RenstraModule === "function") new RenstraModule().init(); // jalankan module
				break;

			case "referensi": // module referensi
				if (typeof ReferensiModule === "function") new ReferensiModule().init(); // jalankan module
				break;

			case "kepegawaian": // module kepegawaian
				if (typeof KepegawaianModule === "function") new KepegawaianModule().init(); // jalankan module
				break;

			case "standar-harga": // module standar harga
				if (typeof StandarHargaModule === "function") new StandarHargaModule().init(); // jalankan module
				break;

			case "pengaturan": // module pengaturan
				if (typeof PengaturanModule === "function") new PengaturanModule().init(); // jalankan module
				break;

			case "tata_naskah": // module tata naskah
				if (typeof TataNaskahModule === "function") new TataNaskahModule().init(); // jalankan module
				break;

			case "mapping": // module mapping
				if (typeof MappingModule === "function") new MappingModule().init(); // jalankan module
				break;

			case "reset_tabel": // module reset tabel
				if (typeof ResetTabelModule === "function") new ResetTabelModule().init(); // jalankan module
				break;
			case "profil":
				if (typeof ProfilModule === "function") new ProfilModule().init();
				break;
			case "wallchat":
				if (typeof WallchatModule === "function") new WallchatModule().init();
				break;
		}
	}
}

// mapping path ke module
window.appModuleMap = {
	"/renstra": "renstra", // module renstra
	"/referensi": "referensi", // module referensi
	"/kepegawaian": "kepegawaian", // module kepegawaian
	"/standar_harga": "standar-harga", // module standar harga
	"/pengaturan": "pengaturan", // module pengaturan
	"/tata_naskah": "tata_naskah", // module tata naskah
	"/mapping": "mapping", // module mapping
	"/reset_tabel": "reset_tabel", // module reset tabel
	"/profil": "profil",
	"/wallchat": "wallchat",
};

// ======================================================
// GLOBAL SINGLETON (WAJIB ADA SATU SAJA)
// ======================================================

window.Ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic"); // engine ajax global
