/* =========================================================
	 UNIVERSAL TABLE MODULE
	 ---------------------------------------------------------
	 → Konfigurasi tombol aksi per module/tabel
	 → Tambah atau ubah aturan tombol di sini
========================================================= */

const ActionConfig = {
	// DEFAULT GLOBAL
	default: {
		roles: {
			super_admin: ["edit", "delete"],
			admin_wilayah: ["edit", "delete"],
			admin_opd: ["edit"],
			editor: ["edit"],
			viewer: [],
		},
	},

	// ===============================
	// REFERENSI
	// ===============================
	referensi: {
		urusan: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		bidang: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		program: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		kegiatan: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		sub_kegiatan: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit", "delete"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},
	},

	// ===============================
	// KEPEGAWAIAN
	// ===============================
	kepegawaian: {
		asn: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: [],
				admin_opd: ["edit"],
				editor: ["edit"],
				viewer: [],
			},
		},
	},

	// ===============================
	// RENSTRA
	// ===============================
	renstra: {
		renstra_neo: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},

		misi_renstra_neo: {
			roles: {
				super_admin: ["edit", "delete"],
				admin_wilayah: ["edit"],
				admin_opd: [],
				editor: [],
				viewer: [],
			},
		},
	},
	// ===============================
	// TATA NASKAH
	// ===============================
	tata_naskah: {
		trx_naskah_dinas: {
			roles: {
				super_admin: [
					"edit",
					"delete",
					{
						action: "print",
						icon: "print",
						color: "blue",
						container: "modal",
						ui: "open-form",
					},
				],

				admin_opd: [
					"edit",
					"delete",
					{
						action: "print",
						icon: "print",
						color: "blue",
						container: "modal",
						ui: "open-form",
					},
				],

				admin_wilayah: [{ action: "print", icon: "print", color: "blue" }],
			},
		},
	},
};
