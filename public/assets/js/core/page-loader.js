const PageLoader = (function () {

    function loadFromUrl() {

        const currentPath = window.location.pathname.replace(/^\/+/g, "");
        AppState.page = currentPath;

        const params = new URLSearchParams(window.location.search);
        const moduleConfig = UIConfig[currentPath];

        if (!moduleConfig) return;

        let tbl = params.get("tbl") || Object.keys(moduleConfig)[0];

        if (!tbl) return;

        if (typeof tableManager !== "undefined") {
            tableManager.load(currentPath, tbl);
        }
    }

    return { loadFromUrl };

})();