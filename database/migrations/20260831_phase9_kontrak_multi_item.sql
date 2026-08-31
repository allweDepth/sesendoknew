CREATE TABLE IF NOT EXISTS kontrak_item_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kontrak_id BIGINT NOT NULL,
  tahap ENUM('dpa','dppa') NOT NULL,
  anggaran_id BIGINT NOT NULL,
  kd_wilayah VARCHAR(30) NOT NULL,
  kd_opd VARCHAR(50) NOT NULL,
  tahun INT NOT NULL,
  kd_sub_keg VARCHAR(80) NOT NULL,
  kd_akun VARCHAR(80) NULL,
  uraian VARCHAR(500) NOT NULL,
  pagu DECIMAL(20,2) NOT NULL DEFAULT 0,
  nilai_kontrak DECIMAL(20,2) NOT NULL DEFAULT 0,
  tgl_insert DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  username_insert VARCHAR(100) NULL,
  tgl_update DATETIME NULL,
  username_update VARCHAR(100) NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kontrak_item (kontrak_id,tahap,anggaran_id,is_deleted),
  KEY idx_kontrak_item_anggaran (tahap,anggaran_id,is_deleted),
  KEY idx_kontrak_item_scope (kd_wilayah,kd_opd,tahun,is_deleted),
  CONSTRAINT fk_kontrak_item_header FOREIGN KEY (kontrak_id) REFERENCES kontrak_neo(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS ppk_id BIGINT NULL AFTER rekanan_id;
ALTER TABLE kontrak_neo ADD COLUMN IF NOT EXISTS pptk_id BIGINT NULL AFTER ppk_id;

INSERT IGNORE INTO kontrak_item_neo
  (kontrak_id,tahap,anggaran_id,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,uraian,pagu,nilai_kontrak,username_insert,is_deleted)
SELECT k.id,k.tahap,k.anggaran_id,k.kd_wilayah,k.kd_opd,k.tahun,k.kd_sub_keg,
       COALESCE(d.kd_akun,p.kd_akun,''),COALESCE(d.uraian,p.uraian,k.uraian_kontrak),
       COALESCE(d.jumlah,p.jumlah,k.total_anggaran),k.nilai_kontrak,k.username_insert,0
FROM kontrak_neo k
LEFT JOIN dpa_neo d ON k.tahap='dpa' AND d.id=k.anggaran_id
LEFT JOIN dppa_neo p ON k.tahap='dppa' AND p.id=k.anggaran_id
WHERE k.is_deleted=0 AND k.anggaran_id IS NOT NULL;

DROP TRIGGER IF EXISTS trg_kontrak_validate_insert;
DROP TRIGGER IF EXISTS trg_kontrak_validate_update;
DROP TRIGGER IF EXISTS trg_dpa_contract_guard_update;
DROP TRIGGER IF EXISTS trg_dpa_contract_guard_delete;
DROP TRIGGER IF EXISTS trg_dppa_contract_guard_update;
DROP TRIGGER IF EXISTS trg_dppa_contract_guard_delete;
DROP TRIGGER IF EXISTS trg_kontrak_item_validate_insert;
DROP TRIGGER IF EXISTS trg_kontrak_item_validate_update;

DELIMITER $$
CREATE TRIGGER trg_kontrak_item_validate_insert BEFORE INSERT ON kontrak_item_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE used_value DECIMAL(20,2) DEFAULT 0;
  IF NEW.tahap='dpa' THEN SELECT jumlah INTO budget FROM dpa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSE SELECT jumlah INTO budget FROM dppa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1; END IF;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO used_value FROM kontrak_item_neo WHERE tahap=NEW.tahap AND anggaran_id=NEW.anggaran_id AND is_deleted=0;
  IF budget IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA/DPPA tidak ditemukan atau belum disetujui'; END IF;
  IF NEW.nilai_kontrak<=0 OR used_value+NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai kontrak uraian melebihi pagu DPA/DPPA tersedia'; END IF;
END$$
CREATE TRIGGER trg_kontrak_item_validate_update BEFORE UPDATE ON kontrak_item_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE used_value DECIMAL(20,2) DEFAULT 0;
  IF NEW.tahap='dpa' THEN SELECT jumlah INTO budget FROM dpa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSE SELECT jumlah INTO budget FROM dppa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1; END IF;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO used_value FROM kontrak_item_neo WHERE tahap=NEW.tahap AND anggaran_id=NEW.anggaran_id AND id<>OLD.id AND is_deleted=0;
  IF budget IS NULL OR NEW.nilai_kontrak<=0 OR used_value+NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai kontrak uraian tidak valid atau melebihi pagu tersedia'; END IF;
END$$
CREATE TRIGGER trg_dpa_contract_guard_update BEFORE UPDATE ON dpa_neo FOR EACH ROW
BEGIN
  DECLARE contracted DECIMAL(20,2) DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO contracted FROM kontrak_item_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contracted>0 AND (NEW.jumlah<contracted OR NEW.is_deleted=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Anggaran DPA tidak boleh lebih kecil dari nilai kontrak dan uraian tidak dapat dihapus'; END IF;
END$$
CREATE TRIGGER trg_dpa_contract_guard_delete BEFORE DELETE ON dpa_neo FOR EACH ROW
BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_item_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END$$
CREATE TRIGGER trg_dppa_contract_guard_update BEFORE UPDATE ON dppa_neo FOR EACH ROW
BEGIN
  DECLARE contracted DECIMAL(20,2) DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO contracted FROM kontrak_item_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contracted>0 AND (NEW.jumlah<contracted OR NEW.is_deleted=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Anggaran DPPA tidak boleh lebih kecil dari nilai kontrak dan uraian tidak dapat dihapus'; END IF;
END$$
CREATE TRIGGER trg_dppa_contract_guard_delete BEFORE DELETE ON dppa_neo FOR EACH ROW
BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_item_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END$$
DELIMITER ;
