UPDATE kontrak_neo
SET nomor_kontrak = COALESCE(NULLIF(TRIM(nomor_kontrak), ''), NULLIF(TRIM(nomor_spk), ''))
WHERE (nomor_kontrak IS NULL OR TRIM(nomor_kontrak) = '')
  AND nomor_spk IS NOT NULL
  AND TRIM(nomor_spk) <> '';

ALTER TABLE kontrak_neo DROP COLUMN nomor_spk;
