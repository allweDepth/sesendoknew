# Master Implementation Checklist

Dokumen ini adalah sumber status untuk seluruh permintaan pengguna. Status `PASS`
berarti memiliki implementasi dan pengujian otomatis; `PARTIAL` berarti fondasi ada
namun seluruh acceptance criteria belum diuji; `OPEN` berarti belum boleh dinyatakan
selesai.

| No | Ruang lingkup | Status | Bukti / pekerjaan lanjutan |
|---:|---|---|---|
| 1 | Kontrak–RAB, realisasi terpadu, schedule, Kurva S, bobot, dokumen | PASS | Phase 4/10; `KontrakRealisasiService`, tes phase4/10 |
| 2 | Import/export RAB Excel dan PDF | PARTIAL | Excel RAB+Kurva S lulus; import RAB dan PDF RAB khusus masih OPEN |
| 3 | Template import seluruh Referensi, standar harga, perencanaan, anggaran, kontrak/RAB, realisasi, absensi | OPEN | Template lama tersedia tetapi matriks kelengkapan seluruh subtab belum lulus |
| 4 | SPJ, LRA, laporan bulanan fisik/keuangan Excel/PDF/chart | PARTIAL | SPJ dan LRA awal lulus phase10; seluruh varian resmi dan laporan bulanan belum lengkap |
| 5 | Modal multi-uraian difilter subkegiatan, search, limit | PASS | Wajib pilih subkegiatan, pencarian uraian/rekening/nilai, limit 50 |
| 6 | Kop surat default OPD dan gambar upload | PARTIAL | Schema `kop_surat_neo` tersedia; UI/upload/render menyeluruh belum diuji |
| 7 | Tata naskah rapi dan mudah | PARTIAL | 25 jenis, workflow dan PDF lulus phase6/9; audit UX seluruh jenis masih diperlukan |
| 8 | Matriks role, pengelola role, pembatasan subkegiatan | PARTIAL | `user_subkegiatan_neo` tersedia; enforcement backend dan UI pengelolaan belum lengkap |
| 9 | Cetak/upload kontrak, SSKK/SSUK, serah terima/gambar/dokumen lain | PARTIAL | PDF kontrak dan 16 jenis upload tersedia; generator isi SSKK/SSUK detail masih OPEN |
| 10 | Folder upload kontrak dan kepegawaian | PASS | Hirarki scope/tahun/kontrak/jenis; schema dokumen pegawai tersedia |
| 11 | Hapus tab menu ganda Kontrak/Realisasi/Standar Harga | OPEN | Perlu verifikasi visual SPA per halaman |
| 12 | Garis/notifikasi kanan atas | OPEN | Perlu reproduksi visual pada sesi login |
| 13 | Dropdown jumlah row dan search global | PARTIAL | Engine mendukung rows/search; audit seluruh tabel belum selesai |
| 14 | Dropdown sidebar kanan | OPEN | Logout diketahui bekerja; aksi lain perlu perbaikan berbasis sesi browser |
| 15 | Edit Tata Naskah tersimpan | OPEN | Create/workflow/PDF lulus; regresi edit spesifik belum tersedia |
| 16 | Pesan sementara, file, hapus kedua pihak, UI profesional | PARTIAL | Enkripsi, ephemeral, file 3 MB, per-party delete lulus phase11; polish UI masih berjalan |
| 17 | Enkripsi pesan browser–server dan database | PARTIAL | TLS + CSP/HSTS dan libsodium authenticated encryption at rest tersedia; E2E browser key ownership belum diterapkan |
| 18 | Performa online/server | PARTIAL | Query limit/index tersedia; profiling beban dan konfigurasi produksi masih OPEN |
| 19 | Wallchat CRUD, gambar/video 2–3 MB, tema, UI medsos | PARTIAL | CRUD dasar ada; tema, video, editor/media wall dan UI final masih OPEN |
| 20 | Dashboard/UI Referensi, Kepegawaian, Tata Naskah dan submenu datar | PARTIAL | Beberapa dashboard modern tersedia; audit visual semua submenu belum selesai |
| 21 | Export resmi Renstra/Renja/RKA/DPA/perubahan/SPT/LRA, warna, sheet per subkegiatan | OPEN | Export dasar ada; kesesuaian seluruh format peraturan belum disahkan |
| 22 | Dummy SSH/SBU/ASB/HSPK, 5 subkegiatan, alur Renja–RKPD–DPPA–kontrak–laporan | PARTIAL | Trace workflow 2 paket lulus; dataset 5 subkegiatan dan acceptance penuh masih OPEN |
| 23 | Kepala OPD menambah subkegiatan; user hanya CRUD uraian/belanja; output/satuan/pagu | OPEN | Role dan workflow perlu enforcement serta UI khusus |
| 24 | Pengetahuan role/kewenangan pada Home OPD | OPEN | Konten dan matriks kewenangan belum dipublikasikan |
| 25 | Hardening hacking/database/online | PARTIAL | Prepared statements, CSRF global, CSP, HSTS, nosniff, same-origin frame dan upload allow-list tersedia; pentest/deployment hardening masih OPEN |

## Gate penyelesaian

Permintaan hanya boleh disebut selesai setelah semua baris berstatus `PASS`, seluruh
migrasi idempoten, suite regresi lulus, dan acceptance visual dilakukan pada sesi
login untuk desktop serta mobile.
