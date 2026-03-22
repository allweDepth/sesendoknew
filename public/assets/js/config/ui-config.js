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
				},
			},
			{
				tag: "field",
				prop: { label: "Master Sub Kegiatan", name: "master_sub_kegiatan_id" },
			},
			{ tag: "field", prop: { label: "Lokasi", name: "lokasi" } },
			{
				tag: "field",
				prop: { label: "Kelompok Sasaran", name: "kelompok_sasaran" },
			},
			{ tag: "field", prop: { label: "Baseline", name: "baseline" } },
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
			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
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
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Urusan",
					name: "kode_urusan",
					source: "rekening_kegiatan",
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
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Bidang",
					name: "kode_bidang",
					source: "rekening_kegiatan",
					parent: "kode_urusan",
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
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "kode_program",
					source: "rekening_kegiatan",
					parent: "kode_program",
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
	form: {
		elements: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kegiatan",
					name: "kode_kegiatan",
					source: "rekening_kegiatan",
					parent: "kode_kegiatan",
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
					source: "satuan",
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
		],
	},
};

UIConfig.akun = {
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

	form: {
		elements: [
			{ tag: "field", prop: { label: "Value", name: "value" } },

			{ tag: "field", prop: { label: "Item", name: "item" } },

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

			// TAMBAHAN
			{
				tag: "fieldTextarea",
				prop: { label: "Data Lain", name: "data_lain", atribut: `rows="2"` },
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

			/* =====================================
KETERANGAN
===================================== */

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
	form: {
		elements: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Barang", name: "uraian_barang" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{
				tag: "fieldDropdown",
				prop: { label: "Satuan", name: "satuan", source: "satuan" },
			},
			{
				tag: "field",
				prop: {
					label: "Harga Satuan",
					name: "harga_satuan",
					format: "currency",
				},
			},
		],
	},
};

UIConfig.ssh = {
	layout: { columns: 1 },
	form: { elements: [...UIConfig.sbu.form.elements] },
};

UIConfig.asb = {
	layout: { columns: 1 },
	form: { elements: [...UIConfig.sbu.form.elements] },
};

UIConfig.hspk = {
	layout: { columns: 1 },
	form: { elements: [...UIConfig.sbu.form.elements] },
};
UIConfig.asn = {
	layout: { columns: 1 },
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
UIConfig.trx_naskah_dinas = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{ tag: "field", prop: { label: "Status", name: "workflow_status" } },
			{ tag: "field", prop: { label: "OPD", name: "kd_opd" } },
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
			{ tag: "field", prop: { label: "Tanggal Dibuat", name: "tgl_insert" } },
		],
	},
};

UIConfig.sk = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nomor Surat", name: "nomor" } },
			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Surat",
					name: "tgl_surat_dibuat",
					calendarType: "date",
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Tentang", name: "tentang", atribut: `rows="2"` },
			},
			{
				tag: "fieldDropdown",
				prop: { label: "Pemberi Tugas", name: "pemberi_tgs", source: "asn" },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},
};

UIConfig.surat_internal = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},
			{ tag: "field", prop: { label: "Kepada", name: "kepada" } },
			{
				tag: "fieldTextarea",
				prop: { label: "Perihal", name: "perihal", atribut: `rows="2"` },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Isi Surat", name: "isi", atribut: `rows="6"` },
			},
		],
	},
};

UIConfig.surat_bebas = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Isi Surat", name: "isi", atribut: `rows="8"` },
			},
		],
	},
};

UIConfig.telaah_staf = {
	form: {
		elements: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Permasalahan",
					name: "permasalahan",
					atribut: `rows="3"`,
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Analisis", name: "analisis", atribut: `rows="5"` },
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Rekomendasi",
					name: "rekomendasi",
					atribut: `rows="3"`,
				},
			},
		],
	},
};
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
UIConfig.__importFactory = function (tbl, templateFile) {
	const templateHtml = templateFile
		? `<a href="/assets/template_import/${templateFile}" target="_blank">
                Download Template ${tbl}
           </a>`
		: `<span class="red">Template tidak tersedia</span>`;

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
UIConfig.satuan.import = UIConfig.__importFactory("satuan", "9. Satuan 1 Header.xlsx");
UIConfig.rekening_kegiatan = UIConfig.rekening_kegiatan || {
	form: { elements: [] },
};
UIConfig.rekening_kegiatan.import = UIConfig.__importFactory("rekening_kegiatan", "9. Satuan 1 Header.xlsx");
UIConfig.sbu.import = UIConfig.__importFactory("sbu_neo", "17. sbu 2024.xlsx");

UIConfig.ssh.import = UIConfig.__importFactory("ssh_neo", "12. ssh 2024.xlsx");
UIConfig.aset.import = UIConfig.__importFactory("aset_neo", null); //DEFAULT GLOBAL TANPA TEMPLATE
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
	form: {
		elements: [
			/* SUB KEGIATAN */
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sub Kegiatan",
					name: "kd_sub_keg",
					source: "group_sub_kegiatan",
					search: true,
				},
			},
			{
				tag: "field",
				prop: {
					label: "Tahap",
					name: "tahap",
					readonly: true,
				},
			},

			{
				tag: "field",
				prop: {
					label: "Pagu",
					name: "total_anggaran",
					readonly: true,
				},
			},

			/* RINCIAN */
			{
				tag: "search",
				prop: {
					label: "Rincian",
					name: "anggaran_id",
					source: "anggaran",
					labelField: "uraian",
				},
			},

			/* KONTRAK */
			{
				tag: "field",
				prop: {
					label: "Nilai Kontrak",
					name: "nilai_kontrak",
				},
			},

			{
				tag: "field",
				prop: {
					label: "Nomor Kontrak",
					name: "nomor_kontrak",
				},
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Kontrak",
					name: "tanggal_kontrak",
					calendarType: "date",
				},
			},

			/* PIHAK */
			{
				tag: "field",
				prop: {
					label: "PPK",
					name: "nama_ppk",
					table: false,
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Penyedia",
					name: "nama_penyedia",
					source: "rekanan",
					search: true,
				},
			},

			/* STATUS */
			{
				tag: "field",
				prop: {
					label: "Status",
					name: "status_kontrak",
					table: false,
				},
			},
		],
	},
};
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

			{
				tag: "fieldTextarea",
				prop: {
					label: "Konten",
					name: "konten",
					rows: 10,
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
					rows: 2,
				},
			},
		],
	},
};
UIConfig.sbu_akun_map = UIConfig.__akunMapFactory("sbu");
UIConfig.ssh_akun_map = UIConfig.__akunMapFactory("ssh");
UIConfig.asb_akun_map = UIConfig.__akunMapFactory("asb");
UIConfig.hspk_akun_map = UIConfig.__akunMapFactory("hspk");

///EKSEKUSI
window.UIConfig = UIConfig;
