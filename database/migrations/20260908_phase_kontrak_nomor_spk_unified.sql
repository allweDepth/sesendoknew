SET @has_nomor_spk = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'kontrak_neo'
    AND COLUMN_NAME = 'nomor_spk'
);
SET @copy_nomor_spk = IF(
  @has_nomor_spk > 0,
  'UPDATE kontrak_neo SET nomor_kontrak = COALESCE(NULLIF(TRIM(nomor_kontrak), ''''), NULLIF(TRIM(nomor_spk), '''')) WHERE (nomor_kontrak IS NULL OR TRIM(nomor_kontrak) = '''') AND nomor_spk IS NOT NULL AND TRIM(nomor_spk) <> ''''',
  'SELECT 1'
);
PREPARE copy_nomor_spk_stmt FROM @copy_nomor_spk;
EXECUTE copy_nomor_spk_stmt;
DEALLOCATE PREPARE copy_nomor_spk_stmt;

ALTER TABLE kontrak_neo DROP COLUMN IF EXISTS nomor_spk;
ALTER TABLE kontrak_neo DROP COLUMN IF EXISTS tanggal_spk;
