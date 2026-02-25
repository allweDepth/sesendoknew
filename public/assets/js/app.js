/**
 * ======================================================
 * MAIN APPLICATION CONTROLLER
 * ======================================================
 * Bertanggung jawab atas:
 * - Inisialisasi semua core system
 * - Menjadi pusat kontrol lifecycle
 * - Tidak boleh memanggil AJAX langsung
 */

class App {
	constructor() {
		// State global
		this.state = new AppState();

		// Gunakan AjaxEngine asli
		this.ajax = new AjaxEngine();

		// Gunakan SpaRouter
		this.router = new SpaRouter(this.state);

		// Gunakan ToastEngine asli (TIDAK perlu instance)
		this.toast = ToastEngine;

		// Gunakan DialogEngine jika ada
		this.dialog = window.DialogEngine || null;

		// Gunakan PageLoader jika ada
		this.loader = window.PageLoader || null;
	}

	init() {
		// Simpan global reference
		window.app = this;

		// Init router
		this.router.init();

		console.log("App initialized.");
	}

	/**
	 * ==================================================
	 * GLOBAL EVENTS
	 * ==================================================
	 */
	bindGlobalEvents() {
		// Contoh global click handler
		document.addEventListener("click", (event) => {
			switch (true) {
				case event.target.matches("[data-route]"):
					event.preventDefault();
					this.router.navigate(event.target.dataset.route);
					break;

				default:
					break;
			}
		});
	}
}
