class PengaturanWilayahModule {

    constructor() {
        this.state = window.app.state;
        this.ajax  = window.app.ajax;
        this.user  = window.app.user;
        this.container = "#pengaturan-wilayah";
    }

    init() {
        this.state.setTable("pengaturan");
        this.render();
        this.loadData();
    }

    render() {
        const readonly = this.user.type_user === "admin_opd" || 
                         this.user.type_user === "viewer";

        const html = `
            <div class="ui raised segment">
                <h4 class="ui dividing header">Kontrol Dokumen</h4>
                <form id="form-pengaturan" class="ui form"></form>
                ${readonly ? `
                    <div class="ui grey message">
                        Mode hanya baca.
                    </div>
                ` : ""}
            </div>
        `;

        $(this.container).html(html);

        this.formEngine = new FormEngine({
            state: this.state,
            ajax: this.ajax,
            formSelector: "#form-pengaturan"
        });

        this.formEngine.init();

        FormEngine.render(
            $("#form-pengaturan"),
            UIConfig.pengaturan.wilayah.elements,
            this.formEngine
        );

        if (readonly) {
            $("#form-pengaturan input").prop("disabled", true);
        }
    }

    loadData() {
        this.ajax.request({
            data: {
                module: "pengaturan",
                action: "load_single"
            },
            success: (res) => {
                if (!res.success) return;
                this.formEngine.populateForm(res.data);
            }
        });
    }

    destroy() {
        this.formEngine?.destroy();
        $(this.container).empty();
    }
}