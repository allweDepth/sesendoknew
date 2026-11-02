# Laporan Keuangan OPD

## Dasar dan bentuk laporan

Modul mengacu pada [PP Nomor 71 Tahun 2010 tentang Standar Akuntansi Pemerintahan](https://peraturan.bpk.go.id/Details/5095/pp-no-71-tahun-2010) dan [Permendagri Nomor 77 Tahun 2020 tentang Pedoman Teknis Pengelolaan Keuangan Daerah](https://peraturan.bpk.go.id/Details/162792/permendagri-no-77-tahun-2020). Gunakan juga Peraturan Kepala Daerah tentang kebijakan akuntansi dan Bagan Akun Standar Pemda yang berlaku untuk tahun serta wilayah bersangkutan.

- **LRA:** menampilkan pagu kontrak dan transaksi realisasi kontrak yang tercatat, per subkegiatan dan rekening. Data ini tidak mencakup pendapatan, transfer, atau belanja non-kontrak yang belum tersedia di aplikasi. Karena itu tampilan dan ekspor diberi keterangan sebagai kertas kerja realisasi, bukan LRA LKPD lengkap.
- **Neraca:** saldo awal dan akhir dimasukkan manual atau diimpor dari template Excel, bersumber dari buku besar dan daftar aset/kewajiban OPD. Kode kelas 1, 2, dan 3 dipakai sebagai pemeriksaan awal untuk Aset, Kewajiban, dan Ekuitas; kode rinci tetap harus dicocokkan dengan Bagan Akun Standar dan kebijakan akuntansi daerah. Saldo awal maupun akhir diperiksa dengan persamaan Aset = Kewajiban + Ekuitas. Rekening induk dan rekening rincian tidak boleh dimasukkan sekaligus agar saldo tidak dihitung ganda.
- **CaLK:** narasi per bagian diisi manual dan harus menjelaskan angka material serta kebijakan yang diterapkan. Aplikasi tidak mengarang kebijakan atau mengisi nilai pengungkapan otomatis.

Ekspor Neraca/CaLK berlabel **Kertas Kerja**. Keseimbangan neraca saja tidak menjamin pengakuan, pengukuran, klasifikasi aset, penyusutan, atau pengungkapan telah benar. Verifikasi dokumen sumber, rekonsiliasi, kebijakan akuntansi daerah, dan pengesahan pejabat tetap diperlukan sebelum laporan dipakai sebagai laporan resmi.

Referensi `akun_neo` pada basis data saat ini didominasi rekening anggaran/belanja dan `aset_neo` adalah referensi klasifikasi barang/aset; keduanya bukan buku besar saldo Neraca. Modul tidak memetakan saldo ke rekening rinci secara otomatis dari kedua tabel itu. Gunakan kode dan nama rekening Neraca dari BAS/kebijakan akuntansi yang berlaku, lalu cocokkan aset dengan register BMD sebelum menyimpan.

## Instalasi skema

Jalankan sekali pada tiap basis data aplikasi setelah deploy:

```sh
php scripts/install_laporan_keuangan.php
```

Jalankan dari root proyek menggunakan akun migrasi database yang memiliki hak `CREATE` (variabel `SESENDOK_SETUP_ADMIN` dan `SESENDOK_SETUP_ADMIN_PASSWORD` bila bukan `root`). Skrip membuat tabel saldo Neraca dan pengungkapan CaLK jika belum ada; data yang sudah ada tidak dihapus.
