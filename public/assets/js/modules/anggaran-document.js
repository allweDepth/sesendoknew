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
		this.canManagePriorities = document.getElementById("anggaranDocument")?.dataset.managePriorities === "1";
		this.workspaceEnabled = ["renja","rka","dpa","renja_p","rka_p","dppa"].includes(table);
		this.collapsedGroups = new Set();
		this.next = { renja: "rka", rka: "dpa", dpa: "renja_p", renja_p: "rka_p", rka_p: "dppa" };
		this.changes = { rkpd: "rkpd_p", renja: "renja_p", rka: "rka_p" };
	}
	init() {
		window.activeBudgetDocument?.request?.abort();
		window.activeBudgetDocument?.closeWorkspace?.();
		window.activeBudgetDocument = this;
		this.state.setTable(this.table);
		this.state.req = null;
		this.injectStyles();
		this.renderBudgetShell();
		this.root = document.querySelector(".anggaran-hierarchy");
		this.route = window.location.pathname;
		$(document).off(".budgetWorkspaceToolbar");
		if (this.workspaceEnabled) this.initWorkspace();
		this.loadGroups();
		this.bindPhase3Actions();
		this.bindAuxActions();
		this.bindBudgetForm();
			$(document)
			.off("form:success.budgetDocument")
			.on(`form:success.${this.table}.table.budgetDocument`, () => { if (this.isActive()) this.loadGroups(); });
	}
	renderBudgetShell() {
		const label = this.formatTitle(this.table.replace("_p", " Perubahan")),
			isRkpd = ["rkpd", "rkpd_p"].includes(this.table),
			next = this.next[this.table],
			change = this.changes[this.table],
			official = !isRkpd,
			role = window.app?.user?.type_user || "viewer",
			canWrite = !["tapd", "viewer"].includes(role),
			canAdvance = ["super_admin", "admin_wilayah", "admin_opd", "kepala_opd", "pa_kpa"].includes(role);
		this.canWrite = canWrite;
		$(this.container).html(
			`<div class="ui container anggaran-hierarchy"><div class="anggaran-hero"><div><small>DOKUMEN PERENCANAAN & ANGGARAN</small><h2>${label} — ${isRkpd ? "Target dan Pagu Sub Kegiatan" : "Rincian Belanja Sub Kegiatan"}</h2><p>${isRkpd ? "RKPD merangkum prioritas, indikator, target, lokasi, kelompok sasaran, dan pagu usulan OPD. Rincian akun disusun pada Renja/RKA hingga DPA." : (canWrite ? "Daftar dimulai dari sub kegiatan. Buka menu pada baris untuk melihat dan mengubah rincian belanja." : "Mode baca regional. Pilih OPD pada bagian atas untuk meninjau dokumen.")}</p></div><div class="ui compact buttons">${canWrite ? `<button class="ui primary button" data-ui="open-form" data-action="add" data-tbl="${this.table}"><i class="plus icon"></i> Tambah Data</button>` : ""}${canAdvance && next ? `<button class="ui teal button" data-phase3-action="advance" data-from="${this.table}" data-to="${next}"><i class="arrow right icon"></i> Proses ${next.toUpperCase()}</button>` : ""}${canAdvance && change ? `<button class="ui orange button" data-phase3-action="advance" data-from="${this.table}" data-to="${change}"><i class="sync icon"></i> Perubahan</button>` : ""}${official ? `<button class="ui button" data-budget-action="recap-excel"><i class="file excel icon"></i> Rekap Excel</button><button class="ui button" data-budget-action="recap-pdf"><i class="file pdf icon"></i> Rekap PDF</button>` : `<button class="ui button" data-budget-action="excel"><i class="file excel icon"></i> Excel</button><button class="ui button" data-budget-action="pdf"><i class="file pdf icon"></i> PDF</button>`}</div></div>${official ? `<div class="ui tiny buttons budget-secondary-export"><button class="ui basic button" data-budget-action="excel"><i class="table icon"></i>${this.table === "rka" ? "RKA Lengkap (Excel)" : "Rincian per Sub Kegiatan (Excel)"}</button><button class="ui basic button" data-budget-action="pdf"><i class="file pdf outline icon"></i>Rincian per Sub Kegiatan (PDF)</button><button class="ui basic button" data-budget-action="tapd"><i class="users icon"></i>Tim Anggaran Daerah</button></div>` : ""}<div id="budgetSummary" class="ui four stackable statistics budget-summary"></div><div id="budgetGroupList"><div class="ui segment"><div class="ui active centered inline loader"></div></div></div></div><div class="ui modal" id="monthlyPlanModal"><i class="close icon"></i><div class="header">Rencana Penarikan Dana per Bulan</div><div class="content"><form class="ui form" id="monthlyPlanForm"><input type="hidden" name="id" required><div class="ui info message" id="monthlyPlanInfo"></div><div class="four fields monthly-fields"></div><div class="ui right labeled input"><input readonly id="monthlyPlanTotal"><div class="ui label">Total Rencana</div></div></form></div><div class="actions"><button class="ui deny button">Batal</button>${canWrite ? '<button class="ui primary button" id="saveMonthlyPlan">Simpan Rencana</button>' : ""}</div></div><div class="ui modal" id="tapdModal"><i class="close icon"></i><div class="header">Tim Anggaran Pemerintah Daerah yang Berlaku</div><div class="content"><div id="tapdActiveList" class="ui relaxed divided list"></div><div class="ui message">Penugasan dipilih otomatis berdasarkan tanggal cetak dan masa berlaku awal-akhir.</div></div></div><div class="ui small modal" id="tapdApprovalModal"><i class="close icon"></i><div class="header">Persetujuan TAPD per Sub Kegiatan</div><div class="content"><input type="hidden" id="approvalSubCode"><div class="ui info message" id="approvalFlowInfo"></div><div class="ui fluid vertical labeled icon buttons"><button class="ui green button" data-approval-scenario="KUNCI"><i class="lock icon"></i>Setujui dan kunci saja</button><button class="ui teal button" data-approval-scenario="KUNCI_SALIN"><i class="copy icon"></i>Setujui, kunci, dan salin/sinkronkan ke dokumen berikutnya</button><button class="ui orange button" data-approval-scenario="KUNCI_GANTI_TUJUAN"><i class="exchange icon"></i>Setujui, kunci, hapus isi lama tujuan, lalu salin ulang</button></div><div class="ui warning message"><b>Ganti tujuan</b> hanya dapat dilakukan bila sub kegiatan pada dokumen tujuan belum dikunci.</div></div><div class="actions"><button class="ui deny button">Batal</button></div></div>`,
		);
		if(official) $('.budget-secondary-export [data-budget-action="tapd"]').before('<button class="ui basic button" data-budget-action="same-price-excel"><i class="clone outline icon"></i>Rekap Harga Sama (Excel)</button>');
		if (official && this.canManagePriorities) {
			$(".budget-secondary-export").append('<button class="ui basic button" data-budget-action="priorities"><i class="list icon" aria-hidden="true"></i>Prioritas Nasional</button>');
		}
	}
	isActive() {
		return window.activeBudgetDocument === this && this.root?.isConnected && window.location.pathname === this.route;
	}
	loadDocument(url, render) {
		this.request?.abort();
		const version = this.requestVersion = (this.requestVersion || 0) + 1;
		const active = () => this.isActive() && this.requestVersion === version;
		const failed = () => {
			if (active()) $("#budgetGroupList").html('<div class="ui warning message"><p>Data belum berhasil dimuat. Silakan coba kembali.</p><button class="ui small basic button" data-budget-action="back">Muat ulang daftar</button></div>');
		};
		this.request = window.Ajax.request({
			url, method: "GET", silent: true,
			success: (response) => {
				if (!active()) return;
				if (!response?.success || !Array.isArray(response.data)) { failed(); return; }
				try { render(response.data, response.meta || {}); }
				catch (error) { console.error("Gagal menampilkan dokumen anggaran", error); failed(); }
			},
			error: failed,
		});
	}
	loadGroups() {
		const workspace = this.workspaceEnabled ? "&workspace=1" : "";
		this.loadDocument(`/anggaran/groups?tbl=${encodeURIComponent(this.table)}${workspace}`, (rows,meta) => this.renderGroups(rows,meta));
	}
	renderGroups(rows, meta = {}) {
		const total = rows.reduce((n, r) => n + Number(r.total || 0), 0),
			locked = rows.filter((r) => Number(r.kunci)).length,
			approved = rows.filter((r) => Number(r.setujui)).length;
		if (this.workspaceEnabled) {
			const planned = rows.reduce((sum,row) => sum + Number(row.pagu ?? row.total ?? 0),0);
			const validated = rows.reduce((sum,row) => sum + Number(row.pagu_disetujui || 0),0);
			const cards = [["Batas Pagu",meta.pagu_maksimal ?? null],["Pagu Sub Kegiatan",planned],
				["Pagu Disetujui",validated],["Total Rincian",total]];
			$("#budgetSummary").html(cards.map(([label,value])=>`<div class="statistic"><div class="value budget-money">${value===null?"—":this.money(value)}</div><div class="label">${label}</div></div>`).join(""));
		} else $("#budgetSummary").html(
			`<div class="statistic"><div class="value">${rows.length}</div><div class="label">Sub Kegiatan</div></div><div class="statistic"><div class="value budget-money">${this.money(total)}</div><div class="label">${this.workspaceEnabled ? "Total Rincian" : "Total Pagu"}</div></div><div class="statistic"><div class="value">${approved}</div><div class="label">Disetujui</div></div><div class="statistic"><div class="value">${locked}</div><div class="label">Dikunci</div></div>`,
		);
		if (!rows.length) {
			$("#budgetGroupList").html(
				'<div class="ui placeholder segment"><div class="ui icon header"><i class="inbox icon"></i>Belum ada sub kegiatan</div></div>',
			);
			return;
		}
		if (this.workspaceEnabled) return this.renderWorkspaceGroups(rows);
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
			const approvalRole=window.app?.user?.type_user || "", canApprove = ["rkpd","rkpd_p"].includes(this.table)?["super_admin","admin_wilayah","tapd"].includes(approvalRole):["super_admin", "admin_wilayah", "tapd", "kepala_opd", "pa_kpa"].includes(approvalRole);
			body += `<tr class="budget-group" data-search="${this.escape([r.kd_sub_keg, r.nama_sub_kegiatan, program, kegiatan].join(" "))}"><td><button class="ui violet icon button" title="Lihat rincian" data-budget-action="details" data-code="${this.escape(r.kd_sub_keg)}"><i class="list icon"></i></button>${canApprove ? `<button class="ui ${Number(r.setujui) ? "orange" : "green"} icon button" title="${Number(r.setujui) ? "Buka persetujuan" : "Setujui dan kunci"}" data-budget-action="approval" data-code="${this.escape(r.kd_sub_keg)}" data-approved="${Number(r.setujui) ? 0 : 1}"><i class="${Number(r.setujui) ? "unlock" : "check"} icon"></i></button>` : ""}</td><td><a data-budget-action="details" data-code="${this.escape(r.kd_sub_keg)}"><b>${this.escape(r.kd_sub_keg)}</b> ${this.escape(r.nama_sub_kegiatan || "Sub Kegiatan")}</a></td><td class="center aligned"><span class="ui tiny label">${r.jumlah_uraian}</span></td><td class="center aligned ${Number(r.kunci) ? "negative" : ""}">${Number(r.kunci) ? "DIKUNCI" : "Terbuka"}</td><td class="center aligned ${Number(r.setujui) ? "positive" : ""}">${Number(r.setujui) ? "DISETUJUI" : "Draft"}</td><td class="right aligned budget-pagu-column"><b>${this.money(r.total)}</b></td><td class="right aligned">0%</td></tr>`;
		});
		$("#budgetGroupList").html(
			`<div class="ui segment table-wrapper"><table class="ui very basic striped table budget-tree-table"><thead><tr><th>Aksi</th><th>Uraian</th><th>Rincian</th><th>Status Sub Kegiatan</th><th>Status Rincian</th><th class="right aligned budget-pagu-column">Pagu</th><th>Realisasi</th></tr></thead><tbody>${body}</tbody></table></div>`,
		);
	}
	initWorkspace() {
		this.root.classList.add("budget-workspace-page");
		this.root.addEventListener("beforetoggle", (event) => {
			const menu = event.target;
			if (event.newState !== "open" || !menu.matches(".budget-action-popover")) return;
			const anchor = menu.previousElementSibling.getBoundingClientRect();
			const height = menu.children.length * 38 + 12;
			const width = Math.min(217, window.innerWidth - 16);
			const spaceBelow = window.innerHeight - anchor.bottom;
			const above = spaceBelow < height + 8 && anchor.top > spaceBelow;
			menu.style.left = `${Math.max(8, Math.min(anchor.left, window.innerWidth - width - 8))}px`;
			menu.style.top = `${above ? anchor.top - 4 : anchor.bottom + 4}px`;
			menu.style.transform = above ? "translateY(-100%)" : "";
			menu.style.maxHeight = `${Math.max(48, (above ? anchor.top : spaceBelow) - 12)}px`;
		}, true);
		this.workspacePage = 1;
		const resetPage = () => {
			if (!this.isActive()) return;
			this.workspacePage = 1;
			this.refreshWorkspaceTree();
		};
		$(document)
			.on("change.budgetWorkspaceToolbar", '#countRow input[name="countRow"]', resetPage)
			.on("input.budgetWorkspaceToolbar", "#cari_data", resetPage);
		this.root.querySelector("#budgetGroupList").insertAdjacentHTML("beforebegin", `
			<div class="ui compact basic buttons budget-tree-controls">
				<button class="ui button" data-budget-action="expand-all">Tampilkan Semua</button>
				<button class="ui button" data-budget-action="collapse-all">Sembunyikan Semua</button>
			</div>`);
		this.root.insertAdjacentHTML("beforeend", `
			<form class="ui large modal form budget-workspace-dialog" role="dialog" aria-modal="true" aria-labelledby="budgetWorkspaceTitle">
				<div class="header budget-workspace-header">
					<span id="budgetWorkspaceTitle"></span>
					<button type="button" class="ui basic icon button" data-workspace="close" aria-label="Tutup modal"><i class="close icon" aria-hidden="true"></i></button>
				</div>
				<div class="scrolling content">
					<div class="budget-workspace-body"></div>
					<div class="ui negative message" role="alert" hidden></div>
				</div>
				<div class="actions">
					<button type="button" class="ui basic button" data-workspace="close">Tutup</button>
					<button type="button" class="ui primary button" data-workspace="edit-sub" hidden>Ubah Sub Kegiatan</button>
					<button type="submit" class="ui primary button" hidden>Simpan</button>
				</div>
			</form>`);
		this.workspaceDialog = this.root.querySelector(".budget-workspace-dialog");
		$(this.workspaceDialog).modal({
			detachable: true,
			autofocus: false,
			observeChanges: true,
			onHide: () => {
				this.workspaceOpen = false;
				this.workspaceRead?.abort();
			},
			onHidden: () => {
				if (this.root.isConnected) return;
				$(this.workspaceDialog).find(".ui.dropdown").dropdown("destroy");
				$(this.workspaceDialog).modal("destroy").remove();
			},
		});
		this.workspaceDialog.addEventListener("click", (event) => {
			const button = event.target.closest("[data-workspace]");
			if (!button) return;
			switch (button.dataset.workspace) {
				case "close": this.closeWorkspace(); break;
				case "edit-sub": this.openWorkspace("edit-sub",this.workspaceDialog.dataset.code); break;
				case "add":
					this.addIndicator(button.closest("[data-indicator-list]"),{});
					break;
				case "remove": {
					const list = button.closest("[data-indicator-list]");
					button.closest("[data-indicator-row]").remove();
					this.refreshIndicatorList(list);
					break;
				}
			}
		});
		this.workspaceDialog.addEventListener("change", (event) => {
			const select = event.target;
			if (select.matches("[data-renstra-picker]") && select.value) {
				const list = select.closest("[data-indicator-list]");
				const reference = JSON.parse(select.selectedOptions[0].dataset.reference);
				const exists = Array.from(list.querySelectorAll('[data-field="renstra_id"]')).some(input => input.value === select.value);
				if (!exists) this.addIndicator(list,reference);
				$(select).parent(".ui.dropdown").dropdown("clear");
			} else if (select.matches("[data-priority-picker]")) {
				const row = JSON.parse(select.selectedOptions[0].dataset.priority || "{}");
				const form = select.form;
				form.elements.id.value = row.id || "";
				form.elements.kode.value = row.kode || "";
				form.elements.uraian.value = row.uraian || "";
				$(form.elements.aktif).closest(".ui.checkbox")
					.checkbox(row.aktif === undefined || Number(row.aktif) === 1 ? "set checked" : "set unchecked");
			}
		});
		this.workspaceDialog.addEventListener("submit", (event) => {
			event.preventDefault();
			this.saveWorkspace(event.currentTarget);
		});
	}
	renderWorkspaceGroups(rows) {
		const printed = new Set(), body = [], totals = new Map();
		const levels = [[1,"urusan","Urusan"],[2,"bidang","Bidang Urusan"],[3,"program","Program"],[5,"kegiatan","Kegiatan"]];
		for (const row of rows) {
			const parts = String(row.kd_sub_keg).split(".");
			for (const [length] of levels) {
				if (length >= parts.length) break;
				const prefix = parts.slice(0,length).join(".");
				const sums = totals.get(prefix) || {pagu:0,rincian:0};
				sums.pagu += Number(row.pagu ?? row.total ?? 0);
				sums.rincian += Number(row.total || 0);
				totals.set(prefix,sums);
			}
		}
		const canApprove = ["super_admin","admin_wilayah","tapd","kepala_opd","pa_kpa"].includes(window.app?.user?.type_user);
		const doc = this.formatTitle(this.table.replace("_p", " Perubahan"));
		for (const [index,row] of rows.entries()) {
			const code = this.escape(row.kd_sub_keg), parts = String(row.kd_sub_keg).split(".");
			const menuId = `budget-row-menu-${index}`;
			const parents = [];
			for (const [length, level, fallback] of levels) {
				if (length >= parts.length) break;
				const prefix = parts.slice(0, length).join(".");
				if (!printed.has(prefix)) {
					printed.add(prefix);
					body.push(`<tr class="budget-level ${level}" data-tree-key="${this.escape(prefix)}" data-tree-parents="${this.escape(parents.join("|"))}">
						<td class="budget-action-cell">${level === "kegiatan" ? `
							<button type="button" class="ui mini basic icon button budget-row-trigger" popovertarget="budget-kegiatan-menu-${index}" aria-label="Menu kegiatan" title="Menu kegiatan"><i class="ellipsis horizontal icon"></i></button>
							<div id="budget-kegiatan-menu-${index}" class="budget-action-popover" popover>
								<button type="button" class="item budget-tree-toggle" data-budget-action="toggle-tree" data-code="${this.escape(prefix)}"><i class="chevron down icon"></i> <span>Sembunyikan Sub Kegiatan</span></button>
								<button type="button" class="item" data-budget-action="indicators" data-code="${code}">Indikator Program &amp; Kegiatan</button>
							</div>` : `
							<button type="button" class="ui mini basic icon button budget-tree-toggle" data-budget-action="toggle-tree" data-code="${this.escape(prefix)}" aria-label="Buka atau tutup kelompok" title="Buka atau tutup kelompok"><i class="chevron down icon"></i></button>`}
						</td>
						<td class="budget-description-cell">${this.escape(row[level] || prefix + " " + fallback)}</td>
						<td></td><td></td><td></td>
						<td class="budget-amount-cell">${this.money(totals.get(prefix).pagu)}</td>
						<td class="budget-amount-cell">${this.money(totals.get(prefix).rincian)}</td>
					</tr>`);
				}
				parents.push(prefix);
			}
			body.push(`<tr class="budget-group" data-tree-parents="${this.escape(parents.join("|"))}" data-search="${this.escape([row.kd_sub_keg,row.nama_sub_kegiatan,row.urusan,row.bidang,row.program,row.kegiatan].join(" "))}">
				<td class="budget-action-cell"><button type="button" class="ui mini violet basic icon button budget-row-trigger" popovertarget="${menuId}" aria-label="Menu subkegiatan ${code}" title="Menu subkegiatan"><i class="ellipsis horizontal icon"></i></button>
					<div id="${menuId}" class="budget-action-popover" popover>
						<button class="item" data-budget-action="view-sub" data-code="${code}">Lihat Sub Kegiatan</button>
						${this.canWrite && !Number(row.kunci) && !Number(row.setujui) ? `<button class="item" data-budget-action="edit-sub" data-code="${code}">Ubah Sub Kegiatan</button>` : ""}
						<button class="item" data-budget-action="details" data-code="${code}">Lihat Rincian Belanja</button>
						<button class="item" data-budget-action="packages" data-code="${code}">${doc} Paket / Kelompok</button>
						${Number(row.jumlah_uraian) ? `<button class="item" data-budget-action="sub-pdf" data-code="${code}">${doc} Rincian Belanja (PDF)</button>` : ""}
						${canApprove && Number(row.jumlah_uraian) ? `<button class="item" data-budget-action="approval" data-code="${code}" data-approved="${Number(row.setujui) ? 0 : 1}">${Number(row.setujui) ? "Buka Persetujuan" : "Setujui dan Kunci"}</button>` : ""}
					</div></td>
				<td class="budget-description-cell"><a href="#" data-budget-action="open-sub" data-code="${code}"><b class="budget-code">${code}</b> ${this.escape(row.nama_sub_kegiatan || "Sub Kegiatan")}</a></td>
				<td class="center aligned">${this.escape(row.jumlah_uraian)}</td>
				<td class="center aligned">${Number(row.kunci) ? "Dikunci" : "Terbuka"}</td>
				<td class="center aligned">${Number(row.setujui) ? "Disetujui" : (Number(row.jumlah_uraian) ? "Draft" : "Belum ada rincian")}</td>
				<td class="budget-amount-cell">${this.money(row.pagu ?? row.total)}</td>
				<td class="budget-amount-cell"><b>${this.money(row.total)}</b></td>
			</tr>`);
		}
		this.root.querySelector("#budgetGroupList").innerHTML = `<div class="ui segment table-wrapper">
			<table class="ui very basic unstackable table budget-tree-table budget-workspace-table" data-global-pagination="off" data-fixed-columns>
			<colgroup><col class="budget-col-action"><col><col class="budget-col-count"><col class="budget-col-status"><col class="budget-col-status"><col class="budget-col-amount"><col class="budget-col-amount"></colgroup><thead><tr><th data-sort-disabled aria-label="Menu baris"></th><th data-sort-disabled>Uraian</th><th data-sort-disabled>Rincian</th><th data-sort-disabled>Status<br>Sub Kegiatan</th><th data-sort-disabled>Status<br>Rincian</th><th data-sort-disabled class="right aligned">Pagu</th><th data-sort-disabled class="right aligned">Total Rincian</th></tr></thead>
			<tbody>${body.join("")}</tbody></table><div class="ui message budget-empty-search" hidden>Tidak ada subkegiatan yang cocok.</div></div>
			<nav data-budget-pager aria-label="Halaman subkegiatan"></nav>`;
		this.refreshWorkspaceTree();
	}
	refreshWorkspaceTree() {
		const pager = this.root.querySelector("[data-budget-pager]");
		if (!pager) return;
		const query = (document.getElementById("cari_data")?.value || "").trim().toLowerCase();
		const searching = query.length > 0;
		const rows = Array.from(this.root.querySelectorAll(".budget-group[data-tree-parents]"));
		const matches = rows.filter(row => row.dataset.search.toLowerCase().includes(query));
		const choice = document.querySelector('#countRow input[name="countRow"]')?.value;
		const limit = choice === "all" ? Math.max(1, matches.length) : Math.max(1, Number.parseInt(choice, 10) || 5);
		const pages = Math.max(1, Math.ceil(matches.length / limit));
		this.workspacePage = Math.min(this.workspacePage, pages);
		const offset = (this.workspacePage - 1) * limit;
		const pageRows = new Set(matches.slice(offset, offset + limit));
		const ancestors = new Set();
		for (const row of rows) {
			const parents = row.dataset.treeParents.split("|").filter(Boolean);
			const onPage = pageRows.has(row);
			if (onPage) parents.forEach(code => ancestors.add(code));
			row.hidden = !onPage || (!searching && parents.some(code => this.collapsedGroups.has(code)));
		}
		for (const row of this.root.querySelectorAll("[data-tree-key]")) {
			row.hidden = !ancestors.has(row.dataset.treeKey)
				|| (!searching && row.dataset.treeParents.split("|").some(code => this.collapsedGroups.has(code)));
			const closed = !searching && this.collapsedGroups.has(row.dataset.treeKey);
			const button = row.querySelector(".budget-tree-toggle");
			button.setAttribute("aria-expanded", String(!closed));
			button.querySelector("i").className = `chevron ${closed ? "right" : "down"} icon`;
			const label = button.querySelector("span");
			if (label) label.textContent = closed ? "Tampilkan Sub Kegiatan" : "Sembunyikan Sub Kegiatan";
		}
		const empty = this.root.querySelector(".budget-empty-search");
		empty.hidden = matches.length !== 0;
		const markup = pages > 1 ? `<div class="table-pagination-footer">${TableManager.paginationMarkup(this.workspacePage, pages)}</div>` : "";
		if (pager.innerHTML !== markup) pager.innerHTML = markup;
	}
	openWorkspace(action, code) {
		const dialog = this.workspaceDialog, form = dialog;
		const titles = {"open-sub":"Sub Kegiatan Belanja","view-sub":"Lihat Sub Kegiatan Belanja","edit-sub":"Ubah Sub Kegiatan Belanja",indicators:"Indikator Program & Kegiatan",packages:"Paket / Kelompok Belanja",priorities:"Kelola Prioritas Pembangunan Nasional"};
		const body = dialog.querySelector(".budget-workspace-body");
		const editButton = form.querySelector('[data-workspace="edit-sub"]');
		editButton.hidden = true;
		form.dataset.mode = "";
		form.dataset.code = code;
		form.querySelector("[type=submit]").hidden = true;
		form.querySelector("[role=alert]").hidden = true;
		dialog.querySelector("#budgetWorkspaceTitle").textContent = titles[action];
		$(body).find(".ui.dropdown").dropdown("destroy");
		body.innerHTML = '<div class="ui active inline centered loader"></div>';
		if (!this.workspaceOpen) {
			this.workspaceOpen = true;
			$(dialog).modal("show");
		}
		this.workspaceRead?.abort();
		const version = this.workspaceVersion = (this.workspaceVersion || 0) + 1;
		const active = () => this.isActive() && this.workspaceOpen && this.workspaceVersion === version;
		const fail = (message) => { if (active()) body.innerHTML = `<div class="ui warning message">${this.escape(message || "Data tidak berhasil dimuat.")}</div>`; };
		this.workspaceRead = window.Ajax.request({
			method: "GET", silent: true,
			url: action === "priorities" ? "/anggaran/prioritas-nasional"
				: action === "packages" ? `/anggaran/details?tbl=${this.table}&kd_sub_keg=${encodeURIComponent(code)}`
				: `/anggaran/sub-kegiatan-metadata?tbl=${this.table}&kode=${encodeURIComponent(code)}`,
			success: (response) => {
				if (!active()) return;
				if (!response?.success) return fail(response?.message);
				if (action === "priorities") {
					body.innerHTML = this.priorityFields(response.data,response.meta);
					form.dataset.mode = "priorities";
					form.querySelector("[type=submit]").hidden = false;
					this.initWorkspaceControls();
					return;
				}
				if (action === "packages") {
					const groups = new Map();
					for (const item of response.data) {
						const key = JSON.stringify([item.kd_akun,item.kelompok,item.sumber_dana_teks]);
						const group = groups.get(key) || {...item,total:0,items:0};
						group.total += Number(item.jumlah || 0);
						group.items++;
						groups.set(key,group);
					}
					body.innerHTML = `<p><b>${this.escape(code)}</b></p><div class="table-wrapper"><table class="ui compact celled table"><thead><tr><th>Rekening</th><th>Paket / Kelompok</th><th>Sumber Dana</th><th>Rincian</th><th>Jumlah</th></tr></thead><tbody>${Array.from(groups.values(),g=>`<tr><td>${this.escape(g.kd_akun)}</td><td>${this.escape(g.kelompok || "Belum dikelompokkan")}</td><td>${this.escape(g.sumber_dana_teks || "—")}</td><td>${g.items}</td><td class="right aligned">${this.money(g.total)}</td></tr>`).join("") || '<tr><td colspan="5">Belum ada paket atau rincian.</td></tr>'}</tbody></table></div>`;
					return;
				}
				const data = response.data, indicators = action === "indicators";
				const edit = indicators ? data.can_edit_indicators : action !== "view-sub" && data.can_edit_sub;
				form.dataset.mode = edit ? (indicators ? "indicators" : "sub") : "";
				form.querySelector("[type=submit]").hidden = !edit;
				if (!indicators) dialog.querySelector("#budgetWorkspaceTitle").textContent = titles[edit ? "edit-sub" : "view-sub"];
				editButton.hidden = action !== "view-sub" || !data.can_edit_sub;
				const canEdit = indicators ? data.can_edit_indicators : data.can_edit_sub;
				const message = canEdit ? "" : `<div class="ui info message">${this.escape(data.edit_message)}</div>`;
				body.innerHTML = message + this.workspaceFields(data, indicators, edit);
				body.querySelectorAll("[data-indicator-list]").forEach(list => this.refreshIndicatorList(list));
				this.initWorkspaceControls();
			},
			error: (response) => fail(response?.message),
		});
	}
	closeWorkspace() {
		if (!this.workspaceDialog || !this.workspaceOpen) return;
		this.workspaceOpen = false;
		this.workspaceRead?.abort();
		$(this.workspaceDialog).modal("hide");
	}
	initWorkspaceControls() {
		$(this.workspaceDialog).find("select.ui.dropdown").dropdown({
			fullTextSearch: true,
			direction: "auto",
		});
		$(this.workspaceDialog).find("select").each((index, select) => {
			const label = select.closest(".field")?.querySelector("span")?.textContent;
			if (label) $(select).parent(".ui.dropdown").attr("aria-label", label);
		});
		$(this.workspaceDialog).find(".ui.checkbox").checkbox();
		$(this.workspaceDialog).modal("refresh");
	}
	priorityFields(rows, context) {
		const options = rows.map(row => `<option data-priority="${this.escape(JSON.stringify(row))}">${this.escape(row.kode)} — ${this.escape(row.uraian)}${Number(row.aktif) ? "" : " (Nonaktif)"}</option>`).join("");
		return `<p>Wilayah <b>${this.escape(context.region)}</b> · Tahun <b>${this.escape(context.year)}</b>. Prioritas aktif tersedia untuk dipilih oleh OPD.</p>
			<label class="field"><span>Entri referensi</span><select class="ui fluid search selection dropdown" data-priority-picker><option>Tambah prioritas baru</option>${options}</select></label>
			<input name="id" type="hidden">
			<label class="field"><span>Kode</span><input name="kode" maxlength="30" required></label>
			<label class="field"><span>Uraian</span><input name="uraian" maxlength="500" required></label>
			<div class="field"><div class="ui checkbox"><input name="aktif" type="checkbox" value="1" checked><label>Aktif</label></div></div>`;
	}
	workspaceFields(data, indicators, edit) {
		const field = (label, name, value, type="text", editable=edit) =>
			`<label class="field"><span>${label}</span><input type="${type}" name="${name}" value="${this.escape(value ?? "")}" ${editable ? "" : "readonly"} ${type==="number" ? 'min="0" step="0.01"' : ""}></label>`;
		const display = (label, value) => `<div class="field"><label>${label}</label><div class="budget-workspace-value">${this.escape(value || "—")}</div></div>`;
		const month = (label, name, date) => {
			const selected = String(date || "").slice(5,7);
			const labels = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
			if (!edit) return display(label,labels[Number(selected)-1]);
			return `<label class="field"><span>${label}</span><select class="ui fluid search selection dropdown" name="bulan_${name}"><option value="">Pilih bulan</option>
				${labels.map((text,index) => {
					const value = String(index+1).padStart(2,"0");
					return `<option value="${value}" ${value === selected ? "selected" : ""}>${text}</option>`;
				}).join("")}</select></label>`;
		};
		const indicatorsList = (label, name, rows, references=[]) => `<section class="ui segment" data-indicator-list="${name}"><h4>${label} <span class="ui mini blue basic label" data-indicator-count></span></h4>
			<div class="budget-indicator-columns"><b>Tolok ukur</b><b>${name === "indikator" ? "Target / Volume" : "Target"}</b><b>Satuan</b></div>
			<div data-indicator-rows>${rows.map(row => this.indicatorRow(row,edit,name === "indikator")).join("")}</div>
			<p data-indicator-empty hidden>Belum ada indikator.</p>
			${edit && references.length ? `<label class="field" style="margin-top:.75rem"><span>Tambah dari Renstra OPD</span>
				<select class="ui fluid search selection dropdown" data-renstra-picker><option value="">Pilih indikator Renstra</option>${references.map(row => `<option value="${this.escape(row.renstra_id)}" data-reference="${this.escape(JSON.stringify(row))}">${this.escape(row.tolok_ukur)}</option>`).join("")}</select></label>` : ""}
			${edit ? '<button type="button" class="ui mini green basic button" data-workspace="add"><i class="plus icon" aria-hidden="true"></i>Tambah Indikator</button>' : ""}</section>`;
		const base = display("SKPD / Sub Unit",data.organization) + display("Bidang Urusan",data.bidang) + display("Program",`${data.program_code} ${data.program_name}`);
		if (indicators) return base + indicatorsList("Indikator Program (Outcome)","indikator_program",data.program.indikator,data.renstra.program)
			+ display("Kegiatan",`${data.activity_code} ${data.activity_name}`)
			+ field("Kelompok Sasaran Kegiatan","kelompok_sasaran",data.activity.kelompok_sasaran)
			+ (edit ? this.priorityDropdown(data.activity,data.priorities) : display("Prioritas Pembangunan Nasional",data.activity.prioritas_nasional))
			+ indicatorsList("Indikator Keluaran Kegiatan","keluaran",data.activity.keluaran,data.renstra.activity)
			+ indicatorsList("Indikator Hasil Kegiatan","hasil",data.activity.hasil);
		const s = data.sub;
		return base + display("Sub Kegiatan",`${data.code} ${data.name}`)
			+ field("Prioritas Pembangunan Provinsi","prioritas_provinsi",s.prioritas_provinsi)
			+ field("Prioritas Pembangunan Kabupaten/Kota","prioritas_kabupaten",s.prioritas_kabupaten)
			+ field("Label Sub Kegiatan","label",s.label)
			+ display("Sumber Dana",data.funds.length ? data.funds.map(f=>`${f.sumber_dana || "Belum ditetapkan"} — ${this.money(f.jumlah)}`).join("\n") : data.fund_text)
			+ field("Lokasi Pelaksanaan","lokasi",s.lokasi) + field("Rincian Lokasi","rincian_lokasi",s.rincian_lokasi)
			+ `<div class="two fields">${month("Mulai Pelaksanaan","mulai",s.mulai)}${month("Akhir Pelaksanaan","selesai",s.selesai)}</div>`
			+ `<div class="two fields">${display("Anggaran Sub Kegiatan",this.money(data.budget))}${display("Total Rincian Belanja",this.money(data.detail_total))}</div>`
			+ `<div class="two fields">${field("Prakiraan Anggaran N+1","anggaran_n1",s.anggaran_n1,"number")}${field("Prakiraan Anggaran N+2","anggaran_n2",s.anggaran_n2,"number")}</div>`
			+ indicatorsList("Indikator Keluaran Sub Kegiatan","indikator",s.indikator,data.renstra.sub);
	}
	priorityDropdown(activity, rows) {
		const current = Number(activity.prioritas_nasional_id || 0);
		const legacy = activity.prioritas_nasional && !rows.some(row => Number(row.id) === current);
		return `<label class="field"><span>Prioritas Pembangunan Nasional</span><select class="ui fluid search selection dropdown" name="prioritas_nasional_id">
			<option value="">Pilih prioritas</option>
			${legacy ? `<option value="legacy" selected>${this.escape(activity.prioritas_nasional)} (data lama)</option>` : ""}
			${rows.map(row => `<option value="${this.escape(row.id)}" ${Number(row.id) === current ? "selected" : ""}>${this.escape(row.kode)} — ${this.escape(row.uraian)}${Number(row.aktif) ? "" : " (Nonaktif)"}</option>`).join("")}
			</select>${rows.some(row => Number(row.aktif)) ? "" : '<small>Daftar aktif belum tersedia. Hubungi TAPD atau administrator wilayah.</small>'}</label>`;
	}
	addIndicator(list, row) {
		const rows = list.querySelector("[data-indicator-rows]");
		rows.insertAdjacentHTML("beforeend",this.indicatorRow(row,true,list.dataset.indicatorList === "indikator",true));
		this.refreshIndicatorList(list);
		rows.lastElementChild.querySelector(row.renstra_id ? '[data-field="target"]' : '[data-field="tolok_ukur"]').focus();
	}
	refreshIndicatorList(list) {
		const count = list.querySelectorAll("[data-indicator-row]").length;
		list.querySelector("[data-indicator-count]").textContent = `${count} indikator`;
		list.querySelector("[data-indicator-empty]").hidden = count > 0;
		const picker = list.querySelector("[data-renstra-picker]");
		if (!picker) return;
		const selected = new Set(Array.from(list.querySelectorAll('[data-field="renstra_id"]'), input => input.value));
		for (const option of picker.options) option.disabled = selected.has(option.value);
		picker.value = "";
		picker.disabled = Array.from(picker.options).slice(1).every(option => option.disabled);
		const dropdown = $(picker).parent(".ui.dropdown");
		if (dropdown.length) {
			dropdown.dropdown("clear");
			dropdown.toggleClass("disabled", picker.disabled);
			dropdown.find(".menu > .item").each((index, item) => {
				$(item).toggleClass("disabled", selected.has(item.dataset.value));
			});
			dropdown.dropdown("refresh");
		}
	}
	indicatorRow(row, edit, volume, pending=false) {
		const protectedRow = row.renstra_id && !pending;
		return `<div class="budget-indicator-columns" data-indicator-row>
			${row.renstra_id ? `<input type="hidden" data-field="renstra_id" value="${this.escape(row.renstra_id)}">` : ""}
			<div><input type="text" data-field="tolok_ukur" aria-label="Tolok ukur" placeholder="Tolok ukur" value="${this.escape(row.tolok_ukur ?? "")}" ${edit && !row.renstra_id ? "" : "readonly"}>
			${row.renstra_id ? '<small class="ui mini teal basic label">Indikator Renstra</small>' : ""}</div>
			<input type="text" data-field="target" aria-label="${volume ? "Target / Volume" : "Target"}" placeholder="Target" value="${this.escape(row.target ?? "")}" ${volume ? 'inputmode="decimal"' : ""} ${edit ? "" : "readonly"}>
			<input type="text" data-field="satuan" aria-label="Satuan" placeholder="Satuan" value="${this.escape(row.satuan ?? "")}" ${edit && !row.renstra_id ? "" : "readonly"}>
			${edit ? `<button type="button" class="ui mini basic red icon button" data-workspace="remove" aria-label="Hapus indikator" ${protectedRow ? 'disabled title="Indikator Renstra"': ''}><i class="trash icon" aria-hidden="true"></i></button>` : ""}
		</div>`;
	}
	saveWorkspace(form) {
		const {mode,code} = form.dataset;
		const submit = form.querySelector("[type=submit]"), error = form.querySelector("[role=alert]");
		if (!mode || submit.disabled) return;
		const payload = Object.fromEntries(new FormData(form));
		for (const list of form.querySelectorAll("[data-indicator-list]")) {
			payload[list.dataset.indicatorList] = Array.from(list.querySelectorAll("[data-indicator-row]"), row =>
				Object.fromEntries(Array.from(row.querySelectorAll("[data-field]"), input => [input.dataset.field,input.value])));
		}
		const version = this.workspaceVersion;
		submit.disabled = true;
		error.hidden = true;
		const finish = (response) => {
			submit.disabled = false;
			if (!this.isActive() || !this.workspaceOpen || version !== this.workspaceVersion) return;
			if (response?.success) this.closeWorkspace();
			else { error.textContent = response?.message || "Perubahan tidak berhasil disimpan."; error.hidden = false; }
		};
		const priority = mode === "priorities";
		window.Ajax.request({url:priority ? "/anggaran/prioritas-nasional" : "/anggaran/sub-kegiatan-metadata",method:"POST",silent:true,
			data:priority ? payload : {tbl:this.table,kode:code,mode,payload:JSON.stringify(payload)},
			success:finish,error:finish,
		});
	}
	loadDetails(code) {
		this.loadDocument(`/anggaran/details?tbl=${encodeURIComponent(this.table)}&kd_sub_keg=${encodeURIComponent(code)}`, rows => {
			this.renderDetails(code, rows);
			if (["dpa", "dppa"].includes(this.table))
				$("#budgetGroupList>.ui.segment:first").append(
					`<a class="ui right floated teal button" data-spa="server" href="${window.appUrl ? window.appUrl("/anggaran/rencana-rekening") : "/anggaran/rencana-rekening"}?tbl=${this.table}&kd_sub_keg=${encodeURIComponent(code)}"><i class="calendar alternate icon"></i>Rencana per Rekening</a>`,
				);
		});
	}
	renderDetails(code, rows) {
		if (["rkpd", "rkpd_p"].includes(this.table)) return this.renderRkpdDetails(code, rows);
		const changed = rows.some((r) => r.jumlah_awal !== undefined && r.jumlah_awal !== null),
			canPlan = ["dpa", "dppa"].includes(this.table),
			groupLocked = rows.some((r) => Number(r.kunci) || Number(r.setujui));
		let lastGroup = "", lastDetailGroup = "",
			cells = "";
		rows.forEach((r) => {
			const group = [r.kel_rek, r.jenis_kelompok, r.kelompok].filter(Boolean).join(" — ");
			if (group && group !== lastGroup) {
				cells += `<tr class="budget-level account"><td colspan="${changed ? 9 : 6}"><i class="folder icon"></i>${this.escape(group)}</td></tr>`;
				lastGroup = group;
				lastDetailGroup = "";
			}
			if (r.uraian && r.uraian !== lastDetailGroup) {
				cells += `<tr class="budget-level subgroup"><td></td><td colspan="${changed ? 8 : 5}"><i class="angle right icon"></i><b>[ - ] ${this.escape(r.uraian)}</b>${r.sumber_dana_teks ? `<div class="ui tiny grey text">Sumber Dana: ${this.escape(r.sumber_dana_teks)}</div>` : ""}</td></tr>`;
				lastDetailGroup = r.uraian;
			}
			const inherited = Number(r.source_id) > 0;
			cells += `<tr data-id="${r.id}"><td>${this.escape(r.kd_akun || "-")}</td><td><b>${this.escape(r.komponen_asli || r.komponen || r.uraian)}</b>${r.spesifikasi ? `<div class="ui tiny grey text">${this.escape(r.spesifikasi)}</div>` : ""}</td>${changed ? `<td class="right aligned">${this.escape(r.volume_awal || 0)}</td><td class="right aligned">${this.money(r.harga_satuan_awal || 0)}</td><td class="right aligned budget-pagu-column">${this.money(r.jumlah_awal || 0)}</td>` : ""}<td class="right aligned">${this.escape(r.koefisien_keterangan || r.volume || 0)}</td><td class="right aligned">${this.money(r.harga_satuan || 0)}</td><td class="right aligned budget-pagu-column"><b>${this.money(r.jumlah || 0)}</b></td><td>${this.canWrite && !groupLocked ? `<div class="ui mini basic icon buttons">${canPlan ? `<button class="ui teal button" title="Rencana bulanan" data-budget-action="monthly" data-id="${r.id}"><i class="calendar alternate icon"></i></button>` : ""}<button class="ui button" title="Edit rincian" data-ui="open-form" data-action="edit" data-tbl="${this.table}" data-id="${r.id}"><i class="blue edit icon"></i></button><button class="ui ${inherited ? "orange" : "red"} button" title="${inherited ? "Nolkan volume" : "Hapus rincian"}" data-budget-action="delete" data-id="${r.id}"><i class="${inherited ? "ban" : "trash"} icon"></i></button></div>` : '<span class="ui grey label">Dikunci</span>'}</td></tr>`;
		});
		const before = changed ? '<th colspan="3" class="center aligned">Sebelum Perubahan</th>' : "",
			cols = changed
				? '<colgroup><col style="width:11%"><col style="width:24%"><col style="width:7%"><col style="width:9%"><col style="width:13%"><col style="width:7%"><col style="width:9%"><col style="width:15%"><col style="width:5%"></colgroup>'
				: '<colgroup><col style="width:15%"><col style="width:34%"><col style="width:12%"><col style="width:15%"><col style="width:19%"><col style="width:5%"></colgroup>';
		$("#budgetGroupList").html(
			`<div class="ui segment"><button class="ui basic button" data-budget-action="back"><i class="arrow left icon"></i> Daftar Sub Kegiatan</button><span class="ui blue label">${this.escape(code)}</span>${this.canWrite && !groupLocked ? `<button class="ui right floated primary button" data-ui="open-form" data-action="add" data-tbl="${this.table}" data-budget-code="${this.escape(code)}"><i class="plus icon"></i> Tambah Rincian Belanja</button>` : ""}</div><div class="ui segment table-wrapper"><table class="ui celled striped fixed table budget-detail-table">${cols}<thead>${changed ? `<tr><th colspan="2"></th>${before}<th colspan="3" class="center aligned">Sesudah Perubahan</th><th></th></tr>` : ""}<tr><th>Kode Akun</th><th>Uraian/Komponen</th>${changed ? "<th>Volume</th><th>Harga</th><th class=\"budget-pagu-column\">Jumlah</th>" : ""}<th>Volume/Target</th><th>Harga Satuan</th><th class="budget-pagu-column">Jumlah/Pagu</th><th>Aksi</th></tr></thead><tbody>${cells || `<tr><td colspan="${changed ? 9 : 6}" class="center aligned">Belum ada rincian</td></tr>`}</tbody></table></div>`,
		);
	}
	renderRkpdDetails(code, rows) {
		const r=rows[0]||{}, changed=this.table==="rkpd_p", next=changed?"renja_perubahan":"renja";
		$("#budgetGroupList").html(`<div class="ui segment"><button class="ui basic button" data-budget-action="back"><i class="arrow left icon"></i>Daftar Sub Kegiatan</button><span class="ui blue label">${this.escape(code)}</span><a class="ui right floated teal button" data-spa="server" href="${window.appUrl('/'+next)}?kd_sub_keg=${encodeURIComponent(code)}"><i class="arrow right icon"></i>Lihat ${changed?'Renja Perubahan':'Renja'} Turunan</a></div><div class="ui two stackable cards rkpd-detail-cards"><div class="ui fluid card"><div class="content"><div class="header">Indikator dan Target</div><div class="description"><h3>${this.escape(r.uraian||'-')}</h3><span class="ui large teal label">Target ${this.escape(r.volume||0)}</span></div></div></div><div class="ui fluid card"><div class="content"><div class="header">Pagu Usulan</div><div class="description"><h2>${this.money(r.jumlah||0)}</h2><p>${this.escape(r.keterangan||'Belum ada keterangan')}</p></div></div></div><div class="ui fluid card"><div class="content"><div class="header">Lokasi</div><div class="description">${this.escape(r.lokasi||'-')}</div></div></div><div class="ui fluid card"><div class="content"><div class="header">Kelompok Sasaran</div><div class="description">${this.escape(r.kelompok_sasaran||'-')}</div></div></div></div>${this.canWrite?`<button class="ui primary button" data-ui="open-form" data-action="edit" data-tbl="${this.table}" data-id="${r.id}"><i class="edit icon"></i>Ubah Rincian RKPD</button>`:''}`);
	}
	bindPhase3Actions() {
		this.bindExport();
		$(document)
			.off("click.budgetWorkspacePage", "[data-budget-pager] [data-page]")
			.on("click.budgetWorkspacePage", "[data-budget-pager] [data-page]", event => {
				if (!this.isActive() || event.currentTarget.matches(".disabled, .active")) return;
				this.workspacePage = Number(event.currentTarget.dataset.page);
				this.refreshWorkspaceTree();
			})
			.off("click.budgetHierarchy", "[data-budget-action]")
			.on("click.budgetHierarchy", "[data-budget-action]", (e) => {
				const b = $(e.currentTarget),
					a = b.data("budget-action");
				const menu = e.currentTarget.closest(".budget-action-popover");
				if (menu?.matches(":popover-open")) menu.hidePopover();
				if (["open-sub","view-sub","edit-sub","indicators","packages","priorities"].includes(a) && this.workspaceEnabled) {
					e.preventDefault();
					this.openWorkspace(a,String(b.data("code")));
				} else if (a === "sub-pdf") {
					this.download(`/anggaran/export_pdf?tbl=${this.table}&kd_sub_keg=${encodeURIComponent(b.data("code"))}`);
				} else if (["toggle-tree","expand-all","collapse-all"].includes(a) && this.workspaceEnabled) {
					const code = String(b.data("code"));
					if (a === "expand-all") this.collapsedGroups.clear();
					else if (a === "collapse-all") this.collapsedGroups = new Set(Array.from(this.root.querySelectorAll("[data-tree-key]"), row=>row.dataset.treeKey));
					else if (this.collapsedGroups.has(code)) this.collapsedGroups.delete(code);
					else this.collapsedGroups.add(code);
					this.refreshWorkspaceTree();
				} else if (a === "details") this.loadDetails(String(b.data("code")));
				else if (a === "back") this.loadGroups();
				else if (a === "approval") {
					if (Number(b.data("approved")) && ["super_admin","admin_wilayah","tapd"].includes(window.app?.user?.type_user || "")) {
						const next=this.next[this.table];$("#approvalSubCode").val(b.data("code"));$("#approvalFlowInfo").html(`<b>${this.escape(b.data("code"))}</b><br>${next?`${this.table.toUpperCase()} → ${next.toUpperCase()}`:"Tahap terakhir: hanya dapat dikunci."}`);$("#tapdApprovalModal [data-approval-scenario]").not('[data-approval-scenario="KUNCI"]').toggleClass("disabled",!next);$("#tapdApprovalModal").modal("show");return;
					}
					window.Ajax.request({
						url: "/anggaran/approval",
						method: "POST",
						data: { tbl: this.table, kd_sub_keg: b.data("code"), approved: b.data("approved") },
						success: (r) => { if (r?.success) this.loadGroups(); },
					});
				}
				else if (a === "pdf") this.download(`/anggaran/export_pdf?tbl=${this.table}`);
				else if (a === "delete") {
					if (!window.confirm("Lanjutkan? Baris turunan dokumen sebelumnya akan dipertahankan dengan volume nol; hanya baris baru yang dapat dihapus.")) return;
					window.Ajax.request({
						method: "POST",
						data: { action: "delete", tbl: this.table, id_row: b.data("id") },
						success: (r) => {
							if (r?.success) this.loadGroups();
						},
					});
				}
			});
		$(document).off("click.tapdScenario","[data-approval-scenario]").on("click.tapdScenario","[data-approval-scenario]",e=>{const b=$(e.currentTarget);if(b.hasClass("disabled"))return;const scenario=b.data("approval-scenario");if(scenario==="KUNCI_GANTI_TUJUAN"&&!window.confirm("Isi lama sub kegiatan pada dokumen tujuan akan dinonaktifkan dan diganti dari sumber yang disetujui. Lanjutkan?"))return;window.Ajax.request({url:"/anggaran/approval",method:"POST",data:{tbl:this.table,kd_sub_keg:$("#approvalSubCode").val(),approved:1,scenario},success:r=>{if(r?.success){$("#tapdApprovalModal").modal("hide");this.loadGroups();}}});});
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
			.on("click.budgetAux", '[data-budget-action="same-price-excel"]', () =>
				this.download(`/anggaran/export_harga_sama_excel?tbl=${this.table}`),
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
		if (!["super_admin", "admin_wilayah"].includes(window.app?.user?.type_user || "")) return;
		$("#tapdModal .content").append(
			'<div class="ui divider"></div><form class="ui small form" id="tapdAssignmentForm"><h4 class="ui header">Tambah Penugasan TAPD</h4><div class="three fields"><div class="field"><label>Nama</label><input name="nama" required></div><div class="field"><label>NIP</label><input name="nip"></div><div class="field"><label>Jabatan</label><input name="jabatan" required></div></div><div class="three fields"><div class="field"><label>Berlaku mulai</label><div class="ui calendar budget-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" name="tanggal_mulai" required autocomplete="off"></div></div></div><div class="field"><label>Sampai</label><div class="ui calendar budget-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" name="tanggal_selesai" required autocomplete="off"></div></div></div><div class="field"><label>&nbsp;</label><button class="ui primary button">Simpan Penugasan</button></div></div></form>',
		);
		const pad=n=>String(n).padStart(2,"0");
		$("#tapdAssignmentForm .ui.calendar").calendar({type:"date",firstDayOfWeek:1,formatter:{date:d=>d?`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`:""}});
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
			.html().replace(/"/g, "&quot;").replace(/'/g, "&#39;");
	}
	money(v) {
		return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(
			Number(v || 0),
		);
	}
	injectStyles() {
		if (document.getElementById("budgetHierarchyStyle")) return;
		$("head").append(
			`<style id="budgetHierarchyStyle">.anggaran-hero{display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#19324d,#346fa7);color:#fff;padding:22px;border-radius:14px;margin-bottom:15px}.anggaran-hero h2{margin:3px 0}.budget-summary{background:#fff;padding:14px;border-radius:10px;margin-bottom:14px!important;box-shadow:0 2px 12px rgba(34,36,38,.08)}.budget-summary .budget-money{font-size:1.45rem!important}.budget-tree-table .budget-level td{font-weight:700}.budget-tree-table .program td{background:#d8ecfb!important;color:#19324d}.budget-tree-table .kegiatan td{background:#eef7fc!important;padding-left:28px!important}.budget-tree-table .account td{background:#f7f7f7!important}.budget-tree-table a{cursor:pointer}.budget-tree-controls{margin:0 0 10px!important}.budget-level.urusan td,.budget-level.bidang td{background:#cbe3f7!important;color:#19324d}.budget-tree-table tr[hidden],.budget-workspace-dialog [hidden],.budget-empty-search[hidden]{display:none!important}.budget-workspace-dialog .budget-workspace-value{background:var(--app-raised,#f3f7fa);color:inherit;padding:.65em;border-radius:.28571429rem;white-space:pre-line}.budget-workspace-dialog .field>span{display:block;font-weight:bold;margin-bottom:.3em}.budget-indicator-columns{display:grid;grid-template-columns:minmax(0,3fr) minmax(4em,1fr) minmax(4em,1fr) auto;gap:.5rem;align-items:start;margin:.45rem 0}.budget-indicator-columns input{min-width:0}.table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch}.budget-detail-table{min-width:1100px!important}[data-budget-action="monthly"]{display:none!important}@media(max-width:800px){.anggaran-hero{display:block}.anggaran-hero .buttons{margin-top:12px}.budget-summary .statistic{min-width:45%!important;margin-bottom:12px!important}}
			.budget-workspace-dialog.ui.form input[readonly] { background: var(--app-raised,#f3f7fa); color: var(--app-muted,#53647b); }
			.budget-workspace-dialog label.field { display: block; }
			.budget-workspace-header { display: flex !important; align-items: center; justify-content: space-between; gap: 1rem; }
			.budget-workspace-header > .button { flex-shrink: 0; margin: 0; }
			.budget-workspace-dialog [data-indicator-list] h4 { display: flex; align-items: center; gap: .5rem; }
			.budget-workspace-dialog [data-indicator-list] [data-workspace="add"] { margin-top: .5rem; }
			.budget-workspace-dialog [data-indicator-row] .label { margin: .25rem 0 0; }
			</style>`,
		);
	}
}
