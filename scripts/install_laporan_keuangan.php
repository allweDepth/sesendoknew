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

echo "Tabel Neraca dan CaLK siap digunakan.\n";
