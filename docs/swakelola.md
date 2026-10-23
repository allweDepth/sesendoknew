# Dokumen swakelola

Pilih **Swakelola** dan **Tipe I–IV** pada kontrak. Referensi pelaksana memakai
`rekanan_id` yang sama dengan fasilitas perusahaan/instansi/organisasi. Pada
**Buat Dokumen Pengadaan**, tombol **Susun Semua Dokumen Paket** menyusun dokumen
sebagai draft. Proses dapat diulang tanpa menggandakan atau menimpa hasil lama.

Master terdiri dari 163 format dari 16 berkas sumber: Tipe I 28, Tipe II 29,
Tipe III 57, Tipe IV 49. Angka tersebut termasuk paket lengkap, bab MDS, dan
format terpisah. Paket lengkap tetap dapat dipilih secara terpisah; pembuatan
massal memilih bab/format agar bundel yang sama tidak digandakan.

Sumber dan checksum ada di `resources/procurement/swakelola/catalog.json`.
Importer mempertahankan paragraf, tabel, penomoran Word, dan catatan kaki.
Jalur Permintaan Kesediaan, Pemilihan Tipe III, serta Usulan Pokmas Tipe IV
memiliki master tersendiri dengan nama sumbernya.

## Penyimpanan

- `swakelola_sumber_neo`: identitas 16 sumber dan SHA-256.
- `master_dokumen_pengadaan_neo`: aturan tipe dan judul master.
- `master_dokumen_pengadaan_bagian_neo`: isi format sumber.
- `dokumen_pengadaan_paragraf_neo`: paragraf, tabel, baris, dan sel sebagai
  baris database dengan parent dan urutan. Master dan hasil memiliki pemilik
  berbeda. Isi JSON pada bagian dipertahankan untuk kompatibilitas API.
- `dokumen_pengadaan_neo` dan `dokumen_pengadaan_bagian_neo`: snapshot hasil
  yang tetap terhubung ke kontrak.

Master dibaca dari database saat draft dibuat. Nama paket, pelaksana, pejabat,
kop resmi, tanggal, dan data terkait ditarik dari lingkup kontrak. Rincian RAB
berasal dari `rab_paket_neo`, fasilitas RAB kontrak yang sudah ada. Koreksi RAB
dilakukan di tab tersebut. Angka RAB pada hasil merupakan snapshot saat dibuat;
hasil yang telah disunting tidak ditimpa oleh pembuatan massal.

Data yang belum tercatat ditandai `—` dan dicatat pada `data_isian` sebagai
`data_belum_tersedia`. Identitas, nomor SK, hasil evaluasi, dan fakta kegiatan
yang belum tersedia tidak dibuat-buat. Petunjuk model seperti `(isi ...)`
tidak ditampilkan pada hasil. Paragraf dan sel lain dapat dikoreksi melalui
satu toolbar di pratinjau dokumen. Kop menggunakan pengaturan OPD yang sama
dengan Tata Naskah dan renderer PDF resmi.

## Pemasangan

Jalankan `php database/migrations/20261023_phase63_swakelola.php` dengan akun
database yang berhak membuat tabel. Konfigurasi akun aplikasi sehari-hari tidak
perlu diubah. Migrasi idempoten dan tidak menulis ulang hasil dokumen yang ada.
Master generik lama Tipe I–IV dinonaktifkan setelah master sumber terpasang.

## Verifikasi

`php tests/swakelola_documents_test.php` memeriksa semua 163 master, round trip
blok database, pengisian otomatis semua tipe, sumber RAB, simpan/edit/PDF,
pembuatan massal tanpa duplikasi, serta pembatasan tipe dan OPD. Data uji
memiliki lingkup sendiri dan dibersihkan pada akhir pengujian.

`php tests/phase58_fixed_procurement_pdf_realization_ui_test.php` juga lulus.
Pemeriksaan lama Phase 56 lulus pada bagian master dan aturan kontrak, lalu
berhenti pada pemeriksaan input tanggal native di kode yang sudah ada; perubahan
ini tidak menambahkan input tanggal native.
