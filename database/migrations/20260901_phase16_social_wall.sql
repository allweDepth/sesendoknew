ALTER TABLE wallchat
  ADD COLUMN IF NOT EXISTS theme VARCHAR(30) NOT NULL DEFAULT 'default' AFTER attachment_size,
  ADD INDEX IF NOT EXISTS idx_wallchat_owner_feed (user_id,type,is_deleted,created_at);
