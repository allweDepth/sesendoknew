ALTER TABLE pengaturan_neo
  ADD COLUMN IF NOT EXISTS awal_rka_p DATETIME NULL,
  ADD COLUMN IF NOT EXISTS akhir_rka_p DATETIME NULL,
  ADD COLUMN IF NOT EXISTS kunci_rkpd TINYINT NULL,
  ADD COLUMN IF NOT EXISTS kunci_rka TINYINT NULL,
  ADD COLUMN IF NOT EXISTS kunci_rkpd_perubahan TINYINT NULL,
  ADD COLUMN IF NOT EXISTS kunci_rka_p TINYINT NULL;

-- Preserve existing shared controls, then allow each stage to be scheduled independently.
UPDATE pengaturan_neo SET awal_rka_p=awal_renja_p,akhir_rka_p=akhir_renja_p WHERE awal_rka_p IS NULL AND akhir_rka_p IS NULL;
UPDATE pengaturan_neo SET kunci_rkpd=0 WHERE kunci_rkpd IS NULL;
UPDATE pengaturan_neo SET kunci_rka=COALESCE(kunci_renja,0) WHERE kunci_rka IS NULL;
UPDATE pengaturan_neo SET kunci_rkpd_perubahan=COALESCE(kunci_renja_p,0) WHERE kunci_rkpd_perubahan IS NULL;
UPDATE pengaturan_neo SET kunci_rka_p=COALESCE(kunci_renja_p,0) WHERE kunci_rka_p IS NULL;
