CREATE TABLE IF NOT EXISTS pimpinan_wilayah_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_wilayah VARCHAR(60) NOT NULL,
  tahun YEAR NOT NULL,
  jenis_pimpinan ENUM('kepala','wakil','sekda') NOT NULL,
  nama_pejabat VARCHAR(255) NOT NULL,
  nomor_sk_pengangkatan VARCHAR(150) NOT NULL,
  tanggal_sk_pengangkatan DATE NOT NULL,
  tmt_jabatan DATE NOT NULL,
  file_sk VARCHAR(500) NULL,
  foto VARCHAR(500) NULL,
  username_insert VARCHAR(100) NULL,
  tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  username_update VARCHAR(100) NULL,
  tgl_update DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pimpinan_wilayah (kd_wilayah,tahun,jenis_pimpinan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
