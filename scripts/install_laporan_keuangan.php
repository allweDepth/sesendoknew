<?php
if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit;
}

$config = require __DIR__ . '/../config/database.php';
$database = getenv('SESENDOK_DB_NAME') ?: $config['dbname'];
$admin = getenv('SESENDOK_SETUP_ADMIN') ?: 'root';
$password = getenv('SESENDOK_SETUP_ADMIN_PASSWORD') ?: '';
$pdo = new PDO(
  'mysql:unix_socket=' . $config['socket'] . ';dbname=' . $database . ';charset=utf8mb4',
  $admin,
  $password,
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("CREATE TABLE IF NOT EXISTS laporan_neraca_saldo_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun SMALLINT UNSIGNED NOT NULL,
  kode_akun VARCHAR(64) NOT NULL,
  uraian VARCHAR(500) NOT NULL,
  kelompok ENUM('ASET','KEWAJIBAN','EKUITAS') NOT NULL,
  saldo_awal DECIMAL(20,2) NOT NULL DEFAULT 0,
  saldo_akhir DECIMAL(20,2) NOT NULL DEFAULT 0,
  catatan TEXT DEFAULT NULL,
  sumber ENUM('manual','excel') NOT NULL DEFAULT 'manual',
  username_insert VARCHAR(100) DEFAULT NULL,
  tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_laporan_neraca_scope (kd_wilayah,kd_opd,tahun,is_deleted),
  KEY idx_laporan_neraca_akun (kode_akun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS laporan_calk_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun SMALLINT UNSIGNED NOT NULL,
  bagian VARCHAR(40) NOT NULL,
  judul VARCHAR(255) NOT NULL,
  uraian TEXT NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  username_insert VARCHAR(100) DEFAULT NULL,
  tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_deleted TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_laporan_calk_scope (kd_wilayah,kd_opd,tahun,is_deleted,urutan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS laporan_keuangan_transaksi_neo (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_wilayah VARCHAR(60) NOT NULL,
  kd_opd VARCHAR(60) NOT NULL,
  tahun SMALLINT UNSIGNED NOT NULL,
  transaksi_uuid CHAR(36) NOT NULL,
  kontrak_id BIGINT UNSIGNED NOT NULL,
  jenis_transaksi ENUM('PROGRES','LS','UANG_MUKA_KONTRAK','UP','TU','GU') NOT NULL,
  nomor_bukti VARCHAR(120) DEFAULT NULL,
  nomor_sp2d VARCHAR(120) DEFAULT NULL,
  tanggal_sp2d DATE DEFAULT NULL,
  nomor_sp2d_uang_muka VARCHAR(120) DEFAULT NULL,
  nomor_sp2d_sumber VARCHAR(120) DEFAULT NULL,
  nomor_spj VARCHAR(120) DEFAULT NULL,
  tanggal_spj DATE DEFAULT NULL,
  tanggal_pengesahan_spj DATE DEFAULT NULL,
  nomor_bast VARCHAR(120) DEFAULT NULL,
  tanggal_bast DATE DEFAULT NULL,
  nomor_up_tu VARCHAR(120) DEFAULT NULL,
  nilai_potongan_uang_muka DECIMAL(20,2) NOT NULL DEFAULT 0,
  status_verifikasi ENUM('MENUNGGU_VERIFIKASI','TERVERIFIKASI') NOT NULL DEFAULT 'MENUNGGU_VERIFIKASI',
  username_input VARCHAR(100) DEFAULT NULL,
  username_verifikasi VARCHAR(100) DEFAULT NULL,
  tgl_verifikasi DATETIME DEFAULT NULL,
  tgl_insert DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tgl_update DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_laporan_keuangan_transaksi (kd_wilayah,kd_opd,tahun,transaksi_uuid),
  KEY idx_laporan_keuangan_transaksi_status (kd_wilayah,kd_opd,tahun,status_verifikasi),
  KEY idx_laporan_keuangan_transaksi_kontrak (kontrak_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$columns = $pdo->query('SHOW COLUMNS FROM daftar_realisasi_neo')->fetchAll(PDO::FETCH_COLUMN, 0);
if (!in_array('nilai_lra', $columns, true)) {
  $pdo->exec('ALTER TABLE daftar_realisasi_neo ADD COLUMN nilai_lra DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER jumlah');
}
$transactionColumns = $pdo->query('SHOW COLUMNS FROM laporan_keuangan_transaksi_neo')->fetchAll(PDO::FETCH_COLUMN, 0);
if (!in_array('nomor_sp2d_uang_muka', $transactionColumns, true)) {
  $pdo->exec('ALTER TABLE laporan_keuangan_transaksi_neo ADD COLUMN nomor_sp2d_uang_muka VARCHAR(120) DEFAULT NULL AFTER tanggal_sp2d');
}

echo "Tabel laporan keuangan dan kolom realisasi LRA siap digunakan.\n";
