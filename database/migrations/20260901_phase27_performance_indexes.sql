ALTER TABLE wallchat ADD INDEX IF NOT EXISTS idx_wall_feed (type,is_deleted,parent_id,created_at);
ALTER TABLE wallchat ADD INDEX IF NOT EXISTS idx_wall_private_sender (type,is_deleted,user_id,deleted_by_sender,created_at);
ALTER TABLE wallchat ADD INDEX IF NOT EXISTS idx_wall_private_receiver (type,is_deleted,receiver_id,deleted_by_receiver,created_at);
ALTER TABLE kontrak_neo ADD INDEX IF NOT EXISTS idx_kontrak_scope (kd_wilayah,kd_opd,tahun,is_deleted);
ALTER TABLE daftar_realisasi_neo ADD INDEX IF NOT EXISTS idx_realisasi_contract_phase27 (kontrak_id,is_deleted,tanggal);
