CREATE TABLE IF NOT EXISTS riwayat_jabatan_neo (
 id INT AUTO_INCREMENT PRIMARY KEY, tahun YEAR NOT NULL, kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), pegawai_id INT NOT NULL,
 nomor_sk VARCHAR(150), jabatan VARCHAR(255) NOT NULL, unit_kerja VARCHAR(255), tmt DATE NOT NULL, tanggal_selesai DATE,
 keterangan VARCHAR(400), disable TINYINT(1) NOT NULL DEFAULT 0, is_deleted TINYINT(1) NOT NULL DEFAULT 0,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, tgl_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 username_insert VARCHAR(100), username_update VARCHAR(100), INDEX idx_rj_scope(kd_wilayah,kd_opd,tahun), INDEX idx_rj_pegawai(pegawai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS riwayat_pangkat_neo (
 id INT AUTO_INCREMENT PRIMARY KEY, tahun YEAR NOT NULL, kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), pegawai_id INT NOT NULL,
 nomor_sk VARCHAR(150), golongan VARCHAR(10) NOT NULL, ruang VARCHAR(10), tmt DATE NOT NULL, masa_kerja_tahun INT DEFAULT 0,
 keterangan VARCHAR(400), disable TINYINT(1) NOT NULL DEFAULT 0, is_deleted TINYINT(1) NOT NULL DEFAULT 0,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, tgl_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 username_insert VARCHAR(100), username_update VARCHAR(100), INDEX idx_rp_scope(kd_wilayah,kd_opd,tahun), INDEX idx_rp_pegawai(pegawai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cuti_pegawai_neo (
 id INT AUTO_INCREMENT PRIMARY KEY, tahun YEAR NOT NULL, kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), pegawai_id INT NOT NULL,
 nomor_surat VARCHAR(150), jenis_cuti VARCHAR(100) NOT NULL, tanggal_mulai DATE NOT NULL, tanggal_selesai DATE NOT NULL,
 jumlah_hari INT NOT NULL DEFAULT 0, status ENUM('diajukan','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diajukan',
 keterangan VARCHAR(400), disable TINYINT(1) NOT NULL DEFAULT 0, is_deleted TINYINT(1) NOT NULL DEFAULT 0,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, tgl_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 username_insert VARCHAR(100), username_update VARCHAR(100), INDEX idx_cuti_scope(kd_wilayah,kd_opd,tahun), INDEX idx_cuti_pegawai(pegawai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sk_pegawai_neo (
 id INT AUTO_INCREMENT PRIMARY KEY, tahun YEAR NOT NULL, kd_wilayah VARCHAR(60), kd_opd VARCHAR(60), pegawai_id INT NOT NULL,
 nomor_sk VARCHAR(150) NOT NULL, tanggal_sk DATE, jenis_sk VARCHAR(100) NOT NULL, tentang VARCHAR(400), file VARCHAR(255),
 keterangan VARCHAR(400), disable TINYINT(1) NOT NULL DEFAULT 0, is_deleted TINYINT(1) NOT NULL DEFAULT 0,
 tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, tgl_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 username_insert VARCHAR(100), username_update VARCHAR(100), INDEX idx_sk_scope(kd_wilayah,kd_opd,tahun), INDEX idx_sk_pegawai(pegawai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
