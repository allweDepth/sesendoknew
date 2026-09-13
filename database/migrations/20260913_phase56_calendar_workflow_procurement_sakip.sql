-- Phase 56: workflow TAPD, master dokumen pengadaan, dan masa berlaku struktur.
-- Acuan bentuk kontrak: Perpres 16/2018 jo. 12/2021 jo. 46/2025 Pasal 28.

ALTER TABLE struktur_jabatan_opd_neo
  ADD COLUMN IF NOT EXISTS parent_kd_opd VARCHAR(60) NULL AFTER parent_id,
  ADD COLUMN IF NOT EXISTS nomor_sk_pengangkatan VARCHAR(150) NULL AFTER eselon,
  ADD COLUMN IF NOT EXISTS tanggal_sk_pengangkatan DATE NULL AFTER nomor_sk_pengangkatan,
  ADD COLUMN IF NOT EXISTS tmt_jabatan DATE NULL AFTER tanggal_sk_pengangkatan,
  ADD COLUMN IF NOT EXISTS berlaku_mulai DATE NULL AFTER tmt_jabatan,
  ADD COLUMN IF NOT EXISTS berlaku_sampai DATE NULL AFTER berlaku_mulai,
  ADD COLUMN IF NOT EXISTS status_jabatan ENUM('AKTIF','BERAKHIR') NOT NULL DEFAULT 'AKTIF' AFTER berlaku_sampai,
  ADD KEY IF NOT EXISTS idx_struktur_jabatan_berlaku (kd_wilayah,kd_opd,tahun,status_jabatan,berlaku_mulai,berlaku_sampai);

-- Riwayat jabatan tidak boleh ditimpa. Satu nama jabatan boleh memiliki beberapa periode SK.
SET @phase56_drop_old_structure_index = IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='struktur_jabatan_opd_neo' AND INDEX_NAME='uq_struktur_jabatan_scope'),
  'ALTER TABLE struktur_jabatan_opd_neo DROP INDEX uq_struktur_jabatan_scope',
  'SELECT 1'
);
PREPARE phase56_stmt FROM @phase56_drop_old_structure_index;
EXECUTE phase56_stmt;
DEALLOCATE PREPARE phase56_stmt;
ALTER TABLE struktur_jabatan_opd_neo
  ADD UNIQUE KEY IF NOT EXISTS uq_struktur_jabatan_periode (kd_wilayah,kd_opd,tahun,nama_jabatan,berlaku_mulai,is_deleted);

UPDATE struktur_jabatan_opd_neo
SET berlaku_mulai=COALESCE(berlaku_mulai,tmt_jabatan,tanggal_sk_pengangkatan,MAKEDATE(tahun,1))
WHERE berlaku_mulai IS NULL;

ALTER TABLE riwayat_jabatan_neo
  ADD COLUMN IF NOT EXISTS sumber_struktur_id BIGINT UNSIGNED NULL AFTER pegawai_id,
  ADD KEY IF NOT EXISTS idx_rj_sumber_struktur (sumber_struktur_id);

ALTER TABLE iku_opd_neo
  ADD COLUMN IF NOT EXISTS penanggung_jawab_struktur_id BIGINT UNSIGNED NULL AFTER penanggung_jawab_pegawai_id,
  ADD KEY IF NOT EXISTS idx_iku_penanggung_struktur (penanggung_jawab_struktur_id);
ALTER TABLE pohon_kinerja_neo
  ADD COLUMN IF NOT EXISTS penanggung_jawab_struktur_id BIGINT UNSIGNED NULL AFTER penanggung_jawab_pegawai_id,
  ADD KEY IF NOT EXISTS idx_pohon_penanggung_struktur (penanggung_jawab_struktur_id);
ALTER TABLE perjanjian_kinerja_neo
  ADD COLUMN IF NOT EXISTS pihak_pertama_struktur_id BIGINT UNSIGNED NULL AFTER pihak_pertama_pegawai_id,
  ADD COLUMN IF NOT EXISTS pihak_kedua_struktur_id BIGINT UNSIGNED NULL AFTER pihak_kedua_pegawai_id,
  ADD KEY IF NOT EXISTS idx_pk_pihak_struktur (pihak_pertama_struktur_id,pihak_kedua_struktur_id);

CREATE TABLE IF NOT EXISTS anggaran_approval_log_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
  dokumen VARCHAR(30) NOT NULL, kd_sub_keg VARCHAR(100) NOT NULL,
  aksi ENUM('BUKA','KUNCI','KUNCI_SALIN','KUNCI_GANTI_TUJUAN') NOT NULL,
  dokumen_tujuan VARCHAR(30) NULL, jumlah_sumber INT NOT NULL DEFAULT 0,
  jumlah_baru INT NOT NULL DEFAULT 0, jumlah_diperbarui INT NOT NULL DEFAULT 0,
  jumlah_dihapus INT NOT NULL DEFAULT 0, username VARCHAR(100) NOT NULL,
  tgl_aksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_approval_scope (kd_wilayah,kd_opd,tahun,dokumen,kd_sub_keg),
  KEY idx_approval_time (tgl_aksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE kontrak_neo
  ADD COLUMN IF NOT EXISTS cara_pengadaan ENUM('PENYEDIA','SWAKELOLA') NOT NULL DEFAULT 'PENYEDIA' AFTER tahap,
  ADD COLUMN IF NOT EXISTS jenis_pengadaan ENUM('BARANG','PEKERJAAN_KONSTRUKSI','JASA_LAINNYA','KONSULTANSI_KONSTRUKSI','KONSULTANSI_NON_KONSTRUKSI') NOT NULL DEFAULT 'BARANG' AFTER cara_pengadaan,
  ADD COLUMN IF NOT EXISTS metode_pemilihan VARCHAR(80) NULL AFTER jenis_pengadaan,
  ADD COLUMN IF NOT EXISTS tipe_swakelola ENUM('I','II','III','IV') NULL AFTER metode_pemilihan,
  ADD COLUMN IF NOT EXISTS bentuk_kontrak ENUM('BUKTI_PEMBELIAN','KUITANSI','SPK','SURAT_PERJANJIAN','SURAT_PESANAN','PERJANJIAN_SWAKELOLA') NULL AFTER tipe_swakelola,
  ADD COLUMN IF NOT EXISTS nomor_rup VARCHAR(100) NULL AFTER bentuk_kontrak,
  ADD COLUMN IF NOT EXISTS nilai_hps DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER total_anggaran;

CREATE TABLE IF NOT EXISTS master_dokumen_pengadaan_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(80) NOT NULL, nama VARCHAR(255) NOT NULL,
  kelompok ENUM('PERSIAPAN','PEMILIHAN','KONTRAK','PELAKSANAAN','SWAKELOLA') NOT NULL,
  cara_pengadaan ENUM('SEMUA','PENYEDIA','SWAKELOLA') NOT NULL DEFAULT 'SEMUA',
  jenis_pengadaan ENUM('SEMUA','BARANG','PEKERJAAN_KONSTRUKSI','JASA_LAINNYA','KONSULTANSI_KONSTRUKSI','KONSULTANSI_NON_KONSTRUKSI') NOT NULL DEFAULT 'SEMUA',
  metode_pemilihan VARCHAR(80) NULL, tipe_swakelola ENUM('I','II','III','IV') NULL,
  bentuk_kontrak ENUM('SEMUA','BUKTI_PEMBELIAN','KUITANSI','SPK','SURAT_PERJANJIAN','SURAT_PESANAN','PERJANJIAN_SWAKELOLA') NOT NULL DEFAULT 'SEMUA',
  nilai_min DECIMAL(20,2) NOT NULL DEFAULT 0, nilai_max DECIMAL(20,2) NULL,
  dasar_hukum TEXT NOT NULL, sumber_format VARCHAR(500) NULL,
  versi VARCHAR(40) NOT NULL DEFAULT '2025.1', berlaku_mulai DATE NOT NULL,
  berlaku_sampai DATE NULL, orientasi ENUM('P','L') NOT NULL DEFAULT 'P',
  aktif TINYINT NOT NULL DEFAULT 1, tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP,
  username_insert VARCHAR(100) NULL, tgl_update DATETIME NULL, username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_master_dokumen_versi (kode,versi),
  KEY idx_master_dokumen_rule (cara_pengadaan,jenis_pengadaan,bentuk_kontrak,nilai_min,nilai_max,aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_dokumen_pengadaan_bagian_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, master_id BIGINT UNSIGNED NOT NULL,
  kode_bagian VARCHAR(80) NOT NULL, judul VARCHAR(255) NOT NULL, urutan SMALLINT NOT NULL DEFAULT 1,
  isi_template LONGTEXT NOT NULL, petunjuk_edit TEXT NULL,
  dapat_diedit TINYINT NOT NULL DEFAULT 1, wajib TINYINT NOT NULL DEFAULT 1,
  format_data JSON NULL, tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP,
  username_insert VARCHAR(100) NULL, tgl_update DATETIME NULL, username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_master_bagian (master_id,kode_bagian), KEY idx_master_bagian_urut (master_id,urutan,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dokumen_pengadaan_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kontrak_id BIGINT NOT NULL,
  master_id BIGINT UNSIGNED NOT NULL, kode_dokumen VARCHAR(80) NOT NULL,
  nomor_dokumen VARCHAR(150) NULL, tanggal_dokumen DATE NOT NULL, judul VARCHAR(350) NOT NULL,
  status ENUM('DRAFT','DIAJUKAN','DISETUJUI','DITANDATANGANI','DIBATALKAN') NOT NULL DEFAULT 'DRAFT',
  data_isian JSON NULL, dasar_hukum_snapshot TEXT NOT NULL, versi_master VARCHAR(40) NOT NULL,
  pembuat_pegawai_id BIGINT NULL, pembuat_role VARCHAR(50) NULL,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  KEY idx_hasil_dokumen_kontrak (kontrak_id,kode_dokumen,is_deleted),
  KEY idx_hasil_dokumen_scope (kd_wilayah,kd_opd,tahun,status,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dokumen_pengadaan_bagian_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, dokumen_id BIGINT UNSIGNED NOT NULL,
  master_bagian_id BIGINT UNSIGNED NULL, kode_bagian VARCHAR(80) NOT NULL,
  judul VARCHAR(255) NOT NULL, urutan SMALLINT NOT NULL DEFAULT 1, isi LONGTEXT NOT NULL,
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_hasil_bagian (dokumen_id,kode_bagian), KEY idx_hasil_bagian_urut (dokumen_id,urutan,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dokumen_pengadaan_lampiran_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, dokumen_id BIGINT UNSIGNED NOT NULL,
  jenis_lampiran VARCHAR(100) NOT NULL, judul VARCHAR(255) NOT NULL, urutan SMALLINT NOT NULL DEFAULT 1,
  isi LONGTEXT NULL, nama_file VARCHAR(255) NULL, path_file VARCHAR(500) NULL, mime_type VARCHAR(120) NULL,
  ukuran BIGINT NOT NULL DEFAULT 0, tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP,
  username_insert VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  KEY idx_lampiran_dokumen (dokumen_id,urutan,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO master_dokumen_pengadaan_neo
(kode,nama,kelompok,cara_pengadaan,jenis_pengadaan,metode_pemilihan,bentuk_kontrak,nilai_min,nilai_max,dasar_hukum,sumber_format,versi,berlaku_mulai,orientasi,username_insert)
VALUES
('NOTA_PESANAN','Nota/Bukti Pembelian','KONTRAK','PENYEDIA','BARANG','Pengadaan Langsung','BUKTI_PEMBELIAN',0,10000000,'Perpres 16/2018 jo. Perpres 12/2021 jo. Perpres 46/2025 Pasal 28 ayat (2).','SBD LKPP dan bukti transaksi yang sah','2025.1','2025-04-30','P','migration_phase56'),
('KUITANSI','Kuitansi Pengadaan','KONTRAK','PENYEDIA','BARANG','Pengadaan Langsung','KUITANSI',10000000.01,50000000,'Perpres 16/2018 jo. Perpres 12/2021 jo. Perpres 46/2025 Pasal 28 ayat (3).','SBD LKPP dan ketentuan bukti pembayaran APBD','2025.1','2025-04-30','P','migration_phase56'),
('SPK_BARANG','Surat Perintah Kerja Pengadaan Barang','KONTRAK','PENYEDIA','BARANG','Pengadaan Langsung','SPK',50000000.01,200000000,'Perpres 46/2025 Pasal 28 ayat (4); PerLKPP 12/2021 sepanjang tidak bertentangan.','SBD LKPP Pengadaan Barang','2025.1','2025-04-30','P','migration_phase56'),
('SPK_JASA_LAINNYA','Surat Perintah Kerja Jasa Lainnya','KONTRAK','PENYEDIA','JASA_LAINNYA','Pengadaan Langsung','SPK',50000000.01,200000000,'Perpres 46/2025 Pasal 28 ayat (4); PerLKPP 12/2021 sepanjang tidak bertentangan.','SBD LKPP Jasa Lainnya','2025.1','2025-04-30','P','migration_phase56'),
('SPK_KONSTRUKSI','Surat Perintah Kerja Pekerjaan Konstruksi','KONTRAK','PENYEDIA','PEKERJAAN_KONSTRUKSI','Pengadaan Langsung','SPK',0,400000000,'Perpres 46/2025 Pasal 28 ayat (4), SE Kepala LKPP 1/2025, dan ketentuan pengadaan jasa konstruksi.','Model Dokumen Pemilihan Kementerian PU dan contoh SPK terunggah','2025.1','2025-04-30','P','migration_phase56'),
('SPK_KONSULTANSI_KONSTRUKSI','Surat Perintah Kerja Konsultansi Konstruksi','KONTRAK','PENYEDIA','KONSULTANSI_KONSTRUKSI','Pengadaan Langsung','SPK',0,100000000,'Perpres 46/2025 Pasal 28 ayat (4) dan ketentuan pengadaan jasa konstruksi.','Model Dokumen Pemilihan Kementerian PU dan contoh SPK terunggah','2025.1','2025-04-30','P','migration_phase56'),
('SPK_KONSULTANSI_NON_KONSTRUKSI','Surat Perintah Kerja Konsultansi Non Konstruksi','KONTRAK','PENYEDIA','KONSULTANSI_NON_KONSTRUKSI','Pengadaan Langsung','SPK',0,100000000,'Perpres 46/2025 Pasal 28 ayat (4); PerLKPP 12/2021 sepanjang tidak bertentangan.','SBD LKPP Jasa Konsultansi Badan Usaha/Perorangan','2025.1','2025-04-30','P','migration_phase56'),
('SURAT_PERJANJIAN','Surat Perjanjian/Kontrak','KONTRAK','PENYEDIA','SEMUA',NULL,'SURAT_PERJANJIAN',0,NULL,'Perpres 46/2025 Pasal 27, 27A, dan 28 ayat (5).','SBD sesuai jenis pengadaan; ambang diverifikasi oleh aplikasi','2025.1','2025-04-30','P','migration_phase56'),
('SURAT_PESANAN','Surat/Bukti Pesanan E-purchasing','KONTRAK','PENYEDIA','SEMUA','E-purchasing','SURAT_PESANAN',0,NULL,'Perpres 46/2025 Pasal 28 ayat (6).','Katalog Elektronik/SPSE','2025.1','2025-04-30','P','migration_phase56'),
('UNDANGAN_PL','Undangan Pengadaan Langsung','PEMILIHAN','PENYEDIA','SEMUA','Pengadaan Langsung','SEMUA',0,NULL,'Perpres 46/2025 dan PerLKPP 12/2021 sepanjang tidak bertentangan.','SBD LKPP/Kementerian PU dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('JADWAL_DOKUMEN','Jadwal dan Kelengkapan Dokumen','PEMILIHAN','PENYEDIA','SEMUA','Pengadaan Langsung','SEMUA',0,NULL,'Perpres 46/2025 dan dokumen pemilihan yang berlaku.','Contoh terunggah','2025.1','2025-04-30','L','migration_phase56'),
('BA_SURVEY_HARGA','Berita Acara Survei Harga','PERSIAPAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan pedoman persiapan pengadaan.','Contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('PAKTA_INTEGRITAS','Pakta Integritas','PEMILIHAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Etika pengadaan pada Perpres 16/2018 beserta perubahannya.','SBD LKPP/Kementerian PU dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('BA_EVALUASI','Berita Acara Evaluasi Penawaran','PEMILIHAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan pedoman pemilihan penyedia.','SBD LKPP/Kementerian PU dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('BA_KLARIFIKASI_NEGOSIASI','Berita Acara Klarifikasi dan Negosiasi','PEMILIHAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan pedoman pemilihan penyedia.','SBD LKPP/Kementerian PU dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('BA_AANWIJZING','Berita Acara Pemberian Penjelasan/Aanwijzing','PEMILIHAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan dokumen pemilihan yang berlaku.','SBD LKPP/Kementerian PU','2025.1','2025-04-30','P','migration_phase56'),
('BAHP','Berita Acara Hasil Pemilihan','PEMILIHAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan pedoman pemilihan penyedia.','SBD LKPP/Kementerian PU dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('PENETAPAN_PENYEDIA','Penetapan Penyedia','PEMILIHAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan pedoman pemilihan penyedia.','SBD dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('PENGUMUMAN_PENYEDIA','Pengumuman Penyedia','PEMILIHAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan pedoman pemilihan penyedia.','SBD dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('SPPBJ','Surat Penunjukan Penyedia Barang/Jasa','KONTRAK','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan pedoman pemilihan penyedia.','SBD LKPP/Kementerian PU dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('SPMK','Surat Perintah Mulai Kerja','PELAKSANAAN','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan syarat kontrak yang berlaku.','SBD LKPP/Kementerian PU dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('RAPAT_PRA_KONTRAK','Berita Acara Rapat Pra Kontrak','KONTRAK','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan syarat kontrak yang berlaku.','SBD dan contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('SSUK','Syarat-Syarat Umum Kontrak/SPK','KONTRAK','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan peraturan pelaksana yang masih berlaku.','SBD LKPP/Kementerian PU; SSUK contoh terunggah','2025.1','2025-04-30','P','migration_phase56'),
('SSKK','Syarat-Syarat Khusus Kontrak','KONTRAK','PENYEDIA','SEMUA',NULL,'SEMUA',0,NULL,'Perpres 46/2025 dan peraturan pelaksana yang masih berlaku.','SBD LKPP/Kementerian PU','2025.1','2025-04-30','P','migration_phase56');

INSERT IGNORE INTO master_dokumen_pengadaan_neo
(kode,nama,kelompok,cara_pengadaan,jenis_pengadaan,tipe_swakelola,bentuk_kontrak,nilai_min,dasar_hukum,sumber_format,versi,berlaku_mulai,orientasi,username_insert)
VALUES
('SWAKELOLA_I','Dokumen Swakelola Tipe I','SWAKELOLA','SWAKELOLA','SEMUA','I','PERJANJIAN_SWAKELOLA',0,'PerLKPP 3/2021 dan Keputusan Deputi I LKPP 2/2022.','Model Dokumen Swakelola LKPP Tipe I','2025.1','2025-04-30','P','migration_phase56'),
('SWAKELOLA_II','Kesepakatan/Perjanjian Swakelola Tipe II','SWAKELOLA','SWAKELOLA','SEMUA','II','PERJANJIAN_SWAKELOLA',0,'PerLKPP 3/2021 dan Keputusan Deputi I LKPP 2/2022.','Model Dokumen Swakelola LKPP Tipe II','2025.1','2025-04-30','P','migration_phase56'),
('SWAKELOLA_III','Kontrak Swakelola Tipe III','SWAKELOLA','SWAKELOLA','SEMUA','III','PERJANJIAN_SWAKELOLA',0,'PerLKPP 3/2021 dan Keputusan Deputi I LKPP 2/2022.','Model Dokumen Swakelola LKPP Tipe III','2025.1','2025-04-30','P','migration_phase56'),
('SWAKELOLA_IV','Kontrak Swakelola Tipe IV','SWAKELOLA','SWAKELOLA','SEMUA','IV','PERJANJIAN_SWAKELOLA',0,'PerLKPP 3/2021 dan Keputusan Deputi I LKPP 2/2022.','Model Dokumen Swakelola LKPP Tipe IV','2025.1','2025-04-30','P','migration_phase56');

-- Setiap master memiliki bagian snapshot yang dapat disunting OPD ketika dokumen dibuat.
INSERT IGNORE INTO master_dokumen_pengadaan_bagian_neo
(master_id,kode_bagian,judul,urutan,isi_template,petunjuk_edit,dapat_diedit,wajib,username_insert)
SELECT id,'IDENTITAS','Identitas Dokumen',1,
'<p><b>{{nama_opd}}</b></p><p><b>{{nama_dokumen}}</b><br>Nomor: {{nomor_dokumen}}<br>Tanggal: {{tanggal_dokumen}}</p>',
'Periksa nomor, tanggal, paket, sumber dana, pejabat, dan penyedia sebelum ditandatangani.',1,1,'migration_phase56'
FROM master_dokumen_pengadaan_neo;

-- Klausul khusus tetap dipisah supaya hasil dokumen dapat diedit tanpa merusak master.
INSERT IGNORE INTO master_dokumen_pengadaan_bagian_neo
(master_id,kode_bagian,judul,urutan,isi_template,petunjuk_edit,dapat_diedit,wajib,username_insert)
SELECT id,'KLAUSUL_KONTRAK','Ketentuan Kontrak',4,
'<ol><li>Ruang lingkup, keluaran, spesifikasi/KAK, personel, dan lokasi pekerjaan.</li><li>Jangka waktu, jadwal, pemeriksaan, serah terima, masa pemeliharaan bila berlaku.</li><li>Harga, perpajakan, cara pembayaran, jaminan, dan denda keterlambatan.</li><li>Perubahan kontrak, keadaan kahar, penghentian/pemutusan, penyelesaian sengketa, integritas, dan sanksi.</li><li>Kewajiban penggunaan produk dalam negeri/TKDN dan keselamatan konstruksi/K3 sesuai jenis paket.</li></ol>',
'Lengkapi nilai dan pilihan klausul berdasarkan rancangan kontrak, hasil pemilihan, serta SBD yang berlaku untuk jenis paket.',1,1,'migration_phase56'
FROM master_dokumen_pengadaan_neo WHERE kelompok='KONTRAK';

INSERT IGNORE INTO master_dokumen_pengadaan_bagian_neo
(master_id,kode_bagian,judul,urutan,isi_template,petunjuk_edit,dapat_diedit,wajib,username_insert)
SELECT id,'HASIL_PEMILIHAN','Hasil Pemeriksaan/Evaluasi',4,
'<table border="1" cellpadding="4"><tr><th>Tahap</th><th>Hasil</th><th>Keterangan</th></tr><tr><td>Administrasi dan kualifikasi</td><td>Memenuhi/Tidak</td><td></td></tr><tr><td>Teknis</td><td>Memenuhi/Tidak</td><td></td></tr><tr><td>Harga/biaya</td><td>Wajar/Tidak</td><td></td></tr><tr><td>Klarifikasi/negosiasi</td><td></td><td></td></tr></table>',
'Sesuaikan tahap dengan metode pemilihan dan lampirkan daftar hadir, penawaran, hasil evaluasi, serta hasil negosiasi.',1,1,'migration_phase56'
FROM master_dokumen_pengadaan_neo WHERE kelompok='PEMILIHAN';

INSERT IGNORE INTO master_dokumen_pengadaan_bagian_neo
(master_id,kode_bagian,judul,urutan,isi_template,petunjuk_edit,dapat_diedit,wajib,username_insert)
SELECT id,'PELAKSANAAN_SWAKELOLA','Organisasi dan Pelaksanaan Swakelola',4,
'<ol><li>Penetapan Tim Persiapan, Tim Pelaksana, dan Tim Pengawas beserta tugasnya.</li><li>Sasaran, keluaran, rencana kegiatan, jadwal, RAB, dan sumber daya.</li><li>Tata cara pembayaran, pelaporan, pengawasan, pemeriksaan hasil, dan serah terima.</li><li>Pengadaan barang/jasa melalui penyedia di dalam Swakelola mengikuti ketentuan yang berlaku.</li></ol>',
'Sesuaikan para pihak: Tipe I oleh K/L/PD penanggung jawab; Tipe II oleh K/L/PD lain; Tipe III oleh Ormas; Tipe IV oleh Kelompok Masyarakat.',1,1,'migration_phase56'
FROM master_dokumen_pengadaan_neo WHERE cara_pengadaan='SWAKELOLA';

INSERT IGNORE INTO master_dokumen_pengadaan_bagian_neo
(master_id,kode_bagian,judul,urutan,isi_template,petunjuk_edit,dapat_diedit,wajib,username_insert)
SELECT id,'ISI','Isi Pokok',2,
'<p>Dokumen ini dibuat untuk paket <b>{{nama_paket}}</b>, sub kegiatan {{kd_sub_keg}}, sumber dana {{sumber_dana}}, dengan nilai Rp {{nilai_kontrak}}.</p><p>Para pihak wajib melaksanakan ketentuan ruang lingkup, mutu, waktu, biaya, produk dalam negeri/TKDN, keselamatan, integritas, pelaporan, pembayaran, perubahan, sanksi, dan serah terima sesuai jenis pengadaan serta peraturan yang berlaku.</p>',
'Sesuaikan substansi dengan KAK/spesifikasi, hasil pemilihan, rancangan kontrak, dan kondisi paket. Jangan menghapus klausul wajib.',1,1,'migration_phase56'
FROM master_dokumen_pengadaan_neo;

INSERT IGNORE INTO master_dokumen_pengadaan_bagian_neo
(master_id,kode_bagian,judul,urutan,isi_template,petunjuk_edit,dapat_diedit,wajib,username_insert)
SELECT id,'PENANDATANGAN','Penandatangan',3,
'<table cellpadding="6"><tr><td width="50%" align="center">Pejabat/PPK<br><br><br><b>{{nama_ppk}}</b><br>NIP {{nip_ppk}}</td><td width="50%" align="center">Penyedia/Pelaksana Swakelola<br><br><br><b>{{nama_penyedia}}</b></td></tr></table>',
'Nama dan kewenangan penandatangan diambil dari struktur/penugasan aktif pada tanggal dokumen.',1,1,'migration_phase56'
FROM master_dokumen_pengadaan_neo;
