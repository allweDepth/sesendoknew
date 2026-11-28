-- seSendoknew / Swakelola I, II, III, IV. Tidak memuat data paket atau identitas.
CREATE TABLE IF NOT EXISTS swakelola_teks_default_neo (
 kode_dokumen varchar(80) NOT NULL,
 tipe varchar(4) NOT NULL,
 nama_dokumen varchar(255) NOT NULL,
 kode_bagian varchar(80) NOT NULL,
 kode_lokasi varchar(255) NOT NULL COMMENT 'P001 atau T001/R001/C001/P001, posisi dalam dokumen',
 lokasi_induk varchar(255) DEFAULT NULL,
 jenis_blok varchar(20) NOT NULL COMMENT 'paragraph/table/row/cell',
 urutan int NOT NULL DEFAULT 0,
 teks_default longtext DEFAULT NULL COMMENT 'Mendukung {{nama_paket}}, {{nama_ppk}}, {{nama_pptk}}, dan variabel lain',
 format_json longtext NOT NULL COMMENT 'Font, indent, numbering, jarak, column_widths, colspan, JSON',
 aktif tinyint NOT NULL DEFAULT 1,
 diperbarui timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(kode_dokumen,kode_bagian,kode_lokasi),
 KEY tipe_dokumen(tipe,kode_dokumen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS swakelola_pengaturan_default_neo (
 tipe varchar(8) NOT NULL COMMENT 'SEMUA atau I/II/III/IV',
 kunci varchar(80) NOT NULL,
 nilai_json longtext NOT NULL,
 keterangan text NOT NULL,
 PRIMARY KEY(tipe,kunci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
