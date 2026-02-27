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
            { tag: "input", prop: { label: "Tahun Anggaran", name: "tahun", type: "number" }},
            { tag: "input", prop: { label: "Tahun Renstra", name: "tahun_renstra", type: "number" }},

            // ================= RANGE PERIODE =================
            {
                tag: "rangeCalendar",
                prop: {
                    label: "Renja",
                    nameStart: "awal_renja",
                    nameEnd: "akhir_renja",
                    calendarType: "datetime"
                }
            },
            {
                tag: "rangeCalendar",
                prop: {
                    label: "DPA",
                    nameStart: "awal_dpa",
                    nameEnd: "akhir_dpa"
                }
            },
            {
                tag: "rangeCalendar",
                prop: {
                    label: "RKPD",
                    nameStart: "awal_rkpd",
                    nameEnd: "akhir_rkpd"
                }
            },

            // ================= CONTROL =================
            { tag: "toggle", prop: { label: "Disable", name: "disable" }},
            { tag: "toggle", prop: { label: "Kunci Global", name: "kunci" }},
            { tag: "toggle", prop: { label: "Setujui Global", name: "setujui" }},
            { tag: "textarea", prop: { label: "Keterangan", name: "keterangan" }}

        ]
    }
};


/* ======================================================
   2️⃣ PERIODE RPJMD
====================================================== */

UIConfig.periode_rpjmd = {

    layout: { columns: 2 },

    validation: {
        periode_mulai: { required: true },
        periode_selesai: { required: true }
    },

    form: {
        elements: [

            {
                tag: "rangeCalendar",
                prop: {
                    label: "Periode RPJMD",
                    nameStart: "periode_mulai",
                    nameEnd: "periode_selesai",
                    calendarType: "year"
                }
            },

            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }},
            { tag: "fieldCheckbox", prop: { label: "Aktif", name: "status_aktif" }}

        ]
    }
};
/* ======================================================
   3️⃣ RENSTRA MODULES
====================================================== */

UIConfig.renstra_neo = {
    layout: { columns: 1 },
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Periode RPJMD", name: "periode_id", source: "periode_rpjmd" }},
            { tag: "fieldTextarea", prop: { label: "Visi", name: "visi", atribut: `rows="3"` }},
            { tag: "fieldCheckbox", prop: { label: "Status", name: "status" }},
            { tag: "fieldCheckbox", prop: { label: "Kunci", name: "kunci" }},
            { tag: "fieldCheckbox", prop: { label: "Setujui", name: "setujui" }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.misi_renstra_neo = {
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Pilih Visi", name: "renstra_id", source: "renstra_neo" }},
            { tag: "fieldTextarea", prop: { label: "Nama Misi", name: "nama_misi", atribut: `rows="2"` }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.tujuan_renstra_neo = {
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Misi", name: "misi_id", source: "misi_renstra_neo" }},
            { tag: "fieldTextarea", prop: { label: "Nama Tujuan", name: "nama_tujuan", atribut: `rows="2"` }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.sasaran_renstra_neo = {
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Tujuan", name: "tujuan_id", source: "tujuan_renstra_neo" }},
            { tag: "fieldTextarea", prop: { label: "Nama Sasaran", name: "nama_sasaran", atribut: `rows="2"` }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.indikator_sasaran_renstra_neo = {
    layout: { columns: 2 },
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Sasaran", name: "sasaran_id", source: "sasaran_renstra_neo" }},
            { tag: "field", prop: { label: "Nama Indikator", name: "nama_indikator" }},
            { tag: "field", prop: { label: "Satuan", name: "satuan" }},
            { tag: "field", prop: { label: "Baseline", name: "baseline" }},
            { tag: "field", prop: { label: "Target T1", name: "target_t1" }},
            { tag: "field", prop: { label: "Target T2", name: "target_t2" }},
            { tag: "field", prop: { label: "Target T3", name: "target_t3" }},
            { tag: "field", prop: { label: "Target T4", name: "target_t4" }},
            { tag: "field", prop: { label: "Target T5", name: "target_t5" }},
            { tag: "field", prop: { label: "Target Akhir", name: "target_akhir" }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.program_renstra_neo = {
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Sasaran", name: "sasaran_id", source: "sasaran_renstra_neo" }},
            { tag: "fieldDropdown", prop: { label: "Program", name: "kode_program", source: "program" }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.indikator_program_renstra_neo = {
    layout: { columns: 2 },
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Program", name: "program_id", source: "program_renstra_neo" }},
            { tag: "field", prop: { label: "Nama Indikator", name: "nama_indikator" }},
            { tag: "field", prop: { label: "Satuan", name: "satuan" }},
            { tag: "field", prop: { label: "Baseline", name: "baseline" }},
            { tag: "field", prop: { label: "Target T1", name: "target_t1" }},
            { tag: "field", prop: { label: "Target T2", name: "target_t2" }},
            { tag: "field", prop: { label: "Target T3", name: "target_t3" }},
            { tag: "field", prop: { label: "Target T4", name: "target_t4" }},
            { tag: "field", prop: { label: "Target T5", name: "target_t5" }},
            { tag: "field", prop: { label: "Target Akhir", name: "target_akhir" }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.kegiatan_renstra_neo = {
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Program", name: "program_id", source: "program_renstra_neo" }},
            { tag: "field", prop: { label: "Kode Kegiatan", name: "kode_kegiatan" }},
            { tag: "fieldTextarea", prop: { label: "Nama Kegiatan", name: "nama_kegiatan", atribut: `rows="2"` }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};

UIConfig.sub_kegiatan_renstra_neo = {
    layout: { columns: 2 },
    form: {
        elements: [
            { tag: "fieldDropdown", prop: { label: "Kegiatan", name: "kegiatan_renstra_id", source: "kegiatan_renstra_neo" }},
            { tag: "field", prop: { label: "Master Sub Kegiatan", name: "master_sub_kegiatan_id" }},
            { tag: "field", prop: { label: "Lokasi", name: "lokasi" }},
            { tag: "field", prop: { label: "Kelompok Sasaran", name: "kelompok_sasaran" }},
            { tag: "field", prop: { label: "Baseline", name: "baseline" }},
            { tag: "field", prop: { label: "Target T1", name: "target_t1" }},
            { tag: "field", prop: { label: "Anggaran T1", name: "anggaran_t1", format: "currency" }},
            { tag: "field", prop: { label: "Target T2", name: "target_t2" }},
            { tag: "field", prop: { label: "Anggaran T2", name: "anggaran_t2", format: "currency" }},
            { tag: "field", prop: { label: "Target T3", name: "target_t3" }},
            { tag: "field", prop: { label: "Anggaran T3", name: "anggaran_t3", format: "currency" }},
            { tag: "field", prop: { label: "Target T4", name: "target_t4" }},
            { tag: "field", prop: { label: "Anggaran T4", name: "anggaran_t4", format: "currency" }},
            { tag: "field", prop: { label: "Target T5", name: "target_t5" }},
            { tag: "field", prop: { label: "Anggaran T5", name: "anggaran_t5", format: "currency" }},
            { tag: "field", prop: { label: "Target Akhir", name: "target_akhir" }},
            { tag: "fieldTextarea", prop: { label: "Keterangan", name: "keterangan", atribut: `rows="2"` }}
        ]
    }
};