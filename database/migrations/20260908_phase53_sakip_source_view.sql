-- Sumber dropdown Pohon Kinerja sepenuhnya dibentuk dari relasi data Renstra.
-- Alias eksplisit pada setiap cabang menjaga definisi view stabil saat diekspor/diimpor MariaDB.
CREATE OR REPLACE VIEW sakip_sumber_kinerja_v AS
SELECT CONCAT('sasaran_renstra:', s.id) AS value_key,
       s.id AS sumber_id,
       'sasaran_renstra' AS sumber_ref,
       r.kd_wilayah AS kd_wilayah,
       r.kd_opd AS kd_opd,
       CONCAT('Sasaran — ', s.kode_sasaran, ' ', s.nama_sasaran) AS uraian
FROM sasaran_renstra_neo s
JOIN tujuan_renstra_neo t ON t.id = s.tujuan_id AND t.is_deleted = 0
JOIN misi_renstra_neo m ON m.id = t.misi_id AND m.is_deleted = 0
JOIN renstra_neo r ON r.id = m.renstra_id AND r.is_deleted = 0
WHERE s.is_deleted = 0
UNION ALL
SELECT CONCAT('program_renstra:', p.id) AS value_key,
       p.id AS sumber_id,
       'program_renstra' AS sumber_ref,
       r.kd_wilayah AS kd_wilayah,
       r.kd_opd AS kd_opd,
       CONCAT('Program — ', p.kode_program, ' ', p.uraian) AS uraian
FROM program_renstra_neo p
JOIN sasaran_renstra_neo s ON s.id = p.sasaran_id AND s.is_deleted = 0
JOIN tujuan_renstra_neo t ON t.id = s.tujuan_id AND t.is_deleted = 0
JOIN misi_renstra_neo m ON m.id = t.misi_id AND m.is_deleted = 0
JOIN renstra_neo r ON r.id = m.renstra_id AND r.is_deleted = 0
WHERE p.is_deleted = 0
UNION ALL
SELECT CONCAT('kegiatan_renstra:', k.id) AS value_key,
       k.id AS sumber_id,
       'kegiatan_renstra' AS sumber_ref,
       r.kd_wilayah AS kd_wilayah,
       r.kd_opd AS kd_opd,
       CONCAT('Kegiatan — ', k.kode_kegiatan, ' ', k.uraian) AS uraian
FROM kegiatan_renstra_neo k
JOIN program_renstra_neo p ON p.id = k.program_id AND p.is_deleted = 0
JOIN sasaran_renstra_neo s ON s.id = p.sasaran_id AND s.is_deleted = 0
JOIN tujuan_renstra_neo t ON t.id = s.tujuan_id AND t.is_deleted = 0
JOIN misi_renstra_neo m ON m.id = t.misi_id AND m.is_deleted = 0
JOIN renstra_neo r ON r.id = m.renstra_id AND r.is_deleted = 0
WHERE k.is_deleted = 0
UNION ALL
SELECT CONCAT('sub_kegiatan_renstra:', sk.id) AS value_key,
       sk.id AS sumber_id,
       'sub_kegiatan_renstra' AS sumber_ref,
       r.kd_wilayah AS kd_wilayah,
       r.kd_opd AS kd_opd,
       CONCAT('Sub Kegiatan — ', rk.kode, ' ', rk.uraian) AS uraian
FROM sub_kegiatan_renstra_neo sk
JOIN rekening_kegiatan rk ON rk.id = sk.master_sub_kegiatan_id
JOIN kegiatan_renstra_neo k ON k.id = sk.kegiatan_renstra_id AND k.is_deleted = 0
JOIN program_renstra_neo p ON p.id = k.program_id AND p.is_deleted = 0
JOIN sasaran_renstra_neo s ON s.id = p.sasaran_id AND s.is_deleted = 0
JOIN tujuan_renstra_neo t ON t.id = s.tujuan_id AND t.is_deleted = 0
JOIN misi_renstra_neo m ON m.id = t.misi_id AND m.is_deleted = 0
JOIN renstra_neo r ON r.id = m.renstra_id AND r.is_deleted = 0
WHERE sk.is_deleted = 0;
