class StandarHargaModule extends BaseCrudModule {
    constructor() {
        super({
            moduleName: "standar_harga",
            menuItems: [
                { label: "SSH", tbl: "ssh" },
                { label: "HSPK", tbl: "hspk" },
                { label: "ASB", tbl: "asb" },
                { label: "SBU", tbl: "sbu" }
            ],
            useMenu: false
        });
    }

    init() {
        super.init();
        this.bindStandardActions();
    }

    buildActionButtons(tbl) {
        const canManage = window.app?.user?.type_user === "tapd";
        return `${super.buildActionButtons(tbl)}
            <div class="ui right floated basic icon buttons" style="margin-top:10px;margin-right:8px;">
                ${canManage ? `<button class="ui button" data-standard-action="copy-year" data-tbl="${tbl}" title="Salin ke tahun lain">
                    <i class="copy outline icon"></i>
                </button><button class="ui button" data-standard-action="mapping" data-tbl="${tbl}" title="Mapping akun">
                    <i class="sitemap icon"></i>
                </button>` : ""}
                <button class="ui button" data-standard-action="export-pdf" data-tbl="${tbl}" title="Download PDF">
                    <i class="file pdf outline icon"></i>
                </button>
            </div>`;
    }

    bindStandardActions() {
        $(document)
            .off("click.standardHarga", "#crud-table-container [data-standard-action]")
            .on("click.standardHarga", "#crud-table-container [data-standard-action]", (event) => {
                event.preventDefault();
                const button = $(event.currentTarget);
                const tbl = button.data("tbl") || this.state.tbl;
                const action = button.data("standard-action");

                if (action === "export-pdf") {
                    const url = `/standar_harga/export_pdf?tbl=${encodeURIComponent(tbl)}`;
                    window.location.href = window.appUrl ? window.appUrl(url) : url;
                    return;
                }

                if (action === "mapping") {
					const url = `/mapping?tbl=mapping&req=${encodeURIComponent(tbl)}`;
					if (window.app?.router?.navigate) window.app.router.navigate(url);
					else window.location.href = window.appUrl ? window.appUrl(url) : url;
					return;
				}

                const targetYear = window.prompt("Salin standar harga ke tahun:", String((Number(window.app?.user?.tahun) || new Date().getFullYear()) + 1));
                if (!targetYear) return;

                window.Ajax.request({
                    url: "/standar_harga/copy_year",
                    method: "POST",
                    data: { tbl, target_year: targetYear },
                    success: (response) => {
                        if (response?.success && window.tableManager) window.tableManager.fetchData();
                    }
                });
            });
    }
}
