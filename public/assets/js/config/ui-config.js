// ======================================================
// 🔥 UI CONFIG (FINAL CLEAN VERSION) @note UIConfig
// ------------------------------------------------------
// - Dynamic dropdown → pakai prop.source
// - Static dropdown → pakai prop.options
// - Atribut HTML → pakai prop.atribut
// - Tidak ada data-server lagi
// ======================================================

const UIConfig = {
	/* ======================================================
       PENGATURAN
    ====================================================== */
	pengaturan: {
		form: {
			elements: [
				// ========================
				// IDENTITAS
				// ========================

				{
					tag: "input",
					prop: {
						label: "Tahun Anggaran",
						name: "tahun",
						type: "number",
					},
				},

				{
					tag: "input",
					prop: {
						label: "Tahun Renstra",
						name: "tahun_renstra",
						type: "number",
					},
				},

				// ========================
				// RANGE PERIODE
				// ========================

				{
					tag: "rangeCalendar",
					prop: {
						label: "Renja",
						name: "renja",
					},
				},

				{
					tag: "rangeCalendar",
					prop: {
						label: "DPA",
						name: "dpa",
					},
				},

				{
					tag: "rangeCalendar",
					prop: {
						label: "RKPD",
						name: "rkpd",
					},
				},

				// ========================
				// KONTROL
				// ========================

				{
					tag: "toggle",
					prop: {
						label: "Disable",
						name: "disable",
					},
				},

				{
					tag: "toggle",
					prop: {
						label: "Kunci Global",
						name: "kunci",
					},
				},

				{
					tag: "toggle",
					prop: {
						label: "Setujui Global",
						name: "setujui",
					},
				},

				{
					tag: "textarea",
					prop: {
						label: "Keterangan",
						name: "keterangan",
					},
				},
			],
		},
		
	},

	/* ======================================================
       RENSTRA
    ====================================================== */
	renstra: {
		/* ==============================
       1. RENSTRA
    ============================== */
		renstra_neo: [
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
			{
				tag: "fieldCheckbox",
				prop: { label: "Status", name: "status" },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Kunci", name: "kunci" },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Setujui", name: "setujui" },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],

		/* ==============================
       2. MISI
    ============================== */
		misi_renstra_neo: [
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

		/* ==============================
       3. TUJUAN
    ============================== */
		tujuan_renstra_neo: [
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

		/* ==============================
       4. SASARAN
    ============================== */
		sasaran_renstra_neo: [
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

		/* ==============================
       5. INDIKATOR SASARAN
    ============================== */
		indikator_sasaran_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sasaran",
					name: "sasaran_id",
					source: "sasaran_renstra_neo",
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

		/* ==============================
       6. PROGRAM
    ============================== */
		program_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Sasaran",
					name: "sasaran_id",
					source: "sasaran_renstra_neo",
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program (Permendagri)",
					name: "kode_program",
					source: "program",
					searchable: true,
					dropdownClass: "search selection scrolling",
				},
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: `rows="2"`,
				},
			},
		],

		/* ==============================
       7. INDIKATOR PROGRAM
    ============================== */
		indikator_program_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "program_id",
					source: "program_renstra_neo",
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

		/* ==============================
       8. KEGIATAN
    ============================== */
		kegiatan_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "program_id",
					source: "program_renstra_neo",
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

		/* ==============================
       9. SUB KEGIATAN
    ============================== */
		sub_kegiatan_renstra_neo: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kegiatan",
					name: "kegiatan_renstra_id",
					source: "kegiatan_renstra_neo",
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
			{ tag: "field", prop: { label: "Anggaran T1", name: "anggaran_t1" } },

			{ tag: "field", prop: { label: "Target T2", name: "target_t2" } },
			{ tag: "field", prop: { label: "Anggaran T2", name: "anggaran_t2" } },

			{ tag: "field", prop: { label: "Target T3", name: "target_t3" } },
			{ tag: "field", prop: { label: "Anggaran T3", name: "anggaran_t3" } },

			{ tag: "field", prop: { label: "Target T4", name: "target_t4" } },
			{ tag: "field", prop: { label: "Anggaran T4", name: "anggaran_t4" } },

			{ tag: "field", prop: { label: "Target T5", name: "target_t5" } },
			{ tag: "field", prop: { label: "Anggaran T5", name: "anggaran_t5" } },

			{ tag: "field", prop: { label: "Target Akhir", name: "target_akhir" } },

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
		],
	},

	/* ======================================================
       REFERENSI
    ====================================================== */
	referensi: {
		urusan: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		bidang: [
			{
				tag: "fieldDropdown",
				prop: { label: "Urusan", name: "kode_urusan", source: "urusan" },
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		program: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Bidang",
					name: "kode_bidang",
					source: "bidang",
					classInput: "search",
				},
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		kegiatan: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Program",
					name: "kode_program",
					source: "program",
					classInput: "search",
				},
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		sub_kegiatan: [
			{
				tag: "fieldDropdown",
				prop: {
					label: "Kegiatan",
					name: "kode_kegiatan",
					source: "kegiatan",
					classInput: "search",
				},
			},
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Nama", name: "nama" } },
		],

		akun: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		satuan: [
			{ tag: "field", prop: { label: "Value", name: "value" } },
			{ tag: "field", prop: { label: "Item", name: "item" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		organisasi: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Alamat", name: "alamat" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		wilayah: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Status", name: "status" } },
		],

		peraturan: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{ tag: "field", prop: { label: "Judul", name: "judul" } },
			{ tag: "field", prop: { label: "Status", name: "status" } },
		],

		rekanan: [
			// ===============================
			// DATA PERUSAHAAN
			// ===============================
			{
				tag: "divider",
				prop: { label: "Data Perusahaan" },
			},

			{
				tag: "field",
				prop: {
					label: "Nama Perusahaan",
					name: "nama_perusahaan",
					classField: "required",
				},
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

			// ===============================
			// DATA REKENING
			// ===============================
			{
				tag: "divider",
				prop: { label: "Data Rekening" },
			},

			{ tag: "field", prop: { label: "Nomor Rekening", name: "no_rekening" } },

			{ tag: "field", prop: { label: "Bank", name: "bank_rekening" } },

			{
				tag: "field",
				prop: { label: "Atas Nama Rekening", name: "atas_nama_rekening" },
			},

			// ===============================
			// DATA DIREKTUR
			// ===============================
			{
				tag: "divider",
				prop: { label: "Data Direktur" },
			},

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

			// ===============================
			// DATA AKTA & NOTARIS
			// ===============================
			{
				tag: "divider",
				prop: { label: "Akta Pendirian" },
			},

			{
				tag: "field",
				prop: { label: "No Akta Pendirian", name: "no_akta_pendirian" },
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Akta Pendirian",
					name: "tgl_akta_pendirian",
					calendarType: "date",
				},
			},

			{
				tag: "fields",
				prop: {
					classGroup: "two",
					children: [
						{
							tag: "field",
							prop: {
								label: "Nama Notaris Pendirian",
								name: "nama_notaris_pendirian",
							},
						},
						{
							tag: "field",
							prop: {
								label: "Lokasi Notaris Pendirian",
								name: "lokasi_notaris_pendirian",
							},
						},
					],
				},
			},

			{
				tag: "divider",
				prop: { label: "Akta Perubahan" },
			},

			{
				tag: "field",
				prop: { label: "No Akta Perubahan", name: "no_akta_perubahan" },
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Akta Perubahan",
					name: "tgl_akta_perubahan",
					calendarType: "date",
				},
			},

			{
				tag: "fields",
				prop: {
					classGroup: "two",
					children: [
						{
							tag: "field",
							prop: {
								label: "Nama Notaris Perubahan",
								name: "nama_notaris_perubahan",
							},
						},
						{
							tag: "field",
							prop: {
								label: "Lokasi Notaris Perubahan",
								name: "lokasi_notaris_perubahan",
							},
						},
					],
				},
			},

			// ===============================
			// DATA LAIN
			// ===============================
			{
				tag: "divider",
				prop: { label: "Informasi Tambahan" },
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Data Lain", name: "data_lain", atribut: `rows="2"` },
			},

			{
				tag: "fieldFile",
				prop: { label: "Upload Dokumen", name: "file" },
			},

			{
				tag: "fieldTextarea",
				prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` },
			},
			{
				tag: "fieldCheckbox",
				prop: { label: "Non Aktif", name: "disable" },
			},
		],

		sumber_dana: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
			{ tag: "field", prop: { label: "Keterangan", name: "keterangan" } },
		],

		mapping: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Uraian Aset", name: "uraian_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Akun", name: "uraian_akun" } },
		],

		aset: [
			{ tag: "field", prop: { label: "Kode", name: "kode" } },
			{ tag: "field", prop: { label: "Uraian", name: "uraian" } },
		],
	},

	/* ======================================================
       STANDAR HARGA
    ====================================================== */
	standar_harga: {
		sbu: [
			{
				tag: "field",
				prop: { label: "Kode Aset", name: "kd_aset", classField: "required" },
			},
			{
				tag: "field",
				prop: { label: "Kode Akun", name: "kd_akun", classField: "required" },
			},
			{
				tag: "field",
				prop: {
					label: "Uraian Barang",
					name: "uraian_barang",
					classField: "required",
				},
			},
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
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

		ssh: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Barang", name: "uraian_barang" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
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

		asb: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Barang", name: "uraian_barang" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
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

		hspk: [
			{ tag: "field", prop: { label: "Kode Aset", name: "kd_aset" } },
			{ tag: "field", prop: { label: "Kode Akun", name: "kd_akun" } },
			{ tag: "field", prop: { label: "Uraian Barang", name: "uraian_barang" } },
			{ tag: "field", prop: { label: "Spesifikasi", name: "spesifikasi" } },
			{ tag: "fieldDropdown", prop: { label: "Satuan", name: "satuan" } },
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
       KEPEGAWAIAN
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
					classField: "required",
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
					placeholder: "Tempat lahir",
					classField: "required",
				},
			},

			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Lahir",
					name: "tgl_lahir",
					calendarType: "date",
					readonly: true,
					classField: "required",
				},
			},

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

			{ tag: "field", prop: { label: "Nomor KTP", name: "no_ktp" } },
			{ tag: "field", prop: { label: "NPWP", name: "npwp" } },
			{ tag: "field", prop: { label: "Alamat", name: "alamat" } },
			{ tag: "field", prop: { label: "Kontak Person", name: "kontak_person" } },
			{ tag: "field", prop: { label: "Email", name: "email" } },

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
						{ value: "kepercayaan", text: "Kepercayaan Tuhan YME." },
					],
				},
			},

			{
				tag: "fieldDropdown",
				prop: {
					label: "Kelamin",
					name: "kelamin",
					classField: "required",
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
					atribut: `rows="2"`,
				},
			},

			{
				tag: "fieldCheckbox",
				prop: {
					label: "Non Aktif",
					name: "disable",
				},
			},
		],
	},
	tata_naskah: {
		/* ======================================================
       📄 1️⃣ DAFTAR NASKAH (DYNAMIC ENGINE)
    ====================================================== */

		trx_naskah_dinas: [
			{ tag: "field", prop: { label: "Nomor", name: "nomor" } },
			{ tag: "field", prop: { label: "Status", name: "workflow_status" } },
			{ tag: "field", prop: { label: "OPD", name: "kd_opd" } },
			{ tag: "field", prop: { label: "Tahun", name: "tahun" } },
			{ tag: "field", prop: { label: "Tanggal Dibuat", name: "tgl_insert" } },
		],

		/* ======================================================
       🧾 2️⃣ SK (Schema Based)
    ====================================================== */
		sk: [
			{
				tag: "field",
				prop: {
					label: "Nomor Surat",
					name: "nomor",
					classField: "required",
				},
			},
			{
				tag: "fieldCalendar",
				prop: {
					label: "Tanggal Surat",
					name: "tgl_surat_dibuat",
					calendarType: "date",
					classField: "required",
				},
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Tentang",
					name: "tentang",
					atribut: 'rows="2"',
					classField: "required",
				},
			},
			{
				tag: "fieldDropdown",
				prop: {
					label: "Pemberi Tugas",
					name: "pemberi_tgs",
					source: "asn",
					search: true,
					clearable: true,
				},
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Keterangan",
					name: "keterangan",
					atribut: 'rows="2"',
				},
			},
		],

		/* ======================================================
       📩 3️⃣ SURAT INTERNAL
    ====================================================== */
		surat_internal: [
			{
				tag: "field",
				prop: { label: "Nomor", name: "nomor" },
			},
			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},
			{
				tag: "field",
				prop: { label: "Kepada", name: "kepada", classField: "required" },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Perihal", name: "perihal", atribut: 'rows="2"' },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Isi Surat", name: "isi", atribut: 'rows="6"' },
			},
		],

		/* ======================================================
       📃 4️⃣ SURAT BEBAS
    ====================================================== */
		surat_bebas: [
			{
				tag: "field",
				prop: { label: "Nomor", name: "nomor" },
			},
			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Isi Surat", name: "isi", atribut: 'rows="8"' },
			},
		],

		/* ======================================================
       📑 5️⃣ TELAAH STAF
    ====================================================== */
		telaah_staf: [
			{
				tag: "field",
				prop: { label: "Nomor", name: "nomor" },
			},
			{
				tag: "fieldCalendar",
				prop: { label: "Tanggal", name: "tanggal", calendarType: "date" },
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Permasalahan",
					name: "permasalahan",
					atribut: 'rows="3"',
				},
			},
			{
				tag: "fieldTextarea",
				prop: { label: "Analisis", name: "analisis", atribut: 'rows="5"' },
			},
			{
				tag: "fieldTextarea",
				prop: {
					label: "Rekomendasi",
					name: "rekomendasi",
					atribut: 'rows="3"',
				},
			},
		],
	},
	/* ======================================================
   🌐 GLOBAL PRINT CONFIG
   Digunakan SEMUA MODULE
   Layout dibuat PERSIS seperti contoh
====================================================== */
	global_print: [
		// ID ROW (hidden)
		{
			tag: "fieldHidden",
			prop: { name: "id_row" },
		},

		// ==============================================
		// DOKUMEN + TANGGAL
		// ==============================================
		{
			tag: "fields",
			prop: {
				classGroup: "two",
				children: [
					{
						tag: "fieldDropdown",
						prop: {
							label: "Dokumen",
							name: "dokumen",
							classField: "required",
							dropdownClass: "read-only selection inverted lainnya",
							options: [],
						},
					},

					{
						tag: "fieldCalendar",
						prop: {
							label: "Tanggal Surat",
							name: "tgl_surat_dibuat",
							calendarType: "date",
							classField: "required",
						},
					},
				],
			},
		},

		// ==============================================
		// UKURAN KERTAS + UKURAN HURUF
		// ==============================================
		{
			tag: "fields",
			prop: {
				classGroup: "two",
				children: [
					{
						tag: "fieldDropdown",
						prop: {
							label: "Ukuran Kertas",
							name: "ukuran_kertas",
							classField: "required",
							dropdownClass: "selection inverted lainnya",
							options: [
								{ value: "letter", text: "Letter (216 x 279 mm)" },
								{ value: "legal", text: "Legal (216 x 356 mm)" },
								{ value: "A4", text: "A4 (210 x 297 mm)" },
								{ value: "A3", text: "A3 (297 x 420 mm)" },
								{
									value: "F4",
									text: "F4 (215 x 330 mm)",
									class: "active selected",
								},
								{ value: "custom", text: "Custom" },
							],
						},
					},

					{
						tag: "field",
						prop: {
							label: "Ukuran Huruf (%)",
							name: "ukuran_huruf",
							atribut: `value="100"`,
							classField: "required",
						},
					},
				],
			},
		},

		// ==============================================
		// ORIENTASI (CUSTOM LAYOUT)
		// ==============================================
		{
			tag: "fieldCustom",
			prop: {
				html: `
<div class="field">
<label>Orientasi</label>
<input type="hidden" name="orientasi" value="portrait">
<div class="ui compact labeled icon menu inverted">
<a class="item aksi active" value="portrait" jns="orientasi">
<i class="id badge icon inverted"></i>Portrait
</a>
<a class="item aksi" value="landscape" jns="orientasi">
<i class="id card icon inverted"></i>Landscape
</a>
</div>
</div>
`,
			},
		},

		{ tag: "divider" },

		{ tag: "header", prop: { label: "Margin Halaman (mm)" } },

		// ==============================================
		// MARGIN ATAS & BAWAH
		// ==============================================
		{
			tag: "fields",
			prop: {
				classGroup: "two",
				children: [
					{
						tag: "field",
						prop: {
							label: "Atas",
							name: "margin_top",
							atribut: `value="20"`,
							classField: "required",
						},
					},
					{
						tag: "field",
						prop: {
							label: "Bawah",
							name: "margin_bottom",
							atribut: `value="20"`,
							classField: "required",
						},
					},
				],
			},
		},

		// ==============================================
		// MARGIN KIRI & KANAN
		// ==============================================
		{
			tag: "fields",
			prop: {
				classGroup: "two",
				children: [
					{
						tag: "field",
						prop: {
							label: "Kiri",
							name: "margin_kiri",
							atribut: `value="20"`,
							classField: "required",
						},
					},
					{
						tag: "field",
						prop: {
							label: "Kanan",
							name: "margin_kanan",
							atribut: `value="20"`,
							classField: "required",
						},
					},
				],
			},
		},

		{ tag: "divider" },

		// HEADER & FOOTER TOGGLE
		{
			tag: "fields",
			prop: {
				classGroup: "two",
				children: [
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
		},

		// MARGIN HEADER & FOOTER
		{
			tag: "fields",
			prop: {
				classGroup: "two",
				children: [
					{
						tag: "field",
						prop: {
							label: "Header",
							name: "margin_header",
							atribut: `value="10"`,
							classField: "required",
						},
					},
					{
						tag: "field",
						prop: {
							label: "Footer",
							name: "margin_footer",
							atribut: `value="10"`,
							classField: "required",
						},
					},
				],
			},
		},

		{ tag: "divider" },

		{ tag: "header", prop: { label: "Kop Surat" } },

		{
			tag: "fieldCheckbox",
			prop: { label: "Cetak Kop Surat", name: "cetak_kop", default: true },
		},

		{
			tag: "fieldRadio",
			prop: {
				name: "kop_dns",
				options: [
					{ value: "standar", text: "Kop standar dinas", checked: true },
					{ value: "custom", text: "Kop gambar (custom)" },
				],
			},
		},
	],
};
// 🔥 WAJIB agar bisa dibaca TableManager
window.UIConfig = UIConfig;
window.UIConfig = window.UIConfig || {};

UIConfig.periode_rpjmd = [
    {
        tag: "fieldCalendar",
        prop: {
            label: "Periode Mulai",
            name: "periode_mulai",
            calendarType: "year",
        },
    },
    {
        tag: "fieldCalendar",
        prop: {
            label: "Periode Selesai",
            name: "periode_selesai",
            calendarType: "year",
        },
    },
    {
        tag: "fieldTextarea",
        prop: {
            label: "Keterangan",
            name: "keterangan",
        },
    },
    {
        tag: "fieldCheckbox",
        prop: {
            label: "Aktif",
            name: "status_aktif",
        },
    },
];
