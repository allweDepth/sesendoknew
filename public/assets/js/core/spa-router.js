/**
 * ============================================================
 * SPA ROUTER - TRUE HISTORY MODE
 * ============================================================
 *
 * - Menggunakan History API
 * - Support Back / Forward
 * - Tanpa reload halaman
 */

class SpaRouter {
	constructor(state) {
		this.state = state;

		this.currentModule = null;

		// Mapping route ke module
		this.routes = {
			"/dashboard": null,
			"/tata-naskah": "tata_naskah",
			"/pengaturan": "pengaturan",
			"/renstra": "renstra",
			"/profil": "profil",
			"/wallchat": "wallchat",
		};
	}

	/**
	 * ========================================================
	 * INIT
	 * ========================================================
	 */
	init() {
		this.bindLinks();

		this.handleInitialRoute();

		this.bindPopState();
	}

	/**
	 * ========================================================
	 * HANDLE INITIAL LOAD
	 * ========================================================
	 */
	handleInitialRoute() {
		const path = window.location.pathname;

		this.loadRoute(path);
	}

	/**
	 * ========================================================
	 * LINK INTERCEPT
	 * ========================================================
	 */
	bindLinks() {
		document.addEventListener("click", (e) => {
			const link = e.target.closest("[data-route]");

			switch (true) {
				case !!link:
					e.preventDefault();

					const route = link.dataset.route;

					this.navigate(route);

					break;

				default:
					break;
			}
		});
	}

	/**
	 * ========================================================
	 * NAVIGATE
	 * ========================================================
	 */
	navigate(path) {
		history.pushState({}, "", path);

		this.loadRoute(path);
	}

	/**
	 * ========================================================
	 * POPSTATE (BACK/FORWARD)
	 * ========================================================
	 */
	bindPopState() {
		window.addEventListener("popstate", () => {
			this.loadRoute(window.location.pathname);
		});
	}

	/**
	 * ========================================================
	 * LOAD ROUTE
	 * ========================================================
	 */
	loadRoute(path) {
		this.destroyCurrentModule();

		const segments = path.split("/").filter(Boolean);

		const base = segments[0] ? "/" + segments[0] : "/dashboard";

		switch (base) {
			case "/renstra":
				this.currentModule = new RenstraModule();
				break;

			case "/pengaturan":
				this.currentModule = new PengaturanModule();
				break;

			case "/profil":
				this.currentModule = new ProfilModule();
				break;

			case "/wallchat":
				this.currentModule = new WallchatModule();
				break;

			case "/tata-naskah":
				this.currentModule = new TataNaskahModule(segments[1] || null);
				break;

			case "/kepegawaian":
				this.currentModule = new KepegawaianModule(segments[1] || null);
				break;

			case "/referensi":
				this.currentModule = new ReferensiModule(segments[1] || null);
				break;

			case "/dashboard":
				this.loadDashboard();
				return;

			default:
				this.loadNotFound();
				return;
		}

		this.state.setModule(base);

		this.currentModule.init();
	}

	/**
	 * ========================================================
	 * DASHBOARD
	 * ========================================================
	 */
	loadDashboard() {
		document.getElementById("main-content").innerHTML = `
            <div class="ui segment">
                <h2 class="ui header">Dashboard</h2>
                <p>Selamat datang di seSendok.</p>
            </div>
        `;
	}

	/**
	 * ========================================================
	 * 404
	 * ========================================================
	 */
	loadNotFound() {
		document.getElementById("main-content").innerHTML = `
            <div class="ui negative message">
                <div class="header">404</div>
                <p>Halaman tidak ditemukan.</p>
            </div>
        `;
	}

	/**
	 * ========================================================
	 * DESTROY MODULE
	 * ========================================================
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
