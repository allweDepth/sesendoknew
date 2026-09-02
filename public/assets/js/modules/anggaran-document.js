class AnggaranDocumentModule extends BaseCrudModule {
	constructor() {
		const paths = {
			"/rkpd": "rkpd",
			"/renja": "renja",
			"/rka": "rka",
			"/dpa": "dpa",
			"/rkpd_perubahan": "rkpd_p",
			"/renja_perubahan": "renja_p",
			"/rka_perubahan": "rka_p",
			"/dppa": "dppa",
		};
		const path = Object.keys(paths).find((p) => window.location.pathname.endsWith(p));
		const table = $("#anggaranDocument").data("table") || paths[path] || "rkpd";
		super({ moduleName: "anggaran", menuItems: [], useMenu: false });
		this.table = table;
		this.next = { rkpd: "renja", renja: "rka", rka: "dpa", dpa: "dppa" };
		this.changes = { rkpd: "rkpd_p", renja: "renja_p", rka: "rka_p" };
	}
	init() {
		this.state.setTable(this.table);
		this.state.req = null;
		this.injectStyles();
		this.renderBudgetShell();
		this.loadGroups();
		this.bindPhase3Actions();
		this.bindAuxActions();
		this.bindBudgetForm();
		$(document)
			.off(`form:success.${this.table}.budget`)
			.on(`form:success.${this.table}.budget`, () => this.loadGroups());
	}
	renderBudgetShell() {
		const label = this.formatTitle(this.table.replace("_p", " Perubahan")),
			next = this.next[this.table],
			change = this.changes[this.table],
			official = ["dpa", "dppa"].includes(this.table);
		$(this.container).html(
			`<div class="ui container anggaran-hierarchy"><div class="anggaran-hero"><div><small>DOKUMEN PERENCANAAN & ANGGARAN</small><h2>${label} — Sub Kegiatan Belanja</h2><p>Daftar dimulai dari sub kegiatan. Buka aksi untuk melihat dan mengubah rincian belanja.</p></div><div class="ui compact buttons"><button class="ui primary button" data-ui="open-form" data-action="add" data-tbl="${this.table}"><i class="plus icon"></i> Tambah Data</button>${next ? `<button class="ui teal button" data-phase3-action="advance" data-from="${this.table}" data-to="${next}"><i class="arrow right icon"></i> Proses ${next.toUpperCase()}</button>` : ""}${change ? `<button class="ui orange button" data-phase3-action="advance" data-from="${this.table}" data-to="${change}"><i class="sync icon"></i> Perubahan</button>` : ""}${official ? `<button class="ui button" data-budget-action="recap-excel"><i class="file excel icon"></i> Rekap Excel</button><button class="ui button" data-budget-action="recap-pdf"><i class="file pdf icon"></i> Rekap PDF</button>` : `<button class="ui button" data-budget-action="excel"><i class="file excel icon"></i> Excel</button><button class="ui button" data-budget-action="pdf"><i class="file pdf icon"></i> PDF</button>`}</div></div>${official ? `<div class="ui tiny buttons budget-secondary-export"><button class="ui basic button" data-budget-action="excel"><i class="table icon"></i>Rincian per Sub Kegiatan (Excel)</button><button class="ui basic button" data-budget-action="pdf"><i class="file pdf outline icon"></i>Rincian per Sub Kegiatan (PDF)</button><button class="ui basic button" data-budget-action="tapd"><i class="users icon"></i>Tim Anggaran Daerah</button></div>` : ""}<div id="budgetSummary" class="ui four stackable statistics budget-summary"></div><div class="ui fluid icon input budget-search"><input placeholder="Cari kode atau nama sub kegiatan..."><i class="search icon"></i></div><div id="budgetGroupList"><div class="ui segment"><div class="ui active centered inline loader"></div></div></div></div><div class="ui modal" id="monthlyPlanModal"><i class="close icon"></i><div class="header">Rencana Penarikan Dana per Bulan</div><div class="content"><form class="ui form" id="monthlyPlanForm"><input type="hidden" name="id" required><div class="ui info message" id="monthlyPlanInfo"></div><div class="four fields monthly-fields"></div><div class="ui right labeled input"><input readonly id="monthlyPlanTotal"><div class="ui label">Total Rencana</div></div></form></div><div class="actions"><button class="ui deny button">Batal</button><button class="ui primary button" id="saveMonthlyPlan">Simpan Rencana</button></div></div><div class="ui modal" id="tapdModal"><i class="close icon"></i><div class="header">Tim Anggaran Pemerintah Daerah yang Berlaku</div><div class="content"><div id="tapdActiveList" class="ui relaxed divided list"></div><div class="ui message">Penugasan dipilih otomatis berdasarkan tanggal cetak dan masa berlaku awal-akhir.</div></div></div>`,
		);
	}
	loadGroups() {
		window.Ajax.request({
			url: `/anggaran/groups?tbl=${encodeURIComponent(this.table)}`,
			method: "GET",
			success: (r) => {
				if (r?.success) this.renderGroups(r.data || []);
			},
		});
	}
	renderGroups(rows) {
		const total = rows.reduce((n, r) => n + Number(r.total || 0), 0),
			locked = rows.filter((r) => Number(r.kunci)).length,
			approved = rows.filter((r) => Number(r.setujui)).length;
		$("#budgetSummary").html(
			`<div class="statistic"><div class="value">${rows.length}</div><div class="label">Sub Kegiatan</div></div><div class="statistic"><div class="value budget-money">${this.money(total)}</div><div class="label">Total Pagu</div></div><div class="statistic"><div class="value">${approved}</div><div class="label">Disetujui</div></div><div class="statistic"><div class="value">${locked}</div><div class="label">Dikunci</div></div>`,
		);
		if (!rows.length) {
			$("#budgetGroupList").html(
				'<div class="ui placeholder segment"><div class="ui icon header"><i class="inbox icon"></i>Belum ada sub kegiatan</div></div>',
			);
			return;
		}
		let lastProgram = "",
			lastKegiatan = "",
			body = "";
		rows.forEach((r) => {
			const program = r.program || "Program belum dipetakan",
				kegiatan = r.kegiatan || "Kegiatan belum dipetakan";
			if (program !== lastProgram) {
				body += `<tr class="budget-level program"><td colspan="7"><i class="folder open icon"></i>${this.escape(program)}</td></tr>`;
				lastProgram = program;
				lastKegiatan = "";
			}
			if (kegiatan !== lastKegiatan) {
				body += `<tr class="budget-level kegiatan"><td colspan="7"><i class="sitemap icon"></i>${this.escape(kegiatan)}</td></tr>`;
				lastKegiatan = kegiatan;
			}
			body += `<tr class="budget-group" data-search="${this.escape([r.kd_sub_keg, r.nama_sub_kegiatan, program, kegiatan].join(" "))}"><td><button class="ui violet icon button" title="Lihat rincian" data-budget-action="details" data-code="${this.escape(r.kd_sub_keg)}"><i class="list icon"></i></button></td><td><a data-budget-action="details" data-code="${this.escape(r.kd_sub_keg)}"><b>${this.escape(r.kd_sub_keg)}</b> ${this.escape(r.nama_sub_kegiatan || "Sub Kegiatan")}</a></td><td class="center aligned"><span class="ui tiny label">${r.jumlah_uraian}</span></td><td class="center aligned ${Number(r.kunci) ? "negative" : ""}">${Number(r.kunci) ? "DIKUNCI" : "Terbuka"}</td><td class="center aligned ${Number(r.setujui) ? "positive" : ""}">${Number(r.setujui) ? "DISETUJUI" : "Draft"}</td><td class="right aligned"><b>${this.money(r.total)}</b></td><td class="right aligned">0%</td></tr>`;
		});
		$("#budgetGroupList").html(
			`<div class="ui segment table-wrapper"><table class="ui very basic striped table budget-tree-table"><thead><tr><th>Aksi</th><th>Uraian</th><th>Rincian</th><th>Status Sub Kegiatan</th><th>Status Rincian</th><th class="right aligned">Pagu</th><th>Realisasi</th></tr></thead><tbody>${body}</tbody></table></div>`,
		);
	}
	loadDetails(code) {
		window.Ajax.request({
			url: `/anggaran/details?tbl=${encodeURIComponent(this.table)}&kd_sub_keg=${encodeURIComponent(code)}`,
			method: "GET",
			success: (r) => {
				if (r?.success) {
					this.renderDetails(code, r.data || []);
					if (["dpa", "dppa"].includes(this.table))
						$("#budgetGroupList>.ui.segment:first").append(
							`<a class="ui right floated teal button" data-spa="server" href="${window.appUrl ? window.appUrl("/anggaran/rencana-rekening") : "/anggaran/rencana-rekening"}?tbl=${this.table}&kd_sub_keg=${encodeURIComponent(code)}"><i class="calendar alternate icon"></i>Rencana per Rekening</a>`,
						);
				}
			},
		});
	}
	renderDetails(code, rows) {
		const changed = rows.some((r) => r.jumlah_awal !== undefined && r.jumlah_awal !== null),
			canPlan = ["dpa", "dppa"].includes(this.table);
		let lastGroup = "",
			cells = "";
		rows.forEach((r) => {
			const group = [r.kel_rek, r.jenis_kelompok, r.kelompok].filter(Boolean).join(" — ");
			if (group && group !== lastGroup) {
				cells += `<tr class="budget-level account"><td colspan="${changed ? 9 : 6}"><i class="folder icon"></i>${this.escape(group)}</td></tr>`;
				lastGroup = group;
			}
			cells += `<tr data-id="${r.id}"><td>${this.escape(r.kd_akun || "-")}</td><td><b>${this.escape(r.komponen || r.uraian)}</b>${r.spesifikasi ? `<div class="ui tiny grey text">${this.escape(r.spesifikasi)}</div>` : ""}</td>${changed ? `<td class="right aligned">${this.escape(r.volume_awal || 0)}</td><td class="right aligned">${this.money(r.harga_satuan_awal || 0)}</td><td class="right aligned">${this.money(r.jumlah_awal || 0)}</td>` : ""}<td class="right aligned">${this.escape(r.volume || 0)}</td><td class="right aligned">${this.money(r.harga_satuan || 0)}</td><td class="right aligned"><b>${this.money(r.jumlah || 0)}</b></td><td><div class="ui mini basic icon buttons">${canPlan ? `<button class="ui teal button" title="Rencana bulanan" data-budget-action="monthly" data-id="${r.id}"><i class="calendar alternate icon"></i></button>` : ""}<button class="ui button" title="Edit rincian" data-ui="open-form" data-action="edit" data-tbl="${this.table}" data-id="${r.id}"><i class="blue edit icon"></i></button><button class="ui red button" title="Hapus rincian" data-budget-action="delete" data-id="${r.id}"><i class="trash icon"></i></button></div></td></tr>`;
		});
		const before = changed ? '<th colspan="3" class="center aligned">Sebelum Perubahan</th>' : "",
			cols = changed
				? '<colgroup><col style="width:13%"><col style="width:22%"><col style="width:8%"><col style="width:10%"><col style="width:12%"><col style="width:8%"><col style="width:10%"><col style="width:12%"><col style="width:5%"></colgroup>'
				: '<colgroup><col style="width:16%"><col style="width:36%"><col style="width:12%"><col style="width:14%"><col style="width:16%"><col style="width:6%"></colgroup>';
		$("#budgetGroupList").html(
			`<div class="ui segment"><button class="ui basic button" data-budget-action="back"><i class="arrow left icon"></i> Daftar Sub Kegiatan</button><span class="ui blue label">${this.escape(code)}</span><button class="ui right floated primary button" data-ui="open-form" data-action="add" data-tbl="${this.table}" data-budget-code="${this.escape(code)}"><i class="plus icon"></i> Tambah Rincian Belanja</button></div><div class="ui segment table-wrapper"><table class="ui celled striped fixed table budget-detail-table">${cols}<thead>${changed ? `<tr><th colspan="2"></th>${before}<th colspan="3" class="center aligned">Sesudah Perubahan</th><th></th></tr>` : ""}<tr><th>Kode Akun</th><th>Uraian/Komponen</th>${changed ? "<th>Volume</th><th>Harga</th><th>Jumlah</th>" : ""}<th>Volume/Target</th><th>Harga Satuan</th><th>Jumlah/Pagu</th><th>Aksi</th></tr></thead><tbody>${cells || `<tr><td colspan="${changed ? 9 : 6}" class="center aligned">Belum ada rincian</td></tr>`}</tbody></table></div>`,
		);
	}
	bindPhase3Actions() {
		this.bindExport();
		$(document)
			.off("click.budgetHierarchy", "[data-budget-action]")
			.on("click.budgetHierarchy", "[data-budget-action]", (e) => {
				const b = $(e.currentTarget),
					a = b.data("budget-action");
				if (a === "details") this.loadDetails(String(b.data("code")));
				else if (a === "back") this.loadGroups();
				else if (a === "pdf") this.download(`/anggaran/export_pdf?tbl=${this.table}`);
				else if (a === "delete") {
					if (!window.confirm("Hapus uraian ini? Data yang telah berkontrak akan ditolak sistem.")) return;
					window.Ajax.request({
						method: "POST",
						data: { action: "delete", tbl: this.table, id_row: b.data("id") },
						success: (r) => {
							if (r?.success) this.loadGroups();
						},
					});
				}
			});
		$(document)
			.off("input.budgetSearch", ".budget-search input")
			.on("input.budgetSearch", ".budget-search input", (e) => {
				const q = String(e.target.value).toLowerCase();
				$(".budget-group").each(function () {
					$(this).toggle(String($(this).data("search")).toLowerCase().includes(q));
				});
			});
		$(document)
			.off("click.budgetFlow", "[data-phase3-action]")
			.on("click.budgetFlow", "[data-phase3-action]", (e) => {
				const b = $(e.currentTarget);
				window.Ajax.request({
					url: "/anggaran/advance",
					method: "POST",
					data: { from: b.data("from"), to: b.data("to"), tahun: window.app?.user?.tahun || new Date().getFullYear() },
					success: (r) => {
						if (r?.success) this.loadGroups();
					},
				});
			});
	}
	bindBudgetForm() {
		const forms = "#form_flyout, #form_modal",
			standardType = '#form_flyout input[name="jenis_standar_harga"], #form_modal input[name="jenis_standar_harga"]',
			standardId = '#form_flyout input[name="id_standar_harga"], #form_modal input[name="id_standar_harga"]',
			calculation =
				'#form_flyout input[name^="vol_"], #form_modal input[name^="vol_"], #form_flyout input[name="harga_satuan"], #form_modal input[name="harga_satuan"]';
		$(document)
			.off(".budgetForm")
			.on("change.budgetForm", standardType, (e) => {
				const type = String($(e.target).val() || "ssh").toLowerCase(),
					form = $(e.target).closest("form"),
					dropdown = form.find('input[name="id_standar_harga"]').closest(".ui.dropdown"),
					engine = window.Flyout?.formEngine;
				if (!dropdown.length) return;
				dropdown.attr("data-source", type).data("source", type);
				if (engine?.isPopulating) return;
				dropdown.dropdown("clear");
				dropdown.find(".menu").empty();
				engine?.loadDropdown(dropdown, {});
			})
			.on("change.budgetForm", standardId, (e) => {
				const form = $(e.target).closest("form"),
					item = $(e.target).closest(".ui.dropdown").find(".item.active.selected");
				form.find('input[name="komponen"]').val($.trim(item.text()));
			})
			.on("input.budgetForm", calculation, (e) => {
				const form = $(e.target).closest("form").length
					? $(e.target).closest("form")
					: $(forms).filter(":visible").first();
				if (!form.length) return;
				let factors = [];
				for (let i = 1; i <= 4; i++) {
					const value = Number(form.find(`[name="vol_${i}"]`).val() || 0),
						unit = form.find(`[name="sat_${i}"]`).val() || "";
					if (value > 0) factors.push({ value, unit });
				}
				const volume = factors.length ? factors.reduce((total, f) => total * f.value, 1) : 0,
					price = Number(form.find('[name="harga_satuan"]').val() || 0);
				form.find('[name="volume"]').val(volume);
				form.find('[name="jumlah"]').val(volume * price);
				form.find('[name="koefisien_keterangan"]').val(factors.map((f) => `${f.value} ${f.unit}`.trim()).join(" x "));
			});
	}
	bindAuxActions() {
		const names = [
			"Januari",
			"Februari",
			"Maret",
			"April",
			"Mei",
			"Juni",
			"Juli",
			"Agustus",
			"September",
			"Oktober",
			"November",
			"Desember",
		];
		$(document)
			.off("click.budgetAux")
			.on("click.budgetAux", '[data-budget-action="excel"]', () =>
				this.download(`/anggaran/export_excel?tbl=${this.table}`),
			)
			.on("click.budgetAux", '[data-budget-action="recap-excel"]', () =>
				this.download(`/anggaran/export_rekap_excel?tbl=${this.table}`),
			)
			.on("click.budgetAux", '[data-budget-action="recap-pdf"]', () =>
				this.download(`/anggaran/export_rekap_pdf?tbl=${this.table}`),
			)
			.on("click.budgetAux", '[data-budget-action="monthly"]', (e) => {
				const id = Number($(e.currentTarget).data("id"));
				window.Ajax.request({
					url: `/anggaran/rencana_bulanan?tbl=${this.table}&id=${id}`,
					method: "GET",
					success: (r) => {
						const d = r.data || {},
							months = d.months || {};
						$("#monthlyPlanForm [name=id]").val(id);
						$("#monthlyPlanInfo").html(
							`<b>${this.escape(d.item?.kd_sub_keg || "")}</b> - ${this.escape(d.item?.kd_akun || "")}<br>Pagu: ${this.money(d.item?.jumlah || 0)}`,
						);
						$("#monthlyPlanModal .monthly-fields").html(
							names
								.map(
									(n, i) =>
										`<div class="field"><label>${n}</label><input type="number" min="0" step="0.01" name="month_${i + 1}" required value="${Number(months[i + 1] || 0)}"></div>`,
								)
								.join(""),
						);
						this.sumMonthly();
						$("#monthlyPlanModal").modal("show");
					},
				});
			})
			.on("input.budgetAux", "#monthlyPlanForm input[type=number]", () => this.sumMonthly())
			.on("click.budgetAux", "#saveMonthlyPlan", () => {
				const form = $("#monthlyPlanForm");
				if (window.FormValidation && !FormValidation.validate(form)) return;
				const months = {};
				for (let i = 1; i <= 12; i++) months[i] = Number(form.find(`[name=month_${i}]`).val() || 0);
				window.Ajax.request({
					url: "/anggaran/rencana_bulanan",
					method: "POST",
					data: { tbl: this.table, id: form.find("[name=id]").val(), months: JSON.stringify(months) },
					success: () => $("#monthlyPlanModal").modal("hide"),
				});
			})
			.on("click.budgetAux", '[data-budget-action="tapd"]', () => {
				window.Ajax.request({
					url: "/anggaran/tapd",
					method: "GET",
					success: (r) => {
						$("#tapdActiveList").html(
							(r.data || [])
								.map(
									(p, i) =>
										`<div class="item"><i class="user circle icon"></i><div class="content"><div class="header">${i + 1}. ${this.escape(p.nama)}</div><div class="description">NIP ${this.escape(p.nip || "-")} - ${this.escape(p.jabatan)}</div></div></div>`,
								)
								.join("") || '<div class="ui message">Belum ada penugasan TAPD aktif.</div>',
						);
						$("#tapdModal").modal("show");
					},
				});
			});
	}
	sumMonthly() {
		let total = 0;
		$("#monthlyPlanForm input[type=number]").each(function () {
			total += Number($(this).val() || 0);
		});
		$("#monthlyPlanTotal").val(this.money(total));
	}
	addTapdAdminForm() {
		if (!["super_admin", "admin_wilayah", "tapd"].includes(window.app?.user?.type_user || "")) return;
		$("#tapdModal .content").append(
			'<div class="ui divider"></div><form class="ui small form" id="tapdAssignmentForm"><h4 class="ui header">Tambah Penugasan TAPD</h4><div class="three fields"><div class="field"><label>Nama</label><input name="nama" required></div><div class="field"><label>NIP</label><input name="nip"></div><div class="field"><label>Jabatan</label><input name="jabatan" required></div></div><div class="three fields"><div class="field"><label>Berlaku mulai</label><input type="date" name="tanggal_mulai" required></div><div class="field"><label>Sampai</label><input type="date" name="tanggal_selesai" required></div><div class="field"><label>&nbsp;</label><button class="ui primary button">Simpan Penugasan</button></div></div></form>',
		);
		$(document)
			.off("submit.tapd", "#tapdAssignmentForm")
			.on("submit.tapd", "#tapdAssignmentForm", (e) => {
				e.preventDefault();
				window.Ajax.request({
					url: "/anggaran/tapd/save",
					method: "POST",
					data: $(e.currentTarget).serialize(),
					success: () => {
						$("#tapdModal").modal("hide");
						e.currentTarget.reset();
					},
				});
			});
	}
	download(p) {
		window.location.href = window.appUrl ? window.appUrl(p) : p;
	}
	escape(v) {
		return $("<div>")
			.text(v ?? "")
			.html();
	}
	money(v) {
		return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(
			Number(v || 0),
		);
	}
	injectStyles() {
		if (document.getElementById("budgetHierarchyStyle")) return;
		$("head").append(
			`<style id="budgetHierarchyStyle">.anggaran-hero{display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#19324d,#346fa7);color:#fff;padding:22px;border-radius:14px;margin-bottom:15px}.anggaran-hero h2{margin:3px 0}.budget-summary{background:#fff;padding:14px;border-radius:10px;margin-bottom:14px!important;box-shadow:0 2px 12px rgba(34,36,38,.08)}.budget-summary .budget-money{font-size:1.45rem!important}.budget-search{margin-bottom:14px}.budget-tree-table .budget-level td{font-weight:700}.budget-tree-table .program td{background:#d8ecfb!important;color:#19324d}.budget-tree-table .kegiatan td{background:#eef7fc!important;padding-left:28px!important}.budget-tree-table .account td{background:#f7f7f7!important}.budget-tree-table a{cursor:pointer}.table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch}.budget-detail-table{min-width:1100px!important}[data-budget-action="monthly"]{display:none!important}@media(max-width:800px){.anggaran-hero{display:block}.anggaran-hero .buttons{margin-top:12px}.budget-summary .statistic{min-width:45%!important;margin-bottom:12px!important}}</style>`,
		);
	}
}
