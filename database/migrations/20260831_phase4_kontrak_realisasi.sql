ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS paket_id INT NULL AFTER anggaran_id;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS rekanan_id INT NULL AFTER paket_id;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS nomor_spk VARCHAR(100) NULL AFTER uraian_kontrak;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS tanggal_spk DATE NULL AFTER nomor_spk;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS disable TINYINT NOT NULL DEFAULT 0;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS kunci TINYINT NOT NULL DEFAULT 0;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS setujui TINYINT NOT NULL DEFAULT 0;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS is_deleted TINYINT NOT NULL DEFAULT 0;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS keterangan VARCHAR(400) NULL;
ALTER TABLE kontrak_neo ADD INDEX IF NOT EXISTS idx_kontrak_scope (kd_wilayah,kd_opd,tahun,is_deleted);
ALTER TABLE kontrak_neo ADD INDEX IF NOT EXISTS idx_kontrak_anggaran (tahap,anggaran_id);
ALTER TABLE kontrak_neo ADD INDEX IF NOT EXISTS idx_kontrak_rekanan (rekanan_id);
ALTER TABLE kontrak_neo ADD UNIQUE INDEX IF NOT EXISTS uq_kontrak_nomor_scope (kd_wilayah,kd_opd,tahun,nomor_kontrak,is_deleted);

ALTER TABLE daftar_realisasi_neo ADD COLUMN IF NOT EXISTS kontrak_id BIGINT NULL AFTER id_paket;
ALTER TABLE daftar_realisasi_neo ADD COLUMN IF NOT EXISTS periode TINYINT NULL AFTER tanggal;
ALTER TABLE daftar_realisasi_neo ADD COLUMN IF NOT EXISTS progress_fisik DECIMAL(7,2) NOT NULL DEFAULT 0 AFTER periode;
ALTER TABLE daftar_realisasi_neo ADD COLUMN IF NOT EXISTS nomor_bukti VARCHAR(100) NULL AFTER progress_fisik;
ALTER TABLE daftar_realisasi_neo ADD COLUMN IF NOT EXISTS setujui TINYINT NOT NULL DEFAULT 0;
ALTER TABLE daftar_realisasi_neo ADD COLUMN IF NOT EXISTS kunci TINYINT NOT NULL DEFAULT 0;
ALTER TABLE daftar_realisasi_neo ADD INDEX IF NOT EXISTS idx_realisasi_scope (kd_wilayah,kd_opd,tahun,is_deleted);
ALTER TABLE daftar_realisasi_neo ADD INDEX IF NOT EXISTS idx_realisasi_kontrak (kontrak_id,tanggal,is_deleted);

ALTER TABLE daftar_paket_neo ADD COLUMN IF NOT EXISTS sumber_tahap ENUM('dpa','dppa') NULL AFTER tahun;
ALTER TABLE daftar_paket_neo ADD COLUMN IF NOT EXISTS anggaran_id BIGINT NULL AFTER sumber_tahap;
ALTER TABLE daftar_paket_neo ADD INDEX IF NOT EXISTS idx_paket_anggaran (sumber_tahap,anggaran_id);
ALTER TABLE daftar_paket_neo ADD INDEX IF NOT EXISTS idx_paket_scope (kd_wilayah,kd_opd,tahun,is_deleted);

DROP TRIGGER IF EXISTS trg_kontrak_validate_insert;
DELIMITER $$
CREATE TRIGGER trg_kontrak_validate_insert BEFORE INSERT ON kontrak_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE sub_code VARCHAR(50) DEFAULT NULL;
  DECLARE provider VARCHAR(255) DEFAULT NULL;
  IF NEW.tahap='dpa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSEIF NEW.tahap='dppa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  END IF;
  IF budget IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Sumber kontrak harus DPA/DPPA yang disetujui'; END IF;
  IF NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai kontrak melebihi anggaran DPA/DPPA'; END IF;
  SELECT nama_perusahaan INTO provider FROM rekanan_neo WHERE id=NEW.rekanan_id AND kd_wilayah=NEW.kd_wilayah AND is_deleted=0 LIMIT 1;
  IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;
  SET NEW.total_anggaran=budget,NEW.kd_sub_keg=sub_code,NEW.nama_penyedia=provider;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_dpa_protect_contract_update;
DELIMITER $$
CREATE TRIGGER trg_dpa_protect_contract_update BEFORE UPDATE ON dpa_neo FOR EACH ROW
BEGIN
  DECLARE contract_total DECIMAL(20,2) DEFAULT 0; DECLARE contract_count INT DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0),COUNT(*) INTO contract_total,contract_count FROM kontrak_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contract_count>0 AND NEW.is_deleted=1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA sudah berkontrak dan tidak dapat dihapus'; END IF;
  IF contract_count>0 AND NEW.jumlah<contract_total THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai DPA tidak boleh lebih kecil dari nilai kontrak'; END IF;
END$$
DELIMITER ;
DROP TRIGGER IF EXISTS trg_dpa_protect_contract_delete;
DELIMITER $$
CREATE TRIGGER trg_dpa_protect_contract_delete BEFORE DELETE ON dpa_neo FOR EACH ROW
BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_dppa_protect_contract_update;
DELIMITER $$
CREATE TRIGGER trg_dppa_protect_contract_update BEFORE UPDATE ON dppa_neo FOR EACH ROW
BEGIN
  DECLARE contract_total DECIMAL(20,2) DEFAULT 0; DECLARE contract_count INT DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0),COUNT(*) INTO contract_total,contract_count FROM kontrak_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contract_count>0 AND NEW.is_deleted=1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPPA sudah berkontrak dan tidak dapat dihapus'; END IF;
  IF contract_count>0 AND NEW.jumlah<contract_total THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai DPPA tidak boleh lebih kecil dari nilai kontrak'; END IF;
END$$
DELIMITER ;
DROP TRIGGER IF EXISTS trg_dppa_protect_contract_delete;
DELIMITER $$
CREATE TRIGGER trg_dppa_protect_contract_delete BEFORE DELETE ON dppa_neo FOR EACH ROW
BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_kontrak_validate_update;
DELIMITER $$
CREATE TRIGGER trg_kontrak_validate_update BEFORE UPDATE ON kontrak_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL; DECLARE sub_code VARCHAR(50) DEFAULT NULL; DECLARE provider VARCHAR(255) DEFAULT NULL;
  IF NEW.tahap='dpa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSEIF NEW.tahap='dppa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1; END IF;
  IF budget IS NULL OR NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak tidak valid atau nilainya melebihi DPA/DPPA'; END IF;
  SELECT nama_perusahaan INTO provider FROM rekanan_neo WHERE id=NEW.rekanan_id AND kd_wilayah=NEW.kd_wilayah AND is_deleted=0 LIMIT 1;
  IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;
  SET NEW.total_anggaran=budget,NEW.kd_sub_keg=sub_code,NEW.nama_penyedia=provider;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_realisasi_validate_insert;
DELIMITER $$
CREATE TRIGGER trg_realisasi_validate_insert BEFORE INSERT ON daftar_realisasi_neo FOR EACH ROW
BEGIN
  DECLARE contract_value DECIMAL(20,2) DEFAULT NULL; DECLARE realized DECIMAL(20,2) DEFAULT 0;
  SELECT nilai_kontrak INTO contract_value FROM kontrak_neo WHERE id=NEW.kontrak_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND is_deleted=0 LIMIT 1;
  IF contract_value IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak realisasi tidak valid'; END IF;
  SELECT COALESCE(SUM(jumlah),0) INTO realized FROM daftar_realisasi_neo WHERE kontrak_id=NEW.kontrak_id AND is_deleted=0;
  IF realized+NEW.jumlah>contract_value THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Realisasi melebihi nilai kontrak'; END IF;
  IF NEW.progress_fisik<0 OR NEW.progress_fisik>100 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Progress fisik harus 0 sampai 100'; END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_realisasi_validate_update;
DELIMITER $$
CREATE TRIGGER trg_realisasi_validate_update BEFORE UPDATE ON daftar_realisasi_neo FOR EACH ROW
BEGIN
  DECLARE contract_value DECIMAL(20,2) DEFAULT NULL; DECLARE realized DECIMAL(20,2) DEFAULT 0;
  SELECT nilai_kontrak INTO contract_value FROM kontrak_neo WHERE id=NEW.kontrak_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND is_deleted=0 LIMIT 1;
  SELECT COALESCE(SUM(jumlah),0) INTO realized FROM daftar_realisasi_neo WHERE kontrak_id=NEW.kontrak_id AND id<>OLD.id AND is_deleted=0;
  IF contract_value IS NULL OR realized+NEW.jumlah>contract_value THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Realisasi melebihi nilai kontrak atau kontrak tidak valid'; END IF;
  IF NEW.progress_fisik<0 OR NEW.progress_fisik>100 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Progress fisik harus 0 sampai 100'; END IF;
END$$
DELIMITER ;
