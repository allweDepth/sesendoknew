CREATE TABLE IF NOT EXISTS pejabat_tahunan_neo (
  id BIGINT NOT NULL AUTO_INCREMENT,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun YEAR NOT NULL,
  jenis_pejabat ENUM('PA_KPA','PPK','PPTK','PPK_SKPD','BENDAHARA','PEJABAT_PENGADAAN','PEJABAT_LAINNYA') NOT NULL,
  pegawai_id BIGINT NOT NULL,
  nama_pegawai VARCHAR(200) NULL,
  nip VARCHAR(50) NULL,
  nomor_sk VARCHAR(150) NOT NULL,
  tanggal_sk DATE NULL,
  berlaku_mulai DATE NOT NULL,
  berlaku_sampai DATE NOT NULL,
  kd_sub_keg VARCHAR(80) NULL,
  keterangan TEXT NULL,
  tgl_insert DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL,
  username_update VARCHAR(100) NULL,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY(id),
  KEY idx_pejabat_aktif(kd_wilayah,kd_opd,tahun,jenis_pejabat,berlaku_mulai,berlaku_sampai,is_deleted),
  KEY idx_pejabat_sub(kd_sub_keg,jenis_pejabat,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS trg_pejabat_fill_insert;
DROP TRIGGER IF EXISTS trg_pejabat_fill_update;
DELIMITER $$
CREATE TRIGGER trg_pejabat_fill_insert BEFORE INSERT ON pejabat_tahunan_neo FOR EACH ROW
BEGIN
  SET NEW.nama_pegawai=(SELECT nama FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  SET NEW.nip=(SELECT nip FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  IF NEW.berlaku_sampai<NEW.berlaku_mulai THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Tanggal berakhir jabatan tidak boleh sebelum tanggal mulai'; END IF;
  IF NEW.jenis_pejabat IN ('PPK','PPTK') AND (NEW.kd_sub_keg IS NULL OR NEW.kd_sub_keg='') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PPK dan PPTK wajib dihubungkan ke sub kegiatan'; END IF;
END$$
CREATE TRIGGER trg_pejabat_fill_update BEFORE UPDATE ON pejabat_tahunan_neo FOR EACH ROW
BEGIN
  SET NEW.nama_pegawai=(SELECT nama FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  SET NEW.nip=(SELECT nip FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  IF NEW.berlaku_sampai<NEW.berlaku_mulai THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Tanggal berakhir jabatan tidak boleh sebelum tanggal mulai'; END IF;
  IF NEW.jenis_pejabat IN ('PPK','PPTK') AND (NEW.kd_sub_keg IS NULL OR NEW.kd_sub_keg='') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PPK dan PPTK wajib dihubungkan ke sub kegiatan'; END IF;
END$$
DELIMITER ;
