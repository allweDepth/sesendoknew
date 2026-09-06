CREATE TABLE IF NOT EXISTS iku_opd_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL,
  renstra_id INT NULL, sasaran_renstra_id INT NULL,
  kode_iku VARCHAR(50) NOT NULL, nama_indikator TEXT NOT NULL,
  definisi_operasional TEXT NULL, formula_perhitungan TEXT NULL,
  satuan VARCHAR(50) NOT NULL, polaritas ENUM('maksimal','minimal','stabil') NOT NULL DEFAULT 'maksimal',
  sumber_data VARCHAR(255) NULL, frekuensi_pengukuran ENUM('bulanan','triwulanan','semesteran','tahunan') NOT NULL DEFAULT 'tahunan',
  penanggung_jawab_pegawai_id BIGINT NULL,
  baseline DECIMAL(20,4) NULL, target_t1 DECIMAL(20,4) NULL, target_t2 DECIMAL(20,4) NULL,
  target_t3 DECIMAL(20,4) NULL, target_t4 DECIMAL(20,4) NULL, target_t5 DECIMAL(20,4) NULL, target_akhir DECIMAL(20,4) NULL,
  status ENUM('draft','ditetapkan','direviu') NOT NULL DEFAULT 'draft', nomor_penetapan VARCHAR(100) NULL, tanggal_penetapan DATE NULL,
  keterangan TEXT NULL, tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_iku_scope (kd_wilayah,kd_opd,renstra_id,kode_iku),
  KEY idx_iku_sasaran (sasaran_renstra_id), KEY idx_iku_scope (kd_wilayah,kd_opd,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pohon_kinerja_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, parent_id BIGINT UNSIGNED NULL,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, renstra_id INT NULL, tahun YEAR NULL,
  kode_kinerja VARCHAR(60) NOT NULL, uraian_kinerja TEXT NOT NULL,
  jenjang ENUM('strategis','taktis','operasional') NOT NULL,
  jenis_kinerja ENUM('outcome','intermediate_outcome','output') NOT NULL,
  iku_id BIGINT UNSIGNED NULL, indikator VARCHAR(500) NULL, satuan VARCHAR(50) NULL, target DECIMAL(20,4) NULL,
  penanggung_jawab_pegawai_id BIGINT NULL,
  sumber_ref ENUM('sasaran_renstra','program_renstra','kegiatan_renstra','sub_kegiatan_renstra','lainnya') NOT NULL DEFAULT 'lainnya',
  sumber_id BIGINT NULL, hubungan_sebab_akibat TEXT NULL, status ENUM('draft','ditetapkan','direviu') NOT NULL DEFAULT 'draft',
  keterangan TEXT NULL, tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_pohon_scope (kd_wilayah,kd_opd,renstra_id,kode_kinerja),
  KEY idx_pohon_parent (parent_id), KEY idx_pohon_iku (iku_id), KEY idx_pohon_scope (kd_wilayah,kd_opd,tahun,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS perjanjian_kinerja_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
  nomor_dokumen VARCHAR(100) NOT NULL, tanggal_dokumen DATE NOT NULL,
  jenis ENUM('awal','perubahan') NOT NULL DEFAULT 'awal', induk_id BIGINT UNSIGNED NULL,
  pihak_pertama_pegawai_id BIGINT NOT NULL, pihak_pertama_jabatan VARCHAR(255) NOT NULL,
  pihak_kedua_pegawai_id BIGINT NOT NULL, pihak_kedua_jabatan VARCHAR(255) NOT NULL,
  dasar_dokumen VARCHAR(255) NULL, status ENUM('draft','diajukan','ditetapkan','direviu') NOT NULL DEFAULT 'draft',
  tanggal_penetapan DATE NULL, keterangan TEXT NULL,
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_pk_nomor (kd_wilayah,kd_opd,tahun,nomor_dokumen), KEY idx_pk_scope (kd_wilayah,kd_opd,tahun,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS perjanjian_kinerja_detail_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, perjanjian_kinerja_id BIGINT UNSIGNED NOT NULL,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
  nomor_urut SMALLINT NOT NULL DEFAULT 1, pohon_kinerja_id BIGINT UNSIGNED NULL, iku_id BIGINT UNSIGNED NULL,
  sasaran_kinerja TEXT NOT NULL, indikator_kinerja VARCHAR(500) NOT NULL, satuan VARCHAR(50) NOT NULL,
  target DECIMAL(20,4) NOT NULL, program_kegiatan VARCHAR(500) NULL, kd_sub_keg VARCHAR(100) NULL,
  anggaran DECIMAL(20,2) NOT NULL DEFAULT 0, sumber_anggaran ENUM('renja','rka','dpa','manual') NOT NULL DEFAULT 'dpa',
  keterangan TEXT NULL, tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  KEY idx_pk_detail_header (perjanjian_kinerja_id), KEY idx_pk_detail_iku (iku_id), KEY idx_pk_detail_sub (kd_sub_keg),
  KEY idx_pk_detail_scope (kd_wilayah,kd_opd,tahun,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pengukuran_kinerja_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
  perjanjian_kinerja_detail_id BIGINT UNSIGNED NOT NULL,
  periode ENUM('bulanan','triwulanan','semesteran','tahunan') NOT NULL, nomor_periode TINYINT NOT NULL,
  target_periode DECIMAL(20,4) NOT NULL DEFAULT 0, realisasi_periode DECIMAL(20,4) NOT NULL DEFAULT 0,
  realisasi_kumulatif DECIMAL(20,4) NOT NULL DEFAULT 0, capaian_persen DECIMAL(10,4) NOT NULL DEFAULT 0,
  bukti_dukung VARCHAR(500) NULL, analisis_capaian TEXT NULL, kendala TEXT NULL, tindak_lanjut TEXT NULL,
  status ENUM('draft','dikirim','diverifikasi','perbaikan') NOT NULL DEFAULT 'draft',
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_ukur_pk_periode (perjanjian_kinerja_detail_id,periode,nomor_periode),
  KEY idx_ukur_scope (kd_wilayah,kd_opd,tahun,status,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS evaluasi_renstra_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, renstra_id INT NOT NULL,
  tahun_evaluasi YEAR NOT NULL, jenis_evaluasi ENUM('tahunan','tengah_periode','akhir_periode','reviu') NOT NULL,
  iku_id BIGINT UNSIGNED NULL, indikator_sasaran_id INT NULL, indikator VARCHAR(500) NOT NULL, satuan VARCHAR(50) NOT NULL,
  target_tahunan DECIMAL(20,4) NULL, target_kumulatif DECIMAL(20,4) NULL,
  realisasi_tahunan DECIMAL(20,4) NULL, realisasi_kumulatif DECIMAL(20,4) NULL, capaian_persen DECIMAL(10,4) NULL,
  pagu_anggaran DECIMAL(20,2) NOT NULL DEFAULT 0, realisasi_anggaran DECIMAL(20,2) NOT NULL DEFAULT 0,
  faktor_pendorong TEXT NULL, faktor_penghambat TEXT NULL, tindak_lanjut TEXT NULL, rekomendasi_reviu TEXT NULL,
  status ENUM('draft','dikirim','diverifikasi','perbaikan','final') NOT NULL DEFAULT 'draft',
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  KEY idx_eval_renstra_scope (kd_wilayah,kd_opd,renstra_id,tahun_evaluasi,is_deleted), KEY idx_eval_renstra_iku (iku_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS renja_sub_kegiatan_kinerja_neo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
  kd_sub_keg VARCHAR(100) NOT NULL, sub_kegiatan_renstra_id INT NULL, pohon_kinerja_id BIGINT UNSIGNED NULL,
  indikator_keluaran VARCHAR(500) NOT NULL, satuan VARCHAR(50) NOT NULL, target DECIMAL(20,4) NOT NULL,
  lokasi VARCHAR(255) NULL, kelompok_sasaran VARCHAR(255) NULL, pagu_indikatif DECIMAL(20,2) NOT NULL DEFAULT 0,
  prakiraan_maju_target DECIMAL(20,4) NULL, prakiraan_maju_pagu DECIMAL(20,2) NULL,
  sumber_dana VARCHAR(255) NULL, catatan_penting TEXT NULL, status ENUM('draft','diajukan','disetujui') NOT NULL DEFAULT 'draft',
  tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_renja_kinerja_scope (kd_wilayah,kd_opd,tahun,kd_sub_keg),
  KEY idx_renja_kinerja_renstra (sub_kegiatan_renstra_id), KEY idx_renja_kinerja_pohon (pohon_kinerja_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
