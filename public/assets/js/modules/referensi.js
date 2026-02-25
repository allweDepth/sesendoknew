class ReferensiModule extends BaseCrudModule {

    constructor() {
        super({
            moduleName: "referensi",
            menuItems: [
                { label: "Urusan", tbl: "urusan" },
                { label: "Bidang", tbl: "bidang" },
                { label: "Program", tbl: "program" },
                { label: "Kegiatan", tbl: "kegiatan" },
                { label: "Sub Kegiatan", tbl: "sub_kegiatan" }
            ]
        });
    }

}