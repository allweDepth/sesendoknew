# Laporan Keuangan OPD

## Dasar dan bentuk laporan

Modul mengacu pada [PP Nomor 71 Tahun 2010 tentang Standar Akuntansi Pemerintahan](https://peraturan.bpk.go.id/Details/5095/pp-no-71-tahun-2010) dan [Permendagri Nomor 77 Tahun 2020 tentang Pedoman Teknis Pengelolaan Keuangan Daerah](https://peraturan.bpk.go.id/Details/162792/permendagri-no-77-tahun-2020). Gunakan juga Peraturan Kepala Daerah tentang kebijakan akuntansi dan Bagan Akun Standar Pemda yang berlaku untuk tahun serta wilayah bersangkutan.

- **LRA:** pagu berasal dari rincian snapshot DPA/DPPA yang disahkan. Belanja ditarik dari nilai LRA yang diisi per rekening pada setiap transaksi dan hanya setelah jenis transaksi, bukti sumber, dan verifikasi pengguna lain lengkap. Progres/BAST yang belum dibayar bukan belanja kas; SP2D GU tidak dihitung sebagai belanja kedua; UP/TU dihitung dari transaksi SPJ yang disahkan, bukan dari pencairan kas UP/TU. Uang muka kontrak harus ditautkan ke SP2D dan nomor SP2D uang muka yang dipotong dicatat pada penyelesaian termin. Data ini tetap hanya kertas kerja belanja kontrak: pendapatan, transfer, belanja non-kontrak, koreksi, transaksi lintas tahun, serta konsolidasi LKPD tidak tersedia otomatis.
- **Neraca:** saldo awal dan akhir dimasukkan manual atau diimpor dari template Excel, bersumber dari buku besar dan daftar aset/kewajiban OPD. Kode kelas 1, 2, dan 3 dipakai sebagai pemeriksaan awal untuk Aset, Kewajiban, dan Ekuitas; kode rinci tetap harus dicocokkan dengan Bagan Akun Standar dan kebijakan akuntansi daerah. Saldo awal maupun akhir diperiksa dengan persamaan Aset = Kewajiban + Ekuitas. Rekening induk dan rekening rincian tidak boleh dimasukkan sekaligus agar saldo tidak dihitung ganda.
- **CaLK:** narasi per bagian diisi manual dan harus menjelaskan angka material serta kebijakan yang diterapkan. Aplikasi tidak mengarang kebijakan atau mengisi nilai pengungkapan otomatis.

Ekspor Neraca/CaLK berlabel **Kertas Kerja**. Keseimbangan neraca saja tidak menjamin pengakuan, pengukuran, klasifikasi aset, penyusutan, atau pengungkapan telah benar. Verifikasi dokumen sumber, rekonsiliasi, kebijakan akuntansi daerah, dan pengesahan pejabat tetap diperlukan sebelum laporan dipakai sebagai laporan resmi.

Referensi `akun_neo` pada basis data saat ini didominasi rekening anggaran/belanja dan `aset_neo` adalah referensi klasifikasi barang/aset; keduanya bukan buku besar saldo Neraca. Modul tidak memetakan saldo ke rekening rinci secara otomatis dari kedua tabel itu. Gunakan kode dan nama rekening Neraca dari BAS/kebijakan akuntansi yang berlaku, lalu cocokkan aset dengan register BMD sebelum menyimpan.

## Alur penelusuran transaksi LRA

1. Pada input realisasi, pilih **Progres**, **LS**, **Uang Muka Kontrak**, **UP**, **TU**, atau **GU**. Satu transaksi harus dilengkapi dokumen sumber; nominal progres kontrak dibedakan dari nilai belanja LRA.
2. Untuk LS, uang muka kontrak, UP, dan TU, isi nilai belanja LRA per rekening pada rincian transaksi sesuai dokumen pembayaran/pertanggungjawaban yang disahkan. UP/TU memerlukan nomor register dan SP2D sumber serta nomor/tanggal SPJ dan tanggal pengesahannya. GU dicatat sebagai tautan dokumen pertanggungjawaban dan tidak menambah belanja LRA.
3. Pengguna berwenang selain penginput memeriksa bukti dan memilih **Verifikasi** di antrean LRA. Menghapus dokumen sumber membatalkan status verifikasi sehingga transaksi harus diperiksa kembali.
4. LRA hanya mengambil baris yang mempunyai snapshot anggaran disahkan, dokumen sumber aktif, jenis transaksi yang diakui, dan status verifikasi. Baris yang belum lengkap ditampilkan di antrean; laporan/ekspor menyatakan sumber yang belum tersedia.

Referensi nomor SP2D uang muka pada termin digunakan untuk penelusuran dokumen, tetapi modul belum menghitung saldo uang muka kontrak, pengembalian, atau potongan lintas transaksi secara otomatis. Demikian pula, pencatatan UP/TU belum membentuk subledger saldo kas muka dan penyelesaian pengembalian. Karena itu, rekonsiliasi uang muka dan UP/TU tetap harus dilakukan terhadap register/buku besar sebelum laporan resmi disahkan.

## Instalasi skema

Jalankan sekali pada tiap basis data aplikasi setelah deploy atau ketika migrasi laporan keuangan diperbarui:

```sh
php scripts/install_laporan_keuangan.php
```

Jalankan dari root proyek menggunakan akun migrasi database yang memiliki hak `CREATE` dan `ALTER` (variabel `SESENDOK_SETUP_ADMIN` dan `SESENDOK_SETUP_ADMIN_PASSWORD` bila bukan `root`). Skrip membuat tabel saldo Neraca, CaLK, dan metadata transaksi jika belum ada, serta menambah kolom nilai LRA per rekening pada transaksi realisasi; data yang sudah ada tidak dihapus.
