-- Satu uraian boleh dipakai beberapa kontrak. Pagu dikunci oleh akumulasi
-- kontrak yang telah disetujui, bukan oleh draft.
DROP TRIGGER IF EXISTS trg_kontrak_item_validate_insert;
DROP TRIGGER IF EXISTS trg_kontrak_item_validate_update;
DROP TRIGGER IF EXISTS trg_dpa_contract_guard_update;
DROP TRIGGER IF EXISTS trg_dppa_contract_guard_update;
DROP TRIGGER IF EXISTS trg_kontrak_validate_update;

DELIMITER $$
CREATE TRIGGER trg_kontrak_item_validate_insert BEFORE INSERT ON kontrak_item_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE used_value DECIMAL(20,2) DEFAULT 0;
  DECLARE approved TINYINT DEFAULT 0;
  IF BINARY NEW.tahap=BINARY 'dpa' THEN
    SELECT jumlah INTO budget FROM dpa_neo WHERE id=NEW.anggaran_id AND BINARY kd_wilayah=BINARY NEW.kd_wilayah AND BINARY kd_opd=BINARY NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSE
    SELECT jumlah INTO budget FROM dppa_neo WHERE id=NEW.anggaran_id AND BINARY kd_wilayah=BINARY NEW.kd_wilayah AND BINARY kd_opd=BINARY NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  END IF;
  SELECT COALESCE(setujui,0) INTO approved FROM kontrak_neo WHERE id=NEW.kontrak_id AND is_deleted=0 LIMIT 1;
  SELECT COALESCE(SUM(i.nilai_kontrak),0) INTO used_value FROM kontrak_item_neo i JOIN kontrak_neo k ON k.id=i.kontrak_id AND k.setujui=1 AND k.is_deleted=0 WHERE BINARY i.tahap=BINARY NEW.tahap AND i.anggaran_id=NEW.anggaran_id AND i.kontrak_id<>NEW.kontrak_id AND i.is_deleted=0;
  IF budget IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA/DPPA tidak ditemukan atau belum disetujui'; END IF;
  IF NEW.nilai_kontrak<=0 OR NEW.nilai_kontrak>budget OR (approved=1 AND used_value+NEW.nilai_kontrak>budget) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Akumulasi kontrak disetujui melebihi pagu uraian DPA/DPPA'; END IF;
END$$

CREATE TRIGGER trg_kontrak_item_validate_update BEFORE UPDATE ON kontrak_item_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE used_value DECIMAL(20,2) DEFAULT 0;
  DECLARE approved TINYINT DEFAULT 0;
  IF BINARY NEW.tahap=BINARY 'dpa' THEN
    SELECT jumlah INTO budget FROM dpa_neo WHERE id=NEW.anggaran_id AND BINARY kd_wilayah=BINARY NEW.kd_wilayah AND BINARY kd_opd=BINARY NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSE
    SELECT jumlah INTO budget FROM dppa_neo WHERE id=NEW.anggaran_id AND BINARY kd_wilayah=BINARY NEW.kd_wilayah AND BINARY kd_opd=BINARY NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  END IF;
  SELECT COALESCE(setujui,0) INTO approved FROM kontrak_neo WHERE id=NEW.kontrak_id AND is_deleted=0 LIMIT 1;
  SELECT COALESCE(SUM(i.nilai_kontrak),0) INTO used_value FROM kontrak_item_neo i JOIN kontrak_neo k ON k.id=i.kontrak_id AND k.setujui=1 AND k.is_deleted=0 WHERE BINARY i.tahap=BINARY NEW.tahap AND i.anggaran_id=NEW.anggaran_id AND i.kontrak_id<>NEW.kontrak_id AND i.is_deleted=0;
  IF budget IS NULL OR NEW.nilai_kontrak<=0 OR NEW.nilai_kontrak>budget OR (approved=1 AND used_value+NEW.nilai_kontrak>budget) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Akumulasi kontrak disetujui melebihi pagu uraian DPA/DPPA'; END IF;
END$$

CREATE TRIGGER trg_kontrak_validate_update BEFORE UPDATE ON kontrak_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE sub_code VARCHAR(50) DEFAULT NULL;
  DECLARE provider VARCHAR(255) DEFAULT NULL;
  DECLARE item_count INT DEFAULT 0;
  DECLARE over_count INT DEFAULT 0;
  SELECT COUNT(*),COALESCE(SUM(pagu),0),MIN(kd_sub_keg) INTO item_count,budget,sub_code FROM kontrak_item_neo WHERE kontrak_id=NEW.id AND is_deleted=0;
  IF item_count=0 THEN
    IF BINARY NEW.tahap=BINARY 'dpa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND BINARY kd_wilayah=BINARY NEW.kd_wilayah AND BINARY kd_opd=BINARY NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
    ELSEIF BINARY NEW.tahap=BINARY 'dppa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND BINARY kd_wilayah=BINARY NEW.kd_wilayah AND BINARY kd_opd=BINARY NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
    END IF;
  END IF;
  IF budget IS NULL OR NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak tidak valid atau nilainya melebihi DPA/DPPA'; END IF;
  IF NEW.setujui=1 THEN
    SELECT COUNT(*) INTO over_count
      FROM kontrak_item_neo ci
      LEFT JOIN dpa_neo d ON BINARY ci.tahap=BINARY 'dpa' AND d.id=ci.anggaran_id AND d.setujui=1 AND d.is_deleted=0
      LEFT JOIN dppa_neo p ON BINARY ci.tahap=BINARY 'dppa' AND p.id=ci.anggaran_id AND p.setujui=1 AND p.is_deleted=0
     WHERE ci.kontrak_id=NEW.id AND ci.is_deleted=0
       AND (COALESCE(d.jumlah,p.jumlah) IS NULL OR ci.nilai_kontrak + COALESCE((SELECT SUM(oi.nilai_kontrak) FROM kontrak_item_neo oi JOIN kontrak_neo ok ON ok.id=oi.kontrak_id AND ok.setujui=1 AND ok.is_deleted=0 WHERE BINARY oi.tahap=BINARY ci.tahap AND oi.anggaran_id=ci.anggaran_id AND oi.kontrak_id<>NEW.id AND oi.is_deleted=0),0)>COALESCE(d.jumlah,p.jumlah));
    IF over_count>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Persetujuan ditolak: akumulasi kontrak uraian melebihi pagu DPA/DPPA'; END IF;
  END IF;
  SELECT nama_perusahaan INTO provider FROM rekanan_neo WHERE id=NEW.rekanan_id AND BINARY kd_wilayah=BINARY NEW.kd_wilayah AND is_deleted=0 LIMIT 1;
  IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;
  SET NEW.total_anggaran=budget,NEW.kd_sub_keg=sub_code,NEW.nama_penyedia=provider;
END$$

CREATE TRIGGER trg_dpa_contract_guard_update BEFORE UPDATE ON dpa_neo FOR EACH ROW
BEGIN
  DECLARE contracted DECIMAL(20,2) DEFAULT 0;
  SELECT COALESCE(SUM(i.nilai_kontrak),0) INTO contracted FROM kontrak_item_neo i JOIN kontrak_neo k ON k.id=i.kontrak_id AND k.setujui=1 AND k.is_deleted=0 WHERE BINARY i.tahap=BINARY 'dpa' AND i.anggaran_id=OLD.id AND i.is_deleted=0;
  IF NEW.jumlah<contracted THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pagu DPA tidak boleh lebih kecil dari akumulasi kontrak yang disetujui'; END IF;
  IF NEW.is_deleted=1 AND EXISTS(SELECT 1 FROM kontrak_item_neo WHERE BINARY tahap=BINARY 'dpa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA sudah terhubung kontrak dan tidak dapat dihapus'; END IF;
END$$

CREATE TRIGGER trg_dppa_contract_guard_update BEFORE UPDATE ON dppa_neo FOR EACH ROW
BEGIN
  DECLARE contracted DECIMAL(20,2) DEFAULT 0;
  SELECT COALESCE(SUM(i.nilai_kontrak),0) INTO contracted FROM kontrak_item_neo i JOIN kontrak_neo k ON k.id=i.kontrak_id AND k.setujui=1 AND k.is_deleted=0 WHERE BINARY i.tahap=BINARY 'dppa' AND i.anggaran_id=OLD.id AND i.is_deleted=0;
  IF NEW.jumlah<contracted THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pagu DPPA tidak boleh lebih kecil dari akumulasi kontrak yang disetujui'; END IF;
  IF NEW.is_deleted=1 AND EXISTS(SELECT 1 FROM kontrak_item_neo WHERE BINARY tahap=BINARY 'dppa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPPA sudah terhubung kontrak dan tidak dapat dihapus'; END IF;
END$$
DELIMITER ;
