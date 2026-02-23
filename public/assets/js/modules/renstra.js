const RenstraModule = {

    init(currentPath, tableManager) {

        if (currentPath !== "renstra") return;

        this.bindTabClick(tableManager);
        this.initDefault(tableManager);
    },

    bindTabClick(tableManager) {

        $(document).on("click", "#renstraMenu .item", function (e) {
            e.preventDefault();

            let tbl = $(this).data("tbl");

            // aktifkan menu
            $("#renstraMenu .item").removeClass("active");
            $(this).addClass("active");

            // ubah judul
            $("#judulTabel").text($(this).text().toUpperCase());

            // ubah tombol
            $("#btnTambah").attr("data-tbl", tbl);
            $("#btnImport").attr("data-tbl", tbl);
            $("#btnExport").attr("data-tbl", tbl);

            // sinkronisasi state
            AppState.tbl = tbl;
            AppState.currentMenu = tbl;

            tableManager.load("renstra", tbl);
        });
    },

    initDefault(tableManager) {

        if (AppState.currentMenu) return;

        let $activeTab = $("#renstraMenu .item.active");

        if (!$activeTab.length) {
            $activeTab = $("#renstraMenu .item").first();
            $activeTab.addClass("active");
        }

        let defaultTbl = $activeTab.data("tbl");

        AppState.tbl = defaultTbl;
        AppState.currentMenu = defaultTbl;

        $("#btnTambah").attr("data-tbl", defaultTbl);
        $("#btnImport").attr("data-tbl", defaultTbl);
        $("#btnExport").attr("data-tbl", defaultTbl);

        tableManager.load("renstra", defaultTbl);
    }
};