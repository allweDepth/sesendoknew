class KepegawaianModule extends BaseCrudModule {
    constructor() {
        super({
            moduleName: "kepegawaian",
            menuItems: [],
            useMenu: false
        });
    }
    loadTable(tbl, req=null) {
        this.employeeOptionsRequest?.abort();
        super.loadTable(tbl, req);
        if (!["riwayat_jabatan", "riwayat_pangkat"].includes(tbl)) return;
        const manager=this.tableManager;
        const escape=v=>$("<span>").text(String(v??"")).html();
        const box=$(`<div class="ui segment"><div class="ui form"><div class="field"><label>Pilih Nama ASN</label><select class="ui fluid search selection dropdown" id="historyEmployeeFilter"><option value="">Semua ASN dalam OPD</option></select></div></div><div class="ui small info message">Seluruh tahun riwayat ditampilkan. Riwayat terbaru yang sudah berlaku otomatis menjadi jabatan atau golongan/ruang pada Data ASN. TMT mendatang ditandai Terjadwal.</div></div>`);
        box.css("clear", "both");
        $("#crud-table-container").prepend(box);
        const select=box.find("select");
        select.dropdown({fullTextSearch:true,clearable:true,onChange:value=>{
            manager.personnelEmployeeId=value?Number(value):null;
            manager.currentPage=1;manager.fetchData();
            const field=window.UIConfig[tbl].form.elements.find(e=>e.prop?.name==="pegawai_id");
            if(value)field.prop.default=String(value);else delete field.prop.default;
        }});
        this.employeeOptionsRequest=this.ajax.request({url:"/kepegawaian/asn-options",method:"GET",silent:true,success:res=>{
            if(!res.success||!box[0].isConnected)return;
            (res.data||[]).forEach(e=>select.append(`<option value="${Number(e.id)}">${escape([e.gelar_depan,e.nama].filter(Boolean).join(" ")+(e.gelar?", "+e.gelar:"")+" — "+(e.nip||""))}</option>`));
            select.dropdown("refresh");
        }});
    }
    renderLayout(){super.renderLayout();const box=$(this.container).find('.ui.container');box.find('h2').remove();box.prepend(`<section class="module-insight-hero"><div><small>SDM & ADMINISTRASI</small><h2><i class="users icon"></i>Kepegawaian</h2><p>Ringkasan pegawai, absensi, jabatan, dan kelengkapan dokumen dalam satu ruang kerja.</p></div><div class="ui three tiny statistics"><div class="statistic"><div class="value"><i class="id badge icon"></i></div><div class="label">Pegawai</div></div><div class="statistic"><div class="value"><i class="calendar check icon"></i></div><div class="label">Absensi</div></div><div class="statistic"><div class="value"><i class="folder open icon"></i></div><div class="label">Dokumen</div></div></div></section><div class="ui info message"><i class="info circle icon"></i>Periksa identitas, status jabatan, dan dokumen pendukung sebelum membuat laporan.</div>`);if(!$('#moduleInsightCss').length)$('head').append('<style id="moduleInsightCss">.module-insight-hero{display:flex;justify-content:space-between;align-items:center;padding:22px;margin-bottom:14px;border-radius:16px;color:#fff;background:linear-gradient(135deg,#17324d,#2185d0);box-shadow:0 12px 30px rgba(23,50,77,.16)}.module-insight-hero h2{margin:4px 0}.module-insight-hero .statistics .value,.module-insight-hero .statistics .label{color:#fff!important}@media(max-width:760px){.module-insight-hero{display:block}.module-insight-hero .statistics{margin-top:15px!important}}</style>');}
}
