class RenstraModule extends BaseCrudModule {

    constructor() {
        super({
            moduleName: "renstra",
            menuItems: [
                { label: "Visi", tbl: "renstra_neo" },
                { label: "Misi", tbl: "misi_renstra_neo" },
                { label: "Tujuan", tbl: "tujuan_renstra_neo" },
                { label: "Sasaran", tbl: "sasaran_renstra_neo" },
                { label: "Indikator Sasaran", tbl: "indikator_sasaran_renstra_neo" },
                { label: "Program", tbl: "program_renstra_neo" },
                { label: "Indikator Program", tbl: "indikator_program_renstra_neo" },
                { label: "Kegiatan", tbl: "kegiatan_renstra_neo" },
                { label: "Sub Kegiatan", tbl: "sub_kegiatan_renstra_neo" }
            ],
            useMenu: true   // 🔥 WAJIB
        });
    }

}