// ======================================================
// PENGATURAN MODULE — FINAL STABLE ENTERPRISE VERSION
// ======================================================

class PengaturanModule {
	constructor() {
		this.container = "#main-content";
		this.ajax = window.app.ajax;
		this.user = window.app.user || {};
		this.data = null;
	}

	// ==================================================
	// INIT
	// ==================================================
	init() {
		this.renderLayout();

		// load data dulu
		this.loadData(() => {
			// setelah data ada, baru load dropdown
			this.initUI(() => {
				// setelah dropdown siap, populate
				this.populate();
			});
		});
	}

	// ==================================================
	// ROLE CHECK (ANTI ERROR)
	// ==================================================
	canEdit() {
		const role = (this.user.type_user || "").toLowerCase().replace(/\s+/g, "_");

		return ["super_admin", "admin_wilayah"].includes(role);
	}

	// ==================================================
	// LAYOUT
	// ==================================================
	renderLayout() {
		$(this.container).html(`<div id="pengaturan-html"></div>`);

		$("#pengaturan-html").load("/pengaturan/fragment", () => {
			let periodeModule = null;

			$("#pengaturan-tabs .item").tab({
				onVisible: (tabPath) => {
					if (tabPath === "periode") {
						if (!periodeModule) {
							periodeModule = new PeriodeRPJMDModule();
							periodeModule.init();
						}
					}
				},
			});

			this.bindSubmit();
			this.bindPeriodTable();
			this.initUI();
		});
	}

	bindPeriodTable() {
		$(document).off('click.documentPeriod', '.edit-document-period').on('click.documentPeriod', '.edit-document-period', e => {
			if (!this.canEdit()) return;
			const button = $(e.currentTarget), row = button.closest('tr'), inputs = row.find('input');
			const editing = row.hasClass('editing');
			$('#document-period-table tr.editing').not(row).removeClass('editing').find('input').prop('readonly', true);
			$('#document-period-table .edit-document-period').not(button).removeClass('green').addClass('blue').find('i').attr('class', 'edit icon');
			row.toggleClass('editing', !editing);
			inputs.prop('readonly', editing);
			button.toggleClass('blue', editing).toggleClass('green', !editing).find('i').attr('class', editing ? 'edit icon' : 'check icon');
			if (!editing) inputs.first().trigger('focus');
		});
	}

	// ==================================================
	// FORM
	// ==================================================
	// renderForm() {
	// 	const disabled = this.canEdit() ? "" : "disabled";

	// 	return `

	//       <h4 class="ui dividing header section-header">
	//           Identitas Wilayah
	//       </h4>

	//       <div class="two fields">
	//           <div class="field">
	//               <label>Tahun Anggaran</label>
	//               <input type="number" name="tahun" ${disabled}>
	//           </div>
	//           <div class="field">
	//               <label>Tahun Renstra</label>
	//               <input type="number" name="tahun_renstra" ${disabled}>
	//           </div>
	//       </div>

	//       <h4 class="ui dividing header section-header">
	//           Aturan Referensi
	//       </h4>

	//       <div class="ui stackable two column grid">

	//           ${this.peraturanDropdown("aturan_anggaran", "Anggaran", disabled)}
	//           ${this.peraturanDropdown("aturan_organisasi", "Organisasi", disabled)}
	//           ${this.peraturanDropdown("aturan_pengadaan", "Pengadaan", disabled)}
	//           ${this.peraturanDropdown("aturan_akun", "Akun", disabled)}
	//           ${this.peraturanDropdown("aturan_asb", "ASB", disabled)}
	//           ${this.peraturanDropdown("aturan_sbu", "SBU", disabled)}
	//           ${this.peraturanDropdown("aturan_ssh", "SSH", disabled)}
	//           ${this.peraturanDropdown("aturan_hspk", "HSPK", disabled)}
	//           ${this.peraturanDropdown("aturan_sumber_dana", "Sumber Dana", disabled)}
	//           ${this.peraturanDropdown("aturan_sub_kegiatan", "Sub Kegiatan", disabled)}

	//       </div>

	//       <h4 class="ui dividing header section-header">
	//           Periode Dokumen
	//       </h4>

	//       <div class="ui stackable two column grid">

	//           ${this.rangeField("renja", "Renja", disabled)}
	//           ${this.rangeField("dpa", "DPA", disabled)}
	//           ${this.rangeField("rkpd", "RKPD", disabled)}
	//           ${this.rangeField("rka", "RKA", disabled)}
	//           ${this.rangeField("renja_p", "Renja Perubahan", disabled)}
	//           ${this.rangeField("dppa", "DPPA", disabled)}
	//           ${this.rangeField("kua_ppas", "KUA PPAS", disabled)}
	//           ${this.rangeField("rapbd", "RAPBD", disabled)}
	//           ${this.rangeField("rak", "RAK", disabled)}
	//           ${this.rangeField("rkpd_perubahan", "RKPD Perubahan", disabled)}
	//           ${this.rangeField("kua_ppas_perubahan", "KUA PPAS Perubahan", disabled)}
	//           ${this.rangeField("apbd_perubahan", "APBD Perubahan", disabled)}
	//           ${this.rangeField("renstra", "Renstra", disabled)}

	//       </div>

	//       <h4 class="ui dividing header section-header">
	//           Kontrol Sistem
	//       </h4>

	//       <div class="ui four stackable cards">

	//           ${this.controlCard("Global", "kunci", "setujui", disabled)}
	//           ${this.controlCard("Renstra", "kunci_renstra", "setujui_renstra", disabled)}
	//           ${this.controlCard("Renja", "kunci_renja", "setujui_renja", disabled)}
	//           ${this.controlCard("DPA", "kunci_dpa", "setujui_dpa", disabled)}
	//           ${this.controlCard("Renja P", "kunci_renja_p", "setujui_renja_p", disabled)}
	//           ${this.controlCard("DPPA", "kunci_dppa", "setujui_dppa", disabled)}
	//           ${this.controlCard("Paket", "kunci_paket", "setujui_paket", disabled)}
	//           ${this.controlCard("Realisasi", "kunci_realisasi", "setujui_realisasi", disabled)}

	//       </div>

	//       <h4 class="ui dividing header section-header">
	//           Keterangan
	//       </h4>

	//       <div class="field">
	//           <textarea name="keterangan" rows="3" ${disabled}></textarea>
	//       </div>

	//       <div class="ui divider"></div>

	//       ${
	// 				this.canEdit()
	// 					? `<div class="right aligned field">
	//                   <button class="ui primary button">
	//                       <i class="save icon"></i> Simpan Perubahan
	//              </button></div>`
	// 					: `<div class="ui grey disabled button">Read Only</div>`
	// 			}
	//       `;
	// }

	// ==================================================
	// DROPDOWN PERATURAN
	// ==================================================
	peraturanDropdown(name, label, disabled) {
		return `
        <div class="eight wide column">
            <div class="field">
                <label>${label}</label>
                <div class="ui fluid search selection dropdown peraturan-dropdown ${disabled}" data-name="${name}">
                    <input type="hidden" name="${name}">
                    <i class="dropdown icon"></i>
                    <div class="default text">Pilih ${label}</div>
                    <div class="menu"></div>
                </div>
            </div>
        </div>`;
	}

	loadPeraturanDropdown(callback) {
		this.ajax.request({
			data: { action: "dropdown", tbl: "peraturan" },
			success: (res) => {
				if (!res.success) return;

				$(".peraturan-dropdown").each(function () {
					const menu = $(this).find(".menu");
					menu.empty();

					res.data.forEach((row) => {
						menu.append(`
                        <div class="item" data-value="${row.id}">
                            ${row.uraian}
                        </div>
                    `);
					});

					$(this).dropdown();
				});

				if (callback) callback(); // 🔥 penting
			},
		});
	}

	// ==================================================
	// RANGE FIELD
	// ==================================================
	rangeField(name, label, disabled) {
		return `
        <div class="eight wide column">
            <div class="field">
                <label>${label}</label>
                <div class="two fields">
                    <div class="field">
                        <div class="ui calendar start_${name}">
                            <div class="ui input left icon">
                                <i class="calendar icon"></i>
                                <input type="text" name="awal_${name}" ${disabled}>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <div class="ui calendar end_${name}">
                            <div class="ui input left icon">
                                <i class="calendar icon"></i>
                                <input type="text" name="akhir_${name}" ${disabled}>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
	}

	controlCard(title, kunci, setujui, disabled) {
		return `
        <div class="card">
            <div class="content">
                <div class="header">${title}</div>
            </div>
            <div class="content">
                <div class="ui toggle checkbox">
                    <input type="checkbox" name="${kunci}" ${disabled}>
                    <label>Kunci</label>
                </div><br>
                <div class="ui toggle checkbox">
                    <input type="checkbox" name="${setujui}" ${disabled}>
                    <label>Setujui</label>
                </div>
            </div>
        </div>`;
	}

	// ==================================================
	// INIT UI
	// ==================================================
	initUI(callback) {
		$(".ui.checkbox").checkbox();

		this.loadPeraturanDropdown(() => {
			if (callback) callback();
		});

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
	// LOAD DATA (SINGLE ROW)
	// ==================================================
	loadData(callback) {
		this.ajax.request({
			url: "/pengaturan/current",
			method: "GET",
			success: (res) => {
				if (res.success && res.data) {
					this.data = res.data;
				}
				if (callback) callback();
			},
		});
	}

	populate() {
		if (!this.data) return;

		const form = $("#form-pengaturan");

		Object.keys(this.data).forEach((key) => {
			const field = form.find(`[name="${key}"]`);
			if (field.closest(".dropdown").length) {
				field.closest(".dropdown").dropdown("set selected", this.data[key]);
			} else if (field.attr("type") === "checkbox") {
				field.prop("checked", this.data[key] == 1);
			} else {
				field.val(this.data[key]);
			}
		});
		this.renderScheduleStatus();
	}

	renderScheduleStatus() {
		const stages = [["rkpd","RKPD"],["renja","Renja"],["rka","RKA"],["dpa","DPA"],["rkpd_perubahan","RKPD Perubahan"],["renja_p","Renja Perubahan"],["dppa","DPPA"]];
		const now = Date.now();
		const html = stages.map(([key,label]) => {
			const start = this.data[`awal_${key}`], end = this.data[`akhir_${key}`];
			const lockedKey = ["rka"].includes(key) ? "kunci_renja" : key === "rkpd_perubahan" ? "kunci_renja_p" : `kunci_${key}`;
			const locked = Number(this.data[lockedKey] || this.data.kunci || 0) === 1;
			const active = start && end && now >= new Date(start).getTime() && now <= new Date(end).getTime() && !locked;
			const color = active ? "green" : locked ? "red" : "orange";
			const status = active ? "Dibuka" : locked ? "Dikunci" : (!start || !end ? "Belum diatur" : "Di luar jadwal");
			return `<div class="${color} card"><div class="content"><div class="right floated ui ${color} label">${status}</div><div class="header">${label}</div><div class="meta">${start || "-"}</div><div class="description">s.d. ${end || "-"}</div></div></div>`;
		}).join("");
		$("#schedule-status-cards").html(html);
	}

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
					id_row: this.data.id,
					mode: "update",
					...formData,
				},
				success: (res) => {
					if (res.success) {
						Toast.show({
							success: true,
							message: "Pengaturan berhasil disimpan",
						});
					}
				},
			});
		});
	}

	destroy() {
		$(this.container).empty();
	}
}
if (typeof PeriodeRPJMDModule === "undefined") {
	class PeriodeRPJMDModule extends BaseCrudModule {
		constructor() {
			super({
				moduleName: "periode_rpjmd",
				container: "#periode-rpjmd-container",
				useMenu: false,
			});
		}

		init() {
			this.renderLayout();
			this.loadTable("periode_rpjmd");

			const canEdit = window.app.user.type_user === "super_admin";
			this.formEngine?.applyReadonly(!canEdit);
		}
	}

	window.PeriodeRPJMDModule = PeriodeRPJMDModule;
}
