class SpaRouter {

    constructor() {
        this.bindLinks();
        this.bindPopState();
    }

    bindLinks() {

        $(document).on("click", "[data-spa]", (e) => {

            e.preventDefault();

            const url = $(e.currentTarget).attr("href");
            const mode = $(e.currentTarget).data("spa");

            history.pushState({ mode }, "", url);

            switch (mode) {

                case "client":
                    this.loadClientModule(url);
                    break;

                case "server":
                    this.loadServerPartial(url);
                    break;

                default:
                    window.location.href = url;
                    break;
            }
        });
    }

    /**
     * ================================
     * CLIENT MODE
     * ================================
     */
    loadClientModule(url) {

        $("#main-content").empty();

        if (window.app && typeof window.app.loadModule === "function") {
            window.app.loadModule(url);
        }
    }

    /**
     * ================================
     * SERVER MODE
     * ================================
     */
    loadServerPartial(url) {

        fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(res => res.text())
        .then(html => {

            $("#main-content").html(html);

            if (window.app && typeof window.app.initPage === "function") {
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

    /**
     * ================================
     * BACK / FORWARD
     * ================================
     */
    bindPopState() {

        window.addEventListener("popstate", (event) => {

            const url = window.location.pathname + window.location.search;
            const mode = event.state?.mode;

            switch (mode) {

                case "client":
                    this.loadClientModule(url);
                    break;

                case "server":
                    this.loadServerPartial(url);
                    break;

                default:
                    window.location.reload();
                    break;
            }
        });
    }
}