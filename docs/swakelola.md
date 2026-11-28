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

## Tata letak Word, keluaran tetap TCPDF

Seluruh cetak Swakelola menggunakan `SwakelolaTcpdfLayoutService`, termasuk
kontrak utama dan bundel. Tidak menyajikan PDF Word statis atau menjalankan
LibreOffice ketika pengguna mencetak. Kontrak utama memuat Pokok Perjanjian,
SSUK dan SSKK, memakai snapshot tersimpan jika ada, atau draf tanpa mengubah DB.

Font, ukuran, spasi, indentasi dan penomoran sumber diimpor sebagai metadata
inert dengan `scripts/import_swakelola_layout.py`. Model disimpan per kode pada
`resources/procurement/swakelola/layout/*.json.gz`. Untuk dokumen Word yang
sebelumnya sudah disimpan, `layout.json` privat mempertahankan format spesifik
paket. PDF cache Word dan pemeriksaan pemetaan Word tidak digunakan oleh jalur
cetak TCPDF. Ukuran kertas, margin, header dan footer mengikuti Page Setup OPD.
Jumlah halaman mengalir sesuai isi, tanpa target jumlah halaman Word.

Setiap paragraf tetap blok mandiri; label nomor/bullet terpisah dari teks dan
baris lanjutannya menggunakan hanging indent. Metadata run mempertahankan
penekanan kata, sedangkan perubahan format pengguna mengatasi default sumber.
Spasi kosong berlebih dan tinggi baris contoh tidak memaksa tanda tangan ke
halaman berikutnya. Baris tanda tangan dikelompokkan, sementara tabel yang
lebih tinggi daripada halaman tetap dapat berlanjut.

Editor tetap mendukung geser batas kolom, tambah/hapus paragraf, baris, kolom
serta sel. Lebar `column_widths` yang disimpan pengguna selalu didahulukan;
penarikan lebar juga tersedia pada RAB dengan isi terikat data. Font, ukuran,
spasi, jarak akhir, bullet/penomoran dan perataan dapat dikustom per paragraf.
Tidak ada batasan pemetaan Word yang menghalangi penambahan/penghapusan teks.

Pengujian: `tests/swakelola_tcpdf_layout_test.php` dan pasangan Python memeriksa
koordinat PDF hasil cetak, perubahan lebar manual, F4/A5, nomor, bullet, paragraf
baru dan penempatan tanda tangan. `tests/swakelola_native_editor_test.cjs` serta
`tests/swakelola_table_editor_test.cjs` memeriksa editor pada Chrome terisolasi,
termasuk drag, serialisasi dan buka ulang. Pengujian geometri memakai Python
untuk membaca hasil PDF, bukan untuk menghasilkan PDF aplikasi.

## Default dari database

Draf `SW_` kini membaca `swakelola_teks_default_neo` dan `swakelola_pengaturan_default_neo` terlebih dahulu. SQL, penanda lokasi, contoh UPDATE, serta petunjuk kode ada di [database/swakelola/README.md](../database/swakelola/README.md). Default tidak menimpa snapshot dokumen tersimpan.
