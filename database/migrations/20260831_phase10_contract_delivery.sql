ALTER TABLE rab_paket_neo
  ADD COLUMN IF NOT EXISTS kontrak_id BIGINT NULL AFTER id,
  ADD COLUMN IF NOT EXISTS kontrak_item_id BIGINT UNSIGNED NULL AFTER kontrak_id,
  ADD COLUMN IF NOT EXISTS bobot DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER jumlah_negoisasi,
  ADD INDEX IF NOT EXISTS idx_rab_kontrak (kontrak_id),
  ADD INDEX IF NOT EXISTS idx_rab_kontrak_item (kontrak_item_id);

ALTER TABLE daftar_realisasi_neo
  ADD COLUMN IF NOT EXISTS rab_id INT NULL AFTER kontrak_id,
  ADD COLUMN IF NOT EXISTS progress_keuangan DECIMAL(7,2) NOT NULL DEFAULT 0 AFTER progress_fisik,
  ADD COLUMN IF NOT EXISTS uraian_progress TEXT NULL AFTER progress_keuangan,
  ADD INDEX IF NOT EXISTS idx_realisasi_rab (rab_id);

CREATE TABLE IF NOT EXISTS kontrak_jadwal_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kontrak_id BIGINT NOT NULL, rab_id INT NULL,
  minggu_ke SMALLINT NOT NULL, tanggal_mulai DATE NOT NULL, tanggal_selesai DATE NOT NULL,
  bobot_rencana DECIMAL(8,4) NOT NULL DEFAULT 0, bobot_realisasi DECIMAL(8,4) NOT NULL DEFAULT 0,
  rencana_kumulatif DECIMAL(8,4) NOT NULL DEFAULT 0, realisasi_kumulatif DECIMAL(8,4) NOT NULL DEFAULT 0,
  keterangan VARCHAR(400) NULL, kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), tahun YEAR,
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100),
  tgl_update DATETIME NULL, username_update VARCHAR(100), is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_kontrak_minggu (kontrak_id,minggu_ke), INDEX idx_jadwal_scope(kd_wilayah,kd_opd,tahun)
);

CREATE TABLE IF NOT EXISTS kontrak_dokumen_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kontrak_id BIGINT NOT NULL,
  jenis_dokumen ENUM('KONTRAK','SPK','SPMK','SSKK','SSUK','RAB','JADWAL','KURVA_S','GAMBAR','BAST','PHO','FHO','ADENDUM','JAMINAN','LAPORAN','LAINNYA') NOT NULL,
  nomor_dokumen VARCHAR(150) NULL, tanggal_dokumen DATE NULL, judul VARCHAR(300) NOT NULL,
  nama_file_asli VARCHAR(255) NOT NULL, path_file VARCHAR(500) NOT NULL, mime_type VARCHAR(120), ukuran BIGINT NOT NULL DEFAULT 0,
  versi SMALLINT NOT NULL DEFAULT 1, keterangan TEXT NULL, kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), tahun YEAR,
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100), is_deleted TINYINT NOT NULL DEFAULT 0,
  INDEX idx_dok_kontrak(kontrak_id,jenis_dokumen), INDEX idx_dok_scope(kd_wilayah,kd_opd,tahun)
);

CREATE TABLE IF NOT EXISTS user_subkegiatan_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, kd_sub_keg VARCHAR(80) NOT NULL,
  peran ENUM('KEPALA_OPD','PA_KPA','PPK','PPTK','PPK_SKPD','BENDAHARA','PEJABAT_PENGADAAN','STAF','VIEWER') NOT NULL,
  dapat_lihat TINYINT NOT NULL DEFAULT 1, dapat_input TINYINT NOT NULL DEFAULT 0,
  dapat_setujui TINYINT NOT NULL DEFAULT 0, dapat_hapus TINYINT NOT NULL DEFAULT 0,
  berlaku_mulai DATE NOT NULL, berlaku_sampai DATE NOT NULL,
  kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), tahun YEAR, keterangan VARCHAR(400),
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100), is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_user_sub_periode(user_id,kd_sub_keg,peran,berlaku_mulai), INDEX idx_user_sub_scope(kd_wilayah,kd_opd,tahun)
);

CREATE TABLE IF NOT EXISTS kop_surat_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), tahun YEAR,
  nama_pemerintah VARCHAR(250) NOT NULL, nama_opd VARCHAR(350) NOT NULL, alamat VARCHAR(500), telepon VARCHAR(100),
  email VARCHAR(150), website VARCHAR(150), kode_pos VARCHAR(20), logo_kiri VARCHAR(500), logo_kanan VARCHAR(500),
  gambar_kop VARCHAR(500), gunakan_gambar_kop TINYINT NOT NULL DEFAULT 0, warna_garis VARCHAR(20) DEFAULT '#000000',
  aktif TINYINT NOT NULL DEFAULT 1, tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100),
  tgl_update DATETIME NULL, username_update VARCHAR(100), is_deleted TINYINT NOT NULL DEFAULT 0,
  INDEX idx_kop_scope(kd_wilayah,kd_opd,tahun,aktif)
);

CREATE TABLE IF NOT EXISTS absensi_pegawai_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, pegawai_id INT NOT NULL, tanggal DATE NOT NULL,
  jam_masuk TIME NULL, jam_pulang TIME NULL, status ENUM('HADIR','DINAS_LUAR','IZIN','SAKIT','CUTI','ALPA','WFH') NOT NULL DEFAULT 'HADIR',
  latitude_masuk DECIMAL(10,7) NULL, longitude_masuk DECIMAL(10,7) NULL, latitude_pulang DECIMAL(10,7) NULL, longitude_pulang DECIMAL(10,7) NULL,
  foto_masuk VARCHAR(500), foto_pulang VARCHAR(500), keterangan VARCHAR(400), kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), tahun YEAR,
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100), tgl_update DATETIME NULL, username_update VARCHAR(100), is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_absen_pegawai_tanggal(pegawai_id,tanggal), INDEX idx_absen_scope(kd_wilayah,kd_opd,tahun,tanggal)
);

CREATE TABLE IF NOT EXISTS dokumen_pegawai_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, pegawai_id INT NOT NULL,
  jenis_dokumen ENUM('KTP','KK','AKTA_LAHIR','IJAZAH','SK_CPNS','SK_PNS','SK_PANGKAT','SK_JABATAN','KARPEG','TASPEN','NPWP','BUKU_NIKAH','CUTI','SERTIFIKAT','LAINNYA') NOT NULL,
  nomor_dokumen VARCHAR(150), tanggal_dokumen DATE, berlaku_sampai DATE, judul VARCHAR(300) NOT NULL,
  nama_file_asli VARCHAR(255) NOT NULL, path_file VARCHAR(500) NOT NULL, mime_type VARCHAR(120), ukuran BIGINT DEFAULT 0,
  kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), tahun YEAR, keterangan TEXT,
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100), is_deleted TINYINT NOT NULL DEFAULT 0,
  INDEX idx_dok_pegawai(pegawai_id,jenis_dokumen), INDEX idx_dok_pegawai_scope(kd_wilayah,kd_opd,tahun)
);
