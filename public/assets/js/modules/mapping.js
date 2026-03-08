class MappingModule extends BaseCrudModule {
	constructor() {
		super({
			moduleName: "mapping",

			menuItems: [
				{ label: "SSH", tbl: "mapping", req: "ssh" },

				{ label: "SBU", tbl: "mapping", req: "sbu" },

				{ label: "ASB", tbl: "mapping", req: "asb" },

				{ label: "HSPK", tbl: "mapping", req: "hspk" },
			],

			useMenu: true,
		});
	}
}
