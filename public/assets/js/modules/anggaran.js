class AnggaranModule {
	constructor(container) {
		this.container = container;
		this.table = container.dataset.table;

		this.init();
	}

	init() {
		this.loadSub();
	}

	async loadSub() {
		let res = await $.post("/anggaran/sub_kegiatan", {
			table: this.table,
			tahun: 2026,
			opd: "1.03",
		});

		let tbody = $("#tableSubKegiatan tbody");

		tbody.empty();

		res.data.forEach((r) => {
			tbody.append(`
<tr data-sub="${r.kd_sub_keg}">
<td>
<button class="ui mini button btnSub">Detail</button>
</td>
<td>${r.kd_sub_keg}</td>
<td>${r.total}</td>
</tr>
`);
		});
	}
}
