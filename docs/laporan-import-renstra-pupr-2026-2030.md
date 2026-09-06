# Laporan Import Renstra Dinas PUPR Kabupaten Pasangkayu

Tanggal pelaksanaan: 6 September 2026

## Ruang lingkup

- Wilayah: Kabupaten Pasangkayu (`76.01`)
- OPD: Dinas Pekerjaan Umum dan Penataan Ruang (`1.03.0.00.0.00.01.0000`)
- Periode sistem: 2026-2030
- Sumber: `Renstra 2025-2030.pdf`, terutama Tabel 4.3 dan Tabel 4.5

Dokumen menyebut target tahun 2025 sebagai tahun transisi, sedangkan periode RPJMD aktif dan lima kolom pagu pada sistem adalah 2026-2030. Karena itu `target_t1` sampai `target_t5` dipetakan ke 2026 sampai 2030.

## Hasil impor

| Entitas | Jumlah |
|---|---:|
| Renstra aktif | 1 |
| Misi | 1 |
| Tujuan | 1 |
| Sasaran | 1 |
| Program | 10 |
| Kegiatan | 23 |
| Subkegiatan | 73 |
| Indikator program | 10 |
| IKU resmi Tabel 4.5 | 7 |
| Referensi SSH placeholder | 5 |
| Batas pagu dokumen | 30 |

## Pagu indikatif

| Tahun | Pagu maksimum per dokumen |
|---:|---:|
| 2026 | Rp241.474.151.501 |
| 2027 | Rp254.219.649.084 |
| 2028 | Rp271.786.423.875 |
| 2029 | Rp297.348.098.734 |
| 2030 | Rp296.848.098.734 |

Untuk setiap tahun, batas yang sama dipasang pada Renja, RKA, DPA, Perubahan Renja, Perubahan RKA, dan DPPA. Baris rincian dokumen tahunan sengaja dibiarkan kosong agar disusun pengguna melalui alur aplikasi dan tidak dianggap sebagai dokumen resmi yang telah ditetapkan.

## IKU Tabel 4.5

1. Indeks Infrastruktur.
2. Persentase tingkat kondisi jalan kabupaten/kota baik dan sedang.
3. Persentase laporan yang disusun tepat waktu.
4. Persentase tersedianya luasan RTH publik sebesar 20% dari luas wilayah kota/kawasan.
5. Persentase jumlah penduduk yang terlayani air bersih.
6. Pengaturan NSPK dalam kegiatan pengawasan.
7. Persentase rumah tinggal bersanitasi.

IKU dihubungkan ke Renstra, sasaran strategis, dan program terkait jika indikator mempunyai program yang langsung relevan. Indeks Infrastruktur dipertahankan sebagai IKU lintas-program.

## Data dummy yang dihapus

- 2 Renstra lama dan seluruh hierarkinya.
- Baris RKPD, Renja, RKA, DPA, RKPD Perubahan, Renja Perubahan, RKA Perubahan, dan DPPA PUPR tahun 2025-2030.
- 5 kontrak, 5 rincian kontrak, 7 realisasi, 7 RAB, 2 jadwal kontrak, dan 2 dokumen kontrak berlabel `TRACE_TEST`/`TRACE_PHASE`.

## Perubahan aplikasi dan skema

- Subkegiatan Renstra sekarang menyimpan `indikator_keluaran` dan `satuan`.
- IKU sekarang memiliki relasi eksplisit `program_renstra_id`.
- Form IKU menyediakan dropdown Program Renstra yang dibatasi menurut OPD aktif.
- Importer transaksional tersedia di `scripts/import_pupr_renstra_2026_2030.php`.
- SSH `PUPR-PAGU-2026` sampai `PUPR-PAGU-2030` bernilai satu rupiah dibuat sebagai placeholder terkendali. Komponen ini wajib diganti atau dirinci saat penyusunan RKA/DPA.

## Status dokumen tahunan

Renja sampai DPPA belum diberi baris belanja otomatis. Keputusan ini menjaga pemisahan antara pagu indikatif Renstra dan rincian anggaran tahunan. Pengguna OPD dapat menyusun rincian, tetapi sistem akan menolak total yang melewati batas pagu yang ditetapkan TAPD.
