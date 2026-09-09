CREATE TABLE IF NOT EXISTS struktur_jabatan_opd_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
  pegawai_id BIGINT NOT NULL, parent_id BIGINT UNSIGNED NULL,
  nama_jabatan VARCHAR(255) NOT NULL, kelompok_jabatan VARCHAR(150) NULL, eselon VARCHAR(30) NULL,
  urutan INT NOT NULL DEFAULT 1, keterangan VARCHAR(500) NULL,
  tgl_insert DATETIME NULL, username_insert VARCHAR(100) NULL, tgl_update DATETIME NULL, username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id), UNIQUE KEY uq_struktur_jabatan_scope (kd_wilayah,kd_opd,tahun,nama_jabatan,is_deleted),
  KEY idx_struktur_jabatan_opd (kd_wilayah,kd_opd,tahun,is_deleted), KEY idx_struktur_jabatan_pegawai (pegawai_id), KEY idx_struktur_jabatan_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE batas_pagu_opd_neo MODIFY dokumen ENUM('rkpd','rkpd_p','renja','rka','dpa','renja_p','rka_p','dppa') NOT NULL;
