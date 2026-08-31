ALTER TABLE wallchat
  ADD COLUMN IF NOT EXISTS content_ciphertext LONGTEXT NULL AFTER content,
  ADD COLUMN IF NOT EXISTS content_nonce VARCHAR(64) NULL AFTER content_ciphertext,
  ADD COLUMN IF NOT EXISTS is_ephemeral TINYINT NOT NULL DEFAULT 0 AFTER content_nonce,
  ADD COLUMN IF NOT EXISTS read_at DATETIME NULL AFTER is_ephemeral,
  ADD COLUMN IF NOT EXISTS deleted_by_sender TINYINT NOT NULL DEFAULT 0 AFTER read_at,
  ADD COLUMN IF NOT EXISTS deleted_by_receiver TINYINT NOT NULL DEFAULT 0 AFTER deleted_by_sender,
  ADD COLUMN IF NOT EXISTS attachment_name VARCHAR(255) NULL AFTER deleted_by_receiver,
  ADD COLUMN IF NOT EXISTS attachment_path VARCHAR(500) NULL AFTER attachment_name,
  ADD COLUMN IF NOT EXISTS attachment_mime VARCHAR(120) NULL AFTER attachment_path,
  ADD COLUMN IF NOT EXISTS attachment_size BIGINT NOT NULL DEFAULT 0 AFTER attachment_mime,
  ADD INDEX IF NOT EXISTS idx_wallchat_private_visibility
    (type,user_id,receiver_id,is_deleted,deleted_by_sender,deleted_by_receiver,created_at);

