// ==============================
// INIT DEFAULT PENGATURAN
// ==============================

if (currentPath === "pengaturan" && !AppState.tbl) {
	tableManager.load("pengaturan", "periode_rpjmd");
}
//pengaturan menu
$("#rpjmd_mulai_calendar").calendar({
	type: "year",
	endCalendar: $("#rpjmd_selesai_calendar"),
});

$("#rpjmd_selesai_calendar").calendar({
	type: "year",
	startCalendar: $("#rpjmd_mulai_calendar"),
});
const PengaturanModule = {
	init(currentPath, tableManager) {
		if (currentPath === "pengaturan" && !AppState.tbl) {
			tableManager.load("pengaturan", "periode_rpjmd");
		}

		this.initCalendar();
	},

	initCalendar() {
		if ($("#rpjmd_mulai_calendar").length) {
			$("#rpjmd_mulai_calendar").calendar({
				type: "year",
				endCalendar: $("#rpjmd_selesai_calendar"),
			});

			$("#rpjmd_selesai_calendar").calendar({
				type: "year",
				startCalendar: $("#rpjmd_mulai_calendar"),
			});
		}
	},
};
