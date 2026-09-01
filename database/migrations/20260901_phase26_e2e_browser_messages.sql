ALTER TABLE user_sesendok_biila ADD COLUMN IF NOT EXISTS message_public_key LONGTEXT NULL AFTER password;
ALTER TABLE wallchat ADD COLUMN IF NOT EXISTS e2e_payload LONGTEXT NULL AFTER content_nonce;
ALTER TABLE wallchat ADD COLUMN IF NOT EXISTS encryption_version VARCHAR(20) NULL AFTER e2e_payload;
