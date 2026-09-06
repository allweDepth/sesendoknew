/**
 * ======================================================
 * 🔥 UI CONFIG – ENTERPRISE FLAT STRUCTURE
 * ======================================================
 * RULES:
 * 1. Semua module FLAT (tidak nested)
 * 2. Setiap module punya:
 *      - layout (opsional)
 *      - validation (opsional)
 *      - permissions (opsional)
 *      - form.elements (WAJIB)
 * 3. Menu hierarchy TIDAK disimpan di sini
 * ======================================================
 */

const UIConfig = {};

/* ======================================================
   1️⃣ PENGATURAN
====================================================== */

UIConfig.pengaturan = {
	layout: { columns: 2 },
	validation: {
		tahun: { required: true },
		tahun_renstra: { required: true },
	},
	form: {
		elements: [
			// ================= IDENTITAS =================
			{
				tag: "input",
				prop: { label: "Tahun Anggaran", name: "tahun", type: "number" },
			},
			{
				tag: "input",
				prop: { label: "Tahun Renstra", name: "tahun_renstra", type: "number" },
			},

			// ================= RANGE PERIODE =================
			{
				tag: "rangeCalendar",
				prop: {
					label: "Renja",
					nameStart: "awal_renja",
					nameEnd: "akhir_renja",
					calendarType: "datetime",
				},
			},
			{
				tag: "rangeCalendar",
				prop: {
					label: "DPA",
					nameStart: "awal_dpa",
					nameEnd: "akhir_dpa",
				},
			},
			{
				tag: "rangeCalendar",
				prop: {
					label: "RKPD",
					nameStart: "awal_rkpd",
					nameEnd: "akhir_rkpd",
				},
			},

			// ================= CONTROL =================
			{ tag: "toggle", prop: { label: "Disable", name: "disable" } },
			{ tag: "toggle", prop: { label: "Kunci Global", name: "kunci" } },
			{ tag: "toggle", prop: { label: "Setujui Global", name: "setujui" } },
			{ tag: "textarea", prop: { label: "Keterangan", name: "keterangan" } },
		],
	},
};

/* ======================================================
   2️⃣ PERIODE RPJMD
====================================================== */

UIConfig.periode_rpjmd = {
	layout: { columns: 1 },

	validation: {
		periode_mulai: { required: true },
		periode_selesai: { required: true },
	},

	form: {
		elements: [
			{
				tag: "rangeCalendar",
				prop: {
					label: "Periode RPJMD",
					nameStart: "periode_mulai",
					nameEnd: "periode_selesai",
					calendarType: "year",
				},
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
			{ tag: "fieldCheckbox", prop: { label: "Aktif", name: "status_aktif" } },
		],
	},
};
/* ======================================================
   3️⃣ RENSTRA MODULES
====================================================== */

UIConfig.renstra_neo = {
	validation: {
		periode_id: { required: true },
		visi: { required: true },
	},
	layout: { columns: 1 },
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Periode RPJMD",
					name: "periode_id",
					source: "periode_rpjmd",
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Visi", name: "visi", atribut: `rows="3"` },
			},
			{ tag: "fieldCheckbox", prop: { label: "Status", name: "status" } },
			{ tag: "fieldCheckbox", prop: { label: "Kunci", name: "kunci" } },
			{ tag: "fieldCheckbox", prop: { label: "Setujui", name: "setujui" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.misi_renstra_neo = {
	validation: {
		renstra_id: { required: true },
		nama_misi: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Pilih Visi",
					name: "renstra_id",
					source: "renstra_neo",
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Nama Misi", name: "nama_misi", atribut: `rows="2"` },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.tujuan_renstra_neo = {
	validation: {
		misi_id: { required: true },
		nama_tujuan: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: { label: "Misi", name: "misi_id", source: "misi_renstra_neo" },
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Nama Tujuan",
					name: "nama_tujuan",
					atribut: `rows="2"`,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.sasaran_renstra_neo = {
	validation: {
		tujuan_id: { required: true },
		nama_sasaran: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Tujuan",
					name: "tujuan_id",
					source: "tujuan_renstra_neo",
				},
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Nama Sasaran",
					name: "nama_sasaran",
					atribut: `rows="2"`,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.indikator_sasaran_renstra_neo = {
	validation: {
		sasaran_id: { required: true },
		nama_indikator: { required: true },
		target_akhir: { required: true },
	},
	layout: { columns: 2 },
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sasaran",
					name: "sasaran_id",
					source: "sasaran_renstra_neo",
					search: true,
				},
			},
			{
				tag: "field",
				prop: { label: "Nama Indikator", name: "nama_indikator" },
			},
			{ tag: "field", prop: { label: "Satuan", name: "satuan" } },
			{ tag: "field", prop: { label: "Baseline", name: "baseline" } },
			{ tag: "field", prop: { label: "Target T1", name: "target_t1" } },
			{ tag: "field", prop: { label: "Target T2", name: "target_t2" } },
			{ tag: "field", prop: { label: "Target T3", name: "target_t3" } },
			{ tag: "field", prop: { label: "Target T4", name: "target_t4" } },
			{ tag: "field", prop: { label: "Target T5", name: "target_t5" } },
			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.program_renstra_neo = {
	validation: {
		sasaran_id: { required: true },
		kode_program: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sasaran",
					name: "sasaran_id",
					source: "sasaran_renstra_neo",
					search: true,
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "kode_program",
					source: "rekening_kegiatan",
					search: true,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.indikator_program_renstra_neo = {
	validation: {
		program_id: { required: true },
		nama_indikator: { required: true },
		target_akhir: { required: true },
	},
	layout: { columns: 2 },
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "program_id",
					source: "program_renstra_neo",
					search: true,
				},
			},
			{
				tag: "field",
				prop: { label: "Nama Indikator", name: "nama_indikator" },
			},
			{ tag: "field", prop: { label: "Satuan", name: "satuan" } },
			{ tag: "field", prop: { label: "Baseline", name: "baseline" } },
			{ tag: "field", prop: { label: "Target T1", name: "target_t1" } },
			{ tag: "field", prop: { label: "Target T2", name: "target_t2" } },
			{ tag: "field", prop: { label: "Target T3", name: "target_t3" } },
			{ tag: "field", prop: { label: "Target T4", name: "target_t4" } },
			{ tag: "field", prop: { label: "Target T5", name: "target_t5" } },
			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.kegiatan_renstra_neo = {
	validation: {
		program_id: { required: true },
		kode_kegiatan: { required: true },
		nama_kegiatan: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "program_id",
					source: "program_renstra_neo",
					search: true,
				},
			},
			{ tag: "field", prop: { label: "Kode Kegiatan", name: "kode_kegiatan" } },
			{
				tag: "fieldTextarea",
				prop: {
					label: "Nama Kegiatan",
					name: "nama_kegiatan",
					atribut: `rows="2"`,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.sub_kegiatan_renstra_neo = {
	validation: {
		kegiatan_renstra_id: { required: true },
		master_sub_kegiatan_id: { required: true },
	},
	layout: { columns: 2 },
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kegiatan",
					name: "kegiatan_renstra_id",
					source: "kegiatan_renstra_neo",
					search: true,
					width: 16,
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Master Sub Kegiatan",
					name: "master_sub_kegiatan_id",
					source: "referensi_sub_kegiatan_renstra",
					parent: "kegiatan_renstra_id",
					search: true,
					width: 16,
				},
			},
			{ tag: "field", prop: { label: "Lokasi", name: "lokasi", width: 16 } },
			{
				tag: "field",
				prop: { label: "Kelompok Sasaran", name: "kelompok_sasaran", width: 16 },
			},
			{ tag: "field", prop: { label: "Baseline", name: "baseline", width: 16 } },
			{ tag: "field", prop: { label: "Target T1", name: "target_t1" } },
			{
				tag: "field",
				prop: { label: "Anggaran T1", name: "anggaran_t1", format: "currency" },
			},
			{ tag: "field", prop: { label: "Target T2", name: "target_t2" } },
			{
				tag: "field",
				prop: { label: "Anggaran T2", name: "anggaran_t2", format: "currency" },
			},
			{ tag: "field", prop: { label: "Target T3", name: "target_t3" } },
			{
				tag: "field",
				prop: { label: "Anggaran T3", name: "anggaran_t3", format: "currency" },
			},
			{ tag: "field", prop: { label: "Target T4", name: "target_t4" } },
			{
				tag: "field",
				prop: { label: "Anggaran T4", name: "anggaran_t4", format: "currency" },
			},
			{ tag: "field", prop: { label: "Target T5", name: "target_t5" } },
			{
				tag: "field",
				prop: { label: "Anggaran T5", name: "anggaran_t5", format: "currency" },
			},
			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir", width: 16 } },
			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: `rows="2"`,
					width: 16,
				},
			},
		],
	},
};
/* ======================================================
   4️⃣ REFERENSI MODULES (SEMUA FLAT)
====================================================== */
/* ======================================================
   REFERENSI – URUSAN
====================================================== */

UIConfig.urusan = {
	validation: {
		kode: { required: true },
		kode_aset: { required: true },
		uraian: { required: true },
	},
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
		],
	},
};

/* ======================================================
   REFERENSI – BIDANG
====================================================== */

UIConfig.bidang = {
	validation: {
		parent_kode: { required: true },
		kode: { required: true },
		uraian: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Urusan",
					name: "parent_kode",
					source: "rekening_kegiatan",
					filter: { level: "urusan" },
					search: true,
				},
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
		],
	},
};

/* ======================================================
   REFERENSI – PROGRAM
====================================================== */

UIConfig.program = {
	validation: {
		parent_kode: { required: true },
		kode: { required: true },
		uraian: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Bidang",
					name: "parent_kode",
					source: "rekening_kegiatan",
					filter: { level: "bidang" },
					search: true,
				},
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
		],
	},
};

/* ======================================================
   REFERENSI – KEGIATAN
====================================================== */

UIConfig.kegiatan = {
	validation: {
		parent_kode: { required: true },
		kode: { required: true },
		uraian: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "parent_kode",
					source: "rekening_kegiatan",
					filter: { level: "program" },
					search: true,
				},
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
		],
	},
};

/* ======================================================
   REFERENSI – SUB KEGIATAN
====================================================== */

UIConfig.sub_kegiatan = {
	validation: {
		parent_kode: { required: true },
		kode: { required: true },
		uraian: { required: true },
		satuan: { required: true },
	},
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kegiatan",
					name: "parent_kode",
					source: "rekening_kegiatan",
					filter: {
						level: "kegiatan",
					},
					search: true,
				},
			},

			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },

			{
				tag: "fieldDropdown",
				prop: {
					label: "Satuan",
					name: "satuan",
					source: "satuan_teks",
					search: true,
				},
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Kinerja", name: "kinerja", atribut: `rows="2"` },
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Indikator", name: "indikator", atribut: `rows="2"` },
			},
		],
	},
};

/* ======================================================
   SUB KEGIATAN ANGGARAN
   (RENJA / RKA / DPA)
====================================================== */

UIConfig.sub_kegiatan_anggaran = {
	validation: {
		kd_sub_keg: { required: true },
		output: { required: true },
		satuan_output: { required: true },
		batas_anggaran: { required: true },
	},
	title: "Sub Kegiatan",
	table: "sub_kegiatan",

	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sub Kegiatan",
					name: "kd_sub_keg",
					source: "rekening_kegiatan",
					search: true,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Output Sub Kegiatan", name: "output", atribut: `rows="2"` },
			},
			{
				tag: "fieldDropdown",
				prop: { label: "Satuan Output", name: "satuan_output", source: "satuan_teks", search: true },
			},
			{
				tag: "field",
				prop: { label: "Batas/Pagu Anggaran", name: "batas_anggaran", type: "number", atribut: `min="0" step="0.01"` },
			},
		],
	},
};

UIConfig.akun = {
	validation: {
		kode: { required: true },
		uraian: { required: true },
	},
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="3"` } },
		],
	},
};

/* ======================================================
   SATUAN (ADD / EDIT)
====================================================== */

UIConfig.satuan = {
	layout: { columns: 1 },
	validation: {
		value: { required: true },
		uraian: { required: true },
	},
	form: {
		elements: [
			{ tag: "field", prop: { label: "Value", name: "value" } },

			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },

			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: `rows="2"`,
				},
			},
		],
	},
};

UIConfig.organisasi = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Alamat", name: "alamat" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],
	},
};

UIConfig.wilayah = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Status", name: "status" } },
		],
	},
};

UIConfig.peraturan = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{ tag: "field", prop: { label: "Judul", name: "judul" } },
			{ tag: "field", prop: { label: "Status", name: "status" } },
		],
	},
};
// ======================================================
// 🔥 TATA NASKAH VALIDATION
// ======================================================
UIConfig.trx_naskah_dinas = {
	validation: {
		nomor: { required: true },
		tanggal_surat: { required: true },
		perihal: { required: true },
		penandatangan: { required: true },
		jbt_pemberi_tgs: { required: true },
		pangkat_pemberi_tgs: { required: true },
		asn: { required: true },
	},
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{ tag: "field", prop: { label: "Perihal", name: "perihal" } },
			{ tag: "field", prop: { label: "Status", name: "workflow_status" } },
			{ tag: "field", prop: { label: "OPD", name: "kd_opd" } },
			{ tag: "field", prop: { label: "Tanggal Dibuat", name: "tgl_insert" } },
		],
	},
};
UIConfig.rekanan = {
	validation: {
		nama_perusahaan: { required: true },
		npwp: { required: true },
		alamat: { required: true },
		direktur: { required: true },
	},

	form: {
		elements: [
			// DATA PERUSAHAAN
			{ tag: "divider", prop: { label: "Data Perusahaan" } },

			{
				tag: "field",
				prop: { label: "Nama Perusahaan", name: "nama_perusahaan", width: 16 },
			},

			{ tag: "field", prop: { label: "NPWP", name: "npwp" } },
			{ tag: "field", prop: { label: "Email", name: "email" } },

			{
				tag: "fieldTextarea",
				prop: {
					label: "Alamat Perusahaan",
					name: "alamat",
					atribut: `rows="2"`,
				},
			},

			// DATA REKENING
			{ tag: "divider", prop: { label: "Data Rekening" } },

			{ tag: "field", prop: { label: "Nomor Rekening", name: "no_rekening" } },
			{ tag: "field", prop: { label: "Bank", name: "bank_rekening" } },

			{
				tag: "field",
				prop: {
					label: "Atas Nama Rekening",
					name: "atas_nama_rekening",
					width: 16,
				},
			},

			// DATA DIREKTUR
			{ tag: "divider", prop: { label: "Data Direktur" } },

			{ tag: "field", prop: { label: "Nama Direktur", name: "direktur" } },
			{ tag: "field", prop: { label: "Jabatan", name: "jabatan" } },
			{ tag: "field", prop: { label: "No KTP", name: "no_ktp" } },

			{
				tag: "fieldTextarea",
				prop: {
					label: "Alamat Direktur",
					name: "alamat_dir",
					atribut: `rows="2"`,
				},
			},

			// AKTA PENDIRIAN
			{ tag: "divider", prop: { label: "Akta Pendirian" } },

			{
				tag: "field",
				prop: { label: "No Akta Pendirian", name: "no_akta_pendirian" },
			},

			{
				tag: "field",
				prop: { label: "Notaris", name: "nama_notaris_pendirian" },
			},

			{
				tag: "field",
				prop: {
					label: "Alamat Notaris",
					name: "lokasi_notaris_pendirian",
					width: 16,
				},
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Akta Pendirian",
					name: "tgl_akta_pendirian",
					calendarType: "date",
				},
			},

			{ tag: "fieldFile", prop: { label: "Upload Dokumen", name: "file" } },

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},

			{ tag: "fieldCheckbox", prop: { label: "Non Aktif", name: "disable" } },
		],
	},
};
UIConfig.rekanan_akta = {
	layout: { columns: 2 },

	validation: {
		no_akta: { required: true },
		tgl_akta: { required: true },
	},

	form: {
		elements: [
			{
				tag: "field",
				prop: {
					name: "rekanan_id",
					atribut: 'type="hidden"',
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Jenis Akta",
					name: "jenis_akta",
					options: [
						{ value: "pendirian", text: "Pendirian" },
						{ value: "perubahan", text: "Perubahan" },
					],
				},
			},

			{
				tag: "field",
				prop: { label: "Nomor Akta", name: "no_akta" },
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Akta",
					name: "tgl_akta",
					calendarType: "date",
				},
			},

			{
				tag: "field",
				prop: { label: "Nama Notaris", name: "nama_notaris" },
			},

			{
				tag: "field",
				prop: { label: "Lokasi Notaris", name: "lokasi_notaris" },
			},
		],
	},
};
UIConfig.sumber_dana = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],
	},
};

UIConfig.mapping = {
	layout: { columns: 1 },
	validation: {
		master_biaya_id: { required: true },
		kd_akun: { required: true },
	},

	form: {
		elements: [
			/* =====================================
TIPE STANDAR BIAYA
===================================== */

			{
				tag: "field",
				prop: {
					label: "Tipe Standar Biaya",
					name: "tipe",
					readonly: true,
				},
			},

			/* =====================================
SEARCH MASTER BIAYA
===================================== */

			{
				tag: "search",
				prop: {
					label: "Cari Standar Biaya",
					name: "master_biaya_id",
					source: "master_biaya",
					labelField: "uraian_label",
				},
			},

			/* =====================================
LABEL HASIL SEARCH
===================================== */

			{
				tag: "field",
				prop: {
					label: "Uraian",
					name: "uraian_label",
					readonly: true,
				},
			},

			/* =====================================
AKUN BELANJA
===================================== */

			{
				tag: "lookupDropdown",
				prop: {
					label: "Akun Belanja",
					name: "kd_akun",
					source: "akun_neo",
				},
			},

		],
	},
};

UIConfig.aset = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
		],
	},
};
/* ======================================================
   STANDAR HARGA
====================================================== */

UIConfig.sbu = {
	layout: { columns: 1 },
	validation: {
		kode: { required: true },
		uraian: { required: true },
		satuan_id: { required: true },
		harga: { required: true },
	},
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kode Aset / Barang (kode dan uraian)",
					name: "kode_aset",
					source: "aset",
					search: true,
				},
			},
			{ tag: "field", prop: { label: "Kelompok Barang", name: "kelompok_barang" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{
				tag: "fieldDropdown",
				prop: { label: "Satuan", name: "satuan_id", source: "satuan", table: false },
			},
			{ tag: "field", prop: { label: "Satuan", name: "satuan", readonly: true } },
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga",
					format: "currency",
				},
			},
			{ tag: "field", prop: { label: "TKDN (%)", name: "tkdn", type: "number" } },
			{ tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan" } },
		],
	},
};

UIConfig.ssh = {
	layout: { columns: 1 },
	validation: { ...UIConfig.sbu.validation },
	form: { elements: [...UIConfig.sbu.form.elements] },
};

UIConfig.asb = {
	layout: { columns: 1 },
	validation: { ...UIConfig.sbu.validation },
	form: { elements: [...UIConfig.sbu.form.elements] },
};

UIConfig.hspk = {
	layout: { columns: 1 },
	validation: { ...UIConfig.sbu.validation },
	form: { elements: [...UIConfig.sbu.form.elements] },
};
UIConfig.asn = {
	layout: { columns: 1 },
	validation: { nama: { required: true }, nip: { required: true } },
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nama Lengkap", name: "nama" } },
			{ tag: "fieldAction", prop: { label: "NIP", name: "nip" } },
			{ tag: "field", prop: { label: "Gelar", name: "gelar" } },
			{ tag: "field", prop: { label: "Gelar Depan", name: "gelar_depan" } },

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

			{ tag: "field", prop: { label: "Jabatan", name: "jabatan" } },
			{ tag: "field", prop: { label: "Tempat Lahir", name: "t4_lahir" } },
			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Lahir",
					name: "tgl_lahir",
					calendarType: "date",
				},
			},

			{ tag: "field", prop: { label: "No KTP", name: "no_ktp" } },
			{ tag: "field", prop: { label: "NPWP", name: "npwp" } },
			{ tag: "field", prop: { label: "Alamat", name: "alamat" } },
			{ tag: "field", prop: { label: "Kontak Person", name: "kontak_person" } },
			{ tag: "field", prop: { label: "Email", name: "email" } },

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
			{ tag: "fieldCheckbox", prop: { label: "Non Aktif", name: "disable" } },
		],
	},
};

UIConfig.pppk = {
	layout: { columns: 1 }, validation: { nama: { required: true }, nip: { required: true } },
	form: { elements: [
		{ tag: "field", prop: { label: "Nama Lengkap", name: "nama" } },
		{ tag: "fieldAction", prop: { label: "Nomor Induk PPPK", name: "nip" } },
		{ tag: "field", prop: { label: "Jabatan", name: "jabatan" } },
		{ tag: "field", prop: { label: "Unit Kerja", name: "unit_kerja" } },
		{ tag: "field", prop: { label: "Status Kepegawaian", name: "status_kepeg" } },
		{ tag: "fieldHidden", prop: { name: "jenis_kepeg", default: "PPPK" } },
		{ tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan" } }
	] }
};

const pegawaiDropdown = { source: "asn", valueField: "id", textField: "nama", search: true };
UIConfig.riwayat_jabatan = { layout: { columns: 1 }, validation: { pegawai_id:{required:true},jabatan:{required:true},tmt:{required:true} }, form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Pegawai",name:"pegawai_id",...pegawaiDropdown}}, {tag:"field",prop:{label:"Nomor SK",name:"nomor_sk"}},
	{tag:"field",prop:{label:"Jabatan",name:"jabatan"}}, {tag:"field",prop:{label:"Unit Kerja",name:"unit_kerja"}},
	{tag:"fieldCalendar",prop:{label:"TMT",name:"tmt",calendarType:"date"}}, {tag:"fieldCalendar",prop:{label:"Tanggal Selesai",name:"tanggal_selesai",calendarType:"date"}},
	{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan"}}
]}};
UIConfig.riwayat_pangkat = { layout: { columns: 1 }, validation: { pegawai_id:{required:true},golongan:{required:true},tmt:{required:true} }, form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Pegawai",name:"pegawai_id",...pegawaiDropdown}}, {tag:"field",prop:{label:"Nomor SK",name:"nomor_sk"}},
	{tag:"field",prop:{label:"Golongan",name:"golongan"}}, {tag:"field",prop:{label:"Ruang",name:"ruang"}},
	{tag:"fieldCalendar",prop:{label:"TMT",name:"tmt",calendarType:"date"}}, {tag:"field",prop:{label:"Masa Kerja (tahun)",name:"masa_kerja_tahun",type:"number"}},
	{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan"}}
]}};
UIConfig.cuti = { layout: { columns: 1 }, validation: { pegawai_id:{required:true},jenis_cuti:{required:true},tanggal_mulai:{required:true},tanggal_selesai:{required:true} }, form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Pegawai",name:"pegawai_id",...pegawaiDropdown}}, {tag:"field",prop:{label:"Nomor Surat",name:"nomor_surat"}},
	{tag:"fieldDropdown",prop:{label:"Jenis Cuti",name:"jenis_cuti",options:[{value:"tahunan",text:"Cuti Tahunan"},{value:"sakit",text:"Cuti Sakit"},{value:"melahirkan",text:"Cuti Melahirkan"},{value:"alasan_penting",text:"Alasan Penting"}]}},
	{tag:"fieldCalendar",prop:{label:"Tanggal Mulai",name:"tanggal_mulai",calendarType:"date"}}, {tag:"fieldCalendar",prop:{label:"Tanggal Selesai",name:"tanggal_selesai",calendarType:"date"}},
	{tag:"field",prop:{label:"Jumlah Hari",name:"jumlah_hari",type:"number"}}, {tag:"fieldDropdown",prop:{label:"Status",name:"status",options:[{value:"diajukan",text:"Diajukan"},{value:"disetujui",text:"Disetujui"},{value:"ditolak",text:"Ditolak"},{value:"selesai",text:"Selesai"}]}},
	{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan"}}
]}};
UIConfig.sk_pegawai = { layout: { columns: 1 }, validation: { pegawai_id:{required:true},nomor_sk:{required:true},jenis_sk:{required:true} }, form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Pegawai",name:"pegawai_id",...pegawaiDropdown}}, {tag:"field",prop:{label:"Nomor SK",name:"nomor_sk"}},
	{tag:"fieldCalendar",prop:{label:"Tanggal SK",name:"tanggal_sk",calendarType:"date"}}, {tag:"field",prop:{label:"Jenis SK",name:"jenis_sk"}},
	{tag:"fieldTextarea",prop:{label:"Tentang",name:"tentang"}}, {tag:"fieldFile",prop:{label:"Dokumen SK (PDF)",name:"file",accept:".pdf"}},
	{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan"}}
]}};
UIConfig.pejabat_tahunan = {layout:{columns:1},validation:{jenis_pejabat:{required:true},pegawai_id:{required:true},nomor_sk:{required:true},berlaku_mulai:{required:true},berlaku_sampai:{required:true}},form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Jenis Pejabat",name:"jenis_pejabat",options:[{value:"PA_KPA",text:"Pengguna Anggaran / KPA"},{value:"PPK",text:"Pejabat Pembuat Komitmen (per Sub Kegiatan)"},{value:"PPTK",text:"PPTK (per Sub Kegiatan)"},{value:"PPK_SKPD",text:"Pejabat Penatausahaan Keuangan"},{value:"BENDAHARA",text:"Bendahara"},{value:"PEJABAT_PENGADAAN",text:"Pejabat Pengadaan"},{value:"PEJABAT_LAINNYA",text:"Pejabat Lainnya"}]}},
	{tag:"fieldDropdown",prop:{label:"Nama Pegawai",name:"pegawai_id",...pegawaiDropdown}},
	{tag:"field",prop:{label:"Nomor SK",name:"nomor_sk"}},{tag:"fieldCalendar",prop:{label:"Tanggal SK",name:"tanggal_sk",calendarType:"date"}},
	{tag:"fieldCalendar",prop:{label:"Berlaku Mulai",name:"berlaku_mulai",calendarType:"date"}},{tag:"fieldCalendar",prop:{label:"Berlaku Sampai",name:"berlaku_sampai",calendarType:"date"}},
	{tag:"fieldDropdown",prop:{label:"Sub Kegiatan (wajib untuk PPK/PPTK)",name:"kd_sub_keg",source:"rekening_kegiatan.sub_kegiatan",search:true}},
	{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan"}}
]}};

UIConfig.global_print = {
	layout: { columns: 2 },
	form: {
		elements: [
			{ tag: "fieldHidden", prop: { name: "id_row" } },

			{
				tag: "fieldDropdown",
				prop: {
					label: "Ukuran Kertas",
					name: "ukuran_kertas",
					options: [
						{ value: "A4", text: "A4" },
						{ value: "F4", text: "F4" },
						{ value: "Legal", text: "Legal" },
					],
				},
			},

			{
				tag: "field",
				prop: { label: "Ukuran Huruf (%)", name: "ukuran_huruf" },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Aktifkan Header", name: "header" },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Aktifkan Footer", name: "footer" },
			},
		],
	},
};
UIConfig.rpjmd_kabupaten={layout:{columns:1},validation:{nama_dokumen:{required:true},berlaku_mulai:{required:true}},form:{elements:[
	{tag:"field",prop:{label:"Nama Dokumen RPJMD",name:"nama_dokumen"}},{tag:"field",prop:{label:"Nomor Perda",name:"nomor_perda"}},
	{tag:"fieldCalendar",prop:{label:"Tanggal Perda",name:"tanggal_perda",calendarType:"date"}},{tag:"fieldCalendar",prop:{label:"Berlaku Mulai",name:"berlaku_mulai",calendarType:"date"}},{tag:"fieldCalendar",prop:{label:"Berlaku Sampai (default 5 tahun)",name:"berlaku_sampai",calendarType:"date"}},
	{tag:"fieldTextarea",prop:{label:"Visi",name:"visi"}},{tag:"fieldTextarea",prop:{label:"Misi",name:"misi"}},{tag:"fieldTextarea",prop:{label:"Sasaran",name:"sasaran"}},{tag:"fieldTextarea",prop:{label:"Indikator",name:"indikator"}},
	{tag:"fieldDropdown",prop:{label:"Status",name:"status",options:[{value:"draft",text:"Draft"},{value:"berlaku",text:"Berlaku"},{value:"berakhir",text:"Berakhir"}]}},{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan"}}
]}};
UIConfig.usulan_pembangunan={layout:{columns:1},validation:{jenis_usulan:{required:true},pengusul:{required:true},uraian:{required:true}},form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Sumber Usulan",name:"jenis_usulan",options:[{value:"musrenbang",text:"Musrenbang"},{value:"pokir_dprd",text:"Pokok Pikiran DPRD"},{value:"masyarakat",text:"Usulan Masyarakat"}]}},{tag:"field",prop:{label:"Pengusul",name:"pengusul"}},{tag:"field",prop:{label:"NIK/Nomor Identitas",name:"nik_no_identitas"}},
	{tag:"fieldTextarea",prop:{label:"Alamat",name:"alamat"}},{tag:"field",prop:{label:"Desa/Kelurahan",name:"desa_kelurahan"}},{tag:"field",prop:{label:"Kecamatan",name:"kecamatan"}},{tag:"fieldTextarea",prop:{label:"Uraian Usulan",name:"uraian"}},{tag:"field",prop:{label:"Volume",name:"volume",type:"number"}},{tag:"field",prop:{label:"Satuan",name:"satuan"}},{tag:"fieldTextarea",prop:{label:"Lokasi",name:"lokasi"}},{tag:"field",prop:{label:"Perkiraan Anggaran",name:"perkiraan_anggaran",type:"number"}},{tag:"field",prop:{label:"Prioritas",name:"prioritas",type:"number"}},
	{tag:"fieldDropdown",prop:{label:"Status",name:"status",options:[{value:"diusulkan",text:"Diusulkan"},{value:"diverifikasi",text:"Diverifikasi"},{value:"diterima",text:"Diterima"},{value:"ditolak",text:"Ditolak"},{value:"diakomodasi",text:"Diakomodasi"}]}},{tag:"fieldTextarea",prop:{label:"Catatan Verifikasi",name:"catatan_verifikasi"}}
]}};
UIConfig.evaluasi_renja={layout:{columns:1},validation:{triwulan:{required:true},kd_sub_keg:{required:true},indikator:{required:true}},form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Triwulan",name:"triwulan",options:[{value:"1",text:"Triwulan I"},{value:"2",text:"Triwulan II"},{value:"3",text:"Triwulan III"},{value:"4",text:"Triwulan IV"}]}},{tag:"fieldDropdown",prop:{label:"Sub Kegiatan",name:"kd_sub_keg",source:"rekening_kegiatan.sub_kegiatan",search:true}},{tag:"fieldTextarea",prop:{label:"Indikator Kinerja",name:"indikator"}},{tag:"field",prop:{label:"Satuan",name:"satuan"}},
	{tag:"field",prop:{label:"Target Tahunan",name:"target_tahunan",type:"number"}},{tag:"field",prop:{label:"Target Triwulan",name:"target_triwulan",type:"number"}},{tag:"field",prop:{label:"Realisasi Triwulan",name:"realisasi_triwulan",type:"number"}},{tag:"field",prop:{label:"Realisasi Kumulatif",name:"realisasi_kumulatif",type:"number"}},{tag:"field",prop:{label:"Pagu Anggaran",name:"pagu_anggaran",type:"number"}},{tag:"field",prop:{label:"Realisasi Anggaran Triwulan",name:"realisasi_anggaran_triwulan",type:"number"}},{tag:"field",prop:{label:"Realisasi Anggaran Kumulatif",name:"realisasi_anggaran_kumulatif",type:"number"}},
	{tag:"fieldTextarea",prop:{label:"Faktor Pendorong",name:"faktor_pendorong"}},{tag:"fieldTextarea",prop:{label:"Faktor Penghambat",name:"faktor_penghambat"}},{tag:"fieldTextarea",prop:{label:"Tindak Lanjut",name:"tindak_lanjut"}},{tag:"fieldDropdown",prop:{label:"Status",name:"status",options:[{value:"draft",text:"Draft"},{value:"dikirim",text:"Dikirim ke Bappeda"},{value:"diverifikasi",text:"Diverifikasi"},{value:"perbaikan",text:"Perlu Perbaikan"}]}}
]}};
UIConfig.__importFactory = function (tbl, templateFile) {
	const templateHtml = templateFile
		? `<a href="/assets/template_import/${templateFile}" target="_blank">
                Download Template ${tbl}
           </a>`
		: `<a href="/template_import?tabel=${encodeURIComponent(tbl)}" target="_blank">Download Template ${tbl}</a>`;

	return {
		layout: { columns: 1 },
		form: {
			elements: [
				{
					tag: "alert",
					prop: {
						variant: "info",
						icon: "info circle",
						title: "Petunjuk Import Data",
						message: `
                            Gunakan template resmi berikut:<br>
                            ${templateHtml}<br><br>
                            Pastikan:
                            <ul>
                                <li>Tidak mengubah nama kolom</li>
                                <li>Tidak ada merge cell</li>
                                <li>Format file adalah .xlsx</li>
                            </ul>
                        `,
						dismissible: true,
						elevated: true,
					},
				},

				{
					tag: "fieldFile",
					prop: {
						label: "File Excel (.xlsx)",
						name: "file",
						accept: ".xlsx",
					},
				},

				{
					tag: "fieldDropdown",
					prop: {
						label: "Jumlah Header",
						name: "jml_header",
						default: "1",
						options: Array.from({ length: 8 }, (_, i) => ({
							value: String(i),
							text: String(i),
						})),
					},
				},
			],
		},
	};
};
UIConfig.dokumen = {
	validation: {
		uraian: { required: true },
	},
	form: {
		elements: [
			{ tag: "textarea", prop: { name: "uraian", label: "Uraian" } },

			{ tag: "input", prop: { name: "file", type: "file", label: "File" } },

			{ tag: "toggle", prop: { name: "disable", label: "Disable" } },

			{ tag: "textarea", prop: { name: "keterangan", label: "Keterangan" } },
		],
	},
};
UIConfig.satuan.import = UIConfig.__importFactory("satuan", "9. Satuan 1 Header.xlsx");
UIConfig.rekanan.import = UIConfig.__importFactory("rekanan", "10. rekanan.xlsx");
UIConfig.rekening_kegiatan = UIConfig.rekening_kegiatan || {
	form: { elements: [] },
};
UIConfig.rekening_kegiatan.import = UIConfig.__importFactory("rekening_kegiatan", "11. Referensi Hierarki.xlsx");
UIConfig.sbu.import = UIConfig.__importFactory("sbu", null);
UIConfig.ssh.import = UIConfig.__importFactory("ssh", null);
UIConfig.hspk.import = UIConfig.__importFactory("hspk", null);
UIConfig.asb.import = UIConfig.__importFactory("asb", null);
UIConfig.aset.import = UIConfig.__importFactory("aset_neo", null); //DEFAULT GLOBAL TANPA TEMPLATE

const phase3Flags = [
	{ tag: "fieldCheckbox", prop: { label: "Kunci", name: "kunci" } },
	{ tag: "fieldCheckbox", prop: { label: "Setujui", name: "setujui" } },
	{ tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` } },
];
UIConfig.rkpd = {
	validation: { kd_sub_keg: { required: true }, target: { required: true }, pagu: { required: true } },
	form: { elements: [
		{ tag: "fieldDropdown", prop: { label: "Sub Kegiatan", name: "kd_sub_keg", source: "sub_kegiatan" } },
		{ tag: "fieldTextarea", prop: { label: "Indikator", name: "indikator", atribut: `rows="2"` } },
		{ tag: "input", prop: { label: "Target", name: "target", type: "number" } },
		{ tag: "input", prop: { label: "Pagu", name: "pagu", type: "number" } },
		{ tag: "fieldDropdown", prop: { label: "Sumber Dana", name: "sumber_dana_id", source: "sumber_dana" } },
		{ tag: "input", prop: { label: "Lokasi", name: "lokasi" } },
		{ tag: "input", prop: { label: "Kelompok Sasaran", name: "kelompok_sasaran" } },
		...phase3Flags,
	] }
};
UIConfig.rkpd_p = UIConfig.rkpd;
const phase3BudgetForm = { layout:{columns:1}, validation:{kd_sub_keg:{required:true},uraian:{required:true},jumlah:{required:true,number:true}}, form: { elements: [
	{ tag: "input", prop: { label: "Kode Sub Kegiatan", name: "kd_sub_keg" } },
	{ tag: "fieldDropdown", prop: { label: "Rekening / Akun", name: "kd_akun", source: "akun", search: true } },
	{ tag: "fieldDropdown", prop: { label: "Objek Belanja", name: "objek_belanja", options:[{value:"belanja_operasi",text:"Belanja Operasi"},{value:"belanja_modal",text:"Belanja Modal"},{value:"belanja_tidak_terduga",text:"Belanja Tidak Terduga"}] } },
	{ tag: "fieldDropdown", prop: { label: "Pengelompokan Belanja / Paket Pekerjaan", name: "jenis_kelompok", options:[{value:"pemaketan",text:"Pemaketan Kerja"},{value:"non_paket",text:"Non Paket"}] } },
	{ tag: "input", prop: { label: "Uraian Paket Pekerjaan", name: "kelompok" } },
	{ tag: "fieldTextarea", prop: { label: "Uraian Rincian Belanja", name: "uraian", atribut: `rows="2"` } },
	{ tag: "fieldDropdown", prop: { label: "Jenis Standar Harga", name: "jenis_standar_harga", options:[{value:"SSH",text:"SSH"},{value:"HSPK",text:"HSPK"},{value:"ASB",text:"ASB"},{value:"SBU",text:"SBU"}] } },
	{ tag: "fieldDropdown", prop: { label: "Komponen Standar Harga", name: "id_standar_harga", source:"ssh", search:true } },
	{ tag: "input", prop: { label: "Komponen", name: "komponen", atribut:"readonly" } },
	{ tag: "fieldTextarea", prop: { label: "Spesifikasi Komponen", name: "spesifikasi", atribut:`rows="2"` } },
	{ tag: "input", prop: { label: "TKDN (%)", name: "tkdn", type:"number" } },
	{ tag: "fieldDropdown", prop: { label: "Satuan", name: "sat_5", source:"satuan_teks", search:true } },
	{ tag: "input", prop: { label: "Harga Satuan", name: "harga_satuan", type: "number" } },
	{ tag: "fieldDropdown", prop: { label: "Keterangan Belanja", name: "keterangan", options:[{value:"Biaya Utama",text:"Biaya Utama"},{value:"Biaya Penunjang Kegiatan",text:"Biaya Penunjang Kegiatan"}] } },
	{ tag: "fieldCheckbox", prop: { label: "Tambahkan Pajak", name: "pajak" } },
	{ tag: "input", prop: { label: "Koefisien 1", name: "vol_1", type:"number" } },
	{ tag: "fieldDropdown", prop: { label: "Satuan Koefisien 1", name: "sat_1", source:"satuan_teks", search:true } },
	{ tag: "input", prop: { label: "Koefisien 2", name: "vol_2", type:"number" } },
	{ tag: "fieldDropdown", prop: { label: "Satuan Koefisien 2", name: "sat_2", source:"satuan_teks", search:true } },
	{ tag: "input", prop: { label: "Koefisien 3", name: "vol_3", type:"number" } },
	{ tag: "fieldDropdown", prop: { label: "Satuan Koefisien 3", name: "sat_3", source:"satuan_teks", search:true } },
	{ tag: "input", prop: { label: "Koefisien 4", name: "vol_4", type:"number" } },
	{ tag: "fieldDropdown", prop: { label: "Satuan Koefisien 4", name: "sat_4", source:"satuan_teks", search:true } },
	{ tag: "input", prop: { label: "Volume Total", name: "volume", type: "number" } },
	{ tag: "input", prop: { label: "Koefisien (Keterangan Jumlah)", name: "koefisien_keterangan", atribut:"readonly" } },
	{ tag: "input", prop: { label: "Total Belanja", name: "jumlah", type: "number" } },
	{ tag: "fieldDropdown", prop: { label: "Sumber Dana", name: "sumber_dana_id", source: "sumber_dana" } },
	...phase3Flags,
] } };
["renja", "rka", "dpa", "renja_p", "rka_p", "dppa"].forEach((key) => { UIConfig[key] = phase3BudgetForm; });
/*
|--------------------------------------------------------------------------
| SUB KEGIATAN - ADD
|--------------------------------------------------------------------------
| Digunakan oleh:
| RENJA
| RENJA PERUBAHAN
| RKA
| RKA PERUBAHAN
| DPA
| DPPA
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| SUB KEGIATAN
|--------------------------------------------------------------------------
| Digunakan untuk:
| add
| edit
|
| Dipanggil oleh:
| data-ui="open-form"
| data-action="add"
| data-action="edit"
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| ANGGARAN - RINCIAN BELANJA
|--------------------------------------------------------------------------
| Digunakan oleh:
| renja_neo
| renja_perubahan_neo
| rka_neo
| rka_perubahan_neo
| dpa_neo
| dppa_neo
|--------------------------------------------------------------------------
*/

UIConfig.anggaran = {
	title: "Rincian Belanja",

	fields: [
		/*
        |--------------------------------------------------------------------------
        | JENIS STANDAR HARGA
        |--------------------------------------------------------------------------
        */

		{
			name: "jenis_standar_harga",
			label: "Jenis Standar Harga",
			type: "dropdown",
			options: [
				{ value: "ssh", text: "SSH" },
				{ value: "sbu", text: "SBU" },
				{ value: "hspk", text: "HSPK" },
				{ value: "asb", text: "ASB" },
			],
		},

		/*
        |--------------------------------------------------------------------------
        | STANDAR HARGA
        |--------------------------------------------------------------------------
        */

		{
			name: "id_standar_harga",
			label: "Standar Harga",
			type: "dropdown_search",
			source: "standar_harga",
			value: "id",
			text: "komponen",
		},

		/*
        |--------------------------------------------------------------------------
        | KOMPONEN
        |--------------------------------------------------------------------------
        */

		{
			name: "komponen",
			label: "Komponen",
			type: "text",
		},

		/*
        |--------------------------------------------------------------------------
        | SPESIFIKASI
        |--------------------------------------------------------------------------
        */

		{
			name: "spesifikasi",
			label: "Spesifikasi",
			type: "text",
		},

		/*
        |--------------------------------------------------------------------------
        | TKDN
        |--------------------------------------------------------------------------
        */

		{
			name: "tkdn",
			label: "TKDN",
			type: "number",
		},

		/*
        |--------------------------------------------------------------------------
        | PAJAK
        |--------------------------------------------------------------------------
        */

		{
			name: "pajak",
			label: "Pajak",
			type: "number",
		},

		/*
        |--------------------------------------------------------------------------
        | HARGA SATUAN
        |--------------------------------------------------------------------------
        */

		{
			name: "harga_satuan",
			label: "Harga Satuan",
			type: "number",
		},

		/*
        |--------------------------------------------------------------------------
        | VOLUME
        |--------------------------------------------------------------------------
        */

		{
			name: "volume",
			label: "Volume",
			type: "number",
		},

		/*
        |--------------------------------------------------------------------------
        | SATUAN
        |--------------------------------------------------------------------------
        */

		{
			name: "sat_1",
			label: "Satuan",
			type: "text",
		},

		/*
        |--------------------------------------------------------------------------
        | JUMLAH
        |--------------------------------------------------------------------------
        */

		{
			name: "jumlah",
			label: "Jumlah",
			type: "number",
			readonly: true,
		},
	],
};
// ======================================================
// 🔥 GENERIC AKUN MAP CONFIG FACTORY
// ======================================================

UIConfig.__akunMapFactory = function (entityName) {
	return {
		layout: { columns: 1 },

		form: {
			elements: [
				{
					tag: "fieldDropdown",
					prop: {
						label: entityName.toUpperCase(),
						name: entityName + "_id",
					},
				},

				{
					tag: "field",
					prop: {
						label: "Kode Akun",
						name: "kd_akun",
					},
				},

				{
					tag: "field",
					prop: {
						label: "Wilayah",
						name: "kd_wilayah",
					},
				},

				{
					tag: "field",
					prop: {
						label: "Peraturan",
						name: "peraturan_id",
					},
				},
			],
		},
	};
};
UIConfig.kontrak = {
	validation: { rekanan_id:{required:true},nomor_spk:{required:true},nomor_spmk:{required:true},nomor_kontrak:{required:true} },
	form: {
		elements: [
			{ tag:"fieldTextarea", prop:{label:"Nama/Ruang Lingkup Pekerjaan",name:"uraian_kontrak",atribut:`rows="2"`} },
			{ tag:"input", prop:{label:"Total Pagu Terpilih (otomatis dari rincian)",name:"total_anggaran",type:"number",atribut:"readonly"} },
			{ tag:"input", prop:{label:"Nilai Kontrak (otomatis dari rincian)",name:"nilai_kontrak",type:"number",atribut:"readonly"} },
			{ tag:"input", prop:{label:"Nomor SPK",name:"nomor_spk"} },
			{ tag:"fieldCalendar", prop:{label:"Tanggal SPK",name:"tanggal_spk",calendarType:"date"} },
			{ tag:"input", prop:{label:"Nomor Kontrak",name:"nomor_kontrak"} },
			{ tag:"fieldCalendar", prop:{label:"Tanggal Kontrak",name:"tanggal_kontrak",calendarType:"date"} },
			{ tag:"input", prop:{label:"Nomor SPMK",name:"nomor_spmk"} },
			{ tag:"fieldCalendar", prop:{label:"Tanggal SPMK",name:"tanggal_spmk",calendarType:"date"} },
			{ tag:"fieldCalendar", prop:{label:"Tanggal Mulai",name:"tanggal_mulai",calendarType:"date"} },
			{ tag:"fieldCalendar", prop:{label:"Tanggal Selesai",name:"tanggal_selesai",calendarType:"date"} },
			{ tag:"input", prop:{label:"Waktu Pelaksanaan (hari)",name:"waktu_pelaksanaan",type:"number"} },
			{ tag:"fieldDropdown", prop:{label:"PPK aktif",name:"ppk_id",source:"pejabat_ppk",search:true} },
			{ tag:"fieldDropdown", prop:{label:"PPTK aktif (jika ada)",name:"pptk_id",source:"pejabat_pptk",search:true} },
			{ tag:"fieldDropdown", prop:{label:"Penyedia dari Referensi",name:"rekanan_id",source:"rekanan",search:true} },
			{ tag:"input", prop:{label:"Status Kontrak",name:"status_kontrak"} },
			{ tag:"fieldCheckbox", prop:{label:"Setujui",name:"setujui"} },
			{ tag:"fieldTextarea", prop:{label:"Keterangan",name:"keterangan",atribut:`rows="2"`} },
		],
	},
};
UIConfig.kontrak_dpa = UIConfig.kontrak;
UIConfig.kontrak_dppa = UIConfig.kontrak;
UIConfig.realisasi = { validation:{kontrak_id:{required:true},tanggal:{required:true},jumlah:{required:true,number:true},progress_fisik:{required:true,number:true}}, form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Kontrak",name:"kontrak_id",source:"kontrak",search:true}},
	{tag:"fieldDropdown",prop:{label:"Uraian RAB",name:"rab_id",source:"rab_kontrak",search:true}},
	{tag:"input",prop:{label:"ID Paket",name:"id_paket",type:"number"}},
	{tag:"input",prop:{label:"ID Uraian Paket",name:"id_uraian_paket",type:"number"}},
	{tag:"input",prop:{label:"ID Dokumen Anggaran",name:"id_dok_anggaran",type:"number"}},
	{tag:"input",prop:{label:"Tahap DPA/DPPA",name:"dok"}},
	{tag:"input",prop:{label:"Kode Sub Kegiatan",name:"kd_sub_keg"}},
	{tag:"input",prop:{label:"Kode Akun",name:"kd_akun"}},
	{tag:"input",prop:{label:"Periode/Bulan",name:"periode",type:"number"}},
	{tag:"fieldCalendar",prop:{label:"Tanggal Realisasi",name:"tanggal",calendarType:"date"}},
	{tag:"input",prop:{label:"Volume",name:"vol",type:"number"}},
	{tag:"input",prop:{label:"Realisasi Keuangan",name:"jumlah",type:"number"}},
	{tag:"input",prop:{label:"Progress Fisik (%)",name:"progress_fisik",type:"number"}},
	{tag:"input",prop:{label:"Progress Keuangan (%)",name:"progress_keuangan",type:"number"}},
	{tag:"fieldTextarea",prop:{label:"Uraian Progress",name:"uraian_progress",atribut:`rows="2"`}},
	{tag:"input",prop:{label:"Path Dokumen Bukti",name:"file"}},
	{tag:"input",prop:{label:"Nomor Bukti",name:"nomor_bukti"}},
	{tag:"fieldTextarea",prop:{label:"Keterangan Paket",name:"ket_paket",atribut:`rows="2"`}},
	{tag:"fieldTextarea",prop:{label:"Uraian Realisasi",name:"ket_uraian_paket",atribut:`rows="2"`}},
	{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan",atribut:`rows="2"`}},
]}};
UIConfig.halaman_berita = {
	form: {
		elements: [
			{
				tag: "field",
				prop: {
					label: "Judul",
					name: "judul",
				},
			},

			{
				tag: "field",
				prop: {
					label: "Slug",
					name: "slug",
				},
			},
			{tag:"fieldDropdown",prop:{label:"Tampil pada halaman",name:"jenis_halaman",options:[{value:"berita",text:"Berita"},{value:"data_teknis",text:"Data Teknis"},{value:"organisasi",text:"Organisasi"},{value:"pelayanan",text:"Pelayanan"}]}},
			{tag:"fieldDropdown",prop:{label:"Status Publikasi",name:"aktif",options:[{value:1,text:"Aktif - tampil ke publik"},{value:0,text:"Nonaktif - disembunyikan"}]}},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Konten",
					name: "konten",
					atribut: `rows="5"`,
				},
			},

			{
				tag: "field",
				prop: {
					label: "Gambar (URL)",
					name: "gambar",
				},
			},

			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: `rows="3"`,
				},
			},
		],
	},
};
UIConfig.absensi={validation:{pegawai_id:{required:true},tanggal:{required:true},status:{required:true}},form:{elements:[
	{tag:"fieldDropdown",prop:{label:"Pegawai",name:"pegawai_id",source:"pegawai",search:true}},
	{tag:"fieldCalendar",prop:{label:"Tanggal",name:"tanggal",calendarType:"date"}},
	{tag:"input",prop:{label:"Jam Masuk",name:"jam_masuk",type:"time"}},{tag:"input",prop:{label:"Jam Pulang",name:"jam_pulang",type:"time"}},
	{tag:"fieldDropdown",prop:{label:"Status",name:"status",options:["HADIR","DINAS_LUAR","IZIN","SAKIT","CUTI","ALPA","WFH"].map(x=>({value:x,text:x.replace('_',' ')}))}},
	{tag:"fieldTextarea",prop:{label:"Keterangan",name:"keterangan",atribut:'rows="3"'}}
]}};
UIConfig.penugasan_subkegiatan={form:{elements:[
	{tag:"input",prop:{label:"ID User",name:"user_id",type:"number"}},{tag:"input",prop:{label:"Kode Sub Kegiatan",name:"kd_sub_keg"}},
	{tag:"fieldDropdown",prop:{label:"Peran",name:"peran",options:["KEPALA_OPD","PA_KPA","PPK","PPTK","PPK_SKPD","BENDAHARA","PEJABAT_PENGADAAN","STAF","VIEWER"].map(x=>({value:x,text:x.replaceAll('_',' ')}))}},
	{tag:"fieldCheckbox",prop:{label:"Dapat melihat",name:"dapat_lihat"}},{tag:"fieldCheckbox",prop:{label:"Dapat input",name:"dapat_input"}},{tag:"fieldCheckbox",prop:{label:"Dapat menyetujui",name:"dapat_setujui"}},{tag:"fieldCheckbox",prop:{label:"Dapat menghapus",name:"dapat_hapus"}},
	{tag:"fieldCalendar",prop:{label:"Berlaku Mulai",name:"berlaku_mulai",calendarType:"date"}},{tag:"fieldCalendar",prop:{label:"Berlaku Sampai",name:"berlaku_sampai",calendarType:"date"}}
]}};
UIConfig.kop_surat={form:{elements:[{tag:"input",prop:{label:"Nama Pemerintah",name:"nama_pemerintah"}},{tag:"input",prop:{label:"Nama OPD",name:"nama_opd"}},{tag:"fieldTextarea",prop:{label:"Alamat",name:"alamat",atribut:'rows="2"'}},{tag:"input",prop:{label:"Telepon",name:"telepon"}},{tag:"input",prop:{label:"Email",name:"email"}},{tag:"input",prop:{label:"Website",name:"website"}},{tag:"input",prop:{label:"Path Logo Kiri",name:"logo_kiri"}},{tag:"input",prop:{label:"Path/Gambar Kop Penuh",name:"gambar_kop"}},{tag:"fieldCheckbox",prop:{label:"Gunakan gambar kop penuh",name:"gunakan_gambar_kop"}},{tag:"fieldCheckbox",prop:{label:"Aktif",name:"aktif"}}]}};
[
	"urusan","bidang","program","kegiatan","sub_kegiatan","satuan","rekening_kegiatan","sbu","ssh","hspk","asb","rkpd","renja","rka","dpa","rkpd_p","renja_p","rka_p","dppa","kontrak","realisasi","asn","pppk","riwayat_jabatan","riwayat_pangkat","cuti","sk_pegawai","pejabat_tahunan","absensi"
].forEach(tbl=>{if(UIConfig[tbl])UIConfig[tbl].import=UIConfig.__importFactory(tbl,null);});

UIConfig.sbu_akun_map = UIConfig.__akunMapFactory("sbu");
UIConfig.ssh_akun_map = UIConfig.__akunMapFactory("ssh");
UIConfig.asb_akun_map = UIConfig.__akunMapFactory("asb");
UIConfig.hspk_akun_map = UIConfig.__akunMapFactory("hspk");

///EKSEKUSI
window.UIConfig = UIConfig;
