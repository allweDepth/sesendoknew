ALTER TABLE page_setup_opd_neo
  ADD COLUMN alignment_tanda_tangan ENUM('L','C','R','J') NOT NULL DEFAULT 'C'
  AFTER posisi_tanda_tangan;
