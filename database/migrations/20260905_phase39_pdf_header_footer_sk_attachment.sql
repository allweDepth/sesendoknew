ALTER TABLE pengaturan_neo
  ADD COLUMN IF NOT EXISTS header_pdf_aktif TINYINT(1) NOT NULL DEFAULT 0 AFTER margin_footer_mm,
  ADD COLUMN IF NOT EXISTS footer_pdf_aktif TINYINT(1) NOT NULL DEFAULT 0 AFTER header_pdf_aktif,
  ADD COLUMN IF NOT EXISTS tinggi_header_mm DECIMAL(5,1) NOT NULL DEFAULT 12.0 AFTER footer_pdf_aktif,
  ADD COLUMN IF NOT EXISTS tinggi_footer_mm DECIMAL(5,1) NOT NULL DEFAULT 10.0 AFTER tinggi_header_mm,
  ADD COLUMN IF NOT EXISTS header_pdf_json LONGTEXT NULL AFTER tinggi_footer_mm,
  ADD COLUMN IF NOT EXISTS footer_pdf_json LONGTEXT NULL AFTER header_pdf_json;
