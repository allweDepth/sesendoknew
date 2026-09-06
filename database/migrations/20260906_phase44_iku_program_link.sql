ALTER TABLE iku_opd_neo
  ADD COLUMN IF NOT EXISTS program_renstra_id INT NULL AFTER sasaran_renstra_id,
  ADD INDEX IF NOT EXISTS idx_iku_program_renstra (program_renstra_id);
