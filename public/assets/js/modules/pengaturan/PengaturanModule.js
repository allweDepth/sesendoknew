class PengaturanModule {

    constructor() {
        this.mainContainer = "#main-content";
        this.user = window.app.user; // pastikan user diinject global
    }

    init() {
        this.renderLayout();

        // Wilayah config always visible (minimal view)
        this.wilayahModule = new PengaturanWilayahModule();
        this.wilayahModule.init();

        // Periode hanya untuk super_admin & admin_wilayah
        if (["super_admin", "admin_wilayah"].includes(this.user.type_user)) {
            this.periodeModule = new PeriodeRPJMDModule();
            this.periodeModule.init();
        }
    }

    renderLayout() {
        const html = `
            <div class="ui segment">
                <h3 class="ui header">
                    <i class="settings icon"></i>
                    Pengaturan Sistem
                </h3>

                <div id="pengaturan-wilayah"></div>
                <div id="periode-rpjmd"></div>
            </div>
        `;
        $(this.mainContainer).html(html);
    }

    destroy() {
        this.wilayahModule?.destroy();
        this.periodeModule?.destroy();
        $(this.mainContainer).empty();
    }
}