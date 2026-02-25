class SpaRouter {
	constructor() {
		this.bindLinks();
		this.bindPopState();
		this.handleInitialLoad(); // 🔥 refresh masuk sini
	}

	// ================================
	// Tentukan mode berdasarkan URL
	// ================================
	resolveMode(url) {
		// Client modules
		const clientRoutes = ["/renstra", "/referensi", "/kepegawaian"];

		if (clientRoutes.some((route) => url.startsWith(route))) {
			return "client";
		}

		return "server";
	}

	// ================================
	// INITIAL LOAD (REFRESH)
	// ================================
	handleInitialLoad() {
		const url = window.location.pathname;
		const mode = this.resolveMode(url);

		if (mode === "client") {
			this.loadClientModule(url);
		}
	}

	// ================================
	// CLICK HANDLER
	// ================================
	bindLinks() {
		$(document).on("click", "[data-spa]", (e) => {
			e.preventDefault();

			const url = $(e.currentTarget).attr("href");
			const mode = $(e.currentTarget).data("spa");

			history.pushState({ mode }, "", url);

			if (mode === "client") {
				this.loadClientModule(url);
			} else {
				this.loadServerPartial(url);
			}
			const $link = $(e.currentTarget);
			const iconClass = $link.find("i").attr("class");
			const text = $link.text().trim();

			// update header
			$("#dynamicHeaderIcon").attr("class", iconClass);
			$("#dynamicHeaderTitle").text(text);
		});
	}

	// ================================
	// CLIENT MODE
	// ================================
	loadClientModule(url) {
		fetch(url, {
			headers: { "X-Requested-With": "XMLHttpRequest" },
		})
			.then((res) => res.text())
			.then((html) => {
				$("#main-content").html(html);

				if (window.app?.loadModule) {
					window.app.loadModule(url);
				}

				if (typeof initFomantic === "function") {
					initFomantic();
				}
			});
	}

	// ================================
	// SERVER MODE
	// ================================
	loadServerPartial(url) {
		fetch(url, {
			headers: { "X-Requested-With": "XMLHttpRequest" },
		})
			.then((res) => res.text())
			.then((html) => {
				$("#main-content").html(html);

				if (window.app?.initPage) {
					window.app.initPage();
				}

				if (typeof initFomantic === "function") {
					initFomantic();
				}
			})
			.catch(() => {
				window.location.href = url;
			});
	}

	// ================================
	// BACK / FORWARD
	// ================================
	bindPopState() {
		window.addEventListener("popstate", (event) => {
			const url = window.location.pathname;
			const mode = event.state?.mode || this.resolveMode(url);

			if (mode === "client") {
				this.loadClientModule(url);
			} else {
				this.loadServerPartial(url);
			}
		});
	}
}
