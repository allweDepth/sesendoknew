ALTER TABLE pengaturan_neo
  ADD COLUMN IF NOT EXISTS jadwal_acuan_json LONGTEXT NULL;
