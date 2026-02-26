class PengaturanModule {

    constructor() {
        this.container = "#main-content";
    }

    init() {
        $(this.container).html(`
            <div class="ui segment">
                <h3 class="ui header">
                    <i class="settings icon"></i>
                    Pengaturan Sistem
                </h3>
                <div id="pengaturan-wilayah"></div>
                <div id="periode-rpjmd"></div>
            </div>
        `);

        console.log("Pengaturan module loaded");
    }

    destroy() {
        $(this.container).empty();
    }
}