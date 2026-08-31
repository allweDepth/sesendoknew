START TRANSACTION;
SET @wilayah='76.01', @opd='1.03.0.00.0.00.01.0000', @tahun=2026, @user='TRACE_TEST_PHASE3';
SET @periode=(SELECT id FROM periode_rpjmd ORDER BY id LIMIT 1);
SET @sub=(SELECT id FROM rekening_kegiatan WHERE kode='X.XX.1.2.01.1' LIMIT 1);
SET @satuan=(SELECT id FROM satuan_neo WHERE is_deleted=0 ORDER BY id LIMIT 1);
SET @dana=(SELECT id FROM sumber_dana_neo WHERE is_deleted=0 ORDER BY id LIMIT 1);
SET @aturan=(SELECT id FROM peraturan_neo ORDER BY id LIMIT 1);
SET @akun=(SELECT kode FROM akun_neo WHERE kode='5.1.02.02.05.0044' LIMIT 1);

INSERT INTO master_biaya (tipe,kode,uraian,spesifikasi,satuan_id,harga,tkdn,keterangan,kd_wilayah,tahun,peraturan_id,disable,is_deleted,tgl_insert,username_insert)
SELECT 'ssh','TRACE_TEST.P3.001','Kertas kerja perencanaan TRACE_TEST','A4 80 gsm',@satuan,75000,40,'TRACE_TEST Phase 1-2 untuk Phase 3',@wilayah,@tahun,@aturan,0,0,NOW(),@user
WHERE NOT EXISTS (SELECT 1 FROM master_biaya WHERE kode='TRACE_TEST.P3.001' AND kd_wilayah=@wilayah AND tahun=@tahun AND is_deleted=0);
SET @biaya=(SELECT id FROM master_biaya WHERE kode='TRACE_TEST.P3.001' AND kd_wilayah=@wilayah AND tahun=@tahun AND is_deleted=0 ORDER BY id DESC LIMIT 1);
INSERT INTO master_biaya_akun (master_biaya_id,kd_akun,kd_wilayah,peraturan_id,disable,is_deleted,tgl_insert,username_insert)
SELECT @biaya,@akun,@wilayah,@aturan,0,0,NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM master_biaya_akun WHERE master_biaya_id=@biaya AND kd_akun=@akun AND is_deleted=0);

INSERT INTO renstra_neo (kd_wilayah,kd_opd,periode_id,visi,status,kunci,setujui,disable,keterangan,tgl_insert,username_insert,is_deleted)
SELECT @wilayah,@opd,@periode,'TRACE_TEST: Pemerintahan daerah yang terencana dan akuntabel','aktif',1,1,0,'TRACE_TEST Phase 3',NOW(),@user,0
WHERE NOT EXISTS (SELECT 1 FROM renstra_neo WHERE visi LIKE 'TRACE_TEST:%' AND kd_wilayah=@wilayah AND kd_opd=@opd AND is_deleted=0);
SET @renstra=(SELECT id FROM renstra_neo WHERE visi LIKE 'TRACE_TEST:%' AND kd_wilayah=@wilayah AND kd_opd=@opd AND is_deleted=0 ORDER BY id DESC LIMIT 1);
INSERT INTO misi_renstra_neo (renstra_id,nama_misi,keterangan,username_insert) SELECT @renstra,'TRACE_TEST: Meningkatkan kualitas perencanaan','TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM misi_renstra_neo WHERE renstra_id=@renstra AND nama_misi LIKE 'TRACE_TEST:%' AND is_deleted=0);
SET @misi=(SELECT id FROM misi_renstra_neo WHERE renstra_id=@renstra AND nama_misi LIKE 'TRACE_TEST:%' AND is_deleted=0 LIMIT 1);
INSERT INTO tujuan_renstra_neo (misi_id,kode_tujuan,nama_tujuan,keterangan,username_insert) SELECT @misi,'TT.01','TRACE_TEST: Perencanaan perangkat daerah meningkat','TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM tujuan_renstra_neo WHERE misi_id=@misi AND kode_tujuan='TT.01' AND is_deleted=0);
SET @tujuan=(SELECT id FROM tujuan_renstra_neo WHERE misi_id=@misi AND kode_tujuan='TT.01' AND is_deleted=0 LIMIT 1);
INSERT INTO sasaran_renstra_neo (tujuan_id,kode_sasaran,nama_sasaran,keterangan,username_insert) SELECT @tujuan,'SS.01','TRACE_TEST: Dokumen perencanaan tepat waktu','TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM sasaran_renstra_neo WHERE tujuan_id=@tujuan AND kode_sasaran='SS.01' AND is_deleted=0);
SET @sasaran=(SELECT id FROM sasaran_renstra_neo WHERE tujuan_id=@tujuan AND kode_sasaran='SS.01' AND is_deleted=0 LIMIT 1);
INSERT INTO indikator_sasaran_renstra_neo (sasaran_id,nama_indikator,satuan,baseline,target_t1,target_t2,target_t3,target_t4,target_t5,target_akhir,keterangan,username_insert)
SELECT @sasaran,'TRACE_TEST: Persentase dokumen tepat waktu','%',70,80,85,90,95,100,100,'TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM indikator_sasaran_renstra_neo WHERE sasaran_id=@sasaran AND nama_indikator LIKE 'TRACE_TEST:%' AND is_deleted=0);
INSERT INTO program_renstra_neo (sasaran_id,kode_program,uraian,keterangan,username_insert) SELECT @sasaran,'X.XX.1','TRACE_TEST: Program Penunjang Urusan Pemerintahan','TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM program_renstra_neo WHERE sasaran_id=@sasaran AND kode_program='X.XX.1' AND is_deleted=0);
SET @program=(SELECT id FROM program_renstra_neo WHERE sasaran_id=@sasaran AND kode_program='X.XX.1' AND is_deleted=0 LIMIT 1);
INSERT INTO indikator_program_renstra_neo (program_id,nama_indikator,satuan,baseline,target_t1,target_t2,target_t3,target_t4,target_t5,target_akhir,keterangan,username_insert)
SELECT @program,'TRACE_TEST: Capaian program','%',70,80,85,90,95,100,100,'TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM indikator_program_renstra_neo WHERE program_id=@program AND nama_indikator LIKE 'TRACE_TEST:%' AND is_deleted=0);
INSERT INTO kegiatan_renstra_neo (program_id,kode_kegiatan,uraian,keterangan,username_insert) SELECT @program,'X.XX.1.2.01','TRACE_TEST: Perencanaan dan Penganggaran','TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM kegiatan_renstra_neo WHERE program_id=@program AND kode_kegiatan='X.XX.1.2.01' AND is_deleted=0);
SET @kegiatan=(SELECT id FROM kegiatan_renstra_neo WHERE program_id=@program AND kode_kegiatan='X.XX.1.2.01' AND is_deleted=0 LIMIT 1);
INSERT INTO sub_kegiatan_renstra_neo (kegiatan_renstra_id,master_sub_kegiatan_id,lokasi,kelompok_sasaran,baseline,target_t1,anggaran_t1,target_t2,anggaran_t2,target_t3,anggaran_t3,target_t4,anggaran_t4,target_t5,anggaran_t5,target_akhir,keterangan,username_insert)
SELECT @kegiatan,@sub,'Kabupaten Mamuju Tengah','Perangkat Daerah',1,1,75000000,1,80000000,1,85000000,1,90000000,1,95000000,1,'TRACE_TEST Phase 3',@user WHERE NOT EXISTS (SELECT 1 FROM sub_kegiatan_renstra_neo WHERE kegiatan_renstra_id=@kegiatan AND master_sub_kegiatan_id=@sub AND is_deleted=0);
SET @renstra_sub=(SELECT id FROM sub_kegiatan_renstra_neo WHERE kegiatan_renstra_id=@kegiatan AND master_sub_kegiatan_id=@sub AND is_deleted=0 LIMIT 1);

INSERT INTO rkpd_neo (renstra_sub_kegiatan_id,kd_wilayah,kd_opd,tahun,kd_program,kd_kegiatan,kd_sub_keg,indikator,target,satuan_id,pagu,sumber_dana_id,lokasi,kelompok_sasaran,status,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT @renstra_sub,@wilayah,@opd,@tahun,'X.XX.1','X.XX.1.2.01','X.XX.1.2.01.1','TRACE_TEST: Dokumen perencanaan tersusun',1,@satuan,75000000,@dana,'Kabupaten Mamuju Tengah','Perangkat Daerah','approved',1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM rkpd_neo WHERE renstra_sub_kegiatan_id=@renstra_sub AND tahun=@tahun AND is_deleted=0);
SET @rkpd=(SELECT id FROM rkpd_neo WHERE renstra_sub_kegiatan_id=@renstra_sub AND tahun=@tahun AND is_deleted=0 LIMIT 1);

INSERT INTO renja_neo (source_table,source_id,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,uraian,jenis_standar_harga,id_standar_harga,komponen,spesifikasi,harga_satuan,volume,jumlah,sumber_dana_id,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT 'rkpd_neo',@rkpd,@wilayah,@opd,@tahun,'X.XX.1.2.01.1',@akun,'TRACE_TEST: Penyusunan dokumen Renja','ssh',@biaya,'Kertas kerja perencanaan','A4 80 gsm',75000,1000,75000000,@dana,1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM renja_neo WHERE source_table='rkpd_neo' AND source_id=@rkpd AND is_deleted=0);
SET @renja=(SELECT id FROM renja_neo WHERE source_table='rkpd_neo' AND source_id=@rkpd AND is_deleted=0 LIMIT 1);
INSERT INTO rka_neo (source_table,source_id,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,uraian,jenis_standar_harga,id_standar_harga,komponen,spesifikasi,harga_satuan,volume,jumlah,sumber_dana_id,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT 'renja_neo',@renja,@wilayah,@opd,@tahun,'X.XX.1.2.01.1',@akun,'TRACE_TEST: Penyusunan dokumen RKA','ssh',@biaya,'Kertas kerja perencanaan','A4 80 gsm',75000,1000,75000000,@dana,1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM rka_neo WHERE source_table='renja_neo' AND source_id=@renja AND is_deleted=0);
SET @rka=(SELECT id FROM rka_neo WHERE source_table='renja_neo' AND source_id=@renja AND is_deleted=0 LIMIT 1);
INSERT INTO dpa_neo (source_table,source_id,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,uraian,jenis_standar_harga,id_standar_harga,komponen,spesifikasi,harga_satuan,volume,jumlah,sumber_dana_id,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT 'rka_neo',@rka,@wilayah,@opd,@tahun,'X.XX.1.2.01.1',@akun,'TRACE_TEST: Dokumen Pelaksanaan Anggaran','ssh',@biaya,'Kertas kerja perencanaan','A4 80 gsm',75000,1000,75000000,@dana,1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM dpa_neo WHERE source_table='rka_neo' AND source_id=@rka AND is_deleted=0);
SET @dpa=(SELECT id FROM dpa_neo WHERE source_table='rka_neo' AND source_id=@rka AND is_deleted=0 LIMIT 1);

INSERT INTO rkpd_p_neo (source_rkpd_id,renstra_sub_kegiatan_id,kd_wilayah,kd_opd,tahun,kd_program,kd_kegiatan,kd_sub_keg,indikator,target_awal,pagu_awal,target,satuan_id,pagu,sumber_dana_id,lokasi,kelompok_sasaran,status_perubahan,status,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT @rkpd,@renstra_sub,@wilayah,@opd,@tahun,'X.XX.1','X.XX.1.2.01','X.XX.1.2.01.1','TRACE_TEST: Dokumen perencanaan perubahan',1,75000000,1,@satuan,82500000,@dana,'Kabupaten Mamuju Tengah','Perangkat Daerah','ubah','approved',1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM rkpd_p_neo WHERE source_rkpd_id=@rkpd AND is_deleted=0);

INSERT INTO renja_p_neo (source_table,source_id,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,uraian,harga_satuan_awal,volume_awal,jumlah_awal,harga_satuan,volume,jumlah,sumber_dana_id,status_perubahan,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT 'renja_neo',@renja,@wilayah,@opd,@tahun,'X.XX.1.2.01.1',@akun,'TRACE_TEST: Renja Perubahan',75000,1000,75000000,75000,1100,82500000,@dana,'ubah',1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM renja_p_neo WHERE source_table='renja_neo' AND source_id=@renja AND is_deleted=0);
INSERT INTO rka_p_neo (source_table,source_id,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,uraian,harga_satuan_awal,volume_awal,jumlah_awal,harga_satuan,volume,jumlah,sumber_dana_id,status_perubahan,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT 'rka_neo',@rka,@wilayah,@opd,@tahun,'X.XX.1.2.01.1',@akun,'TRACE_TEST: RKA Perubahan',75000,1000,75000000,75000,1100,82500000,@dana,'ubah',1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM rka_p_neo WHERE source_table='rka_neo' AND source_id=@rka AND is_deleted=0);
INSERT INTO dppa_neo (source_table,source_id,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,uraian,harga_satuan_awal,volume_awal,jumlah_awal,harga_satuan,volume,jumlah,sumber_dana_id,status_perubahan,kunci,setujui,keterangan,tgl_insert,username_insert)
SELECT 'dpa_neo',@dpa,@wilayah,@opd,@tahun,'X.XX.1.2.01.1',@akun,'TRACE_TEST: DPPA',75000,1000,75000000,75000,1100,82500000,@dana,'ubah',1,1,'TRACE_TEST Phase 3',NOW(),@user WHERE NOT EXISTS (SELECT 1 FROM dppa_neo WHERE source_table='dpa_neo' AND source_id=@dpa AND is_deleted=0);
COMMIT;
