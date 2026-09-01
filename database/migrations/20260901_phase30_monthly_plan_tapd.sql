CREATE TABLE IF NOT EXISTS rencana_realisasi_anggaran_neo (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, dokumen ENUM('dpa','dppa') NOT NULL, anggaran_id BIGINT NOT NULL,
 kd_wilayah VARCHAR(20) NOT NULL, kd_opd VARCHAR(50) NOT NULL, tahun SMALLINT NOT NULL,
 kd_sub_keg VARCHAR(100) NOT NULL, kd_akun VARCHAR(100) NOT NULL, jenis ENUM('belanja','pendapatan') NOT NULL DEFAULT 'belanja',
 bulan TINYINT NOT NULL, nilai DECIMAL(20,2) NOT NULL DEFAULT 0, keterangan VARCHAR(255) NULL,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
 tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
 UNIQUE KEY uq_rencana_bulan (dokumen,anggaran_id,bulan),
 INDEX idx_rencana_scope (kd_wilayah,kd_opd,tahun,dokumen,is_deleted), INDEX idx_rencana_sub_akun (kd_sub_keg,kd_akun)
);
CREATE TABLE IF NOT EXISTS tapd_penugasan_neo (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, kd_wilayah VARCHAR(20) NOT NULL, tahun SMALLINT NOT NULL,
 user_id BIGINT NULL, nama VARCHAR(255) NOT NULL, nip VARCHAR(50) NULL, jabatan VARCHAR(150) NOT NULL,
 urutan SMALLINT NOT NULL DEFAULT 1, tanggal_mulai DATE NOT NULL, tanggal_selesai DATE NOT NULL,
 aktif TINYINT NOT NULL DEFAULT 1, tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 username_insert VARCHAR(100) NULL, tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
 INDEX idx_tapd_berlaku (kd_wilayah,tahun,tanggal_mulai,tanggal_selesai,aktif,is_deleted)
);
