class RenstraModule extends BaseCrudModule {

    constructor() {
        super({
            moduleName: "renstra",
            menuItems: [
                { label: "Visi", tbl: "renstra_neo" },
                { label: "Misi", tbl: "misi_renstra_neo" },
                { label: "Tujuan", tbl: "tujuan_renstra_neo" },
                { label: "Sasaran", tbl: "sasaran_renstra_neo" },
                { label: "Indikator Sasaran", tbl: "indikator_sasaran_renstra_neo" },
                { label: "Program", tbl: "program_renstra_neo" },
                { label: "Indikator Program", tbl: "indikator_program_renstra_neo" },
                { label: "Kegiatan", tbl: "kegiatan_renstra_neo" },
                { label: "Sub Kegiatan", tbl: "sub_kegiatan_renstra_neo" }
            ],
            useMenu: true   // 🔥 WAJIB
        });
    }

    renderLayout() {
        super.renderLayout();
        $('#crudMenu').after('<div class="ui small buttons renstra-export"><button class="ui green button" data-renstra-export="excel"><i class="file excel icon"></i>Excel Renstra</button><button class="ui red button" data-renstra-export="pdf"><i class="file pdf icon"></i>PDF Renstra</button></div><div class="ui hidden divider"></div>');
        $(document).off('click.renstraExport','[data-renstra-export]').on('click.renstraExport','[data-renstra-export]',e=>{const type=$(e.currentTarget).data('renstra-export'),path=`/renstra/export_${type}`;window.location.href=window.appUrl?window.appUrl(path):path;});
    }

}
