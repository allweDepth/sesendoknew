# PHASE 0 — Audit Arsitektur dan Database

Tanggal audit: 31 Agustus 2026  
Document root: `public/`  
Database: `sesendoknew_db` (MariaDB 12.3.2)

## Ruang lingkup yang diperiksa

- Entry point dan routing: `public/index.php`, `app/Core/Router.php`, `routes/web.php`.
- Core dan dynamic engine: `app/Core/*`, `DynamicController`, `DynamicTableService`, seluruh service `DynamicTable/*`.
- Kontrak UI: sidebar, SPA router, page loader, App initializer, UIConfig, ActionConfig, RoleConfig, TableManager, FormEngine, Flyout.
- Controller, view/shell, dan modul JavaScript untuk delapan grup utama.
- `app/Config/table_profiles.php`, dump `config/sesendoknew_db.sql`, dan metadata database live.
- Permendagri 86/2017, 90/2019, dan 77/2020 melalui Database Peraturan BPK.

## Arsitektur aktual

Alur CRUD dinamis yang benar-benar dipakai adalah:

`sidebar -> URL -> routes/web.php -> controller shell -> view -> SpaRouter -> App.loadModule -> module JS -> BaseCrudModule/TableManager/FormEngine/Flyout -> /dynamic -> DynamicController -> DynamicTableService -> table_profiles.php -> tabel fisik`

Alur khusus yang tidak sepenuhnya memakai CRUD dinamis:

- Renstra memakai `RenstraController`, tree view, dan beberapa endpoint khusus.
- Anggaran memakai `AnggaranController` dan service hierarchy/group/copy/perubahan.
- Tata Naskah memakai controller dan schema/form/PDF khusus.
- Wallchat memakai `WallchatController` dan `WallchatModel`.

## Baseline database

- Dump SQL: 62 tabel.
- Database live: 62 tabel.
- Nama tabel dump dan live sama setelah normalisasi/sort bytewise.
- Table profile: 41 logical profile menuju 35 tabel fisik unik.
- Foreign key eksplisit: 3.
- Index entries: 89.
- Foreign key aktual:
  - `master_biaya_akun.master_biaya_id -> master_biaya.id`
  - `rekanan_akta.rekanan_id -> rekanan_neo.id`
  - `trx_naskah_struktur.naskah_id -> trx_naskah_dinas.id`

## Temuan route dan controller

1. Empat route didefinisikan dua kali. Karena `routes/web.php` mengembalikan associative array PHP, definisi terakhir menang:
   - `/renja`: duplikat identik.
   - `/dpa`: `DpaController::index` ditimpa `AnggaranController::dpa`.
   - `/renja_perubahan`: `RenjaPerubahanController::index` ditimpa `AnggaranController::renjaPerubahan`.
   - `/dppa`: `DppaController::index` ditimpa `AnggaranController::dppa`.
2. Route `/import` menunjuk `DynamicController::import`, tetapi method tersebut tidak ada.
3. `/tata_naskah/dokumen` dipakai sidebar tetapi tidak terdaftar. Method `TataNaskahController::dashboard()` tersedia tanpa route.
4. Method `cetak`, `updateStatus`, dan `uploadSignature` pada Tata Naskah tidak memiliki route.
5. Wallchat hanya mendaftarkan `/wallchat`; endpoint yang dipanggil/tersedia (`feed`, `store`, `comment`, `privateMessage`, `delete`) tidak terdaftar.
6. `Router::route()` memblokir setiap URI yang mengandung `/store`, `/update`, atau `/delete` sebelum lookup route. Akibatnya beberapa legacy route yang masih didaftarkan tidak mungkin dijalankan.
7. Sidebar `/user_pemda` tidak mempunyai route maupun module map.

## Temuan database/profile

1. Dua physical table dari profile tidak ada di dump maupun database live:
   - `renstra_skpd -> renstra_skpd_neo`
   - `tujuan_sasaran_renstra -> tujuan_sasaran_renstra_neo`
2. RKPD dan RKPD Perubahan belum mempunyai route, controller, module, profile, atau tabel transaksi.
3. Canonical perubahan yang tersedia adalah `renja_p_neo` dan `rka_p_neo`. Controller masih memakai tabel tidak ada `renja_perubahan_neo` dan `rka_perubahan_neo`.
4. Profile `ssh/sbu/asb/hspk` benar menuju unified table `master_biaya`, tetapi field profile masih memakai kontrak lama (`kd_aset`, `kd_akun`, `uraian_barang`, `satuan`, `harga_satuan`) dan pivot table per jenis yang tidak ada. Schema canonical memakai `kode_aset`, `uraian`, `satuan_id`, `harga`, dan `master_biaya_akun`.
5. Profile `mapping` memakai qualified select/join dan memang berbasis `master_biaya_akun`; validator harus membedakan ekspresi join dari kolom base table.
6. Profile ASN mengharapkan field `uraian`, sedangkan tabel memakai `nama`; profile juga mencoba auto-fill `tahun`, tetapi `db_asn_pemda_neo` tidak mempunyai kolom tersebut.
7. Profile `rekanan` mencoba auto-fill `tahun` dan `kd_opd`, yang tidak ada pada `rekanan_neo`.
8. Banyak profile child Renstra mencoba auto-fill `kd_wilayah/tahun/kd_opd`, sedangkan tabel child menyimpan relasi parent dan tidak memiliki ketiga kolom tersebut.
9. Wallchat profile hanya memvalidasi kolom yang ada, tetapi controller memakai `receiver_id` yang tidak ada. Enum schema adalah `status/pesan/komentar`, sedangkan controller mengirim `status/comment/private`.
10. Validator profile setelah diperbaiki memeriksa 41 profile dan menghasilkan 94 mismatch nyata/candidate yang harus diselesaikan per fase.

## Temuan SPA dan JavaScript

1. `App.initPage()` menggunakan `switch(true)`, tetapi `case "wallchat"` dan `case "halaman_berita"` adalah string sehingga tidak pernah cocok.
2. `SpaRouter.loadServerPartial()` memanggil `loadModule()` lalu `initPage()`, sehingga module tertentu berisiko diinisialisasi dua kali.
3. Back/forward hanya memakai `window.location.pathname`; query string tidak ikut diteruskan. Navigasi antar-subtab berbasis `tbl`/`req` dapat kehilangan state pada popstate.
4. `PageLoader` dan module map menggunakan kontrak module yang berbeda untuk beberapa halaman server/client; perlu diuji per URL setelah route canonical ditetapkan.
5. `wallchat.js` memanggil `/wallchat/feed`, tetapi route tidak ada.

## Temuan permission

- Role yang diizinkan `Auth`: `super_admin`, `admin_wilayah`, `admin_opd`, `editor`, `viewer`, `user`.
- Role yang ada pada data live: `super_admin`, `admin_wilayah`, `admin_opd`, `user`.
- `DynamicTableService` tidak memberi action apa pun kepada role `user`, walaupun `Auth` mengizinkannya login.
- `role-config.js` hanya mendefinisikan empat role utama dan tidak memuat `editor`/`user`.
- ActionConfig dan backend permission perlu dicocokkan per logical table; frontend visibility tidak boleh menjadi satu-satunya enforcement.

## Temuan engine metadata

`DB::query()` menutup cursor untuk semua statement yang tidak diawali `SELECT`. Pemanggilan `SHOW COLUMNS`, `SHOW TABLES`, dan `SHOW CREATE TABLE` di beberapa service/controller kemudian mencoba melakukan fetch dari cursor yang sudah ditutup. Selain itu, placeholder pada bentuk `SHOW ... LIKE ?` tidak diterima prepared statement native MariaDB. Lokasi kandidat terdampak:

- `DynamicTableService::getTableColumns()` dan pemeriksaan tabel/kolom lain.
- `ProfileGenerator`.
- `ResetTabelController` backup/restore metadata.
- Validator lama (sudah diperbaiki pada PHASE 0 dengan `information_schema`).

## Regulasi sebagai constraint desain

- Permendagri 86/2017: RKPD adalah dokumen tahunan daerah yang harus diturunkan ke Renja perangkat daerah; perubahan RKPD menjadi dasar perubahan Renja. Implementasi RKPD tidak boleh sekadar menyalin detail belanja Renja.
- Permendagri 90/2019: nomenklatur harus mempertahankan hierarki urusan, bidang urusan, program, kegiatan, dan subkegiatan serta klasifikasi keuangan terkait.
- Permendagri 77/2020: alur penganggaran/pelaksanaan harus menjaga provenance dari perencanaan/RKA ke DPA, perubahan, pelaksanaan, penatausahaan, dan pelaporan.

Sumber resmi:

- https://peraturan.bpk.go.id/Details/311927/permendagri-no-86-tahun-2017
- https://peraturan.bpk.go.id/Details/139075/permendagri-no-90tahun-2019
- https://peraturan.bpk.go.id/details/162792/permendagri-no-77-tahun-%202020

## Gate fase berikutnya

PHASE 0 dinyatakan PASS ketika audit dan alat validasinya repeatable, bukan ketika 94 mismatch fitur sudah hilang. Mismatch tersebut menjadi backlog berurutan PHASE 1–9. PHASE 1 harus dimulai dari lima mode `rekening_kegiatan`, lalu Rekanan, Satuan, Mapping, Aset, Akun, Sumber Dana, Organisasi, Peraturan, dan Wilayah. Tidak ada perubahan schema bisnis pada PHASE 0.
