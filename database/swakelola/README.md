# Default teks dan format Swakelola

Database aplikasi: `sesendoknew_db`. SQL lengkap: `default-swakelola.sql` (sekitar 42 MB). Berisi 163 bagian model MDS tipe I, II, III, IV, dengan 33.849 baris struktur. Tidak berisi data penyedia, pejabat, paket, atau dokumen hasil.

## Tabel yang diedit

- `swakelola_teks_default_neo`: teks dan format setiap paragraf, tabel, baris, sel.
- `swakelola_pengaturan_default_neo`: fallback format untuk `SEMUA`, atau khusus `I`, `II`, `III`, `IV`. Format pada masing-masing paragraf lebih utama daripada fallback.

Kunci baris: `kode_dokumen` + `kode_bagian` + `kode_lokasi`. Kolom `tipe` dan `nama_dokumen` membantu mencari dokumen. `P001` berarti paragraf pertama; `T003/R002/C001/P001` berarti paragraf pertama pada sel pertama baris kedua tabel ketiga. Angka menunjukkan urutan blok sumber, bukan nomor halaman. Halaman mengikuti kertas dan margin OPD.

`lokasi_induk` membentuk struktur; `urutan` mengatur urutan antarsaudara (mulai 0). `jenis_blok`: `paragraph`, `table`, `row`, `cell`. Jangan mengubah kunci/induk atau menonaktifkan induk tanpa memindahkan anaknya. Untuk mengganti kata-kata cukup ubah `teks_default` pada baris `paragraph`.

## Cari dan ubah satu paragraf

```sql
USE sesendoknew_db;
SELECT tipe,kode_dokumen,nama_dokumen,kode_bagian,kode_lokasi,teks_default
FROM swakelola_teks_default_neo
WHERE jenis_blok='paragraph' AND teks_default LIKE '%pembayaran%'
ORDER BY tipe,kode_dokumen,kode_lokasi;
```

Gunakan tiga kunci dari hasil pencarian untuk perubahan terarah. Contoh berikut memakai variabel SQL agar kunci dapat disalin dahulu:

```sql
SET @dokumen='SW_II_01_07';
SET @bagian='ISI';
-- Isi lokasi yang diperoleh dari SELECT sebelum menjalankan UPDATE.
SET @lokasi='SALIN_KODE_LOKASI_DARI_HASIL_SELECT';
UPDATE swakelola_teks_default_neo
SET teks_default='Pembayaran dilaksanakan sesuai kebutuhan dan realisasi pekerjaan {{nama_paket}}.'
WHERE kode_dokumen=@dokumen AND kode_bagian=@bagian
  AND kode_lokasi=@lokasi AND jenis_blok='paragraph';
```

Variabel data tersedia antara lain: `{{nama_paket}}`, `{{tahun}}`, `{{nama_opd}}`, `{{nama_ppk}}`, `{{nip_ppk}}`, `{{nama_pptk}}`, `{{nip_pptk}}`, `{{nama_pelaksana}}`, `{{pimpinan_pelaksana}}`, `{{alamat_pelaksana}}`, `{{npwp}}`, `{{nilai_kontrak}}`. Semuanya diisi dari paket dan identitas pejabat/penyedia. Data belum tersedia mengikuti mekanisme isian aplikasi; SQL ini tidak menciptakan identitas.

## Format, nomor, dan lebar tabel

`format_json` menyimpan format per blok. Paragraf memakai `word_format` (nama historis metadata, tetap dicetak TCPDF): `font`, `size` dalam pt, `before`/`after` dalam pt, `line_height`, `left`/`right`/`first` dalam pt, serta `prefix` (label nomor) dan `runs`. Tabel memakai `column_widths` dalam persentase. Pertahankan total 100. Baris `cell` menyimpan `colspan` dan format sel. Gunakan `JSON_SET` agar properti lainnya tetap terjaga:

```sql
UPDATE swakelola_teks_default_neo
SET format_json=JSON_SET(format_json,'$.word_format.after',6)
WHERE kode_dokumen=@dokumen AND kode_bagian=@bagian
  AND kode_lokasi=@lokasi AND jenis_blok='paragraph';
```

Jika mengubah kata-kata, renderer memakai `teks_default`; format run lama hanya digunakan bila teks cocok. Nomor/bullet jangan disatukan ke paragraf lain. Editor dokumen tetap menyediakan pengaturan paragraf serta drag lebar kolom manual. Pengaturan per dokumen tersimpan bersama dokumen tersebut.

## Alur aplikasi

1. Draf membaca tabel default khusus ini melalui `SwakelolaDefaultService::blocks()`.
2. Pengaturan fallback diterapkan, lalu data paket, penyedia, PPK dan PPTK diisi.
3. Saat disimpan, isi dan format menjadi snapshot pada tabel dokumen hasil yang sudah ada.
4. Mengubah default tidak mengubah kontrak/dokumen yang telah disimpan. Buka draf baru untuk melihat perubahan default; jangan menimpa dokumen lama tanpa meninjau isinya.
5. TCPDF mencetak snapshot, menggunakan ukuran kertas/margin dari Page Setup OPD. Tidak mengunci jumlah halaman Word.

Tabel master umum lama tetap tersedia untuk kompatibilitas. Untuk kode `SW_` yang sudah ada di tabel baru, default khusus ini menjadi sumber utama. Mengedit master lama saja tidak mengganti default baru.

## Impor, pasang, dan ekspor ulang

Pilih database `sesendoknew_db` pada phpMyAdmin lalu impor `default-swakelola.sql`. Akun pemasang memerlukan CREATE/INSERT/UPDATE. SQL memulihkan `sql_mode` sesi setelah selesai. Import ulang memperbarui default yang sama dari isi file; simpan backup penyesuaian sebelum mengimpor ulang. Dokumen hasil tetap utuh.

Dari root proyek, menggunakan akun database yang berwenang melalui konfigurasi lokal/env:

```sh
php scripts/export_swakelola_defaults.php --install
```

`--install` membuat tabel jika belum ada. Pengisian awal hanya menambahkan bagian model yang belum ada dan tidak mereset bagian yang sudah dikustom. Ekspor ulang dari database yang sudah terpasang cukup:

```sh
php scripts/export_swakelola_defaults.php
```

File SQL diekspor dari isi tabel terbaru. Setiap INSERT didahului komentar tipe/kode dokumen, bagian, lokasi, jenis blok, serta cuplikan teks. Tidak ada password di SQL.

## File kode

- `app/Services/SwakelolaDefaultService.php`: membaca struktur default dan pengaturan dari SQL.
- `app/Services/ProcurementDocumentService.php`, metode `draft()`: mengambil default dan mengisi data paket.
- `app/Services/SwakelolaDocumentService.php`: struktur blok, identitas/placeholder, penyimpanan snapshot.
- `app/Services/SwakelolaAutoFillService.php`: pengisian model MDS, RAB, jadwal, data pejabat.
- `app/Services/SwakelolaTcpdfLayoutService.php`: cetak TCPDF, numbering, jarak paragraf, tabel, aliran halaman.
- `public/assets/js/modules/kontrak.js`: editor paragraf/tabel dan drag lebar kolom.
- `database/swakelola/schema.sql`: struktur tabel khusus.
- `scripts/export_swakelola_defaults.php`: pemasangan awal dan ekspor SQL.

Ubah kata-kata di tabel default; ubah perilaku cetak di renderer. Metadata `.json.gz` sumber hanya menjadi fallback pemasangan untuk model yang belum tersedia di database.
