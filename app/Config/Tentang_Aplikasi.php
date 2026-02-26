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


⸻

==========================================================

📘 RELATIONAL DROPDOWN ENGINE & TABLE PROFILE – PETA DEBUG

==========================================================

⸻
🧩 PETA DEBUG – RELATIONAL DROPDOWN ENGINE

+——————————————————+–––––––––––––––––––––––+
| MAU CEK                                              | BUKA FILE                                     |
+——————————————————+–––––––––––––––––––––––+
| Dropdown salah parent                               | config/table_profiles.php                     |
| Dropdown ambil data semua (tidak terfilter)         | DynamicTableService.php → loadDropdown()      |
| Dropdown tidak selected saat edit                   | FormEngine.js → populateForm()                |
| Dropdown request 5x ke server                       | FormEngine.js → loadDropdownSources()         |
| Dropdown tidak refresh                              | FormEngine.js → static render()               |
| Dropdown tidak kirim parent_value                   | cascade-dropdown.js / event change handler    |
| Data beda wilayah muncul                            | table_profiles.php → modes.where              |
| Data beda peraturan muncul                          | table_profiles.php → modes.where              |
| Child tidak ikut berubah saat parent ganti          | cascade-dropdown.js                           |
| Dropdown tidak punya option                         | UIConfig.js (cek data-source)                 |
+——————————————————+–––––––––––––––––––––––+

⸻

🏛 PETA DEBUG – TABLE PROFILE CONFIG

+——————————————————+–––––––––––––––––––––––+
| MAU CEK                                              | BUKA FILE                                     |
+——————————————————+–––––––––––––––––––––––+
| Relasi parent-child salah                           | config/table_profiles.php → relations         |
| local_key salah                                     | config/table_profiles.php                     |
| parent_key salah                                    | config/table_profiles.php                     |
| Dropdown value salah                                | config/table_profiles.php → dropdown.value    |
| Dropdown label salah                                | config/table_profiles.php → dropdown.label    |
| Insert tidak isi kd_wilayah                         | config/table_profiles.php → auto_session      |
| Insert tidak isi peraturan                          | config/table_profiles.php → auto_session      |
| Query bocor lintas wilayah                          | config/table_profiles.php → modes.where       |
| Query bocor lintas peraturan                        | config/table_profiles.php → modes.where       |
| Mode edit tidak tampil semua field                  | config/table_profiles.php → modes.edit        |
| Field tidak searchable                              | config/table_profiles.php → searchable        |
+——————————————————+–––––––––––––––––––––––+

⸻

🛡 PETA DEBUG – HIERARCHY VALIDATOR

+——————————————————+–––––––––––––––––––––––+
| MAU CEK                                              | BUKA FILE                                     |
+——————————————————+–––––––––––––––––––––––+
| Sub_kegiatan bisa tanpa kegiatan                    | DynamicTableService.php → validateHierarchy() |
| Program beda bidang tetap bisa masuk                | validateHierarchy()                           |
| Import tidak cek parent                             | ImportService.php / validateHierarchy()       |
| Update bisa ubah parent sembarangan                 | update() → validateHierarchy()                |
| Insert tidak cek parent                             | insert() → validateHierarchy()                |
+——————————————————+–––––––––––––––––––––––+

⸻

🔄 PETA DEBUG – AUTO CASCADE DROPDOWN JS

+——————————————————+–––––––––––––––––––––––+
| MAU CEK                                              | BUKA FILE                                     |
+——————————————————+–––––––––––––––––––––––+
| Child tidak reload saat parent berubah              | cascade-dropdown.js                           |
| parent_value tidak terkirim                         | cascade-dropdown.js                           |
| Dropdown tidak kosong sebelum reload                | cascade-dropdown.js                           |
| Dropdown refresh tidak jalan                       | FormEngine.js → dropdown(‘refresh’)           |
| Value lama tetap ada setelah parent ganti          | cascade-dropdown.js                           |
| Dropdown child tidak punya data-parent             | UIConfig.js                                   |
+——————————————————+–––––––––––––––––––––––+

⸻

🧪 PETA DEBUG – IMPORT GOVERNANCE

+——————————————————+–––––––––––––––––––––––+
| MAU CEK                                              | BUKA FILE                                     |
+——————————————————+–––––––––––––––––––––––+
| Import duplicate kode lolos                         | ImportService.php → duplicate check           |
| Import tidak rollback saat error                    | runTransaction()                              |
| Import tidak cek hierarchy                          | validateHierarchy()                           |
| Import tidak isi kd_wilayah                         | resolveAutoFields()                           |
| Import tidak isi peraturan                          | resolvePeraturan()                            |
| Import beda regulasi masuk                          | modes.where                                   |
| Import salah format tanggal                         | normalization section                         |
+——————————————————+–––––––––––––––––––––––+

⸻

🧠 PETA DEBUG – CRUD ENGINE (INSERT & UPDATE)

+——————————————————+–––––––––––––––––––––––+
| MAU CEK                                              | BUKA FILE                                     |
+——————————————————+–––––––––––––––––––––––+
| Insert gagal tanpa alasan jelas                     | insert() → validate()                         |
| Update tidak berubah                                | update() → diff checker                       |
| Audit tidak tercatat                                | logActivity()                                 |
| Transaction tidak rollback                          | runTransaction()                              |
| Field tidak tersimpan                               | filter columns section                        |
| Checkbox tidak jadi 1                               | normalization boolean section                 |
| Tanggal salah format                                | normalization date section                    |
| Scope tidak terinject                               | resolveAutoFields()                           |
+——————————————————+–––––––––––––––––––––––+

⸻

📊 PETA DEBUG – STRUKTUR HIERARKI REFERENSI

+——————————————————+–––––––––––––––––––––––+
| LEVEL                                                | CEK RELASI DI FILE                            |
+——————————————————+–––––––––––––––––––––––+
| URUSAN                                               | table_profiles.php → urusan                   |
| BIDANG                                               | relations: kode_urusan                        |
| PROGRAM                                              | relations: kode_bidang                        |
| KEGIATAN                                             | relations: kode_program                       |
| SUB_KEGIATAN                                         | relations: kode_kegiatan                      |
+——————————————————+–––––––––––––––––––––––+

⸻

🚦 FLOW DEBUG CEPAT (SAAT ERROR SUB_KEGIATAN)

Jika:

“Sub kegiatan salah kegiatan”

Langkah cek cepat:
	1.	Buka table_profiles.php
	2.	Cek relations sub_kegiatan
	3.	Pastikan:
local_key = kode_kegiatan
parent_key = kode
	4.	Cek loadDropdown()
	5.	Pastikan parent_value dikirim
	6.	Cek modes.where (kd_wilayah & peraturan)

⸻

🔐 PETA DEBUG – MULTI WILAYAH & MULTI PERATURAN

+——————————————————+–––––––––––––––––––––––+
| MASALAH                                              | CEK DI FILE                                   |
+——————————————————+–––––––––––––––––––––––+
| Data wilayah lain muncul                             | modes.where                                   |
| Data regulasi lama muncul                            | modes.where                                   |
| Dropdown beda regulasi                               | modes.where + auto_session                    |
| Insert beda wilayah                                  | resolveAutoFields()                           |
| Update beda wilayah                                  | validateHierarchy()                           |
+——————————————————+–––––––––––––––––––––––+

⸻

🏗 STATUS ENGINE ANDA SEKARANG

Layer Sistem:

1️⃣ Scope Filter (kd_wilayah & peraturan)
2️⃣ Relational Filter (relations config)
3️⃣ Hierarchy Validator
4️⃣ Import Governance
5️⃣ Audit Trail
6️⃣ Transaction Safety

=========================================================
==========================================================

📘 PETA DEBUG KHUSUS MODULE JS

==========================================================

⸻

🧩 TABLE MANAGER (engine/table-manager.js)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | BUKA FILE / METHOD                           |
+–––––––––––––––––––––––––+———————————————–+
| Table tidak reload                               | loadData()                                   |
| Pagination tidak jalan                           | renderPagination()                           |
| Header tabel kosong                              | renderHeader()                               |
| Body tabel kosong                                | renderBody()                                 |
| Primary key salah                                | state.primaryKey                             |
| Search tidak bekerja                             | handleSearch()                               |
| Sorting tidak jalan                              | handleSort()                                 |
| Table reload 2x                                  | event binding duplicate                      |
| Placeholder tidak muncul                         | renderEmptyState()                           |
| Delete tidak jalan                               | handleDelete()                               |
+–––––––––––––––––––––––––+———————————————–+

⸻

🧩 FORM ENGINE (engine/form-engine.js)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | METHOD                                        |
+–––––––––––––––––––––––––+———————————————–+
| Dropdown tidak load                              | loadDropdownSources()                        |
| Dropdown request 5x                              | init() + render()                            |
| Dropdown tidak selected saat edit                | populateForm()                               |
| Data edit tidak muncul                           | loadData()                                   |
| Submit tidak jalan                               | bindEvents()                                 |
| Checkbox tidak jadi 1                            | populateForm()                               |
| Calendar tidak muncul                            | static render()                              |
| Dropdown tidak refresh                           | dropdown(‘refresh’)                          |
| Data lama masih ada                              | render() destroy dropdown                    |
+–––––––––––––––––––––––––+———————————————–+

⸻

🧩 FLYOUT CONTROLLER (engine/flyout-controller.js)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | METHOD                                        |
+–––––––––––––––––––––––––+———————————————–+
| Form tidak terbuka                               | open()                                        |
| Edit tidak load data                             | open() → loadData()                           |
| state tidak berubah                              | state.setTable()                              |
| Action tidak terset                              | state.setAction()                             |
| Modal tidak muncul                               | modal(“show”)                                 |
| Sidebar tidak muncul                             | sidebar(“show”)                               |
| Form submit tidak trigger                        | getActiveForm()                               |
| FormEngine instance undefined                    | constructor open()                            |
+–––––––––––––––––––––––––+———————————————–+

⸻

🧩 SPA ROUTER (engine/spa-router.js)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | METHOD                                        |
+–––––––––––––––––––––––––+———————————————–+
| Halaman tidak load                               | navigate()                                    |
| Content tidak masuk #main-content                | render()                                      |
| URL berubah tapi konten lama                     | history.pushState()                           |
| Reload pakai AJAX tapi return HTML               | Ajax.request()                                |
| Back button tidak jalan                          | popstate listener                             |
| 404 muncul salah                                 | route mapping                                 |
+–––––––––––––––––––––––––+———————————————–+

⸻

==========================================================

📘 PETA DEBUG KHUSUS MODULE PHP

==========================================================

⸻

🧩 ROUTER (app/Core/Router.php)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | BAGIAN                                        |
+–––––––––––––––––––––––––+———————————————–+
| Route tidak ketemu                               | route()                                       |
| 404 padahal file ada                             | mapping controller                            |
| Method tidak dipanggil                           | $controller->$method()                        |
| AJAX return HTML bukan JSON                      | Controller isAjax()                           |
+–––––––––––––––––––––––––+———————————————–+

⸻

🧩 CONTROLLER (app/Controllers)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | BAGIAN                                        |
+–––––––––––––––––––––––––+———————————————–+
| JSON tidak return                                | JsonResponse::success()                       |
| Edit return 2x                                   | Method dipanggil dua kali                     |
| Dropdown salah tabel                             | DynamicTableService dipanggil salah           |
| Module salah                                     | $request[‘module’]                            |
| action tidak terbaca                             | $request[‘action’]                            |
+–––––––––––––––––––––––––+———————————————–+

⸻

🧩 SERVICE LAYER (DynamicTableService.php)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | METHOD                                        |
+–––––––––––––––––––––––––+———————————————–+
| Insert tidak simetris                            | insert()                                      |
| Update tidak simetris                            | update()                                      |
| Dropdown salah kegiatan                          | loadDropdown()                                |
| Hierarchy tidak dicek                            | validateHierarchy()                           |
| Scope bocor wilayah                              | applyWhereFromProfile()                       |
| Peraturan tidak terinject                        | resolvePeraturan()                            |
| Audit tidak jalan                                | logActivity()                                 |
| Import tidak rollback                            | runTransaction()                              |
+–––––––––––––––––––––––––+———————————————–+

⸻

🧩 AUTH (app/Core/Auth.php)

+–––––––––––––––––––––––––+———————————————–+
| MAU CEK                                          | METHOD                                        |
+–––––––––––––––––––––––––+———————————————–+
| Login gagal padahal benar                        | login()                                       |
| Session hilang setelah refresh                   | session_start()                               |
| Session timeout terlalu cepat                    | check() timeout logic                         |
| Role tidak dikenali                              | allowedRoles()                                |
| Logout tidak bersih                              | logout()                                      |
+–––––––––––––––––––––––––+———————————————–+

⸻

==========================================================

📘 PETA DEBUG FULL STACK – END TO END

==========================================================

⸻

🔄 FLOW: USER KLIK EDIT SUB_KEGIATAN

1️⃣ Klik tombol edit
→ FlyoutController.open()

2️⃣ state.setTable(‘sub_kegiatan’)
→ FormEngine.render()

3️⃣ FormEngine.loadData(id)
→ Ajax POST action=edit

4️⃣ Controller → DynamicTableService.getEdit()

5️⃣ Response JSON dikirim

6️⃣ FormEngine.populateForm()

7️⃣ loadDropdownSources()

8️⃣ Dropdown kirim action=dropdown

9️⃣ DynamicTableService.loadDropdown()

10️⃣ Query dengan relations + scope

11️⃣ Dropdown refresh

12️⃣ populateForm set selected

Selesai.

⸻

🚨 JIKA ADA ERROR “SUB KEGIATAN SALAH KEGIATAN”

Checklist debug:
	1.	table_profiles.php → relations benar?
	2.	loadDropdown() pakai parent_value?
	3.	modes.where ada kd_wilayah & peraturan?
	4.	cascade dropdown kirim parent_value?
	5.	populateForm jalan setelah dropdown load?

⸻

🔐 FULL SYSTEM SAFETY LAYER

Layer 1 → SPA Router
Layer 2 → Controller
Layer 3 → Service
Layer 4 → Table Profile
Layer 5 → Scope Filter
Layer 6 → Hierarchy Validator
Layer 7 → Transaction
Layer 8 → Audit

Semua harus sinkron.

⸻

🏁 STATUS SISTEM ANDA SEKARANG

✔ JS Modular
✔ PHP Layered
✔ Relational Aware
✔ Multi Wilayah Safe
✔ Multi Regulasi Safe
✔ Deterministic Dropdown
✔ Simetris Insert/Update
✔ Enterprise Governance
==========================================================

📘 PETA VISUAL TREE – ARSITEKTUR FOLDER SISTEM

==========================================================
public/
│
├── index.php                → Entry point semua request
│
├── assets/
│   ├── engine/
│   │   ├── table-manager.js
│   │   ├── form-engine.js
│   │   ├── flyout-controller.js
│   │   ├── spa-router.js
│   │   └── cascade-dropdown.js
│   │
│   └── css/
│
app/
│
├── Core/
│   ├── Router.php
│   ├── Controller.php
│   ├── DB.php
│   └── Auth.php
│
├── Controllers/
│   ├── ReferensiController.php
│   ├── DynamicController.php
│   └── ...
│
├── Services/
│   ├── DynamicTableService.php
│   ├── ImportService.php
│   └── JsonResponse.php
│
├── Config/
│   └── table_profiles.php
│
└── Views/
    └── ...
    ==========================================================

📘 PETA DEPENDENCY ANTAR FILE

==========================================================
index.php
   ↓
Router.php
   ↓
Controller
   ↓
DynamicTableService
   ↓
table_profiles.php
   ↓
Database
Frontend chain:
Button Click
   ↓
FlyoutController
   ↓
FormEngine
   ↓
Ajax.request
   ↓
Controller
   ↓
DynamicTableService
==========================================================

📘 PETA FLOW REQUEST AJAX DETAIL

==========================================================
🔄 FLOW: LOAD TABLE
TableManager.loadData()
    ↓
Ajax POST
    action = list
    tbl = kegiatan
    ↓
DynamicController
    ↓
DynamicTableService.getList()
    ↓
Apply profile mode
    ↓
Apply scope filter
    ↓
Return JSON
    ↓
TableManager.renderBody()
🔄 FLOW: EDIT DATA
FlyoutController.open()
    ↓
FormEngine.loadData()
    ↓
Ajax POST
    action = edit
    tbl = sub_kegiatan
    id_row = 3395
    ↓
DynamicTableService.getEdit()
    ↓
Apply scope filter
    ↓
Return JSON
    ↓
FormEngine.populateForm()
🔄 FLOW: DROPDOWN RELATIONAL
FormEngine.loadDropdownSources()
    ↓
Ajax POST
    action = dropdown
    tbl = kegiatan
    parent_value = kode_program
    ↓
DynamicTableService.loadDropdown()
    ↓
Baca relations dari table_profiles
    ↓
Build WHERE local_key = parent_value
    ↓
Apply scope filter
    ↓
Return JSON
    ↓
Dropdown.refresh()
==========================================================

📘 CHECKLIST DEBUG PRODUKSI (CRITICAL)

==========================================================
🔍 SAAT DEPLOY PRODUKSI – WAJIB CEK

+––––––––––––––––––––––+
| CEK                                        |
+––––––––––––––––––––––+
| Semua table_profiles punya modes.where     |
| Semua tabel struktur punya relations       |
| auto_session aktif untuk tabel scoped      |
| validateHierarchy dipanggil di insert      |
| validateHierarchy dipanggil di update      |
| runTransaction dipakai di import           |
| Audit log aktif                            |
| Session timeout aktif                      |
| allowed_roles sesuai kebutuhan             |
+––––––––––––––––––––––+

⸻

==========================================================

📘 ERROR SCENARIO MAP

==========================================================

⸻

❌ ERROR: Dropdown salah kegiatan

Cek:
	1.	table_profiles → relations
	2.	loadDropdown() pakai parent_value?
	3.	modes.where ada?
	4.	parent_value dikirim JS?
	5.	Dropdown child kosong sebelum reload?

⸻

❌ ERROR: Data lintas wilayah muncul

Cek:
	1.	modes.where
	2.	resolveAutoFields
	3.	validateHierarchy
	4.	auto_session

⸻

❌ ERROR: Insert berhasil tapi tidak muncul di list

Cek:
	1.	modes.default.where
	2.	Scope filter tahun
	3.	kd_opd mismatch
	4.	peraturan mismatch

⸻

==========================================================

📘 PERFORMANCE DEBUG MAP

==========================================================

⸻

⚡ DROPDOWN LAMBAT

Cek:
	•	Apakah SELECT pakai index?
	•	Apakah ORDER BY pakai index?
	•	Apakah dropdown load tanpa parent?
	•	Apakah dropdown dipanggil 5x?

⸻

⚡ TABLE LAMBAT

Cek:
	•	SELECT *
	•	Searchable terlalu banyak field
	•	Tidak ada limit
	•	Query tidak pakai index

⸻

==========================================================

📘 SECURITY CHECK MAP

==========================================================

⸻

🔐 WAJIB ADA

✔ session_regenerate_id saat login
✔ session timeout
✔ allowed_roles
✔ Scope filter
✔ Hierarchy validation
✔ Import strict mode
✔ Transaction wrap
✔ Soft lock enforcement

⸻

==========================================================

📘 FULL SYSTEM LAYER VISUAL (TEXT)

==========================================================
[ FRONTEND UI ]
        ↓
[ SPA ROUTER ]
        ↓
[ CONTROLLER ]
        ↓
[ SERVICE LAYER ]
        ↓
[ PROFILE CONFIG ]
        ↓
[ DATABASE ]
Semua keputusan query HARUS lewat profile.

Tidak boleh ada query liar di controller.
==========================================================

STATUS SISTEM ANDA SEKARANG

==========================================================

Level arsitektur Anda sekarang:

✔ Modular JS
✔ Layered PHP
✔ Relational deterministic
✔ Multi wilayah aman
✔ Multi regulasi aman
✔ Transaction safe
✔ Audit ready
✔ Governance ready

==========================================================

📦 1. STANDARD OPERATING PROCEDURE (SOP) DEVELOPER INTERNAL

==========================================================

⸻

🎯 TUJUAN

Menjamin semua developer:
	•	Tidak membuat query liar
	•	Tidak merusak relasi hierarki
	•	Tidak membocorkan data lintas wilayah/peraturan
	•	Tidak membuat dropdown tidak deterministic

⸻

📌 ATURAN WAJIB

1️⃣ DILARANG

❌ Query langsung di controller
❌ Hardcode WHERE kd_wilayah
❌ Hardcode peraturan
❌ Query tanpa lewat table_profiles
❌ Insert tanpa validateHierarchy
❌ Update tanpa transaction
❌ Dropdown tanpa relations config

⸻

2️⃣ WAJIB

✔ Semua query lewat DynamicTableService
✔ Semua tabel punya profile di table_profiles.php
✔ Semua tabel struktur punya relations
✔ Semua tabel scoped punya auto_session
✔ Semua CRUD pakai runTransaction()
✔ Semua insert & update pakai validate()

⸻

📌 PROSEDUR TAMBAH TABEL BARU

Langkah wajib:
	1.	Tambahkan entry di table_profiles.php
	2.	Tentukan:
	•	table
	•	primary_key
	•	auto_session (jika scoped)
	•	dropdown config
	•	modes.default
	•	modes.where
	3.	Jika punya parent:
	•	Tambahkan relations
	4.	Tambahkan validation jika perlu
	5.	Test:
	•	Insert
	•	Update
	•	Dropdown
	•	Scope filter

⸻

📌 PROSEDUR TAMBAH DROPDOWN RELATIONAL
	1.	Pastikan tabel child punya relations
	2.	Pastikan JS kirim parent_value
	3.	Pastikan loadDropdown() pakai relations
	4.	Test:
	•	Parent berubah → child reload
	•	Edit → selected benar

⸻

📌 PROSEDUR IMPORT DATA
	1.	Aktifkan import.enabled
	2.	Gunakan mode strict untuk struktur
	3.	Jalankan:
	•	duplicate check
	•	validateHierarchy
	•	scope validation
	4.	Gunakan transaction wrap
	5.	Log audit import

⸻

==========================================================

🏗 2. BLUEPRINT UNTUK DIPAKAI SEMUA OPD

==========================================================

⸻

🎯 STRUKTUR STANDAR APLIKASI OPD

Semua OPD WAJIB pakai struktur:
Controller
   ↓
Service Layer
   ↓
Table Profile
   ↓
Database
Tidak boleh langsung DB di controller.

⸻

🎯 STANDAR RELASI STRUKTUR

Semua OPD wajib pakai:
URUSAN
BIDANG
PROGRAM
KEGIATAN
SUB_KEGIATAN
🎯 STANDAR SCOPE DATA

Semua tabel operasional WAJIB:
'auto_session' => ['kd_wilayah', 'tahun', 'kd_opd']
'modes' => [
   'default' => [
       'where' => [
           'kd_wilayah' => 'user',
           'tahun' => 'user'
       ]
   ]
]
🎯 STANDAR KEAMANAN
	•	Session timeout aktif
	•	allowed_roles di setiap tabel
	•	Soft lock untuk referensi nasional
	•	Audit trail wajib
	•	Transaction wajib

⸻

🎯 STANDAR PERFORMANCE
	•	Dropdown wajib pakai select minimal field
	•	Tidak boleh SELECT *
	•	Semua kode struktur wajib indexed
	•	Pagination wajib ada limit

⸻

==========================================================

🧠 3. DECISION TREE DEBUG CEPAT

==========================================================

⸻

🚨 CASE: Dropdown Salah Data

Apakah tabel punya relations?
→ Tidak → Tambahkan relations
→ Ya → Cek local_key benar?

Apakah parent_value terkirim?
→ Tidak → Cek cascade JS
→ Ya → Cek loadDropdown()

Apakah scope filter ada?
→ Tidak → Tambahkan modes.where

⸻

🚨 CASE: Data Bocor Wilayah

Apakah modes.where ada kd_wilayah?
→ Tidak → Tambahkan

Apakah auto_session ada?
→ Tidak → Tambahkan

Apakah validateHierarchy pakai scope?
→ Tidak → Perbaiki

⸻

🚨 CASE: Insert Tidak Muncul

Apakah modes.default.where filter tahun?
→ Ya → Pastikan session tahun sama

Apakah peraturan aktif sama?
→ Cek resolvePeraturan()

⸻

🚨 CASE: Import Gagal

Apakah duplicate check aktif?
Apakah hierarchy check aktif?
Apakah mode strict?
Apakah transaction wrap?

⸻

==========================================================

🔬 4. SYSTEM HARDENING CHECKLIST PRODUKSI

==========================================================

⸻

🔐 SECURITY HARDENING

✔ session_regenerate_id() saat login
✔ session timeout aktif
✔ Cookie httponly
✔ Role-based access
✔ Soft lock enforcement
✔ CSRF protection (jika ada form publik)

⸻

🧱 DATA HARDENING

✔ validateHierarchy di insert
✔ validateHierarchy di update
✔ Scope filter selalu aktif
✔ Peraturan auto inject
✔ Audit trail aktif

⸻

⚡ PERFORMANCE HARDENING

✔ Index pada:
	•	kode
	•	kode_parent
	•	kd_wilayah
	•	peraturan
✔ Dropdown limit jika > 1000 data
✔ Search pakai LIKE indexed field
✔ Tidak ada SELECT *

⸻

🔄 TRANSACTION HARDENING

✔ Semua insert pakai runTransaction()
✔ Semua update pakai runTransaction()
✔ Import pakai strict mode
✔ Rollback jika error

⸻

📊 MONITORING HARDENING

✔ Log audit per aksi
✔ Log error query
✔ Log import error
✔ Log login gagal