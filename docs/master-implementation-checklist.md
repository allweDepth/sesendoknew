# Master Implementation Checklist

Dokumen ini adalah sumber status untuk seluruh permintaan pengguna. Status `PASS`
berarti memiliki implementasi dan pengujian otomatis; `PARTIAL` berarti fondasi ada
namun seluruh acceptance criteria belum diuji; `OPEN` berarti belum boleh dinyatakan
selesai.

| No | Ruang lingkup | Status | Bukti / pekerjaan lanjutan |
|---:|---|---|---|
| 1 | Kontrak–RAB, realisasi terpadu, schedule, Kurva S, bobot, dokumen | PASS | Phase 4/10; `KontrakRealisasiService`, tes phase4/10 |
| 2 | Import/export RAB Excel dan PDF | PASS | Import Excel, export Excel+Kurva S, dan PDF RAB+jadwal lulus phase10 |
| 3 | Template import seluruh Referensi, standar harga, perencanaan, anggaran, kontrak/RAB, realisasi, absensi | PASS | 33 profile wajib dan tabel fisiknya lulus phase12; template dibuat dari schema aktual |
| 4 | SPJ, LRA, laporan bulanan fisik/keuangan Excel/PDF/chart | PASS | SPJ berbasis bukti, LRA pagu–realisasi–sisa, rekap fisik/keuangan 12 bulan, total, format berwarna, PDF, dan chart Excel lulus phase19 |
| 5 | Modal multi-uraian difilter subkegiatan, search, limit | PASS | Wajib pilih subkegiatan, pencarian uraian/rekening/nilai, limit 50 |
| 6 | Kop surat default OPD dan gambar upload | PASS | Form resmi, default OPD, logo kiri/kanan, gambar penuh, warna garis, MIME/size validation, dan render PDF lulus phase13 |
| 7 | Tata naskah rapi dan mudah | PASS | 25 jenis, workflow/edit/PDF lulus phase6/9; wizard tiga langkah, pencarian jenis/daftar, indikator kelengkapan field, validasi dan API toast yang benar lulus phase23 |
| 8 | Matriks role, pengelola role, pembatasan subkegiatan | PASS | Matriks backend/frontend, CRUD user OPD oleh kepala OPD, hashing, pembatasan role/scope, deaktivasi, dan penugasan subkegiatan lulus phase12/14 |
| 9 | Cetak/upload kontrak, SSKK/SSUK, serah terima/gambar/dokumen lain | PASS | PDF kontrak, generator SSKK/SSUK, dan 16 jenis dokumen upload terhubung; phase13 |
| 10 | Folder upload kontrak dan kepegawaian | PASS | Hirarki scope/tahun/kontrak/jenis; schema dokumen pegawai tersedia |
| 11 | Hapus tab menu ganda Kontrak/Realisasi/Standar Harga | PASS | `useMenu=false` untuk kedua modul; lulus phase12 |
| 12 | Garis/notifikasi kanan atas | PASS | Wrapper/progress toast yatim dibersihkan lewat callback dan fallback timer; container tanpa `.ui.toast` dipaksa tersembunyi; lulus phase22 |
| 13 | Dropdown jumlah row dan search global | PASS | Navbar global terhubung `TableManager`, rows/search diteruskan ke seluruh tabel dinamis |
| 14 | Dropdown sidebar kanan | PASS | Pesan dan Profil memakai route SPA resmi, tema gelap/terang persisten serta mendukung keyboard, Logout memakai dialog konfirmasi; seluruh aksi menu lulus phase24 |
| 15 | Edit Tata Naskah tersimpan | PASS | Struktur dan header disimpan transaksional, load edit dipulihkan; lulus phase6 |
| 16 | Pesan sementara, file, hapus kedua pihak, UI profesional | PASS | Enkripsi, pesan sementara, file privat 3 MB, read state, per-party delete, modal kirim dan inbox profesional lulus phase11/16 |
| 17 | Enkripsi pesan browser–server dan database | PASS | Phase 26: RSA-OAEP 3072 identity per browser (IndexedDB), AES-GCM per pesan, kunci simetris dibungkus untuk pengirim/penerima, plaintext tidak dikirim/disimpan; pesan legacy tetap kompatibel |
| 18 | Performa online/server | PASS | Phase 27: pagination/limit, batch komentar tanpa N+1, indeks feed/inbox/kontrak/realisasi, dan runbook OPcache–MariaDB–slow query–cache produksi lulus tes |
| 19 | Wallchat CRUD, gambar/video 2–3 MB, tema, UI medsos | PASS | Feed sosial, composer ringkas, CRUD berbasis pemilik, gambar/video 3 MB, lima tema, komentar dan media terproteksi lulus phase7/16; respons upload selalu JSON dan folder upload tersedia lulus phase22 |
| 20 | Dashboard/UI Referensi, Kepegawaian, Tata Naskah dan submenu datar | PASS | Phase 23/28: wizard Tata Naskah dan dashboard responsif Referensi/Kepegawaian dengan hero, rangkuman, petunjuk, pencarian serta tabel dinamis lulus tes |
| 21 | Export resmi Renstra/Renja/RKA/DPA/perubahan/SPJ/LRA, warna, sheet per subkegiatan | PASS | Renstra T-C.25/T-C.27 PDF+Excel lulus phase21; dua keluarga Renja/RKA/DPA normal dan perubahan PDF+Excel per subkegiatan lulus phase20; SPJ/LRA resmi dan chart lulus phase19; mapping rekening-standar-aset lulus phase18 |
| 22 | Dummy SSH/SBU/ASB/HSPK, 5 subkegiatan, alur Renja–RKPD–DPPA–kontrak–laporan | PASS | Phase 15/29: ≥10 data tiap standar dan lima rantai RKPD–DPPA kini seluruhnya memiliki kontrak, item, RAB, dan realisasi; seed idempoten dan tes integrasi lulus |
| 23 | Kepala OPD menambah subkegiatan; user hanya CRUD uraian/belanja; output/satuan/pagu | PASS | Mutasi master hanya Kepala OPD/PA/KPA dan administrator terkait; PPTK/PPK/staf dibatasi penugasan, form memuat output/satuan/batas pagu; phase17 |
| 24 | Pengetahuan role/kewenangan pada Home OPD | PASS | Dashboard menampilkan role aktif, lingkup, aksi, batas akses, dan panduan semua role; phase12 |
| 25 | Hardening hacking/database/online | PASS | Phase 27: prepared statement, CSRF, CSP/HSTS, session strict/HttpOnly/SameSite/Secure, batas request, upload allow-list/non-eksekusi, penolakan file sensitif/listing, least privilege dan runbook audit/deployment lulus gate |
| 26 | Rencana penarikan DPA/DPPA per rincian rekening dan 12 bulan | PASS | Phase 30: tabel rencana bulanan, validasi tidak melampaui pagu, UI per rincian, rangkuman per subkegiatan pada PDF resmi |
| 27 | Penugasan TAPD bertanggal dan penanda tangan dokumen | PASS | Phase 30: role TAPD daerah, masa berlaku awal/akhir, urutan dan tabel tanda tangan sesuai tanggal cetak |
| 28 | Hierarki rekening, lebar kolom konsisten, PDF rincian dan rekap DPA/DPPA | PASS | Phase 30/31: baris hierarki akun, uraian rincian tanpa pengulangan kode, tabel fixed/colgroup, PDF rincian serta rekap resmi telah dirender visual |
| 29 | Wall media, toast otomatis, inbox terpisah, lifecycle file upload | PASS | Media memakai endpoint privat tanpa cache basi; toast maksimal empat dan auto-hide; inbox punya halaman/kembali ke Wall; hapus Wall/dokumen kontrak menghapus file fisik |
| 30 | Scope user berdasarkan role, organisasi/OPD dan akun semua role | PASS | Role TAPD ditambahkan, scope regional/OPD/subkegiatan diterapkan, seed idempoten membuat akun uji seluruh role pada scope data aktif |
| 31 | CMS depan Berita/Data Teknis/Organisasi/Pelayanan dengan status aktif | PASS | Phase 31: tipe konten dan toggle aktif, akses pengelola daerah/TAPD, halaman publik hero/card hanya menampilkan konten aktif |
| 32 | Rencana DPA/DPPA per rekening level akhir pada halaman list | PASS | Phase 33: halaman penuh non-modal, tabel horizontal, pembagian 12 bulan per kode rekening, batas maksimal pagu rekening, rangkuman per sub kegiatan, dan tombol kembali |
| 33 | User OPD/Kepala OPD/TAPD bersumber dari pegawai sesuai wilayah dan OPD | PASS | Phase 33: relasi `pegawai_id`, dropdown pencarian pegawai aktif, scope wilayah/OPD, tab User Wilayah dan Tim Anggaran Daerah, aktivasi user serta masa penugasan TAPD |
| 34 | PDF normal/perubahan mengikuti orientasi dan kolom formulir resmi | PASS | Phase 33: normal A4 potret, perubahan A4 lanskap; kolom PPN/satuan/volume/harga/jumlah sejajar; tabel input bulanan dan TAPD tidak dicampur ke formulir rincian resmi |

## Gate penyelesaian

Permintaan hanya boleh disebut selesai setelah semua baris berstatus `PASS`, seluruh
migrasi idempoten, suite regresi lulus, dan acceptance visual dilakukan pada sesi
login untuk desktop serta mobile.
