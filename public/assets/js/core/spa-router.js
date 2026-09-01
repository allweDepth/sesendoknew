class SpaRouter {
	constructor() {
		this.sessionLocked = false;
		this.bindLinks();
		this.bindPopState();
		this.handleInitialLoad(); // 🔥 refresh masuk sini
	}

	// ================================
	// Tentukan mode berdasarkan URL
	// ================================
	resolveMode(url) {
		const routeUrl = window.appRoutePath ? window.appRoutePath(url) : url;
		const firstSegment = "/" + routeUrl.split("/")[1];

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
		const url = window.appRoutePath ? window.appRoutePath(window.location.pathname) : window.location.pathname;
		this.updateHeaderFromCurrentUrl();
		const mode = this.resolveMode(url);

		if (mode === "client") {
			this.loadClientModule(url);
		} else {
			if (window.app?.initPage) {
				window.app.initPage();
			}
		}
	}

	updateHeaderFromCurrentUrl() {
		const currentPath = window.location.pathname;
		const currentParams = new URLSearchParams(window.location.search);
		let bestMatch = null;

		document.querySelectorAll("[data-spa][data-title]").forEach((link) => {
			const target = new URL(link.href, window.location.origin);
			if (target.pathname !== currentPath) return;
			const matches = [...target.searchParams].every(([key, value]) => currentParams.get(key) === value);
			if (matches && (!bestMatch || target.searchParams.size > bestMatch.params)) bestMatch = { link, params: target.searchParams.size };
		});
		if (bestMatch) this.updateHeader(bestMatch.link);
	}

	updateHeader(link) {
		const $link = $(link), title = $link.data('title') || 'Dashboard';
		const icon = $link.find('i.icon').last().attr('class') || 'home icon';
		const description = $link.data('description') || `Kelola ${String(title).replaceAll('/', ' — ')} pada tahun anggaran aktif.`;
		$('#dynamicHeaderTitle').text(title);
		$('#dynamicHeaderIcon').attr('class', icon).addClass('circular blue');
		$('#dynamicHeader .pDashboard').text(description);
		$('.sidebarutama a.item').removeClass('active');
		$link.addClass('active').parents('.content').show().siblings('.title').addClass('active');
	}

	async ensureSession() {
		if (this.sessionLocked) return false;
		try {
			const response = await fetch('/session/status', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, cache: 'no-store' });
			const payload = await response.json().catch(() => ({}));
			if (!response.ok || payload.expired || payload.success !== true) throw new Error(payload.message || 'Session habis');
			return true;
		} catch (_) {
			this.sessionLocked = true;
			$('body').addClass('session-expired');
			window.location.replace(window.appUrl ? window.appUrl('/') : '/');
			return false;
		}
	}

	// ================================
	// CLICK HANDLER
	// ================================
	bindLinks() {
		$(document).on("click", "[data-spa]", async (e) => {
			e.preventDefault();
			if (!(await this.ensureSession())) return;

			const $link = $(e.currentTarget);

			const url = $link.attr("href");
			const mode = $link.data("spa");

			const req = $link.data("req");
			// 🔥 TAMBAHAN: ambil title dari menu
			const title = $link.data("title"); // ambil data-title dari menu

			// 🔥 UPDATE HEADER <a href="/module" data-spa="client" data-title="MODULE NAME">
			if (title) this.updateHeader(e.currentTarget);
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
					window.tableManager = null;
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
				window.tableManager = null;
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
			const url = window.appRoutePath ? window.appRoutePath(window.location.pathname) : window.location.pathname;
			const mode = event.state?.mode || this.resolveMode(url);

			if (mode === "client") {
				this.loadClientModule(url);
			} else {
				this.loadServerPartial(url);
			}
		});
	}
}
