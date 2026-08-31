class AnggaranDocumentModule extends BaseCrudModule {
	constructor() {
		const pathMap = {
			"/rkpd":"rkpd", "/renja":"renja", "/rka":"rka", "/dpa":"dpa",
			"/rkpd_perubahan":"rkpd_p", "/renja_perubahan":"renja_p",
			"/rka_perubahan":"rka_p", "/dppa":"dppa"
		};
		const matchedPath = Object.keys(pathMap).find((path) => window.location.pathname.endsWith(path));
		const table = $("#anggaranDocument").data("table") || pathMap[matchedPath] || "rkpd";
		super({ moduleName: "anggaran", menuItems: [{ label: table, tbl: table }], useMenu: false });
		this.table = table;
		this.next = { rkpd:"renja", renja:"rka", rka:"dpa", dpa:"dppa" };
		this.changes = { rkpd:"rkpd_p", renja:"renja_p", rka:"rka_p" };
	}

	init() { super.init(); this.bindPhase3Actions(); }

	buildActionButtons(tbl) {
		const create = tbl === "rkpd" ? super.buildActionButtons(tbl) : `<div class="ui right floated basic icon buttons" style="margin-top:10px"><button class="ui button" data-action="export" data-tbl="${tbl}" title="Download Excel"><i class="alternate download icon"></i></button></div>`;
		const next = this.next[tbl]; const change = this.changes[tbl];
		return `${create}<div class="ui right floated basic icon buttons" style="margin-top:10px;margin-right:8px">
			${next ? `<button class="ui button" data-phase3-action="advance" data-from="${tbl}" data-to="${next}" title="Proses ke ${next.toUpperCase()}"><i class="arrow right icon"></i></button>` : ""}
			${change ? `<button class="ui button" data-phase3-action="advance" data-from="${tbl}" data-to="${change}" title="Buat dokumen perubahan"><i class="sync alternate icon"></i></button>` : ""}
			<button class="ui button" data-phase3-action="pdf" data-tbl="${tbl}" title="Download PDF"><i class="file pdf outline icon"></i></button>
		</div>`;
	}

	bindPhase3Actions() {
		$(document).off("click.phase3", "[data-phase3-action]").on("click.phase3", "[data-phase3-action]", (event) => {
			const button = $(event.currentTarget); const action = button.data("phase3-action");
			if (action === "pdf") { const url=`/anggaran/export_pdf?tbl=${encodeURIComponent(button.data("tbl"))}`; window.location.href=window.appUrl?window.appUrl(url):url; return; }
			window.Ajax.request({ url:"/anggaran/advance", method:"POST", data:{ from:button.data("from"), to:button.data("to"), tahun:window.app?.user?.tahun || new Date().getFullYear() }, success:(response)=>{ if(response?.success) Toast.success(response.message); } });
		});
	}
}
