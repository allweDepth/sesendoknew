const PengaturanModule = {

    init(currentPath, tableManager) {

        // ==============================
        // INIT DEFAULT PENGATURAN
        // ==============================
        if (currentPath === "pengaturan" && !AppState.tbl) {
            tableManager.load("pengaturan", "periode_rpjmd");
        }

        // ==============================
        // INIT CALENDAR PENGATURAN
        // ==============================
        if (currentPath === "pengaturan") {

            // Pastikan elemen ada sebelum init
            if ($("#rpjmd_mulai_calendar").length) {
                $("#rpjmd_mulai_calendar").calendar({
                    type: "year",
                    endCalendar: $("#rpjmd_selesai_calendar"),
                });
            }

            if ($("#rpjmd_selesai_calendar").length) {
                $("#rpjmd_selesai_calendar").calendar({
                    type: "year",
                    startCalendar: $("#rpjmd_mulai_calendar"),
                });
            }

        }

    }

};