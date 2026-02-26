class PeriodeRPJMDModule {

    constructor() {
        this.state = window.app.state;
        this.ajax  = window.app.ajax;
        this.container = "#periode-rpjmd";
    }

    init() {
        this.state.setTable("periode_rpjmd");

        this.tableManager = new TableManager({
            state: this.state,
            ajax: this.ajax,
            container: this.container
        });

        this.tableManager.init();
    }

    destroy() {
        this.tableManager?.destroy();
        $(this.container).empty();
    }
}