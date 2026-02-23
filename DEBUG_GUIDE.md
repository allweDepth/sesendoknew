
# 🔥 seSendok Frontend Engine — DEBUG GUIDE & ARCHITECTURE DOCUMENTATION

Dokumen ini adalah panduan lengkap untuk debug, memahami arsitektur,
dan audit sebelum deploy sistem frontend seSendok.

==============================================================
SECTION 1 — QUICK DEBUG GUIDE
==============================

📌 1️⃣ TABLE / LISTING ERROR

Masalah                            → Cek File
----------------------------------------------

Table tidak reload                 → engine/table-manager.js
Pagination tidak jalan             → engine/table-manager.js
Header tabel kosong                → engine/table-manager.js
Placeholder tidak muncul           → engine/table-manager.js
Data kosong padahal backend ada    → engine/table-manager.js (fetch)
Primary key salah                  → engine/table-manager.js (meta.primary_key)

📌 2️⃣ FORM ERROR

Masalah                            → Cek File
----------------------------------------------

Form tidak submit                  → engine/form-container-manager.js
Form edit tidak load data          → engine/form-container-manager.js (loadData)
Validasi tidak jalan               → engine/form-container-manager.js (initValidation)
Save tidak kirim ke backend        → engine/form-container-manager.js (save)
Import Excel gagal                 → engine/form-container-manager.js (import mode)

📌 3️⃣ DROPDOWN ERROR

Masalah                            → Cek File
----------------------------------------------

Dropdown kosong                    → form-container-manager.js (fetchDropdown)
Dropdown tidak refresh             → form-container-manager.js
Dropdown parent tidak trigger      → form-container-manager.js
Source salah                       → config/ui-config.js
serverSources kosong               → FormContainerManager.open()

📌 4️⃣ BUTTON & ROLE ERROR

Masalah                            → Cek File
----------------------------------------------

Button edit hilang                 → config/action-config.js
Delete tidak muncul                → config/action-config.js
Role tidak bisa aksi               → config/action-config.js
Role salah baca                    → AppState.role

📌 5️⃣ MODULE ERROR

Modul                              → Cek File
----------------------------------------------

Renstra                            → modules/renstra.js
Pengaturan                         → modules/pengaturan.js
Tata Naskah                        → modules/tata_naskah.js
Wallchat                           → modules/wallchat.js
Profil                             → modules/profil.js

📌 6️⃣ AJAX ERROR

Masalah                            → Cek File
----------------------------------------------

Ajax gagal                         → core/ajax.js
Toast tidak muncul                 → core/toast.js
Dialog tidak muncul                → core/dialog.js
Base URL salah                     → core/config.js

📌 7️⃣ AUTO LOAD MODULE ERROR

Jika tidak ada request `dynamic` di Network:

Cek:
app-init.js → GLOBAL MODULE AUTO INIT

Jika tidak terpanggil:
→ tableManager.load() tidak dieksekusi

==============================================================
SECTION 2 — DEPENDENCY DIAGRAM
===============================

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

🔎 Dependency Core Flow

UIConfig → FormEngine → FormContainerManager → AjaxEngine
ActionConfig → TableManager → AjaxEngine
AppState → Semua Engine

==============================================================
SECTION 3 — ARCHITECTURE FLOW
==============================

🟢 1️⃣ LOAD HALAMAN

Page Load
→ app-init.js
→ GLOBAL MODULE AUTO INIT
→ tableManager.load(module, tbl)
→ fetch()
→ AjaxEngine.request()
→ Backend (dynamic)
→ renderTable()
→ renderPagination()

🟢 2️⃣ USER CLICK TAMBAH

Click Button (data-ui="open-form")
→ FormContainerManager.open()
→ buildConfig()
→ FormEngine.render()
→ loadDropdowns()

🟢 3️⃣ USER SUBMIT FORM

Submit Form
→ initValidation()
→ save()
→ AjaxEngine.request()
→ Backend
→ success
→ tableManager.fetch()
→ Table reload

🟢 4️⃣ USER CLICK DELETE

Click delete
→ DialogEngine.show()
→ AjaxEngine.request()
→ Backend delete
→ tableManager.fetch()
→ Table reload

==============================================================
SECTION 4 — APPSTATE LIFECYCLE
===============================

1. GLOBAL AUTO INIT
   AppState.module = currentPath
   AppState.tbl = tbl
2. User click tab
   AppState.tbl berubah
   AppState.pagination.page reset ke 1
3. User search
   AppState.pagination.search berubah
4. User pagination
   AppState.pagination.page berubah

AppState adalah SINGLE SOURCE OF TRUTH.

==============================================================
SECTION 5 — CHECKLIST AUDIT SEBELUM DEPLOY
===========================================

[ ] GLOBAL MODULE AUTO INIT tidak double load
[ ] Tidak ada module memanggil tableManager.load() sendiri
[ ] AppState.module dan tbl selalu sinkron
[ ] Tidak ada currentMenu yang tidak dipakai
[ ] Semua UIConfig key sesuai nama tabel backend
[ ] ActionConfig role sesuai backend
[ ] AjaxEngine selalu return JSON
[ ] Tidak ada console.error
[ ] Tidak ada request dynamic 2x saat load
[ ] Pagination reset saat pindah tabel
[ ] Dropdown serverSources terisi benar
[ ] Import Excel tidak mengubah AppState.module
[ ] Delete selalu reload table
[ ] Role viewer tidak melihat edit/delete
[ ] Sidebar & accordion tetap berfungsi
[ ] Form validation berjalan
[ ] Semua module diuji minimal 1 kali CRUD

==============================================================
SECTION 6 — STRATEGY DEBUG CEPAT
=================================

Jika ada bug:

1️⃣ Cek Network tab

- Tidak ada dynamic → cek app-init.js
- Ada dynamic tapi kosong → cek table-manager.js
- 500 error → cek backend

2️⃣ Cek Console

- UIConfig undefined → cek config/ui-config.js
- Role undefined → cek AppState.role

3️⃣ Cek State
   console.log(AppState)

4️⃣ Jika renstra kacau

- Cek modules/renstra.js
- Pastikan tidak double load

==============================================================
SECTION 7 — PRODUCTION HARDENING (OPTIONAL)
============================================

[ ] Tambahkan history.pushState saat tab switch
[ ] Tambahkan global error boundary
[ ] Tambahkan logging perubahan AppState
[ ] Freeze AppState di production mode
[ ] Tambahkan audit log ajax

==============================================================
END OF DOCUMENT
===============

Tidak perlu scroll 2000 baris lagi.
Debug sistematis.
Arsitektur jelas.
State terkontrol.
