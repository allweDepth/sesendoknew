class KontrakModule extends BaseCrudModule {
	constructor() {
		super({
			moduleName: "kontrak & realisasi",
			menuItems: [
				{ label: "SPK / SPMK / Kontrak", tbl: "kontrak" },
				{ label: "Realisasi", tbl: "realisasi" },
				{ label: "Laporan Realisasi & Grafik", tbl: "__laporan" },
			],
			useMenu: false,
		});
		this.contractItems = [];
		this.availableItems = [];
		this.contractId = null;
		this.deliveryData = null;
	}

	init() {
		this.styles();
		this.rowAction();
		super.init();
		this.bindActions();
		this.bindUpload();
	}

	bindUpload() {
		$(document)
			.off("click.contractDocumentTab", '[data-tab="documents"]')
			.on("click.contractDocumentTab", '[data-tab="documents"]', () => {
				const target = $("#contractDeliveryContent .tab[data-tab=documents]");
				if (!target.length || $("#contractDocumentForm").length) return;
				target
					.prepend(
						`<form class="ui form segment" id="contractDocumentForm">
				<div class="four fields">
					<div class="field"><label>Jenis</label><select name="jenis_dokumen" class="ui dropdown">${["KONTRAK", "SPK", "SPMK", "SSKK", "SSUK", "RAB", "JADWAL", "KURVA_S", "GAMBAR", "BAST", "PHO", "FHO", "ADENDUM", "JAMINAN", "LAPORAN", "LAINNYA"].map((x) => `<option value="${x}">${x.replaceAll("_", " ")}</option>`).join("")}</select></div>
					<div class="required field"><label>Judul</label><input name="judul" required></div>
					<div class="field"><label>Tanggal</label><div class="ui calendar contract-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" name="tanggal_dokumen" placeholder="YYYY-MM-DD" autocomplete="off"></div></div></div>
					<div class="required field"><label>File (maks. 25 MB)</label><input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx,.zip"></div>
				</div>
				<button type="submit" class="ui teal button"><i class="upload icon"></i>Upload Dokumen</button>
			</form>`,
					)
					.find(".ui.dropdown")
					.dropdown();

				const docs = this.deliveryData?.documents || [];
				target
					.children(".ui.segment")
					.not("#contractDocumentForm")
					.each((i, element) => {
						const doc = docs[i];
						if (!doc || $(element).find("[data-contract-document-delete]").length) return;
						$(element).append(
							`<div class="ui right floated mini buttons"><a class="ui basic blue button" href="${window.appUrl ? window.appUrl(`/kontrak/document/download?id=${doc.id}`) : `/kontrak/document/download?id=${doc.id}`}"><i class="download icon"></i>Unduh</a><button class="ui basic red button" data-contract-document-delete="${doc.id}"><i class="trash icon"></i>Hapus</button></div><div class="ui clearing divider"></div>`,
						);
					});
			});

		$(document)
			.off("submit.contractDocument", "#contractDocumentForm")
			.on("submit.contractDocument", "#contractDocumentForm", (e) => {
				e.preventDefault();
				if (window.FormValidation && !FormValidation.validate(e.currentTarget)) return;
				const form = new FormData(e.currentTarget);
				form.set("contract_id", this.contractId);
				const button = $(e.currentTarget).find("button[type=submit]").addClass("loading disabled");
				window.Ajax.request({
					url: "/kontrak/document/upload",
					method: "POST",
					data: form,
					processData: false,
					contentType: false,
					success: () => this.openDelivery(this.contractId, "documents"),
					complete: () => button.removeClass("loading disabled"),
				});
			});

		$(document)
			.off("click.contractDocumentDelete", "[data-contract-document-delete]")
			.on("click.contractDocumentDelete", "[data-contract-document-delete]", (e) => {
				if (!confirm("Hapus dokumen dan file fisiknya dari server?")) return;
				window.Ajax.request({
					url: "/kontrak/document/delete",
					method: "POST",
					data: { id: $(e.currentTarget).data("contract-document-delete") },
					success: () => this.openDelivery(this.contractId, "documents"),
				});
			});
	}

	loadTable(tbl, req = null) {
		if (tbl === "__laporan") {
			this.state.tbl = tbl;
			this.renderReport();
			return;
		}
		super.loadTable(tbl, req);
	}

	buildActionButtons(tbl) {
		return `${super.buildActionButtons(tbl)}<div class="ui right floated basic icon buttons" style="margin-top:10px;margin-right:8px">${tbl === "kontrak" ? '<button class="ui button" data-p4="contract-pdf" title="PDF kontrak"><i class="file pdf icon"></i></button>' : ""}<button class="ui button" data-p4="report-excel" title="Laporan Excel"><i class="file excel icon"></i></button><button class="ui button" data-p4="report-pdf" title="Laporan PDF"><i class="chart bar icon"></i></button></div>`;
	}

	rowAction() {
		window.TableRowInjector = (c) =>
			c.tbl === "kontrak"
				? `<button class="ui violet button" data-contract-items="${c.id}" title="Kelola uraian kontrak"><i class="list alternate outline icon"></i></button><button class="ui teal button" data-contract-delivery="${c.id}" title="RAB, Time Schedule, Kurva S, dan dokumen"><i class="tasks icon"></i></button>`
				: "";
	}

	bindActions() {
		$(document)
			.off("click.phase4", "[data-p4]")
			.on("click.phase4", "[data-p4]", (e) => {
				const a = $(e.currentTarget).data("p4");
				if (a === "contract-pdf") {
					const id = window.prompt("ID kontrak yang akan dicetak:");
					if (id) this.download(`/kontrak/pdf?id=${encodeURIComponent(id)}`);
					return;
				}
				this.download(a === "report-excel" ? "/kontrak/laporan_excel" : "/kontrak/laporan_pdf");
			});
		$(document)
			.off("click.contractItems", "[data-contract-items]")
			.on("click.contractItems", "[data-contract-items]", (e) =>
				this.openItems(Number($(e.currentTarget).data("contract-items"))),
			);
		$(document)
			.off("click.contractDelivery", "[data-contract-delivery]")
			.on("click.contractDelivery", "[data-contract-delivery]", (e) =>
				this.openDelivery(Number($(e.currentTarget).data("contract-delivery"))),
			);
		$(document)
			.off("click.contractDeliveryBack", "[data-contract-delivery-back]")
			.on("click.contractDeliveryBack", "[data-contract-delivery-back]", () =>
				this.loadTable("kontrak", this.state.req ?? null),
			);
		$(document)
			.off("input.contractSearch", "#contractItemSearch")
			.on(
				"input.contractSearch",
				"#contractItemSearch",
				this.debounce(() => this.loadAvailable(), 350),
			);
		$(document)
			.off("change.contractSub", "#contractSubFilter")
			.on("change.contractSub", "#contractSubFilter", () => this.loadAvailable());
		$(document)
			.off("click.contractItemAction", "[data-contract-item-action]")
			.on("click.contractItemAction", "[data-contract-item-action]", (e) => this.itemAction($(e.currentTarget)));
		$(document)
			.off("change.contractValue", "[data-contract-value]")
			.on("change.contractValue", "[data-contract-value]", (e) => {
				this.contractItems[Number($(e.currentTarget).data("contract-value"))].nilai_kontrak = Number(
					e.target.value || 0,
				);
				this.renderSelected();
			});
		$(document)
			.off("click.deliveryAction", "[data-delivery-action]")
			.on("click.deliveryAction", "[data-delivery-action]", (e) =>
				this.deliveryAction($(e.currentTarget).data("delivery-action"), $(e.currentTarget)),
			);
		$(document)
			.off("change.rabImport", "#rabImportFile")
			.on("change.rabImport", "#rabImportFile", (e) => this.importRab(e.currentTarget));
	}

	openItems(id) {
		this.contractId = id;
		this.ensureModal();
		$("#contractItemsModal").modal({ closable: false, allowMultiple: true }).modal("show");
		$("#contractSelectedList").html('<div class="ui active centered inline loader"></div>');
		window.Ajax.request({
			url: `/kontrak/available-subactivities?contract_id=${id}`,
			method: "GET",
			success: (r) => {
				$("#contractSubFilter")
					.html(
						'<option value="">Pilih sub kegiatan dahulu</option>' +
							(r.data || [])
								.map(
									(x) =>
										`<option value="${this.esc(x.kd_sub_keg)}">${this.esc(x.kd_sub_keg)} · ${x.jumlah_uraian} uraian · ${this.money(x.pagu)}</option>`,
								)
								.join(""),
					)
					.dropdown("refresh");
			},
		});
		window.Ajax.request({
			url: `/kontrak/items?contract_id=${id}`,
			method: "GET",
			success: (r) => {
				this.contractItems = (r.data || []).map((x) => ({
					...x,
					pagu: Number(x.pagu),
					nilai_kontrak: Number(x.nilai_kontrak),
				}));
				this.renderSelected();
				this.renderAvailable();
			},
		});
	}

	ensureModal() {
		if ($("#contractItemsModal").length) return;
		$("body").append(
			`<div class="ui large modal" id="contractItemsModal"><i class="close icon"></i><div class="header"><i class="violet list alternate outline icon"></i> Rincian Uraian Kontrak</div><div class="content"><div class="ui info message"><div class="header">Multi sub kegiatan dan multi uraian</div><p>Pilih subkegiatan, lalu cari uraian, kode rekening, atau nilai pagu. Maksimal 50 hasil per pencarian agar tetap ringan.</p></div><div class="contract-item-grid"><section><div class="field"><label>Sub Kegiatan</label><select class="ui fluid search dropdown" id="contractSubFilter"><option value="">Pilih sub kegiatan dahulu</option></select></div><div class="ui fluid icon input" style="margin-top:9px"><input id="contractItemSearch" placeholder="Cari uraian, rekening, atau nilai anggaran..."><i class="search icon"></i></div><div id="contractAvailableList" class="contract-scroll"><div class="ui message">Pilih sub kegiatan untuk menampilkan uraian.</div></div></section><section><h4 class="ui header">Uraian terpilih</h4><div id="contractSelectedList" class="contract-scroll"></div></section></div></div><div class="actions"><button class="ui deny button">Tutup</button><button class="ui positive violet button" data-contract-item-action="save"><i class="save icon"></i>Simpan Rincian</button></div></div>`,
		);
		$("#contractSubFilter").dropdown();
	}

	loadAvailable() {
		const sub = $("#contractSubFilter").val() || "";
		if (!sub) {
			this.availableItems = [];
			this.renderAvailable();
			return;
		}
		const q = encodeURIComponent($("#contractItemSearch").val() || "");
		$("#contractAvailableList").html('<div class="ui active centered inline loader"></div>');
		window.Ajax.request({
			url: `/kontrak/available-items?q=${q}&kd_sub_keg=${encodeURIComponent(sub)}&limit=50&contract_id=${this.contractId}`,
			method: "GET",
			success: (r) => {
				this.availableItems = r.data || [];
				this.renderAvailable();
			},
		});
	}

	renderAvailable() {
		const selected = new Set(this.contractItems.map((x) => `${x.tahap}:${x.anggaran_id}`));
		const rows = this.availableItems
			.map((x, i) => ({ ...x, _index: i }))
			.filter((x) => !selected.has(`${x.tahap}:${x.anggaran_id}`));
		$("#contractAvailableList").html(
			rows
				.map(
					(x) =>
						`<article class="contract-item-card"><div><span class="ui tiny ${x.tahap === "dppa" ? "orange" : "blue"} label">${this.esc(x.tahap.toUpperCase())}</span><b>${this.esc(x.kd_sub_keg)}</b><small>${this.esc(x.kd_akun || "Tanpa kode akun")}</small><p>${this.esc(x.uraian)}</p><strong>Pagu tersedia ${this.money(x.pagu_tersedia)}</strong></div><button class="ui mini violet icon button" data-contract-item-action="add" data-index="${x._index}"><i class="plus icon"></i></button></article>`,
				)
				.join("") || '<div class="ui message">Tidak ada uraian lain yang tersedia.</div>',
		);
	}

	renderSelected() {
		let pagu = 0,
			value = 0;
		const html = this.contractItems
			.map((x, i) => {
				pagu += Number(x.pagu || 0);
				value += Number(x.nilai_kontrak || 0);
				return `<article class="contract-selected-card"><button class="ui mini red basic icon button" data-contract-item-action="remove" data-index="${i}"><i class="trash icon"></i></button><div><b>${this.esc(x.kd_sub_keg)} · ${this.esc(x.kd_akun || "")}</b><p>${this.esc(x.uraian)}</p><small>Pagu ${this.money(x.pagu)}</small><div class="ui right labeled fluid input"><label class="ui label">Nilai</label><input type="number" min="1" max="${Number(x.pagu_tersedia || x.pagu)}" value="${Number(x.nilai_kontrak || 0)}" data-contract-value="${i}"><div class="ui label">IDR</div></div></div></article>`;
			})
			.join("");
		$("#contractSelectedList").html(
			html +
				`<div class="contract-total"><span>Pagu ${this.money(pagu)}</span><b>Nilai Kontrak ${this.money(value)}</b></div>`,
		);
	}

	itemAction(btn) {
		const a = btn.data("contract-item-action"),
			i = Number(btn.data("index"));
		if (a === "add") {
			const x = this.availableItems[i];
			if (x) this.contractItems.push({ ...x, pagu: Number(x.pagu), nilai_kontrak: Number(x.pagu_tersedia) });
			this.renderAvailable();
			this.renderSelected();
			return;
		}
		if (a === "remove") {
			this.contractItems.splice(i, 1);
			this.renderSelected();
			this.renderAvailable();
			return;
		}
		if (a === "save") {
			if (!this.contractItems.length) {
				Toast.error("Pilih minimal satu uraian kontrak");
				return;
			}
			btn.addClass("loading disabled");
			const items = this.contractItems.map((x) => ({
				tahap: x.tahap,
				anggaran_id: x.anggaran_id,
				nilai_kontrak: Number(x.nilai_kontrak || 0),
			}));
			window.Ajax.request({
				url: "/kontrak/items/save",
				method: "POST",
				data: { contract_id: this.contractId, items: JSON.stringify(items) },
				success: () => {
					$("#contractItemsModal").modal("hide");
					window.tableManager?.fetchData();
				},
				complete: () => btn.removeClass("loading disabled"),
			});
		}
	}

	openDelivery(id, activeTab = "rab") {
		this.contractId = id;
		if (this.tableManager && typeof this.tableManager.destroy === "function") this.tableManager.destroy();
		window.tableManager = null;
		window.history.replaceState(
			null,
			"",
			window.appUrl ? window.appUrl(`/kontrak?detail=${id}`) : `/kontrak?detail=${id}`,
		);
		$("#crud-table-container").html(
			`<div class="contract-delivery-page"><div class="ui clearing segment contract-delivery-toolbar"><button class="ui left floated basic button" data-contract-delivery-back><i class="arrow left icon"></i>Kembali ke Tabel Kontrak</button><div class="ui right floated buttons"><input type="file" id="rabImportFile" accept=".xlsx,.xls" hidden><button class="ui button" data-delivery-action="import"><i class="upload icon"></i>Import RAB</button><button class="ui green button" data-delivery-action="excel"><i class="file excel icon"></i>Excel</button><button class="ui red button" data-delivery-action="pdf"><i class="file pdf icon"></i>PDF</button></div></div><div id="contractDeliveryContent"><div class="ui active centered inline loader"></div></div></div>`,
		);
		window.Ajax.request({
			url: `/kontrak/delivery?contract_id=${id}`,
			method: "GET",
			success: (r) => {
				this.deliveryData = r.data || {};
				this.renderDelivery(activeTab);
			},
		});
	}

	renderDelivery(activeTab = "rab") {
		const d = this.deliveryData || {},
			rab = d.rab || [],
			weeks = d.schedule || [],
			curve = d.curve || [];
		const rabRows = (rab.length ? rab : [{}])
			.map(
				(x, i) =>
					`<tr><td><input type="hidden" value="${x.id || ""}" data-rab="id"><input value="${this.esc(x.nomor || i + 1)}" data-rab="nomor" required></td><td><input value="${this.esc(x.uraian || "")}" data-rab="uraian" required></td><td><input value="${this.esc(x.satuan || "")}" data-rab="satuan" required></td><td><input type="number" min="0.000000000001" step="any" value="${Number(x.vol_negoisasi || 0)}" data-rab="volume" required></td><td><input type="number" min="0.01" step="any" value="${Number(x.harga_sat_negoisasi || 0)}" data-rab="harga_satuan" required></td><td>${this.money(x.jumlah_negoisasi)}</td><td>${Number(x.bobot || 0).toFixed(2)}%</td></tr>`,
			)
			.join("");
		const scheduleByRab = new Map();
		weeks.forEach((x) => {
			const key = Number(x.rab_id || 0);
			if (!scheduleByRab.has(key)) scheduleByRab.set(key, []);
			scheduleByRab.get(key).push(x);
		});
		const scheduleGroups = rab
			.map((item) => {
				const rows = scheduleByRab.get(Number(item.id)) || [];
				const inputRows = rows.length
					? rows
					: [
							{
								rab_id: item.id,
								minggu_ke: 1,
								tanggal_mulai: d.contract?.tanggal_mulai || "",
								tanggal_selesai: d.contract?.tanggal_selesai || "",
								bobot_rencana: 0,
								bobot_realisasi: 0,
								rencana_kumulatif: 0,
								realisasi_kumulatif: 0,
							},
						];
				return `<section class="contract-schedule-item" data-rab-schedule="${item.id}"><div class="schedule-item-title"><div><span class="ui teal label">RAB ${this.esc(item.nomor)}</span><strong>${this.esc(item.uraian)}</strong><small>Bobot terhadap kontrak: ${Number(item.bobot || 0).toFixed(2)}%</small></div><button class="ui mini basic teal button" data-delivery-action="add-week" data-rab-id="${item.id}"><i class="plus icon"></i>Tambah Periode</button></div><div class="table-wrapper"><table class="ui compact celled table delivery-table"><thead><tr><th>Minggu</th><th>Mulai</th><th>Selesai</th><th>Rencana Item %</th><th>Realisasi Item %</th><th>Kum. Rencana Item</th><th>Kum. Realisasi Item</th><th></th></tr></thead><tbody>${inputRows.map((x) => this.scheduleRow(x, item.id)).join("")}</tbody></table></div></section>`;
			})
			.join("");
		const bars = curve
			.map(
				(x) =>
					`<div class="s-point"><span style="height:${Math.max(2, Number(x.rencana_kumulatif || 0) * 1.4)}px"></span><i style="height:${Math.max(2, Number(x.realisasi_kumulatif || 0) * 1.4)}px"></i><small>M${x.minggu_ke}</small></div>`,
			)
			.join("");
		const docCards =
			(d.documents || [])
				.map(
					(x) =>
						`<div class="ui segment"><b>${this.esc(x.jenis_dokumen)} · ${this.esc(x.judul)}</b><br><small>${this.esc(x.nama_file_asli)} · versi ${x.versi}</small></div>`,
				)
				.join("") || '<div class="ui message">Belum ada dokumen diunggah.</div>';
		$("#contractDeliveryContent")
			.html(`<div class="contract-delivery-heading"><div><h3 class="ui header"><i class="tasks teal icon"></i><div class="content">RAB, Time Schedule & Kurva S<div class="sub header">${this.esc(d.contract?.nomor_kontrak || "")} · ${this.esc(d.contract?.uraian_kontrak || "")}</div></div></h3></div></div><div class="ui top attached tabular menu"><a class="${activeTab === "rab" ? "active " : ""}item" data-tab="rab">RAB</a><a class="${activeTab === "schedule" ? "active " : ""}item" data-tab="schedule">Time Schedule & Kurva S</a><a class="${activeTab === "documents" ? "active " : ""}item" data-tab="documents">Dokumen Terkait</a></div>
			<div class="ui bottom attached ${activeTab === "rab" ? "active " : ""}tab segment" data-tab="rab"><form class="ui form" id="contractRabForm"><div class="ui positive message">Nilai kontrak ${this.money(d.contract?.nilai_kontrak)}. Bobot RAB dihitung otomatis dari jumlah setiap item terhadap total RAB.</div><div class="table-wrapper"><table class="ui compact celled table delivery-table"><thead><tr><th>No</th><th>Uraian</th><th>Satuan</th><th>Volume</th><th>Harga Satuan</th><th>Jumlah</th><th>Bobot</th></tr></thead><tbody id="rabRows">${rabRows}</tbody></table></div><div class="ui buttons"><button type="button" class="ui button" data-delivery-action="add-rab"><i class="plus icon"></i>Tambah Baris</button><button type="button" class="ui primary button" data-delivery-action="save-rab"><i class="save icon"></i>Simpan RAB</button></div></form></div>
			<div class="ui bottom attached ${activeTab === "schedule" ? "active " : ""}tab segment" data-tab="schedule"><form class="ui form" id="contractScheduleForm"><div class="ui info message"><div class="header">Jadwal disusun per item RAB</div><p>Rencana dan realisasi setiap item dicatat per minggu/periode. Kurva S kontrak dihitung dari bobot item RAB × progres kumulatif item.</p></div><div class="s-chart">${bars || '<div class="ui message">Belum ada Kurva S tersimpan.</div>'}</div>${scheduleGroups || '<div class="ui warning message">Simpan RAB terlebih dahulu agar Time Schedule dapat dibuat per item RAB.</div>'}<button type="button" class="ui teal button" data-delivery-action="save-schedule" ${rab.length ? "" : "disabled"}><i class="save icon"></i>Simpan Time Schedule</button></form></div>
			<div class="ui bottom attached ${activeTab === "documents" ? "active " : ""}tab segment" data-tab="documents"><div class="ui info message">Dokumen kontrak disusun per kontrak dan jenis dokumen: Kontrak, SPK, SPMK, SSKK, SSUK, RAB, Jadwal, Kurva S, gambar, BAST, PHO/FHO, adendum, jaminan, dan laporan.</div>${docCards}</div>`);
		$("#contractDeliveryContent .menu .item").tab();
		this.initContractCalendars($("#contractDeliveryContent"));
		if (activeTab === "documents") $("#contractDeliveryContent .menu .item[data-tab=documents]").trigger("click");
	}

	initContractCalendars(container) {
		const scope = container ? $(container) : $(document);
		if (typeof $.fn.calendar !== "function") return;
		scope.find(".ui.calendar.contract-calendar").each(function () {
			const cal = $(this);
			if (cal.data("module-calendar")) return;
			cal.calendar({
				type: "date",
				firstDayOfWeek: 1,
				formatter: {
					date(date) {
						if (!date) return "";
						const pad = (n) => String(n).padStart(2, "0");
						return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
					},
				},
			});
		});
	}

	scheduleRow(x, rabId) {
		return `<tr><td><input type="hidden" value="${rabId}" data-week="rab_id"><input type="number" min="1" step="1" value="${x.minggu_ke || ""}" data-week="minggu_ke" required></td><td><div class="ui calendar contract-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" value="${x.tanggal_mulai || ""}" data-week="tanggal_mulai" required autocomplete="off"></div></div></td><td><div class="ui calendar contract-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" value="${x.tanggal_selesai || ""}" data-week="tanggal_selesai" required autocomplete="off"></div></div></td><td><input type="number" min="0" max="100" step="0.01" value="${Number(x.bobot_rencana || 0)}" data-week="bobot_rencana" required></td><td><input type="number" min="0" max="100" step="0.01" value="${Number(x.bobot_realisasi || 0)}" data-week="bobot_realisasi" required></td><td>${Number(x.rencana_kumulatif || 0).toFixed(2)}%</td><td>${Number(x.realisasi_kumulatif || 0).toFixed(2)}%</td><td><button type="button" class="ui mini red basic icon button" data-delivery-action="remove-week"><i class="trash icon"></i></button></td></tr>`;
	}

	deliveryAction(a, button) {
		if (a === "excel") {
			this.download(`/kontrak/rab_excel?contract_id=${this.contractId}`);
			return;
		}
		if (a === "pdf") {
			this.download(`/kontrak/rab_pdf?contract_id=${this.contractId}`);
			return;
		}
		if (a === "import") {
			$("#rabImportFile").trigger("click");
			return;
		}
		if (a === "add-rab") {
			$("#rabRows").append(
				'<tr><td><input type="hidden" data-rab="id"><input data-rab="nomor" required></td><td><input data-rab="uraian" required></td><td><input data-rab="satuan" required></td><td><input type="number" min="0.000000000001" step="any" data-rab="volume" required></td><td><input type="number" min="0.01" step="any" data-rab="harga_satuan" required></td><td>-</td><td>-</td></tr>',
			);
			return;
		}
		if (a === "add-week") {
			const rabId = Number(button.data("rab-id"));
			const body = $(`[data-rab-schedule="${rabId}"] tbody`);
			const next =
				Math.max(
					0,
					...body
						.find('[data-week="minggu_ke"]')
						.map((_, el) => Number($(el).val() || 0))
						.get(),
				) + 1;
			body.append(
				this.scheduleRow(
					{
						minggu_ke: next,
						tanggal_mulai: this.deliveryData?.contract?.tanggal_mulai || "",
						tanggal_selesai: this.deliveryData?.contract?.tanggal_selesai || "",
						bobot_rencana: 0,
						bobot_realisasi: 0,
					},
					rabId,
				),
			);
			this.initContractCalendars(body);
			return;
		}
		if (a === "remove-week") {
			button.closest("tr").remove();
			return;
		}

		const collect = (selector, key) =>
			$(selector)
				.map((_, tr) => {
					const row = {};
					$(tr)
						.find(`[data-${key}]`)
						.each((__, el) => (row[$(el).data(key)] = $(el).val()));
					return row;
				})
				.get();
		const isRab = a === "save-rab";
		if (!isRab && a !== "save-schedule") return;
		const form = isRab ? $("#contractRabForm") : $("#contractScheduleForm");
		if (window.FormValidation && !FormValidation.validate(form)) return;
		const payload = isRab
			? { contract_id: this.contractId, items: JSON.stringify(collect("#rabRows tr", "rab")) }
			: { contract_id: this.contractId, weeks: JSON.stringify(collect("#contractScheduleForm tbody tr", "week")) };
		button.addClass("loading disabled");
		window.Ajax.request({
			url: isRab ? "/kontrak/rab/save" : "/kontrak/schedule/save",
			method: "POST",
			data: payload,
			success: () => this.openDelivery(this.contractId, isRab ? "rab" : "schedule"),
			complete: () => button.removeClass("loading disabled"),
		});
	}

	importRab(input) {
		const file = input.files?.[0];
		if (!file) return;
		const data = new FormData();
		data.append("contract_id", this.contractId);
		data.append("file", file);
		window.Ajax.request({
			url: "/kontrak/rab/import",
			method: "POST",
			data,
			processData: false,
			contentType: false,
			success: () => this.openDelivery(this.contractId, "rab"),
			complete: () => {
				input.value = "";
			},
		});
	}

	debounce(fn, wait) {
		let t;
		return (...args) => {
			clearTimeout(t);
			t = setTimeout(() => fn(...args), wait);
		};
	}
	esc(v) {
		return String(v ?? "").replace(
			/[&<>'"]/g,
			(c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;" })[c],
		);
	}
	money(v) {
		return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(
			Number(v || 0),
		);
	}
	download(path) {
		window.location.href = window.appUrl ? window.appUrl(path) : path;
	}

	renderReport() {
		window.history.replaceState(
			null,
			"",
			window.appUrl ? window.appUrl("/kontrak?tab=laporan") : "/kontrak?tab=laporan",
		);
		$("#crud-table-container").html('<div class="ui active centered inline loader"></div>');
		window.Ajax.request({ url: "/kontrak/summary", method: "GET", success: (r) => this.drawDashboard(r.data || {}) });
	}

	drawDashboard(data) {
		const t = data.totals || {},
			max = Math.max(...(data.monthly || [0]), 1);
		const bars = (data.monthly || [])
			.map(
				(v, i) =>
					`<div class="p4-bar-wrap"><div class="p4-bar" style="height:${Math.max(4, (v / max) * 150)}px" title="${this.money(v)}"></div><small>${["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"][i]}</small></div>`,
			)
			.join("");
		const statuses = (data.status || [])
			.map((s) => `<div class="p4-status">${this.esc(s.label)}<b>${s.jumlah}</b></div>`)
			.join("");
		$("#crud-table-container").html(
			`<div class="p4-hero"><div><small>MONITORING PELAKSANAAN</small><h2>Kontrak & Realisasi</h2><p>SPK, SPMK, kontrak, dan kemajuan pekerjaan.</p></div><div class="ui buttons"><button class="ui green button" data-p4="report-excel"><i class="file excel icon"></i>Excel + Grafik</button><button class="ui red button" data-p4="report-pdf"><i class="file pdf icon"></i>PDF</button></div></div><div class="p4-cards"><div><small>Kontrak</small><strong>${t.jumlah_kontrak || 0}</strong></div><div><small>Total Anggaran</small><strong>${this.money(t.total_anggaran)}</strong></div><div><small>Nilai Kontrak</small><strong>${this.money(t.nilai_kontrak)}</strong></div><div><small>Realisasi</small><strong>${this.money(t.realisasi)}</strong></div></div><div class="p4-grid"><section><h3>Realisasi per Bulan</h3><div class="p4-chart">${bars}</div></section><section><h3>Status Kontrak</h3>${statuses || '<div class="ui message">Belum ada data.</div>'}</section></div>`,
		);
	}

	styles() {
		if (document.getElementById("phase4Style")) return;
		$("head").append(
			`<style id="phase4Style">.p4-hero{background:linear-gradient(135deg,#112c46,#2185d0);color:#fff;padding:24px;border-radius:14px;display:flex;justify-content:space-between}.p4-cards,.p4-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:14px}.p4-grid{grid-template-columns:2fr 1fr}.p4-cards>div,.p4-grid section{background:#fff;border:1px solid #e6edf5;border-radius:12px;padding:18px;box-shadow:0 8px 24px rgba(20,52,80,.07)}.p4-cards small,.p4-cards strong{display:block;margin-top:6px}.p4-chart{height:190px;display:flex;align-items:flex-end;gap:8px}.p4-bar-wrap{height:170px;flex:1;display:flex;flex-direction:column;justify-content:flex-end;text-align:center}.p4-bar{background:linear-gradient(#54c8ff,#2185d0);border-radius:5px 5px 0 0}.p4-status{padding:12px;border-bottom:1px solid #eee}.p4-status b{float:right}.contract-item-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.contract-scroll{max-height:52vh;overflow:auto;margin-top:12px}.contract-item-card,.contract-selected-card{display:flex;gap:12px;justify-content:space-between;border:1px solid #e3eaf2;border-radius:10px;padding:12px;margin-bottom:9px}.contract-item-card small,.contract-item-card p,.contract-selected-card p,.contract-selected-card small{display:block;margin:5px 0}.contract-selected-card>div{flex:1}.contract-total{position:sticky;bottom:0;background:#f4f0ff;border:1px solid #d7c9ff;border-radius:9px;padding:12px;display:flex;justify-content:space-between}.contract-delivery-page{padding:0 0 24px}.contract-delivery-toolbar{position:sticky;top:0;z-index:20;margin-bottom:14px!important}.contract-delivery-heading{margin:10px 0 14px}.contract-schedule-item{border:1px solid #dfe9ef;border-radius:10px;padding:12px;margin:12px 0;background:#fbfdff}.schedule-item-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.schedule-item-title strong,.schedule-item-title small{display:block;margin-top:4px}.delivery-table input{min-width:90px;width:100%;border:1px solid #d8e0e5;border-radius:4px;padding:7px}.s-chart{min-height:110px;display:flex;align-items:flex-end;gap:6px;overflow-x:auto;margin:12px 0}.s-point{min-width:34px;height:100px;display:flex;align-items:flex-end;gap:2px;position:relative;padding-bottom:18px}.s-point span,.s-point i{display:block;width:12px;max-height:80px;background:#2185d0}.s-point i{background:#21ba45}.s-point small{position:absolute;bottom:0;left:0}@media(max-width:800px){.p4-cards,.p4-grid,.contract-item-grid{grid-template-columns:1fr}.p4-hero,.schedule-item-title{display:block}.contract-delivery-toolbar{position:static}.contract-delivery-toolbar .right.floated{float:none!important;margin-top:8px}.delivery-table{min-width:850px}.table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch}}</style>`,
		);
	}
}
