ALTER TABLE group_sub_kegiatan
  ADD COLUMN IF NOT EXISTS output TEXT NULL AFTER nama_sub_keg,
  ADD COLUMN IF NOT EXISTS satuan_output VARCHAR(100) NULL AFTER output,
  ADD COLUMN IF NOT EXISTS batas_anggaran DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER satuan_output;

