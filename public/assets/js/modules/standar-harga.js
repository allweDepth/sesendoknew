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
                </button><button class="ui button" data-standard-action="import-sipd" data-tbl="${tbl}" title="Impor workbook SIPD beserta mapping rekening">
                    <i class="file excel outline icon"></i>
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

                if (action === "import-sipd") {
                    this.openSipdImport(tbl);
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

    openSipdImport(tbl) {
        const year = Number(window.app?.user?.tahun) || new Date().getFullYear();
        const html = `<form class="ui form" id="sipd-standard-import" enctype="multipart/form-data">
            <div class="ui info message">Format ekspor SIPD 9 kolom. ID standar harga, kelompok barang, satuan, harga, dan seluruh kode rekening akan dipetakan otomatis.</div>
            <div class="field"><label>Jenis standar harga</label><input value="${String(tbl).toUpperCase()}" readonly></div>
            <div class="field"><label>Tahun data</label><input type="number" name="tahun" min="2000" max="2100" value="${year}" required></div>
            <div class="field"><label>Workbook SIPD</label><input type="file" name="file" accept=".xlsx,.xls" required></div>
        </form>`;
        $("#mainModal .header").text(`Impor ${String(tbl).toUpperCase()} dari SIPD`);
        $("#mainModal .content").html(html);
        $("#mainModal .btnSubmit").off("click.sipdImport").on("click.sipdImport", (event) => {
            event.preventDefault();
            const form=document.getElementById("sipd-standard-import");
            if(!form?.reportValidity()) return;
            const data=new FormData(form);data.append("tbl",tbl);
            const button=$(event.currentTarget).addClass("loading disabled");
            window.Ajax.request({url:"/standar_harga/import_sipd",method:"POST",data,processData:false,contentType:false,success:(response)=>{if(response?.success){$("#mainModal").modal("hide");window.tableManager?.fetchData();}},complete:()=>button.removeClass("loading disabled")});
        });
        $("#mainModal").modal("show");
    }
}
