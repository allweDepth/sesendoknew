class LaporanKeuanganModule {
	constructor() {
		this.root = document.getElementById("financialStatementsRoot");
		this.report = new URLSearchParams(window.location.search).get("laporan") || "lra";
		this.balanceRows = [];
		this.noteRows = [];
		this.sections = {};
		const role = window.app?.user?.type_user || "viewer";
		this.canReadAccounts = ["super_admin", "admin_wilayah", "tapd", "admin_opd", "kepala_opd", "pa_kpa", "bendahara"].includes(role);
		this.canWrite = ["super_admin", "admin_wilayah", "admin_opd", "kepala_opd", "pa_kpa", "bendahara"].includes(role);
		if (!this.canReadAccounts && ["neraca", "calk"].includes(this.report)) this.report = "lra";
	}

	init() {
		if (!this.root) return;
		this.styles();
		this.renderShell();
		this.bindEvents();
		this.load();
	}

	styles() {
		if (document.getElementById("financialStatementsStyle")) return;
		const style = document.createElement("style");
		style.id = "financialStatementsStyle";
		style.textContent = `.financial-report{max-width:1500px;margin:0 auto;padding-bottom:30px}.financial-report-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:24px;border-radius:14px;color:#fff;background:linear-gradient(135deg,#102b43,#2185d0)}.financial-report-hero h1{margin:5px 0}.financial-report-hero p{margin:0;max-width:760px}.financial-report-hero .eyebrow{font-size:.75rem;letter-spacing:.13em;opacity:.8}.financial-report-tabs{margin-top:16px!important}.financial-report-tabs .item{cursor:pointer!important}.financial-report-toolbar,.financial-input-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:15px 0}.financial-report-toolbar h2,.financial-report-toolbar p{margin:0 0 5px}.financial-report-toolbar p,.financial-draft-note{color:#667788}.financial-input-toolbar{justify-content:flex-end}.financial-table-wrap{overflow:auto}.financial-table-wrap input{min-width:110px;width:100%;padding:7px;border:1px solid #d5dee7;border-radius:5px}.financial-table-wrap [data-balance-code]{min-width:130px}.financial-table-wrap [data-balance-name]{min-width:210px}.financial-table-wrap [data-balance-note]{min-width:180px}.financial-table-wrap td{vertical-align:middle!important}.financial-table-wrap tfoot th{background:#f4f8fc!important}.financial-draft-note{font-size:.9rem;margin-top:12px}.calk-entry{position:relative;padding:16px!important}.calk-entry .field{margin-bottom:12px}.calk-entry-toolbar{display:flex;justify-content:flex-end}.calk-entry textarea{width:100%;resize:vertical;border:1px solid #d5dee7;border-radius:5px;padding:9px}@media(max-width:760px){.financial-report-hero{display:block}.financial-report-hero>a{margin-top:14px}.financial-report-toolbar{align-items:flex-start}.financial-report-toolbar>.ui.buttons{display:flex;flex-wrap:wrap}}`;
		document.head.appendChild(style);
	}

	url(path) {
		return window.appUrl ? window.appUrl(path) : path;
	}

	escape(value) {
		return String(value ?? "").replace(/[&<>"']/g, (char) => ({
			"&": "&amp;",
			"<": "&lt;",
			">": "&gt;",
			'"': "&quot;",
			"'": "&#39;",
		})[char]);
	}

	money(value) {
		return new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 }).format(Number(value || 0));
	}

	async request(path, options = {}) {
		const method = String(options.method || "GET").toUpperCase();
		const headers = new Headers(options.headers || {});
		if (!["GET", "HEAD", "OPTIONS"].includes(method)) headers.set("X-CSRF-Token", window.CSRF_TOKEN || "");
		const response = await fetch(this.url(path), { credentials: "same-origin", ...options, headers });
		const result = await response.json();
		if (!response.ok || result?.success === false) throw new Error(result?.message || "Permintaan gagal diproses.");
		return result?.data ?? result;
	}

	renderShell() {
		const tabs = [
			["lra", "LRA", "chart bar"],
			...(this.canReadAccounts ? [["neraca", "Neraca", "balance scale"], ["calk", "CaLK", "book"]] : []),
		];
		this.root.innerHTML = `<section class="financial-report">
			<header class="financial-report-hero"><div><span class="eyebrow">REALISASI & AKUNTANSI OPD</span><h1>Laporan Keuangan</h1><p>Tahun kerja ${this.escape(window.app?.user?.tahun || new Date().getFullYear())}. LRA memakai snapshot anggaran disahkan dan belanja kontrak terverifikasi. Neraca/CaLK tetap kertas kerja sampai saldo buku besar/jurnal dan pengungkapan tersedia.</p></div><a class="ui inverted basic button" href="${this.url("/kontrak?tbl=realisasi")}" data-spa="client"><i class="arrow left icon"></i>Ke Penginputan Realisasi</a></header>
			<nav class="ui pointing secondary menu financial-report-tabs" aria-label="Jenis laporan">${tabs.map(([key, title, icon]) => `<button type="button" class="${key === this.report ? "active" : ""} item" data-report="${key}"><i class="${icon} icon"></i>${title}</button>`).join("")}</nav>
			<div id="financialReportContent"><div class="ui active centered inline loader"></div></div>
		</section>`;
	}

	bindEvents() {
		this.root.addEventListener("click", async (event) => {
			const target = event.target.closest("[data-report],[data-action]");
			if (!target) return;
			if (target.dataset.report) {
				this.report = target.dataset.report;
				window.history.replaceState(null, "", this.url(`/laporan-keuangan?laporan=${this.report}`));
				this.root.querySelectorAll("[data-report]").forEach((tab) => tab.classList.toggle("active", tab === target));
				await this.load();
				return;
			}
			try {
				if (target.dataset.action === "add-balance") this.addBalanceRow();
				if (target.dataset.action === "save-balances") await this.saveBalances(target);
				if (target.dataset.action === "remove-balance") target.closest("tr")?.remove();
				if (target.dataset.action === "add-note") this.addNoteRow();
				if (target.dataset.action === "remove-note") target.closest("[data-note-row]")?.remove();
				if (target.dataset.action === "save-notes") await this.saveNotes(target);
				if (target.dataset.action === "verify-transaction") await this.verifyTransaction(target);
			} catch (error) {
				window.Toast?.error?.(error.message);
			}
		});
		this.root.addEventListener("change", async (event) => {
			if (event.target.matches("#balanceImportFile")) await this.importBalances(event.target);
		});
		this.root.addEventListener("input", (event) => {
			if (event.target.matches("[data-balance-code]")) this.updateAccountGroup(event.target);
		});
	}

	async load() {
		const content = this.root.querySelector("#financialReportContent");
		content.innerHTML = '<div class="ui active centered inline loader"></div>';
		try {
			const data = await this.request(`/laporan-keuangan/data?type=${encodeURIComponent(this.report)}`);
			if (this.report === "lra") this.renderLra(data);
			else if (this.report === "neraca") this.renderBalanceSheet(data);
			else this.renderNotes(data);
		} catch (error) {
			content.innerHTML = `<div class="ui negative message">${this.escape(error.message)}</div>`;
		}
	}

	renderLra(data) {
		const rows = data.rows || [];
		const transactions = data.transactions || [];
		const content = this.root.querySelector("#financialReportContent");
		const statusClass = data.status === "KERTAS_KERJA" ? "positive" : "warning";
		const missing = (data.missing_sources || []).map((item) => `<li>${this.escape(item)}</li>`).join("");
		const pendingRows = transactions.map((row) => {
			const docs = String(row.file_ids || "").split(",").filter(Boolean).map((id) => `<a href="${this.url(`/kontrak/realization/file/download?id=${encodeURIComponent(id)}`)}" target="_blank" rel="noopener">Bukti #${this.escape(id)}</a>`).join("<br>") || "Belum ada bukti";
			const known = Boolean(row.jenis_transaksi);
			const status = row.status_verifikasi || "BELUM_DIKLASIFIKASI";
			const verify = data.can_verify_transactions && known && status === "MENUNGGU_VERIFIKASI" && Number(row.jumlah_dokumen) > 0
				? `<button type="button" class="ui mini teal button" data-action="verify-transaction" data-transaction-uuid="${this.escape(row.transaksi_uuid)}"><i class="check icon"></i>Verifikasi</button>`
				: "";
			return `<tr><td>${this.escape(row.tanggal || "-")}</td><td>${this.escape(row.nomor_kontrak || "-")}<br><small>${this.escape(row.uraian || row.kd_sub_keg || "")}</small></td><td>${this.escape(row.jenis_transaksi || "Belum diklasifikasikan")}</td><td>${this.escape(row.nomor_bukti || "-")}<br>${this.escape(row.nomor_sp2d || row.nomor_spj || "")}${row.nomor_sp2d_uang_muka ? `<br><small>SP2D uang muka: ${this.escape(row.nomor_sp2d_uang_muka)}</small>` : ""}</td><td class="right aligned">${this.money(row.jumlah)}</td><td class="right aligned">${this.money(row.nilai_lra)}</td><td>${docs}</td><td>${this.escape(status)}${verify ? `<br>${verify}` : ""}</td></tr>`;
		}).join("");
		content.innerHTML = `<div class="ui ${statusClass} message"><b>Status kertas kerja LRA: ${this.escape(data.status || "BELUM_LENGKAP")}</b>
			<ul>${missing || "<li>Sumber data untuk kertas kerja tersedia.</li>"}</ul>
			<p>Lingkup masih transaksi belanja kontrak yang dicatat di seSendok; belum mencakup pendapatan, transfer, belanja non-kontrak, koreksi, atau konsolidasi LKPD.</p></div>
			<div class="financial-report-toolbar"><div><h2>Laporan Realisasi Anggaran ${this.escape(data.tahun)}</h2><p>Sumber: ${this.escape(data.source || "Realisasi transaksi kontrak")}.</p></div><div class="ui buttons"><a class="ui green button" href="${this.url("/laporan-keuangan/export?report=lra&format=xlsx")}"><i class="file excel icon"></i>Excel</a><a class="ui red button" href="${this.url("/laporan-keuangan/export?report=lra&format=pdf")}"><i class="file pdf icon"></i>PDF</a></div></div>
			<div class="financial-table-wrap"><table class="ui celled compact table"><thead><tr><th>No</th><th>Sub Kegiatan</th><th>Kode Rekening</th><th class="right aligned">Pagu disahkan</th><th class="right aligned">Belanja terverifikasi</th><th class="right aligned">Sisa</th><th class="right aligned">Realisasi %</th></tr></thead><tbody>${rows.map((row, i) => `<tr><td>${i + 1}</td><td>${this.escape(row.kd_sub_keg)}</td><td>${this.escape(row.kd_akun)}</td><td class="right aligned">${this.money(row.pagu)}</td><td class="right aligned">${this.money(row.realisasi)}</td><td class="right aligned">${this.money(row.sisa)}</td><td class="right aligned">${Number(row.persen || 0).toFixed(2)}%</td></tr>`).join("") || '<tr><td colspan="7" class="center aligned">Belum ada transaksi belanja yang terverifikasi untuk tahun dan OPD ini.</td></tr>'}</tbody><tfoot><tr><th colspan="3">Total kertas kerja</th><th class="right aligned">${this.money(data.summary?.pagu)}</th><th class="right aligned">${this.money(data.summary?.realisasi)}</th><th class="right aligned">${this.money(data.summary?.sisa)}</th><th></th></tr></tfoot></table></div>
			<section><div class="financial-report-toolbar"><div><h3>Penelusuran transaksi dan verifikasi</h3><p>Riwayat transaksi ditampilkan bersama dokumen sumber. Nilai transaksi kontrak dipisahkan dari nilai LRA per rekening. Progres dan GU tidak menjadi belanja LRA langsung.</p></div></div><div class="financial-table-wrap"><table class="ui celled compact table"><thead><tr><th>Tanggal</th><th>Kontrak / uraian</th><th>Jenis</th><th>Nomor bukti</th><th class="right aligned">Nilai kontrak/progres</th><th class="right aligned">Nilai LRA</th><th>Dokumen</th><th>Status / aksi</th></tr></thead><tbody>${pendingRows || '<tr><td colspan="8" class="center aligned">Belum ada transaksi pada tahun dan OPD ini.</td></tr>'}</tbody></table></div></section>`;
	}

	async verifyTransaction(button) {
		const uuid = button.dataset.transactionUuid;
		if (!uuid) throw new Error("ID transaksi tidak tersedia.");
		button.classList.add("loading", "disabled");
		try {
			await this.request("/laporan-keuangan/transaksi/verifikasi", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ transaksi_uuid: uuid }),
			});
			window.Toast?.success?.("Transaksi berhasil diverifikasi.");
			await this.load();
		} finally {
			button.classList.remove("loading", "disabled");
		}
	}

	renderBalanceSheet(data) {
		this.balanceRows = data.rows || [];
		const summary = data.summary || {};
		const totals = summary.totals || { saldo_awal: {}, saldo_akhir: {} };
		const status = this.balanceRows.length ? (summary.seimbang ? "Seimbang pada saldo awal dan akhir." : "Belum seimbang. Periksa kelengkapan saldo dan rekening.") : "Saldo belum diisi.";
		const rows = this.balanceRows.length ? this.balanceRows : [{}];
		const content = this.root.querySelector("#financialReportContent");
		content.innerHTML = `<div class="ui warning message"><b>Neraca belum dihitung otomatis dari jurnal/buku besar.</b> Aplikasi belum mempunyai sumber saldo awal dan mutasi jurnal terverifikasi untuk membentuk Neraca otomatis. Saldo pada halaman ini harus dimasukkan atau diimpor dari buku besar/daftar aset resmi; jangan menurunkan saldo dari progres atau nilai kontrak.</div>
			<div class="ui ${summary.seimbang ? "positive" : "warning"} message"><b>Status Neraca:</b> ${this.escape(status)}${this.balanceRows.length ? `<br>Selisih awal: Rp ${this.money(summary.selisih_awal)} · Selisih akhir: Rp ${this.money(summary.selisih_akhir)}` : ""}<br>Kelompok akun mengikuti struktur SAP: 1 Aset, 2 Kewajiban, 3 Ekuitas. Gunakan rekening rinci, bukan akun induk sekaligus anaknya.</div>
			<div class="financial-report-toolbar"><div><h2>Neraca ${this.escape(data.tahun)}</h2><p>Masukkan saldo awal dan akhir yang bersumber dari buku besar/daftar aset dan dokumen akuntansi OPD. Saldo tidak dibuat dari nilai kontrak.</p></div><div class="ui buttons"><a class="ui basic button" href="${this.url("/laporan-keuangan/saldo/template")}"><i class="download icon"></i>Template Excel</a><a class="ui green button" href="${this.url("/laporan-keuangan/export?report=neraca&format=xlsx")}"><i class="file excel icon"></i>Excel</a><a class="ui red button" href="${this.url("/laporan-keuangan/export?report=neraca&format=pdf")}"><i class="file pdf icon"></i>PDF</a></div></div>
			${this.canWrite ? `<div class="financial-input-toolbar"><label class="ui teal button" for="balanceImportFile"><i class="upload icon"></i>Impor Excel</label><input id="balanceImportFile" type="file" accept=".xlsx,.xls" hidden><button type="button" class="ui basic button" data-action="add-balance"><i class="plus icon"></i>Tambah Rekening</button><button type="button" class="ui primary button" data-action="save-balances"><i class="save icon"></i>Simpan Neraca</button></div>` : ""}
			<div class="financial-table-wrap"><table class="ui celled compact table"><thead><tr><th>Kode rekening</th><th>Uraian rekening</th><th>Kelompok</th><th>Saldo awal</th><th>Saldo akhir</th><th>Catatan sumber</th>${this.canWrite ? "<th></th>" : ""}</tr></thead><tbody id="balanceRows">${rows.map((row) => this.balanceRow(row)).join("")}</tbody><tfoot><tr><th colspan="3">Total aset</th><th class="right aligned">${this.money(totals.saldo_awal?.ASET)}</th><th class="right aligned">${this.money(totals.saldo_akhir?.ASET)}</th><th colspan="${this.canWrite ? 2 : 1}"></th></tr><tr><th colspan="3">Total kewajiban + ekuitas</th><th class="right aligned">${this.money(Number(totals.saldo_awal?.KEWAJIBAN || 0) + Number(totals.saldo_awal?.EKUITAS || 0))}</th><th class="right aligned">${this.money(Number(totals.saldo_akhir?.KEWAJIBAN || 0) + Number(totals.saldo_akhir?.EKUITAS || 0))}</th><th colspan="${this.canWrite ? 2 : 1}"></th></tr></tfoot></table></div>
			<p class="financial-draft-note">Keluaran Neraca ditandai sebagai kertas kerja. Seimbangkan saldo, cocokkan dengan daftar aset/buku besar, dan lakukan verifikasi sebelum dipakai sebagai laporan resmi.</p>`;
	}

	balanceRow(row = {}) {
		const readonly = this.canWrite ? "" : "readonly";
		const remove = this.canWrite ? '<td><button type="button" class="ui mini basic red icon button" data-action="remove-balance" aria-label="Hapus baris"><i class="trash icon"></i></button></td>' : "";
		return `<tr><td><input class="ui input" data-balance-code value="${this.escape(row.kode_akun || "")}" ${readonly} placeholder="1.1.1.01"></td><td><input class="ui input" data-balance-name value="${this.escape(row.uraian || "")}" ${readonly} maxlength="500" placeholder="Uraian rekening"></td><td data-account-group>${this.escape(row.kelompok || this.groupFromCode(row.kode_akun))}</td><td><input type="number" step="0.01" data-balance-opening value="${this.escape(row.saldo_awal ?? 0)}" ${readonly}></td><td><input type="number" step="0.01" data-balance-closing value="${this.escape(row.saldo_akhir ?? 0)}" ${readonly}></td><td><input data-balance-note value="${this.escape(row.catatan || "")}" ${readonly} placeholder="Sumber/saldo aset"></td>${remove}</tr>`;
	}

	groupFromCode(code) {
		const prefix = String(code || "").trim().charAt(0);
		return ({ "1": "ASET", "2": "KEWAJIBAN", "3": "EKUITAS" })[prefix] || "";
	}

	updateAccountGroup(input) {
		const groupCell = input.closest("tr")?.querySelector("[data-account-group]");
		if (groupCell) groupCell.textContent = this.groupFromCode(input.value);
	}

	addBalanceRow() {
		this.root.querySelector("#balanceRows")?.insertAdjacentHTML("beforeend", this.balanceRow({}));
	}

	async saveBalances(button) {
		const rows = [...this.root.querySelectorAll("#balanceRows tr")].map((row) => ({
			kode_akun: row.querySelector("[data-balance-code]")?.value || "",
			uraian: row.querySelector("[data-balance-name]")?.value || "",
			kelompok: this.groupFromCode(row.querySelector("[data-balance-code]")?.value),
			saldo_awal: row.querySelector("[data-balance-opening]")?.value || "",
			saldo_akhir: row.querySelector("[data-balance-closing]")?.value || "",
			catatan: row.querySelector("[data-balance-note]")?.value || "",
		})).filter((row) => row.kode_akun || row.uraian);
		button.classList.add("loading", "disabled");
		try {
			await this.request("/laporan-keuangan/saldo/save", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ rows }) });
			window.Toast?.success?.("Saldo Neraca berhasil disimpan.");
			await this.load();
		} finally {
			button.classList.remove("loading", "disabled");
		}
	}

	async importBalances(input) {
		const file = input.files?.[0];
		if (!file) return;
		if (!window.confirm("Impor akan mengganti seluruh saldo Neraca aktif untuk OPD dan tahun ini. Lanjutkan?")) {
			input.value = "";
			return;
		}
		const form = new FormData();
		form.append("file", file);
		try {
			await this.request("/laporan-keuangan/saldo/import", { method: "POST", body: form });
			window.Toast?.success?.("Saldo Neraca berhasil diimpor.");
			await this.load();
		} catch (error) {
			window.Toast?.error?.(error.message);
		} finally {
			input.value = "";
		}
	}

	renderNotes(data) {
		this.noteRows = data.rows || [];
		this.sections = data.sections || {};
		const content = this.root.querySelector("#financialReportContent");
		const balanceStatus = data.neraca?.seimbang ? "Neraca saat ini seimbang." : "Neraca belum seimbang atau saldo belum tersedia.";
		content.innerHTML = `<div class="ui ${data.neraca?.seimbang ? "positive" : "warning"} message"><b>Prasyarat pengungkapan:</b> ${this.escape(balanceStatus)} CaLK menjelaskan kebijakan, rincian, dan informasi material; isi dengan fakta dan dokumen pendukung, bukan teks contoh yang belum diverifikasi.</div>
			<div class="financial-report-toolbar"><div><h2>Catatan atas Laporan Keuangan ${this.escape(data.tahun)}</h2><p>Susun pengungkapan berdasarkan kebijakan akuntansi pemerintah daerah dan bukti transaksi tahun berjalan.</p></div><div class="ui buttons"><a class="ui green button" href="${this.url("/laporan-keuangan/export?report=calk&format=xlsx")}"><i class="file excel icon"></i>Excel</a><a class="ui red button" href="${this.url("/laporan-keuangan/export?report=calk&format=pdf")}"><i class="file pdf icon"></i>PDF</a></div></div>
			${this.canWrite ? `<div class="financial-input-toolbar"><button type="button" class="ui basic button" data-action="add-note"><i class="plus icon"></i>Tambah Catatan</button><button type="button" class="ui primary button" data-action="save-notes"><i class="save icon"></i>Simpan CaLK</button></div>` : ""}
			<div id="calkRows">${this.noteRows.length ? this.noteRows.map((row) => this.noteRow(row)).join("") : this.canWrite ? this.noteRow({}) : '<div class="ui message">Belum ada catatan CaLK untuk tahun ini.</div>'}</div>
			<p class="financial-draft-note">CaLK adalah bagian dari laporan keuangan dan harus konsisten dengan Neraca/LRA serta kebijakan akuntansi daerah. Dokumen ini merupakan kertas kerja sampai diverifikasi pejabat berwenang.</p>`;
	}

	noteRow(row = {}) {
		const options = Object.entries(this.sections).map(([key, label]) => `<option value="${this.escape(key)}" ${row.bagian === key ? "selected" : ""}>${this.escape(label)}</option>`).join("");
		const readonly = this.canWrite ? "" : "disabled";
		const remove = this.canWrite ? '<button type="button" class="ui mini basic red icon button" data-action="remove-note" aria-label="Hapus catatan"><i class="trash icon"></i></button>' : "";
		return `<article class="ui segment calk-entry" data-note-row><div class="calk-entry-toolbar">${remove}</div><div class="field"><label>Bagian CaLK</label><select class="ui dropdown" data-note-section ${readonly}>${options}</select></div><div class="field"><label>Judul pengungkapan</label><input data-note-title maxlength="255" value="${this.escape(row.judul || "")}" ${this.canWrite ? "" : "readonly"}></div><div class="field"><label>Uraian dan dasar pendukung</label><textarea rows="4" data-note-body ${readonly}>${this.escape(row.uraian || "")}</textarea></div></article>`;
	}

	addNoteRow() {
		this.root.querySelector("#calkRows")?.insertAdjacentHTML("beforeend", this.noteRow({}));
	}

	async saveNotes(button) {
		const rows = [...this.root.querySelectorAll("[data-note-row]")].map((row) => ({
			bagian: row.querySelector("[data-note-section]")?.value || "",
			judul: row.querySelector("[data-note-title]")?.value || "",
			uraian: row.querySelector("[data-note-body]")?.value || "",
		}));
		button.classList.add("loading", "disabled");
		try {
			await this.request("/laporan-keuangan/calk/save", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ rows }) });
			window.Toast?.success?.("Catatan CaLK berhasil disimpan.");
			await this.load();
		} finally {
			button.classList.remove("loading", "disabled");
		}
	}
}
