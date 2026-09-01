CREATE TABLE IF NOT EXISTS rencana_rekening_anggaran_neo (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 dokumen ENUM('dpa','dppa') NOT NULL,
 kd_wilayah VARCHAR(60) NOT NULL,
 kd_opd VARCHAR(60) NOT NULL,
 tahun SMALLINT NOT NULL,
 kd_sub_keg VARCHAR(100) NOT NULL,
 kd_akun VARCHAR(100) NOT NULL,
 jenis ENUM('belanja','pendapatan') NOT NULL DEFAULT 'belanja',
 bulan TINYINT NOT NULL,
 nilai DECIMAL(20,2) NOT NULL DEFAULT 0,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 username_insert VARCHAR(100) NULL,
 tgl_update DATETIME NULL,
 username_update VARCHAR(100) NULL,
 is_deleted TINYINT NOT NULL DEFAULT 0,
 UNIQUE KEY uq_rencana_rekening_bulan (dokumen,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,jenis,bulan),
 INDEX idx_rencana_rekening_scope (kd_wilayah,kd_opd,tahun,dokumen,kd_sub_keg,is_deleted)
);
ALTER TABLE user_sesendok_biila ADD COLUMN IF NOT EXISTS pegawai_id INT NULL AFTER id;
ALTER TABLE user_sesendok_biila ADD INDEX IF NOT EXISTS idx_user_pegawai (pegawai_id,kd_wilayah,kd_opd);
ALTER TABLE tapd_penugasan_neo ADD COLUMN IF NOT EXISTS pegawai_id INT NULL AFTER user_id;
ALTER TABLE tapd_penugasan_neo ADD INDEX IF NOT EXISTS idx_tapd_pegawai (pegawai_id,kd_wilayah);
