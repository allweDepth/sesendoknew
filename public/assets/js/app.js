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

        // State global aplikasi
        this.state = new AppState();

        // HTTP handler
        this.ajax = new AjaxService();

        // Router SPA
        this.router = new SpaRouter(this.state);

        // Toast notification
        this.toast = new Toast();

        // Dialog controller
        this.dialog = new Dialog();

        // Page Loader
        this.loader = new PageLoader();
    }

    /**
     * ==================================================
     * INIT APPLICATION
     * ==================================================
     */
    init() {

        // Inisialisasi router
        this.router.init();

        // Set event global
        this.bindGlobalEvents();

        console.log("Application initialized successfully.");
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