ALTER TABLE pengaturan_neo
  ADD COLUMN IF NOT EXISTS ukuran_kertas VARCHAR(10) NOT NULL DEFAULT 'A4' AFTER tahun_renstra,
  ADD COLUMN IF NOT EXISTS orientasi_kertas ENUM('AUTO','P','L') NOT NULL DEFAULT 'AUTO' AFTER ukuran_kertas,
  ADD COLUMN IF NOT EXISTS font_pdf VARCHAR(20) NOT NULL DEFAULT 'helvetica' AFTER orientasi_kertas,
  ADD COLUMN IF NOT EXISTS ukuran_font_pdf DECIMAL(4,1) NOT NULL DEFAULT 10.0 AFTER font_pdf;

CREATE TABLE IF NOT EXISTS rpjmd_kabupaten_neo (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, kd_wilayah VARCHAR(60) NOT NULL,
 nama_dokumen VARCHAR(255) NOT NULL, nomor_perda VARCHAR(100) NULL,
 tanggal_perda DATE NULL, berlaku_mulai DATE NOT NULL, berlaku_sampai DATE NOT NULL,
 visi TEXT NULL, misi LONGTEXT NULL, sasaran LONGTEXT NULL, indikator LONGTEXT NULL,
 status ENUM('draft','berlaku','berakhir') NOT NULL DEFAULT 'draft', keterangan TEXT NULL,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
 tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
 INDEX idx_rpjmd_wilayah_masa(kd_wilayah,berlaku_mulai,berlaku_sampai,is_deleted)
);

CREATE TABLE IF NOT EXISTS usulan_pembangunan_neo (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NULL, tahun SMALLINT NOT NULL,
 jenis_usulan ENUM('musrenbang','pokir_dprd','masyarakat') NOT NULL, pengusul VARCHAR(255) NOT NULL,
 nik_no_identitas VARCHAR(100) NULL, alamat TEXT NULL, desa_kelurahan VARCHAR(150) NULL, kecamatan VARCHAR(150) NULL,
 uraian TEXT NOT NULL, volume DECIMAL(18,2) NULL, satuan VARCHAR(50) NULL, lokasi TEXT NULL,
 perkiraan_anggaran DECIMAL(20,2) NOT NULL DEFAULT 0, prioritas TINYINT NULL,
 status ENUM('diusulkan','diverifikasi','diterima','ditolak','diakomodasi') NOT NULL DEFAULT 'diusulkan', catatan_verifikasi TEXT NULL,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
 tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
 INDEX idx_usulan_scope(kd_wilayah,kd_opd,tahun,jenis_usulan,is_deleted)
);

CREATE TABLE IF NOT EXISTS evaluasi_renja_neo (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun SMALLINT NOT NULL,
 triwulan TINYINT NOT NULL, kd_sub_keg VARCHAR(100) NOT NULL, indikator VARCHAR(500) NOT NULL, satuan VARCHAR(50) NULL,
 target_tahunan DECIMAL(20,4) NOT NULL DEFAULT 0, target_triwulan DECIMAL(20,4) NOT NULL DEFAULT 0,
 realisasi_triwulan DECIMAL(20,4) NOT NULL DEFAULT 0, realisasi_kumulatif DECIMAL(20,4) NOT NULL DEFAULT 0,
 pagu_anggaran DECIMAL(20,2) NOT NULL DEFAULT 0, realisasi_anggaran_triwulan DECIMAL(20,2) NOT NULL DEFAULT 0,
 realisasi_anggaran_kumulatif DECIMAL(20,2) NOT NULL DEFAULT 0, faktor_pendorong TEXT NULL, faktor_penghambat TEXT NULL,
 tindak_lanjut TEXT NULL, status ENUM('draft','dikirim','diverifikasi','perbaikan') NOT NULL DEFAULT 'draft',
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
 tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
 UNIQUE KEY uq_evaluasi_renja(kd_wilayah,kd_opd,tahun,triwulan,kd_sub_keg,indikator(150)),
 INDEX idx_evaluasi_scope(kd_wilayah,kd_opd,tahun,triwulan,is_deleted), CHECK (triwulan BETWEEN 1 AND 4)
);
