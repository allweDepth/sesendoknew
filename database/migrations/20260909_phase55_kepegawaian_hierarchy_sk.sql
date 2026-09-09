ALTER TABLE struktur_jabatan_opd_neo
  ADD COLUMN IF NOT EXISTS parent_kd_opd VARCHAR(60) NULL AFTER parent_id,
  ADD COLUMN IF NOT EXISTS nomor_sk_pengangkatan VARCHAR(150) NULL AFTER eselon,
  ADD COLUMN IF NOT EXISTS tanggal_sk_pengangkatan DATE NULL AFTER nomor_sk_pengangkatan,
  ADD COLUMN IF NOT EXISTS tmt_jabatan DATE NULL AFTER tanggal_sk_pengangkatan,
  ADD KEY IF NOT EXISTS idx_struktur_jabatan_parent_scope (kd_wilayah,parent_kd_opd,parent_id);

ALTER TABLE riwayat_jabatan_neo
  ADD COLUMN IF NOT EXISTS sumber_struktur_id BIGINT UNSIGNED NULL AFTER pegawai_id,
  ADD KEY IF NOT EXISTS idx_rj_sumber_struktur (sumber_struktur_id);

INSERT INTO db_asn_pemda_neo
  (kd_wilayah,kd_opd,nama,gelar_depan,gelar,nip,t4_lahir,tgl_lahir,golongan,ruang,jenis_kepeg,status_kepeg,jabatan,unit_kerja,aktif,disable,is_deleted,username_insert)
SELECT '76.01','BUPATI','BUPATI KABUPATEN PASANGKAYU','','','197001010000000001','Pasangkayu','1970-01-01','I','a','ASN-DUMMY','Aktif','Bupati Kabupaten Pasangkayu','Pemerintah Kabupaten Pasangkayu',1,0,0,'migration_phase55'
WHERE NOT EXISTS (SELECT 1 FROM db_asn_pemda_neo WHERE kd_wilayah='76.01' AND kd_opd='BUPATI' AND nip='197001010000000001');

INSERT INTO db_asn_pemda_neo
  (kd_wilayah,kd_opd,nama,gelar_depan,gelar,nip,t4_lahir,tgl_lahir,golongan,ruang,jenis_kepeg,status_kepeg,jabatan,unit_kerja,aktif,disable,is_deleted,username_insert)
SELECT '76.01','SETDA','SEKRETARIS DAERAH KABUPATEN PASANGKAYU','','','197501010000000001','Pasangkayu','1975-01-01','I','a','ASN-DUMMY','Aktif','Sekretaris Daerah','Sekretariat Daerah Kabupaten Pasangkayu',1,0,0,'migration_phase55'
WHERE NOT EXISTS (SELECT 1 FROM db_asn_pemda_neo WHERE kd_wilayah='76.01' AND kd_opd='SETDA' AND nip='197501010000000001');

INSERT INTO struktur_jabatan_opd_neo
  (kd_wilayah,kd_opd,tahun,pegawai_id,parent_id,parent_kd_opd,nama_jabatan,kelompok_jabatan,eselon,nomor_sk_pengangkatan,tanggal_sk_pengangkatan,tmt_jabatan,urutan,keterangan,is_deleted,username_insert)
SELECT '76.01','BUPATI',2026,a.id,NULL,NULL,'Bupati Kabupaten Pasangkayu','Pimpinan Daerah','I.a','SK-BUPATI-PASANGKAYU-001/2026','2026-01-01','2026-01-01',1,'Dummy struktur wilayah untuk pengujian alur kepegawaian.',0,'migration_phase55'
FROM db_asn_pemda_neo a
WHERE a.kd_wilayah='76.01' AND a.kd_opd='BUPATI' AND a.nip='197001010000000001'
  AND NOT EXISTS (SELECT 1 FROM struktur_jabatan_opd_neo s WHERE s.kd_wilayah='76.01' AND s.kd_opd='BUPATI' AND s.tahun=2026 AND s.nama_jabatan='Bupati Kabupaten Pasangkayu' AND s.is_deleted=0);

INSERT INTO struktur_jabatan_opd_neo
  (kd_wilayah,kd_opd,tahun,pegawai_id,parent_id,parent_kd_opd,nama_jabatan,kelompok_jabatan,eselon,nomor_sk_pengangkatan,tanggal_sk_pengangkatan,tmt_jabatan,urutan,keterangan,is_deleted,username_insert)
SELECT '76.01','SETDA',2026,a.id,s.id,'BUPATI','Sekretaris Daerah','Pimpinan Tinggi Madya','II.a','SK-BUPATI-PASANGKAYU-002/2026','2026-01-02','2026-01-02',1,'Dummy struktur wilayah untuk pengujian alur kepegawaian.',0,'migration_phase55'
FROM db_asn_pemda_neo a
JOIN struktur_jabatan_opd_neo s ON s.kd_wilayah='76.01' AND s.kd_opd='BUPATI' AND s.tahun=2026 AND s.nama_jabatan='Bupati Kabupaten Pasangkayu' AND s.is_deleted=0
WHERE a.kd_wilayah='76.01' AND a.kd_opd='SETDA' AND a.nip='197501010000000001'
  AND NOT EXISTS (SELECT 1 FROM struktur_jabatan_opd_neo x WHERE x.kd_wilayah='76.01' AND x.kd_opd='SETDA' AND x.tahun=2026 AND x.nama_jabatan='Sekretaris Daerah' AND x.is_deleted=0);

INSERT INTO riwayat_jabatan_neo
  (tahun,kd_wilayah,kd_opd,pegawai_id,sumber_struktur_id,nomor_sk,jabatan,unit_kerja,tmt,keterangan,username_insert)
SELECT s.tahun,s.kd_wilayah,s.kd_opd,s.pegawai_id,s.id,s.nomor_sk_pengangkatan,s.nama_jabatan,'Pemerintah Kabupaten Pasangkayu',COALESCE(s.tmt_jabatan,s.tanggal_sk_pengangkatan),s.keterangan,'migration_phase55'
FROM struktur_jabatan_opd_neo s
WHERE s.kd_wilayah='76.01' AND s.kd_opd IN ('BUPATI','SETDA') AND s.tahun=2026 AND s.is_deleted=0
  AND NOT EXISTS (SELECT 1 FROM riwayat_jabatan_neo r WHERE r.sumber_struktur_id=s.id AND r.is_deleted=0);
