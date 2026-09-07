class ReferensiModule extends BaseCrudModule {
	constructor() {
		super({
			moduleName: "referensi",
			menuItems: [], // tidak dipakai
			useMenu: false, // 🔥 wajib false
		});
	}
	init(){
		super.init();
		this.bindSakipExports();
		$(document).off("change.rpjmdDefault",'[name="berlaku_mulai"]').on("change.rpjmdDefault",'[name="berlaku_mulai"]',function(){
			const end=$(this).closest("form").find('[name="berlaku_sampai"]');
			if(!this.value||end.val())return;
			const date=new Date(this.value);if(Number.isNaN(date.getTime()))return;
			date.setFullYear(date.getFullYear()+5);date.setDate(date.getDate()-1);
			end.val(date.toISOString().slice(0,10)).trigger("change");
		});
	}
	bindSakipExports(){
		$(document).off("click.sakipExport","#crud-table-container [data-sakip-export]").on("click.sakipExport","#crud-table-container [data-sakip-export]",e=>{e.preventDefault();const kind=$(e.currentTarget).data("sakip-export");const tbl=this.state.tbl;const path=kind==="tree"?"/sakip/pohon_kinerja_pdf":`/sakip/export_pdf?tbl=${encodeURIComponent(tbl)}`;window.location.href=window.appUrl?window.appUrl(path):path;});
	}
	buildActionButtons(tbl){const base=super.buildActionButtons(tbl);const reportTypes=["iku_opd","perjanjian_kinerja_detail","pengukuran_kinerja","evaluasi_renja","evaluasi_renstra"];if(tbl==="pohon_kinerja")return `${base}<div class="ui right floated basic icon buttons" style="margin-top:10px;margin-right:8px"><button class="ui button" data-sakip-export="tree" title="PDF pohon kinerja bertingkat"><i class="sitemap icon"></i><i class="file pdf outline icon"></i></button></div>`;if(reportTypes.includes(tbl))return `${base}<div class="ui right floated basic icon buttons" style="margin-top:10px;margin-right:8px"><button class="ui button" data-sakip-export="report" title="PDF laporan dengan uraian lengkap"><i class="file pdf outline icon"></i></button></div>`;return base;}
	renderLayout(){super.renderLayout();this.decorateDashboard('Referensi Daerah','Pusat data kode, organisasi, wilayah, rekening, dan klasifikasi yang dipakai lintas modul.','database','Data Terintegrasi');}
	decorateDashboard(title,subtitle,icon,label){const box=$(this.container).find('.ui.container');box.find('h2').remove();box.prepend(`<section class="module-insight-hero"><div><small>${label}</small><h2><i class="${icon} icon"></i>${title}</h2><p>${subtitle}</p></div><div class="ui three tiny statistics"><div class="statistic"><div class="value">1</div><div class="label">Sumber data</div></div><div class="statistic"><div class="value"><i class="search icon"></i></div><div class="label">Pencarian</div></div><div class="statistic"><div class="value"><i class="shield icon"></i></div><div class="label">Scope OPD</div></div></div></section><div class="ui info message"><i class="info circle icon"></i>Gunakan pencarian global dan pilihan jumlah baris di taskbar. Perubahan kode referensi dapat berdampak pada dokumen turunan.</div>`);this.injectDashboardCss();}
	injectDashboardCss(){if($('#moduleInsightCss').length)return;$('head').append('<style id="moduleInsightCss">.module-insight-hero{display:flex;justify-content:space-between;align-items:center;padding:22px;margin-bottom:14px;border-radius:16px;color:#fff;background:linear-gradient(135deg,#17324d,#2185d0);box-shadow:0 12px 30px rgba(23,50,77,.16)}.module-insight-hero h2{margin:4px 0}.module-insight-hero .statistics .value,.module-insight-hero .statistics .label{color:#fff!important}@media(max-width:760px){.module-insight-hero{display:block}.module-insight-hero .statistics{margin-top:15px!important}}</style>');}
}
