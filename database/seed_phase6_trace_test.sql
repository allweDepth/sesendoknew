START TRANSACTION;
SET @wilayah='76.01'; SET @opd='1.03.0.00.0.00.01.0000'; SET @tahun=2026;
SET @jenis=(SELECT id FROM ref_jenis_naskah WHERE nama='Nota Dinas' LIMIT 1);
SET @klasifikasi=(SELECT id FROM ref_klasifikasi_keamanan WHERE kode='B' LIMIT 1);
DELETE h FROM trx_naskah_status_history h JOIN trx_naskah_dinas t ON t.id=h.naskah_id WHERE t.uuid='TRACE_TEST_PHASE6';
DELETE FROM trx_naskah_dinas WHERE uuid='TRACE_TEST_PHASE6';
INSERT INTO trx_naskah_dinas(uuid,jenis_id,nomor,nomor_urut,tahun,klasifikasi_id,tanggal_surat,perihal,status,workflow_status,kd_wilayah,kd_opd,username_insert,tgl_insert,keterangan)
VALUES('TRACE_TEST_PHASE6',@jenis,'B/TRACE/DPUPR/2026',999,@tahun,@klasifikasi,'2026-08-31','Undangan Rapat Evaluasi Pelaksanaan Kegiatan','draft','draft',@wilayah,@opd,'TRACE_TEST',NOW(),'TRACE_TEST Phase 6');
SET @naskah=LAST_INSERT_ID();
INSERT INTO trx_naskah_struktur(naskah_id,struktur_json,kd_wilayah,kd_opd,tahun,tgl_insert,username_insert)
VALUES(@naskah,JSON_OBJECT('kepada','Sekretaris dan Para Kepala Bidang','dari','Kepala Dinas','tanggal_surat','31 Agustus 2026','perihal','Undangan Rapat Evaluasi Pelaksanaan Kegiatan','isi','Sehubungan dengan pelaksanaan kegiatan Tahun Anggaran 2026, diminta menghadiri rapat evaluasi pada waktu dan tempat yang telah ditentukan.','jbt_pemberi_tgs','Kepala Dinas','nama_penandatangan','TRACE TEST PEJABAT','tembusan',JSON_ARRAY(JSON_OBJECT('text','Bupati Pasangkayu sebagai laporan'))),@wilayah,@opd,@tahun,NOW(),'TRACE_TEST');
INSERT INTO trx_naskah_meta(naskah_id,meta_key,meta_value,kd_wilayah,kd_opd,username_insert,tgl_insert) VALUES
(@naskah,'arah','keluar',@wilayah,@opd,'TRACE_TEST',NOW()),(@naskah,'media','elektronik',@wilayah,@opd,'TRACE_TEST',NOW()),(@naskah,'klasifikasi_keamanan','B',@wilayah,@opd,'TRACE_TEST',NOW());
INSERT INTO trx_naskah_status_history(naskah_id,status_dari,status_ke,catatan,username,created_at) VALUES(@naskah,NULL,'draft','TRACE_TEST pembuatan naskah','TRACE_TEST',NOW());
COMMIT;
