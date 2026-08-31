CREATE TABLE IF NOT EXISTS rkpd_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  renstra_sub_kegiatan_id INT NULL,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun YEAR NOT NULL,
  kd_program VARCHAR(50) NULL,
  kd_kegiatan VARCHAR(50) NULL,
  kd_sub_keg VARCHAR(50) NOT NULL,
  indikator VARCHAR(510) NULL,
  target DECIMAL(18,2) NOT NULL DEFAULT 0,
  satuan_id INT NULL,
  pagu DECIMAL(20,2) NOT NULL DEFAULT 0,
  sumber_dana_id INT NULL,
  lokasi VARCHAR(255) NULL,
  kelompok_sasaran VARCHAR(255) NULL,
  status ENUM('draft','final','approved') NOT NULL DEFAULT 'draft',
  disable TINYINT NOT NULL DEFAULT 0,
  kunci TINYINT NOT NULL DEFAULT 0,
  setujui TINYINT NOT NULL DEFAULT 0,
  keterangan TEXT NULL,
  tgl_insert DATETIME NULL,
  username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL,
  username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_rkpd_scope (kd_wilayah, kd_opd, tahun, is_deleted),
  KEY idx_rkpd_sub (kd_sub_keg),
  KEY idx_rkpd_renstra (renstra_sub_kegiatan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rkpd_p_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_rkpd_id BIGINT UNSIGNED NULL,
  renstra_sub_kegiatan_id INT NULL,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun YEAR NOT NULL,
  kd_program VARCHAR(50) NULL,
  kd_kegiatan VARCHAR(50) NULL,
  kd_sub_keg VARCHAR(50) NOT NULL,
  indikator VARCHAR(510) NULL,
  target_awal DECIMAL(18,2) NOT NULL DEFAULT 0,
  pagu_awal DECIMAL(20,2) NOT NULL DEFAULT 0,
  target DECIMAL(18,2) NOT NULL DEFAULT 0,
  satuan_id INT NULL,
  pagu DECIMAL(20,2) NOT NULL DEFAULT 0,
  sumber_dana_id INT NULL,
  lokasi VARCHAR(255) NULL,
  kelompok_sasaran VARCHAR(255) NULL,
  status_perubahan ENUM('awal','ubah','tambah','hapus') NOT NULL DEFAULT 'awal',
  status ENUM('draft','final','approved') NOT NULL DEFAULT 'draft',
  disable TINYINT NOT NULL DEFAULT 0,
  kunci TINYINT NOT NULL DEFAULT 0,
  setujui TINYINT NOT NULL DEFAULT 0,
  keterangan TEXT NULL,
  tgl_insert DATETIME NULL,
  username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL,
  username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rkpd_p_source (source_rkpd_id),
  KEY idx_rkpd_p_scope (kd_wilayah, kd_opd, tahun, is_deleted),
  KEY idx_rkpd_p_sub (kd_sub_keg)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE renja_neo ADD COLUMN IF NOT EXISTS source_table VARCHAR(30) NULL AFTER id;
ALTER TABLE renja_neo ADD COLUMN IF NOT EXISTS source_id BIGINT NULL AFTER source_table;
ALTER TABLE rka_neo ADD COLUMN IF NOT EXISTS source_table VARCHAR(30) NULL AFTER id;
ALTER TABLE rka_neo ADD COLUMN IF NOT EXISTS source_id BIGINT NULL AFTER source_table;
ALTER TABLE dpa_neo ADD COLUMN IF NOT EXISTS source_table VARCHAR(30) NULL AFTER id;
ALTER TABLE dpa_neo ADD COLUMN IF NOT EXISTS source_id BIGINT NULL AFTER source_table;
ALTER TABLE renja_p_neo ADD COLUMN IF NOT EXISTS source_table VARCHAR(30) NULL AFTER id;
ALTER TABLE renja_p_neo ADD COLUMN IF NOT EXISTS source_id BIGINT NULL AFTER source_table;
ALTER TABLE rka_p_neo ADD COLUMN IF NOT EXISTS source_table VARCHAR(30) NULL AFTER id;
ALTER TABLE rka_p_neo ADD COLUMN IF NOT EXISTS source_id BIGINT NULL AFTER source_table;
ALTER TABLE dppa_neo ADD COLUMN IF NOT EXISTS source_table VARCHAR(30) NULL AFTER id;
ALTER TABLE dppa_neo ADD COLUMN IF NOT EXISTS source_id BIGINT NULL AFTER source_table;
ALTER TABLE renja_neo ADD INDEX IF NOT EXISTS idx_renja_source (source_table, source_id);
ALTER TABLE rka_neo ADD INDEX IF NOT EXISTS idx_rka_source (source_table, source_id);
ALTER TABLE dpa_neo ADD INDEX IF NOT EXISTS idx_dpa_source (source_table, source_id);
ALTER TABLE renja_p_neo ADD INDEX IF NOT EXISTS idx_renja_p_source (source_table, source_id);
ALTER TABLE rka_p_neo ADD INDEX IF NOT EXISTS idx_rka_p_source (source_table, source_id);
ALTER TABLE dppa_neo ADD INDEX IF NOT EXISTS idx_dppa_source (source_table, source_id);

CREATE TABLE IF NOT EXISTS anggaran_workflow_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_table VARCHAR(30) NOT NULL,
  target_table VARCHAR(30) NOT NULL,
  tahun YEAR NOT NULL,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NULL,
  jumlah_data INT NOT NULL DEFAULT 0,
  username VARCHAR(100) NOT NULL,
  tgl_copy DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_workflow_scope (kd_wilayah, kd_opd, tahun, tgl_copy)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
