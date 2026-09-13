ALTER TABLE daftar_realisasi_neo
  ADD COLUMN IF NOT EXISTS transaksi_uuid CHAR(36) NULL AFTER kontrak_id,
  ADD INDEX IF NOT EXISTS idx_realisasi_transaksi (transaksi_uuid,is_deleted);

CREATE TABLE IF NOT EXISTS realisasi_dokumen_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaksi_uuid CHAR(36) NOT NULL,
  kontrak_id BIGINT NOT NULL,
  nama_file_asli VARCHAR(255) NOT NULL,
  path_file VARCHAR(500) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  ukuran BIGINT UNSIGNED NOT NULL DEFAULT 0,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun YEAR NOT NULL,
  tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL,
  username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  INDEX idx_realisasi_dokumen_transaksi (transaksi_uuid,is_deleted),
  INDEX idx_realisasi_dokumen_scope (kd_wilayah,kd_opd,tahun,is_deleted)
);

CREATE OR REPLACE VIEW daftar_realisasi_transaksi_neo AS
SELECT MIN(dr.id) id,
       COALESCE(dr.transaksi_uuid,CONCAT('legacy-',dr.id)) transaksi_uuid,
       dr.kontrak_id,dr.kd_wilayah,dr.kd_opd,dr.tahun,dr.tanggal,dr.periode,
       GROUP_CONCAT(DISTINCT dr.kd_sub_keg ORDER BY dr.kd_sub_keg SEPARATOR ', ') kd_sub_keg,
       GROUP_CONCAT(DISTINCT dr.kd_akun ORDER BY dr.kd_akun SEPARATOR ', ') kd_akun,
       SUM(dr.jumlah) jumlah,MAX(dr.progress_fisik) progress_fisik,
       MAX(dr.nomor_bukti) nomor_bukti,MAX(dr.uraian_progress) uraian_progress,
       MAX(dr.keterangan) keterangan,0 is_deleted
FROM daftar_realisasi_neo dr
WHERE dr.is_deleted=0
GROUP BY COALESCE(dr.transaksi_uuid,CONCAT('legacy-',dr.id)),dr.kontrak_id,dr.kd_wilayah,dr.kd_opd,dr.tahun,dr.tanggal,dr.periode;
