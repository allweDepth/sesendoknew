ALTER TABLE halaman_berita ADD COLUMN IF NOT EXISTS jenis_halaman ENUM('berita','data_teknis','organisasi','pelayanan') NOT NULL DEFAULT 'berita' AFTER slug;
ALTER TABLE halaman_berita ADD COLUMN IF NOT EXISTS aktif TINYINT NOT NULL DEFAULT 1 AFTER jenis_halaman;
ALTER TABLE halaman_berita ADD INDEX IF NOT EXISTS idx_halaman_publik (jenis_halaman,aktif,is_deleted,kd_wilayah);
ALTER TABLE kontrak_dokumen_neo ADD COLUMN IF NOT EXISTS tgl_update DATETIME NULL AFTER username_insert;
ALTER TABLE kontrak_dokumen_neo ADD COLUMN IF NOT EXISTS username_update VARCHAR(100) NULL AFTER tgl_update;
