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
		this.bindUpload();
		const detailId = Number(new URLSearchParams(window.location.search).get("detail"));
		if (Number.isSafeInteger(detailId) && detailId > 0) {
			this.renderLayout();
			this.bindExport();
			this.bindActions();
			this.openDelivery(detailId);
			return;
		}
		super.init();
		this.bindActions();
	}

	documentUploadForm() {
		return `<form class="ui form segment" id="contractDocumentForm"><h4 class="ui header"><i class="paperclip icon"></i>Tambah File Paket</h4><div class="two fields"><div class="field"><label>Jenis Dokumen</label><select name="jenis_dokumen" class="ui dropdown">${["KONTRAK","SPK","SPMK","SSKK","SSUK","RAB","JADWAL","KURVA_S","GAMBAR","BAST","PHO","FHO","ADENDUM","JAMINAN","LAPORAN","LAINNYA"].map(x=>`<option value="${x}">${x.replaceAll("_"," ")}</option>`).join("")}</select></div><div class="required field"><label>Judul Dokumen</label><input name="judul" required maxlength="255"></div></div><div class="two fields"><div class="field"><label>Nomor Dokumen</label><input name="nomor_dokumen" maxlength="255"></div><div class="field"><label>Tanggal Dokumen</label><div class="ui calendar contract-calendar"><div class="ui input left icon"><i class="calendar icon"></i><input name="tanggal_dokumen" placeholder="YYYY-MM-DD" autocomplete="off"></div></div></div></div><div class="required field"><label>File (maks. 25 MB)</label><input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xlsm,.docx,.zip"></div><div class="field"><label>Keterangan</label><textarea name="keterangan" rows="2"></textarea></div><div class="ui error message"></div><button type="submit" class="ui teal button"><i class="upload icon"></i>Upload Dokumen</button></form>`;
	}

	uploadedDocumentCards(docs, canManage) {
		return docs.map(x=>`<div class="ui clearing segment" data-uploaded-document="${Number(x.id)}"><div class="ui right floated mini buttons"><a class="ui basic blue button" href="${this.esc(window.appUrl ? window.appUrl(`/kontrak/document/download?id=${Number(x.id)}`) : `/kontrak/document/download?id=${Number(x.id)}`)}"><i class="download icon"></i>Unduh</a>${canManage?`<button type="button" class="ui basic red button" data-contract-document-delete="${Number(x.id)}"><i class="trash icon"></i>Hapus</button>`:""}</div><b>${this.esc(x.jenis_dokumen)} · ${this.esc(x.judul)}</b><div>${this.esc(x.nama_file_asli)} · versi ${Number(x.versi || 1)}</div>${x.keterangan?`<div class="description">${this.esc(x.keterangan)}</div>`:""}</div>`).join("") || '<div class="ui message">Belum ada dokumen diunggah.</div>';
	}

	bindUpload() {
		$(document)
			.off("submit.contractDocument", "#contractDocumentForm")
			.on("submit.contractDocument", "#contractDocumentForm", (e) => {
				e.preventDefault();
				if (window.FormValidation && !FormValidation.validate(e.currentTarget)) return;
				const form = new FormData(e.currentTarget);
				if (form.get("file")?.size > 25 * 1024 * 1024) {
					$(e.currentTarget).addClass("error").find(".ui.error.message").text("Ukuran file maksimal 25 MB.");
					return;
				}
				form.set("contract_id", this.contractId);
				const button = $(e.currentTarget).find("button[type=submit]").addClass("loading disabled");
				window.Ajax.request({
					url: "/kontrak/document/upload",
					method: "POST",
					data: form,
					processData: false,
					contentType: false,
					success: r => { if (r?.success) this.openDelivery(this.contractId, "documents"); },
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
        if(!window.contractSettingsSubmitGuard){
            document.addEventListener('submit',e=>{if(e.target.id==='form_flyout'&&e.target.dataset.contractSettings==='1'){e.preventDefault();e.stopImmediatePropagation();}},true);
            window.contractSettingsSubmitGuard=true;
        }
        $(document).off('click.contractSettings','[data-rab-settings],[data-rab-import-settings],[data-offer-settings]').on('click.contractSettings','[data-rab-settings],[data-rab-import-settings],[data-offer-settings]',e=>{const b=$(e.currentTarget);if(b.is('[data-rab-settings]'))this.openRabSettings();else if(b.is('[data-rab-import-settings]'))this.openRabImportSettings();else this.openOfferSettings();});
        $(document).off('change.rabSidebarImport','#rabImportSidebarFile').on('change.rabSidebarImport','#rabImportSidebarFile',e=>this.importRab(e.currentTarget));
        $(document).off('click.rabStage','[data-rab-stage]').on('click.rabStage','[data-rab-stage]',e=>{const stage=$(e.currentTarget).data('rab-stage');if(stage===(this.rabStage||'negoisasi'))return;if(this.rabDirty&&!confirm('Perubahan rincian belum disimpan. Pindah tahap dan abaikan perubahan?'))return;this.rabDirty=false;this.rabStage=stage;this.renderDelivery('rab');});
        $(document).off('input.rabDirty','#rabRows :input').on('input.rabDirty','#rabRows :input',()=>this.rabDirty=true);
        $(document).off('click.rabRound','[data-rab-round],[data-rab-round-reset]').on('click.rabRound','[data-rab-round],[data-rab-round-reset]',e=>{if(this.rabDirty){Toast.error('Simpan rincian RAB sebelum membulatkan total.');return;}const button=$(e.currentTarget).addClass('loading disabled');window.Ajax.request({url:'/kontrak/rab/total',method:'POST',data:{contract_id:this.contractId,stage:this.rabStage||'negoisasi',nilai:button.is('[data-rab-round-reset]')?'':$('#rabManualTotal').val()},success:()=>{this.closeContractSettings();this.openDelivery(this.contractId,'rab');},complete:()=>button.removeClass('loading disabled')});});
        $(document).off('change.contractBudget','#form_flyout [name=tanggal_kontrak],#form_flyout [name=kd_opd]').on('change.contractBudget','#form_flyout [name=tanggal_kontrak],#form_flyout [name=kd_opd]',()=>this.loadBudgetContext($('#form_flyout')));
		$(document).off("change.contractType",'#form_flyout [name=cara_pengadaan],#form_flyout [name=jenis_pengadaan]').on("change.contractType",'#form_flyout [name=cara_pengadaan],#form_flyout [name=jenis_pengadaan]',()=>this.renderContractTypes($("#form_flyout")));
		$(document).off("form:rendered.contractProcess").on("form:rendered.contractProcess",(_e,target)=>{$(target).removeAttr('data-contract-settings');this.loadProcessForm($(target));this.loadBudgetContext($(target));});
		$(document).off("form:populated.contractProcess").on("form:populated.contractProcess",(_e,tbl,data,target)=>{if(tbl==="kontrak"){$(target).attr('data-saved-contract-type',data?.jenis_kontrak||'');this.loadProcessForm($(target));this.loadBudgetContext($(target));}});
		$(document).off("change.contractProcess input.contractProcess",'#form_flyout [name=cara_pengadaan],#form_flyout [name=metode_pemilihan],#form_flyout [name=tipe_swakelola]')
			.on("change.contractProcess input.contractProcess",'#form_flyout [name=cara_pengadaan],#form_flyout [name=metode_pemilihan],#form_flyout [name=tipe_swakelola]',()=>this.renderProcessForm($("#form_flyout")));
		$(document).off("input.processValue change.processValue","#contractProcessFields [data-process-value]").on("input.processValue change.processValue","#contractProcessFields [data-process-value]",e=>{
			const input=$(e.currentTarget),code=input.closest('[data-process-code]').attr('data-process-code');
			this.processValues[code]??={};this.processValues[code][input.attr('data-process-value')]=input.val();this.serializeProcessForm($("#form_flyout"));
		});
		$(document).off("click.swakelolaGenerate","#generateAllSwakelola").on("click.swakelolaGenerate","#generateAllSwakelola",()=>this.generateAllSwakelola());
		$(document).off("click.contractPrintSettings","#contractPrintSettings").on("click.contractPrintSettings","#contractPrintSettings",()=>this.openPrintSettings());
		$(document).off("click.procurementBundle","#generateProcurementBundle").on("click.procurementBundle","#generateProcurementBundle",()=>this.generateProcurementBundle());
		$(document).off("input.rabDescription",'#rabRows textarea[data-rab="uraian"]').on("input.rabDescription",'#rabRows textarea[data-rab="uraian"]',()=>this.resizeRabDescriptions());
		$(document).off("input.rabTotals",'#rabRows [data-rab="volume"],#rabRows [data-rab="harga_satuan"],#rabRows [data-rab="pajak"]').on("input.rabTotals",'#rabRows [data-rab="volume"],#rabRows [data-rab="harga_satuan"],#rabRows [data-rab="pajak"]',()=>this.recalculateRabTotals());
		$(document).off("click.procurementCreate","[data-procurement-create]").on("click.procurementCreate","[data-procurement-create]",e=>this.openProcurementEditor(Number($(e.currentTarget).data("procurement-create"))))
			.off("click.procurementEdit","[data-procurement-edit]").on("click.procurementEdit","[data-procurement-edit]",e=>this.openProcurementResult(Number($(e.currentTarget).data("procurement-edit"))))
			.off("click.procurementPdf","[data-procurement-pdf]").on("click.procurementPdf","[data-procurement-pdf]",e=>this.download(`/kontrak/procurement/pdf?id=${Number($(e.currentTarget).data("procurement-pdf"))}`))
			.off("click.procurementDelete","[data-procurement-delete]").on("click.procurementDelete","[data-procurement-delete]",e=>{const id=Number($(e.currentTarget).data("procurement-delete"));if(!confirm("Hapus dokumen hasil kontrak ini?"))return;window.Ajax.request({url:"/kontrak/procurement/delete",method:"POST",data:{id},success:r=>{if(r?.success)this.loadProcurementDocuments();}});})
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
			.off("click.realizationClose", ".sidebarkanan .btnFlyoutClose")
			.on("click.realizationClose", ".sidebarkanan .btnFlyoutClose", () => this.resetRealizationSidebar());
		$(document)
			.off("click.realizationExport", "[data-realization-export]")
			.on("click.realizationExport", "[data-realization-export]", (e) => {
				const [format, type] = String($(e.currentTarget).data("realization-export")).split(":");
				const month = Number(this.reportMonth || new Date().getMonth() + 1);
				const monthQuery = format.startsWith("fisik_keuangan_") ? `&month=${month}` : "";
				const endpoint = type === "xlsx" ? "excel" : type;
				this.download(`/kontrak/financial_${endpoint}?format=${encodeURIComponent(format)}${monthQuery}`);
			});
		$(document)
			.off("change.realizationReportMonth", "[data-report-month]")
			.on("change.realizationReportMonth", "[data-report-month]", (e) => {
				const month = Number(e.currentTarget.value);
				if (month < 1 || month > 12) return;
				const params = new URLSearchParams(window.location.search);
				params.set("month", String(month));
				const url = window.appUrl ? window.appUrl(`/kontrak?${params}`) : `/kontrak?${params}`;
				window.history.replaceState({ mode: "client" }, "", url);
				this.renderReport();
			});
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

    loadBudgetContext(form) {
        if(!form.find('[name=nilai_kontrak]').length)return;
        let panel=form.find('#contractBudgetContext');
        if(!panel.length){form.find('[name=nilai_kontrak]').closest('.field').append('<div class="ui small info message" id="contractBudgetContext" aria-live="polite"></div>');panel=form.find('#contractBudgetContext');}
        const id=Number(form.find('[name=id_row]').val()||0),token=this.budgetContextRequest=(this.budgetContextRequest||0)+1;
        window.Ajax.request({url:`/kontrak/budget-context?contract_id=${id}&tanggal_kontrak=${encodeURIComponent(this.contractBudgetDate())}&kd_opd=${encodeURIComponent(form.find('[name=kd_opd]').val()||'')}`,method:'GET',success:r=>{
            if(token!==this.budgetContextRequest)return;
            const d=r.data||{},rab=d.rab||{};
            panel.removeClass('negative').addClass('info').html(`<div>${this.esc(d.approval?.label||'Nomor/tanggal pengesahan belum dicatat TAPD untuk OPD/tahun ini.')}</div><div>RAB HPS: <b>${Number(rab.baris)?this.money(rab.stages?.hps?.total):'Belum ada RAB'}</b></div><div>RAB Penawaran: <b>${Number(rab.baris)?this.money(rab.stages?.penawaran?.total):'Belum ada RAB'}</b></div><div>RAB Negosiasi: <b>${Number(rab.baris)?this.money(rab.stages?.negoisasi?.total):'Belum ada RAB'}</b></div><small>Nilai kontrak dapat dibulatkan manual. Rincian RAB tetap tersimpan dengan nilai asal.</small>`);
        },error:r=>{if(token===this.budgetContextRequest)panel.removeClass('info').addClass('negative').text(r?.message||'Periksa tanggal kontrak dan pengesahan anggaran.');}});
    }

    contractSettingsSidebar(title,html) {
        this.resetRealizationSidebar();
        $('#content_flyout').text(title);$('#icon_flyout').attr('class','sliders horizontal icon');
        $('#form_flyout').html(html).attr('data-contract-settings','1');$('.flyout-footer').hide();$('.sidebarkanan').sidebar('show');
    }
    closeContractSettings(){ $('.sidebarkanan').sidebar('hide');$('.flyout-footer').show(); }
    openRabSettings(){
        const stage=this.rabStage||'negoisasi',label={hps:'HPS',penawaran:'Penawaran',negoisasi:'Negosiasi'}[stage],t=this.deliveryData?.rab_totals?.stages?.[stage]||{};
        this.contractSettingsSidebar(`Pembulatan RAB ${label}`,`<div class="ui form"><div class="ui info message">Pembulatan ${label} disimpan terpisah dari harga satuan dan jumlah rincian.</div><div class="field"><label>Total perhitungan ${label}</label><input readonly value="${Number(t.raw||0).toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2})}"></div><div class="field"><label>Total ${label} yang digunakan</label><input id="rabManualTotal" type="number" min="0.01" step="0.01" value="${t.total??0}"></div><div class="ui fluid vertical buttons"><button type="button" class="ui teal button" data-rab-round>Simpan Pembulatan ${label}</button><button type="button" class="ui basic button" data-rab-round-reset>Gunakan Total Rincian</button></div></div>`);
    }
    openRabImportSettings(){
        this.contractSettingsSidebar('Impor HPS / Penawaran / Negosiasi',`<div class="ui form" id="rabImportSettings"><div class="ui info message">Satu file untuk ketiga tahap. Isi volume dan harga satuan masing-masing tahap; jumlah dan pajak dihitung aplikasi. Baris keterangan/divisi tetap disertakan.</div><a class="ui fluid basic blue button" href="${window.appUrl('/kontrak/rab/import-template')}"><i class="download icon"></i>Unduh Template Excel</a><div class="ui divider"></div><h4 class="ui header">Pembulatan total saat impor</h4><p>Kosongkan untuk memakai total perhitungan.</p>${['hps','penawaran','negoisasi'].map(k=>`<div class="field"><label>${{hps:'HPS',penawaran:'Penawaran',negoisasi:'Negosiasi'}[k]}</label><input type="number" min="0.01" step="0.01" data-import-round="${k}" placeholder="Tanpa pembulatan"></div>`).join('')}<div class="field required"><label>File Excel</label><input type="file" id="rabImportSidebarFile" accept=".xlsx,.xls"></div><div class="ui warning message">Impor mengganti rincian RAB paket ini. Periksa data sebelum memilih file.</div></div>`);
    }
    openOfferSettings(){this.contractSettingsSidebar('Cetak Penawaran Rekanan','<div id="contractOfferPrintControls"></div>');this.loadOfferPrintSettings();}

	loadProcessForm(form) {
		if(!form.find('[name=proses_pengadaan]').length)return;
		form.find('[name=proses_pengadaan]').closest('.column').hide();
		const token=this.processRequest=(this.processRequest||0)+1,id=Number(form.find('[name=id_row]').val()||0);
		this.processValues={};this.processCatalog=null;
		form.find('#contractProcessFields').remove();
		form.find('[name=proses_pengadaan]').closest('.column').after('<div class="sixteen wide column" id="contractProcessFields"><div class="ui active centered inline loader"></div></div>');
		window.Ajax.request({url:`/kontrak/procurement/process?contract_id=${id}`,method:"GET",success:r=>{
			if(token!==this.processRequest||!form.find('[name=proses_pengadaan]').length)return;
			this.contractTypes=r.data?.contract_types||{};this.renderContractTypes(form);
			this.processCatalog=r.data?.catalog||{};this.processValues=r.data?.values||{};this.renderProcessForm(form);
		},error:()=>{if(token===this.processRequest)form.find('#contractProcessFields').html('<div class="ui negative message">Nomor/tanggal proses gagal dimuat. Buka kembali form sebelum menyimpan.</div>');}});
	}
	renderContractTypes(form) {
		const way=form.find('[name=cara_pengadaan]').val(),kind=form.find('[name=jenis_pengadaan]').val(),input=form.find('[name=jenis_kontrak]'),dropdown=input.closest('.ui.dropdown');
		const options=this.contractTypes?.[way==='SWAKELOLA'?'SWAKELOLA':kind];if(!input.length||!options)return;
		const previous=input.val()||form.attr('data-saved-contract-type'),fallback=way==='SWAKELOLA'?'PERJANJIAN_SWAKELOLA':kind==='PEKERJAAN_KONSTRUKSI'?'HARGA_SATUAN':'LUMSUM';
		dropdown.dropdown('change values',Object.entries(options).map(([value,name])=>({value,name})));
		dropdown.dropdown('set selected',Object.hasOwn(options,previous)?previous:fallback);
		form.removeAttr('data-saved-contract-type');
	}

	processFlow(form) {
		if(form.find('[name=cara_pengadaan]').val()==="SWAKELOLA")return "SWAKELOLA";
		const method=String(form.find('[name=metode_pemilihan]').val()||'').toLowerCase();
		if(/katalog|e[ -]?purchas|e[ -]?catalog/.test(method))return "KATALOG";
		if(/tender|lelang|seleksi/.test(method))return "TENDER";
		return method.includes('pengadaan langsung')?"LANGSUNG":"LAINNYA";
	}

	processSteps(form) {
		const flow=this.processFlow(form),type=form.find('[name=tipe_swakelola]').val();
		const variant=/pemilihan|usulan/i.test(String(form.find('[name=metode_pemilihan]').val()||''));
		return (this.processCatalog?.[flow]?.steps||[]).filter(s=>{
			if(flow!=="SWAKELOLA")return true;if(s[3]!==type)return false;if(!['III','IV'].includes(type))return true;
			const source=s[0].match(/^SW_[IV]+_(\d+)_/)?.[1];return source==='05'||(variant?['03','04']:['01','02']).includes(source);
		});
	}

	serializeProcessForm(form) {
		const values={};this.processSteps(form).filter(s=>s[2]!=="header").forEach(([code])=>{values[code]=this.processValues[code]||{nomor:'',tanggal:'',waktu:''};});
		form.find('[name=proses_pengadaan]').val(JSON.stringify(values));
	}

	renderProcessForm(form) {
		if(!this.processCatalog)return;
		const flow=this.processFlow(form),steps=this.processSteps(form).filter(s=>s[2]!=="header"),escape=x=>this.esc(String(x??''));
		form.find('#contractProcessFields').html(`<h4 class="ui dividing header"><i class="calendar alternate outline icon"></i>Nomor dan Tanggal Proses</h4><div class="ui small info message visible">${escape(this.processCatalog[flow]?.label)}. Nomor/tanggal Kontrak dan SPMK mengikuti isian di atas. Setiap tahap lainnya diisi terpisah.</div><div class="ui styled fluid accordion">${steps.map(([code,label])=>{
			const v=this.processValues[code]||{};
			return `<div class="title"><i class="dropdown icon"></i>${escape(label)}<div class="ui tiny grey text">${escape(v.tanggal||'Tanggal belum diisi')}</div></div><div class="content" data-process-code="${escape(code)}"><div class="field"><label>Nomor ${escape(label)}</label><input type="text" maxlength="255" data-process-value="nomor" value="${escape(v.nomor)}" placeholder="Kosong jika tidak bernomor"></div><div class="field"><label>Tanggal</label><div class="ui calendar contract-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" data-process-value="tanggal" value="${escape(v.tanggal)}" placeholder="YYYY-MM-DD" autocomplete="off"></div></div></div><div class="field"><label>Waktu / batas waktu (jika ada)</label><input type="text" maxlength="100" data-process-value="waktu" value="${escape(v.waktu)}" placeholder="Contoh: 10:00 WITA"></div></div>`;
		}).join('')}</div>${steps.length?'':'<div class="ui message">Pilih metode atau tipe swakelola untuk menampilkan tahap yang sesuai.</div>'}`);
		form.find('#contractProcessFields .ui.accordion').accordion({exclusive:false});this.initContractCalendars(form.find('#contractProcessFields'));this.serializeProcessForm(form);
	}

    contractBudgetDate() {
        const value=String($('#form_flyout [name=tanggal_kontrak]').val()||'');
        return /^\d{2}\/\d{2}\/\d{4}$/.test(value)?value.split('/').reverse().join('-'):value;
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
		this.realizationId = Number(id || 0);
		this.realizationItems = [];
		this.realizationContracts = [];
		this.realizationFiles = [];
		this.resetRealizationSidebar();
		$("#content_flyout").text(this.realizationId ? "Ubah Realisasi Kontrak" : "Input Realisasi Kontrak");
		$("#icon_flyout").attr("class", "chartline icon");
		const submit = $(".sidebarkanan .flyout-footer .btnSubmit");
		submit.attr("id", "realizationSubmit").addClass("disabled").on("click.realizationSave", (event) => {
			event.preventDefault();
			event.stopImmediatePropagation();
			if (!submit.hasClass("disabled")) this.saveRealization();
		});
		$("#form_flyout").html(`<div class="ui form" id="realizationSidebarForm">
			<div id="realizationValidation" class="ui negative message" role="alert" hidden></div>
			<h4 class="ui dividing header">Transaksi dan Kontrak</h4>
			<div class="field required"><label>Kontrak</label><select class="ui fluid search dropdown" id="realizationContract"><option value="">Pilih kontrak</option></select></div>
			<div class="two fields"><div class="field required"><label>Tanggal transaksi/progres</label><div class="ui calendar contract-calendar" data-calendar-type="date"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" id="realizationDate" placeholder="YYYY-MM-DD" autocomplete="off"></div></div></div><div class="field"><label>Realisasi kontrak di luar transaksi ini</label><input type="text" id="realizationPrevious" disabled value="Rp 0"></div></div>
			<div class="field required"><label>Uraian transaksi/progres</label><textarea id="realizationDescription" rows="2" placeholder="Contoh: Pembayaran termin, uang muka, atau progres pekerjaan..."></textarea></div>
			<h4 class="ui dividing header">Dokumen Keuangan</h4><div class="field required"><label>Jenis transaksi keuangan</label><select class="ui fluid dropdown" id="realizationTransactionType">
				<option value="PROGRES">Progres / BAST / tagihan belum dibayar</option><option value="LS">Pembayaran langsung (LS)</option><option value="UANG_MUKA_KONTRAK">Uang muka kontrak kepada penyedia</option><option value="UP">Belanja melalui UP (SPJ)</option><option value="TU">Belanja melalui TU (SPJ)</option><option value="GU">SP2D GU / penggantian UP</option>
			</select></div>
			<div id="realizationFinanceNotice" class="ui info message"></div>
			<div data-finance-field="ALL" class="field"><label>Nomor bukti transaksi</label><input id="realizationFinanceProof" maxlength="120" placeholder="Nomor kuitansi, bukti transfer, atau dokumen sumber"></div>
			<div data-finance-field="LS,UANG_MUKA_KONTRAK,GU" class="two fields"><div class="field"><label>Nomor SP2D</label><input id="realizationSp2dNumber" maxlength="120"></div><div class="field"><label>Tanggal SP2D</label><input type="date" id="realizationSp2dDate"></div></div>
			<div data-finance-field="UP,TU" class="two fields"><div class="field"><label>Nomor register UP/TU</label><input id="realizationUpTuRegister" maxlength="120" placeholder="Nomor register sumber dana"></div><div class="field"><label>Nomor SP2D sumber UP/TU</label><input id="realizationUpTuSp2d" maxlength="120"></div></div>
			<div data-finance-field="UP,TU,GU" class="three fields"><div class="field"><label>Nomor SPJ</label><input id="realizationSpjNumber" maxlength="120"></div><div class="field"><label>Tanggal SPJ</label><input type="date" id="realizationSpjDate"></div><div class="field"><label>Tanggal pengesahan SPJ</label><input type="date" id="realizationSpjApprovalDate"></div></div>
			<div data-finance-field="LS" class="two fields"><div class="field"><label>Potongan uang muka kontrak (jika ada)</label><input type="number" min="0" step="0.01" id="realizationAdvanceDeduction" value="0"></div><div class="field"><label>Nomor SP2D uang muka yang dipotong</label><input id="realizationAdvanceSp2d" maxlength="120" placeholder="Wajib diisi jika ada potongan"></div></div>
			<div class="field"><label>Keterangan tambahan</label><textarea id="realizationNote" rows="2"></textarea></div>
			<div class="field ${this.realizationId ? "" : "required"}"><label>Bukti transaksi (SP2D/SPJ/BAST/kuitansi/dokumen pengembalian; PDF/gambar/XLSX/DOCX, maks. 15 MB per file)</label><input type="file" id="realizationFiles" multiple ${this.realizationId ? "" : "required"} accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.docx"><div id="realizationExistingFiles" class="ui relaxed divided list"></div></div>
			<div class="ui info message">Bukti dilampirkan dan dicatat per transaksi. Transaksi keuangan masuk LRA setelah diverifikasi oleh pengguna lain yang berwenang. Progres saja, SP2D GU, dan pencairan UP/TU tidak dihitung sebagai belanja dua kali.</div>
			<h4 class="ui dividing header">Nilai dan Rekening</h4><div class="field required"><label>Jumlah transaksi/progres</label><input type="number" id="realizationTotal" min="0" step="any" inputmode="decimal" placeholder="0"></div>
			<div id="realizationAllocationSummary" class="ui small message" aria-live="polite">Pilih kontrak untuk memuat rekening.</div><button type="button" class="ui fluid violet button" id="realizationItemsButton" disabled><i class="list alternate outline icon"></i>Atur Uraian per Rekening</button>
			</div>`);
		$(".sidebarkanan .flyout-body").scrollTop(0);
		this.initContractCalendars($("#form_flyout"));
		$("#form_flyout").off("change.realisasiKeuangan", "#realizationTransactionType").on("change.realisasiKeuangan", "#realizationTransactionType", () => this.updateRealizationFinanceFields());
		$("#realizationTransactionType").dropdown();
		this.updateRealizationFinanceFields();
		$(".sidebarkanan").sidebar("show");
		const load = (detail = null) => window.Ajax.request({ url: "/kontrak/realization-contracts", method: "GET", success: (r) => {
			this.realizationContracts = r.data || [];
			const select = $("#realizationContract");
			select.html('<option value="">Pilih kontrak</option>' + this.realizationContracts.map((x) => `<option value="${x.id}">${this.esc(x.uraian_kontrak || x.nomor_kontrak)} · ${this.money(x.nilai_kontrak)}</option>`).join(""));
			if (select.hasClass("noselection")) select.dropdown("destroy");
			select.dropdown({ fullTextSearch: true, highlightMatches: true, onChange: (value) => this.loadRealizationContract(Number(value || 0), detail) });
			if (detail) {
				select.dropdown("set selected", String(detail.contract_id));
				select.closest(".field").toggleClass("disabled", true);
				select.prop("disabled", true);
			}
		} });
		if (this.realizationId) {
			window.Ajax.request({ url: `/kontrak/realization/detail?id=${this.realizationId}`, method: "GET", success: (r) => {
				const detail = r.data || {};
				this.realizationFiles = detail.files || [];
				$("#realizationDate").val(detail.tanggal || "");
				$("#realizationDescription").val(detail.uraian_transaksi || "");
				$("#realizationNote").val(detail.keterangan || "");
				$("#realizationTotal").val(Number(detail.jumlah || 0));
				this.populateRealizationFinance(detail.keuangan || {});
				this.renderRealizationFiles();
				load(detail);
			} });
		} else {
			load();
		}
	}

	updateRealizationFinanceFields() {
		const type = $("#realizationTransactionType").val() || "PROGRES";
		$("#form_flyout [data-finance-field]").each((_, field) => {
			const types = String(field.dataset.financeField || "").split(",");
			$(field).toggle(types.includes("ALL") ? type !== "PROGRES" : types.includes(type));
		});
		const messages = {
			PROGRES: "Progres, BAST, atau tagihan belum dibayar bukan pengeluaran kas LRA. Tetap dicatat untuk jejak kontrak, tetapi tidak menambah realisasi belanja.",
			LS: "LS dihitung sebagai belanja LRA hanya setelah nomor/tanggal SP2D dan bukti diverifikasi. Uang muka yang dipotong harus dicatat agar tidak dihitung dua kali.",
			UANG_MUKA_KONTRAK: "Uang muka kontrak adalah arus kas yang harus ditautkan ke SP2D kontrak. Nilai uang muka juga harus direkonsiliasi terhadap potongan pada pembayaran termin berikutnya.",
			UP: "Belanja dari UP dicatat per bukti dan nomor sumber UP; bukan saat UP dicairkan. Belanja baru dapat masuk LRA setelah SPJ disahkan dan transaksi diverifikasi.",
			TU: "Belanja dari TU dicatat per bukti dan nomor sumber TU; pencairan TU bukan belanja kedua. SPJ harus disahkan dan diverifikasi sebelum masuk LRA.",
			GU: "SP2D GU adalah penggantian uang persediaan, bukan belanja baru. Tautkan ke SPJ yang telah disahkan; GU tidak menambah realisasi LRA lagi.",
		};
		$("#realizationFinanceNotice").toggleClass("warning", type === "PROGRES" || type === "GU").toggleClass("info", type !== "PROGRES" && type !== "GU").text(messages[type]);
		this.syncRealizationLraTotal();
		if ($("#realizationItemsModal").hasClass("active")) this.renderRealizationItems();
	}

	populateRealizationFinance(data = {}) {
		$("#realizationTransactionType").dropdown("set selected", data.jenis_transaksi || "PROGRES");
		$("#realizationFinanceProof").val(data.nomor_bukti || "");
		$("#realizationSp2dNumber").val(data.nomor_sp2d || "");
		$("#realizationSp2dDate").val(data.tanggal_sp2d || "");
		$("#realizationUpTuSp2d").val(data.nomor_sp2d_sumber || "");
		$("#realizationSpjNumber").val(data.nomor_spj || "");
		$("#realizationSpjDate").val(data.tanggal_spj || "");
		$("#realizationSpjApprovalDate").val(data.tanggal_pengesahan_spj || "");
		$("#realizationUpTuRegister").val(data.nomor_up_tu || "");
		$("#realizationAdvanceDeduction").val(Number(data.nilai_potongan_uang_muka || 0));
		$("#realizationAdvanceSp2d").val(data.nomor_sp2d_uang_muka || "");
		this.updateRealizationFinanceFields();
	}

	renderRealizationFiles(){const list=$("#realizationExistingFiles");if(!list.length)return;list.html(this.realizationFiles.length?this.realizationFiles.map(f=>`<div class="item"><i class="file outline icon"></i><div class="content"><div class="header">${this.esc(f.nama_file_asli)}</div><div class="description">${this.money(Number(f.ukuran||0)/1024).replace('Rp','')} KB &nbsp; <a href="${window.appUrl?window.appUrl(`/kontrak/realization/file/download?id=${f.id}`):`/kontrak/realization/file/download?id=${f.id}`}"><i class="download icon"></i>Unduh</a> &nbsp; <a href="#" data-realization-file-delete="${f.id}"><i class="trash icon"></i>Hapus</a></div></div></div>`).join(""):'<div class="ui tiny warning message">Belum ada file transaksi.</div>');}

	loadRealizationContract(id, detail = null) {
		this.contractId = id || null;
		$("#realizationItemsButton, #realizationSubmit").prop("disabled", true).addClass("disabled");
		if (!id) {
			$("#realizationPrevious").val("Rp 0");
			this.realizationItems = [];
			return;
		}
		const contract = this.realizationContracts.find((x) => Number(x.id) === id);
		$("#realizationPrevious").val(this.money(contract?.realisasi || 0));
		window.Ajax.request({ url: `/kontrak/realization-items?contract_id=${id}`, method: "GET", success: (r) => {
			this.realizationItems = (r.data?.items || []).map((x) => ({ ...x, jumlah_sekarang: 0, nilai_lra_sekarang: 0, progress_fisik: 0 }));
			if(detail){const chosen=new Map((detail.items||[]).map(x=>[`${x.tahap}:${x.anggaran_id}`,x]));this.realizationItems.forEach(x=>{const old=chosen.get(`${x.tahap}:${x.anggaran_id}`);if(old){x.realisasi=Math.max(0,Number(x.realisasi||0)-Number(old.jumlah||0));x.jumlah_sekarang=Number(old.jumlah||0);x.nilai_lra_sekarang=Number(old.nilai_lra||0);x.progress_fisik=Number(old.progress_fisik||0);}});}
			const current = Number(r.data?.contract?.nilai_kontrak || contract?.nilai_kontrak || 0);
			$("#realizationPrevious").val(this.money(this.realizationItems.reduce((sum, x) => sum + Number(x.realisasi || 0), 0)));
			$("#realizationTotal").attr("max", Math.max(0, current));
			$("#realizationItemsButton, #realizationSubmit").prop("disabled", false).removeClass("disabled");
			this.syncRealizationLraTotal();
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
		$("body").append(`<div class="ui large modal" id="realizationItemsModal"><i class="close icon"></i><div class="header"><i class="list alternate outline icon"></i> Uraian Realisasi per Kontrak</div><div class="content"><div class="ui info message">Pisahkan progres kontrak dari nilai belanja kas yang diakui LRA. Untuk LS/uang muka kontrak/UP/TU, isi nilai LRA per rekening berdasarkan SP2D atau SPJ yang sah; progres dan GU harus bernilai nol.</div><div class="table-wrapper realization-items-table-wrapper"><table class="ui compact celled striped table"><thead><tr><th>DPA/DPPA</th><th>Anggaran</th><th>Nilai Kontrak</th><th>Realisasi Sekarang</th><th data-lra-column>Belanja LRA Sekarang</th><th>Realisasi</th><th>% Keuangan</th><th>% Fisik</th><th>Aksi</th></tr></thead><tbody id="realizationItemsBody"></tbody><tfoot><tr><th colspan="3" class="right aligned">Total</th><th id="realizationItemsTotal" class="right aligned">Rp 0</th><th data-lra-column id="realizationItemsLraTotal" class="right aligned">Rp 0</th><th colspan="4"></th></tr></tfoot></table></div></div><div class="actions"><button type="button" class="ui deny button">Tutup</button><button class="ui violet button" data-realization-items-done><i class="check icon"></i>Selesai</button></div></div>`);
		$(document).off("click.realizationItemsDone", "[data-realization-items-done]").on("click.realizationItemsDone", "[data-realization-items-done]", () => { this.syncRealizationTotal(); $("#realizationItemsModal").modal("hide"); });
	}

	renderRealizationItems() {
		const needsLra = ["LS", "UANG_MUKA_KONTRAK", "UP", "TU"].includes($("#realizationTransactionType").val());
		$("#realizationItemsBody").html(this.realizationItems.map((x, i) => `<tr><td><span class="ui tiny ${x.tahap === "dppa" ? "orange" : "blue"} label">${this.esc(x.tahap.toUpperCase())}</span><br><small>${this.esc(x.kd_sub_keg)}</small></td><td><b>${this.esc(x.kd_akun)}</b><br>${this.esc(x.uraian)}<br><small>${this.money(x.pagu)}</small></td><td class="right aligned">${this.money(x.nilai_kontrak)}</td><td><input class="ui input" type="number" min="0" max="${Math.max(0, Number(x.nilai_kontrak) - Number(x.realisasi || 0))}" step="any" inputmode="decimal" data-realization-amount="${i}" value="${Number(x.jumlah_sekarang || 0)}"></td><td data-lra-column>${needsLra ? `<input class="ui input" type="number" min="0" step="0.01" inputmode="decimal" data-realization-lra-amount="${i}" value="${Number(x.nilai_lra_sekarang || 0)}">` : ""}</td><td class="right aligned">${this.money(x.realisasi)}</td><td class="right aligned" data-realization-percent="${i}">${Number(x.persen_keuangan || 0).toFixed(2)}%</td><td><input class="ui input" type="number" min="0" max="100" step="0.01" data-realization-physical="${i}" value="${Number(x.progress_fisik || 0)}"></td><td><button type="button" class="ui mini basic teal button" data-realization-detail="${i}"><i class="info circle icon"></i>Detail</button></td></tr>`).join(""));
		$("[data-lra-column]").toggle(needsLra);
		$("[data-realization-amount]").on("input", (e) => { const i = Number($(e.currentTarget).data("realization-amount")); this.realizationItems[i].jumlah_sekarang = Number(e.currentTarget.value || 0); const total = Number(this.realizationItems[i].realisasi || 0) + this.realizationItems[i].jumlah_sekarang; $("[data-realization-percent='" + i + "']").text((this.realizationItems[i].nilai_kontrak > 0 ? total / this.realizationItems[i].nilai_kontrak * 100 : 0).toFixed(2) + "%"); this.syncRealizationTotal(); });
		$("[data-realization-lra-amount]").on("input", (e) => { const i = Number($(e.currentTarget).data("realization-lra-amount")); this.realizationItems[i].nilai_lra_sekarang = Number(e.currentTarget.value || 0); this.syncRealizationLraTotal(); });
		$("[data-realization-physical]").on("input", (e) => { const i = Number($(e.currentTarget).data("realization-physical")); this.realizationItems[i].progress_fisik = Number(e.currentTarget.value || 0); });
		this.syncRealizationTotal();
		this.syncRealizationLraTotal();
	}

	syncRealizationTotal() {
		const total = this.realizationItems.reduce((sum, x) => sum + Number(x.jumlah_sekarang || 0), 0);
		$("#realizationItemsTotal").text(this.money(total));
		if ($("#realizationTotal").length && document.activeElement?.id !== "realizationTotal") $("#realizationTotal").val(total ? total : "");
	}

	syncRealizationLraTotal() {
		const total = this.realizationItems.reduce((sum, x) => sum + Number(x.nilai_lra_sekarang || 0), 0);
		$("#realizationItemsLraTotal").text(this.money(total));
		const needsLra = ["LS", "UANG_MUKA_KONTRAK", "UP", "TU"].includes($("#realizationTransactionType").val());
		const missing = needsLra && total <= 0 && this.realizationItems.length > 0;
		$("#realizationAllocationSummary").toggleClass("warning", missing).html(`<b>${this.realizationItems.length} rekening</b> · Belanja LRA: <b>${this.money(total)}</b>${missing ? '<p>Nilai belanja LRA belum diisi. Buka <b>Atur Uraian per Rekening</b>, lalu isi kolom <b>Belanja LRA Sekarang</b> sesuai SP2D/SPJ.</p>' : ''}`);
		$("#realizationItemsButton").toggleClass("basic", !missing);
	}

	showRealizationDetail(index) {
		const x = this.realizationItems[index];
		if (!x) return;
		$("#realizationDetailModal").remove();
		$("body").append(`<div class="ui small modal" id="realizationDetailModal"><i class="close icon"></i><div class="header"><i class="sitemap icon"></i> Detail Uraian Belanja</div><div class="content"><table class="ui definition compact table"><tbody><tr><td>Program</td><td>${this.esc(x.program || "-")}</td></tr><tr><td>Kegiatan</td><td>${this.esc(x.kegiatan || "-")}</td></tr><tr><td>Sub Kegiatan</td><td>${this.esc(x.sub_kegiatan || x.kd_sub_keg || "-")}</td></tr><tr><td>Kode Rekening Belanja</td><td>${this.esc(x.rekening || x.kd_akun || "-")}</td></tr><tr><td>Uraian</td><td>${this.esc(x.uraian || "-")}</td></tr><tr><td>Realisasi transaksi</td><td>${this.money(x.jumlah_sekarang)}</td></tr></tbody></table></div><div class="actions"><button class="ui approve teal button">Tutup</button></div></div>`);
		$("#realizationDetailModal").modal("show");
	}

	saveRealization() {
		$("#realizationValidation").prop("hidden", true);
		$("#realizationSidebarForm .field.error").removeClass("error");
		const contractId = Number($("#realizationContract").val() || 0), total = Number($("#realizationTotal").val() || 0), rows = this.realizationItems.map((x) => ({ tahap: x.tahap, anggaran_id: x.anggaran_id,
                pengesahan_anggaran_id:x.pengesahan_anggaran_id||null, jumlah: Number(x.jumlah_sekarang || 0), nilai_lra: Number(x.nilai_lra_sekarang || 0), progress_fisik: Number(x.progress_fisik || 0) }));
		const distributed = rows.reduce((sum, x) => sum + x.jumlah, 0);
		if (!contractId || !$("#realizationDate").val() || !String($("#realizationDescription").val() || "").trim()) return this.realizationError("Kontrak, tanggal, uraian transaksi, dan uraian per item wajib diisi");
		if (total <= 0 || Math.abs(total - distributed) > 0.01) return this.realizationError("Jumlah realisasi harus sama dengan total pembagian pada uraian", "#realizationTotal");
		const selected = Array.from($("#realizationFiles")[0]?.files || []);
		if (!this.realizationId && !selected.length) return this.realizationError("File transaksi wajib ditambahkan saat input realisasi", "#realizationFiles");
		const type = $("#realizationTransactionType").val() || "PROGRES";
		const lraTotal = rows.reduce((sum, row) => sum + row.nilai_lra, 0);
		if (["LS", "UANG_MUKA_KONTRAK", "UP", "TU"].includes(type) && lraTotal <= 0) return this.realizationError("Nilai belanja LRA belum diisi. Buka Atur Uraian per Rekening dan isi Belanja LRA Sekarang sesuai SP2D/SPJ.", "#realizationItemsButton", true);
		if (["PROGRES", "GU"].includes(type) && lraTotal > 0) return this.realizationError("Progres dan SP2D GU tidak boleh menambah nilai belanja LRA.");
		const keuangan = {
			jenis_transaksi: type,
			nomor_bukti: $("#realizationFinanceProof").val(),
			nomor_sp2d: $("#realizationSp2dNumber").val(),
			tanggal_sp2d: $("#realizationSp2dDate").val(),
			nomor_sp2d_uang_muka: $("#realizationAdvanceSp2d").val(),
			nomor_sp2d_sumber: $("#realizationUpTuSp2d").val(),
			nomor_spj: $("#realizationSpjNumber").val(),
			tanggal_spj: $("#realizationSpjDate").val(),
			tanggal_pengesahan_spj: $("#realizationSpjApprovalDate").val(),
			nomor_up_tu: $("#realizationUpTuRegister").val(),
			nilai_potongan_uang_muka: Number($("#realizationAdvanceDeduction").val() || 0),
		};
		if (type !== "PROGRES" && !keuangan.nomor_bukti.trim()) return this.realizationError("Nomor bukti wajib diisi untuk transaksi keuangan.", "#realizationFinanceProof");
		if (["LS", "UANG_MUKA_KONTRAK", "GU"].includes(type) && (!keuangan.nomor_sp2d.trim() || !keuangan.tanggal_sp2d)) return this.realizationError("Nomor dan tanggal SP2D wajib untuk jenis transaksi ini.", !keuangan.nomor_sp2d.trim() ? "#realizationSp2dNumber" : "#realizationSp2dDate");
		if (["UP", "TU", "GU"].includes(type) && (!keuangan.nomor_spj.trim() || !keuangan.tanggal_spj || !keuangan.tanggal_pengesahan_spj)) return this.realizationError("Nomor SPJ, tanggal SPJ, dan tanggal pengesahan wajib untuk UP/TU/GU.", !keuangan.nomor_spj.trim() ? "#realizationSpjNumber" : !keuangan.tanggal_spj ? "#realizationSpjDate" : "#realizationSpjApprovalDate");
		if (["UP", "TU"].includes(type) && (!keuangan.nomor_sp2d_sumber.trim() || !keuangan.nomor_up_tu.trim())) return this.realizationError("Tautkan transaksi UP/TU ke register dan SP2D sumbernya.", !keuangan.nomor_up_tu.trim() ? "#realizationUpTuRegister" : "#realizationUpTuSp2d");
		if (keuangan.nilai_potongan_uang_muka < 0 || keuangan.nilai_potongan_uang_muka > total) return this.realizationError("Potongan uang muka tidak boleh melebihi nilai transaksi.", "#realizationAdvanceDeduction");
		if (type === "LS" && keuangan.nilai_potongan_uang_muka > 0 && !keuangan.nomor_sp2d_uang_muka.trim()) return this.realizationError("Cantumkan nomor SP2D uang muka yang dipotong.", "#realizationAdvanceSp2d");
		if (type !== "PROGRES" && !selected.length && !this.realizationFiles.length) return this.realizationError("Lampirkan bukti pembayaran/SPJ/BAST untuk transaksi keuangan.", "#realizationFiles");
		const form = new FormData();
		form.set("realization_id", this.realizationId);
		form.set("contract_id", contractId);
		form.set("tanggal", $("#realizationDate").val());
		form.set("uraian_transaksi", $("#realizationDescription").val());
		form.set("keterangan", $("#realizationNote").val());
		form.set("items", JSON.stringify(rows));
		form.set("keuangan", JSON.stringify(keuangan));
		selected.forEach((file) => form.append("transaction_files[]", file));
		const button = $("#realizationSubmit").addClass("loading disabled");
		window.Ajax.request({ url: "/kontrak/realization/save", method: "POST", data:form,processData:false,contentType:false, success: () => { this.resetRealizationSidebar(); $(".sidebarkanan").sidebar("hide"); window.tableManager?.fetchData(); }, error: (r) => this.realizationError(r?.message || "Data belum tersimpan. Periksa kembali isian transaksi dan rekening."), complete: () => button.removeClass("loading disabled") });
	}

	realizationError(message, selector = "", items = false) {
		const form = $("#realizationSidebarForm");
		const target = selector ? $(selector) : form.find("input, textarea, select").filter(function () { return $(this).closest(".field").hasClass("required") && !String($(this).val() || "").trim(); }).first();
		target.closest(".field").addClass("error");
		$("#realizationValidation").prop("hidden", false).text(message);
		if (items) {
			this.openRealizationItems();
			$("#realizationItemsBody [data-realization-lra-amount]").first().closest("td").addClass("error");
			$("#realizationItemsBody [data-realization-lra-amount]").first().trigger("focus");
		} else if (target.length) target[0].scrollIntoView({block: "center", behavior: "smooth"});
		else $(".sidebarkanan .flyout-body").scrollTop(0);
		return false;
	}

	resetRealizationSidebar() {
		$(".sidebarkanan .flyout-footer .btnSubmit")
			.off("click.realizationSave").removeAttr("id").prop("disabled", false).removeClass("loading disabled");
		$(".sidebarkanan .flyout-footer").show();
	}

	ensureModal() {
		if ($("#contractItemsModal").length) return;
		$("body").append(
			`<div class="ui large modal" id="contractItemsModal"><i class="close icon"></i><div class="header"><i class="violet list alternate outline icon"></i> Rincian Uraian Kontrak</div><div class="content"><div class="ui info message"><div class="header">Multi sub kegiatan dan multi uraian</div><p>Pilih subkegiatan berdasarkan kode atau nama, lalu cari uraian, kode rekening, atau nilai pagu. Maksimal 50 hasil per pencarian agar tetap ringan.</p></div><div class="contract-item-grid"><section><div class="field"><label>Sub Kegiatan</label><select class="ui fluid search dropdown" id="contractSubFilter"><option value="">Pilih sub kegiatan dahulu</option></select><div id="contractSubFilterStatus" class="ui small basic message">Memuat sub kegiatan tahun aktif...</div></div><div class="ui fluid icon input" style="margin-top:9px"><input id="contractItemSearch" placeholder="Cari uraian, rekening, atau nilai anggaran..."><i class="search icon"></i></div><div id="contractAvailableList" class="contract-scroll"><div class="ui message">Pilih sub kegiatan untuk menampilkan uraian.</div></div></section><section><h4 class="ui header">Uraian terpilih</h4><div id="contractSelectedList" class="contract-scroll"></div></section></div></div><div class="actions"><button type="button" class="ui deny button">Tutup</button><button class="ui positive violet button" data-contract-item-action="save"><i class="save icon"></i>Simpan Rincian</button></div></div>`,
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
			url: `/kontrak/available-items?q=${q}&kd_sub_keg=${encodeURIComponent(sub)}&limit=50&contract_id=${this.contractId}&tanggal_kontrak=${encodeURIComponent(this.contractBudgetDate())}&kd_opd=${encodeURIComponent($('#form_flyout [name=kd_opd]').val()||'')}`,
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
						`<article class="contract-item-card"><div><span class="ui tiny ${x.tahap === "dppa" ? "orange" : "blue"} label">${this.esc(x.dokumen_anggaran||x.tahap.toUpperCase())}</span><b>${this.esc(x.kd_sub_keg)}</b><small>${this.esc(x.kd_akun || "Tanpa kode akun")}</small><p>${this.esc(x.uraian)}</p><small>Pagu ${this.money(x.pagu)} · Sudah berkontrak ${this.money(x.nilai_terpakai)} · Sisa ${this.money(x.pagu_tersedia)}</small><strong>Sisa pagu ${this.money(x.pagu_tersedia)}</strong></div><button class="ui mini violet icon button" data-contract-item-action="add" data-index="${x._index}"><i class="plus icon"></i></button></article>`,
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
                pengesahan_anggaran_id:x.pengesahan_anggaran_id||null,
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
			`<div class="contract-delivery-page"><div class="ui clearing segment contract-delivery-toolbar"><button class="ui left floated basic button" data-contract-delivery-back><i class="arrow left icon"></i>Kembali ke Tabel Kontrak</button><div class="ui right floated buttons"><input type="file" id="rabImportFile" accept=".xlsx,.xls" hidden><button class="ui violet button" data-delivery-action="sskk"><i class="file alternate outline icon"></i>Buat SSKK</button><button class="ui purple button" data-delivery-action="ssuk"><i class="file alternate icon"></i>Buat SSUK</button><button class="ui button" data-delivery-action="import"><i class="upload icon"></i>Import RAB</button><button class="ui green button" data-delivery-action="excel"><i class="file excel icon"></i>Excel</button><button class="ui red button" data-delivery-action="pdf"><i class="file pdf icon"></i>PDF</button></div></div><div id="contractDeliveryContent"><div class="ui active centered inline loader"></div></div></div>`,
		);
		window.Ajax.request({
			url: `/kontrak/delivery?contract_id=${id}`,
			method: "GET",
			success: (r) => {
				this.deliveryData = r.data || {};
				this.renderDelivery(activeTab);
			},
            error: xhr => {const message=xhr?.responseJSON?.message||"Data kontrak tidak dapat dimuat. Periksa izin akses atau muat ulang halaman.";$("#contractDeliveryContent").html(`<div class="ui negative message">${this.esc(message)}</div>`);},
		});
	}

	renderDelivery(activeTab = "rab") {
		const d = this.deliveryData || {},
			rab = d.rab || [],
			weeks = d.schedule || [],
			curve = d.curve || [];
        const stage=this.rabStage||'negoisasi',stageName={hps:'HPS',penawaran:'Penawaran',negoisasi:'Negosiasi'}[stage],totals=d.rab_totals?.stages||{};
		const rabRows = (rab.length ? rab : [{}])
			.map(
				(x, i) => x.type==="KETERANGAN"?this.rabNoteRow(x):
					`<tr><td><input type="hidden" value="${x.id || ""}" data-rab="id"><input value="${this.esc(x.nomor || i + 1)}" data-rab="nomor" required></td><td><textarea rows="2" data-rab="uraian" aria-label="Uraian pekerjaan" required>${this.esc(x.uraian || "")}</textarea></td><td><input value="${this.esc(x.satuan || "")}" data-rab="satuan" required></td><td><input type="number" min="0.000000000001" step="any" value="${Number(x[`vol_${stage}`] ?? 0)}" data-rab="volume" required></td><td><input type="number" min="0.01" step="any" value="${Number(x[`harga_sat_${stage}`] ?? 0)}" data-rab="harga_satuan" required></td><td><input type="number" min="0" max="100" step="any" value="${Number(x.pajak || 0)}" data-rab="pajak" aria-label="Pajak persen"></td><td data-rab-total>${this.money(x[`jumlah_${stage}`])}</td><td data-rab-weight>${Number(x.bobot || 0).toFixed(2)}%</td></tr>`,
			)
			.join("");
		const scheduleByRab = new Map();
		weeks.forEach((x) => {
			const key = Number(x.rab_id || 0);
			if (!scheduleByRab.has(key)) scheduleByRab.set(key, []);
			scheduleByRab.get(key).push(x);
		});
		const scheduleGroups = rab.filter(x=>x.type!=="KETERANGAN")
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
		const docCards = this.uploadedDocumentCards(d.documents || [], d.can_manage_documents !== false);
		$("#contractDeliveryContent")
			.html(`<div class="contract-delivery-heading"><div><h3 class="ui header"><i class="tasks teal icon"></i><div class="content">RAB, Time Schedule & Kurva S<div class="sub header">${this.esc(d.contract?.nomor_kontrak || "")} · ${this.esc(d.contract?.uraian_kontrak || "")}</div></div></h3></div><button type="button" class="ui basic button" id="contractPrintSettings"><i class="print icon"></i>Pengaturan Cetak</button></div><div class="ui four item stackable menu contract-section-tabs"><a class="${activeTab === "rab" ? "active " : ""}blue item" data-tab="rab"><i class="calculator icon"></i>RAB</a><a class="${activeTab === "schedule" ? "active " : ""}teal item" data-tab="schedule"><i class="calendar alternate icon"></i>Jadwal & Kurva S</a><a class="${activeTab === "documents" ? "active " : ""}orange item" data-tab="documents"><i class="folder open icon"></i>Dokumen Terkait</a></div>
			<div class="ui bottom attached ${activeTab === "rab" ? "active " : ""}tab segment" data-tab="rab"><div class="ui secondary menu contract-rab-toolbar"><div class="item"><button type="button" class="ui basic button" data-rab-settings><i class="sliders horizontal icon"></i>Pembulatan</button></div><div class="item"><button type="button" class="ui basic button" data-rab-import-settings><i class="upload icon"></i>Impor RAB</button></div><div class="right menu"><div class="item"><button type="button" class="ui primary button" data-offer-settings><i class="print icon"></i>Cetak Penawaran</button></div></div></div><form class="ui form" id="contractRabForm"><div class="ui three item menu">${['hps','penawaran','negoisasi'].map(k=>`<a class="${stage===k?'active ':''}item" data-rab-stage="${k}">${{hps:'HPS',penawaran:'Penawaran',negoisasi:'Negosiasi'}[k]}<br>${this.money(totals[k]?.total||0)}</a>`).join('')}</div><div class="ui positive message">${d.contract?.nilai_kontrak==null?"Nilai kontrak belum ditetapkan. Total RAB estimasi "+this.money(rab.reduce((sum,x)=>sum+Number(x.jumlah_negoisasi||0),0)):"Nilai kontrak "+this.money(d.contract.nilai_kontrak)}. Jumlah RAB sudah termasuk pajak; bobot dihitung dari setiap item terhadap total RAB.</div><div class="table-wrapper"><table class="ui compact celled table delivery-table contract-rab-table"><colgroup><col style="width:6%"><col style="width:34%"><col style="width:6%"><col style="width:9%"><col style="width:17%"><col style="width:6%"><col style="width:15%"><col style="width:7%"></colgroup><thead><tr><th>No</th><th>Uraian</th><th>Satuan</th><th>Volume</th><th>Harga Satuan Sebelum Pajak</th><th>Pajak %</th><th>Jumlah Termasuk Pajak</th><th>Bobot</th></tr></thead><tbody id="rabRows">${rabRows}</tbody></table></div><div class="ui buttons"><button type="button" class="ui button" data-delivery-action="add-rab"><i class="plus icon"></i>Tambah Baris</button><button type="button" class="ui button" data-delivery-action="add-rab-note"><i class="heading icon"></i>Tambah Keterangan</button><button type="button" class="ui primary button" data-delivery-action="save-rab"><i class="save icon"></i>Simpan RAB</button></div></form></div>
			<div class="ui bottom attached ${activeTab === "schedule" ? "active " : ""}tab segment" data-tab="schedule"><form class="ui form" id="contractScheduleForm"><div class="ui info message"><div class="header">Jadwal disusun per item RAB</div><p>Rencana dan realisasi setiap item dicatat per minggu/periode. Kurva S kontrak dihitung dari bobot item RAB × progres kumulatif item.</p></div><div class="s-chart">${bars || '<div class="ui message">Belum ada Kurva S tersimpan.</div>'}</div>${scheduleGroups || '<div class="ui warning message">Simpan RAB terlebih dahulu agar Time Schedule dapat dibuat per item RAB.</div>'}<button type="button" class="ui teal button" data-delivery-action="save-schedule" ${rab.length ? "" : "disabled"}><i class="save icon"></i>Simpan Time Schedule</button></form></div>
			<div class="ui bottom attached ${activeTab === "documents" ? "active " : ""}tab segment" data-tab="documents"><div class="ui info message">Dokumen kontrak disusun per kontrak dan jenis dokumen: Kontrak, SPK, SPMK, SSKK, SSUK, RAB, Jadwal, Kurva S, gambar, BAST, PHO/FHO, adendum, jaminan, dan laporan.</div>${d.can_manage_documents !== false ? this.documentUploadForm() : ""}${docCards}</div>`);
		$("#contractDeliveryContent .contract-section-tabs").append(`<a class="${activeTab === "procurement" ? "active " : ""}violet item" data-tab="procurement"><i class="file signature icon"></i>Dokumen Pengadaan</a>`);
		$("#contractDeliveryContent").append(`<div class="ui bottom attached ${activeTab === "procurement" ? "active " : ""}tab segment" data-tab="procurement"><div id="procurementDocumentContent"><div class="ui active centered inline loader"></div></div></div>`);
		$("#contractDeliveryContent .contract-section-tabs > .item").tab({onVisible:()=>this.resizeRabDescriptions()});
		this.resizeRabDescriptions();
		$("#contractDocumentForm .ui.dropdown").dropdown();
		this.initContractCalendars($("#contractDeliveryContent"));
        if(d.can_manage_documents===false){$("#contractRabForm, #contractScheduleForm").find(":input").prop("disabled",true);$('[data-delivery-action="import"],#contractPrintSettings').prop("disabled",true).addClass("disabled");}
		if (activeTab === "documents") $("#contractDeliveryContent .menu .item[data-tab=documents]").trigger("click");

		this.loadProcurementDocuments();
	}

	loadOfferPrintSettings() {
		window.Ajax.request({url:`/kontrak/penawaran/settings?contract_id=${this.contractId}`,method:"GET",success:r=>{
			const p=r.data||{},manage=this.deliveryData?.can_manage_documents!==false;
			$("#contractOfferPrintControls").html(`<div class="ui form" id="contractOfferPrintForm"><h4 class="ui header">Cetak Penawaran Rekanan</h4><p>Nilai cetak memakai rincian penawaran RAB yang tersimpan. Kertas, margin, font, dan spasi mengikuti pengaturan cetak.</p><div class="field"><label>Nomor surat penawaran</label><input name="nomor_surat" maxlength="255" required value="${this.esc(p.nomor_surat||'SPSE')}"></div><div class="field"><label>Tanggal surat penawaran</label><input name="tanggal_surat" type="date" required value="${this.esc(p.tanggal_surat||'')}"></div><div class="field"><label>Masa berlaku (hari kalender)</label><input name="masa_berlaku" type="number" min="1" max="365" required value="${Number(p.masa_berlaku||20)}"></div><div class="field"><div class="ui toggle checkbox"><input type="checkbox" name="kop_aktif" value="1" ${p.kop_aktif?'checked':''}><label>Kop rekanan</label></div></div><div class="field" id="offerLetterheadFile" ${p.kop_aktif?'':'style="display:none"'}><label>File kop rekanan (PNG, JPG, WebP; maksimal 3 MB)</label><input type="file" name="kop_file" accept="image/png,image/jpeg,image/webp"><small>${this.esc(p.kop_filename||'Pilih gambar kop rekanan')}</small></div><div class="ui error message"></div><div class="ui fluid vertical buttons"><button type="button" class="ui primary button" data-offer-print="bundle"><i class="file pdf icon"></i>Surat Penawaran & RAB</button><button type="button" class="ui button" data-offer-print="letter">Surat Penawaran</button><button type="button" class="ui button" data-offer-print="rab">Rincian Penawaran</button></div></div>`);
			const form=$("#contractOfferPrintForm");form.find('.ui.checkbox').checkbox();form.find('[name=kop_aktif]').on('change',()=>$('#offerLetterheadFile').toggle(form.find('[name=kop_aktif]').prop('checked')));
			if(!manage)form.find('input').prop('disabled',true);
			form.on('click','[data-offer-print]',e=>this.printOffer($(e.currentTarget).data('offer-print'),manage));
		}});
	}

	async printOffer(kind,manage=true) {
		const form=$("#contractOfferPrintForm"),buttons=form.find('[data-offer-print]');if(buttons.hasClass('loading'))return;
		form.removeClass('error');form.find('.field').removeClass('error');form.find('.prompt').remove();
		const showError=(message,errors={})=>{form.addClass('error');form.find('.error.message').text(message);Object.entries(errors).forEach(([name,messages])=>{const field=form.find(`[name="${name}"]`).closest('.field').addClass('error');$('<div class="ui pointing red basic label prompt"></div>').text(Array.isArray(messages)?messages.join(' '):String(messages)).appendTo(field);});};
		if(manage&&!form[0].checkValidity()){form[0].reportValidity();return;}
		buttons.addClass('loading disabled');
		try {
			if(manage){const data=new FormData(form.is('form')?form[0]:form.closest('form')[0]);data.set('contract_id',this.contractId);data.set('_csrf',window.CSRF_TOKEN||'');const saved=await fetch(window.appUrl('/kontrak/penawaran/save'),{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':window.CSRF_TOKEN||'','X-Requested-With':'XMLHttpRequest'},body:data});const result=await saved.json();if(!saved.ok||!result.success){showError(result.message||'Pengaturan belum dapat disimpan',result.errors||{});return;}form.find('#offerLetterheadFile small').text(result.data?.kop_filename||'Pilih gambar kop rekanan');}
			const response=await fetch(window.appUrl(`/kontrak/penawaran/pdf?contract_id=${this.contractId}&kind=${kind}`),{credentials:'same-origin'});
			if(!response.ok||!response.headers.get('Content-Type')?.includes('application/pdf')){const text=await response.text();let message=text;try{message=JSON.parse(text).message||text;}catch{}throw new Error(message);}
			const url=URL.createObjectURL(await response.blob()),a=document.createElement('a');a.href=url;a.download=`penawaran-${kind}-${this.contractId}.pdf`;document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);
		}catch(error){showError(error.message||'PDF penawaran belum dapat dibuat');}finally{buttons.removeClass('loading disabled');}
	}

	loadProcurementDocuments() {
		window.Ajax.request({url:`/kontrak/procurement/templates?contract_id=${this.contractId}`,method:"GET",success:r=>{const d=r.data||{},templates=d.templates||[];window.Ajax.request({url:`/kontrak/procurement/documents?contract_id=${this.contractId}`,method:"GET",success:x=>{const docs=x.data||[];const groups={};templates.forEach(t=>(groups[t.kelompok]??=[]).push(t));$("#procurementDocumentContent").html(`${d.contract?.cara_pengadaan==="PENYEDIA"?'<div class="ui basic segment"><button class="ui primary button" id="generateProcurementBundle"><i class="file pdf icon"></i>Generate Semua Dokumen - 1 PDF</button><span class="ui small text"> Termasuk sampul SPK dan dokumen berurutan.</span></div>':""}<div class="ui info message"><div class="header">Bentuk kontrak yang direkomendasikan: ${(d.recommended_form||'-').replaceAll('_',' ')}</div><p>Rekomendasi dihitung otomatis dari cara/jenis/metode pengadaan dan nilai kontrak menurut Pasal 28 Perpres 46/2025. Master disalin menjadi dokumen hasil sehingga narasi dapat diedit tanpa mengubah master.</p></div>${d.contract?.cara_pengadaan==="SWAKELOLA"?'<button class="ui primary button" id="generateAllSwakelola"><i class="magic icon"></i>Susun Semua Dokumen Paket</button><p>Isi otomatis dari data paket, pelaksana, RAB, dan jadwal. Dokumen yang sudah dibuat tetap tersimpan.</p>':""}<div class="ui stackable two column grid"><div class="column"><h4 class="ui header">Master Dokumen (${templates.length})</h4><p>${this.esc(d.contract?.cara_pengadaan==="SWAKELOLA"?"Swakelola Tipe "+d.contract.tipe_swakelola:"")} · ${this.esc(d.contract?.nama_penyedia_resmi||"Pelaksana belum dipilih")}</p>${Object.entries(groups).map(([name,rows])=>`<div class="ui segment"><div class="ui tiny teal ribbon label">${this.esc(name)}</div><div class="ui relaxed divided list">${rows.map(t=>`<div class="item"><div class="right floated content"><button class="ui mini ${t.direkomendasikan&&t.sesuai_nilai?'green':'basic'} button" data-procurement-create="${t.id}"><i class="edit icon"></i>Buat</button></div><div class="content"><b>${this.esc(t.nama)}</b>${t.direkomendasikan?'<span class="ui mini green label">Direkomendasikan</span>':''}<div class="description">${this.esc(t.sumber_format||'')} · ${this.esc(t.versi)}</div></div></div>`).join('')}</div></div>`).join('')}</div><div class="column"><h4 class="ui header">Dokumen Hasil Kontrak</h4>${docs.map(doc=>`<div class="ui segment"><div class="ui right floated mini buttons"><button class="ui basic blue button" data-procurement-edit="${doc.id}"><i class="edit icon"></i>Edit</button><button class="ui red button" data-procurement-pdf="${doc.id}"><i class="file pdf icon"></i>PDF</button><button class="ui basic red button" data-procurement-delete="${doc.id}" title="Hapus dokumen hasil"><i class="trash icon"></i>Hapus</button></div><b>${this.esc(doc.judul)}</b><div>${this.esc(doc.nomor_dokumen||'-')} · ${this.esc(doc.tanggal_dokumen)} · <span class="ui tiny label">${this.esc(doc.status)}</span></div><small>Master versi ${this.esc(doc.versi_master)} · ${this.esc(doc.pembuat_role||'')}</small></div>`).join('')||'<div class="ui message">Belum ada dokumen hasil.</div>'}</div></div>`);if(d.can_edit===false){$('#contractPrintSettings').prop('disabled',true).addClass('disabled');$('#procurementDocumentContent').find('[data-procurement-create],[data-procurement-edit],[data-procurement-delete],#generateAllSwakelola,#generateProcurementBundle').prop('disabled',true).addClass('disabled');}}});}});
	}

	generateAllSwakelola() {
		const button=$("#generateAllSwakelola");if(button.hasClass("loading"))return;button.addClass("loading disabled");
		window.Ajax.request({url:"/kontrak/procurement/generate-swakelola",method:"POST",data:{contract_id:this.contractId},success:r=>{if(r?.success)this.loadProcurementDocuments();},complete:()=>button.removeClass("loading disabled")});
	}
	rabNoteRow(x) {
		return `<tr class="active rab-note-row"><td><input type="hidden" data-rab="id" value="${x.id||""}"><input type="hidden" data-rab="type" value="KETERANGAN"><input data-rab="nomor" value="${this.esc(x.nomor??"")}" placeholder="Opsional"></td><td colspan="7"><div class="ui action input"><input data-rab="uraian" required value="${this.esc(x.uraian||"")}" placeholder="Judul divisi / keterangan" style="font-weight:bold"><button type="button" class="ui icon button" data-delivery-action="rab-note-up" title="Pindah ke atas"><i class="arrow up icon"></i></button><button type="button" class="ui icon button" data-delivery-action="rab-note-down" title="Pindah ke bawah"><i class="arrow down icon"></i></button><button type="button" class="ui icon button" data-delivery-action="rab-note-delete" title="Hapus keterangan"><i class="trash icon"></i></button></div></td></tr>`;
	}

	resizeRabDescriptions() {
		$('#rabRows textarea[data-rab="uraian"]:visible').each((_,input)=>{input.style.height="auto";input.style.height=`${input.scrollHeight+2}px`;});
	}

	recalculateRabTotals() {
		const rows=$("#rabRows tr").toArray(),values=rows.map(tr=>{const row=$(tr),volume=Number(row.find('[data-rab="volume"]').val())||0,price=Number(row.find('[data-rab="harga_satuan"]').val())||0,tax=Number(row.find('[data-rab="pajak"]').val())||0;return Math.round((volume*price*(1+tax/100)+Number.EPSILON)*100)/100;}),total=values.reduce((sum,x)=>sum+x,0);
		rows.forEach((tr,i)=>{$(tr).find('[data-rab-total]').text(this.money(values[i]));$(tr).find('[data-rab-weight]').text((total?100*values[i]/total:0).toFixed(2)+"%");});
	}
	openPrintSettings() {
		window.Ajax.request({url:`/kontrak/print-settings?contract_id=${this.contractId}`,method:"GET",success:r=>{
			const s=r.data||{};$("#contractPrintSettingsModal").remove();
			this.contractSettingsSidebar("Pengaturan Cetak Kontrak",`<div id="contractPrintSettingsModal"><div class="header"><i class="print icon"></i>Pengaturan Cetak Kontrak</div><div class="content"><div class="ui form" id="contractPrintSettingsForm"><p>Jenis/ukuran huruf dan halaman mengikuti Page Setup PDF. Paragraf rata kiri-kanan dengan baris terakhir rata kiri. Pengaturan berlaku untuk dokumen dan bundel kontrak ini.</p><div class="three fields"><div class="field"><label>Sebelum paragraf (pt)</label><input type="number" name="paragraph_before" min="0" max="72" step="0.5" required value="${Number(s.paragraph_before??0)}"></div><div class="field"><label>Sesudah paragraf (pt)</label><input type="number" name="paragraph_after" min="0" max="72" step="0.5" required value="${Number(s.paragraph_after??6)}"></div><div class="field"><label>Spasi baris</label><input type="number" name="line_spacing" min="0.5" max="3" step="0.05" required value="${Number(s.line_spacing??1.5)}"><small>Default 1,5 baris</small></div></div><div class="ui error message"></div></div></div><div class="ui fluid vertical buttons"><button class="ui button" type="button" id="resetContractPrintSettings">Default</button><button type="button" class="ui deny button">Batal</button><button class="ui primary button" type="button" id="saveContractPrintSettings"><i class="save icon"></i>Simpan</button></div></div>`);
			const modal=$("#contractPrintSettingsModal"),form=$("#contractPrintSettingsForm");
			modal.find("#resetContractPrintSettings").on("click",()=>{form.find('[name=paragraph_before]').val(0);form.find('[name=paragraph_after]').val(6);form.find('[name=line_spacing]').val(1.5);form.removeClass('error').find('.field').removeClass('error');});
			modal.find("#saveContractPrintSettings").on("click",()=>{
				let valid=true;form.find('input').each((_,input)=>{const ok=input.checkValidity();$(input).closest('.field').toggleClass('error',!ok);valid=valid&&ok;});
				form.toggleClass('error',!valid);if(!valid){form.find('.error.message').text('Isi spasi sebelum/sesudah antara 0–72 pt dan spasi baris antara 0,5–3.');return;}
				const data={contract_id:this.contractId};form.find(':input').serializeArray().forEach(x=>data[x.name]=x.value);
				const button=modal.find('#saveContractPrintSettings').addClass('loading disabled');
				window.Ajax.request({url:'/kontrak/print-settings/save',method:'POST',data,success:res=>{if(res.success)this.closeContractSettings();},complete:()=>button.removeClass('loading disabled')});
			});
			modal.find('.deny').on('click',()=>this.closeContractSettings());
		}});
	}

	async generateProcurementBundle() {
		const button=$("#generateProcurementBundle");if(button.hasClass("loading"))return;button.addClass("loading disabled");
		try {
			const response=await fetch(window.appUrl("/kontrak/procurement/bundle-pdf"),{method:"POST",credentials:"same-origin",headers:{"X-CSRF-TOKEN":window.CSRF_TOKEN||"","X-Requested-With":"XMLHttpRequest"},body:new URLSearchParams({contract_id:String(this.contractId),_csrf:window.CSRF_TOKEN||""})});
			if(!response.ok||!response.headers.get("Content-Type")?.includes("application/pdf"))throw new Error(await response.text());
			const url=URL.createObjectURL(await response.blob()),link=document.createElement("a");link.href=url;link.download=`bundel-dokumen-pengadaan-${this.contractId}.pdf`;document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);this.loadProcurementDocuments();
		}catch(error){Toast.show({success:false,message:error.message||"Dokumen belum dapat digabung"});}
		finally{button.removeClass("loading disabled");}
	}
	openProcurementEditor(masterId) {window.Ajax.request({url:`/kontrak/procurement/draft?contract_id=${this.contractId}&master_id=${masterId}`,method:"GET",success:r=>this.showProcurementEditor(r.data||{})});}
	openProcurementResult(id) {window.Ajax.request({url:`/kontrak/procurement/documents?id=${id}`,method:"GET",success:r=>this.showProcurementEditor(r.data||{})});}
	showProcurementEditor(data) {
		const master=data.master||{},contract=data.contract||this.deliveryData?.contract||{},sections=data.sections||[],code=String(data.kode_dokumen||master.kode||"").toUpperCase(),swakelolaMode=code.startsWith("SW_"),paragraphMode=["SURAT_PERJANJIAN","SSKK","SSUK"].includes(code);
		if(paragraphMode&&typeof DocumentBuilder!=="function"){
			if(this.procurementBuilderLoading)return;this.procurementBuilderLoading=true;const script=document.createElement("script");script.src=window.appUrl("/assets/js/engine/document/document_builder.js?v=20260913.59");script.onload=()=>{this.procurementBuilderLoading=false;this.showProcurementEditor(data);};script.onerror=()=>{this.procurementBuilderLoading=false;alert("Editor paragraf gagal dimuat. Silakan muat ulang halaman.");};document.body.appendChild(script);return;
		}
		$("#procurementEditorModal").remove();this.procurementSectionMap=[];this.procurementBuilder=null;this.swakelolaEditors=[];this.swakelolaSections=null;this.swakelolaData=null;
		$("body").append(`<div class="ui large modal" id="procurementEditorModal"><i class="close icon"></i><div class="header">${paragraphMode||swakelolaMode?'Editor Paragraf Dokumen':'Data Dokumen Fixed'}</div><div class="scrolling content"><form class="ui form" id="procurementDocumentForm"><input type="hidden" name="id" value="${data.id||''}"><input type="hidden" name="contract_id" value="${this.contractId}"><input type="hidden" name="master_id" value="${data.master_id||master.id||''}"><input type="hidden" name="document_code" value="${this.esc(code)}"><div class="three fields"><div class="required field"><label>Judul</label><input name="judul" value="${this.esc(data.judul||master.nama||'')}"></div><div class="field"><label>Nomor</label><input name="nomor_dokumen" value="${this.esc(data.nomor_dokumen||contract.nomor_kontrak||'')}"></div><div class="required field"><label>Tanggal</label><div class="ui calendar contract-calendar"><div class="ui input left icon"><i class="calendar icon"></i><input type="text" name="tanggal_dokumen" value="${this.esc(data.tanggal_dokumen||contract.tanggal_kontrak||'')}" autocomplete="off"></div></div></div></div><div class="field"><label>Status</label><select class="ui dropdown" name="status">${['DRAFT','DIAJUKAN','DISETUJUI','DITANDATANGANI'].map(s=>`<option ${s===(data.status||'DRAFT')?'selected':''}>${s}</option>`).join('')}</select></div>${swakelolaMode?'<div id="procurementParagraphEditor"></div>':paragraphMode?'<div class="ui visible info message"><i class="paragraph icon"></i>Isi diedit per paragraf. Gunakan toolbar pada setiap baris untuk jenis paragraf, penomoran, perataan, tebal, miring, dan garis bawah. Tidak ada kode HTML yang perlu diisi.</div><div id="procurementParagraphEditor"></div>':'<div class="ui positive icon message"><i class="lock icon"></i><div class="content"><div class="header">Format dokumen dikunci sesuai contoh resmi</div><p>Garis, susunan kolom, judul, kop, dan halaman dibentuk otomatis. Pengguna cukup mengisi judul, nomor, tanggal, dan data kontrak.</p></div></div>'}</form></div><div class="actions"><button type="button" class="ui deny button">Batal</button><button class="ui primary button" id="saveProcurementDocument"><i class="save icon"></i>Simpan Dokumen</button></div></div>`);
		if(swakelolaMode){this.swakelolaData=typeof data.data_isian==="string"?JSON.parse(data.data_isian||"{}"):data.data_isian||{};this.renderSwakelolaEditor(sections,data.kop_surat||{});}
		if(paragraphMode){const schema={sections:[]},values={};sections.forEach((section,index)=>{const key=`procurement_section_${index}`;this.procurementSectionMap.push({key,code:section.kode_bagian,title:section.judul});schema.sections.push({type:"editable_table",name:key,label:`${index+1}. ${section.judul}`,columns:["URAIAN"]});values[key]=this.procurementParagraphs(section.isi||section.isi_template||"",contract,data);});this.procurementBuilder=new DocumentBuilder($("#procurementParagraphEditor"));this.procurementBuilder.schema=schema;this.procurementBuilder.data=values;this.procurementBuilder.render();}
		$("#procurementEditorModal .ui.dropdown").dropdown();this.initContractCalendars($("#procurementEditorModal"));$("#procurementEditorModal").modal({closable:false}).modal("show");
	}
	renderSwakelolaEditor(sections,kop={}) {
		this.swakelolaSections=sections.map(section=>({...section,blocks:this.normalizeSwakelolaLayout(JSON.parse(section.isi||section.isi_template||"[]"))}));
		this.swakelolaNodes=new Map();this.swakelolaParents=new Map();this.swakelolaTables=new Map();this.swakelolaActive=null;this.swakelolaCell=null;this.swakelolaKop=kop;
		$("#procurementEditorModal").addClass("swakelola-modal");
		if(!$("#swakelolaEditorStyle").length)$("head").append(`<style id="swakelolaEditorStyle">
		.swakelola-modal{width:min(1180px,96vw)!important}.swakelola-modal .scrolling.content{background:#f3f5f7!important;padding:20px!important}.sw-toolbar{position:sticky;top:-20px;z-index:30;display:flex;align-items:center;gap:5px;flex-wrap:wrap;padding:12px 16px;background:#fff;border:1px solid #dde3e8;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px #0000000a}.sw-toolbar .ui.button{margin:0!important}.sw-toolbar .sw-hint{margin-left:auto;color:#657180;font-size:12px}.sw-sheet{background:#fff;max-width:880px;margin:0 auto 20px;padding:42px 44px;border:1px solid #e0e5e9;border-radius:4px;box-shadow:0 3px 12px #00000008;font-size:14px;line-height:1.65}.sw-sheet p{margin:0 0 12px;white-space:pre-wrap;overflow-wrap:anywhere;min-height:1.5em;outline:none;border-radius:3px}.sw-sheet p:focus{background:#f0f7ff;box-shadow:0 0 0 2px #b9d8f4}.sw-sheet .sw-table-wrap{overflow-x:auto}.sw-sheet table.sw-rab{min-width:620px}.sw-sheet table.sw-rab td{font-size:12px;padding:8px}.sw-sheet table.sw-rab td:nth-child(n+5){white-space:nowrap}.sw-sheet table{width:100%;border-collapse:collapse;table-layout:fixed;margin:16px 0 24px}.sw-sheet td{position:relative;border:1px solid #b9c2cb;padding:10px 12px;vertical-align:top;overflow-wrap:anywhere}.sw-sheet table.sw-borderless>tbody>tr>td{border:0}.sw-sheet table.sw-borderless{margin:8px 0}.sw-sheet td.sw-selected{box-shadow:inset 0 0 0 2px #2185d0}.sw-col-resize{position:absolute;right:-4px;top:0;bottom:0;width:8px;cursor:col-resize;z-index:2;touch-action:none}.sw-col-resize:hover{background:#2185d066}.sw-table-tools{display:flex;gap:10px;flex-wrap:wrap}.sw-table-tool-group{display:flex;align-items:center;gap:5px}.sw-table-tool-group>span{font-size:11px;color:#526575}.sw-context-menu.ui.menu{position:fixed;z-index:10050;margin:0;min-width:290px;max-width:calc(100vw - 16px);background:#fff;color:#263746;box-shadow:0 5px 24px #0003;border:1px solid #c9d5df;border-radius:6px;padding:10px}.sw-context-menu .sw-table-tools{display:block}.sw-context-menu .sw-table-tool-group{justify-content:space-between;padding:8px 2px}.sw-context-menu .sw-table-tool-group>span{font-size:13px;min-width:54px}.sw-context-menu[hidden]{display:none!important}.sw-context-caption{font-size:12px;padding:3px 2px 9px;border-bottom:1px solid #e1e7ed;color:#526575}.sw-sheet td p{margin-bottom:7px}.sw-sheet td p:last-child{margin-bottom:0}.sw-kop{font-size:12px;color:#617487;border-bottom:2px solid #a6b2bf;text-align:center;padding:8px 0 16px;margin-bottom:24px}.sw-auto-note{max-width:880px;margin:0 auto 16px;color:#4a6175;font-size:13px}.sw-data-warning{max-width:880px;margin:0 auto 16px!important}@media(max-width:700px){.sw-sheet{padding:22px 16px}.sw-sheet td{padding:7px;font-size:12px}.sw-toolbar .sw-hint{display:none}}
		</style>`);
		const safeImage=value=>/^data:image\/(?:png|jpeg|webp);base64,[A-Za-z0-9+/=]+$/.test(value||"")?value:"";
		const kopImage=safeImage(kop.gambar_kop),leftLogo=safeImage(kop.logo_kiri),rightLogo=safeImage(kop.logo_kanan);
		const kopHtml=kop.gunakan_gambar_kop&&kopImage?`<img alt="Kop surat resmi" src="${kopImage}" style="width:100%;height:auto">`:`<div style="display:flex;align-items:center;gap:16px">${leftLogo?`<img alt="Logo kiri" src="${leftLogo}" style="width:64px;max-height:80px;object-fit:contain">`:""}<div style="flex:1;text-align:center;color:#111"><div style="font-size:15px;font-weight:bold">${this.esc(kop.nama_pemerintah||"")}</div><div style="font-size:20px;font-weight:bold">${this.esc(kop.nama_opd||"")}</div><div>${this.esc(kop.alamat||"")}</div><div>${this.esc(kop.kontak||"")}</div></div>${rightLogo?`<img alt="Logo kanan" src="${rightLogo}" style="width:64px;max-height:80px;object-fit:contain">`:""}</div>`;
		const render=(blocks,locked=false)=>blocks.map(block=>{
            if(block.type==="table"){
                const key=String(this.swakelolaTables.size),readonly=locked||block.readonly;
                this.swakelolaTables.set(key,{block,readonly});
                return `<div class="sw-table-wrap table-scroll-region"><table data-sw-table="${key}" class="${block.layout==="rab"?"sw-rab":""} ${block.borderless?"sw-borderless":""}"><colgroup>${block.column_widths.map(w=>`<col style="width:${Number(w)}%">`).join("")}</colgroup><tbody>${block.rows.map((row,r)=>{let column=0;return `<tr>${row.map((cell,c)=>{const start=column;column+=Number(cell.colspan)||1;return `<td data-sw-row="${r}" data-sw-cell="${c}" colspan="${Number(cell.colspan)||1}">${render(cell.blocks,readonly)}${column<block.column_widths.length?`<span contenteditable="false" class="sw-col-resize" data-sw-boundary="${column}" title="Geser lebar kolom" role="separator" aria-orientation="vertical"></span>`:""}</td>`;}).join("")}</tr>`;}).join("")}</tbody></table></div>`;
            }
			if(/^\s*\[(?:Kop|Logo)[^\]]*\]\s*$/i.test(block.text||""))return "";
			const key=String(this.swakelolaNodes.size);this.swakelolaNodes.set(key,block);this.swakelolaParents.set(key,blocks);
			const styles=block.style||(block.bold?["bold"]:[]),align=["left","center","right","justify"].includes(block.align)?block.align:"left";
			return `<p contenteditable="${locked?"false":"true"}" role="${locked?"paragraph":"textbox"}" aria-label="Paragraf dokumen" data-sw-node="${key}" style="text-align:${align};font-weight:${styles.includes("bold")?"bold":"normal"};font-style:${styles.includes("italic")?"italic":"normal"};text-decoration:${styles.includes("underline")?"underline":"none"}">${this.esc(block.text||"")}</p>`;
		}).join("");
		$("#procurementParagraphEditor").html(`<div class="sw-toolbar" role="toolbar" aria-label="Format paragraf"><button type="button" class="ui mini button" data-sw-style="bold" title="Tebal"><b>B</b></button><button type="button" class="ui mini button" data-sw-style="italic" title="Miring"><i>I</i></button><button type="button" class="ui mini button" data-sw-style="underline" title="Garis bawah"><u>U</u></button>${[["left","Rata kiri"],["center","Tengah"],["right","Rata kanan"],["justify","Rata penuh"]].map(([value,label])=>`<button type="button" class="ui mini basic button" data-sw-align="${value}">${label}</button>`).join("")}${this.swakelolaParagraphButtons()}${this.swakelolaTableButtons()}<span class="sw-hint">Pilih sel untuk mengatur baris/kolom · geser batas kolom</span></div><div class="sw-auto-note"><i class="check circle green icon"></i>Dokumen disusun otomatis dari master swakelola dan data paket.</div>${this.swakelolaSections.map(section=>`<article class="sw-sheet"><div class="sw-kop">${kopHtml}</div>${render(section.blocks)}</article>`).join("")}`);
		const editor=$("#procurementParagraphEditor");editor.off(".swakelola");
        this.bindSwakelolaTables(editor);this.bindSwakelolaParagraphs(editor);
		if(editor.find(".sw-rab").length)editor.find(".sw-auto-note").after('<div class="sw-auto-note">Rincian RAB ditarik dari RAB kontrak. <button type="button" class="ui mini basic blue button" data-sw-open-rab>Buka RAB Kontrak</button></div>');
		editor.on("click.swakelola","[data-sw-open-rab]",()=>{$("#procurementEditorModal").modal("hide");this.openDelivery(this.contractId,"rab");});
		const missing=this.swakelolaData?.data_belum_tersedia||[];if(missing.length)editor.find(".sw-auto-note").after(`<div class="ui visible warning message sw-data-warning"><b>Data sumber belum lengkap.</b> ${missing.length} rincian belum tercatat pada paket/referensi dan ditandai — pada dokumen. Lengkapi data paket untuk pengisian otomatis.</div>`);
		editor.on("focusin.swakelola click.swakelola","[data-sw-node]",e=>{this.swakelolaActive=e.currentTarget.contentEditable!=="false"?e.currentTarget:null;editor.find("[data-sw-paragraph-action]").prop("disabled",!this.swakelolaActive);});
		editor.on("mousedown.swakelola",".sw-toolbar button,.sw-context-menu button",e=>e.preventDefault());
		editor.on("click.swakelola","[data-sw-style],[data-sw-align]",e=>{
			const element=this.swakelolaActive;if(!element)return;
			const block=this.swakelolaNodes.get(element.dataset.swNode),button=e.currentTarget;
			if(button.dataset.swStyle){const style=button.dataset.swStyle;let styles=block.style||(block.bold?["bold"]:[]);styles=styles.includes(style)?styles.filter(x=>x!==style):[...styles,style];block.style=styles;block.bold=styles.includes("bold");element.style.fontWeight=block.bold?"bold":"normal";element.style.fontStyle=styles.includes("italic")?"italic":"normal";element.style.textDecoration=styles.includes("underline")?"underline":"none";}
			if(button.dataset.swAlign){block.align=button.dataset.swAlign;element.style.textAlign=block.align;}
		});
	}
    normalizeSwakelolaLayout(blocks){
        const out=[],cell=text=>({colspan:1,blocks:[{type:"paragraph",text,align:"left"}]});
        for(let b of blocks){
            if(b.type==="table"){
                b.rows.forEach(row=>row.forEach(c=>{c.colspan=Number(c.colspan)||1;c.blocks=this.normalizeSwakelolaLayout(c.blocks);}));
                if(b.layout==="identity"&&b.column_widths?.length===3&&b.rows.every(r=>r.length===3&&r.every(c=>c.colspan===1))&&b.rows.some(r=>/^[•●]\s+/.test(r[0].blocks[0]?.text||""))){
                    b.rows.forEach(r=>{const first=r[0].blocks[0],match=(first?.text||"").match(/^([•●])\s+(.*)$/s);if(match)first.text=match[2];r.unshift({colspan:1,blocks:[{type:"paragraph",text:match?match[1]:"",align:"left"}]});});
                    const [label,colon,value]=b.column_widths,marker=Math.min(5,label/4);b.column_widths=[marker,label-marker,colon,value];
                }
                const count=Math.max(1,...b.rows.map(row=>row.reduce((n,c)=>n+(Number(c.colspan)||1),0))),first=String(b.rows[0]?.[0]?.blocks[0]?.text||"").trim();
                if(!b.column_widths?.length)b.column_widths=/^No\.?$/i.test(first)&&count>1?[6,...Array(count-1).fill(94/(count-1))]:Array(count).fill(100/count);
                if(first==="→"&&b.rows.length===1&&count===2&&!b.borderless){b.borderless=true;b.column_widths=[6,94];}
            }else{
                b.text=String(b.text||"").replace(/^\s*([•●▪◦–-]|\([a-zA-Z0-9]+\)|\d+(?:\.\d+)*[.)]|[a-zA-Z][.)]|[ivxlcdmIVXLCDM]+[.)])[ \t]+/,"$1 ");
                const m=String(b.text||"").match(/^\s*((?:[•●]\s*)?(?:Paket Pengadaan|Instansi Penanggung\s*Jawab Anggaran|Tahun Anggaran|Sasaran\/Output|Nama\b[^:\n]{0,130}|Alamat\b[^:\n]{0,130}|E-?mail\b[^:\n]{0,130}|Website\b[^:\n]{0,130}|Nomor\b[^:\n]{0,100}|NIP|NPWP|Jabatan|Pangkat(?:\/Golongan)?))\s*:\s*([\s\S]*)$/i);
                if(m){const row=[cell(m[1]),cell(":"),cell(m[2])];if(out.at(-1)?.layout==="identity"){out.at(-1).rows.push(row);continue;}b={type:"table",layout:"identity",borderless:true,column_widths:[38,7,55],rows:[row]};}
            }out.push(b);
        }return out.map(b=>b.layout==="identity"&&b.column_widths.length===3&&b.rows.some(r=>/^[•●]\s+/.test(r[0]?.blocks[0]?.text||""))?this.normalizeSwakelolaLayout([b])[0]:b);
    }
    swakelolaParagraphButtons(){
        return `<div class="sw-table-tool-group"><span>Paragraf</span><div class="ui mini icon buttons" role="group" aria-label="Paragraf">${[["before","Sisip paragraf sebelum kursor","plus square outline"],["after","Sisip paragraf sesudah kursor","plus square"],["delete","Hapus paragraf pada kursor","trash alternate outline"]].map(([action,label,icon])=>`<button type="button" class="ui ${action==="delete"?"red":"blue"} basic icon button" data-sw-paragraph-action="${action}" aria-label="${label}" title="${label}" disabled><i class="${icon} icon"></i></button>`).join("")}</div></div>`;
    }
    refreshSwakelolaEditor(target,offset=0){
        let ordinal=0,focus=-1;
        const walk=blocks=>blocks.forEach(b=>{if(b.type==="table")b.rows.forEach(r=>r.forEach(c=>walk(c.blocks)));else if(!/^\s*\[(?:Kop|Logo)[^\]]*\]\s*$/i.test(b.text||"")){if(b===target)focus=ordinal;ordinal++;}});
        this.swakelolaSections.forEach(s=>walk(s.blocks));
        const sections=this.swakelolaSections.map(s=>({...s,isi:JSON.stringify(s.blocks)})),scroller=$("#procurementEditorModal .scrolling.content"),scroll=scroller.scrollTop();
        this.renderSwakelolaEditor(sections,this.swakelolaKop);scroller.scrollTop(scroll);
        const element=document.querySelector(`#procurementParagraphEditor [data-sw-node="${focus}"]`);
        if(element&&element.contentEditable!=="false"){
            element.focus({preventScroll:true});const range=document.createRange(),selection=window.getSelection();
            if(element.firstChild){range.setStart(element.firstChild,Math.min(offset,element.firstChild.textContent.length));}else range.selectNodeContents(element);
            range.collapse(true);selection.removeAllRanges();selection.addRange(range);
        }
    }
    bindSwakelolaParagraphs(editor){
        const hang=element=>{
            const match=element.innerText.match(/^(\s*(?:[•●▪◦–-]|\([a-zA-Z0-9]+\)|\d+(?:\.\d+)*[.)]|[a-zA-Z][.)]|[ivxlcdmIVXLCDM]+[.)])\s+)/);
            element.style.paddingLeft="";element.style.textIndent="";
            if(match){const canvas=document.createElement("canvas"),context=canvas.getContext("2d");context.font=getComputedStyle(element).font;let width=context.measureText(match[1]).width;
                if(element.firstChild?.nodeType===3&&element.firstChild.textContent.length>=match[1].length){const range=document.createRange();range.setStart(element.firstChild,0);range.setEnd(element.firstChild,match[1].length);width=range.getBoundingClientRect().width||width;}
                element.style.paddingLeft=width+"px";element.style.textIndent=-width+"px";}
        };
        editor.find("[data-sw-node]").each((i,e)=>hang(e));
        editor.on("input.swakelola","[data-sw-node]",e=>hang(e.currentTarget));
        const remove=(block,blocks,index)=>{
            blocks.splice(index,1);if(!blocks.length)blocks.push({type:"paragraph",text:"",align:"left"});
            return blocks[Math.min(index,blocks.length-1)];
        };
        editor.on("click.swakelola","[data-sw-paragraph-action]",e=>{
            const element=this.swakelolaActive;if(!element)return;
            this.collectSwakelolaSections();const block=this.swakelolaNodes.get(element.dataset.swNode),blocks=this.swakelolaParents.get(element.dataset.swNode),index=blocks.indexOf(block),action=e.currentTarget.dataset.swParagraphAction;
            let target;
            if(action==="delete")target=remove(block,blocks,index);
            else{target={type:"paragraph",text:"",align:block.align||"left"};blocks.splice(index+(action==="after"?1:0),0,target);}
            this.refreshSwakelolaEditor(target);
        });
        editor.on("keydown.swakelola","[data-sw-node]",e=>{
            const element=e.currentTarget;if(element.contentEditable==="false"||e.isComposing||e.originalEvent?.isComposing)return;
            const selection=window.getSelection();if(!selection.rangeCount)return;
            const range=selection.getRangeAt(0);if(!element.contains(range.startContainer)||!element.contains(range.endContainer))return;
            const before=range.cloneRange();before.selectNodeContents(element);before.setEnd(range.startContainer,range.startOffset);
            const start=before.toString().length,text=element.innerText,end=start+range.toString().length;
            const block=this.swakelolaNodes.get(element.dataset.swNode),blocks=this.swakelolaParents.get(element.dataset.swNode),index=blocks.indexOf(block);
            if(e.key==="Enter"&&!e.shiftKey){
                e.preventDefault();this.collectSwakelolaSections();block.text=text.slice(0,start);
                const next={...block,text:text.slice(end)};blocks.splice(index+1,0,next);this.refreshSwakelolaEditor(next);return;
            }
            if(!selection.isCollapsed)return;
            if((e.key==="Backspace"&&start===0)||(e.key==="Delete"&&start===text.length)){
                if(!text.trim()){e.preventDefault();this.collectSwakelolaSections();this.refreshSwakelolaEditor(remove(block,blocks,index));return;}
                const other=blocks[index+(e.key==="Backspace"?-1:1)];if(other?.type!=="paragraph")return;
                e.preventDefault();this.collectSwakelolaSections();
                if(e.key==="Backspace"){const offset=other.text.length;other.text+=block.text;blocks.splice(index,1);this.refreshSwakelolaEditor(other,offset);}
                else{const offset=block.text.length;block.text+=other.text;blocks.splice(index+1,1);this.refreshSwakelolaEditor(block,offset);}
            }
        });
    }
    swakelolaTableButtons(){
        const groups=[
            ["Baris",[["row-before","Tambah baris di atas","plus square outline"],["row-after","Tambah baris di bawah","plus square"],["row-delete","Hapus baris","trash alternate outline"]]],
            ["Kolom",[["col-before","Tambah kolom kiri","plus square outline"],["col-after","Tambah kolom kanan","plus square"],["col-delete","Hapus kolom","trash alternate outline"]]],
            ["Sel",[["cell-before","Sisip sel sebelum kursor (geser kanan)","plus square outline"],["cell-after","Sisip sel sesudah kursor (geser kanan)","plus square"],["cell-delete","Hapus sel (geser kiri)","trash alternate outline"]]]
        ];
        return `<div class="sw-table-tools">${groups.map(([label,actions])=>`<div class="sw-table-tool-group"><span>${label}</span><div class="ui mini icon buttons" role="group" aria-label="${label}">${actions.map(([action,title,icon])=>`<button type="button" class="ui ${action.endsWith("delete")?"red":"blue"} basic icon button" data-sw-table-action="${action}" title="${title}" aria-label="${title}" disabled><i class="${icon} icon"></i></button>`).join("")}</div></div>`).join("")}</div>`;
    }
    bindSwakelolaTables(editor){
        editor.append(`<div class="ui vertical menu sw-context-menu" role="dialog" aria-label="Pengaturan tabel" hidden><div class="sw-context-caption">Tabel · sebelum / sesudah / hapus <button type="button" class="ui mini basic icon button right floated" data-sw-context-close title="Tutup panel tabel" aria-label="Tutup panel tabel"><i class="close icon"></i></button></div>${this.swakelolaParagraphButtons()}${this.swakelolaTableButtons()}</div>`);
        const menu=editor.find(".sw-context-menu"),close=()=>menu.prop("hidden",true);
        menu.on("click.swakelola","[data-sw-context-close]",close);
        $(document).off(".swakelolaContext").on("pointerdown.swakelolaContext",e=>{if(!$(e.target).closest(".sw-context-menu").length)close();}).on("keydown.swakelolaContext",e=>{if(e.key==="Escape")close();});
        editor.closest(".scrolling.content").off("scroll.swakelolaContext").on("scroll.swakelolaContext",close);
        editor.on("contextmenu.swakelola","td[data-sw-row]",e=>{
            const td=$(e.target).closest("td[data-sw-row]")[0];if(td!==e.currentTarget)return;
            $(td).trigger("click");const paragraph=$(e.target).closest("[data-sw-node]")[0];
            if(paragraph&&paragraph.contentEditable!=="false"){this.swakelolaActive=paragraph;editor.find("[data-sw-paragraph-action]").prop("disabled",false);}
            if(!this.swakelolaCell){close();return;}
            menu.prop("hidden",false).css({left:0,top:0});const rect=menu[0].getBoundingClientRect();
            // Keep the browser context menu intact; show application tools beside the editor instead of replacing it.
            const sheet=td.closest(".sw-sheet").getBoundingClientRect(),toolbar=editor.find(".sw-toolbar")[0].getBoundingClientRect();
            menu.css({left:Math.max(8,Math.min(sheet.right-rect.width,window.innerWidth-rect.width-8)),top:Math.max(8,Math.min(toolbar.bottom+8,window.innerHeight-rect.height-8))});
        });
        editor.on("click.swakelola focusin.swakelola","td[data-sw-row]",e=>{
            const td=$(e.target).closest("td[data-sw-row]")[0];if(td!==e.currentTarget)return;
            const table=td.closest("table[data-sw-table]"),entry=this.swakelolaTables.get(table.dataset.swTable);
            this.swakelolaCell=entry.readonly?null:{td,table,...entry,row:Number(td.dataset.swRow),cell:Number(td.dataset.swCell)};
            editor.find(".sw-selected").removeClass("sw-selected");if(this.swakelolaCell)$(td).addClass("sw-selected");
            editor.find("[data-sw-table-action]").prop("disabled",!this.swakelolaCell);
            if(this.swakelolaCell){editor.find('[data-sw-table-action="row-delete"]').prop("disabled",entry.block.rows.length<=1);editor.find('[data-sw-table-action="col-delete"]').prop("disabled",entry.block.column_widths.length<=1);}
        });
        editor.on("pointerdown.swakelola","[data-sw-boundary]",e=>{
            e.preventDefault();e.stopPropagation();const handle=e.currentTarget,table=handle.closest("table"),entry=this.swakelolaTables.get(table.dataset.swTable),boundary=Number(handle.dataset.swBoundary);
            if(entry.readonly)return;
            const widths=entry.block.column_widths,start=e.originalEvent.clientX,total=table.getBoundingClientRect().width,left=widths[boundary-1],right=widths[boundary],min=Math.min(2,(left+right)/4);
            const move=event=>{const delta=Math.max(min-left,Math.min(right-min,(event.clientX-start)/total*100));widths[boundary-1]=left+delta;widths[boundary]=right-delta;Array.from(table.querySelector(":scope > colgroup").children).forEach((col,i)=>col.style.width=widths[i]+"%");};
            const end=()=>{document.removeEventListener("pointermove",move);document.removeEventListener("pointerup",end);document.removeEventListener("pointercancel",end);};
            document.addEventListener("pointermove",move);document.addEventListener("pointerup",end);document.addEventListener("pointercancel",end);
        });
        editor.on("click.swakelola","[data-sw-table-action]",e=>{
            const active=this.swakelolaCell;if(!active)return;
            this.collectSwakelolaSections();
            this.editSwakelolaTable(active.block,active.row,active.cell,e.currentTarget.dataset.swTableAction);
            const sections=this.swakelolaSections.map(section=>({...section,isi:JSON.stringify(section.blocks)})),scroll=$("#procurementEditorModal .scrolling.content").scrollTop();
            this.renderSwakelolaEditor(sections,this.swakelolaKop);
            $("#procurementEditorModal .scrolling.content").scrollTop(scroll);
        });
    }
    editSwakelolaTable(table,rowIndex,cellIndex,action){
        const empty=()=>({colspan:1,blocks:[{type:"paragraph",text:"",align:"left"}]}),row=table.rows[rowIndex],start=row.slice(0,cellIndex).reduce((n,c)=>n+(Number(c.colspan)||1),0),span=Number(row[cellIndex].colspan)||1,n=table.column_widths.length;
        if(action.startsWith("cell-")){
            if(action==="cell-delete"){
                row.splice(cellIndex,1);for(let i=0;i<span;i++)row.push(empty());
            }else{
                if(span>1){row[cellIndex].colspan=span-1;row.splice(cellIndex+(action==="cell-after"?1:0),0,empty());}
                else{
                    row.splice(cellIndex+(action==="cell-after"?1:0),0,empty());
                    table.rows.forEach((r,i)=>{if(i!==rowIndex)r.push(empty());});
                    table.column_widths.push(100/n);const total=table.column_widths.reduce((a,b)=>a+b,0);table.column_widths=table.column_widths.map(w=>w/total*100);
                }
            }return;
        }
        if(action==="row-delete"){if(table.rows.length>1)table.rows.splice(rowIndex,1);return;}
        if(action.startsWith("row-")){table.rows.splice(rowIndex+(action==="row-after"?1:0),0,Array.from({length:n},empty));return;}
        if(action==="col-delete"){
            if(n===1)return;
            // Delete the logical column containing the cursor; merged cells shrink by one.
            table.rows.forEach(r=>{let col=0;for(let i=0;i<r.length;i++){const width=Number(r[i].colspan)||1;if(start>=col&&start<col+width){if(width>1)r[i].colspan=width-1;else r.splice(i,1);break;}col+=width;}});
            table.column_widths.splice(start,1);
        }else{
            const at=start+(action==="col-after"?span:0);
            table.rows.forEach(r=>{let col=0;for(let i=0;i<r.length;i++){const width=Number(r[i].colspan)||1;if(at===col){r.splice(i,0,empty());return;}if(at>col&&at<col+width){r[i].colspan=width+1;return;}col+=width;}r.push(empty());});
            table.column_widths.splice(at,0,100/n);
        }
        const sum=table.column_widths.reduce((a,b)=>a+b,0);table.column_widths=table.column_widths.map(w=>w/sum*100);
    }
	collectSwakelolaSections() {
		$("#procurementParagraphEditor [data-sw-node]").each((i,element)=>{const block=this.swakelolaNodes.get(element.dataset.swNode);if(block)block.text=element.innerText;});
		return this.swakelolaSections.map(section=>({kode_bagian:section.kode_bagian,judul:section.judul,isi:JSON.stringify(section.blocks)}));
	}
	procurementParagraphs(raw,contract,documentData){let parsed=null;try{parsed=typeof raw==="string"?JSON.parse(raw):raw;}catch(e){parsed=null;}let rows=Array.isArray(parsed)?parsed:[];if(!rows.length){const holder=document.createElement("div");holder.innerHTML=String(raw||"");rows=Array.from(holder.querySelectorAll("p,li,h1,h2,h3,h4,tr")).map(node=>({type:node.matches("li")?"numbered":"paragraph",align:node.matches("h1,h2,h3,h4")?"center":"justify",style:node.matches("h1,h2,h3,h4")?["bold"]:[],text:node.textContent.trim()})).filter(x=>x.text);if(!rows.length&&holder.textContent.trim())rows=[{type:"paragraph",align:"justify",text:holder.textContent.trim()}];}const vars={nama_opd:window.app?.user?.nama_opd||window.app?.user?.nama_org||contract.kd_opd||"-",nama_dokumen:documentData.judul||"-",nomor_dokumen:documentData.nomor_dokumen||contract.nomor_kontrak||"-",tanggal_dokumen:documentData.tanggal_dokumen||contract.tanggal_kontrak||"-",nama_paket:contract.uraian_kontrak||"-",kd_sub_keg:contract.kd_sub_keg||"-",sumber_dana:contract.sumber_dana||"APBD",nilai_kontrak:this.money(contract.nilai_kontrak||0),nama_ppk:contract.nama_ppk_resmi||contract.nama_ppk||"-",nip_ppk:contract.nip_ppk_resmi||contract.nip_ppk||"-",nama_penyedia:contract.nama_penyedia_resmi||contract.nama_penyedia||"-"};return rows.map(row=>{if(typeof row==="string")row={text:row};let text=String(row.text||"");Object.entries(vars).forEach(([key,value])=>text=text.replaceAll(`{{${key}}}`,value));return{type:row.type||"paragraph",align:row.align||"justify",style:Array.isArray(row.style)?row.style:[],...(row.format?{format:row.format}:{}),text};}).filter(row=>row.text.trim());}
	procurementAttachmentRow(a) {return `<div class="ui segment procurement-attachment"><button type="button" class="ui right floated mini red icon button" data-procurement-attachment-delete><i class="trash icon"></i></button><div class="two fields"><div class="field"><label>Jenis</label><input data-attachment-type value="${this.esc(a.jenis_lampiran||'NARASI')}"></div><div class="field"><label>Judul Lampiran</label><input data-attachment-title value="${this.esc(a.judul||'')}"></div></div><div class="field"><label>Isi Lampiran</label><textarea data-attachment-body rows="6">${this.esc(a.isi||'')}</textarea></div></div>`;}
	saveProcurementDocument() {const f=$("#procurementDocumentForm"),sections=[],structure=this.procurementBuilder?.collectStructure?.()||{};(this.procurementSectionMap||[]).forEach(section=>sections.push({kode_bagian:section.code,judul:section.title,isi:JSON.stringify(structure[section.key]||[])}));const payload={id:f.find('[name=id]').val(),contract_id:f.find('[name=contract_id]').val(),master_id:f.find('[name=master_id]').val(),judul:f.find('[name=judul]').val(),nomor_dokumen:f.find('[name=nomor_dokumen]').val(),tanggal_dokumen:f.find('[name=tanggal_dokumen]').val(),status:f.find('[name=status]').val(),sections:JSON.stringify(this.swakelolaSections?this.collectSwakelolaSections():sections),data_isian:JSON.stringify(this.swakelolaData||{}),attachments:"[]"};window.Ajax.request({url:"/kontrak/procurement/save",method:"POST",data:payload,success:r=>{if(r?.success){$("#procurementEditorModal").modal("hide");this.procurementBuilder=null;this.loadProcurementDocuments();}}});}

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
		if (a === "sskk" || a === "ssuk") {
			this.download(`/kontrak/terms_pdf?contract_id=${this.contractId}&type=${a.toUpperCase()}`);
			return;
		}
		if (a === "excel") {
			this.download(`/kontrak/rab_excel?contract_id=${this.contractId}`);
			return;
		}
		if (a === "pdf") {
			this.download(`/kontrak/rab_pdf?contract_id=${this.contractId}`);
			return;
		}
		if (a === "import") {
            this.openRabImportSettings();
			return;
		}
		if(a==="add-rab-note"){$("#rabRows").append(this.rabNoteRow({}));return;}
		if(["rab-note-up","rab-note-down","rab-note-delete"].includes(a)){const row=button.closest("tr");if(a==="rab-note-up")row.prev().before(row);else if(a==="rab-note-down")row.next().after(row);else row.remove();return;}
		if (a === "add-rab") {
			$("#rabRows").append(
				'<tr><td><input type="hidden" data-rab="id"><input data-rab="nomor" required></td><td><textarea rows="2" data-rab="uraian" aria-label="Uraian pekerjaan" required></textarea></td><td><input data-rab="satuan" required></td><td><input type="number" min="0.000000000001" step="any" data-rab="volume" required></td><td><input type="number" min="0.01" step="any" data-rab="harga_satuan" required></td><td><input type="number" min="0" max="100" step="any" value="0" data-rab="pajak" aria-label="Pajak persen"></td><td data-rab-total>-</td><td data-rab-weight>-</td></tr>',
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
			? { contract_id: this.contractId, stage:this.rabStage||'negoisasi', items: JSON.stringify(collect("#rabRows tr", "rab")) }
			: { contract_id: this.contractId, weeks: JSON.stringify(collect("#contractScheduleForm tbody tr", "week")) };
		button.addClass("loading disabled");
		window.Ajax.request({
			url: isRab ? "/kontrak/rab/save" : "/kontrak/schedule/save",
			method: "POST",
			data: payload,
			success: () => {this.rabDirty=false;this.openDelivery(this.contractId, isRab ? "rab" : "schedule");},
			complete: () => button.removeClass("loading disabled"),
		});
	}

	importRab(input) {
		const file = input.files?.[0];
		if (!file) return;
		const data = new FormData();
        $('#rabImportSettings [data-import-round]').each((_,el)=>data.append(`rounding[${$(el).data('import-round')}]`,el.value));
		data.append("contract_id", this.contractId);
		data.append("file", file);
		window.Ajax.request({
			url: "/kontrak/rab/import",
			method: "POST",
			data,
			processData: false,
			contentType: false,
			success: () => {this.rabDirty=false;this.closeContractSettings();this.openDelivery(this.contractId, "rab");},
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
		const params = new URLSearchParams(window.location.search);
		const report = params.get("report") || "dashboard";
		const supported = ["dashboard", "realisasi", "fisik_keuangan"];
		this.realizationReport = supported.includes(report) ? report : "dashboard";
		this.state.tbl = "__laporan";
		this.reportMonth = Math.min(12, Math.max(1, Number(params.get("month")) || new Date().getMonth() + 1));
		const query = this.realizationReport === "dashboard"
			? ""
			: `&report=${this.realizationReport}${this.realizationReport === "fisik_keuangan" ? `&month=${this.reportMonth}` : ""}`;
		const url = window.appUrl ? window.appUrl(`/kontrak?tbl=__laporan${query}`) : `/kontrak?tbl=__laporan${query}`;
		window.history.replaceState({ mode: "client" }, "", url);
		$("#crud-table-container").html('<div class="ui active centered inline loader"></div>');
		if (this.realizationReport === "dashboard") {
			window.Ajax.request({
				url: "/kontrak/summary",
				method: "GET",
				success: (r) => this.drawDashboard(r.data || {}),
				error: (r) => this.showRealizationReportError(r),
			});
			return;
		}
		window.Ajax.request({
			url: `/kontrak/financial-data?format=${encodeURIComponent(this.realizationReport)}${this.realizationReport === "fisik_keuangan" ? `&month=${this.reportMonth}` : ""}`,
			method: "GET",
			success: (r) => this.drawFinancialReport(this.realizationReport, r.data || {}),
			error: (r) => this.showRealizationReportError(r),
		});
	}

	showRealizationReportError(response) {
		const message = response?.message || "Laporan realisasi gagal dimuat.";
		$("#crud-table-container").html(`<div class="ui negative message">${this.esc(message)}</div>`);
	}

	drawFinancialReport(report, data) {
		const rows = data.rows || [];
		const isPhysical = report === "fisik_keuangan";
		const title = isPhysical ? "Laporan Fisik & Keuangan" : "Laporan Realisasi";
		const exportButtons = (format) => `<div class="ui buttons"><button type="button" class="ui green button" data-realization-export="${format}:xlsx"><i class="file excel icon"></i>Excel</button><button type="button" class="ui red button" data-realization-export="${format}:pdf"><i class="file pdf icon"></i>PDF</button></div>`;
		if (isPhysical) {
			const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
			const numberPercent = (value) => value === null || value === undefined ? "—" : `${Number(value).toFixed(2)}%`;
			const monthly = data.bulan_kumulatif || [];
			const chart = (titleText, targetKey, actualKey) => {
				const values = monthly.flatMap((row) => [row[targetKey], row[actualKey]]).filter((value) => value !== null && value !== undefined).map(Number);
				const max = Math.max(1, ...values);
				const bars = monthly.map((row) => {
					const target = row[targetKey];
					const actual = row[actualKey];
					const targetHeight = target === null || target === undefined ? 0 : Math.max(2, Number(target) / max * 130);
					const actualHeight = actual === null || actual === undefined ? 0 : Math.max(2, Number(actual) / max * 130);
					return `<div class="p4-trend-month"><div class="p4-trend-bars"><i class="p4-target" style="height:${targetHeight}px" title="Target ${numberPercent(target)}"></i><i class="p4-actual" style="height:${actualHeight}px" title="Realisasi ${numberPercent(actual)}"></i></div><small>${monthNames[Number(row.bulan) - 1].slice(0, 3)}</small></div>`;
				}).join("");
				return `<section class="p4-trend-card"><h3>${titleText}</h3><div class="p4-trend-chart">${bars}</div><div class="p4-trend-legend"><span><i class="p4-target"></i>Target</span><span><i class="p4-actual"></i>Realisasi</span></div></section>`;
			};
			const detailRows = rows.map((row, i) => `<tr><td>${i + 1}</td><td>${this.esc(row.kd_sub_keg)}</td><td>${this.esc(row.kd_akun)}</td><td>${this.esc(row.uraian || row.uraian_rekening || "-")}</td><td class="right aligned">${this.money(row.pagu)}</td><td class="right aligned">${this.money(row.realisasi)}</td><td class="right aligned">${numberPercent(row.persen)}</td><td class="right aligned">${numberPercent(row.fisik)}</td></tr>`).join("");
			const detailTotal = `<tr class="positive"><th colspan="4">TOTAL</th><th class="right aligned">${this.money(data.total_pagu)}</th><th class="right aligned">${this.money(data.total_realisasi_bulan)}</th><th class="right aligned">${numberPercent(data.total_pagu > 0 ? Number(data.total_realisasi_bulan) / Number(data.total_pagu) * 100 : 0)}</th><th>—</th></tr>`;
			const categoryRows = (data.kategori || []).map((category) => {
				const monthActual = rows.filter((row) => row.kategori === ({ "Belanja Pegawai": "pegawai", "Belanja Barang dan Jasa": "barang_jasa", "Belanja Modal": "modal" }[category.kategori] || "lainnya")).reduce((sum, row) => sum + Number(row.realisasi || 0), 0);
				return `<tr><td>${this.esc(category.kategori)}</td><td class="right aligned">${this.money(category.pagu)}</td><td class="right aligned">${this.money(monthActual)}</td><td class="right aligned">${this.money(category.realisasi_kumulatif)}</td><td class="right aligned">${numberPercent(category.persen)}</td></tr>`;
			}).join("");
			const monthlyRows = monthly.map((row) => `<tr><td>${monthNames[Number(row.bulan) - 1]}</td><td class="right aligned">${row.target_keuangan === null ? "Belum diisi" : this.money(row.target_keuangan)}</td><td class="right aligned">${row.realisasi_keuangan === null ? "—" : this.money(row.realisasi_keuangan)}</td><td class="right aligned">${numberPercent(row.target_fisik)}</td><td class="right aligned">${numberPercent(row.realisasi_fisik)}</td></tr>`).join("");
			const reportNotes = `${data.ada_rencana_keuangan ? "" : '<div class="ui warning message">Rencana anggaran bulanan belum tersedia; target keuangan tidak dibuat otomatis.</div>'}${data.ada_rencana_fisik ? "" : '<div class="ui warning message">Jadwal kontrak belum tersedia; target fisik ditampilkan kosong, bukan angka perkiraan.</div>'}`;
			$("#crud-table-container").html(
				`<div class="ui clearing segment"><h2>${title} ${this.esc(data.tahun || "")}</h2><p>Rincian realisasi bulan terpilih dan ringkasan kumulatif sampai bulan tersebut. Sumber data: dokumen anggaran disahkan, transaksi realisasi, dan jadwal kontrak.</p><div class="field"><label>Bulan laporan</label><select class="ui dropdown" data-report-month>${monthNames.map((name, i) => `<option value="${i + 1}" ${Number(data.bulan) === i + 1 ? "selected" : ""}>${name}</option>`).join("")}</select></div>${reportNotes}</div><section class="ui segment"><div class="ui clearing"><h3 class="ui left floated header">Rincian Anggaran dan Realisasi — ${this.esc(data.nama_bulan || "")}</h3><div class="ui right floated">${exportButtons("fisik_keuangan_detail")}</div></div><div class="table-wrapper"><table class="ui celled compact striped table"><thead><tr><th>No</th><th>Sub kegiatan</th><th>Kode rekening</th><th>Uraian</th><th class="right aligned">Jumlah anggaran</th><th class="right aligned">Realisasi bulan ini</th><th class="right aligned">Keuangan %</th><th class="right aligned">Fisik %</th></tr></thead><tbody>${detailRows || '<tr><td colspan="8" class="center aligned">Belum ada rincian anggaran/realisasi untuk bulan ini.</td></tr>'}${detailRows ? detailTotal : ""}</tbody></table></div></section><section class="ui segment"><div class="ui clearing"><h3 class="ui left floated header">Ringkasan Fisik & Keuangan s.d. ${this.esc(data.nama_bulan || "")}</h3><div class="ui right floated">${exportButtons("fisik_keuangan_ringkasan")}</div></div><div class="table-wrapper"><table class="ui celled compact striped table"><thead><tr><th>Jenis belanja</th><th class="right aligned">Pagu</th><th class="right aligned">Realisasi bulan ini</th><th class="right aligned">Realisasi kumulatif</th><th class="right aligned">Capaian</th></tr></thead><tbody>${categoryRows}<tr class="positive"><th>TOTAL</th><th class="right aligned">${this.money(data.total_pagu)}</th><th class="right aligned">${this.money(data.total_realisasi_bulan)}</th><th class="right aligned">${this.money(data.total_realisasi_kumulatif)}</th><th class="right aligned">${numberPercent(data.total_pagu > 0 ? Number(data.total_realisasi_kumulatif) / Number(data.total_pagu) * 100 : 0)}</th></tr></tbody></table></div><div class="p4-trend-grid">${chart("Progres Keuangan Kumulatif", "target_keuangan_persen", "realisasi_keuangan_persen")}${chart("Progres Fisik Kumulatif", "target_fisik", "realisasi_fisik")}</div><h4>Rekap kumulatif per bulan</h4><div class="table-wrapper"><table class="ui celled compact striped table"><thead><tr><th>Bulan</th><th class="right aligned">Target keuangan</th><th class="right aligned">Realisasi keuangan</th><th class="right aligned">Target fisik</th><th class="right aligned">Realisasi fisik</th></tr></thead><tbody>${monthlyRows}</tbody></table></div></section>`,
			);
			$(".ui.dropdown").dropdown();
			return;
		}
		const format = "realisasi";
		const exports = exportButtons(format);
		const body = rows.map((row, i) => `<tr><td>${i + 1}</td><td>${this.esc(row.tanggal || "-")}</td><td>${this.esc(row.nomor_kontrak || "-")}</td><td>${this.esc(row.penyedia || "-")}</td><td>${this.esc(row.uraian || "-")}</td><td>${this.esc(row.kd_akun || "-")}</td><td class="right aligned">${this.money(row.jumlah)}</td><td class="right aligned">${Number(row.progress_fisik || 0).toFixed(2)}%</td><td class="right aligned">${Number(row.progress_keuangan || 0).toFixed(2)}%</td></tr>`).join("");
		const headers = "<th>No</th><th>Tanggal</th><th>Nomor kontrak</th><th>Penyedia</th><th>Uraian realisasi</th><th>Kode rekening</th><th class=\"right aligned\">Nilai realisasi</th><th class=\"right aligned\">Fisik</th><th class=\"right aligned\">Keuangan</th>";
		const emptyColumns = 9;
		const summary = `<div class="p4-cards"><div><small>Jumlah transaksi realisasi</small><strong>${Number(data.jumlah_transaksi || 0)}</strong></div><div><small>Total nilai realisasi</small><strong>${this.money(data.total_realisasi)}</strong></div></div>`;
		$("#crud-table-container").html(
			`<div class="ui clearing segment"><div class="ui right floated">${exports}</div><h2>${title} ${this.esc(data.tahun || "")}</h2><p>Data bersumber dari transaksi pada menu Penginputan Realisasi.</p></div>${summary}<div class="table-wrapper"><table class="ui celled compact striped table"><thead><tr>${headers}</tr></thead><tbody>${body || `<tr><td colspan="${emptyColumns}" class="center aligned">Belum ada data realisasi untuk laporan ini.</td></tr>`}</tbody></table></div>`,
		);
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
			`<div class="p4-hero"><div><small>MONITORING PELAKSANAAN</small><h2>Dashboard Realisasi</h2><p>Ringkasan kontrak dan transaksi realisasi.</p></div><div class="ui buttons"><button class="ui green button" data-p4="report-excel"><i class="file excel icon"></i>Excel + Grafik</button><button class="ui red button" data-p4="report-pdf"><i class="file pdf icon"></i>PDF</button></div></div><div class="p4-cards"><div><small>Kontrak</small><strong>${t.jumlah_kontrak || 0}</strong></div><div><small>Total Anggaran</small><strong>${this.money(t.total_anggaran)}</strong></div><div><small>Nilai Kontrak</small><strong>${this.money(t.nilai_kontrak)}</strong></div><div><small>Realisasi</small><strong>${this.money(t.realisasi)}</strong></div></div><div class="p4-grid"><section><h3>Realisasi per Bulan</h3><div class="p4-chart">${bars}</div></section><section><h3>Status Kontrak</h3>${statuses || '<div class="ui message">Belum ada data.</div>'}</section></div>`,
		);
	}

	styles() {
		if (document.getElementById("phase4Style")) return;
		$("head").append(
			`<style id="phase4Style">.p4-hero{background:linear-gradient(135deg,#112c46,#2185d0);color:#fff;padding:24px;border-radius:14px;display:flex;justify-content:space-between}.p4-cards,.p4-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:14px}.p4-grid{grid-template-columns:2fr 1fr}.p4-cards>div,.p4-grid section{background:#fff;border:1px solid #e6edf5;border-radius:12px;padding:18px;box-shadow:0 8px 24px rgba(20,52,80,.07)}.p4-cards small,.p4-cards strong{display:block;margin-top:6px}.p4-chart{height:190px;display:flex;align-items:flex-end;gap:8px}.p4-bar-wrap{height:170px;flex:1;display:flex;flex-direction:column;justify-content:flex-end;text-align:center}.p4-bar{background:linear-gradient(#54c8ff,#2185d0);border-radius:5px 5px 0 0}.p4-status{padding:12px;border-bottom:1px solid #eee}.p4-status b{float:right}.contract-item-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.contract-scroll{max-height:52vh;overflow:auto;margin-top:12px}.contract-item-card,.contract-selected-card{display:flex;gap:12px;justify-content:space-between;border:1px solid #e3eaf2;border-radius:10px;padding:12px;margin-bottom:9px}.contract-item-card small,.contract-item-card p,.contract-selected-card p,.contract-selected-card small{display:block;margin:5px 0}.contract-selected-card>div{flex:1}.contract-total{position:sticky;bottom:0;background:#f4f0ff;border:1px solid #d7c9ff;border-radius:9px;padding:12px;display:flex;justify-content:space-between}.contract-section-tabs.ui.menu{gap:6px;border:0;box-shadow:none;background:transparent;margin:16px 0}.contract-section-tabs.ui.menu>.item{border-radius:7px!important;border:1px solid #dce5ee!important;min-height:48px;font-weight:600;justify-content:center}.contract-section-tabs.ui.menu>.item:before{display:none}.contract-section-tabs.ui.menu>.blue.item{color:#1678c2;background:#edf6ff}.contract-section-tabs.ui.menu>.teal.item{color:#00827c;background:#edfafa}.contract-section-tabs.ui.menu>.orange.item{color:#9d5600;background:#fff7eb}.contract-section-tabs.ui.menu>.violet.item{color:#6435c9;background:#f5f0ff}.contract-section-tabs.ui.menu>.active.item{box-shadow:inset 0 -3px 0 currentColor}.contract-rab-toolbar.ui.menu{margin:0 0 12px;min-height:0;box-shadow:none;border:0;flex-wrap:wrap}.contract-rab-toolbar.ui.menu .item{padding:0 6px 0 0}.contract-delivery-heading{display:flex;align-items:center;justify-content:space-between;gap:12px}.contract-delivery-page{padding:0 0 24px}.contract-delivery-toolbar{position:sticky;top:0;z-index:20;margin-bottom:14px!important}.contract-delivery-heading{margin:10px 0 14px}.contract-schedule-item{border:1px solid #dfe9ef;border-radius:10px;padding:12px;margin:12px 0;background:#fbfdff}.schedule-item-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.schedule-item-title strong,.schedule-item-title small{display:block;margin-top:4px}.delivery-table input{min-width:90px;width:100%;border:1px solid #d8e0e5;border-radius:4px;padding:7px}.ui.table.contract-rab-table{table-layout:fixed;width:100%;min-width:1000px}.ui.table.contract-rab-table th{overflow-wrap:normal;word-break:normal}.ui.table.contract-rab-table td{padding:7px;vertical-align:middle}.ui.form .contract-rab-table input,.ui.form .contract-rab-table textarea{min-width:0;width:100%;padding:7px 5px}.ui.form .contract-rab-table textarea{min-height:4.4em;resize:vertical;line-height:1.4}.contract-rab-table [data-rab=nomor],.contract-rab-table [data-rab=satuan],.contract-rab-table [data-rab=pajak]{text-align:center}.ui.table.contract-rab-table [data-rab=volume],.ui.table.contract-rab-table [data-rab=harga_satuan],.ui.table.contract-rab-table [data-rab-total],.ui.table.contract-rab-table [data-rab-weight]{text-align:right}.contract-rab-table [data-rab-total],.contract-rab-table [data-rab-weight]{white-space:nowrap;font-variant-numeric:tabular-nums}.contract-rab-table input[type=number]{appearance:textfield;-moz-appearance:textfield}.contract-rab-table input::-webkit-inner-spin-button,.contract-rab-table input::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}.s-chart{min-height:110px;display:flex;align-items:flex-end;gap:6px;overflow-x:auto;margin:12px 0}.s-point{min-width:34px;height:100px;display:flex;align-items:flex-end;gap:2px;position:relative;padding-bottom:18px}.s-point span,.s-point i{display:block;width:12px;max-height:80px;background:#2185d0}.s-point i{background:#21ba45}.s-point small{position:absolute;bottom:0;left:0}@media(max-width:800px){.p4-cards,.p4-grid,.contract-item-grid{grid-template-columns:1fr}.p4-hero,.schedule-item-title{display:block}.contract-delivery-toolbar{position:static}.contract-delivery-toolbar .right.floated{float:none!important;margin-top:8px}.delivery-table{min-width:850px}.table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch}}</style>`,
		);
		$("head").append(`<style id="physicalFinancialReportStyle">
#realizationSidebarForm label{line-height:1.4}#realizationSidebarForm .ui.message{font-size:.92em;line-height:1.5}
#realizationItemsButton{margin-bottom:14px}#realizationValidation[hidden]{display:none!important}
#realizationItemsModal td.error{background:#fff6f6!important}#realizationItemsModal td.error input{border:1px solid #e0b4b4;background:#fff6f6;color:#9f3a38}
.p4-trend-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:16px 0}.p4-trend-card{border:1px solid #e6edf5;border-radius:10px;padding:14px;background:#fff}.p4-trend-chart{height:160px;display:flex;align-items:flex-end;gap:5px;border-bottom:1px solid #d9e2ec;padding:0 4px}.p4-trend-month{flex:1;min-width:22px;text-align:center}.p4-trend-bars{height:138px;display:flex;justify-content:center;align-items:flex-end;gap:2px}.p4-trend-bars i{display:block;width:8px;min-height:0;border-radius:3px 3px 0 0}.p4-trend-bars .p4-target,.p4-trend-legend .p4-target{background:#2185d0}.p4-trend-bars .p4-actual,.p4-trend-legend .p4-actual{background:#f2711c}.p4-trend-month small{font-size:11px}.p4-trend-legend{display:flex;gap:16px;margin-top:8px}.p4-trend-legend span{display:flex;align-items:center;gap:5px}.p4-trend-legend i{width:10px;height:10px;display:inline-block}@media(max-width:800px){.p4-trend-grid{grid-template-columns:1fr}}</style>`);
	}
}
