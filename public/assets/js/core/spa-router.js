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
		const firstSegment = "/" + url.split("/")[1];

		// Jika ada module JS untuk segment ini → client
		if (window.appModuleMap && window.appModuleMap[firstSegment]) {
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
		} else {
			if (window.app?.initPage) {
				window.app.initPage();
			}
		}
	}

	// ================================
	// CLICK HANDLER
	// ================================
	bindLinks() {
		$(document).on("click", "[data-spa]", (e) => {
			e.preventDefault();

			const $link = $(e.currentTarget);

			const url = $link.attr("href");
			const mode = $link.data("spa");

			const req = $link.data("req");
			// 🔥 TAMBAHAN: ambil title dari menu
			const title = $link.data("title"); // ambil data-title dari menu

			// 🔥 UPDATE HEADER <a href="/module" data-spa="client" data-title="MODULE NAME">
			if (title) {
				$("#dynamicHeaderTitle").text(title); // ubah teks header
			}
			if (window.app?.resetState) {
				window.app.resetState();
			}
			// Set req dari menu ke state sebelum navigasi
			if (window.app?.state && req) {
				window.app.state.req = req;
			}

			history.pushState({ mode }, "", url);

			if (mode === "client") {
				this.loadClientModule(url);
			} else {
				this.loadServerPartial(url);
			}
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
				// destroy table lama
				if (window.TableManager && TableManager.instances) {
					Object.values(TableManager.instances).forEach((tbl) => {
						if (typeof tbl.destroy === "function") {
							tbl.destroy();
						}
					});

					TableManager.instances = {};
				}

				// render module baru
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

				// 🔥 memastikan module JS tersedia
				if (window.app?.loadModule) {
					window.app.loadModule(url);
				}

				if (window.app?.initPage) {
					window.app.initPage();
				}

				if (typeof initFomantic === "function") {
					initFomantic();
				}
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
