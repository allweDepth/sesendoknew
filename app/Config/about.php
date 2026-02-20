CREATE TABLE renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kd_wilayah VARCHAR(10) NOT NULL,
    kd_opd VARCHAR(20) NOT NULL,
    periode_id INT NOT NULL,
    visi TEXT NOT NULL,
    status VARCHAR(50),
    kunci TINYINT(1) DEFAULT 0,
    setujui TINYINT(1) DEFAULT 0,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    username_update VARCHAR(100)
);
CREATE TABLE misi_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    renstra_id INT NOT NULL,
    kode_misi VARCHAR(10),
    nama_misi TEXT NOT NULL,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);
CREATE TABLE tujuan_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    misi_id INT NOT NULL,
    kode_tujuan VARCHAR(10),
    nama_tujuan TEXT NOT NULL,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);
CREATE TABLE sasaran_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tujuan_id INT NOT NULL,
    kode_sasaran VARCHAR(10),
    nama_sasaran TEXT NOT NULL,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);
CREATE TABLE indikator_sasaran_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sasaran_id INT NOT NULL,
    nama_indikator TEXT NOT NULL,
    satuan VARCHAR(50),
    baseline DECIMAL(18,2) DEFAULT 0,
    target_t1 DECIMAL(18,2) DEFAULT 0,
    target_t2 DECIMAL(18,2) DEFAULT 0,
    target_t3 DECIMAL(18,2) DEFAULT 0,
    target_t4 DECIMAL(18,2) DEFAULT 0,
    target_t5 DECIMAL(18,2) DEFAULT 0,
    target_akhir DECIMAL(18,2) DEFAULT 0,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);
CREATE TABLE program_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sasaran_id INT NOT NULL,
    kode_program VARCHAR(20),
    nama_program TEXT NOT NULL,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);
CREATE TABLE indikator_program_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    nama_indikator TEXT NOT NULL,
    satuan VARCHAR(50),
    baseline DECIMAL(18,2) DEFAULT 0,
    target_t1 DECIMAL(18,2) DEFAULT 0,
    target_t2 DECIMAL(18,2) DEFAULT 0,
    target_t3 DECIMAL(18,2) DEFAULT 0,
    target_t4 DECIMAL(18,2) DEFAULT 0,
    target_t5 DECIMAL(18,2) DEFAULT 0,
    target_akhir DECIMAL(18,2) DEFAULT 0,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);
CREATE TABLE kegiatan_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    kode_kegiatan VARCHAR(20),
    nama_kegiatan TEXT NOT NULL,
    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);
CREATE TABLE sub_kegiatan_renstra_neo (
    id INT AUTO_INCREMENT PRIMARY KEY,

    kegiatan_renstra_id INT NOT NULL,
    master_sub_kegiatan_id INT NOT NULL,

    lokasi VARCHAR(255),
    kelompok_sasaran VARCHAR(255),

    baseline DECIMAL(18,2) DEFAULT 0,

    target_t1 DECIMAL(18,2) DEFAULT 0,
    anggaran_t1 DECIMAL(18,2) DEFAULT 0,

    target_t2 DECIMAL(18,2) DEFAULT 0,
    anggaran_t2 DECIMAL(18,2) DEFAULT 0,

    target_t3 DECIMAL(18,2) DEFAULT 0,
    anggaran_t3 DECIMAL(18,2) DEFAULT 0,

    target_t4 DECIMAL(18,2) DEFAULT 0,
    anggaran_t4 DECIMAL(18,2) DEFAULT 0,

    target_t5 DECIMAL(18,2) DEFAULT 0,
    anggaran_t5 DECIMAL(18,2) DEFAULT 0,

    target_akhir DECIMAL(18,2) DEFAULT 0,

    disable TINYINT(1) DEFAULT 0,
    keterangan TEXT,
    tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    username_insert VARCHAR(100) NOT NULL,
    tgl_update DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    username_update VARCHAR(100)
);