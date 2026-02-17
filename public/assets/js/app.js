/* =========================================================
	 GLOBAL CONFIG
	 ---------------------------------------------------------
	 → Konfigurasi dasar aplikasi
	 → Jika ganti base URL atau endpoint API, ubah di sini
========================================================= */

const AppConfig = {
	baseUrl: window.BASEURL || "/", // Base URL aplikasi
	apiUrl: window.BASEURL || "/", // Endpoint API utama
	defaultMethod: "POST", // Default method AJAX
};

/* =========================================================
	 GLOBAL STATE (UNIVERSAL)
	 ---------------------------------------------------------
	 → Menyimpan state aktif tabel & pagination
	 → Dipakai oleh TableManager dan modul lain
========================================================= */

const AppState = {
	halaman: 1, // Halaman aktif saat ini
	rows: 10, // Jumlah data per halaman
	jenis: "", // Module aktif (referensi, renstra, dll)
	mode: "", // add / edit / detail  <-- TAMBAH INI
	tbl: "", // Tabel aktif
	cari: "", // Keyword pencarian
	currentMenu: "", // Tracking menu sebelumnya
};
/* =========================================================
   GLOBAL TOAST ENGINE
========================================================= */

const ToastEngine = {
	show({ success = true, message = "" }) {
		if (!message) return;

		$("body").toast({
			message: message,
			class: success ? "success" : "error",
			position: "top right",
			displayTime: 3000,
			showProgress: "bottom",
		});
	},
};
/* =========================================================
	 CORE AJAX ENGINE
	 ---------------------------------------------------------
	 → Wrapper universal untuk semua AJAX request
	 → Semua komunikasi backend lewat sini
========================================================= */

class AjaxEngine {
	constructor(url = "") {
		this.url = url;
	}

	request({
		url = this.url,
		method = AppConfig.defaultMethod,
		data = {},
		beforeSend = null,
		success = null,
		error = null,
		complete = null,
		processData = true,
		contentType = "application/x-www-form-urlencoded; charset=UTF-8",
	}) {
		return $.ajax({
			type: method,
			url: url,
			data: data,
			dataType: "json",
			processData: processData,
			contentType: contentType,
			beforeSend: function () {
				if (beforeSend) beforeSend();
			},
			success: function (res) {
				// 🔥 AUTO TOAST GLOBAL
				if (res && typeof res.success !== "undefined") {
					ToastEngine.show({
						success: res.success,
						message: res.message || "",
					});
				}

				if (success) success(res);
			},
			error: function (err) {
				console.error("AJAX ERROR:", err);
				if (error) error(err);
			},
			complete: function () {
				if (complete) complete();
			},
		});
	}
}

/* =========================================================
	 UNIVERSAL TABLE MODULE
	 ---------------------------------------------------------
	 → Konfigurasi tombol aksi per module/tabel
	 → Tambah atau ubah aturan tombol di sini
========================================================= */

const ActionConfig = {
	// DEFAULT GLOBAL (jika tidak ada aturan khusus)
	default: {
		buttons: ["edit", "delete"],
	},

	// MODULE REFERENSI
	referensi: {
		sub_kegiatan: {
			buttons: ["edit", "delete"],
		},
		satuan: {
			buttons: ["edit"],
		},
	},

	// MODULE RENSTRA
	renstra: {
		tujuan_sasaran_renstra: {
			buttons: ["edit"], // hanya tombol edit
		},
	},
};
// untuk renstra menu
const RenstraHeaderConfig = {
	renstra_neo: [
   "Periode Mulai",
   "Periode Selesai",
   "Visi"
],
	misi_renstra_neo: ["Kode", "Uraian"],
	tujuan_renstra_neo: ["Kode", "Uraian"],
	sasaran_renstra_neo: ["Kode", "Uraian"],
	indikator_sasaran_renstra_neo: ["Indikator", "Satuan"],
	program_renstra_neo: ["Kode Program", "Uraian"],
	indikator_program_renstra_neo: ["Indikator", "Target"],
	anggaran_program_renstra_neo: ["Tahun", "Pagu"],
};
/* =========================================================
	 TABLE MANAGER
	 ---------------------------------------------------------
	 → Engine utama load & render tabel
	 → Mengatur:
			 - Load data
			 - Render table
			 - Render pagination
========================================================= */

class TableManager {
	constructor() {
		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");
	}

	/* -----------------------------------------------------
		 Load tabel berdasarkan module & nama tabel
		 Dipanggil saat klik menu
	------------------------------------------------------ */
	load(jenis, tbl) {
		// Reset halaman jika pindah menu
		if (AppState.currentMenu !== tbl) {
			AppState.halaman = 1;
		}

		AppState.jenis = jenis;
		AppState.tbl = tbl;
		AppState.currentMenu = tbl;

		this.fetch();
	}

	/* -----------------------------------------------------
		 Fetch data dari backend
		 Mengirim:
				 jenis, tbl, halaman, rows, cari
	------------------------------------------------------ */
	fetch() {
		// Sinkronisasi jumlah rows dari dropdown (Fomantic UI)
		if ($("#countRow").length) {
			let value = $("#countRow").dropdown("get value");
			AppState.rows = parseInt(value) || AppState.rows;
		}

		this.ajax.request({
			data: {
				jenis: AppState.jenis,
				tbl: AppState.tbl,
				halaman: AppState.halaman,
				rows: AppState.rows,
				cari: AppState.cari,
			},
			success: (res) => {
				if (!res || res.success !== true) {
					console.warn(res?.message || "Response tidak valid");
					this.renderTable([]);
					return;
				}

				let rows = Array.isArray(res.data) ? res.data : [];
				let meta = res.meta || {};

				this.renderTable(rows);

				this.renderPagination({
					total: meta.total || 0,
					limit: meta.limit || AppState.rows,
					page: meta.page || AppState.halaman,
				});
			},
		});
	}

	/* -----------------------------------------------------
		 Render isi tbody tabel
	------------------------------------------------------ */
	renderTable(rows) {
		if (AppState.jenis === "renstra") {
			let headers = RenstraHeaderConfig[AppState.tbl] || [];

			let theadHtml = "<tr>";

			headers.forEach((h) => {
				theadHtml += `<th>${h}</th>`;
			});

			theadHtml += `<th class="collapsing">Aksi</th></tr>`;

			$("thead tr").replaceWith(theadHtml);
		}
		let target = `tbody[name="tabel_${AppState.jenis}"]`;
		let html = "";

		if (!rows.length) {
			html = `
            <tr>
                <td colspan="100%" class="center aligned">
                    Tidak ada data
                </td>
            </tr>
        `;
			$(target).html(html);
			return;
		}

		rows.forEach((row) => {
			html += "<tr>";

			Object.keys(row).forEach((key) => {
				if (key === "id") return;
				html += `<td>${row[key] ?? ""}</td>`;
			});

			html += `<td class="collapsing">
            ${this.buildActionButtons(row)}
        </td>`;

			html += "</tr>";
		});

		$(target).html(html);
	}

	/* -----------------------------------------------------
		 Render pagination
		 ⚠️ LOGIC ASLI TIDAK DIUBAH
	------------------------------------------------------ */
	renderPagination(meta) {
		let target = `div[name="pagination_${AppState.jenis}"]`;

		if (!meta || !meta.total) {
			$(target).html("");
			return;
		}

		let currentPage = meta.page || 1;
		let limit = meta.limit || 10;
		let totalPage = Math.ceil(meta.total / limit);

		if (totalPage <= 1) {
			$(target).html("");
			return;
		}

		let html = `<div class="ui pagination menu">`;

		if (currentPage > 1) {
			html += `<a class="item" data-page="1">
					<i class="angle double left chevron icon"></i>
				 </a>`;

			html += `<a class="item" data-page="${currentPage - 1}">
					<i class="angle left icon"></i>
				 </a>`;
		}

		let start = Math.max(1, currentPage - 1);
		let end = Math.min(totalPage, currentPage + 1);

		for (let i = start; i <= end; i++) {
			let active = i === currentPage ? "active" : "";
			html += `<a class="item ${active}" data-page="${i}">${i}</a>`;
		}

		if (currentPage < totalPage) {
			html += `<a class="item" data-page="${currentPage + 1}">
					<i class="angle right icon"></i>
				 </a>`;

			html += `<a class="item" data-page="${totalPage}">
					<i class="angle double right chevron icon"></i>
				 </a>`;
		}

		html += `</div>`;

		$(target).html(html);
	}

	/* -----------------------------------------------------
		 Build tombol aksi (edit/delete/detail)
	------------------------------------------------------ */
	buildActionButtons(row) {
		let jenis = AppState.jenis;
		let tbl = AppState.tbl;

		let config =
			ActionConfig[jenis]?.[tbl] || ActionConfig[jenis] || ActionConfig.default;

		let buttons = config.buttons || [];

		if (!buttons.length) return "";

		let html = `<div class="ui icon basic mini buttons">`;

		buttons.forEach((btn) => {
			if (btn === "edit") {
				html += `
			<button class="ui button"
				data-ui="open-form"
				data-container="flyout"
				data-jns="edit"
				data-tbl="${tbl}"
				data-id="${row.id}">
				<i class="edit outline blue icon"></i>
			</button>
			`;
			}

			if (btn === "delete") {
				html += `
			<button class="ui red button"
				data-ui="delete-row"
				data-jns="delete"
				data-tbl="${tbl}"
				data-id="${row.id}">
				<i class="trash alternate outline red icon"></i>
			</button>
			`;
			}

			if (btn === "detail") {
				html += `
			<button class="ui green button"
				data-ui="detail-row"
				data-container="flyout"
				data-jns="detail"
				data-tbl="${tbl}"
				data-id="${row.id}">
				<i class="eye icon"></i>
			</button>
			`;
			}
		});

		html += `</div>`;
		return html;
	}
}
/* =========================================================
	 FORM ENGINE PRO - FIELD VARIATIONS (FOMANTIC STYLE)@note
========================================================= */

class FormEngine {
	/* ============================
		 Render Form ke Container
	============================ */
	static render(container, elements = []) {
		let html = this.build(elements);

		$(container).html(html);

		this.init();
	}

	/* ============================
		 Build All Elements
	============================ */
	static build(elements = []) {
		let html = "";

		elements.forEach((el) => {
			if (el.prop?.role) {
				if (!el.prop.role.includes(AppState.role)) return;
			}
			html += this.element(el);
		});

		return html;
	}

	/* ============================
		 Build Single Element
	============================ */
	static element(el) {
		const { tag, prop = {} } = el;

		switch (tag) {
			/* ===== SINGLE FIELD ===== */
			case "cardProfile":
				return this.cardProfile(prop);

			case "field":
				return this.fieldWrapper(this.input(prop), prop);

			case "fieldTextarea":
				return this.fieldWrapper(
					`<textarea name="${prop.name}" ${prop.atribut || ""}></textarea>`,
					prop,
				);

			case "fieldDropdown":
				return this.fieldWrapper(this.dropdown(prop), prop);

			case "fieldCheckbox":
				return `
					<div class="field ${prop.classField || ""}">
						<div class="ui checkbox">
							<input type="checkbox" name="${prop.name}" ${prop.atribut || ""}>
							<label>${prop.label || ""}</label>
						</div>
					</div>
				`;

			/* ===== GROUP FIELDS ===== */
			case "fields":
				return `
					<div class="fields ${prop.classGroup || ""}">
						${this.build(prop.children || [])}
					</div>
				`;

			/* ===== INLINE FIELDS ===== */
			case "inlineFields":
				return `
					<div class="inline fields">
						${this.build(prop.children || [])}
					</div>
				`;

			/* ===== DIVIDER ===== */
			case "divider":
				return `
					<h4 class="ui dividing header">
						${prop.label || ""}
					</h4>
				`;

			default:
				return "";
		}
	}
	//CARD
	static cardProfile(prop) {
		let defaultImage = AppConfig.baseUrl + "assets/img/avatar/default.jpeg";

		let imagePath = prop?.image?.trim() ? prop.image : defaultImage;

		return `
	<div class="ui special fluid card">

		<div class="content">
			<div class="right floated meta">${prop.meta || ""}</div>
			<img class="ui avatar image"
			     src="${defaultImage}"
			     onerror="imgsrc(this)">
			${prop.title || ""}
		</div>

		<div class="blurring dimmable image">
			<div class="ui dimmer">
				<div class="content">
					<div class="center">
						<button class="ui inverted icon button"
							name="direct"
							type="button"
							jns="upload"
							tbl="${prop.table}"
							id_row="${prop.id_row || ""}"
							dok="${prop.dokumen}"
							accept="${prop.accept}">
							<i class="file icon"></i>
							Upload File
						</button>
					</div>
				</div>
			</div>
			<img src="${defaultImage}" onerror="imgsrc(this)">
		</div>

		<div class="content">
			<span class="right floated">
				<i class="heart outline like icon"></i> likes
			</span>
			<i class="comment icon"></i> comments
		</div>

	</div>
	`;
	}
	/* ============================
		 Field Wrapper
	============================ */
	static fieldWrapper(inner, prop) {
		return `
			<div class="field 
				${prop.classField || ""} 
				${prop.width || ""}">
				
				${prop.label ? `<label>${prop.label}</label>` : ""}
				${inner}
			</div>
		`;
	}

	/* ============================
		 Basic Input
	============================ */
	static input(prop) {
		// icon input
		if (prop.icon) {
			return `
				<div class="ui icon input ${prop.classInput || ""}">
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
					<i class="${prop.icon} icon"></i>
				</div>
			`;
		}

		// labeled input
		if (prop.labelInput) {
			return `
				<div class="ui labeled input ${prop.classInput || ""}">
					<div class="ui label">${prop.labelInput}</div>
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
				</div>
			`;
		}

		// action input
		if (prop.action) {
			return `
				<div class="ui action input ${prop.classInput || ""}">
					<input type="${prop.type || "text"}"
						   name="${prop.name}"
						   ${prop.atribut || ""}>
					<button class="ui button">${prop.action}</button>
				</div>
			`;
		}

		// default input
		return `
			<input type="${prop.type || "text"}"
				   name="${prop.name}"
				   ${prop.atribut || ""}>
		`;
	}

	/* ============================
		 Dropdown
	============================ */
	static dropdown(prop) {
		let options = "";

		(prop.options || []).forEach((opt) => {
			options += `
				<div class="item" data-value="${opt.value}">
					${opt.text}
				</div>
			`;
		});

		return `
			<div class="ui selection dropdown ${prop.classInput || ""}">
				<input type="hidden" name="${prop.name}">
				<i class="dropdown icon"></i>
				<div class="default text">Pilih</div>
				<div class="menu">
					${options}
				</div>
			</div>
		`;
	}

	/* ============================
		 Init Fomantic Components
	============================ */
	static init() {
		$(".ui.dropdown").dropdown();
		$(".ui.checkbox").checkbox();
		$(".ui.card .image").dimmer({
			on: "hover",
		});
	}
}
/* =========================================================
   ROLE ENGINE
========================================================= */

const RoleConfig = {
	admin: {
		canAdd: true,
		canEdit: true,
		canDelete: true,
		canUpload: true,
	},
	editor: {
		canAdd: true,
		canEdit: true,
		canDelete: false,
		canUpload: true,
	},
	viewer: {
		canAdd: false,
		canEdit: false,
		canDelete: false,
		canUpload: false,
	},
};

// Simulasi role login
AppState.role = "admin";
/* =========================================================
   UI BUILDER CONFIG
	 BUAT FIELD UNTUK FORM sesuai tbl
========================================================= */

const UIConfig = {
	/* ======================================================@note
	   STANDAR HARGA
	====================================================== */
	standar_harga: {
		/* ===================== SBU (sbu_neo) ===================== */
		sbu: [
			{
				tag: "field",
				prop: { label: "Kode Aset", name: "kd_aset", classField: "required" },
			},
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{
				tag: "field",
				prop: {
					label: "Uraian Barang",
					name: "uraian_barang",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{
				tag: "fieldDropdown",
				prop: { label: "Satuan", name: "satuan", options: [] },
			},
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],

		/* ===================== SSH (ssh_neo) ===================== */
		ssh: [
			{
				tag: "field",
				prop: { label: "Kode Aset", name: "kd_aset", classField: "required" },
			},
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{
				tag: "field",
				prop: {
					label: "Uraian Barang",
					name: "uraian_barang",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{
				tag: "fieldDropdown",
				prop: { label: "Satuan", name: "satuan", options: [] },
			},
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],

		/* ===================== ASB (asb_neo) ===================== */
		asb: [
			{
				tag: "field",
				prop: { label: "Kode Aset", name: "kd_aset", classField: "required" },
			},
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{
				tag: "field",
				prop: {
					label: "Uraian Barang",
					name: "uraian_barang",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{
				tag: "fieldDropdown",
				prop: { label: "Satuan", name: "satuan", options: [] },
			},
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],

		/* ===================== HSPK (hspk_neo) ===================== */
		hspk: [
			{
				tag: "field",
				prop: { label: "Kode Aset", name: "kd_aset", classField: "required" },
			},
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{
				tag: "field",
				prop: {
					label: "Uraian Barang",
					name: "uraian_barang",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{
				tag: "fieldDropdown",
				prop: { label: "Satuan", name: "satuan", options: [] },
			},
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					atribut: `type="number"`,
				},
			},
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
		],
	},

	/* ======================================================
	   KEPEGAWAIAN (db_asn_pemda_neo)
	====================================================== */
	kepegawaian: {
		asn: [
			{
				tag: "cardProfile",
				prop: {
					title: "Aparatur Sipil Negara (ASN)",
					meta: "14h",
					image: "img/avatar/default.jpeg",
					table: "asn",
					id_row: 1,
					dokumen: "file_photo",
					accept: ".jpg,.png,.jpeg",
				},
			},
			{
				tag: "field",
				prop: {
					label: "Nama Lengkap (tanpa gelar)",
					name: "nama",
					placeholder: "Nama Lengkap (tanpa gelar)",
					classField: "required",
				},
			},
			{
				tag: "fieldAction",
				prop: {
					label: "Nomor Induk Pegawai",
					name: "nip",
					placeholder: "NIP",
					button: {
						icon: "search",
						class: "teal",
						attr: {
							jns: "get_data",
							tbl: "asn",
							klm: "nip",
						},
					},
					classField: "required",
				},
			},
			{
				tag: "field",
				prop: {
					label: "Gelar",
					name: "gelar",
					placeholder: "Gelar di belakang nama",
					non_data: true,
				},
			},
			{
				tag: "field",
				prop: {
					label: "Gelar Depan Nama",
					name: "gelar_depan",
					placeholder: "Gelar di depan nama",
					non_data: true,
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kelompok Jabatan",
					name: "kelompok",
					options: [
						{ value: "1", text: "Kepala OPD" },
						{ value: "2", text: "Sekretaris" },
						{ value: "3", text: "Kepala Bidang" },
						{ value: "4", text: "ASN" },
						{ value: "5", text: "non ASN" },
					],
				},
			},
			{
				tag: "field",
				prop: {
					label: "Jabatan",
					name: "jabatan",
					placeholder: "Jabatan...",
				},
			},
			{
				tag: "field",
				prop: {
					label: "Tempat Lahir",
					name: "t4_lahir",
					placeholder: "tempat lahir",
					non_data: true,
				},
			},
			{
				tag: "fieldDate",
				prop: {
					label: "Tanggal Lahir",
					name: "tgl_lahir",
					placeholder: "Input tanggal lahir..",
					calendarType: "date",
					readonly: true,
				},
			},

			// ==============================
			// PANGKAT & GOLONGAN (DIPERTAHANKAN)
			// ==============================

			{
				tag: "fieldDropdown",
				prop: {
					label: "Golongan",
					name: "golongan",
					options: [
						{ value: "1", text: "I" },
						{ value: "2", text: "II" },
						{ value: "3", text: "III" },
						{ value: "4", text: "IV" },
					],
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Ruang",
					name: "ruang",
					options: [
						{ value: "a", text: "a" },
						{ value: "b", text: "b" },
						{ value: "c", text: "c" },
						{ value: "d", text: "d" },
						{ value: "e", text: "e" },
					],
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Jenis Kepegawaian",
					name: "jenis_kepeg",
					options: [
						{ value: "pnsp", text: "ASN pusat" },
						{ value: "pnsd1", text: "ASN Provinsi" },
						{ value: "pnsd2", text: "ASN Kabupaten/Kota" },
						{ value: "pnsp_dpb1", text: "ASN Pusat diperbantukan Provinsi" },
						{ value: "pnsp_dpb2", text: "ASN Pusat diperbantukan Kab./Kota" },
						{ value: "pnsp_dpk1", text: "ASN Pusat dipekerjakan Provinsi" },
						{ value: "pnsp_dpk2", text: "ASN Pusat dipekerjakan Kab./Kota" },
						{ value: "pnsd_dpb_pusat", text: "ASN Daerah diperbantukan Pusat" },
						{ value: "pnsd_dpk_pusat", text: "ASN Daerah dipekerjakan Pusat" },
						{ value: "swasta", text: "Swasta" },
					],
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Status Kepegawaian",
					name: "status_kepeg",
					options: [
						{ value: "capeg", text: "Calon Pegawai" },
						{ value: "peg_tetap", text: "ASN/Pegawai tetap" },
						{ value: "mpp", text: "Masa Persiapan Pensiun" },
						{ value: "pen_uang_tunggu", text: "Pensiunan" },
						{ value: "peg_seorsing", text: "Pegawai Seorsing" },
						{ value: "cuti", text: "Cuti" },
						{ value: "peg_sementara", text: "Pegawai Sementara" },
						{ value: "peg_bulanan", text: "Pegawai Bulanan" },
					],
				},
			},
			{
				tag: "field",
				prop: {
					label: "Nomor KTP",
					name: "no_ktp",
					placeholder: "Nomor ktp...",
				},
			},
			{
				tag: "field",
				prop: { label: "NPWP", name: "npwp", placeholder: "NPWP..." },
			},
			{
				tag: "field",
				prop: { label: "Alamat", name: "alamat", placeholder: "Alamat..." },
			},
			{
				tag: "field",
				prop: {
					label: "Kontak Person",
					name: "kontak_person",
					placeholder: "Kontak Person...",
				},
			},
			{
				tag: "field",
				prop: { label: "email", name: "email", placeholder: "email..." },
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Agama",
					name: "agama",
					options: [
						{ value: "islam", text: "Islam" },
						{ value: "kristen", text: "Kristen" },
						{ value: "katolik", text: "Katolik" },
						{ value: "protestan", text: "Protestan" },
						{ value: "hindu", text: "Hindu" },
						{ value: "budha", text: "Budha" },
						{ value: "konghucu", text: "Konghucu" },
						{ value: "yahudi", text: "Yahudi" },
						{ value: "kepercayaan", text: "Kepercayaan Tuhan YME." },
					],
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kelamin",
					name: "kelamin",
					options: [
						{ value: "pria", text: "Pria" },
						{ value: "wanita", text: "Wanita" },
					],
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Status",
					name: "status",
					options: [
						{ value: "menikah", text: "Menikah" },
						{ value: "janda-duda", text: "Duda-Janda" },
						{ value: "lajang", text: "Lajang" },
					],
				},
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					rows: 2,
					non_data: true,
				},
			},
			{
				tag: "fieldCheckbox",
				prop: {
					label: "Non Aktif",
					name: "disable",
					type: "toggle",
					non_data: true,
				},
			},
		],
	},

	/* ======================================================
	   REFERENSI
	====================================================== */
	referensi: {
		/* sub_kegiatan_neo */
		sub_kegiatan: [
			{
				tag: "field",
				prop: { label: "Kode", name: "kode", classField: "required" },
			},
			{
				tag: "field",
				prop: {
					label: "Nomenklatur Urusan",
					name: "nomenklatur_urusan",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "Kinerja", name: "kinerja" } },
			{ tag: "field", prop: { label: "Indikator", name: "indikator" } },
		],

		/* rekanan_neo */
		rekanan: [
			{
				tag: "field",
				prop: {
					label: "Nama Perusahaan",
					name: "nama_perusahaan",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "NPWP", name: "npwp" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Alamat", name: "alamat", atribut: `rows="3"` },
			},
			{ tag: "field", prop: { label: "Email", name: "email" } },
			{ tag: "field", prop: { label: "No Rekening", name: "no_rekening" } },
			{ tag: "field", prop: { label: "Bank Rekening", name: "bank_rekening" } },
			{ tag: "field", prop: { label: "Direktur", name: "direktur" } },
		],

		/* satuan_neo */
		satuan: [
			{
				tag: "field",
				prop: { label: "Value", name: "value", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Item", name: "item", classField: "required" },
			},
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		/* mapping_aset_akun */
		mapping: [
			{
				tag: "fieldDropdown",
				prop: { label: "Aset", name: "id_aset", options: [] },
			},
			{
				tag: "fieldDropdown",
				prop: { label: "Akun", name: "id_akun", options: [] },
			},
		],

		/* aset_neo */
		neraca: [
			{
				tag: "field",
				prop: { label: "Kode", name: "kode", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Uraian", name: "uraian", classField: "required" },
			},
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		/* takun_neo */
		akun: [
			{
				tag: "field",
				prop: { label: "Kode", name: "kode", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Uraian", name: "uraian", classField: "required" },
			},
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		/* sumber_dana_neo */
		sumber_dana: [
			{
				tag: "field",
				prop: { label: "Kode", name: "kode", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Uraian", name: "uraian", classField: "required" },
			},
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		/* organisasi_neo */
		organisasi: [
			{
				tag: "field",
				prop: { label: "Kode", name: "kode", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Uraian", name: "uraian", classField: "required" },
			},
			{ tag: "field", prop: { label: "Alamat", name: "alamat" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		/* peraturan_neo */
		peraturan: [
			{
				tag: "field",
				prop: { label: "Nomor", name: "nomor", classField: "required" },
			},
			{ tag: "field", prop: { label: "Judul", name: "judul" } },
			{
				tag: "field",
				prop: {
					label: "Tanggal Penetapan",
					name: "tgl_penetapan",
					atribut: `type="date"`,
				},
			},
			{ tag: "field", prop: { label: "Status", name: "status" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="3"` },
			},
		],

		/* wilayah_neo */
		wilayah: [
			{
				tag: "field",
				prop: { label: "Kode", name: "kode", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Uraian", name: "uraian", classField: "required" },
			},
			{ tag: "field", prop: { label: "Status", name: "status" } },
			{ tag: "field", prop: { label: "Jumlah Kecamatan", name: "jml_kec" } },
			{ tag: "field", prop: { label: "Jumlah Desa", name: "jml_desa" } },
			{ tag: "field", prop: { label: "Luas", name: "luas" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],
	},
};

/* =========================================================
   FORM CONTAINER MANAGER
   Bisa tampil di:
   - Flyout
   - Modal
========================================================= */

class FormContainerManager {
	constructor() {
		this.$flyout = $("#mainContext .sidebarkanan");
		this.$modal = $("#mainModal");

		this.activeContainer = "flyout";

		this.ajax = new AjaxEngine(AppConfig.apiUrl + "dynamic");

		this.initContainers(); // <-- TAMBAHKAN INI
	}
	initContainers() {
		// INIT FLYOUT HANYA SEKALI
		if (this.$flyout.length && !this.$flyout.data("module-sidebar")) {
			this.$flyout.sidebar({
				context: $("#mainContext"),
				transition: "push",
				// dimPage: false,
				closable: false, //saat dimmer di klik sidebar kanan tidak muncul
				scrollLock: true,
			});
		}

		if (this.$modal.length && !this.$modal.data("module-modal")) {
			this.$modal.modal({ closable: false });
		}

		this.bindEvents();

		// Modal biasanya sudah aman, tapi boleh guard juga
		if (this.$modal.length && !this.$modal.data("module-modal")) {
			this.$modal.modal({
				closable: false,
			});
		}
	}
	/* --------------------------------------------- */
	bindEvents() {
		const self = this; // 🔥 penting untuk jaga context

		// OPEN FORM
		$(document).on("click", '[data-ui="open-form"]', function (e) {
			e.preventDefault();
			self.open($(this));
		});

		// SUBMIT
		$(document).on("click", ".btnSubmit", function () {
			self.save();
		});

		// CLOSE BUTTON
		$(document).on("click", ".btnFlyoutClose", function () {
			self.$flyout.sidebar("hide");
		});

		// ICON CLOSE
		$(document).on("click", ".sidebarkanan .close.icon", function () {
			self.$flyout.sidebar("hide");
		});
	}
	getActiveForm() {
		if (this.activeContainer === "modal") {
			return $("#form_modal");
		}
		return $("#form_flyout");
	}
	/* --------------------------------------------- */
	open($btn) {
		const jenis = $btn.data("jns");
		const tbl = $btn.data("tbl");
		const container = $btn.data("container") || "flyout";
		const idRow = $btn.data("id");

		this.activeContainer = container;

		AppState.mode = jenis;
		AppState.tbl = tbl;

		// 🔥 TAMBAH INI
		AppState.jenis = window.location.pathname.replace(/^\/+/g, "");

		let config = this.buildConfig(jenis, tbl);

		this.render(config, container);

		if ((jenis === "edit" || jenis === "detail") && idRow) {
			this.loadData(idRow, container);
		}

		this.show(container);
	}

	/* --------------------------------------------- */
	render(config, container) {
		$("#form_modal").empty();
		$("#form_flyout").empty();

		// ✅ target HARUS dideklarasikan di luar if
		let target;

		if (container === "modal") {
			target = "#form_modal";
			$("#icon_modal").attr("class", config.icon);
			$("#content_modal").text(config.header);
		} else {
			target = "#form_flyout";
			$("#icon_flyout").attr("class", config.icon);
			$("#content_flyout").text(config.header);
		}

		FormEngine.render(target, config.elements);

		// ✅ Hidden ID hanya saat edit
		if (AppState.mode === "edit") {
			$(target).prepend(`<input type="hidden" name="id">`);
		}
	}

	/* --------------------------------------------- */
	show(container) {
		if (container === "modal") {
			this.$modal.modal("show");
		} else {
			this.$flyout.sidebar("show");
		}
	}

	/* --------------------------------------------- */
	hide(container) {
		if (container === "modal") {
			this.$modal.modal("hide");
		} else {
			this.$flyout.sidebar("hide");
		}
	}
	/* --------------------------------------------- */
	loadData(idRow, container) {
		this.ajax.request({
			data: {
				jenis: "edit",
				tbl: AppState.tbl,
				id_row: idRow,
			},
			success: (res) => {
				if (res.success && res.data) {
					let $formTarget =
						container === "modal" ? $("#form_modal") : $("#form_flyout");

					Object.keys(res.data).forEach((key) => {
						let $field = $formTarget.find(`[name="${key}"]`);

						if (!$field.length) return;

						// 🔹 Dropdown Fomantic
						if ($field.closest(".ui.dropdown").length) {
							$field
								.closest(".ui.dropdown")
								.dropdown("set selected", res.data[key]);
						}

						// 🔹 Checkbox
						else if ($field.attr("type") === "checkbox") {
							if (res.data[key] == 1 || res.data[key] === true) {
								$field.closest(".ui.checkbox").checkbox("check");
							} else {
								$field.closest(".ui.checkbox").checkbox("uncheck");
							}
						}

						// 🔹 File input (tidak bisa di-set demi security)
						else if ($field.attr("type") === "file") {
							// skip (browser tidak izinkan set value file)
						}

						// 🔹 Input biasa
						else {
							$field.val(res.data[key]);
						}
					});
				}
			},
		});
	}
	/* --------------------------------------------- */
	buildConfig(jenis, tbl) {
		let config = {
			icon: "folder icon",
			header: "",
			elements: [],
		};

		config.header =
			jenis === "add"
				? "Tambah Data"
				: jenis === "edit"
					? "Edit Data"
					: "Detail Data";

		config.icon =
			jenis === "add"
				? "plus icon"
				: jenis === "edit"
					? "edit icon"
					: "eye icon";

		if (UIConfig[AppState.jenis]?.[tbl]) {
			config.elements = UIConfig[AppState.jenis][tbl];
		}

		return config;
	}

	/* --------------------------------------------- */
	save() {
		let $formTarget = this.getActiveForm();
		let formElement = $formTarget[0];

		if (!formElement) return;

		let formData = new FormData(formElement);

		formData.append("jenis", AppState.mode);
		formData.append("tbl", AppState.tbl);

		this.ajax.request({
			url: AppConfig.apiUrl + "dynamic",
			method: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: (res) => {
				if (res.success) {
					this.hide(this.activeContainer); // <-- lebih aman
					new TableManager().fetch();
				}
			},
		});
	}
}
// Kenapa Ini Versi CEO & Interaktif?
function loadProfil() {
	$.post(
		"/profil/load",
		function (res) {
			if (res.status === "success") {
				let d = res.data;

				for (let key in d) {
					$('[name="' + key + '"]').val(d[key]);
				}

				$("#card_nama").text(d.nama);
				$("#card_type").text(d.type_user);
				$("#card_tahun").text(d.tahun);
				$("#card_login").text(d.tgl_login ?? "-");

				if (d.photo) {
					$("#preview_photo").attr("src", "uploads/" + d.photo);
				}

				$(".ui.dropdown").dropdown("refresh");
			}
		},
		"json",
	);
}

/* =========================================================
	 INIT APPLICATION (document.ready)
	 ---------------------------------------------------------
	 → Semua inisialisasi dijalankan saat DOM siap
	 → Mengatur:
			 - TableManager
			 - Auto load berdasarkan URL
			 - Sidebar
			 - Menu click
			 - Dropdown rows
			 - Pagination click
			 - Search
			 - Flyout
========================================================= */

$(document).ready(function () {
	//@note $ready
	/* ---------------------------------------------
	   Inisialisasi Table Manager
	---------------------------------------------- */
	tableManager = new TableManager();

	/* ---------------------------------------------
	   Ambil elemen context utama
	---------------------------------------------- */
	const $context = $("#mainContext");

	/* ---------------------------------------------
	   Sidebar reference
	---------------------------------------------- */
	const $sidebarUtama = $(".sidebarutama");

	/* ---------------------------------------------
	   Ambil parameter URL (?tbl=...)
	---------------------------------------------- */
	const params = new URLSearchParams(window.location.search);
	const tblFromUrl = params.get("tbl");

	/* ---------------------------------------------
	   Ambil path aktif
	---------------------------------------------- */
	const currentPath = window.location.pathname.replace(/^\/+/g, "");

	/* ---------------------------------------------
	   Sinkronisasi jumlah rows pertama kali
	---------------------------------------------- */
	if ($("#countRow").length) {
		let value = $("#countRow").dropdown("get value");
		AppState.rows = parseInt(value) || 10;
	}

	/* ---------------------------------------------
	   Auto load tabel jika ada ?tbl=
	---------------------------------------------- */

	if (tblFromUrl && currentPath) {
		tableManager.load(currentPath, tblFromUrl);
	}

	/* ---------------------------------------------
	   Inisialisasi Sidebar Fomantic
	---------------------------------------------- */
	$sidebarUtama.sidebar({
		context: $context,
		transition: "push",
	});

	/* ---------------------------------------------
	   Toggle Sidebar Button
	---------------------------------------------- */
	$("#toggleSidebar").on("click", function () {
		$sidebarUtama.sidebar("toggle");
	});

	/* =============================================
	   MENU CLICK LOAD TABLE
	============================================= */
	$(document).on("click", '[name="menu_table"]', function (e) {
		e.preventDefault();

		let jenis = $(this).attr("jenis");
		let tbl = $(this).attr("tbl");

		tableManager.load(jenis, tbl);
	});

	/* =============================================
	   DROPDOWN COUNT ROW
	============================================= */
	$("#countRow").dropdown({
		onChange: function (value) {
			AppState.rows = parseInt(value) || 10;
			AppState.halaman = 1;
			tableManager.fetch();
		},
	});

	/* ---------------------------------------------
	   Inisialisasi dropdown & accordion
	---------------------------------------------- */
	$(".ui.dropdown").dropdown();
	$(".ui.accordion").accordion({ exclusive: false });

	/* =============================================
	   PAGINATION CLICK
	============================================= */
	$(document).on("click", '[name^="pagination_"] .item', function () {
		let page = parseInt($(this).data("page"));
		if (!page) return;

		AppState.halaman = page;
		tableManager.fetch();
	});

	/* =============================================
	   SEARCH INPUT (Debounce)
	============================================= */
	let searchTimer;

	$("#cari_data").on("input", function () {
		clearTimeout(searchTimer);

		searchTimer = setTimeout(() => {
			AppState.cari = $(this).val().trim();
			AppState.halaman = 1;
			tableManager.fetch();
		}, 700);
	});

	/* ---------------------------------------------
	   Inisialisasi Flyout Manager
	---------------------------------------------- */
	// let flyoutManager = new FlyoutManager("#mainContext");
	let formContainerManager = new FormContainerManager();
	/* =========================================
	   WALLCHAT MODULE (TETAP UTUH)
	========================================= */

	$(document).on("submit", "#formPost", function (e) {
		e.preventDefault();
		$.post(
			"/wallchat/store",
			$(this).serialize(),
			function (res) {
				if (res.status) location.reload();
			},
			"json",
		);
	});

	$(document).on("submit", ".formComment", function (e) {
		e.preventDefault();

		let parent_id = $(this).data("id");
		let content = $(this).find('input[name="content"]').val();

		$.post(
			"/wallchat/comment",
			{
				parent_id: parent_id,
				content: content,
			},
			function (res) {
				if (res.status) location.reload();
			},
			"json",
		);
	});

	$(document).on("click", "#btnPrivateMessage", function () {
		$("#modalPrivateMessage").modal("show");
	});

	$(document).on("submit", "#formPrivateMessage", function (e) {
		e.preventDefault();

		$.post(
			"/wallchat/private",
			$(this).serialize(),
			function (res) {
				if (res.status) {
					$("#modalPrivateMessage").modal("hide");
					Swal.fire({
						icon: "success",
						title: "Berhasil",
						text: res.message,
					});
				}
			},
			"json",
		);
	});
	/* ---------------------------------------------
	   Handler Delete
	---------------------------------------------- */
	$(document).on("click", '[data-ui="delete-row"]', function () {
		let tbl = $(this).data("tbl");
		let id = $(this).data("id");

		Swal.fire({
			title: "Yakin hapus data?",
			icon: "warning",
			showCancelButton: true,
		}).then((result) => {
			if (!result.isConfirmed) return;

			$.post(
				AppConfig.apiUrl + "dynamic",
				{
					jenis: "delete",
					tbl: tbl,
					id_row: id,
				},
				function () {
					tableManager.fetch();
				},
			);
		});
	});
	// export excel
	$(document).on("click", 'button[data-action="export"]', function (e) {
		console.log("EXPORT CLICKED");

		e.preventDefault();

		const table = $(this).data("tbl");

		window.location.href = AppConfig.apiUrl + "export?tabel=" + table;
	});

	/* ---------------------------------------------
	   Load Profil jika halaman profil
	---------------------------------------------- */
	if (window.location.pathname === "/profil") {
		loadProfil();
	}
	// ==============================
	// RENSTRA TAB SWITCH (BERSIH)
	// ==============================

	$(document).on("click", "#renstraMenu .item", function () {
		let tbl = $(this).data("tbl");

		// aktifkan menu
		$("#renstraMenu .item").removeClass("active");
		$(this).addClass("active");

		// ubah judul
		$("#judulTabel").text($(this).text().toUpperCase());

		// ubah tombol
		$("#btnTambah").attr("data-tbl", tbl);
		$("#btnImport").attr("data-tbl", tbl);
		$("#btnExport").attr("data-tbl", tbl);

		// 🔥 pakai engine global
		tableManager.load("renstra", tbl);
	});
});
