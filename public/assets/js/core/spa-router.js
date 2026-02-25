/**
 * ======================================================
 * SPA ROUTER
 * ======================================================
 * Bertugas:
 * - Mengatur perpindahan halaman
 * - Memanggil module yang sesuai
 * - Mengontrol lifecycle module
 */

class SpaRouter {

    constructor(state) {

        this.state = state;

        // Module aktif
        this.currentModule = null;

    }

    /**
     * INIT ROUTER
     */
    init() {

        console.log("Router initialized.");

    }

    /**
     * NAVIGATE
     */
    navigate(routeName) {

        // Hancurkan module lama jika ada
        this.destroyCurrentModule();

        // Simpan module ke state
        this.state.setModule(routeName);

        // Load module sesuai route
        this.loadModule(routeName);

    }

    /**
     * LOAD MODULE
     */
    loadModule(routeName) {

        switch (routeName) {

            case "tata_naskah":
                this.currentModule = new TataNaskahModule();
                break;

            case "pengaturan":
                this.currentModule = new PengaturanModule();
                break;

            case "renstra":
                this.currentModule = new RenstraModule();
                break;

            case "profil":
                this.currentModule = new ProfilModule();
                break;

            case "wallchat":
                this.currentModule = new WallchatModule();
                break;

            default:
                console.warn("Module tidak ditemukan:", routeName);
                return;
        }

        this.currentModule.init();
    }

    /**
     * DESTROY MODULE LAMA
     */
    destroyCurrentModule() {

        switch (typeof this.currentModule?.destroy) {

            case "function":
                this.currentModule.destroy();
                break;

            default:
                break;
        }

        this.currentModule = null;
    }

}