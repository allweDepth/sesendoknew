ALTER TABLE user_sesendok_biila
  ADD COLUMN IF NOT EXISTS periode_aktif_id INT NULL AFTER tahun,
  ADD INDEX IF NOT EXISTS idx_user_periode_aktif (periode_aktif_id);

UPDATE user_sesendok_biila u
JOIN periode_rpjmd p
  ON u.tahun BETWEEN p.periode_mulai AND p.periode_selesai
 AND COALESCE(p.status_aktif,1)=1
SET u.periode_aktif_id=p.id
WHERE u.periode_aktif_id IS NULL;
