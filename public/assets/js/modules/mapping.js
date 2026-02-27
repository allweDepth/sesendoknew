class MappingModule extends BaseCrudModule {

    constructor() {
        super({
            moduleName: "mapping",
            menuItems: [
                { label: "SBU",  tbl: "sbu_akun_map" },
                { label: "SSH",  tbl: "ssh_akun_map" },
                { label: "ASB",  tbl: "asb_akun_map" },
                { label: "HSPK", tbl: "hspk_akun_map" }
            ],
            useMenu: true
        });
    }

}