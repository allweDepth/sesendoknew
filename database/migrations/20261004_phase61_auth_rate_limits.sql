CREATE TABLE IF NOT EXISTS auth_rate_limits (
  bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
  attempts INT UNSIGNED NOT NULL DEFAULT 1,
  expires_at BIGINT UNSIGNED NOT NULL,
  INDEX idx_auth_rate_expiry (expires_at)
) ENGINE=InnoDB;
