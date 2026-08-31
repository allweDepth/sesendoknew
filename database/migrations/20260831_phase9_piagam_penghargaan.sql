INSERT INTO ref_jenis_naskah
  (kelompok_id,nama,kode_form,sub_kategori,urutan,schema_json,schema_version,allowed_roles,auto_generate_nomor,kd_wilayah,kd_opd,username_insert,tgl_insert,keterangan)
SELECT kelompok_id,'Piagam Penghargaan','surat_keterangan','Naskah Dinas Khusus',14,schema_json,schema_version,allowed_roles,auto_generate_nomor,kd_wilayah,kd_opd,'system',NOW(),'Ditambahkan untuk melengkapi bentuk Tata Naskah ANRI 5/2025'
FROM ref_jenis_naskah src
WHERE src.nama='Sertifikat'
  AND NOT EXISTS (SELECT 1 FROM ref_jenis_naskah WHERE nama='Piagam Penghargaan')
LIMIT 1;
