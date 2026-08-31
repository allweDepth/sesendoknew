-- Phase 2: index untuk query aktual master_biaya dan mapping akun.
-- Aman dijalankan satu kali pada sesendoknew_db; tidak menghapus data/kolom.

ALTER TABLE master_biaya
  ADD INDEX idx_master_biaya_scope (tipe, kd_wilayah, tahun, peraturan_id, is_deleted),
  ADD INDEX idx_master_biaya_satuan (satuan_id),
  ADD INDEX idx_master_biaya_kode (kode);

ALTER TABLE master_biaya_akun
  ADD INDEX idx_master_biaya_akun_scope (kd_wilayah, peraturan_id, is_deleted),
  ADD INDEX idx_master_biaya_akun_lookup (master_biaya_id, kd_akun, peraturan_id, is_deleted);
