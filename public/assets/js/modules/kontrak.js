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
		this.realizationItems = [];
		this.realizationContracts = [];
		this.realizationId = 0;
		this.realizationFiles = [];
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
		if (tbl === "realisasi") {
			return `<div class="ui right floated basic buttons" style="margin-top:10px"><button class="ui teal button" data-realization-add><i class="plus icon"></i>Input Realisasi</button><button class="ui icon button" data-action="export" data-tbl="${tbl}" title="Export"><i class="alternate download icon"></i></button></div>`;
		}
		return `${super.buildActionButtons(tbl)}<div class="ui right floated basic icon buttons" style="margin-top:10px;margin-right:8px"><button class="ui button" data-p4="report-excel" title="Laporan Excel"><i class="file excel icon"></i></button><button class="ui button" data-p4="report-pdf" title="Laporan PDF"><i class="chart bar icon"></i></button></div>`;
	}

	rowAction() {
		window.TableRowInjector = (c) => c.tbl === "kontrak"
			? `<button class="ui violet button" data-contract-items="${c.id}" title="Kelola uraian kontrak"><i class="list alternate outline icon"></i></button><button class="ui teal button" data-contract-delivery="${c.id}" title="RAB, Time Schedule, Kurva S, dan dokumen"><i class="tasks icon"></i></button><button class="ui red button" data-contract-pdf="${c.id}" title="Unduh PDF kontrak"><i class="file pdf icon"></i></button>`
			: c.tbl === "realisasi" ? `<button class="ui blue button" data-realization-edit="${c.id}" title="Ubah transaksi dengan rincian lengkap"><i class="edit icon"></i></button>` : "";
	}

	bindActions() {
		$(document).off("click.procurementCreate","[data-procurement-create]").on("click.procurementCreate","[data-procurement-create]",e=>this.openProcurementEditor(Number($(e.currentTarget).data("procurement-create"))))
			.off("click.procurementEdit","[data-procurement-edit]").on("click.procurementEdit","[data-procurement-edit]",e=>this.openProcurementResult(Number($(e.currentTarget).data("procurement-edit"))))
			.off("click.procurementPdf","[data-procurement-pdf]").on("click.procurementPdf","[data-procurement-pdf]",e=>this.download(`/kontrak/procurement/pdf?id=${Number($(e.currentTarget).data("procurement-pdf"))}`))
			.off("click.procurementAttachmentAdd","#addProcurementAttachment").on("click.procurementAttachmentAdd","#addProcurementAttachment",()=>$("#procurementAttachments").append(this.procurementAttachmentRow({})))
			.off("click.procurementAttachmentDelete","[data-procurement-attachment-delete]").on("click.procurementAttachmentDelete","[data-procurement-attachment-delete]",e=>$(e.currentTarget).closest(".procurement-attachment").remove())
			.off("click.procurementSave","#saveProcurementDocument").on("click.procurementSave","#saveProcurementDocument",()=>this.saveProcurementDocument());
		$(document)
			.off("click.realizationAdd", "[data-realization-add]")
			.on("click.realizationAdd", "[data-realization-add]", () => this.openRealization(0));
		$(document).off("click.realizationEdit","[data-realization-edit]").on("click.realizationEdit","[data-realization-edit]",e=>this.openRealization(Number($(e.currentTarget).data("realization-edit"))));
		$(document).off("click.contractPdf","[data-contract-pdf]").on("click.contractPdf","[data-contract-pdf]",e=>this.download(`/kontrak/pdf?id=${Number($(e.currentTarget).data("contract-pdf"))}`));
		$(document).off("click.realizationFileDelete","[data-realization-file-delete]").on("click.realizationFileDelete","[data-realization-file-delete]",e=>{if(!confirm("Hapus file transaksi ini?"))return;window.Ajax.request({url:"/kontrak/realization/file/delete",method:"POST",data:{id:Number($(e.currentTarget).data("realization-file-delete"))},success:()=>this.openRealization(this.realizationId)});});
		$(document)
			.off("click.realizationItems", "#realizationItemsButton")
			.on("click.realizationItems", "#realizationItemsButton", () => this.openRealizationItems());
		$(document)
			.off("input.realizationAmount", "[data-realization-amount], #realizationTotal")
			.on("input.realizationAmount", "[data-realization-amount], #realizationTotal", (e) => {
				if ($(e.target).is("[data-realization-amount]")) this.syncRealizationTotal();
			});
		$(document)
			.off("click.realizationDetail", "[data-realization-detail]")
			.on("click.realizationDetail", "[data-realization-detail]", (e) => this.showRealizationDetail(Number($(e.currentTarget).data("realization-detail"))));
		$(document)
			.off("click.realizationSave", "#realizationSubmit")
			.on("click.realizationSave", "#realizationSubmit", () => this.saveRealization());
		$(document)
			.off("click.realizationClose", ".sidebarkanan .btnFlyoutClose")
			.on("click.realizationClose", ".sidebarkanan .btnFlyoutClose", () => this.resetRealizationSidebar());
		$(document)
			.off("click.phase4", "[data-p4]")
			.on("click.phase4", "[data-p4]", (e) => {
				const a = $(e.currentTarget).data("p4");
				this.download(a === "report-excel" ? "/kontrak/laporan_excel" : "/kontrak/laporan_pdf");
			});
		$(document)
			.off("click.contractItems", "[data-contract-items]")
			.on("click.contractItems", "[data-contract-items]", (e) =>
				this.openItems(Number($(e.currentTarget).data("contract-items"))),
			);
		$(document)
			.off("click.contractDetailButton", "[data-contract-detail-button]")
			.on("click.contractDetailButton", "[data-contract-detail-button]", () => {
				const id = Number($("#form_flyout [name=id_row]").val() || 0);
				this.openItems(id);
			});
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
			.off("change.contractSub", "#contractSubFilter");
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
		const draft = $("#form_flyout [name=contract_items]").val();
		this.contractItems = !id && draft ? (() => { try { return JSON.parse(draft).map((x) => ({ ...x, pagu: Number(x.pagu), nilai_kontrak: Number(x.nilai_kontrak) })); } catch (e) { return []; } })() : [];
		$("#contractItemsModal").modal({ closable: false, allowMultiple: true }).modal("show");
		$("#contractSelectedList").html('<div class="ui active centered inline loader"></div>');
		this.loadSubActivities();
		if (id) window.Ajax.request({ url: `/kontrak/items?contract_id=${id}`, method: "GET", success: (r) => { this.contractItems = (r.data || []).map((x) => ({ ...x, pagu: Number(x.pagu), nilai_kontrak: Number(x.nilai_kontrak) })); this.renderSelected(); this.renderAvailable(); } });
		else { this.renderSelected(); this.renderAvailable(); }
	}

	loadSubActivities() {
		window.Ajax.request({
			url: `/kontrak/available-subactivities?contract_id=${this.contractId || 0}`,
			method: "GET",
			success: (r) => {
				const select = $("#contractSubFilter");
				if (select.hasClass("noselection")) select.dropdown("destroy");
				select
					.html(
						'<option value="">Pilih sub kegiatan dahulu</option>' +
							(r.data || [])
								.map(
									(x) =>
														`<option value="${this.esc(x.kd_sub_keg)}">${this.esc(x.kd_sub_keg)} · ${this.esc(x.nama_sub_kegiatan || "")} · ${this.esc(x.contoh_uraian || "")} · ${(x.sumber || "").toUpperCase()} · ${x.jumlah_uraian} uraian · ${this.money(x.pagu)}</option>`,
								)
								.join(""),
					)
					.dropdown({
						fullTextSearch: true,
						highlightMatches: true,
						onChange: (value) => {
							if (!value) return;
							this.loadAvailable();
							select.dropdown("hide");
							select.next(".ui.dropdown").dropdown("hide");
							setTimeout(() => {
								select.dropdown("hide");
								select.next(".ui.dropdown").dropdown("hide");
							}, 0);
						},
					});
								const count = (r.data || []).length;
								$("#contractSubFilterStatus").text(count ? `${count} sub kegiatan DPA/DPPA tersedia pada tahun aktif.` : "Tidak ada sub kegiatan DPA/DPPA yang disetujui dan dikunci pada tahun aktif.").toggleClass("ui negative message", !count).toggleClass("ui small basic message", !!count);
				if (!count) Toast.show({ success: false, message: "Belum ada sub kegiatan DPA/DPPA yang disetujui dan dikunci pada tahun aktif." });
			},
		});
	}

	openRealization(id = 0) {
		this.realizationId = Number(id || 0);this.realizationItems=[];this.realizationContracts=[];this.realizationFiles=[];this.resetRealizationSidebar();
		$("#content_flyout").text(this.realizationId ? "Ubah Realisasi Kontrak" : "Input Realisasi Kontrak");$("#icon_flyout").attr("class","chart line icon");$(".sidebarkanan").addClass("realization-sidebar-active");$(".flyout-footer").hide();
		$("#form_flyout").html(`<div class="ui form" id="realizationSidebarForm"><div class="field required"><label>Kontrak</label><select class="ui fluid search dropdown" id="realizationContract"><option value="">Pilih kontrak</option></select></div><div class="two fields"><div class="field required"><label>Tanggal Transaksi</label><div class="ui calendar contract-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" id="realizationDate" placeholder="YYYY-MM-DD" autocomplete="off"></div></div></div><div class="field"><label>Realisasi kontrak di luar transaksi ini</label><input type="text" id="realizationPrevious" disabled value="Rp 0"></div></div><div class="field required"><label>Uraian Transaksi</label><textarea id="realizationDescription" rows="2" placeholder="Contoh: Pembayaran termin pekerjaan..."></textarea></div><div class="field required"><label>Jumlah Realisasi</label><input type="number" id="realizationTotal" min="0" step="any" inputmode="decimal" placeholder="0"></div><button type="button" class="ui fluid violet button" id="realizationItemsButton" disabled><i class="list alternate outline icon"></i>Atur Uraian Realisasi</button><div class="field"><label>Keterangan</label><textarea id="realizationNote" rows="2"></textarea></div><div class="field ${this.realizationId?'':'required'}"><label>File Transaksi (PDF/gambar/XLSX/DOCX, masing-masing maks. 15 MB)</label><input type="file" id="realizationFiles" multiple ${this.realizationId?'':'required'} accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.docx"><div id="realizationExistingFiles" class="ui relaxed divided list"></div></div><div class="ui info message">Tambah dan edit memakai properti yang sama. Nilai per uraian diatur melalui tombol di atas; file lama dapat diunduh atau dihapus lalu diganti.</div><button type="button" class="ui fluid teal button" id="realizationSubmit"><i class="check icon"></i>${this.realizationId?'Simpan Perubahan':'Simpan Realisasi'}</button></div>`);
		this.initContractCalendars($("#form_flyout"));$(".sidebarkanan").sidebar("show");
		const load=(detail=null)=>window.Ajax.request({url:"/kontrak/realization-contracts",method:"GET",success:r=>{this.realizationContracts=r.data||[];const select=$("#realizationContract");select.html('<option value="">Pilih kontrak</option>'+this.realizationContracts.map(x=>`<option value="${x.id}">${this.esc(x.nomor_kontrak)} · ${this.money(x.nilai_kontrak)}</option>`).join(""));if(select.hasClass("noselection"))select.dropdown("destroy");select.dropdown({fullTextSearch:true,highlightMatches:true,onChange:value=>this.loadRealizationContract(Number(value||0),detail)});if(detail){select.dropdown("set selected",String(detail.contract_id));select.closest('.field').toggleClass('disabled',true);select.prop('disabled',true);}}});
		if(this.realizationId)window.Ajax.request({url:`/kontrak/realization/detail?id=${this.realizationId}`,method:"GET",success:r=>{const d=r.data||{};this.realizationFiles=d.files||[];$("#realizationDate").val(d.tanggal||"");$("#realizationDescription").val(d.uraian_transaksi||"");$("#realizationNote").val(d.keterangan||"");$("#realizationTotal").val(Number(d.jumlah||0));this.renderRealizationFiles();load(d);}});else load();
	}

	renderRealizationFiles(){const list=$("#realizationExistingFiles");if(!list.length)return;list.html(this.realizationFiles.length?this.realizationFiles.map(f=>`<div class="item"><i class="file outline icon"></i><div class="content"><div class="header">${this.esc(f.nama_file_asli)}</div><div class="description">${this.money(Number(f.ukuran||0)/1024).replace('Rp','')} KB &nbsp; <a href="${window.appUrl?window.appUrl(`/kontrak/realization/file/download?id=${f.id}`):`/kontrak/realization/file/download?id=${f.id}`}"><i class="download icon"></i>Unduh</a> &nbsp; <a href="#" data-realization-file-delete="${f.id}"><i class="trash icon"></i>Hapus</a></div></div></div>`).join(""):'<div class="ui tiny warning message">Belum ada file transaksi.</div>');}

	loadRealizationContract(id, detail = null) {
		this.contractId = id || null;
		$("#realizationItemsButton").prop("disabled", !id);
		if (!id) {
			$("#realizationPrevious").val("Rp 0");
			this.realizationItems = [];
			return;
		}
		const contract = this.realizationContracts.find((x) => Number(x.id) === id);
		$("#realizationPrevious").val(this.money(contract?.realisasi || 0));
		window.Ajax.request({ url: `/kontrak/realization-items?contract_id=${id}`, method: "GET", success: (r) => {
			this.realizationItems = (r.data?.items || []).map((x) => ({ ...x, jumlah_sekarang: 0, progress_fisik: 0 }));
			if(detail){const chosen=new Map((detail.items||[]).map(x=>[`${x.tahap}:${x.anggaran_id}`,x]));this.realizationItems.forEach(x=>{const old=chosen.get(`${x.tahap}:${x.anggaran_id}`);if(old){x.realisasi=Math.max(0,Number(x.realisasi||0)-Number(old.jumlah||0));x.jumlah_sekarang=Number(old.jumlah||0);x.progress_fisik=Number(old.progress_fisik||0);}});}
			const current = Number(r.data?.contract?.nilai_kontrak || contract?.nilai_kontrak || 0);
			$("#realizationPrevious").val(this.money(this.realizationItems.reduce((sum, x) => sum + Number(x.realisasi || 0), 0)));
			$("#realizationTotal").attr("max", Math.max(0, current));
		} });
	}

	openRealizationItems() {
		if (!this.contractId || !this.realizationItems.length) return Toast.error("Pilih kontrak yang memiliki uraian kontrak");
		if (!(Number($("#realizationTotal").val()) > 0)) Toast.show({ success: false, message: "Isi jumlah realisasi transaksi terlebih dahulu atau masukkan nilainya dari tabel uraian." });
		this.ensureRealizationModal();
		this.renderRealizationItems();
		$("#realizationItemsModal").modal({ closable: false, allowMultiple: true }).modal("show");
	}

	ensureRealizationModal() {
		if ($("#realizationItemsModal").length) return;
		$("body").append(`<div class="ui large modal" id="realizationItemsModal"><i class="close icon"></i><div class="header"><i class="list alternate outline icon"></i> Uraian Realisasi per Kontrak</div><div class="content"><div class="ui info message">Nilai realisasi dan persentase fisik diisi per uraian. Persentase keuangan dihitung otomatis dari nilai kontrak.</div><div class="table-wrapper realization-items-table-wrapper"><table class="ui compact celled striped table"><thead><tr><th>DPA/DPPA</th><th>Anggaran</th><th>Nilai Kontrak</th><th>Realisasi Sekarang</th><th>Realisasi</th><th>% Keuangan</th><th>% Fisik</th><th>Aksi</th></tr></thead><tbody id="realizationItemsBody"></tbody><tfoot><tr><th colspan="3" class="right aligned">Total</th><th id="realizationItemsTotal" class="right aligned">Rp 0</th><th colspan="4"></th></tr></tfoot></table></div></div><div class="actions"><button class="ui deny button">Tutup</button><button class="ui violet button" data-realization-items-done><i class="check icon"></i>Selesai</button></div></div>`);
		$(document).off("click.realizationItemsDone", "[data-realization-items-done]").on("click.realizationItemsDone", "[data-realization-items-done]", () => { this.syncRealizationTotal(); $("#realizationItemsModal").modal("hide"); });
	}

	renderRealizationItems() {
		$("#realizationItemsBody").html(this.realizationItems.map((x, i) => `<tr><td><span class="ui tiny ${x.tahap === "dppa" ? "orange" : "blue"} label">${this.esc(x.tahap.toUpperCase())}</span><br><small>${this.esc(x.kd_sub_keg)}</small></td><td><b>${this.esc(x.kd_akun)}</b><br>${this.esc(x.uraian)}<br><small>${this.money(x.pagu)}</small></td><td class="right aligned">${this.money(x.nilai_kontrak)}</td><td><input class="ui input" type="number" min="0" max="${Math.max(0, Number(x.nilai_kontrak) - Number(x.realisasi || 0))}" step="any" inputmode="decimal" data-realization-amount="${i}" value="${Number(x.jumlah_sekarang || 0)}"></td><td class="right aligned">${this.money(x.realisasi)}</td><td class="right aligned" data-realization-percent="${i}">${Number(x.persen_keuangan || 0).toFixed(2)}%</td><td><input class="ui input" type="number" min="0" max="100" step="0.01" data-realization-physical="${i}" value="${Number(x.progress_fisik || 0)}"></td><td><button type="button" class="ui mini basic teal button" data-realization-detail="${i}"><i class="info circle icon"></i>Detail</button></td></tr>`).join(""));
		$("[data-realization-amount]").on("input", (e) => { const i = Number($(e.currentTarget).data("realization-amount")); this.realizationItems[i].jumlah_sekarang = Number(e.currentTarget.value || 0); const total = Number(this.realizationItems[i].realisasi || 0) + this.realizationItems[i].jumlah_sekarang; $("[data-realization-percent='" + i + "']").text((this.realizationItems[i].nilai_kontrak > 0 ? total / this.realizationItems[i].nilai_kontrak * 100 : 0).toFixed(2) + "%"); this.syncRealizationTotal(); });
		$("[data-realization-physical]").on("input", (e) => { const i = Number($(e.currentTarget).data("realization-physical")); this.realizationItems[i].progress_fisik = Number(e.currentTarget.value || 0); });
		this.syncRealizationTotal();
	}

	syncRealizationTotal() {
		const total = this.realizationItems.reduce((sum, x) => sum + Number(x.jumlah_sekarang || 0), 0);
		$("#realizationItemsTotal").text(this.money(total));
		if ($("#realizationTotal").length && document.activeElement?.id !== "realizationTotal") $("#realizationTotal").val(total ? total : "");
	}

	showRealizationDetail(index) {
		const x = this.realizationItems[index];
		if (!x) return;
		$("#realizationDetailModal").remove();
		$("body").append(`<div class="ui small modal" id="realizationDetailModal"><i class="close icon"></i><div class="header"><i class="sitemap icon"></i> Detail Uraian Belanja</div><div class="content"><table class="ui definition compact table"><tbody><tr><td>Program</td><td>${this.esc(x.program || "-")}</td></tr><tr><td>Kegiatan</td><td>${this.esc(x.kegiatan || "-")}</td></tr><tr><td>Sub Kegiatan</td><td>${this.esc(x.sub_kegiatan || x.kd_sub_keg || "-")}</td></tr><tr><td>Kode Rekening Belanja</td><td>${this.esc(x.rekening || x.kd_akun || "-")}</td></tr><tr><td>Uraian</td><td>${this.esc(x.uraian || "-")}</td></tr><tr><td>Realisasi transaksi</td><td>${this.money(x.jumlah_sekarang)}</td></tr></tbody></table></div><div class="actions"><button class="ui approve teal button">Tutup</button></div></div>`);
		$("#realizationDetailModal").modal("show");
	}

	saveRealization() {
		const contractId = Number($("#realizationContract").val() || 0), total = Number($("#realizationTotal").val() || 0), rows = this.realizationItems.map((x) => ({ tahap: x.tahap, anggaran_id: x.anggaran_id, jumlah: Number(x.jumlah_sekarang || 0), progress_fisik: Number(x.progress_fisik || 0) }));
		const distributed = rows.reduce((sum, x) => sum + x.jumlah, 0);
		if (!contractId || !$("#realizationDate").val() || !String($("#realizationDescription").val() || "").trim()) return Toast.error("Kontrak, tanggal, uraian transaksi, dan uraian per item wajib diisi");
		if (total <= 0 || Math.abs(total - distributed) > 0.01) return Toast.error("Jumlah realisasi harus sama dengan total pembagian pada uraian");
		const selected=Array.from($("#realizationFiles")[0]?.files||[]);if(!this.realizationId&&!selected.length)return Toast.error("File transaksi wajib ditambahkan saat input realisasi");
		const form=new FormData();form.set("realization_id",this.realizationId);form.set("contract_id",contractId);form.set("tanggal",$("#realizationDate").val());form.set("uraian_transaksi",$("#realizationDescription").val());form.set("keterangan",$("#realizationNote").val());form.set("items",JSON.stringify(rows));selected.forEach(file=>form.append("transaction_files[]",file));
		const button = $("#realizationSubmit").addClass("loading disabled");
		window.Ajax.request({ url: "/kontrak/realization/save", method: "POST", data:form,processData:false,contentType:false, success: () => { this.resetRealizationSidebar(); $(".sidebarkanan").sidebar("hide"); window.tableManager?.fetchData(); }, complete: () => button.removeClass("loading disabled") });
	}

	resetRealizationSidebar() {
		$(".sidebarkanan").removeClass("realization-sidebar-active");
		$(".flyout-footer").show();
	}

	ensureModal() {
		if ($("#contractItemsModal").length) return;
		$("body").append(
			`<div class="ui large modal" id="contractItemsModal"><i class="close icon"></i><div class="header"><i class="violet list alternate outline icon"></i> Rincian Uraian Kontrak</div><div class="content"><div class="ui info message"><div class="header">Multi sub kegiatan dan multi uraian</div><p>Pilih subkegiatan berdasarkan kode atau nama, lalu cari uraian, kode rekening, atau nilai pagu. Maksimal 50 hasil per pencarian agar tetap ringan.</p></div><div class="contract-item-grid"><section><div class="field"><label>Sub Kegiatan</label><select class="ui fluid search dropdown" id="contractSubFilter"><option value="">Pilih sub kegiatan dahulu</option></select><div id="contractSubFilterStatus" class="ui small basic message">Memuat sub kegiatan tahun aktif...</div></div><div class="ui fluid icon input" style="margin-top:9px"><input id="contractItemSearch" placeholder="Cari uraian, rekening, atau nilai anggaran..."><i class="search icon"></i></div><div id="contractAvailableList" class="contract-scroll"><div class="ui message">Pilih sub kegiatan untuk menampilkan uraian.</div></div></section><section><h4 class="ui header">Uraian terpilih</h4><div id="contractSelectedList" class="contract-scroll"></div></section></div></div><div class="actions"><button class="ui deny button">Tutup</button><button class="ui positive violet button" data-contract-item-action="save"><i class="save icon"></i>Simpan Rincian</button></div></div>`,
		);
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
						`<article class="contract-item-card"><div><span class="ui tiny ${x.tahap === "dppa" ? "orange" : "blue"} label">${this.esc(x.tahap.toUpperCase())}</span><b>${this.esc(x.kd_sub_keg)}</b><small>${this.esc(x.kd_akun || "Tanpa kode akun")}</small><p>${this.esc(x.uraian)}</p><small>Pagu ${this.money(x.pagu)} · Sudah berkontrak ${this.money(x.nilai_terpakai)} · Sisa ${this.money(x.pagu_tersedia)}</small><strong>Sisa pagu ${this.money(x.pagu_tersedia)}</strong></div><button class="ui mini violet icon button" data-contract-item-action="add" data-index="${x._index}"><i class="plus icon"></i></button></article>`,
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
				return `<article class="contract-selected-card"><button class="ui mini red basic icon button" data-contract-item-action="remove" data-index="${i}"><i class="trash icon"></i></button><div><b>${this.esc(x.kd_sub_keg)} · ${this.esc(x.kd_akun || "")}</b><p>${this.esc(x.uraian)}</p><small>Pagu ${this.money(x.pagu)} · Sudah berkontrak ${this.money(x.nilai_terpakai || 0)} · Sisa tersedia ${this.money(x.pagu_tersedia || x.pagu)}</small><div class="ui right labeled fluid input"><label class="ui label">Nilai</label><input type="number" min="1" max="${Number(x.pagu_tersedia || x.pagu)}" value="${Number(x.nilai_kontrak || 0)}" data-contract-value="${i}"><div class="ui label">IDR</div></div></div></article>`;
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
			const items = this.contractItems.map((x) => ({
				tahap: x.tahap,
				anggaran_id: x.anggaran_id,
				kd_sub_keg: x.kd_sub_keg,
				kd_akun: x.kd_akun,
				uraian: x.uraian,
				pagu: Number(x.pagu || 0),
				nilai_terpakai: Number(x.nilai_terpakai || 0),
				pagu_tersedia: Number(x.pagu_tersedia || x.pagu || 0),
				nilai_kontrak: Number(x.nilai_kontrak || 0),
			}));
			if (!this.contractId) {
				const first = this.contractItems[0];
				$("#form_flyout [name=contract_items]").val(JSON.stringify(items));
				$("#form_flyout [name=tahap]").val(first.tahap);
				$("#form_flyout [name=anggaran_id]").val(first.anggaran_id);
				$("#form_flyout [name=kd_sub_keg]").val(first.kd_sub_keg);
				$("#form_flyout [name=total_anggaran]").val(this.contractItems.reduce((sum, x) => sum + Number(x.pagu || 0), 0));
				$("#form_flyout [name=nilai_kontrak]").val(this.contractItems.reduce((sum, x) => sum + Number(x.nilai_kontrak || 0), 0));
				$("#contractItemsModal").modal("hide");
				return;
			}
			btn.addClass("loading disabled");
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
		$("#contractDeliveryContent .tabular.menu").append(`<a class="${activeTab === "procurement" ? "active " : ""}item" data-tab="procurement">Buat Dokumen Pengadaan</a>`);
		$("#contractDeliveryContent").append(`<div class="ui bottom attached ${activeTab === "procurement" ? "active " : ""}tab segment" data-tab="procurement"><div id="procurementDocumentContent"><div class="ui active centered inline loader"></div></div></div>`);
		$("#contractDeliveryContent .menu .item").tab();
		this.initContractCalendars($("#contractDeliveryContent"));
		if (activeTab === "documents") $("#contractDeliveryContent .menu .item[data-tab=documents]").trigger("click");
		this.loadProcurementDocuments();
	}

	loadProcurementDocuments() {
		window.Ajax.request({url:`/kontrak/procurement/templates?contract_id=${this.contractId}`,method:"GET",success:r=>{const d=r.data||{},templates=d.templates||[];window.Ajax.request({url:`/kontrak/procurement/documents?contract_id=${this.contractId}`,method:"GET",success:x=>{const docs=x.data||[];const groups={};templates.forEach(t=>(groups[t.kelompok]??=[]).push(t));$("#procurementDocumentContent").html(`<div class="ui info message"><div class="header">Bentuk kontrak yang direkomendasikan: ${(d.recommended_form||'-').replaceAll('_',' ')}</div><p>Rekomendasi dihitung otomatis dari cara/jenis/metode pengadaan dan nilai kontrak menurut Pasal 28 Perpres 46/2025. Master disalin menjadi dokumen hasil sehingga narasi dapat diedit tanpa mengubah master.</p></div><div class="ui stackable two column grid"><div class="column"><h4 class="ui header">Master Dokumen</h4>${Object.entries(groups).map(([name,rows])=>`<div class="ui segment"><div class="ui tiny teal ribbon label">${this.esc(name)}</div><div class="ui relaxed divided list">${rows.map(t=>`<div class="item"><div class="right floated content"><button class="ui mini ${t.direkomendasikan&&t.sesuai_nilai?'green':'basic'} button" data-procurement-create="${t.id}"><i class="edit icon"></i>Buat</button></div><div class="content"><b>${this.esc(t.nama)}</b>${t.direkomendasikan?'<span class="ui mini green label">Direkomendasikan</span>':''}<div class="description">${this.esc(t.sumber_format||'')} · ${this.esc(t.versi)}</div></div></div>`).join('')}</div></div>`).join('')}</div><div class="column"><h4 class="ui header">Dokumen Hasil Kontrak</h4>${docs.map(doc=>`<div class="ui segment"><div class="ui right floated mini buttons"><button class="ui basic blue button" data-procurement-edit="${doc.id}"><i class="edit icon"></i>Edit</button><button class="ui red button" data-procurement-pdf="${doc.id}"><i class="file pdf icon"></i>PDF</button></div><b>${this.esc(doc.judul)}</b><div>${this.esc(doc.nomor_dokumen||'-')} · ${this.esc(doc.tanggal_dokumen)} · <span class="ui tiny label">${this.esc(doc.status)}</span></div><small>Master versi ${this.esc(doc.versi_master)} · ${this.esc(doc.pembuat_role||'')}</small></div>`).join('')||'<div class="ui message">Belum ada dokumen hasil.</div>'}</div></div>`);}});}});
	}

	openProcurementEditor(masterId) {window.Ajax.request({url:`/kontrak/procurement/draft?contract_id=${this.contractId}&master_id=${masterId}`,method:"GET",success:r=>this.showProcurementEditor(r.data||{})});}
	openProcurementResult(id) {window.Ajax.request({url:`/kontrak/procurement/documents?id=${id}`,method:"GET",success:r=>this.showProcurementEditor(r.data||{})});}
	showProcurementEditor(data) {
		$("#procurementEditorModal").remove();const master=data.master||{},contract=data.contract||this.deliveryData?.contract||{},sections=data.sections||[],attachments=data.attachments||[];$("body").append(`<div class="ui large modal" id="procurementEditorModal"><i class="close icon"></i><div class="header">Editor Dokumen Pengadaan</div><div class="scrolling content"><form class="ui form" id="procurementDocumentForm"><input type="hidden" name="id" value="${data.id||''}"><input type="hidden" name="contract_id" value="${this.contractId}"><input type="hidden" name="master_id" value="${data.master_id||master.id||''}"><div class="three fields"><div class="required field"><label>Judul</label><input name="judul" value="${this.esc(data.judul||master.nama||'')}"></div><div class="field"><label>Nomor</label><input name="nomor_dokumen" value="${this.esc(data.nomor_dokumen||contract.nomor_kontrak||'')}"></div><div class="required field"><label>Tanggal</label><div class="ui calendar contract-calendar"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" name="tanggal_dokumen" value="${this.esc(data.tanggal_dokumen||contract.tanggal_kontrak||'')}" autocomplete="off"></div></div></div></div><div class="field"><label>Status</label><select class="ui dropdown" name="status">${['DRAFT','DIAJUKAN','DISETUJUI','DITANDATANGANI'].map(s=>`<option ${s===(data.status||'DRAFT')?'selected':''}>${s}</option>`).join('')}</select></div><div class="ui warning message">Gunakan variabel seperti <code>{{nama_paket}}</code>, <code>{{nilai_kontrak}}</code>, <code>{{nama_ppk}}</code>, dan <code>{{nama_penyedia}}</code>. Isinya diisi otomatis saat PDF dibuat.</div>${sections.map((s,i)=>`<div class="field required"><label>${i+1}. ${this.esc(s.judul)} ${s.petunjuk_edit?`<small>— ${this.esc(s.petunjuk_edit)}</small>`:''}</label><input type="hidden" data-section-code value="${this.esc(s.kode_bagian)}"><textarea data-section-body rows="8">${this.esc(s.isi||s.isi_template||'')}</textarea></div>`).join('')}<div class="ui clearing divider"></div><button type="button" class="ui right floated small button" id="addProcurementAttachment"><i class="plus icon"></i>Tambah Lampiran Narasi</button><h4 class="ui header">Lampiran Dokumen</h4><div id="procurementAttachments">${attachments.map(a=>this.procurementAttachmentRow(a)).join('')}</div></form></div><div class="actions"><button class="ui deny button">Batal</button><button class="ui primary button" id="saveProcurementDocument"><i class="save icon"></i>Simpan Dokumen</button></div></div>`);$("#procurementEditorModal .ui.dropdown").dropdown();this.initContractCalendars($("#procurementEditorModal"));$("#procurementEditorModal").modal({closable:false}).modal("show");
	}
	procurementAttachmentRow(a) {return `<div class="ui segment procurement-attachment"><button type="button" class="ui right floated mini red icon button" data-procurement-attachment-delete><i class="trash icon"></i></button><div class="two fields"><div class="field"><label>Jenis</label><input data-attachment-type value="${this.esc(a.jenis_lampiran||'NARASI')}"></div><div class="field"><label>Judul Lampiran</label><input data-attachment-title value="${this.esc(a.judul||'')}"></div></div><div class="field"><label>Isi Lampiran</label><textarea data-attachment-body rows="6">${this.esc(a.isi||'')}</textarea></div></div>`;}
	saveProcurementDocument() {const f=$("#procurementDocumentForm"),sections=[],attachments=[];f.find('[data-section-code]').each(function(){const code=$(this).val(),box=$(this).siblings('[data-section-body]');sections.push({kode_bagian:code,isi:box.val()});});f.find('.procurement-attachment').each(function(){attachments.push({jenis_lampiran:$(this).find('[data-attachment-type]').val(),judul:$(this).find('[data-attachment-title]').val(),isi:$(this).find('[data-attachment-body]').val()});});const payload={id:f.find('[name=id]').val(),contract_id:f.find('[name=contract_id]').val(),master_id:f.find('[name=master_id]').val(),judul:f.find('[name=judul]').val(),nomor_dokumen:f.find('[name=nomor_dokumen]').val(),tanggal_dokumen:f.find('[name=tanggal_dokumen]').val(),status:f.find('[name=status]').val(),sections:JSON.stringify(sections),attachments:JSON.stringify(attachments)};window.Ajax.request({url:"/kontrak/procurement/save",method:"POST",data:payload,success:r=>{if(r?.success){$("#procurementEditorModal").modal("hide");this.loadProcurementDocuments();}}});}

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
		const wrapped = (...args) => {
			clearTimeout(t);
			t = setTimeout(() => fn(...args), wait);
		};
		wrapped.cancel = () => clearTimeout(t);
		return wrapped;
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
