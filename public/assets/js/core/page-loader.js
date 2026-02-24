const PageLoader = (function () {

    function loadFromUrl() {

        const currentPath = window.location.pathname.replace(/^\/+/g, "");
        AppState.page = currentPath;

        const params = new URLSearchParams(window.location.search);
        // const moduleConfig = UIConfig[currentPath];
const segments = currentPath.split("/");
const moduleName = segments[0];

const moduleConfig = UIConfig[moduleName];
        if (!moduleConfig) return;

        let tbl = params.get("tbl") || Object.keys(moduleConfig)[0];

        if (!tbl) return;

        if (typeof tableManager !== "undefined") {
            tableManager.load(currentPath, tbl);
        }
    }

    return { loadFromUrl };

})();