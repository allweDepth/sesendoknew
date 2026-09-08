DROP TRIGGER IF EXISTS trg_kontrak_final_budget_insert;
DROP TRIGGER IF EXISTS trg_kontrak_final_budget_update;
DROP TRIGGER IF EXISTS trg_kontrak_item_final_budget_insert;
DROP TRIGGER IF EXISTS trg_kontrak_item_final_budget_update;

DELIMITER $$
CREATE TRIGGER trg_kontrak_final_budget_insert BEFORE INSERT ON kontrak_neo FOR EACH ROW
BEGIN
  DECLARE sub_code VARCHAR(50) DEFAULT NULL;
  IF BINARY NEW.tahap=BINARY 'dpa' THEN
    SELECT kd_sub_keg INTO sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND kunci=1 AND is_deleted=0 LIMIT 1;
  ELSEIF BINARY NEW.tahap=BINARY 'dppa' THEN
    SELECT kd_sub_keg INTO sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND kunci=1 AND is_deleted=0 LIMIT 1;
  END IF;
  IF sub_code IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak hanya dapat memakai DPA/DPPA yang disetujui dan dikunci'; END IF;
  IF BINARY NEW.tahap=BINARY 'dpa' AND EXISTS (SELECT 1 FROM dppa_neo WHERE kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND kd_sub_keg=sub_code AND setujui=1 AND kunci=1 AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Gunakan DPPA final untuk sub kegiatan ini'; END IF;
END$$

CREATE TRIGGER trg_kontrak_final_budget_update BEFORE UPDATE ON kontrak_neo FOR EACH ROW
BEGIN
  DECLARE sub_code VARCHAR(50) DEFAULT NULL;
  IF BINARY NEW.tahap=BINARY 'dpa' THEN
    SELECT kd_sub_keg INTO sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND kunci=1 AND is_deleted=0 LIMIT 1;
  ELSEIF BINARY NEW.tahap=BINARY 'dppa' THEN
    SELECT kd_sub_keg INTO sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND kunci=1 AND is_deleted=0 LIMIT 1;
  END IF;
  IF sub_code IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak hanya dapat memakai DPA/DPPA yang disetujui dan dikunci'; END IF;
  IF BINARY NEW.tahap=BINARY 'dpa' AND EXISTS (SELECT 1 FROM dppa_neo WHERE kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND kd_sub_keg=sub_code AND setujui=1 AND kunci=1 AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Gunakan DPPA final untuk sub kegiatan ini'; END IF;
END$$

CREATE TRIGGER trg_kontrak_item_final_budget_insert BEFORE INSERT ON kontrak_item_neo FOR EACH ROW
BEGIN
  DECLARE final_count INT DEFAULT 0;
  IF BINARY NEW.tahap=BINARY 'dpa' THEN SELECT COUNT(*) INTO final_count FROM dpa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND kunci=1 AND is_deleted=0;
  ELSEIF BINARY NEW.tahap=BINARY 'dppa' THEN SELECT COUNT(*) INTO final_count FROM dppa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND kunci=1 AND is_deleted=0;
  END IF;
  IF final_count=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Rincian kontrak hanya dapat memakai DPA/DPPA yang disetujui dan dikunci'; END IF;
END$$

CREATE TRIGGER trg_kontrak_item_final_budget_update BEFORE UPDATE ON kontrak_item_neo FOR EACH ROW
BEGIN
  DECLARE final_count INT DEFAULT 0;
  IF BINARY NEW.tahap=BINARY 'dpa' THEN SELECT COUNT(*) INTO final_count FROM dpa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND kunci=1 AND is_deleted=0;
  ELSEIF BINARY NEW.tahap=BINARY 'dppa' THEN SELECT COUNT(*) INTO final_count FROM dppa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND kunci=1 AND is_deleted=0;
  END IF;
  IF final_count=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Rincian kontrak hanya dapat memakai DPA/DPPA yang disetujui dan dikunci'; END IF;
END$$
DELIMITER ;
