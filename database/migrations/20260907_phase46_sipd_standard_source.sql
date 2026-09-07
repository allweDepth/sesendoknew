-- Jejak sumber impor standar harga SIPD. Kolom nullable menjaga kompatibilitas
-- data lama dan memungkinkan impor idempoten berdasarkan ID/kode sumber.
ALTER TABLE master_biaya
  ADD COLUMN IF NOT EXISTS sipd_id VARCHAR(60) NULL AFTER tipe,
  ADD COLUMN IF NOT EXISTS kode_kelompok VARCHAR(100) NULL AFTER kode_aset,
  ADD INDEX IF NOT EXISTS idx_master_biaya_sipd (tipe, sipd_id, kd_wilayah, tahun, peraturan_id, is_deleted);

