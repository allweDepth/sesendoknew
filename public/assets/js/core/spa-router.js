const SPARouter = (function () {
	const container = ".content-scroll";
	let loading = false;

	function init() {
		bindLinks();
		handlePopState();
	}

	function bindLinks() {
		$(document).on("click", "a", function (e) {
			const href = $(this).attr("href");

			if (!shouldHandle(href, this)) return;

			e.preventDefault();
			navigate(href);
		});
	}

	function shouldHandle(href, el) {
		if (!href) return false;
		if (href.startsWith("#")) return false;
		if ($(el).attr("target") === "_blank") return false;
		if ($(el).hasClass("no-spa")) return false;
		if (href.startsWith("http")) return false;
		return true;
	}

	function navigate(url, push = true) {
		if (loading) return;
		loading = true;

		showProgress();

		$.ajax({
			url: url,
			headers: { "X-Requested-With": "XMLHttpRequest" },
			success: function (response) {

    $(container).html(response);

    if (push) history.pushState({}, "", url);

    // 🔥 HITUNG ULANG PATH
    const currentPath = window.location.pathname.replace(/^\/+/g, "");
    AppState.page = currentPath;

    const params = new URLSearchParams(window.location.search);

    const moduleConfig = UIConfig[currentPath];

    if (moduleConfig) {
        let tbl = params.get("tbl") || Object.keys(moduleConfig)[0];

        if (tbl) {
            tableManager.load(currentPath, tbl);
        }
    }
},
			error: function () {
				window.location.href = url;
			},
			complete: function () {
				loading = false;
				hideProgress();
			},
		});
	}

	function handlePopState() {
		window.onpopstate = function () {
			navigate(location.pathname, false);
		};
	}

	function reInitComponents() {
		// 🔥 Re-init seluruh aplikasi sesuai lifecycle asli
		if (typeof App !== "undefined" && typeof App.init === "function") {
			App.init();
		}
	}

	function showProgress() {
		if (!$("#spa-progress").length) {
			$("body").append('<div id="spa-progress"></div>');
		}

		$("#spa-progress").css({
			position: "fixed",
			top: 0,
			left: 0,
			height: "3px",
			width: "0%",
			background: "#2185d0",
			zIndex: 9999,
			transition: "width 0.3s ease",
		});

		setTimeout(() => {
			$("#spa-progress").css("width", "70%");
		}, 100);
	}

	function hideProgress() {
		$("#spa-progress").css("width", "100%");
		setTimeout(() => {
			$("#spa-progress").remove();
		}, 200);
	}

	return { init, navigate };
})();
