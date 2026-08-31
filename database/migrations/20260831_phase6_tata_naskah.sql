CREATE TABLE IF NOT EXISTS trx_naskah_status_history (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, naskah_id BIGINT NOT NULL, status_dari VARCHAR(20), status_ke VARCHAR(20) NOT NULL,
 catatan VARCHAR(400), username VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_naskah_status_history(naskah_id,created_at),
 CONSTRAINT fk_naskah_status_history FOREIGN KEY(naskah_id) REFERENCES trx_naskah_dinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ref_klasifikasi_keamanan(kode,nama,warna,username_insert,tgl_insert,keterangan)
SELECT 'B','Biasa/Terbuka','hitam','TRACE_TEST',NOW(),'Peraturan ANRI Nomor 5 Tahun 2025' WHERE NOT EXISTS(SELECT 1 FROM ref_klasifikasi_keamanan WHERE kode='B');
INSERT INTO ref_klasifikasi_keamanan(kode,nama,warna,username_insert,tgl_insert,keterangan)
SELECT 'T','Terbatas','hitam','TRACE_TEST',NOW(),'Peraturan ANRI Nomor 5 Tahun 2025' WHERE NOT EXISTS(SELECT 1 FROM ref_klasifikasi_keamanan WHERE kode='T');
INSERT INTO ref_klasifikasi_keamanan(kode,nama,warna,username_insert,tgl_insert,keterangan)
SELECT 'R','Rahasia','merah','TRACE_TEST',NOW(),'Peraturan ANRI Nomor 5 Tahun 2025' WHERE NOT EXISTS(SELECT 1 FROM ref_klasifikasi_keamanan WHERE kode='R');
INSERT INTO ref_klasifikasi_keamanan(kode,nama,warna,username_insert,tgl_insert,keterangan)
SELECT 'SR','Sangat Rahasia','merah','TRACE_TEST',NOW(),'Peraturan ANRI Nomor 5 Tahun 2025' WHERE NOT EXISTS(SELECT 1 FROM ref_klasifikasi_keamanan WHERE kode='SR');
