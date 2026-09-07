-- Header kontrak multi-uraian divalidasi terhadap jumlah seluruh kontrak_item,
-- bukan hanya baris DPA representatif pertama.
DROP TRIGGER IF EXISTS trg_kontrak_validate_update;
DELIMITER $$
CREATE TRIGGER trg_kontrak_validate_update BEFORE UPDATE ON kontrak_neo FOR EACH ROW
BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE sub_code VARCHAR(50) DEFAULT NULL;
  DECLARE provider VARCHAR(255) DEFAULT NULL;
  DECLARE item_count INT DEFAULT 0;
  SELECT COUNT(*),COALESCE(SUM(pagu),0),MIN(kd_sub_keg) INTO item_count,budget,sub_code FROM kontrak_item_neo WHERE kontrak_id=NEW.id AND is_deleted=0;
  IF item_count=0 THEN
    IF NEW.tahap='dpa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
    ELSEIF NEW.tahap='dppa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
    END IF;
  END IF;
  IF budget IS NULL OR NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak tidak valid atau nilainya melebihi DPA/DPPA'; END IF;
  SELECT nama_perusahaan INTO provider FROM rekanan_neo WHERE id=NEW.rekanan_id AND kd_wilayah=NEW.kd_wilayah AND is_deleted=0 LIMIT 1;
  IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;
  SET NEW.total_anggaran=budget,NEW.kd_sub_keg=sub_code,NEW.nama_penyedia=provider;
END$$
DELIMITER ;
