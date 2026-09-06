ALTER TABLE sub_kegiatan_renstra_neo
  ADD COLUMN IF NOT EXISTS indikator_keluaran VARCHAR(500) NULL AFTER kelompok_sasaran,
  ADD COLUMN IF NOT EXISTS satuan VARCHAR(100) NULL AFTER indikator_keluaran;
