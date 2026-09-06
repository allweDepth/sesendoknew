CREATE TABLE IF NOT EXISTS page_setup_opd_neo (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kd_wilayah VARCHAR(60) NOT NULL, kd_opd VARCHAR(60) NOT NULL, tahun YEAR NOT NULL,
 ukuran_kertas VARCHAR(32) NOT NULL DEFAULT 'A4', orientasi_kertas ENUM('AUTO','P','L') NOT NULL DEFAULT 'AUTO',
 font_pdf VARCHAR(20) NOT NULL DEFAULT 'helvetica', ukuran_font_pdf DECIMAL(4,1) NOT NULL DEFAULT 10,
 lebar_kertas_mm DECIMAL(7,1) NULL, tinggi_kertas_mm DECIMAL(7,1) NULL,
 margin_atas_mm DECIMAL(5,1) NOT NULL DEFAULT 10, margin_kanan_mm DECIMAL(5,1) NOT NULL DEFAULT 10,
 margin_bawah_mm DECIMAL(5,1) NOT NULL DEFAULT 12, margin_kiri_mm DECIMAL(5,1) NOT NULL DEFAULT 10,
 margin_header_mm DECIMAL(5,1) NOT NULL DEFAULT 5, margin_footer_mm DECIMAL(5,1) NOT NULL DEFAULT 8,
 header_pdf_aktif TINYINT NOT NULL DEFAULT 0, footer_pdf_aktif TINYINT NOT NULL DEFAULT 0,
 tinggi_header_mm DECIMAL(5,1) NOT NULL DEFAULT 12, tinggi_footer_mm DECIMAL(5,1) NOT NULL DEFAULT 10,
 header_pdf_json LONGTEXT NULL, footer_pdf_json LONGTEXT NULL,
 tinggi_tanda_tangan_mm DECIMAL(5,1) NOT NULL DEFAULT 35,
 posisi_tanda_tangan ENUM('kiri','tengah','kanan','dua_kolom') NOT NULL DEFAULT 'kanan',
 teks_tanda_tangan VARCHAR(500) NULL,
 tgl_insert DATETIME DEFAULT CURRENT_TIMESTAMP, username_insert VARCHAR(100) NULL,
 tgl_update DATETIME NULL, username_update VARCHAR(100) NULL, is_deleted TINYINT NOT NULL DEFAULT 0,
 UNIQUE KEY uq_page_setup_opd (kd_wilayah,kd_opd,tahun), KEY idx_page_setup_scope(kd_wilayah,kd_opd,tahun,is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
