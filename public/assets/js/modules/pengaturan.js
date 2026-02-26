// ======================================================
// PENGATURAN MODULE — ENTERPRISE PREMIUM (CLEAN)
// ======================================================

class PengaturanModule {
	constructor() {
		this.container = "#main-content";
		this.ajax = window.app.ajax;
		this.user = window.app.user || {};
		this.data = null;
	}

	init() {
		this.renderLayout();
		this.loadData();
	}

	// ==================================================
	// LAYOUT WRAPPER
	// ==================================================
	renderLayout() {
		const tahunLabel = this.user.tahun || "-";

		$(this.container).html(`
            <div class="ui container form-wrapper">

                <!-- HEADER -->
                <div class="ui top attached segment page-header">
                    <div class="ui grid">
                        <div class="twelve wide column">
                            <h2 class="ui header">
                                <i class="settings icon"></i>
                                <div class="content">
                                    Pengaturan Sistem
                                    <div class="sub header">
                                        Konfigurasi wilayah, periode & kontrol sistem
                                    </div>
                                </div>
                            </h2>
                        </div>

                        <div class="four wide right aligned column">
                            <div class="ui tiny grey label">
                                Tahun Aktif: ${tahunLabel}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FORM PANEL -->
                <div class="ui bottom attached raised very padded segment">
                    <form class="ui form" id="form-pengaturan">
                        ${this.renderForm()}
                    </form>
                </div>

            </div>
        `);

		this.initUI();
		this.bindSubmit();
	}

	// ==================================================
	// FORM CONTENT
	// ==================================================
	renderForm() {
		const canEdit = ["super_admin", "admin_wilayah"].includes(
			this.user.type_user,
		);

		const disabled = canEdit ? "" : "disabled";

		return `

        <!-- ================= IDENTITAS ================= -->
        <h4 class="ui dividing header section-header">
            Identitas Wilayah
        </h4>

        <div class="two fields">
            <div class="field">
                <label>Tahun Anggaran</label>
                <input type="number" name="tahun" ${disabled}>
            </div>
            <div class="field">
                <label>Tahun Renstra</label>
                <input type="number" name="tahun_renstra" ${disabled}>
            </div>
        </div>

<!-- ================= ATURAN REFERENSI ================= -->
<h4 class="ui dividing header section-header">
    Aturan Referensi
</h4>

<div class="ui stackable two column grid">

    ${this.peraturanDropdown("aturan_anggaran", "Anggaran", disabled)}
    ${this.peraturanDropdown("aturan_organisasi", "Organisasi", disabled)}
    ${this.peraturanDropdown("aturan_pengadaan", "Pengadaan", disabled)}
    ${this.peraturanDropdown("aturan_akun", "Akun", disabled)}
    ${this.peraturanDropdown("aturan_asb", "ASB", disabled)}
    ${this.peraturanDropdown("aturan_sbu", "SBU", disabled)}
    ${this.peraturanDropdown("aturan_ssh", "SSH", disabled)}
    ${this.peraturanDropdown("aturan_hspk", "HSPK", disabled)}
    ${this.peraturanDropdown("aturan_sumber_dana", "Sumber Dana", disabled)}
    ${this.peraturanDropdown("aturan_sub_kegiatan", "Sub Kegiatan", disabled)}

</div>
        <!-- ================= PERIODE ================= -->
        <h4 class="ui dividing header section-header">
            Periode Dokumen
        </h4>

        <div class="ui stackable four column grid">

            ${this.rangeField("renja", "Renja", disabled)}
            ${this.rangeField("dpa", "DPA", disabled)}
            ${this.rangeField("rkpd", "RKPD", disabled)}
            ${this.rangeField("rka", "RKA", disabled)}

            ${this.rangeField("renja_p", "Renja Perubahan", disabled)}
            ${this.rangeField("dppa", "DPPA", disabled)}
            ${this.rangeField("kua_ppas", "KUA PPAS", disabled)}
            ${this.rangeField("rapbd", "RAPBD", disabled)}

            ${this.rangeField("rak", "RAK", disabled)}
            ${this.rangeField("rkpd_perubahan", "RKPD Perubahan", disabled)}
            ${this.rangeField("kua_ppas_perubahan", "KUA PPAS Perubahan", disabled)}
            ${this.rangeField("apbd_perubahan", "APBD Perubahan", disabled)}

            ${this.rangeField("renstra", "Renstra", disabled)}

        </div>


        <!-- ================= KONTROL ================= -->
        <h4 class="ui dividing header section-header">
            Kontrol Sistem
        </h4>

        <div class="ui four stackable cards">

            ${this.controlCard("Global", "kunci", "setujui", disabled)}
            ${this.controlCard("Renstra", "kunci_renstra", "setujui_renstra", disabled)}
            ${this.controlCard("Renja", "kunci_renja", "setujui_renja", disabled)}
            ${this.controlCard("DPA", "kunci_dpa", "setujui_dpa", disabled)}
            ${this.controlCard("Renja P", "kunci_renja_p", "setujui_renja_p", disabled)}
            ${this.controlCard("DPPA", "kunci_dppa", "setujui_dppa", disabled)}
            ${this.controlCard("Paket", "kunci_paket", "setujui_paket", disabled)}
            ${this.controlCard("Realisasi", "kunci_realisasi", "setujui_realisasi", disabled)}

        </div>


        <!-- ================= KETERANGAN ================= -->
        <h4 class="ui dividing header section-header">
            Keterangan
        </h4>

        <div class="field">
            <textarea name="keterangan" rows="3" ${disabled}></textarea>
        </div>


        <!-- ================= ACTION ================= -->
        <div class="ui divider"></div>

        ${
					canEdit
						? `
                <div class="right aligned field">
                    <button class="ui primary button">
                        <i class="save icon"></i>
                        Simpan Perubahan
                    </button>
                </div>
                `
						: `
                <div class="ui grey disabled button">
                    Read Only
                </div>
                `
				}

        `;
	}

	// ==================================================
	// RANGE FIELD (FOMANTIC RANGE CALENDAR)
	// ==================================================
	rangeField(name, label, disabled) {
		return `
        <div class="four wide computer eight wide tablet sixteen wide mobile column">

            <div class="field">
                <label>${label}</label>

                <div class="two fields">

                    <div class="field">
                        <div class="ui calendar start_${name}">
                            <div class="ui input left icon">
                                <i class="calendar icon"></i>
                                <input type="text"
                                       name="awal_${name}"
                                       ${disabled}>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <div class="ui calendar end_${name}">
                            <div class="ui input left icon">
                                <i class="calendar icon"></i>
                                <input type="text"
                                       name="akhir_${name}"
                                       ${disabled}>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
        `;
	}

	// ==================================================
	// CONTROL CARD
	// ==================================================
	controlCard(title, kunciField, setujuiField, disabled) {
		return `
        <div class="card">
            <div class="content">
                <div class="header">${title}</div>
            </div>
            <div class="content">

                <div class="ui toggle checkbox">
                    <input type="checkbox"
                           name="${kunciField}"
                           ${disabled}>
                    <label>Kunci</label>
                </div>

                <br>

                <div class="ui toggle checkbox">
                    <input type="checkbox"
                           name="${setujuiField}"
                           ${disabled}>
                    <label>Setujui</label>
                </div>

            </div>
        </div>
        `;
	}

	// ==================================================
	// INIT UI
	// ==================================================
	initUI() {
		$(".ui.checkbox").checkbox();

		const ranges = [
			"renja",
			"dpa",
			"rkpd",
			"rka",
			"renja_p",
			"dppa",
			"kua_ppas",
			"rapbd",
			"rak",
			"rkpd_perubahan",
			"kua_ppas_perubahan",
			"apbd_perubahan",
			"renstra",
		];

		ranges.forEach((name) => {
			$(`.start_${name}`).calendar({
				type: "datetime",
				endCalendar: $(`.end_${name}`),
			});

			$(`.end_${name}`).calendar({
				type: "datetime",
				startCalendar: $(`.start_${name}`),
			});
		});
	}

	// ==================================================
	// LOAD DATA
	// ==================================================
	loadData() {
		this.ajax.request({
			data: {
				action: "default", // single row mode
				tbl: "pengaturan",
			},
			success: (res) => {
				if (!res.success || !res.data || !res.data.length) return;

				this.data = res.data[0];
				this.populate();
			},
		});
	}

	populate() {
		if (!this.data) return;

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

	// ==================================================
	// SUBMIT
	// ==================================================
	bindSubmit() {
		$("#form-pengaturan").on("submit", (e) => {
			e.preventDefault();

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
					action: "edit",
					tbl: "pengaturan",
					id: this.data.id,
					...formData,
				},
				success: (res) => {
					if (res.success) {
						window.app.toast.success("Pengaturan berhasil disimpan");
					}
				},
			});
		});
	}

	destroy() {
		$(this.container).empty();
	}
    peraturanDropdown(name, label, disabled) {

    return `
    <div class="eight wide computer eight wide tablet sixteen wide mobile column">
        <div class="field">
            <label>${label}</label>
            <div class="ui fluid search selection dropdown peraturan-dropdown ${disabled}"
                 data-name="${name}">
                <input type="hidden" name="${name}">
                <i class="dropdown icon"></i>
                <div class="default text">Pilih ${label}</div>
                <div class="menu"></div>
            </div>
        </div>
    </div>
    `;
}
loadPeraturanDropdown() {

    this.ajax.request({
        data: {
            action: "dropdown",
            tbl: "peraturan"
        },
        success: (res) => {

            if (!res.success || !res.data) return;

            const rows = res.data;

            $(".peraturan-dropdown").each(function () {

                const menu = $(this).find(".menu");

                rows.forEach(row => {
                    menu.append(`
                        <div class="item" data-value="${row.id}">
                            ${row.uraian}
                        </div>
                    `);
                });

                $(this).dropdown();
            });
        }
    });
}
}
