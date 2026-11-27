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
		const routePath = routeUrl.split(/[?#]/)[0];
		if (routePath === "/kepegawaian/struktur") return "server";
		const firstSegment = "/" + routePath.split("/")[1];

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
		const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
		const url = window.appRoutePath ? window.appRoutePath(currentUrl) : currentUrl;
		this.updateHeaderFromCurrentUrl();
		const mode = this.resolveMode(url);

		if (mode === "client") {
			window.app?.loadModule?.(url);
		} else {
			if (window.app?.initPage) {
				window.app.initPage();
			}
			if (typeof window.initMapsPage === "function") {
				window.initMapsPage();
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
			if (matches && (!bestMatch || target.searchParams.size > bestMatch.params))
				bestMatch = { link, params: target.searchParams.size };
		});
		if (bestMatch) this.updateHeader(bestMatch.link);
		else this.setActiveNavigation(null);
	}

	updateHeader(link) {
		const $link = $(link),
			title = $link.data("title") || "Dashboard";
		const sidebarLink = [...document.querySelectorAll(".sidebarutama a[data-spa]")]
			.find(candidate => candidate.href === link.href);
		const icon = $link.find("i.icon").last().attr("class")
			|| $(sidebarLink).closest(".workspace-group").find(".workspace-nav-icon > i").attr("class")
			|| "home icon";
		const description =
			$link.data("description") || `Kelola ${String(title).replaceAll("/", " — ")} pada tahun anggaran aktif.`;
		$("#dynamicHeaderTitle").text(title);
		$("#dynamicHeaderIcon").attr("class", icon).addClass("circular blue");
		$("#dynamicHeader .pDashboard").text(description);
		this.setActiveNavigation(sidebarLink);
	}

	setActiveNavigation(link) {
		$(".sidebarutama a.item.active").removeClass("active").removeAttr("aria-current");
		if (link) $(link).addClass("active").attr("aria-current", "page");
		document.dispatchEvent(new Event("workspace:navigation"));
	}

	async ensureSession() {
		if (this.sessionLocked) return false;
		try {
			const response = await fetch("/session/status", {
				headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
				cache: "no-store",
			});
			const payload = await response.json().catch(() => ({}));
			if (!response.ok || payload.expired || payload.success !== true)
				throw new Error(payload.message || "Session habis");
			return true;
		} catch (_) {
			this.sessionLocked = true;
			$("body").addClass("session-expired");
			window.location.replace(window.appUrl ? window.appUrl("/") : "/");
			return false;
		}
	}

	// ================================
	// CLICK HANDLER
	// ================================
	bindLinks() {
		$(document).on("click", "[data-spa]", async (e) => {
			if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button > 0) return;
			e.preventDefault();
			const intent = this.intent = Symbol();
			if (!(await this.ensureSession()) || intent !== this.intent) return;

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
	beginNavigation() {
		this.navigation?.abort();
		window.activeBudgetDocument?.request?.abort();
		window.activeBudgetDocument?.closeWorkspace?.();
		this.navigation = new AbortController();
		document.querySelector("#main-content")?.setAttribute("aria-busy", "true");
		return this.navigation;
	}

	finishNavigation(navigation) {
		if (navigation === this.navigation) document.querySelector("#main-content")?.removeAttribute("aria-busy");
	}

	loadClientModule(url) {
		const navigation = this.beginNavigation();
		fetch(url, {
			signal: navigation.signal,
			headers: { "X-Requested-With": "XMLHttpRequest" },
		})
			.then((res) => res.text())
			.then((html) => {
				if (navigation !== this.navigation || navigation.signal.aborted) return;
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
			}).catch(error => { if (error.name !== "AbortError") console.error("Gagal memuat halaman", error); })
			.finally(() => this.finishNavigation(navigation));
	}

	// ================================
	// SERVER MODE
	// ================================
	loadServerPartial(url) {
		const navigation = this.beginNavigation();
		return fetch(url, {
			signal: navigation.signal,
			headers: { "X-Requested-With": "XMLHttpRequest" },
		})
			.then((res) => res.text())
			.then((html) => {
				if (navigation !== this.navigation || navigation.signal.aborted) return;
				window.tableManager = null;
				$("#main-content").html(html);

				// Struktur organisasi is a complete server-rendered page. Loading the
				// generic Kepegawaian module here would replace its fragment.
				const routeUrl = window.appRoutePath ? window.appRoutePath(url) : url;
				const routePath = routeUrl.split(/[?#]/)[0];
				if (routePath !== "/kepegawaian/struktur" && !routePath.startsWith("/maps") && window.app?.loadModule) {
					window.app.loadModule(url);
				}

				if (window.app?.initPage) {
					window.app.initPage();
				}

				if (typeof window.initMapsPage === "function") {
					window.initMapsPage();
				}

				if (typeof initFomantic === "function") {
					initFomantic();
				}
			}).catch(error => { if (error.name !== "AbortError") console.error("Gagal memuat halaman", error); })
			.finally(() => this.finishNavigation(navigation));
	}

	// ================================
	// BACK / FORWARD
	// ================================
	bindPopState() {
		window.addEventListener("popstate", (event) => {
			this.intent = null;
			this.updateHeaderFromCurrentUrl();
			const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
			const url = window.appRoutePath ? window.appRoutePath(currentUrl) : currentUrl;
			const mode = event.state?.mode || this.resolveMode(url);

			if (mode === "client") {
				this.loadClientModule(url);
			} else {
				this.loadServerPartial(url);
			}
		});
	}
}
