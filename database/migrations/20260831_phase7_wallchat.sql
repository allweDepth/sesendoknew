ALTER TABLE wallchat ADD COLUMN IF NOT EXISTS receiver_id INT NULL AFTER user_id;
ALTER TABLE wallchat MODIFY type ENUM('status','pesan','komentar','comment','private') NOT NULL DEFAULT 'status';
UPDATE wallchat SET type='comment' WHERE type='komentar';
UPDATE wallchat SET type='private' WHERE type='pesan';
ALTER TABLE wallchat MODIFY type ENUM('status','comment','private') NOT NULL DEFAULT 'status';
ALTER TABLE wallchat ADD INDEX IF NOT EXISTS idx_wallchat_receiver (receiver_id,type,is_deleted,created_at);
ALTER TABLE wallchat ADD INDEX IF NOT EXISTS idx_wallchat_feed (type,parent_id,is_deleted,created_at);
