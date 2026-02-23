ID = 4
type_user = super_admin
Wilayah = Semua
OPD = Semua
Sub Kegiatan = Semua
Approve = Ya


ID = 3
type_user = admin_wilayah
Wilayah = 1 wilayah
OPD = Semua OPD di wilayah tersebut
Sub Kegiatan = Semua
Approve = Ya


ID = 2
type_user = admin_opd
Wilayah = 1 wilayah
OPD = 1 OPD
Sub Kegiatan = Berdasarkan mapping user_sub_kegiatan
Approve = Tidak


ID = 1
type_user = viewer
Wilayah = 1 wilayah
OPD = 1 OPD
Sub Kegiatan = Berdasarkan mapping user_sub_kegiatan
Approve = Tidak
+----+---------------+------------+---------+---------------+----------+
| ID | TYPE_USER     | WILAYAH    | OPD     | SUB_KEGIATAN | APPROVE  |
+----+---------------+------------+---------+---------------+----------+
| 4  | super_admin   | Semua      | Semua   | Semua         | Ya       |
| 3  | admin_wilayah | 1 wilayah  | Semua   | Semua         | Ya       |
| 2  | admin_opd     | 1 wilayah  | 1 OPD   | Mapping       | Tidak    |
| 1  | viewer        | 1 wilayah  | 1 OPD   | Mapping       | Tidak    |
+----+---------------+------------+---------+---------------+----------+

PRINSIP FINAL ENGINE – DynamicTableService
+----------------------+----------------------------------------------+--------------------------+
| jenis                | Kondisi Tambahan                             | Aksi Engine              |
+----------------------+----------------------------------------------+--------------------------+
| add                  | -                                            | INSERT (buat data baru)  |
| edit                 | hanya kirim id_row                           | LOAD 1 ROW (isi form)    |
| edit                 | kirim id + field lain                        | UPDATE (simpan perubahan)|
| delete               | kirim id_row                                 | DELETE (hapus data)      |
| dropdown             | kirim source                                 | LOAD DROPDOWN            |
| (kosong / lainnya)   | -                                            | LISTING (buildQuery)     |
+----------------------+----------------------------------------------+--------------------------+
Struktur Umum
+----------------+-----------------------------------------------+
| Level          | Fungsi                                        |
+----------------+-----------------------------------------------+
| module         | Nama modul (pengaturan, renstra, dll)        |
| table          | Nama tabel (periode_rpjmd, misi_renstra_neo) |
| tag            | Jenis komponen form                          |
| prop           | Properti komponen                            |
+----------------+-----------------------------------------------+
Bentuk Dasar
const UIConfig = {
    pengaturan: {
        periode_rpjmd: [
            {
                tag: "fieldCalendar",
                prop: {
                    label: "Periode Mulai",
                    name: "periode_mulai",
                    calendarType: "year",
                    attr: {
                        "data-group": "rpjmd",
                        "data-range": "start"
                    }
                }
            }
        ]
    }
};
+-------------------+----------------------------------+
| tag               | Komponen                         |
+-------------------+----------------------------------+
| field             | input text/number                |
| fieldTextarea     | textarea                         |
| fieldDropdown     | dropdown                         |
| fieldCheckbox     | checkbox / toggle                |
| fieldCalendar     | calendar fomantic                |
| fields            | group fields (grid)              |
| inlineFields      | inline layout                    |
| divider           | header pemisah                   |
| cardProfile       | card upload/avatar               |
+-------------------+----------------------------------+
Properti prop Standar
+---------------+----------------------------------+
| Properti      | Fungsi                           |
+---------------+----------------------------------+
| label         | Label field                      |
| name          | Name attribute (wajib untuk DB)  |
| type          | type input                       |
| options       | static dropdown options          |
| source        | dropdown server source           |
| parent        | dropdown dependency              |
| calendarType  | year / date                      |
| readonly      | readonly field                   |
| classField    | class tambahan                   |
| attr          | attribute HTML tambahan          |
+---------------+----------------------------------+
 Aturan UIConfig

✔ name harus sama dengan kolom database
✔ module harus sama dengan AppState.jenis
✔ table harus sama dengan AppState.tbl
✔ Jangan hardcode ID kalau bisa pakai attr
STRUKTUR AppState
+----------------+------------------------------------------+
| Property       | Fungsi                                   |
+----------------+------------------------------------------+
| halaman        | Page aktif untuk pagination              |
| rows           | Jumlah data per halaman                  |
| jenis          | Module aktif                             |
| tbl            | Tabel aktif                              |
| mode           | add / edit / detail                      |
| cari           | Keyword pencarian                        |
| currentMenu    | Tracking menu terakhir                   |
| serverSources  | Dropdown server dependency               |
| role           | Role login aktif                         |
+----------------+------------------------------------------+
Role Matrix Contoh
+-------------+--------+--------+--------+---------+
| Role        | Add    | Edit   | Delete | Upload  |
+-------------+--------+--------+--------+---------+
| super_admin | ✔      | ✔      | ✔      | ✔       |
| admin       | ✔      | ✔      | ✔      | ✔       |
| editor      | ✔      | ✔      | ✖      | ✔       |
| viewer      | ✖      | ✖      | ✖      | ✖       |
+-------------+--------+--------+--------+---------+
PETA DEBUG SEMUA MODUL JS
+--------------------------------------+-------------------------------------------+
| MAU CEK                              | BUKA FILE                                 |
+--------------------------------------+-------------------------------------------+
| Kenapa table tidak reload            | engine/table-manager.js                   |
| Kenapa pagination tidak jalan        | engine/table-manager.js                   |
| Kenapa header tabel kosong           | engine/table-manager.js                   |
| Kenapa placeholder tidak muncul      | engine/table-manager.js                   |
| Kenapa primary key salah             | engine/table-manager.js                   |
+--------------------------------------+-------------------------------------------+
| Kenapa form tidak submit             | engine/form-container-manager.js          |
| Kenapa form tidak load data edit     | engine/form-container-manager.js          |
| Kenapa form tidak validasi           | engine/form-container-manager.js          |
| Kenapa save tidak kirim ke backend   | engine/form-container-manager.js          |
| Kenapa import excel gagal            | engine/form-container-manager.js          |
| Kenapa struktur tata naskah rusak    | engine/form-container-manager.js          |
+--------------------------------------+-------------------------------------------+
| Kenapa dropdown kosong               | engine/form-container-manager.js          |
| Kenapa dropdown tidak refresh        | engine/form-container-manager.js          |
| Kenapa dropdown parent tidak jalan   | engine/form-container-manager.js          |
| Kenapa dropdown tidak fetch server   | AppState.serverSources + form-container   |
+--------------------------------------+-------------------------------------------+
| Kenapa field tidak muncul di form    | config/ui-config.js                       |
| Kenapa label salah                   | config/ui-config.js                       |
| Kenapa dropdown source salah         | config/ui-config.js                       |
| Kenapa role tidak bisa lihat field   | config/ui-config.js (prop.role)           |
+--------------------------------------+-------------------------------------------+
| Kenapa button edit/delete hilang     | config/action-config.js                   |
| Kenapa role tidak bisa delete        | config/action-config.js                   |
| Kenapa tombol aksi beda modul        | config/action-config.js                   |
+--------------------------------------+-------------------------------------------+
| Kenapa ajax gagal / no response      | core/ajax.js                              |
| Kenapa toast tidak muncul            | core/toast.js                             |
| Kenapa dialog tidak muncul           | core/dialog.js                            |
| Kenapa baseUrl salah                 | core/config.js                            |
+--------------------------------------+-------------------------------------------+
| Kenapa Renstra kacau                 | modules/renstra.js                        |
| Kenapa tab Renstra tidak switch      | modules/renstra.js                        |
| Kenapa default renstra tidak load    | app-init.js (GLOBAL AUTO INIT)            |
+--------------------------------------+-------------------------------------------+
| Kenapa Pengaturan tidak load         | modules/pengaturan.js                     |
| Kenapa calendar tidak link           | modules/pengaturan.js                     |
+--------------------------------------+-------------------------------------------+
| Kenapa Tata Naskah error             | modules/tata_naskah.js                    |
| Kenapa nomor surat tidak auto        | app-init.js (registerPlugin)              |
| Kenapa document builder error        | engine/document/document_builder.js       |
| Kenapa schema tidak cocok            | engine/document/document_schema.js        |
+--------------------------------------+-------------------------------------------+
| Kenapa Wallchat tidak kirim pesan    | modules/wallchat.js                       |
| Kenapa private message gagal         | modules/wallchat.js                       |
+--------------------------------------+-------------------------------------------+
| Kenapa Profil tidak load             | modules/profil.js                         |
| Kenapa foto tidak muncul             | modules/profil.js                         |
+--------------------------------------+-------------------------------------------+
| Kenapa sidebar tidak toggle          | app-init.js                               |
| Kenapa accordion tidak buka          | app-init.js                               |
| Kenapa dropdown global tidak aktif   | app-init.js                               |
| Kenapa module tidak auto load        | app-init.js (GLOBAL MODULE AUTO INIT)     |
+--------------------------------------+-------------------------------------------+
| Kenapa state aneh / data tertukar    | AppState (core/state.js)                  |
| Kenapa role salah baca               | AppState.role                             |
| Kenapa serverSources kosong          | FormContainerManager.open()               |
+--------------------------------------+-------------------------------------------+
| Kenapa export tidak jalan            | app-init.js (data-action export)          |
| Kenapa delete tidak reload table     | app-init.js (data-action delete)          |
+--------------------------------------+-------------------------------------------+

# 🔥 DEBUG GUIDE — seSendok Frontend Engine

Panduan cepat debug tanpa scroll 2000 baris.

---

## 📌 1️⃣ TABLE / LISTING ERROR

| Masalah | Cek File |
|----------|-----------|
| Table tidak reload | engine/table-manager.js |
| Pagination tidak jalan | engine/table-manager.js |
| Header tabel kosong | engine/table-manager.js |
| Placeholder tidak muncul | engine/table-manager.js |
| Data kosong padahal backend ada | engine/table-manager.js (fetch) |
| Primary key salah | engine/table-manager.js (meta.primary_key) |

---

## 📌 2️⃣ FORM ERROR

| Masalah | Cek File |
|----------|-----------|
| Form tidak submit | engine/form-container-manager.js |
| Form edit tidak load data | engine/form-container-manager.js (loadData) |
| Validasi tidak jalan | engine/form-container-manager.js (initValidation) |
| Save tidak kirim ke backend | engine/form-container-manager.js (save) |
| Import Excel gagal | engine/form-container-manager.js (import mode) |

---

## 📌 3️⃣ DROPDOWN ERROR

| Masalah | Cek File |
|----------|-----------|
| Dropdown kosong | form-container-manager.js (fetchDropdown) |
| Dropdown tidak refresh | form-container-manager.js |
| Dropdown parent tidak trigger | form-container-manager.js |
| Source salah | config/ui-config.js |
| serverSources kosong | FormContainerManager.open() |

---

## 📌 4️⃣ BUTTON & ROLE ERROR

| Masalah | Cek File |
|----------|-----------|
| Button edit hilang | config/action-config.js |
| Delete tidak muncul | config/action-config.js |
| Role tidak bisa aksi | config/action-config.js |
| Role salah baca | AppState.role |

---

## 📌 5️⃣ MODULE ERROR

| Modul | Cek File |
|--------|----------|
| Renstra | modules/renstra.js |
| Pengaturan | modules/pengaturan.js |
| Tata Naskah | modules/tata_naskah.js |
| Wallchat | modules/wallchat.js |
| Profil | modules/profil.js |

---

## 📌 6️⃣ AJAX ERROR

| Masalah | Cek File |
|----------|-----------|
| Ajax gagal | core/ajax.js |
| Toast tidak muncul | core/toast.js |
| Dialog tidak muncul | core/dialog.js |
| Base URL salah | core/config.js |

---

## 📌 7️⃣ AUTO LOAD MODULE ERROR

Cek:
app-init.js → GLOBAL MODULE AUTO INIT

Jika tidak ada request `dynamic` di network,
berarti tableManager.load() tidak terpanggil.


app-init.js
│
├── core/config.js
├── core/state.js
├── core/ajax.js
├── core/toast.js
├── core/dialog.js
│
├── config/ui-config.js
├── config/action-config.js
├── config/role-config.js
│
├── engine/table-manager.js
│       └── depends on:
│           ├── AppState
│           ├── AjaxEngine
│           └── UIConfig
│
├── engine/form-container-manager.js
│       └── depends on:
│           ├── AppState
│           ├── AjaxEngine
│           ├── UIConfig
│           └── TableManager
│
├── engine/form-engine.js
│       └── depends on:
│           ├── UIConfig
│           └── AppState
│
└── modules/
        ├── renstra.js
        ├── pengaturan.js
        ├── tata_naskah.js
        ├── wallchat.js
        └── profil.js