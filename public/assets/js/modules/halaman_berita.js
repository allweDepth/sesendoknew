class HalamanBeritaModule extends BaseCrudModule {
	constructor(){super({moduleName:"halaman berita",menuItems:[],useMenu:false});this.editor=null;this.saving=false;}
	init(){super.init();this.loadTable("halaman_berita");this.bindEditor();}
	buildActionButtons(tbl){return `<div class="ui right floated basic buttons" style="margin-top:10px"><button class="ui primary button" data-ui="open-form" data-action="add" data-tbl="${tbl}"><i class="plus icon"></i>Berita Baru</button><button class="ui icon button" data-action="export" data-tbl="${tbl}" title="Export"><i class="alternate download icon"></i></button></div>`;}
	bindEditor(){
		$(document).off("click.newsWorkspace").on("click.newsWorkspace",'[data-ui="open-form"][data-tbl="halaman_berita"]',e=>{
			e.preventDefault();e.stopImmediatePropagation();
			const button=$(e.currentTarget),action=button.data("action"),id=button.data("id")||button.closest("tr").data("id")||null;
			this.openEditor(action,id);
		});
	}
	openEditor(action,id){
		const show=data=>{
			const sidebar=$(".sidebarkanan");
			$("#halamanBeritaEditor").remove();
			$("#crud-table-container").hide().after('<div id="halamanBeritaEditor"></div>');
			sidebar.addClass("news-editor-sidebar").find(".flyout-footer").hide();
			sidebar.find("#content_flyout").text("Format Berita");
			sidebar.find("#icon_flyout").attr("class","paint brush icon");
			this.editor=new RichDocumentEditor({container:"#halamanBeritaEditor",inspectorContainer:".sidebarkanan #form_flyout",onBack:()=>this.closeEditor(),onSave:(html,meta)=>this.saveEditor(action,id,html,meta)}).mount(data.konten||"");
			this.editor.setMeta(data);
			sidebar.find(".btnFlyoutClose").off("click.newsEditor").on("click.newsEditor",e=>{e.preventDefault();e.stopPropagation();this.closeEditor();});
			sidebar.sidebar({context:"#mainContext",dimPage:false,closable:false,transition:"push",exclusive:false,onHidden:()=>this.restoreSidebar()}).sidebar("show");
		};
		if(action==="edit"&&id)this.ajax.request({data:{action:"edit",tbl:"halaman_berita",id_row:id},success:res=>res?.data?show(res.data):Toast.show({success:false,message:"Data berita tidak ditemukan"})});
		else show({jenis_halaman:"berita",aktif:1});
	}
	saveEditor(action,id,html,meta){
		if(this.saving)return;
		if(!String(meta.judul||"").trim())return Toast.show({success:false,message:"Judul berita wajib diisi"});
		const slug=String(meta.slug||meta.judul).toLowerCase().trim().replace(/[^a-z0-9]+/g,"-").replace(/^-|-$/g,"");
		this.saving=true;
		const saveButton=$("#halamanBeritaEditor [data-rde='save']").addClass("loading disabled");
		this.ajax.request({data:{action:action==="edit"?"edit":"add",...(action==="edit"?{mode:"update"}:{}),tbl:"halaman_berita",...(id?{id_row:id}:{}),...meta,slug,konten:html},success:res=>{if(res?.success)this.closeEditor(true);},error:()=>{this.saving=false;saveButton.removeClass("loading disabled");},complete:()=>{if(this.editor){this.saving=false;saveButton.removeClass("loading disabled");}}});
	}
	restoreSidebar(){const sidebar=$(".sidebarkanan");sidebar.removeClass("news-editor-sidebar");sidebar.find(".btnFlyoutClose").off("click.newsEditor");sidebar.find("#form_flyout").empty();sidebar.find(".flyout-footer").show();sidebar.find("#content_flyout").text("Lengkapi Data");sidebar.find("#icon_flyout").attr("class","folder icon");}
	closeEditor(reload=false){if(this.editor?.scope)this.editor.scope.off(".rde");const sidebar=$(".sidebarkanan");const wasVisible=sidebar.hasClass("visible");sidebar.sidebar("hide");if(!wasVisible)this.restoreSidebar();$("#halamanBeritaEditor").remove();$("#crud-table-container").show();this.editor=null;this.saving=false;if(reload)this.loadTable("halaman_berita");}
	destroy(){$(document).off(".newsWorkspace");this.closeEditor();}
}
