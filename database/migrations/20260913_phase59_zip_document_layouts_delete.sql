-- Dua lembar pada ZIP contoh yang sebelumnya belum menjadi master mandiri.
INSERT IGNORE INTO master_dokumen_pengadaan_neo
(kode,nama,kelompok,cara_pengadaan,jenis_pengadaan,metode_pemilihan,bentuk_kontrak,nilai_min,nilai_max,dasar_hukum,sumber_format,versi,berlaku_mulai,orientasi,username_insert)
VALUES
('LAMPIRAN_NEGOSIASI','Lampiran Rincian Penawaran Setelah Klarifikasi dan Negosiasi','PEMILIHAN','PENYEDIA','SEMUA','Pengadaan Langsung','SEMUA',0,NULL,'Pedoman pemilihan penyedia dan dokumen pemilihan yang berlaku.','Contoh SPK dan dokumen lainnya/7.pdf','2026.2','2025-04-30','L','migration_phase59'),
('DAFTAR_HADIR_KLARIFIKASI','Daftar Hadir Klarifikasi dan Negosiasi','PEMILIHAN','PENYEDIA','SEMUA','Pengadaan Langsung','SEMUA',0,NULL,'Pedoman pemilihan penyedia dan dokumen pemilihan yang berlaku.','Contoh SPK dan dokumen lainnya/8.pdf','2026.2','2025-04-30','P','migration_phase59');

-- Master fixed tetap memiliki satu bagian metadata agar pembuatan snapshot konsisten,
-- tetapi tidak menampilkan editor HTML kepada pengguna.
INSERT IGNORE INTO master_dokumen_pengadaan_bagian_neo
(master_id,kode_bagian,judul,urutan,isi_template,petunjuk_edit,dapat_diedit,wajib,username_insert)
SELECT id,'FIXED_LAYOUT','Format Tetap dari Dokumen Contoh',1,'','Data tabel diambil otomatis dari kontrak, RAB, penyedia, dan pejabat terkait.',0,0,'migration_phase59'
FROM master_dokumen_pengadaan_neo
WHERE kode IN ('LAMPIRAN_NEGOSIASI','DAFTAR_HADIR_KLARIFIKASI');

-- Tandai sumber format setiap dokumen ZIP agar pemetaan layout dapat diaudit.
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/2.pdf',versi='2026.2' WHERE kode='UNDANGAN_PL';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/3.pdf',versi='2026.2' WHERE kode='JADWAL_DOKUMEN';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/4.pdf',versi='2026.2' WHERE kode='BA_SURVEY_HARGA';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/5.pdf',versi='2026.2' WHERE kode='PAKTA_INTEGRITAS';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/6.pdf',versi='2026.2' WHERE kode='BA_KLARIFIKASI_NEGOSIASI';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/9.pdf',versi='2026.2' WHERE kode='PENETAPAN_PENYEDIA';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/10.pdf',versi='2026.2' WHERE kode='PENGUMUMAN_PENYEDIA';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/11.pdf',versi='2026.2' WHERE kode='BA_EVALUASI';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/12.pdf',versi='2026.2' WHERE kode='BAHP';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/13.pdf',versi='2026.2' WHERE kode='SPPBJ';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/14.pdf',versi='2026.2' WHERE kode='SPMK';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/15.pdf',versi='2026.2' WHERE kode='RAPAT_PRA_KONTRAK';
UPDATE master_dokumen_pengadaan_neo SET sumber_format='Contoh SPK dan dokumen lainnya/1.pdf dan 16.pdf',versi='2026.2' WHERE kode LIKE 'SPK_%';
