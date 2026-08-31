# Mapping Standar Harga, Rekening Belanja, dan Aset

## Prinsip data

1. `master_biaya.kode_aset` menunjuk kode aktif pada `aset_neo.kode` dan bukan
   teks bebas.
2. Hubungan standar harga ke rekening belanja tetap banyak-ke-banyak melalui
   `master_biaya_akun`. Kode rekening dan kode barang/aset tidak disamakan.
3. Saat komponen dipakai pada Renja/RKA/DPA/perubahan, backend memeriksa
   pasangan `id_standar_harga + jenis_standar_harga + kd_akun`, scope wilayah,
   tahun, dan keberadaan kode aset aktif.
4. LRA mengambil klasifikasi rekening belanja. Pelaporan aset/neraca mengambil
   klasifikasi barang/aset. Jejak standar harga menghubungkan keduanya tanpa
   mengubah makna masing-masing klasifikasi.

## Dasar regulasi yang diperiksa

- Permendagri 90 Tahun 2019: klasifikasi, kodefikasi, dan nomenklatur
  perencanaan pembangunan dan keuangan daerah untuk konsistensi SIPD.
- Kepmendagri 050-5889 Tahun 2021: pemutakhiran klasifikasi dan nomenklatur
  Permendagri 90.
- Permendagri 77 Tahun 2020: pedoman teknis pengelolaan keuangan daerah.
- PP 71 Tahun 2010: Standar Akuntansi Pemerintahan dan penyajian laporan
  keuangan pemerintah.
- Permendagri 47 Tahun 2021: pembukuan, inventarisasi, dan pelaporan Barang
  Milik Daerah.
- Perpres 72 Tahun 2025: Standar Harga Satuan Regional yang berlaku dan
  mencabut Perpres 33 Tahun 2020 beserta perubahannya.

Referensi resmi:

- https://peraturan.bpk.go.id/Details/139075/permendagri-no-90-tahun-2019
- https://peraturan.bpk.go.id/Details/162792/permendagri-no-77-tahun-%20202
- https://peraturan.bpk.go.id/Details/196220/permendagri-no-47-tahun-2021
- https://peraturan.bpk.go.id/Details/5095/pp-no-71-tahun-2010
- https://peraturan.bpk.go.id/Details/321610/perpres-no-72-tahun-2025

## Audit data lokal 1 September 2026

Seluruh 41 baris aktif saat audit adalah data dummy berawalan `TRACE` dan belum
memiliki kode aset referensial; 40 di antaranya juga belum memiliki mapping
rekening. Data tersebut tidak dikoreksi ke kode arbitrer karena mapping harus
ditentukan menurut substansi barang/jasa dan peraturan kepala daerah yang
berlaku. Input, edit, serta import baru sekarang ditolak jika kode aset tidak
valid; pemakaian pada rincian anggaran ditolak jika pasangan rekening belum
dimapping.

