// ======================================================
// PENGATURAN MODULE (SINGLE FILE CLEAN VERSION)
// ======================================================

class PengaturanModule {

    constructor() {
        this.container = "#main-content";
        this.ajax = window.app.ajax;
        this.user = window.app.user || {};
        this.data = null;
    }

    init() {
        this.render();
        this.loadData();
    }

    render() {

        const canEdit =
            ["super_admin", "admin_wilayah"]
                .includes(this.user.type_user);

        const disabled = canEdit ? "" : "disabled";

        const html = `
            <div class="ui raised segment">
                <h3 class="ui dividing header">
                    <i class="settings icon"></i>
                    Pengaturan Sistem
                </h3>

                <form class="ui form" id="form-pengaturan">

                    <div class="field">
                        <label>Tahun Anggaran</label>
                        <input type="number" name="tahun" ${disabled}>
                    </div>

                    <div class="field">
                        <label>Tahun Renstra</label>
                        <input type="number" name="tahun_renstra" ${disabled}>
                    </div>

                    <div class="two fields">
                        <div class="field">
                            <label>Awal Renja</label>
                            <input type="date" name="awal_renja" ${disabled}>
                        </div>
                        <div class="field">
                            <label>Akhir Renja</label>
                            <input type="date" name="akhir_renja" ${disabled}>
                        </div>
                    </div>

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="kunci" ${disabled}>
                            <label>Kunci Global</label>
                        </div>
                    </div>

                    ${
                        canEdit
                            ? `
                        <button type="submit" class="ui primary button">
                            <i class="save icon"></i> Simpan
                        </button>
                    `
                            : `
                        <div class="ui grey disabled button">
                            Read Only
                        </div>
                    `
                    }

                </form>
            </div>
        `;

        $(this.container).html(html);

        $('.ui.checkbox').checkbox();

        $("#form-pengaturan").on("submit", (e) => {
            e.preventDefault();
            this.submit();
        });
    }

    loadData() {

        this.ajax.request({
            data: {
                action: "edit",
                tbl: "pengaturan_neo"
            },
            success: (res) => {

                if (!res.success || !res.data) return;

                this.data = res.data;
                this.populate();
            }
        });
    }

    populate() {

        const form = $("#form-pengaturan");

        Object.keys(this.data).forEach((key) => {

            const field = form.find(`[name="${key}"]`);
            if (!field.length) return;

            if (field.attr("type") === "checkbox") {
                field.prop("checked", this.data[key] == 1);
            } else {
                field.val(this.data[key]);
            }
        });
    }

    submit() {

        if (!this.data) return;

        const formData = {};

        $("#form-pengaturan")
            .serializeArray()
            .forEach((item) => {
                formData[item.name] = item.value;
            });

        $("#form-pengaturan input[type=checkbox]").each(function () {
            formData[this.name] = this.checked ? 1 : 0;
        });

        this.ajax.request({
            data: {
                action: "update",
                tbl: "pengaturan_neo",
                id: this.data.id,
                ...formData
            },
            success: (res) => {

                if (res.success) {
                    window.app.toast.success("Pengaturan berhasil disimpan");
                }
            }
        });
    }

    destroy() {
        $(this.container).empty();
    }
}