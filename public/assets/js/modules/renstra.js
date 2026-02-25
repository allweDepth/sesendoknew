class RenstraModule extends BaseCrudModule {

    constructor() {
        super({
            moduleName: "renstra",
            menuItems: [
                { label: "Misi", tbl: "misi_renstra_neo" },
                { label: "Tujuan", tbl: "tujuan_renstra_neo" },
                { label: "Sasaran", tbl: "sasaran_renstra_neo" },
                { label: "Indikator Sasaran", tbl: "indikator_sasaran_renstra_neo" },
                { label: "Program", tbl: "program_renstra_neo" },
                { label: "Indikator Program", tbl: "indikator_program_renstra_neo" },
                { label: "Kegiatan", tbl: "kegiatan_renstra_neo" },
                { label: "Sub Kegiatan", tbl: "sub_kegiatan_renstra_neo" }
            ]
        });
    }

    init() {

        // 🔥 Bangun layout dasar sendiri (WAJIB di client mode)
        $("#main-content").html(`
            <div class="ui segment">
                <h3 class="ui header">RENSTRA</h3>

                <div id="renstra-menu" class="ui secondary menu"></div>

                <div class="table-wrapper">
                    <table class="ui celled table">
                        <thead></thead>
                        <tbody id="renstra-tbody"></tbody>
                    </table>
                </div>

                <div id="renstra-pagination"></div>
            </div>
        `);

        // lalu panggil parent logic
        super.init();
    }
}