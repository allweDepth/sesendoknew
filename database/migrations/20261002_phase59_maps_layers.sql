CREATE TABLE IF NOT EXISTS maps_layers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_layer VARCHAR(160) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  storage_dir VARCHAR(500) NOT NULL,
  components_json TEXT NOT NULL,
  style_json JSON NULL,
  ukuran BIGINT UNSIGNED NOT NULL DEFAULT 0,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  username_insert VARCHAR(100) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  INDEX idx_maps_scope (kd_wilayah,kd_opd,is_deleted),
  INDEX idx_maps_uploader (user_id,is_deleted)
);

ALTER TABLE maps_layers
  ADD COLUMN IF NOT EXISTS style_json JSON NULL;
