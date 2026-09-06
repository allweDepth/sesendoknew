CREATE TABLE IF NOT EXISTS batas_pagu_opd_neo (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun SMALLINT NOT NULL,
  dokumen ENUM('renja','rka','dpa','renja_p','rka_p','dppa') NOT NULL,
  pagu_maksimal DECIMAL(20,2) NOT NULL DEFAULT 0,
  keterangan VARCHAR(500) NULL,
  tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL,
  username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_batas_pagu_scope (kd_wilayah, kd_opd, tahun, dokumen),
  INDEX idx_batas_pagu_wilayah_tahun (kd_wilayah, tahun, is_deleted)
);
