-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 01 Sep 2026 pada 03.58
-- Versi server: 12.3.3-MariaDB
-- Versi PHP: 8.5.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `sesendoknew_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi_pegawai_neo`
--

CREATE TABLE `absensi_pegawai_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` enum('HADIR','DINAS_LUAR','IZIN','SAKIT','CUTI','ALPA','WFH') NOT NULL DEFAULT 'HADIR',
  `latitude_masuk` decimal(10,7) DEFAULT NULL,
  `longitude_masuk` decimal(10,7) DEFAULT NULL,
  `latitude_pulang` decimal(10,7) DEFAULT NULL,
  `longitude_pulang` decimal(10,7) DEFAULT NULL,
  `foto_masuk` varchar(500) DEFAULT NULL,
  `foto_pulang` varchar(500) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `akun_neo`
--

CREATE TABLE `akun_neo` (
  `id` int(8) NOT NULL,
  `akun` int(11) NOT NULL,
  `kelompok` int(11) DEFAULT NULL,
  `jenis_akun` int(11) DEFAULT NULL,
  `objek` int(11) DEFAULT NULL,
  `rincian_objek` int(11) DEFAULT NULL,
  `sub_rincian_objek` int(11) DEFAULT NULL,
  `kode` varchar(25) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `belanja` tinyint(1) DEFAULT NULL,
  `pembiayaan` tinyint(1) DEFAULT NULL,
  `peraturan` int(11) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `aksi` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `analisa_rab`
--

CREATE TABLE `analisa_rab` (
  `id` bigint(20) NOT NULL,
  `kontrak_id` bigint(20) DEFAULT NULL,
  `kode` varchar(100) DEFAULT NULL,
  `uraian` varchar(500) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `harga_satuan_hps` decimal(20,2) DEFAULT NULL,
  `harga_satuan_penawaran` decimal(20,2) DEFAULT NULL,
  `harga_satuan_kontrak` decimal(20,2) DEFAULT NULL,
  `volume_hps` decimal(20,2) DEFAULT NULL,
  `volume_penawaran` decimal(20,2) DEFAULT NULL,
  `volume_kontrak` decimal(20,2) DEFAULT NULL,
  `jumlah_hps` decimal(20,2) DEFAULT NULL,
  `jumlah_penawaran` decimal(20,2) DEFAULT NULL,
  `jumlah_kontrak` decimal(20,2) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggaran_copy_log`
--

CREATE TABLE `anggaran_copy_log` (
  `id` bigint(20) NOT NULL,
  `from_tahap` varchar(20) DEFAULT NULL,
  `to_tahap` varchar(20) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `jumlah_data` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `tgl_copy` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggaran_perubahan_template`
--

CREATE TABLE `anggaran_perubahan_template` (
  `id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga_awal` varchar(20) DEFAULT NULL,
  `id_standar_harga_awal` bigint(20) DEFAULT NULL,
  `komponen_awal` text DEFAULT NULL,
  `spesifikasi_awal` text DEFAULT NULL,
  `tkdn_awal` decimal(10,2) DEFAULT NULL,
  `pajak_awal` decimal(10,2) DEFAULT NULL,
  `harga_satuan_awal` decimal(20,4) DEFAULT NULL,
  `volume_awal` decimal(20,4) DEFAULT NULL,
  `jumlah_awal` decimal(20,4) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `status_perubahan` enum('awal','ubah','tambah','hapus') DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggaran_program_renstra_neo`
--

CREATE TABLE `anggaran_program_renstra_neo` (
  `id` bigint(20) NOT NULL,
  `program_id` bigint(20) NOT NULL,
  `tahun` year(4) NOT NULL,
  `pagu` decimal(18,2) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `user_insert` varchar(50) DEFAULT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_update` varchar(50) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggaran_template`
--

CREATE TABLE `anggaran_template` (
  `id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggaran_workflow_log`
--

CREATE TABLE `anggaran_workflow_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `source_table` varchar(30) NOT NULL,
  `target_table` varchar(30) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) NOT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `jumlah_data` int(11) NOT NULL DEFAULT 0,
  `username` varchar(100) NOT NULL,
  `tgl_copy` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `aset_neo`
--

CREATE TABLE `aset_neo` (
  `id` int(8) NOT NULL,
  `akun` int(11) NOT NULL,
  `kelompok` int(11) DEFAULT NULL,
  `jenis_akun` int(11) DEFAULT NULL,
  `objek` int(11) DEFAULT NULL,
  `rincian_objek` int(11) DEFAULT NULL,
  `sub_rincian_objek` int(11) DEFAULT NULL,
  `kode` varchar(25) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `belanja` tinyint(1) DEFAULT NULL,
  `pembiayaan` tinyint(1) DEFAULT NULL,
  `peraturan` int(11) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `aksi` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache_schema_naskah`
--

CREATE TABLE `cache_schema_naskah` (
  `jenis_id` int(11) NOT NULL,
  `schema_version` int(11) NOT NULL,
  `schema_json` longtext NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cuti_pegawai_neo`
--

CREATE TABLE `cuti_pegawai_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `pegawai_id` int(11) NOT NULL,
  `nomor_surat` varchar(150) DEFAULT NULL,
  `jenis_cuti` varchar(100) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jumlah_hari` int(11) NOT NULL DEFAULT 0,
  `status` enum('diajukan','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diajukan',
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar_paket_neo`
--

CREATE TABLE `daftar_paket_neo` (
  `id` int(11) NOT NULL,
  `kd_rup` varchar(25) DEFAULT NULL,
  `kd_paket` varchar(25) DEFAULT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `sumber_tahap` enum('dpa','dppa') DEFAULT NULL,
  `anggaran_id` bigint(20) DEFAULT NULL,
  `uraian` text NOT NULL,
  `id_uraian` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`id_uraian`)),
  `kd_sub_keg` text NOT NULL,
  `volume` int(11) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `harga_satuan` decimal(36,12) DEFAULT NULL,
  `jumlah` decimal(36,12) DEFAULT NULL,
  `pagu` decimal(36,12) NOT NULL,
  `metode_pengadaan` varchar(255) DEFAULT NULL,
  `metode_pemilihan` varchar(255) DEFAULT NULL,
  `pengadaan_penyedia` varchar(255) DEFAULT NULL,
  `jns_kontrak` varchar(255) DEFAULT NULL,
  `renc_output` varchar(255) DEFAULT NULL,
  `output` varchar(255) DEFAULT NULL,
  `id_rekanan` int(11) NOT NULL,
  `nama_rekanan` varchar(255) DEFAULT NULL,
  `nama_ppk` varchar(255) DEFAULT NULL,
  `nip_ppk` varchar(25) DEFAULT NULL,
  `nama_pptk` varchar(255) DEFAULT NULL,
  `waktu_pelaksanaan` int(11) DEFAULT NULL,
  `waktu_pemeliharaan` int(11) DEFAULT NULL,
  `nip_pptk` varchar(25) DEFAULT NULL,
  `tgl_kontrak` date DEFAULT NULL,
  `no_kontrak` varchar(255) DEFAULT NULL,
  `tgl_persiapan_kont` datetime DEFAULT NULL,
  `no_persiapan_kont` varchar(255) DEFAULT NULL,
  `tgl_spmk` datetime DEFAULT NULL,
  `no_spmk` varchar(255) DEFAULT NULL,
  `addendum` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`addendum`)),
  `tgl_undangan` datetime DEFAULT NULL,
  `no_undangan` varchar(255) DEFAULT NULL,
  `tgl_penawaran` datetime DEFAULT NULL,
  `no_penawaran` varchar(255) DEFAULT NULL,
  `tgl_nego` datetime DEFAULT NULL,
  `no_nego` varchar(255) DEFAULT NULL,
  `tgl_sppbj` date DEFAULT NULL,
  `no_sppbj` varchar(255) DEFAULT NULL,
  `tgl_pho` datetime DEFAULT NULL,
  `no_pho` varchar(255) DEFAULT NULL,
  `tgl_fho` datetime DEFAULT NULL,
  `no_fho` varchar(255) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `file_kontrak` varchar(255) DEFAULT NULL,
  `file_addendum` varchar(255) DEFAULT NULL,
  `file_pho` varchar(255) DEFAULT NULL,
  `file_fho` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `file_laporan` varchar(255) DEFAULT NULL,
  `file_dokumentasi0` varchar(255) DEFAULT NULL,
  `file_dokumentasi50` varchar(255) DEFAULT NULL,
  `file_dokumentasi100` varchar(255) DEFAULT NULL,
  `disable` tinyint(1) DEFAULT 0,
  `setujui` tinyint(1) DEFAULT 0,
  `kunci` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar_realisasi_neo`
--

CREATE TABLE `daftar_realisasi_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `kd_opd` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `kd_sub_keg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `kd_akun` varchar(100) NOT NULL,
  `id_paket` int(11) NOT NULL,
  `kontrak_id` bigint(20) DEFAULT NULL,
  `rab_id` int(11) DEFAULT NULL,
  `ket_paket` text NOT NULL,
  `id_uraian_paket` int(11) NOT NULL,
  `ket_uraian_paket` varchar(400) NOT NULL,
  `id_dok_anggaran` int(11) NOT NULL,
  `dok` varchar(50) NOT NULL,
  `vol` decimal(36,12) NOT NULL,
  `jumlah` decimal(36,12) NOT NULL,
  `tanggal` date NOT NULL,
  `periode` tinyint(4) DEFAULT NULL,
  `progress_fisik` decimal(7,2) NOT NULL DEFAULT 0.00,
  `progress_keuangan` decimal(7,2) NOT NULL DEFAULT 0.00,
  `uraian_progress` text DEFAULT NULL,
  `nomor_bukti` varchar(100) DEFAULT NULL,
  `file` varchar(400) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `keterangan` varchar(400) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `setujui` tinyint(4) NOT NULL DEFAULT 0,
  `kunci` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `daftar_realisasi_neo`
--
DELIMITER $$
CREATE TRIGGER `trg_realisasi_validate_insert` BEFORE INSERT ON `daftar_realisasi_neo` FOR EACH ROW BEGIN
  DECLARE contract_value DECIMAL(20,2) DEFAULT NULL; DECLARE realized DECIMAL(20,2) DEFAULT 0;
  SELECT nilai_kontrak INTO contract_value FROM kontrak_neo WHERE id=NEW.kontrak_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND is_deleted=0 LIMIT 1;
  IF contract_value IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak realisasi tidak valid'; END IF;
  SELECT COALESCE(SUM(jumlah),0) INTO realized FROM daftar_realisasi_neo WHERE kontrak_id=NEW.kontrak_id AND is_deleted=0;
  IF realized+NEW.jumlah>contract_value THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Realisasi melebihi nilai kontrak'; END IF;
  IF NEW.progress_fisik<0 OR NEW.progress_fisik>100 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Progress fisik harus 0 sampai 100'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_realisasi_validate_update` BEFORE UPDATE ON `daftar_realisasi_neo` FOR EACH ROW BEGIN
  DECLARE contract_value DECIMAL(20,2) DEFAULT NULL; DECLARE realized DECIMAL(20,2) DEFAULT 0;
  SELECT nilai_kontrak INTO contract_value FROM kontrak_neo WHERE id=NEW.kontrak_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND is_deleted=0 LIMIT 1;
  SELECT COALESCE(SUM(jumlah),0) INTO realized FROM daftar_realisasi_neo WHERE kontrak_id=NEW.kontrak_id AND id<>OLD.id AND is_deleted=0;
  IF contract_value IS NULL OR realized+NEW.jumlah>contract_value THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Realisasi melebihi nilai kontrak atau kontrak tidak valid'; END IF;
  IF NEW.progress_fisik<0 OR NEW.progress_fisik>100 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Progress fisik harus 0 sampai 100'; END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar_uraian_paket`
--

CREATE TABLE `daftar_uraian_paket` (
  `id` int(11) NOT NULL,
  `id_paket` int(11) NOT NULL,
  `id_dok_anggaran` int(11) NOT NULL,
  `dok` varchar(25) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_sub_keg` varchar(50) NOT NULL,
  `kd_akun` varchar(50) NOT NULL,
  `kel_rek` varchar(50) NOT NULL,
  `jumlah_pagu` decimal(36,12) NOT NULL,
  `jumlah_kontrak` decimal(36,12) NOT NULL,
  `vol_kontrak` decimal(36,12) NOT NULL,
  `sat_kontrak` varchar(50) NOT NULL,
  `realisasi_vol` decimal(36,12) DEFAULT NULL,
  `realisasi_jumlah` decimal(36,12) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL,
  `username_insert` varchar(255) NOT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `db_asn_pemda_neo`
--

CREATE TABLE `db_asn_pemda_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama` varchar(400) NOT NULL,
  `gelar_depan` varchar(50) DEFAULT NULL,
  `gelar` varchar(60) DEFAULT NULL,
  `nip` varchar(18) NOT NULL,
  `t4_lahir` varchar(100) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `file_akta_lahir` varchar(255) DEFAULT NULL,
  `golongan` varchar(1) NOT NULL,
  `ruang` varchar(1) NOT NULL,
  `agama` varchar(100) DEFAULT NULL,
  `kelamin` varchar(100) DEFAULT NULL,
  `jenis_kepeg` varchar(100) DEFAULT NULL,
  `status_kepeg` varchar(100) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `id_jabatan` int(11) DEFAULT NULL,
  `no_ktp` varchar(100) DEFAULT NULL,
  `file_ktp` varchar(255) DEFAULT NULL,
  `no_kk` varchar(255) DEFAULT NULL,
  `tgl_kk` date DEFAULT NULL,
  `file_kk` varchar(255) DEFAULT NULL,
  `npwp` varchar(21) DEFAULT NULL,
  `file_npwp` varchar(255) DEFAULT NULL,
  `alamat` varchar(300) DEFAULT NULL,
  `kontak_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `no_buku_nikah` varchar(225) DEFAULT NULL,
  `tgl_nikah` date DEFAULT NULL,
  `file_buku_nikah` varchar(255) DEFAULT NULL,
  `nama_anak` varchar(400) DEFAULT NULL,
  `nik_anak` varchar(400) DEFAULT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `nama_pasangan` varchar(100) DEFAULT NULL,
  `no_karpeg` varchar(100) DEFAULT NULL,
  `tgl_karpeg` date DEFAULT NULL,
  `file_karpeg` varchar(255) DEFAULT NULL,
  `no_taspen` varchar(100) DEFAULT NULL,
  `tgl_taspen` date DEFAULT NULL,
  `no_karsi_karsu` varchar(100) DEFAULT NULL,
  `tgl_karsi_karsu` date DEFAULT NULL,
  `file_karsi_karsu` varchar(255) DEFAULT NULL,
  `nmr_sk_terakhir` varchar(150) DEFAULT NULL,
  `tgl_tmt_akhir` date DEFAULT NULL,
  `pj_sk_terakhir` varchar(150) DEFAULT NULL,
  `nmr_sk_cpns` varchar(150) DEFAULT NULL,
  `tgl_sk_cpns` date DEFAULT NULL,
  `pj_sk_cpns` varchar(150) DEFAULT NULL,
  `nmr_sk_pns` varchar(150) DEFAULT NULL,
  `tgl_sk_pns` date DEFAULT NULL,
  `pj_sk_pns` varchar(150) DEFAULT NULL,
  `sk_pangkat_terakhir` varchar(100) DEFAULT NULL,
  `tgl_sk_terakhir` date DEFAULT NULL,
  `pend_sekolah_sd` varchar(225) DEFAULT NULL,
  `pend_ijasah_sd` varchar(225) DEFAULT NULL,
  `pend_tgl_tmt_sd` date DEFAULT NULL,
  `pend_t4_sd` varchar(225) DEFAULT NULL,
  `pend_file_sd` varchar(225) DEFAULT NULL,
  `pend_sekolah_smp` varchar(225) DEFAULT NULL,
  `pend_ijasah_smp` varchar(225) DEFAULT NULL,
  `pend_tgl_tmt_smp` date DEFAULT NULL,
  `pend_t4_smp` varchar(225) DEFAULT NULL,
  `pend_file_smp` varchar(225) DEFAULT NULL,
  `pend_sekolah_smu` varchar(255) DEFAULT NULL,
  `pend_ijasah_smu` varchar(255) DEFAULT NULL,
  `pend_tgl_tmt_smu` date DEFAULT NULL,
  `pend_t4_smu` varchar(255) DEFAULT NULL,
  `pend_file_smu` varchar(80) DEFAULT NULL,
  `pend_sekolah_s1` varchar(255) DEFAULT NULL,
  `pend_ijasah_s1` varchar(255) DEFAULT NULL,
  `pend_tgl_tmt_s1` date DEFAULT NULL,
  `pend_t4_s1` varchar(255) DEFAULT NULL,
  `pend_file_s1` varchar(80) DEFAULT NULL,
  `pend_sekolah_s2` varchar(255) DEFAULT NULL,
  `pend_ijasah_s2` varchar(255) DEFAULT NULL,
  `pend_tgl_tmt_s2` date DEFAULT NULL,
  `pend_t4_s2` varchar(255) DEFAULT NULL,
  `pend_file_s2` varchar(80) DEFAULT NULL,
  `pend_sekolah_s3` varchar(255) DEFAULT NULL,
  `pend_ijasah_s3` varchar(255) DEFAULT NULL,
  `pend_tgl_tmt_s3` date DEFAULT NULL,
  `pend_t4_s3` varchar(255) DEFAULT NULL,
  `pend_file_s3` varchar(80) DEFAULT NULL,
  `pend_sekolah_akhir` varchar(255) DEFAULT NULL,
  `pend_ijasah_akhir` varchar(255) DEFAULT NULL,
  `pend_tgl_tmt_akhir` date DEFAULT NULL,
  `pend_t4_akhir` varchar(255) DEFAULT NULL,
  `pend_file_akhir` varchar(80) DEFAULT NULL,
  `file_photo` varchar(255) DEFAULT NULL,
  `gapok` decimal(20,2) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT NULL,
  `no_urut` int(11) DEFAULT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `no_rekening` varchar(150) DEFAULT NULL,
  `nama_bank` varchar(150) DEFAULT NULL,
  `urutan` int(11) DEFAULT NULL,
  `kelompok` int(11) DEFAULT NULL,
  `suka` int(11) DEFAULT NULL,
  `follow` int(11) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokumen_pegawai_neo`
--

CREATE TABLE `dokumen_pegawai_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `jenis_dokumen` enum('KTP','KK','AKTA_LAHIR','IJAZAH','SK_CPNS','SK_PNS','SK_PANGKAT','SK_JABATAN','KARPEG','TASPEN','NPWP','BUKU_NIKAH','CUTI','SERTIFIKAT','LAINNYA') NOT NULL,
  `nomor_dokumen` varchar(150) DEFAULT NULL,
  `tanggal_dokumen` date DEFAULT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `judul` varchar(300) NOT NULL,
  `nama_file_asli` varchar(255) NOT NULL,
  `path_file` varchar(500) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `ukuran` bigint(20) DEFAULT 0,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dpa_neo`
--

CREATE TABLE `dpa_neo` (
  `id` bigint(20) NOT NULL,
  `source_table` varchar(30) DEFAULT NULL,
  `source_id` bigint(20) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `dpa_neo`
--
DELIMITER $$
CREATE TRIGGER `trg_dpa_contract_guard_delete` BEFORE DELETE ON `dpa_neo` FOR EACH ROW BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_item_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_dpa_contract_guard_update` BEFORE UPDATE ON `dpa_neo` FOR EACH ROW BEGIN
  DECLARE contracted DECIMAL(20,2) DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO contracted FROM kontrak_item_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contracted>0 AND (NEW.jumlah<contracted OR NEW.is_deleted=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Anggaran DPA tidak boleh lebih kecil dari nilai kontrak dan uraian tidak dapat dihapus'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_dpa_protect_contract_delete` BEFORE DELETE ON `dpa_neo` FOR EACH ROW BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_dpa_protect_contract_update` BEFORE UPDATE ON `dpa_neo` FOR EACH ROW BEGIN
  DECLARE contract_total DECIMAL(20,2) DEFAULT 0; DECLARE contract_count INT DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0),COUNT(*) INTO contract_total,contract_count FROM kontrak_neo WHERE tahap='dpa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contract_count>0 AND NEW.is_deleted=1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA sudah berkontrak dan tidak dapat dihapus'; END IF;
  IF contract_count>0 AND NEW.jumlah<contract_total THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai DPA tidak boleh lebih kecil dari nilai kontrak'; END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dppa_neo`
--

CREATE TABLE `dppa_neo` (
  `id` bigint(20) NOT NULL,
  `source_table` varchar(30) DEFAULT NULL,
  `source_id` bigint(20) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga_awal` varchar(20) DEFAULT NULL,
  `id_standar_harga_awal` bigint(20) DEFAULT NULL,
  `komponen_awal` text DEFAULT NULL,
  `spesifikasi_awal` text DEFAULT NULL,
  `tkdn_awal` decimal(10,2) DEFAULT NULL,
  `pajak_awal` decimal(10,2) DEFAULT NULL,
  `harga_satuan_awal` decimal(20,4) DEFAULT NULL,
  `volume_awal` decimal(20,4) DEFAULT NULL,
  `jumlah_awal` decimal(20,4) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `status_perubahan` enum('awal','ubah','tambah','hapus') DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `dppa_neo`
--
DELIMITER $$
CREATE TRIGGER `trg_dppa_contract_guard_delete` BEFORE DELETE ON `dppa_neo` FOR EACH ROW BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_item_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_dppa_contract_guard_update` BEFORE UPDATE ON `dppa_neo` FOR EACH ROW BEGIN
  DECLARE contracted DECIMAL(20,2) DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO contracted FROM kontrak_item_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contracted>0 AND (NEW.jumlah<contracted OR NEW.is_deleted=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Anggaran DPPA tidak boleh lebih kecil dari nilai kontrak dan uraian tidak dapat dihapus'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_dppa_protect_contract_delete` BEFORE DELETE ON `dppa_neo` FOR EACH ROW BEGIN
  IF EXISTS(SELECT 1 FROM kontrak_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPPA sudah berkontrak dan tidak dapat dihapus'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_dppa_protect_contract_update` BEFORE UPDATE ON `dppa_neo` FOR EACH ROW BEGIN
  DECLARE contract_total DECIMAL(20,2) DEFAULT 0; DECLARE contract_count INT DEFAULT 0;
  SELECT COALESCE(SUM(nilai_kontrak),0),COUNT(*) INTO contract_total,contract_count FROM kontrak_neo WHERE tahap='dppa' AND anggaran_id=OLD.id AND is_deleted=0;
  IF contract_count>0 AND NEW.is_deleted=1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPPA sudah berkontrak dan tidak dapat dihapus'; END IF;
  IF contract_count>0 AND NEW.jumlah<contract_total THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai DPPA tidak boleh lebih kecil dari nilai kontrak'; END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `group_rekap_akun`
--

CREATE TABLE `group_rekap_akun` (
  `id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `total_anggaran` decimal(20,2) DEFAULT NULL,
  `tahap` enum('renja','rka','dpa','renja_p','rka_p','dppa') DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `group_sub_kegiatan`
--

CREATE TABLE `group_sub_kegiatan` (
  `id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `nama_sub_keg` text DEFAULT NULL,
  `output` text DEFAULT NULL,
  `satuan_output` varchar(100) DEFAULT NULL,
  `batas_anggaran` decimal(20,2) NOT NULL DEFAULT 0.00,
  `tahap` enum('renja','renja_p','rka','rka_p','dpa','dppa') DEFAULT NULL,
  `total_anggaran` decimal(20,2) DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `setujui` int(11) NOT NULL,
  `tanggal_setujui` datetime NOT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `halaman_berita`
--

CREATE TABLE `halaman_berita` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kd_wilayah` varchar(50) DEFAULT NULL,
  `kd_opd` varchar(50) DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `konten` longtext NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` timestamp NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `import_logs`
--

CREATE TABLE `import_logs` (
  `id` bigint(20) NOT NULL,
  `tabel` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `total_rows` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `indikator_program_renstra_neo`
--

CREATE TABLE `indikator_program_renstra_neo` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `nama_indikator` text NOT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `baseline` decimal(18,2) DEFAULT 0.00,
  `target_t1` decimal(18,2) DEFAULT 0.00,
  `target_t2` decimal(18,2) DEFAULT 0.00,
  `target_t3` decimal(18,2) DEFAULT 0.00,
  `target_t4` decimal(18,2) DEFAULT 0.00,
  `target_t5` decimal(18,2) DEFAULT 0.00,
  `target_akhir` decimal(18,2) DEFAULT 0.00,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `indikator_sasaran_renstra_neo`
--

CREATE TABLE `indikator_sasaran_renstra_neo` (
  `id` int(11) NOT NULL,
  `sasaran_id` int(11) NOT NULL,
  `nama_indikator` text NOT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `baseline` decimal(18,2) DEFAULT 0.00,
  `target_t1` decimal(18,2) DEFAULT 0.00,
  `target_t2` decimal(18,2) DEFAULT 0.00,
  `target_t3` decimal(18,2) DEFAULT 0.00,
  `target_t4` decimal(18,2) DEFAULT 0.00,
  `target_t5` decimal(18,2) DEFAULT 0.00,
  `target_akhir` decimal(18,2) DEFAULT 0.00,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kd_wilayah_neo`
--

CREATE TABLE `kd_wilayah_neo` (
  `id` int(11) NOT NULL,
  `kode` varchar(60) NOT NULL,
  `uraian` int(11) NOT NULL,
  `prioritas_pembangunan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prioritas_pembangunan`)),
  `disable` int(11) DEFAULT NULL,
  `peraturan` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kegiatan_renstra_neo`
--

CREATE TABLE `kegiatan_renstra_neo` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `kode_kegiatan` varchar(20) DEFAULT NULL,
  `uraian` varchar(510) DEFAULT NULL,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kontrak_dokumen_neo`
--

CREATE TABLE `kontrak_dokumen_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kontrak_id` bigint(20) NOT NULL,
  `jenis_dokumen` enum('KONTRAK','SPK','SPMK','SSKK','SSUK','RAB','JADWAL','KURVA_S','GAMBAR','BAST','PHO','FHO','ADENDUM','JAMINAN','LAPORAN','LAINNYA') NOT NULL,
  `nomor_dokumen` varchar(150) DEFAULT NULL,
  `tanggal_dokumen` date DEFAULT NULL,
  `judul` varchar(300) NOT NULL,
  `nama_file_asli` varchar(255) NOT NULL,
  `path_file` varchar(500) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `ukuran` bigint(20) NOT NULL DEFAULT 0,
  `versi` smallint(6) NOT NULL DEFAULT 1,
  `keterangan` text DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kontrak_item_neo`
--

CREATE TABLE `kontrak_item_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kontrak_id` bigint(20) NOT NULL,
  `tahap` enum('dpa','dppa') NOT NULL,
  `anggaran_id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(30) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
  `tahun` int(11) NOT NULL,
  `kd_sub_keg` varchar(80) NOT NULL,
  `kd_akun` varchar(80) DEFAULT NULL,
  `uraian` varchar(500) NOT NULL,
  `pagu` decimal(20,2) NOT NULL DEFAULT 0.00,
  `nilai_kontrak` decimal(20,2) NOT NULL DEFAULT 0.00,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Trigger `kontrak_item_neo`
--
DELIMITER $$
CREATE TRIGGER `trg_kontrak_item_validate_insert` BEFORE INSERT ON `kontrak_item_neo` FOR EACH ROW BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE used_value DECIMAL(20,2) DEFAULT 0;
  IF NEW.tahap='dpa' THEN SELECT jumlah INTO budget FROM dpa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSE SELECT jumlah INTO budget FROM dppa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1; END IF;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO used_value FROM kontrak_item_neo WHERE tahap=NEW.tahap AND anggaran_id=NEW.anggaran_id AND is_deleted=0;
  IF budget IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Uraian DPA/DPPA tidak ditemukan atau belum disetujui'; END IF;
  IF NEW.nilai_kontrak<=0 OR used_value+NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai kontrak uraian melebihi pagu DPA/DPPA tersedia'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_kontrak_item_validate_update` BEFORE UPDATE ON `kontrak_item_neo` FOR EACH ROW BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE used_value DECIMAL(20,2) DEFAULT 0;
  IF NEW.tahap='dpa' THEN SELECT jumlah INTO budget FROM dpa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSE SELECT jumlah INTO budget FROM dppa_neo WHERE id=NEW.anggaran_id AND setujui=1 AND is_deleted=0 LIMIT 1; END IF;
  SELECT COALESCE(SUM(nilai_kontrak),0) INTO used_value FROM kontrak_item_neo WHERE tahap=NEW.tahap AND anggaran_id=NEW.anggaran_id AND id<>OLD.id AND is_deleted=0;
  IF budget IS NULL OR NEW.nilai_kontrak<=0 OR used_value+NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai kontrak uraian tidak valid atau melebihi pagu tersedia'; END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kontrak_jadwal_neo`
--

CREATE TABLE `kontrak_jadwal_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kontrak_id` bigint(20) NOT NULL,
  `rab_id` int(11) DEFAULT NULL,
  `minggu_ke` smallint(6) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `bobot_rencana` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `bobot_realisasi` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `rencana_kumulatif` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `realisasi_kumulatif` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `keterangan` varchar(400) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kontrak_neo`
--

CREATE TABLE `kontrak_neo` (
  `id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `anggaran_id` bigint(20) DEFAULT NULL,
  `paket_id` int(11) DEFAULT NULL,
  `rekanan_id` int(11) DEFAULT NULL,
  `ppk_id` bigint(20) DEFAULT NULL,
  `pptk_id` bigint(20) DEFAULT NULL,
  `nama_sub_keg` text DEFAULT NULL,
  `tahap` enum('dpa','dppa') DEFAULT NULL,
  `total_anggaran` decimal(20,2) DEFAULT NULL,
  `nilai_kontrak` decimal(20,2) DEFAULT NULL,
  `nomor_kontrak` varchar(100) DEFAULT NULL,
  `tanggal_kontrak` date DEFAULT NULL,
  `uraian_kontrak` text DEFAULT NULL,
  `nomor_spk` varchar(100) DEFAULT NULL,
  `tanggal_spk` date DEFAULT NULL,
  `nomor_spmk` varchar(100) DEFAULT NULL,
  `tanggal_spmk` date DEFAULT NULL,
  `waktu_pelaksanaan` bigint(20) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `nama_ppk` varchar(200) DEFAULT NULL,
  `nama_penyedia` varchar(200) DEFAULT NULL,
  `nama_tim_teknis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nama_tim_teknis`)),
  `tanggal_pho` date DEFAULT NULL,
  `tanggal_fho` date DEFAULT NULL,
  `tanggal_serah_terima` date DEFAULT NULL,
  `status_kontrak` varchar(50) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `disable` tinyint(4) NOT NULL DEFAULT 0,
  `kunci` tinyint(4) NOT NULL DEFAULT 0,
  `setujui` tinyint(4) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0,
  `keterangan` varchar(400) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kop_surat_neo`
--

CREATE TABLE `kop_surat_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `nama_pemerintah` varchar(250) NOT NULL,
  `nama_opd` varchar(350) NOT NULL,
  `alamat` varchar(500) DEFAULT NULL,
  `telepon` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `kode_pos` varchar(20) DEFAULT NULL,
  `logo_kiri` varchar(500) DEFAULT NULL,
  `logo_kanan` varchar(500) DEFAULT NULL,
  `gambar_kop` varchar(500) DEFAULT NULL,
  `gunakan_gambar_kop` tinyint(4) NOT NULL DEFAULT 0,
  `warna_garis` varchar(20) DEFAULT '#000000',
  `aktif` tinyint(4) NOT NULL DEFAULT 1,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_activity`
--

CREATE TABLE `log_activity` (
  `id` bigint(20) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` bigint(20) NOT NULL,
  `action` enum('insert','update','delete','restore') NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_biaya`
--

CREATE TABLE `master_biaya` (
  `id` bigint(20) NOT NULL,
  `tipe` enum('ssh','sbu','asb','hspk') DEFAULT NULL,
  `kode` varchar(100) DEFAULT NULL,
  `kode_aset` varchar(100) DEFAULT NULL,
  `kelompok_barang` varchar(255) DEFAULT NULL,
  `uraian` varchar(510) DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `satuan_id` bigint(20) DEFAULT NULL,
  `harga` decimal(18,2) DEFAULT NULL,
  `tkdn` decimal(5,2) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `tahun` int(11) DEFAULT NULL,
  `peraturan_id` bigint(20) DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(50) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_biaya_akun`
--

CREATE TABLE `master_biaya_akun` (
  `id` bigint(20) NOT NULL,
  `master_biaya_id` bigint(20) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `peraturan_id` bigint(20) DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(50) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `misi_renstra_neo`
--

CREATE TABLE `misi_renstra_neo` (
  `id` int(11) NOT NULL,
  `renstra_id` int(11) NOT NULL,
  `nama_misi` text NOT NULL,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `organisasi_neo`
--

CREATE TABLE `organisasi_neo` (
  `id` int(8) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kode` varchar(30) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `nama_kepala` varchar(255) DEFAULT NULL,
  `nip_kepala` varchar(19) DEFAULT NULL,
  `singkatan` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `peraturan_id` int(11) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `aksi` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pejabat_tahunan_neo`
--

CREATE TABLE `pejabat_tahunan_neo` (
  `id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(60) NOT NULL,
  `kd_opd` varchar(60) NOT NULL,
  `tahun` year(4) NOT NULL,
  `jenis_pejabat` enum('PA_KPA','PPK','PPTK','PPK_SKPD','BENDAHARA','PEJABAT_PENGADAAN','PEJABAT_LAINNYA') NOT NULL,
  `pegawai_id` bigint(20) NOT NULL,
  `nama_pegawai` varchar(200) DEFAULT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `nomor_sk` varchar(150) NOT NULL,
  `tanggal_sk` date DEFAULT NULL,
  `berlaku_mulai` date NOT NULL,
  `berlaku_sampai` date NOT NULL,
  `kd_sub_keg` varchar(80) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Trigger `pejabat_tahunan_neo`
--
DELIMITER $$
CREATE TRIGGER `trg_pejabat_fill_insert` BEFORE INSERT ON `pejabat_tahunan_neo` FOR EACH ROW BEGIN
  SET NEW.nama_pegawai=(SELECT nama FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  SET NEW.nip=(SELECT nip FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  IF NEW.berlaku_sampai<NEW.berlaku_mulai THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Tanggal berakhir jabatan tidak boleh sebelum tanggal mulai'; END IF;
  IF NEW.jenis_pejabat IN ('PPK','PPTK') AND (NEW.kd_sub_keg IS NULL OR NEW.kd_sub_keg='') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PPK dan PPTK wajib dihubungkan ke sub kegiatan'; END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pejabat_fill_update` BEFORE UPDATE ON `pejabat_tahunan_neo` FOR EACH ROW BEGIN
  SET NEW.nama_pegawai=(SELECT nama FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  SET NEW.nip=(SELECT nip FROM db_asn_pemda_neo WHERE id=NEW.pegawai_id LIMIT 1);
  IF NEW.berlaku_sampai<NEW.berlaku_mulai THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Tanggal berakhir jabatan tidak boleh sebelum tanggal mulai'; END IF;
  IF NEW.jenis_pejabat IN ('PPK','PPTK') AND (NEW.kd_sub_keg IS NULL OR NEW.kd_sub_keg='') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PPK dan PPTK wajib dihubungkan ke sub kegiatan'; END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan_neo`
--

CREATE TABLE `pengaturan_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `tahun_renstra` year(4) NOT NULL,
  `aturan_anggaran` varchar(255) NOT NULL,
  `aturan_organisasi` int(11) DEFAULT NULL,
  `aturan_pengadaan` varchar(255) NOT NULL,
  `aturan_akun` int(11) NOT NULL,
  `aturan_asb` int(11) NOT NULL,
  `aturan_sbu` int(11) NOT NULL,
  `aturan_ssh` int(11) NOT NULL,
  `aturan_hspk` int(11) NOT NULL,
  `aturan_sumber_dana` int(11) NOT NULL,
  `aturan_sub_kegiatan` int(11) NOT NULL,
  `rpjmd_aktif` varchar(255) DEFAULT NULL,
  `awal_renja` datetime DEFAULT NULL,
  `akhir_renja` datetime DEFAULT NULL,
  `awal_dpa` datetime DEFAULT NULL,
  `akhir_dpa` datetime DEFAULT NULL,
  `awal_renja_p` datetime DEFAULT NULL,
  `akhir_renja_p` datetime DEFAULT NULL,
  `awal_dppa` datetime DEFAULT NULL,
  `akhir_dppa` datetime DEFAULT NULL,
  `awal_renstra` datetime DEFAULT NULL,
  `akhir_renstra` datetime DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui` tinyint(1) DEFAULT 0,
  `kunci_renstra` tinyint(1) DEFAULT NULL,
  `kunci_renja` tinyint(1) DEFAULT NULL,
  `kunci_dpa` tinyint(1) DEFAULT NULL,
  `kunci_renja_p` tinyint(1) DEFAULT NULL,
  `kunci_dppa` tinyint(1) DEFAULT NULL,
  `kunci_paket` tinyint(1) DEFAULT NULL,
  `kunci_realisasi` tinyint(1) DEFAULT NULL,
  `setujui_renstra` tinyint(1) DEFAULT NULL,
  `setujui_renja` tinyint(1) DEFAULT NULL,
  `setujui_dpa` tinyint(1) DEFAULT NULL,
  `setujui_renja_p` tinyint(1) DEFAULT NULL,
  `setujui_dppa` tinyint(1) DEFAULT NULL,
  `setujui_paket` tinyint(1) DEFAULT NULL,
  `setujui_realisasi` tinyint(1) DEFAULT NULL,
  `id_opd_tampilkan` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `awal_rkpd` datetime DEFAULT NULL,
  `akhir_rkpd` datetime DEFAULT NULL,
  `awal_kua_ppas` datetime DEFAULT NULL,
  `akhir_kua_ppas` datetime DEFAULT NULL,
  `awal_rka` datetime DEFAULT NULL,
  `akhir_rka` datetime DEFAULT NULL,
  `awal_rapbd` datetime DEFAULT NULL,
  `akhir_rapbd` datetime DEFAULT NULL,
  `awal_rak` datetime DEFAULT NULL,
  `akhir_rak` datetime DEFAULT NULL,
  `awal_rkpd_perubahan` datetime DEFAULT NULL,
  `akhir_rkpd_perubahan` datetime DEFAULT NULL,
  `awal_kua_ppas_perubahan` datetime DEFAULT NULL,
  `akhir_kua_ppas_perubahan` datetime DEFAULT NULL,
  `awal_apbd_perubahan` datetime DEFAULT NULL,
  `akhir_apbd_perubahan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `peraturan_neo`
--

CREATE TABLE `peraturan_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kode` varchar(255) NOT NULL,
  `type_dok` varchar(255) NOT NULL,
  `judul` varchar(400) NOT NULL,
  `judul_singkat` varchar(400) DEFAULT NULL,
  `nomor` varchar(255) NOT NULL,
  `bentuk` varchar(255) NOT NULL,
  `bentuk_singkat` varchar(255) NOT NULL,
  `t4_penetapan` varchar(255) NOT NULL,
  `tgl_penetapan` date NOT NULL,
  `tgl_pengundangan` date NOT NULL,
  `status` varchar(255) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `aksi` varchar(50) DEFAULT NULL,
  `file` varchar(400) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `keterangan` varchar(255) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `periode_rpjmd`
--

CREATE TABLE `periode_rpjmd` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `periode_mulai` year(4) NOT NULL,
  `periode_selesai` year(4) NOT NULL,
  `status_aktif` tinyint(1) DEFAULT 1,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `program_renstra_neo`
--

CREATE TABLE `program_renstra_neo` (
  `id` int(11) NOT NULL,
  `sasaran_id` int(11) NOT NULL,
  `kode_program` varchar(20) DEFAULT NULL,
  `uraian` varchar(510) DEFAULT NULL,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rab_paket_neo`
--

CREATE TABLE `rab_paket_neo` (
  `id` int(11) NOT NULL,
  `kontrak_id` bigint(20) DEFAULT NULL,
  `kontrak_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_renja_p` int(11) NOT NULL,
  `id_dpa` int(11) NOT NULL,
  `id_dppa` int(11) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `satuan` varchar(50) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `vol_hps` decimal(36,12) DEFAULT NULL,
  `vol_penawaran` decimal(36,12) DEFAULT NULL,
  `vol_negoisasi` decimal(36,12) DEFAULT NULL,
  `harga_sat_hps` decimal(36,12) DEFAULT NULL,
  `harga_sat_penawaran` decimal(36,12) DEFAULT NULL,
  `harga_sat_negoisasi` decimal(36,12) DEFAULT NULL,
  `pajak` decimal(8,2) DEFAULT NULL,
  `jumlah_hps` decimal(36,12) DEFAULT NULL,
  `jumlah_penawaran` decimal(36,12) DEFAULT NULL,
  `jumlah_negoisasi` decimal(36,12) DEFAULT NULL,
  `bobot` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `KBBI` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_jenis_naskah`
--

CREATE TABLE `ref_jenis_naskah` (
  `id` int(11) NOT NULL,
  `kelompok_id` int(11) DEFAULT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `kode_form` varchar(50) DEFAULT NULL,
  `sub_kategori` varchar(200) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `schema_json` longtext DEFAULT NULL,
  `schema_version` int(11) DEFAULT 1,
  `allowed_roles` varchar(255) DEFAULT NULL,
  `auto_generate_nomor` tinyint(4) DEFAULT 0,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_jenis_naskah_dinas`
--

CREATE TABLE `ref_jenis_naskah_dinas` (
  `id` int(11) NOT NULL,
  `kode` varchar(20) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `orientasi` varchar(20) DEFAULT NULL,
  `template_default` text DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_kelompok_naskah`
--

CREATE TABLE `ref_kelompok_naskah` (
  `id` int(11) NOT NULL,
  `kode` varchar(5) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_klasifikasi_keamanan`
--

CREATE TABLE `ref_klasifikasi_keamanan` (
  `id` int(11) NOT NULL,
  `kode` varchar(5) DEFAULT NULL,
  `nama` varchar(50) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_template_naskah`
--

CREATE TABLE `ref_template_naskah` (
  `id` int(11) NOT NULL,
  `jenis_id` int(11) NOT NULL,
  `nama_template` varchar(150) DEFAULT NULL,
  `form_schema` longtext DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `register_naskah_dinas`
--

CREATE TABLE `register_naskah_dinas` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `klasifikasi_keamanan` varchar(255) NOT NULL,
  `jenis_naskah_dinas` varchar(255) NOT NULL,
  `sifat` varchar(255) NOT NULL,
  `sub_sifat` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `jabatan` varchar(255) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `nip` varchar(18) DEFAULT NULL,
  `pangkat` varchar(255) DEFAULT NULL,
  `asal_surat` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `file` varchar(400) DEFAULT NULL,
  `username_insert` varchar(255) NOT NULL,
  `tgl_insert` datetime NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `disable` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekanan_akta`
--

CREATE TABLE `rekanan_akta` (
  `id` int(11) NOT NULL,
  `rekanan_id` int(11) NOT NULL,
  `jenis_akta` enum('pendirian','perubahan','cabang') NOT NULL DEFAULT 'perubahan',
  `no_akta` varchar(255) NOT NULL,
  `tgl_akta` date DEFAULT NULL,
  `nama_notaris` varchar(255) DEFAULT NULL,
  `lokasi_notaris` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekanan_neo`
--

CREATE TABLE `rekanan_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_perusahaan` varchar(255) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `npwp` varchar(21) NOT NULL,
  `no_rekening` varchar(255) DEFAULT NULL,
  `bank_rekening` varchar(255) DEFAULT NULL,
  `atas_nama_rekening` varchar(255) DEFAULT NULL,
  `direktur` varchar(255) NOT NULL,
  `jabatan` varchar(150) DEFAULT NULL,
  `no_ktp` varchar(255) DEFAULT NULL,
  `alamat_dir` varchar(255) DEFAULT NULL,
  `no_akta_pendirian` varchar(255) DEFAULT NULL,
  `tgl_akta_pendirian` date DEFAULT NULL,
  `lokasi_notaris_pendirian` varchar(255) DEFAULT NULL,
  `nama_notaris_pendirian` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `no_sortir` int(11) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `username_insert` varchar(100) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `no_akta_perubahan` varchar(255) DEFAULT NULL,
  `tgl_akta_perubahan` date DEFAULT NULL,
  `nama_notaris_perubahan` varchar(255) DEFAULT NULL,
  `lokasi_notaris_perubahan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekening_kegiatan`
--

CREATE TABLE `rekening_kegiatan` (
  `id` bigint(20) NOT NULL,
  `kode` varchar(30) NOT NULL,
  `parent_kode` varchar(30) DEFAULT NULL,
  `level` enum('urusan','bidang','program','kegiatan','sub_kegiatan') NOT NULL,
  `uraian` varchar(500) NOT NULL,
  `kd_wilayah` varchar(25) DEFAULT NULL,
  `peraturan_id` int(11) DEFAULT NULL,
  `kinerja` text DEFAULT NULL,
  `indikator` text DEFAULT NULL,
  `satuan` varchar(100) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rencana_realisasi_anggaran_neo`
--

CREATE TABLE `rencana_realisasi_anggaran_neo` (
  `id` bigint(20) NOT NULL,
  `dokumen` enum('dpa','dppa') NOT NULL,
  `anggaran_id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(20) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
  `tahun` smallint(6) NOT NULL,
  `kd_sub_keg` varchar(100) NOT NULL,
  `kd_akun` varchar(100) NOT NULL,
  `jenis` enum('belanja','pendapatan') NOT NULL DEFAULT 'belanja',
  `bulan` tinyint(4) NOT NULL,
  `nilai` decimal(20,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `renja_neo`
--

CREATE TABLE `renja_neo` (
  `id` bigint(20) NOT NULL,
  `source_table` varchar(30) DEFAULT NULL,
  `source_id` bigint(20) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `renja_p_neo`
--

CREATE TABLE `renja_p_neo` (
  `id` bigint(20) NOT NULL,
  `source_table` varchar(30) DEFAULT NULL,
  `source_id` bigint(20) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga_awal` varchar(20) DEFAULT NULL,
  `id_standar_harga_awal` bigint(20) DEFAULT NULL,
  `komponen_awal` text DEFAULT NULL,
  `spesifikasi_awal` text DEFAULT NULL,
  `tkdn_awal` decimal(10,2) DEFAULT NULL,
  `pajak_awal` decimal(10,2) DEFAULT NULL,
  `harga_satuan_awal` decimal(20,4) DEFAULT NULL,
  `volume_awal` decimal(20,4) DEFAULT NULL,
  `jumlah_awal` decimal(20,4) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `status_perubahan` enum('awal','ubah','tambah','hapus') DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `renstra_neo`
--

CREATE TABLE `renstra_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `periode_id` int(11) NOT NULL,
  `visi` text NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui` tinyint(1) DEFAULT 0,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_jabatan_neo`
--

CREATE TABLE `riwayat_jabatan_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `pegawai_id` int(11) NOT NULL,
  `nomor_sk` varchar(150) DEFAULT NULL,
  `jabatan` varchar(255) NOT NULL,
  `unit_kerja` varchar(255) DEFAULT NULL,
  `tmt` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_pangkat_neo`
--

CREATE TABLE `riwayat_pangkat_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `pegawai_id` int(11) NOT NULL,
  `nomor_sk` varchar(150) DEFAULT NULL,
  `golongan` varchar(10) NOT NULL,
  `ruang` varchar(10) DEFAULT NULL,
  `tmt` date NOT NULL,
  `masa_kerja_tahun` int(11) DEFAULT 0,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rka_neo`
--

CREATE TABLE `rka_neo` (
  `id` bigint(20) NOT NULL,
  `source_table` varchar(30) DEFAULT NULL,
  `source_id` bigint(20) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rka_p_neo`
--

CREATE TABLE `rka_p_neo` (
  `id` bigint(20) NOT NULL,
  `source_table` varchar(30) DEFAULT NULL,
  `source_id` bigint(20) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kd_sub_keg` varchar(50) DEFAULT NULL,
  `kd_akun` varchar(50) DEFAULT NULL,
  `kel_rek` varchar(50) DEFAULT NULL,
  `objek_belanja` varchar(100) DEFAULT NULL,
  `uraian` text DEFAULT NULL,
  `jenis_kelompok` varchar(50) DEFAULT NULL,
  `kelompok` varchar(100) DEFAULT NULL,
  `jenis_standar_harga_awal` varchar(20) DEFAULT NULL,
  `id_standar_harga_awal` bigint(20) DEFAULT NULL,
  `komponen_awal` text DEFAULT NULL,
  `spesifikasi_awal` text DEFAULT NULL,
  `tkdn_awal` decimal(10,2) DEFAULT NULL,
  `pajak_awal` decimal(10,2) DEFAULT NULL,
  `harga_satuan_awal` decimal(20,4) DEFAULT NULL,
  `volume_awal` decimal(20,4) DEFAULT NULL,
  `jumlah_awal` decimal(20,4) DEFAULT NULL,
  `jenis_standar_harga` varchar(20) DEFAULT NULL,
  `id_standar_harga` bigint(20) DEFAULT NULL,
  `komponen` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tkdn` decimal(10,2) DEFAULT NULL,
  `pajak` decimal(10,2) DEFAULT NULL,
  `harga_satuan` decimal(20,4) DEFAULT NULL,
  `vol_1` decimal(20,4) DEFAULT NULL,
  `vol_2` decimal(20,4) DEFAULT NULL,
  `vol_3` decimal(20,4) DEFAULT NULL,
  `vol_4` decimal(20,4) DEFAULT NULL,
  `vol_5` decimal(20,4) DEFAULT NULL,
  `sat_1` varchar(20) DEFAULT NULL,
  `sat_2` varchar(20) DEFAULT NULL,
  `sat_3` varchar(20) DEFAULT NULL,
  `sat_4` varchar(20) DEFAULT NULL,
  `sat_5` varchar(20) DEFAULT NULL,
  `volume` decimal(20,4) DEFAULT NULL,
  `jumlah` decimal(20,4) DEFAULT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `status_perubahan` enum('awal','ubah','tambah','hapus') DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `disable` tinyint(4) DEFAULT 0,
  `kunci` tinyint(4) DEFAULT 0,
  `setujui` tinyint(4) DEFAULT 0,
  `is_deleted` tinyint(4) DEFAULT 0,
  `tgl_insert` datetime DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rkpd_neo`
--

CREATE TABLE `rkpd_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `renstra_sub_kegiatan_id` int(11) DEFAULT NULL,
  `kd_wilayah` varchar(60) NOT NULL,
  `kd_opd` varchar(60) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_program` varchar(50) DEFAULT NULL,
  `kd_kegiatan` varchar(50) DEFAULT NULL,
  `kd_sub_keg` varchar(50) NOT NULL,
  `indikator` varchar(510) DEFAULT NULL,
  `target` decimal(18,2) NOT NULL DEFAULT 0.00,
  `satuan_id` int(11) DEFAULT NULL,
  `pagu` decimal(20,2) NOT NULL DEFAULT 0.00,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `kelompok_sasaran` varchar(255) DEFAULT NULL,
  `status` enum('draft','final','approved') NOT NULL DEFAULT 'draft',
  `disable` tinyint(4) NOT NULL DEFAULT 0,
  `kunci` tinyint(4) NOT NULL DEFAULT 0,
  `setujui` tinyint(4) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rkpd_p_neo`
--

CREATE TABLE `rkpd_p_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `source_rkpd_id` bigint(20) UNSIGNED DEFAULT NULL,
  `renstra_sub_kegiatan_id` int(11) DEFAULT NULL,
  `kd_wilayah` varchar(60) NOT NULL,
  `kd_opd` varchar(60) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_program` varchar(50) DEFAULT NULL,
  `kd_kegiatan` varchar(50) DEFAULT NULL,
  `kd_sub_keg` varchar(50) NOT NULL,
  `indikator` varchar(510) DEFAULT NULL,
  `target_awal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `pagu_awal` decimal(20,2) NOT NULL DEFAULT 0.00,
  `target` decimal(18,2) NOT NULL DEFAULT 0.00,
  `satuan_id` int(11) DEFAULT NULL,
  `pagu` decimal(20,2) NOT NULL DEFAULT 0.00,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `kelompok_sasaran` varchar(255) DEFAULT NULL,
  `status_perubahan` enum('awal','ubah','tambah','hapus') NOT NULL DEFAULT 'awal',
  `status` enum('draft','final','approved') NOT NULL DEFAULT 'draft',
  `disable` tinyint(4) NOT NULL DEFAULT 0,
  `kunci` tinyint(4) NOT NULL DEFAULT 0,
  `setujui` tinyint(4) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sasaran_renstra_neo`
--

CREATE TABLE `sasaran_renstra_neo` (
  `id` int(11) NOT NULL,
  `tujuan_id` int(11) NOT NULL,
  `kode_sasaran` varchar(10) DEFAULT NULL,
  `nama_sasaran` text NOT NULL,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `satuan_neo`
--

CREATE TABLE `satuan_neo` (
  `id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `uraian` varchar(510) DEFAULT NULL,
  `sebutan_lain` varchar(255) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `aksi` varchar(50) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `peraturan_id` int(11) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sk_asn_neo`
--

CREATE TABLE `sk_asn_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomor` varchar(255) NOT NULL,
  `tgl_surat_dibuat` date NOT NULL,
  `tentang` varchar(400) NOT NULL,
  `pemberi_tgs` varchar(18) NOT NULL,
  `jbt_pemberi_tgs` varchar(150) DEFAULT NULL,
  `pangkat_pemberi_tgs` varchar(150) DEFAULT NULL,
  `nama_pemberi_tgs` varchar(255) NOT NULL,
  `nama_ditugaskan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `bentuk_lampiran` tinyint(1) NOT NULL,
  `menimbang` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `mengingat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `menetapkan_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `menetapkan_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `menetapkan_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `menetapkan_4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `tembusan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sk_pegawai_neo`
--

CREATE TABLE `sk_pegawai_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `pegawai_id` int(11) NOT NULL,
  `nomor_sk` varchar(150) NOT NULL,
  `tanggal_sk` date DEFAULT NULL,
  `jenis_sk` varchar(100) NOT NULL,
  `tentang` varchar(400) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sub_kegiatan_renstra_neo`
--

CREATE TABLE `sub_kegiatan_renstra_neo` (
  `id` int(11) NOT NULL,
  `kegiatan_renstra_id` int(11) NOT NULL,
  `master_sub_kegiatan_id` int(11) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `kelompok_sasaran` varchar(255) DEFAULT NULL,
  `baseline` decimal(18,2) DEFAULT 0.00,
  `target_t1` decimal(18,2) DEFAULT 0.00,
  `anggaran_t1` decimal(18,2) DEFAULT 0.00,
  `target_t2` decimal(18,2) DEFAULT 0.00,
  `anggaran_t2` decimal(18,2) DEFAULT 0.00,
  `target_t3` decimal(18,2) DEFAULT 0.00,
  `anggaran_t3` decimal(18,2) DEFAULT 0.00,
  `target_t4` decimal(18,2) DEFAULT 0.00,
  `anggaran_t4` decimal(18,2) DEFAULT 0.00,
  `target_t5` decimal(18,2) DEFAULT 0.00,
  `anggaran_t5` decimal(18,2) DEFAULT 0.00,
  `target_akhir` decimal(18,2) DEFAULT 0.00,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sub_keg_dpa_neo`
--

CREATE TABLE `sub_keg_dpa_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `kd_sub_keg` varchar(22) NOT NULL,
  `kel_rek` varchar(50) NOT NULL,
  `uraian` text NOT NULL,
  `tolak_ukur_capaian_keg` varchar(255) DEFAULT NULL,
  `target_kinerja_capaian_keg` varchar(400) DEFAULT NULL,
  `tolak_ukur_keluaran` varchar(400) DEFAULT NULL,
  `target_kinerja_keluaran` varchar(255) DEFAULT NULL,
  `tolak_ukur_hasil` varchar(255) DEFAULT NULL,
  `target_kinerja_hasil` varchar(255) DEFAULT NULL,
  `tolak_ukur_capaian_keg_p` varchar(255) DEFAULT NULL,
  `target_kinerja_capaian_keg_p` varchar(400) DEFAULT NULL,
  `tolak_ukur_keluaran_p` varchar(400) DEFAULT NULL,
  `target_kinerja_keluaran_p` varchar(255) DEFAULT NULL,
  `tolak_ukur_hasil_p` varchar(255) DEFAULT NULL,
  `target_kinerja_hasil_p` varchar(255) DEFAULT NULL,
  `sumber_dana` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `keluaran_sub_keg` varchar(255) DEFAULT NULL,
  `keluaran_sub_keg_p` varchar(255) DEFAULT NULL,
  `awal_pelaksanaan` date DEFAULT NULL,
  `akhir_pelaksanaan` date DEFAULT NULL,
  `jumlah_pagu` decimal(40,12) DEFAULT NULL,
  `jumlah_pagu_p` decimal(40,12) DEFAULT NULL,
  `jumlah_rincian` decimal(40,12) DEFAULT NULL,
  `jumlah_rincian_p` decimal(40,12) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL,
  `aksi` int(11) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `kelompok_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `keterangan_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`keterangan_json`)),
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `setujui` tinyint(1) DEFAULT 0,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui_p` tinyint(1) DEFAULT 0,
  `kunci_p` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sub_keg_renja_neo`
--

CREATE TABLE `sub_keg_renja_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `kd_sub_keg` varchar(22) NOT NULL,
  `kel_rek` varchar(50) NOT NULL,
  `uraian` text NOT NULL,
  `tolak_ukur_capaian_keg` varchar(255) DEFAULT NULL,
  `target_kinerja_capaian_keg` varchar(400) DEFAULT NULL,
  `tolak_ukur_keluaran` varchar(400) DEFAULT NULL,
  `target_kinerja_keluaran` varchar(255) DEFAULT NULL,
  `tolak_ukur_hasil` varchar(255) DEFAULT NULL,
  `target_kinerja_hasil` varchar(255) DEFAULT NULL,
  `tolak_ukur_capaian_keg_p` varchar(255) DEFAULT NULL,
  `target_kinerja_capaian_keg_p` varchar(400) DEFAULT NULL,
  `tolak_ukur_keluaran_p` varchar(400) DEFAULT NULL,
  `target_kinerja_keluaran_p` varchar(255) DEFAULT NULL,
  `tolak_ukur_hasil_p` varchar(255) DEFAULT NULL,
  `target_kinerja_hasil_p` varchar(255) DEFAULT NULL,
  `sumber_dana` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `keluaran_sub_keg` varchar(255) DEFAULT NULL,
  `keluaran_sub_keg_p` varchar(255) DEFAULT NULL,
  `awal_pelaksanaan` date DEFAULT NULL,
  `akhir_pelaksanaan` date DEFAULT NULL,
  `jumlah_pagu` decimal(40,12) DEFAULT NULL,
  `jumlah_pagu_p` decimal(40,12) DEFAULT NULL,
  `jumlah_rincian` decimal(40,12) DEFAULT NULL,
  `jumlah_rincian_p` decimal(40,12) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL,
  `aksi` int(11) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `kelompok_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
  `keterangan_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`keterangan_json`)),
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `setujui` tinyint(1) DEFAULT 0,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui_p` tinyint(1) DEFAULT 0,
  `kunci_p` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sumber_dana_neo`
--

CREATE TABLE `sumber_dana_neo` (
  `id` int(8) NOT NULL,
  `sumber_dana` int(11) NOT NULL,
  `kelompok` int(11) DEFAULT NULL,
  `jenis_akun` int(11) DEFAULT NULL,
  `objek` int(11) DEFAULT NULL,
  `rincian_objek` int(11) DEFAULT NULL,
  `sub_rincian_objek` int(11) NOT NULL,
  `kode` varchar(25) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `peraturan_id` int(11) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `aksi` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tapd_penugasan_neo`
--

CREATE TABLE `tapd_penugasan_neo` (
  `id` bigint(20) NOT NULL,
  `kd_wilayah` varchar(20) NOT NULL,
  `tahun` smallint(6) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `jabatan` varchar(150) NOT NULL,
  `urutan` smallint(6) NOT NULL DEFAULT 1,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `aktif` tinyint(4) NOT NULL DEFAULT 1,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `trx_naskah_dinas`
--

CREATE TABLE `trx_naskah_dinas` (
  `id` bigint(20) NOT NULL,
  `uuid` varchar(100) DEFAULT NULL,
  `jenis_id` bigint(20) NOT NULL,
  `nomor` varchar(100) DEFAULT NULL,
  `nomor_urut` int(11) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `klasifikasi_id` int(11) DEFAULT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `perihal` varchar(255) DEFAULT NULL,
  `status` enum('draft','final') DEFAULT 'draft',
  `file_pdf` varchar(255) DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `workflow_status` varchar(20) DEFAULT 'draft',
  `verified_by` varchar(100) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `signed_by` varchar(100) DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `final_at` datetime DEFAULT NULL,
  `document_hash` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `trx_naskah_dinas`
--

INSERT INTO `trx_naskah_dinas` (`id`, `uuid`, `jenis_id`, `nomor`, `nomor_urut`, `tahun`, `klasifikasi_id`, `tanggal_surat`, `perihal`, `status`, `file_pdf`, `kd_wilayah`, `kd_opd`, `username_insert`, `tgl_insert`, `username_update`, `tgl_update`, `keterangan`, `workflow_status`, `verified_by`, `verified_at`, `signed_by`, `signed_at`, `final_at`, `document_hash`) VALUES
(2, NULL, 5, '02 DPUPR 2026', NULL, '2026', NULL, '2026-03-24', 'PENGANGKATAN PEJABAT PENATAUSAHAAN KEUANGAN DINAS PEKERJAAN UMUM DAN PENATAAN RUANG KABUPATEN PASANGKAYU TAHUN 2026', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'inayah', '2026-03-24 04:32:12', NULL, NULL, 'oke', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, 5, '05', NULL, '2026', NULL, '2026-03-26', 'perihal', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'inayah', '2026-03-26 03:12:42', NULL, NULL, '', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'TRACE_TEST_PHASE6', 7, 'B/TRACE/DPUPR/2026', 999, '2026', 1, '2026-08-31', 'TRACE EDIT TATA NASKAH TERSIMPAN', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 16:08:56', 'TRACE_TEST', '2026-09-01 03:12:56', 'TRACE_TEST Phase 6', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'tnd_6a957e412f7dd6.76381103', 1, 'TRACE/001/1.03.0.00.0.00.01.0000/2026', 901, '2026', 1, '2026-08-31', 'Contoh Peraturan Arsip Nasional Republik Indonesia', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_1', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'tnd_6a957e412fc033.65405791', 2, 'TRACE/002/1.03.0.00.0.00.01.0000/2026', 902, '2026', 1, '2026-08-31', 'Contoh Instruksi', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_2', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'tnd_6a957e412febb6.21012988', 3, 'TRACE/003/1.03.0.00.0.00.01.0000/2026', 903, '2026', 1, '2026-08-31', 'Contoh Surat Edaran', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_3', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'tnd_6a957e41300640.03591738', 4, 'TRACE/004/1.03.0.00.0.00.01.0000/2026', 904, '2026', 1, '2026-08-31', 'Contoh Standar Operasional Prosedur Administrasi Pemerintah', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_4', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'tnd_6a957e413028f7.71574290', 5, 'TRACE/005/1.03.0.00.0.00.01.0000/2026', 905, '2026', 1, '2026-08-31', 'Contoh Naskah Dinas Penetapan', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_5', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'tnd_6a957e413045a4.23914977', 6, 'TRACE/006/1.03.0.00.0.00.01.0000/2026', 906, '2026', 1, '2026-08-31', 'Contoh Naskah Dinas Penugasan', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_6', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'tnd_6a957e41305f87.86572839', 7, 'TRACE/007/1.03.0.00.0.00.01.0000/2026', 907, '2026', 1, '2026-08-31', 'Contoh Nota Dinas', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_7', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'tnd_6a957e413077d6.43806216', 8, 'TRACE/008/1.03.0.00.0.00.01.0000/2026', 908, '2026', 1, '2026-08-31', 'Contoh Memorandum', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_8', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'tnd_6a957e413093f4.97395519', 9, 'TRACE/009/1.03.0.00.0.00.01.0000/2026', 909, '2026', 1, '2026-08-31', 'Contoh Disposisi', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_9', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'tnd_6a957e4130ad36.17728655', 10, 'TRACE/010/1.03.0.00.0.00.01.0000/2026', 910, '2026', 1, '2026-08-31', 'Contoh Surat Undangan Internal', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_10', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'tnd_6a957e4130c5f6.34163582', 11, 'TRACE/011/1.03.0.00.0.00.01.0000/2026', 911, '2026', 1, '2026-08-31', 'Contoh Naskah Dinas Korespondensi Eksternal', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_11', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'tnd_6a957e4130dcf9.67255682', 12, 'TRACE/012/1.03.0.00.0.00.01.0000/2026', 912, '2026', 1, '2026-08-31', 'Contoh Surat Perjanjian', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_12', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'tnd_6a957e4130f476.02886266', 13, 'TRACE/013/1.03.0.00.0.00.01.0000/2026', 913, '2026', 1, '2026-08-31', 'Contoh Surat Kuasa', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_13', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'tnd_6a957e413109f3.79600369', 14, 'TRACE/014/1.03.0.00.0.00.01.0000/2026', 914, '2026', 1, '2026-08-31', 'Contoh Berita Acara', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_14', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'tnd_6a957e41311e34.54658247', 15, 'TRACE/015/1.03.0.00.0.00.01.0000/2026', 915, '2026', 1, '2026-08-31', 'Contoh Surat Keterangan', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_15', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'tnd_6a957e413137a7.94736054', 16, 'TRACE/016/1.03.0.00.0.00.01.0000/2026', 916, '2026', 1, '2026-08-31', 'Contoh Surat Pengantar', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_16', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'tnd_6a957e41315d65.47268672', 17, 'TRACE/017/1.03.0.00.0.00.01.0000/2026', 917, '2026', 1, '2026-08-31', 'Contoh Pengumuman', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_17', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'tnd_6a957e41317579.81592429', 18, 'TRACE/018/1.03.0.00.0.00.01.0000/2026', 918, '2026', 1, '2026-08-31', 'Contoh Laporan', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_18', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'tnd_6a957e41318e54.24620143', 19, 'TRACE/019/1.03.0.00.0.00.01.0000/2026', 919, '2026', 1, '2026-08-31', 'Contoh Telaah Staf', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_19', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'tnd_6a957e4131a445.85029933', 20, 'TRACE/020/1.03.0.00.0.00.01.0000/2026', 920, '2026', 1, '2026-08-31', 'Contoh Notula', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_20', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'tnd_6a957e4131b959.02449551', 21, 'TRACE/021/1.03.0.00.0.00.01.0000/2026', 921, '2026', 1, '2026-08-31', 'Contoh Sambutan Tertulis', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_21', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'tnd_6a957e4131d2f2.37876075', 22, 'TRACE/022/1.03.0.00.0.00.01.0000/2026', 922, '2026', 1, '2026-08-31', 'Contoh Siaran Pers', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_22', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 'tnd_6a957e4131e914.37042815', 23, 'TRACE/023/1.03.0.00.0.00.01.0000/2026', 923, '2026', 1, '2026-08-31', 'Contoh Sertifikat', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_23', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'tnd_6a957e41320212.91937913', 24, 'TRACE/024/1.03.0.00.0.00.01.0000/2026', 924, '2026', 1, '2026-08-31', 'Contoh Surat Tanda Tamat Pelatihan', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:14:41', NULL, NULL, 'TRACE_SAMPLE_TND_24', 'draft', NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'tnd_6a9580cddf0146.60069035', 25, 'TRACE/025/1.03.0.00.0.00.01.0000/2026', 925, '2026', 1, '2026-08-31', 'Contoh Piagam Penghargaan', 'draft', NULL, '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 13:25:33', NULL, NULL, 'TRACE_SAMPLE_TND_25', 'draft', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `trx_naskah_meta`
--

CREATE TABLE `trx_naskah_meta` (
  `id` bigint(20) NOT NULL,
  `naskah_id` bigint(20) DEFAULT NULL,
  `meta_key` varchar(100) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `trx_naskah_meta`
--

INSERT INTO `trx_naskah_meta` (`id`, `naskah_id`, `meta_key`, `meta_value`, `kd_wilayah`, `kd_opd`, `username_insert`, `tgl_insert`) VALUES
(14, 2, 'nomor', '02 DPUPR 2026', NULL, NULL, NULL, NULL),
(15, 2, 'tanggal_surat', '24 Maret 2026', NULL, NULL, NULL, NULL),
(16, 2, 'file', '', NULL, NULL, NULL, NULL),
(17, 2, 'perihal', 'PENGANGKATAN PEJABAT PENATAUSAHAAN KEUANGAN DINAS PEKERJAAN UMUM DAN PENATAAN RUANG KABUPATEN PASANGKAYU TAHUN 2026', NULL, NULL, NULL, NULL),
(18, 2, 'penandatangan', '3', NULL, NULL, NULL, NULL),
(19, 2, 'jbt_pemberi_tgs', 'Kepala Dinas', NULL, NULL, NULL, NULL),
(20, 2, 'pangkat_pemberi_tgs', 'Pembina, IV/a', NULL, NULL, NULL, NULL),
(21, 2, 'asn', '111', NULL, NULL, NULL, NULL),
(22, 2, 'keterangan', 'oke', NULL, NULL, NULL, NULL),
(23, 2, 'jenis_id', '5', NULL, NULL, NULL, NULL),
(24, 2, 'struktur_json', '{\"menimbang\":[{\"type\":\"numbered\",\"text\":\"<ol><li><strong>bahwa</strong> : untuk menyediakan pengelolaan keuangan Dinas Pekerjaan Umum danPenataan Ruang Kabupaten Pasangkayu, maka dipandang perlu mengangkatPejabat Penatausahaan Keuangan pada Dinas Pekerjaan Umum dan PenataanRuang Kabupaten Pasangkayu;</li><li><li>bahwa yang tersebut namanya dalam lampiran keputusan ini dipandang cakap&nbsp;<span style=\\\"font-size: 1em;\\\">dan mampu melaksanakan tugas;</span></li><li><li>bahwa sebagaimana dimaksud pada point a perlu ditetapkan dalam keputusan</li><li>Kepala Dinas Pekerjaan Umum dan Penataan Ruang.</li></li></li></ol>\",\"align\":\"justify\"}],\"mengingat\":[{\"type\":\"numbered\",\"text\":\"<ol><li>Undang-Undang : Nomor 28 Tahun 1999 tentang penyelenggaraan Negara yang&nbsp;bersih dan bebas dari korupsi dan nepotisme (Lembaran Negara Republik&nbsp;Indonesia Tahun 1999 Nomor 75, Tambahan Lembaran Negara Nomor 3851);</li><li><span style=\\\"font-size: 1em;\\\">Undang-Undang Nomor 7 Tahun 2003 tentang Pembentukan Kabupaten Luwu&nbsp;</span><span style=\\\"font-size: 1em;\\\">Timur dan Kabupaten Mamuju Utara di Provinsi Sulawesi Selatan (Lembaran&nbsp;</span><span style=\\\"font-size: 1em;\\\">Negara. Republik Indonesia Tahun 2003 Nomor 27, Tambahan Lembaran&nbsp;</span><span style=\\\"font-size: 1em;\\\">Negara Nomor 4270);</span></li></ol>\",\"align\":\"justify\"}],\"menetapkan\":[{\"type\":\"paragraph\",\"text\":\"KEPUTUSAN : KEPALA DINAS PEKERJAAN UMUM DAN PENATAAN RUANG&nbsp;TENTANG PENGANGKATAN PEJABAT PENATAUSAHAAN KEUANGAN DINAS&nbsp;PEKERJAAN UMUM DAN PENATAAN RUANG KABUPATEN PASANGKAYU&nbsp;TAHUN 2026\",\"align\":\"justify\"}],\"menetapkan_1\":[{\"type\":\"paragraph\",\"text\":\"PPK SKPD mempunyai tugas dan wewenang:\",\"align\":\"justify\"},{\"type\":\"numbered\",\"text\":\"<ol><li><strong>melakukan</strong> : verifikasi SPP-UP, SPP-GU, SPP-TU, dan SPP-LS beserta bukti&nbsp;kelengkapannya yang diajukan oleh Bendahara Pengeluaran;</li><li>menyiapkan SPM;melakukan verifikasi laporan pertanggungjawaban Bendahara Penerimaan dan&nbsp;Bendahara Pengeluaran;</li><li>melaksanakan fungsi akuntansi pada SKPD; dan</li><li>menyusun laporan keuangan SKPD.</li></ol>\",\"align\":\"justify\"}],\"menetapkan_2\":[{\"type\":\"paragraph\",\"text\":\"Selain melaksanakan tugas dan wewenang pada angka 5, PPK SKPD&nbsp;melaksanakan tugas dan wewenang lainnya yaitu:\",\"align\":\"justify\"},{\"type\":\"numbered\",\"text\":\"<ol><li><strong>melakukan</strong> : verifikasi SPP-UP, SPP-GU, SPP-TU, dan SPP-LS beserta bukti kelengkapannya yang diajukan oleh Bendahara lainnya;</li><li>melakukan verifikasi surat permintaan pembayaran atas pengembalian kelebihanpendapatan daerah dari bendahara penerimaan; dan</li><li>menerbitkan surat pernyataan verifikasi kelengkapan dan keabsahan SPP-UP,SPP-GU, SPP-TU dan SPP-LS beserta bukti kelengkapannya sebagai dasar penyiapan SPM.</li></ol>\",\"align\":\"justify\"}],\"menetapkan_3\":[{\"type\":\"paragraph\",\"text\":\"<strong>Segala</strong> : biaya yang timbul akibat keputusan ini dibebankan kepada APBD Dinas&nbsp;Pekerjaan Umum dan Penataan Ruang Kabupaten Pasangkayu Tahun Anggaran2026.\",\"align\":\"justify\"}],\"menetapkan_4\":[{\"type\":\"paragraph\",\"text\":\"Keputusan : ini mulai berlaku pada tanggal ditetapkan dan apabila terdapat&nbsp;kekeliruan didalamnya akan diperbaharui sebagaimana mestinya.\",\"align\":\"justify\"}],\"nama_ditugaskan\":[{\"nama\":\"Sri Irda Ayu, SP.,M.SI\",\"pangkat\":\"\",\"nip\":\"198306252005022003\",\"jabatan\":\"Kepala Bidang Binamarga\",\"jabatan_sk\":\"\",\"_id\":\"111\"}],\"tembusan\":[{\"type\":\"numbered\",\"text\":\"<ol><li><strong>Bupati</strong> : Pasangkayu, di Pasangkayu;</li><li>Sekretaris Daerah Kabupaten Pasangkayu di Pasangkayu;</li><li>Inspektur Inspektorat Kabupaten Pasangkayu di Pasangkayu;</li><li>Kepala Badan PKAD Kabupaten Pasangkayu di Pasangkayu;</li></ol>\",\"align\":\"justify\"}]}', NULL, NULL, NULL, NULL),
(36, 4, 'nomor', '05', NULL, NULL, NULL, NULL),
(37, 4, 'tanggal_surat', '26 Maret 2026', NULL, NULL, NULL, NULL),
(38, 4, 'file', '', NULL, NULL, NULL, NULL),
(39, 4, 'perihal', 'perihal', NULL, NULL, NULL, NULL),
(40, 4, 'penandatangan', '3', NULL, NULL, NULL, NULL),
(41, 4, 'jbt_pemberi_tgs', 'Kepala Dinas', NULL, NULL, NULL, NULL),
(42, 4, 'pangkat_pemberi_tgs', 'Pembina, IV/a', NULL, NULL, NULL, NULL),
(43, 4, 'asn', '93', NULL, NULL, NULL, NULL),
(44, 4, 'keterangan', '', NULL, NULL, NULL, NULL),
(45, 4, 'jenis_id', '5', NULL, NULL, NULL, NULL),
(46, 4, 'struktur_json', '{\"menimbang\":[{\"type\":\"paragraph\",\"text\":\"oke\",\"align\":\"justify\"}],\"mengingat\":[{\"type\":\"paragraph\",\"text\":\"oke\",\"align\":\"justify\"}],\"menetapkan\":[],\"menetapkan_1\":[],\"menetapkan_2\":[],\"menetapkan_3\":[],\"menetapkan_4\":[],\"nama_ditugaskan\":[{\"nama\":\"Gusti Ayu Nastuti\",\"pangkat\":\"Juru Muda, I/a\",\"nip\":\"819950307868545000\",\"jabatan\":\"\",\"jabatan_sk\":\"\",\"_id\":\"819950307868545000\"},{\"nama\":\"Rosmayani\",\"pangkat\":\"Juru Muda, I/a\",\"nip\":\"352782285393646000\",\"jabatan\":\"\",\"jabatan_sk\":\"\",\"_id\":\"352782285393646000\"}],\"tembusan\":[]}', NULL, NULL, NULL, NULL),
(47, 5, 'arah', 'keluar', '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 16:08:56'),
(48, 5, 'media', 'elektronik', '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 16:08:56'),
(49, 5, 'klasifikasi_keamanan', 'B', '76.01', '1.03.0.00.0.00.01.0000', 'TRACE_TEST', '2026-08-31 16:08:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `trx_naskah_status_history`
--

CREATE TABLE `trx_naskah_status_history` (
  `id` bigint(20) NOT NULL,
  `naskah_id` bigint(20) NOT NULL,
  `status_dari` varchar(20) DEFAULT NULL,
  `status_ke` varchar(20) NOT NULL,
  `catatan` varchar(400) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `trx_naskah_struktur`
--

CREATE TABLE `trx_naskah_struktur` (
  `id` bigint(20) NOT NULL,
  `naskah_id` bigint(20) NOT NULL,
  `struktur_json` longtext NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` int(11) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `trx_nomor_counter`
--

CREATE TABLE `trx_nomor_counter` (
  `id` int(11) NOT NULL,
  `klasifikasi_id` int(11) DEFAULT NULL,
  `tahun` int(11) DEFAULT NULL,
  `last_number` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tujuan_renstra_neo`
--

CREATE TABLE `tujuan_renstra_neo` (
  `id` int(11) NOT NULL,
  `misi_id` int(11) NOT NULL,
  `kode_tujuan` varchar(10) DEFAULT NULL,
  `nama_tujuan` text NOT NULL,
  `disable` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_sesendok_biila`
--

CREATE TABLE `user_sesendok_biila` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(18) DEFAULT NULL,
  `password` varchar(225) NOT NULL,
  `message_public_key` longtext DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `kd_opd` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_org` varchar(400) NOT NULL,
  `kd_wilayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_user` varchar(20) NOT NULL,
  `photo` varchar(255) NOT NULL DEFAULT 'default.jpeg',
  `signature_image` varchar(255) DEFAULT NULL,
  `signature_verified` tinyint(4) DEFAULT 0,
  `tgl_daftar` datetime NOT NULL,
  `tgl_login` datetime DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `kontak_person` varchar(255) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `font_size` decimal(5,2) DEFAULT NULL,
  `theme` varchar(50) DEFAULT 'auto',
  `warna_tbl` varchar(150) DEFAULT NULL,
  `scrolling_table` varchar(50) DEFAULT NULL,
  `disable_login` tinyint(1) NOT NULL DEFAULT 1,
  `disable_anggaran` tinyint(1) NOT NULL DEFAULT 1,
  `disable_kontrak` tinyint(1) NOT NULL DEFAULT 1,
  `disable_realisasi` tinyint(1) NOT NULL DEFAULT 1,
  `disable_chat` tinyint(1) NOT NULL DEFAULT 1,
  `ket` varchar(250) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `user_sesendok_biila`
--

INSERT INTO `user_sesendok_biila` (`id`, `username`, `email`, `nama`, `nip`, `password`, `message_public_key`, `remember_token`, `kd_opd`, `nama_org`, `kd_wilayah`, `type_user`, `photo`, `signature_image`, `signature_verified`, `tgl_daftar`, `tgl_login`, `tahun`, `kontak_person`, `alamat`, `font_size`, `theme`, `warna_tbl`, `scrolling_table`, `disable_login`, `disable_anggaran`, `disable_kontrak`, `disable_realisasi`, `disable_chat`, `ket`, `disable`) VALUES
(1, 'alwi_mansyur', 'alwi@gmail.com', 'Alwi Mansyur', '1980', '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, NULL, '1.03.0.00.0.00.01.0000', 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG', '76.01', 'admin_wilayah', 'images/avatar/default.jpeg', NULL, 0, '2018-06-04 21:57:05', '2024-10-23 15:03:04', '2024', 'pasangkayu ji', NULL, 90.00, 'auto', 'non', 'short', 0, 0, 0, 0, 1, 'apa yang dapat saya berikan', 0),
(2, 'nabiila', 'nabiila@gmail.com', 'Najwan Nabiila', '123456789012345678', '$2y$12$1qb72gQsUL.UlMLmkOZ8KOtPjhZhxDIf.AiY7kaD7zqs90GaAZJdy', '{\"alg\":\"RSA-OAEP-256\",\"e\":\"AQAB\",\"ext\":true,\"key_ops\":[\"encrypt\"],\"kty\":\"RSA\",\"n\":\"pSCtXM-BR4vh-x_dkCstygKRHbU7ilStf5dAY0wbYk8Wg30T53fdv6fuZrdhE2laHUZVLUaDa1skWTS5ZQvaTrpLpyxt9iYc-4U9I8qJCdzkGSNPaeDlVJ90lex79fXtsCYM2eXCy6VfSF6qgyJ-cS1xC6YhKDglj4xLpshK5URVU6nmsnocHcYGDXrB26xPk9JhhuHubAOPoutCtsNjBzTH6g1tX8QY-VBR0OL2IR-eAXjfdmFjZMOizzhbQgoRIRp1z02ISiQVL0Gec0O2WVhWCUm_sEJE8rrwLI0AA91hOVD5_eRYrM-jfLd1vNvE7pJhTVGYO2Dn2Qz7_ezJ323O768dPmbklJDETv26ZFQbRG57orEmbCgNW_hAcW5ehbOEYg-qfkWUQE6MOPfuGV11Q4x_Ht05ATR-WSnuKCXstZ-a6t0jQWmLcv5pGE35LYkB3zzoBuOiuQPVzVAsLmxfPIs9oqjwHAA00jFi664f878cz1Aq8N-a8YqGIa0L\"}', NULL, '1.03.0.00.0.00.01.0000', 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG', '76.01', 'admin_opd', 'img/avatar/username(nabiila)_dok(photo)_wilayah(76.01)_2305070e99916190687b3774c0d56f134b954d74_2.jpg', NULL, 0, '2018-06-09 15:54:29', '2026-02-24 21:15:01', '2026', '08128888', NULL, 80.00, 'auto', 'non', 'short', 0, 0, 0, 0, 1, 'Apa yang dapat saya berikan untuk Pasangkayu', 0),
(3, 'inayah', 'inayah@gmail.com', 'Inayah Nadhilah', NULL, '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, NULL, '1.03.0.00.0.00.01.0000', 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG', '76.01', 'super_admin', 'images/avatar/default.jpeg', NULL, 0, '2018-06-22 22:04:17', '2020-03-08 02:30:41', '2026', '', NULL, 80.00, 'auto', NULL, 'short', 0, 0, 0, 0, 1, 'dimana mana hatiku senang oke', 0),
(4, 'Arlinda', 'arlinda@gmail.com', 'Arlinda Achmad', NULL, '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, NULL, '', 'Prof', '', 'admin_opd', 'images/avatar/default.jpeg', NULL, 0, '2018-07-10 14:27:06', '2018-10-21 12:23:09', '2024', '', NULL, 80.00, 'auto', NULL, 'short', 0, 0, 0, 0, 1, 'Apa yang dapat saya berikan untuk Pasangkayu.', 0),
(5, 'administrator', 'alwi.mansyur@gmail.com', 'administrator', NULL, '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, NULL, '', 'administrator AHSP', '', 'user', 'images/avatar/c14719a7f71e46badf2cf93ae373ae9797281782_9.png', NULL, 0, '2023-02-09 23:41:34', '2023-02-23 00:05:26', '2024', '08128886665', NULL, 80.00, 'auto', 'non', 'short', 0, 0, 0, 0, 1, 'Apa yang dapat saya berikan untuk mu', 0),
(8, 'trace_user_b6d132', 'trace_b6d132@example.test', 'TRACE USER OPD', '9900b6d132', '$2y$12$Yvbw303kraYklB/x5V0G7e5KMmuk97uIftsWSERR6Jnf2S3CLYa46', NULL, NULL, '1.03.0.00.0.00.01.0000', 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG', '76.01', 'pptk', 'default.jpeg', NULL, 0, '2026-08-31 18:08:47', NULL, '2026', '0812000000', NULL, NULL, 'auto', NULL, NULL, 0, 1, 1, 1, 1, NULL, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_subkegiatan_neo`
--

CREATE TABLE `user_subkegiatan_neo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `kd_sub_keg` varchar(80) NOT NULL,
  `peran` enum('KEPALA_OPD','PA_KPA','PPK','PPTK','PPK_SKPD','BENDAHARA','PEJABAT_PENGADAAN','STAF','VIEWER') NOT NULL,
  `dapat_lihat` tinyint(4) NOT NULL DEFAULT 1,
  `dapat_input` tinyint(4) NOT NULL DEFAULT 0,
  `dapat_setujui` tinyint(4) NOT NULL DEFAULT 0,
  `dapat_hapus` tinyint(4) NOT NULL DEFAULT 0,
  `berlaku_mulai` date NOT NULL,
  `berlaku_sampai` date NOT NULL,
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `wallchat`
--

CREATE TABLE `wallchat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `type` enum('status','comment','private') NOT NULL DEFAULT 'status',
  `content` text NOT NULL,
  `content_ciphertext` longtext DEFAULT NULL,
  `content_nonce` varchar(64) DEFAULT NULL,
  `e2e_payload` longtext DEFAULT NULL,
  `encryption_version` varchar(20) DEFAULT NULL,
  `is_ephemeral` tinyint(4) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `deleted_by_sender` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_by_receiver` tinyint(4) NOT NULL DEFAULT 0,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_mime` varchar(120) DEFAULT NULL,
  `attachment_size` bigint(20) NOT NULL DEFAULT 0,
  `theme` varchar(30) NOT NULL DEFAULT 'default',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `wilayah_neo`
--

CREATE TABLE `wilayah_neo` (
  `id` int(11) NOT NULL,
  `kode` varchar(255) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `status` varchar(255) NOT NULL,
  `jml_kec` int(11) DEFAULT NULL,
  `jml_kel` int(11) DEFAULT NULL,
  `jml_desa` int(11) DEFAULT NULL,
  `luas` decimal(20,12) DEFAULT NULL,
  `penduduk` int(11) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `peta` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `absensi_pegawai_neo`
--
ALTER TABLE `absensi_pegawai_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_absen_pegawai_tanggal` (`pegawai_id`,`tanggal`),
  ADD KEY `idx_absen_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`tanggal`);

--
-- Indeks untuk tabel `akun_neo`
--
ALTER TABLE `akun_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `analisa_rab`
--
ALTER TABLE `analisa_rab`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `anggaran_copy_log`
--
ALTER TABLE `anggaran_copy_log`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `anggaran_perubahan_template`
--
ALTER TABLE `anggaran_perubahan_template`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `anggaran_program_renstra_neo`
--
ALTER TABLE `anggaran_program_renstra_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indeks untuk tabel `anggaran_template`
--
ALTER TABLE `anggaran_template`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `anggaran_workflow_log`
--
ALTER TABLE `anggaran_workflow_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workflow_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`tgl_copy`);

--
-- Indeks untuk tabel `aset_neo`
--
ALTER TABLE `aset_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cache_schema_naskah`
--
ALTER TABLE `cache_schema_naskah`
  ADD PRIMARY KEY (`jenis_id`,`schema_version`);

--
-- Indeks untuk tabel `cuti_pegawai_neo`
--
ALTER TABLE `cuti_pegawai_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cuti_scope` (`kd_wilayah`,`kd_opd`,`tahun`),
  ADD KEY `idx_cuti_pegawai` (`pegawai_id`);

--
-- Indeks untuk tabel `daftar_paket_neo`
--
ALTER TABLE `daftar_paket_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_paket_anggaran` (`sumber_tahap`,`anggaran_id`),
  ADD KEY `idx_paket_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`is_deleted`);

--
-- Indeks untuk tabel `daftar_realisasi_neo`
--
ALTER TABLE `daftar_realisasi_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_realisasi_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`is_deleted`),
  ADD KEY `idx_realisasi_kontrak` (`kontrak_id`,`tanggal`,`is_deleted`),
  ADD KEY `idx_realisasi_rab` (`rab_id`),
  ADD KEY `idx_realisasi_contract_phase27` (`kontrak_id`,`is_deleted`,`tanggal`);

--
-- Indeks untuk tabel `daftar_uraian_paket`
--
ALTER TABLE `daftar_uraian_paket`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `db_asn_pemda_neo`
--
ALTER TABLE `db_asn_pemda_neo`
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- Indeks untuk tabel `dokumen_pegawai_neo`
--
ALTER TABLE `dokumen_pegawai_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dok_pegawai` (`pegawai_id`,`jenis_dokumen`),
  ADD KEY `idx_dok_pegawai_scope` (`kd_wilayah`,`kd_opd`,`tahun`);

--
-- Indeks untuk tabel `dpa_neo`
--
ALTER TABLE `dpa_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dpa_source` (`source_table`,`source_id`);

--
-- Indeks untuk tabel `dppa_neo`
--
ALTER TABLE `dppa_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dppa_source` (`source_table`,`source_id`);

--
-- Indeks untuk tabel `group_rekap_akun`
--
ALTER TABLE `group_rekap_akun`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `group_sub_kegiatan`
--
ALTER TABLE `group_sub_kegiatan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `halaman_berita`
--
ALTER TABLE `halaman_berita`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `import_logs`
--
ALTER TABLE `import_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `indikator_program_renstra_neo`
--
ALTER TABLE `indikator_program_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `indikator_sasaran_renstra_neo`
--
ALTER TABLE `indikator_sasaran_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kd_wilayah_neo`
--
ALTER TABLE `kd_wilayah_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_kode_wilayah` (`kode`),
  ADD KEY `idx_kode_wilayah` (`kode`);

--
-- Indeks untuk tabel `kegiatan_renstra_neo`
--
ALTER TABLE `kegiatan_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kontrak_dokumen_neo`
--
ALTER TABLE `kontrak_dokumen_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dok_kontrak` (`kontrak_id`,`jenis_dokumen`),
  ADD KEY `idx_dok_scope` (`kd_wilayah`,`kd_opd`,`tahun`);

--
-- Indeks untuk tabel `kontrak_item_neo`
--
ALTER TABLE `kontrak_item_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kontrak_item` (`kontrak_id`,`tahap`,`anggaran_id`,`is_deleted`),
  ADD KEY `idx_kontrak_item_anggaran` (`tahap`,`anggaran_id`,`is_deleted`),
  ADD KEY `idx_kontrak_item_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`is_deleted`);

--
-- Indeks untuk tabel `kontrak_jadwal_neo`
--
ALTER TABLE `kontrak_jadwal_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kontrak_minggu` (`kontrak_id`,`minggu_ke`),
  ADD KEY `idx_jadwal_scope` (`kd_wilayah`,`kd_opd`,`tahun`);

--
-- Indeks untuk tabel `kontrak_neo`
--
ALTER TABLE `kontrak_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kontrak_nomor_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`nomor_kontrak`,`is_deleted`),
  ADD KEY `idx_kontrak_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`is_deleted`),
  ADD KEY `idx_kontrak_anggaran` (`tahap`,`anggaran_id`),
  ADD KEY `idx_kontrak_rekanan` (`rekanan_id`);

--
-- Indeks untuk tabel `kop_surat_neo`
--
ALTER TABLE `kop_surat_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kop_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`aktif`);

--
-- Indeks untuk tabel `log_activity`
--
ALTER TABLE `log_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indeks untuk tabel `master_biaya`
--
ALTER TABLE `master_biaya`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_master_biaya_scope` (`tipe`,`kd_wilayah`,`tahun`,`peraturan_id`,`is_deleted`),
  ADD KEY `idx_master_biaya_satuan` (`satuan_id`),
  ADD KEY `idx_master_biaya_kode` (`kode`);

--
-- Indeks untuk tabel `master_biaya_akun`
--
ALTER TABLE `master_biaya_akun`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_master_biaya` (`master_biaya_id`),
  ADD KEY `idx_master_biaya_akun_scope` (`kd_wilayah`,`peraturan_id`,`is_deleted`),
  ADD KEY `idx_master_biaya_akun_lookup` (`master_biaya_id`,`kd_akun`,`peraturan_id`,`is_deleted`);

--
-- Indeks untuk tabel `misi_renstra_neo`
--
ALTER TABLE `misi_renstra_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_misi` (`renstra_id`,`nama_misi`) USING HASH;

--
-- Indeks untuk tabel `organisasi_neo`
--
ALTER TABLE `organisasi_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pejabat_tahunan_neo`
--
ALTER TABLE `pejabat_tahunan_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pejabat_aktif` (`kd_wilayah`,`kd_opd`,`tahun`,`jenis_pejabat`,`berlaku_mulai`,`berlaku_sampai`,`is_deleted`),
  ADD KEY `idx_pejabat_sub` (`kd_sub_keg`,`jenis_pejabat`,`is_deleted`);

--
-- Indeks untuk tabel `pengaturan_neo`
--
ALTER TABLE `pengaturan_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `peraturan_neo`
--
ALTER TABLE `peraturan_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `periode_rpjmd`
--
ALTER TABLE `periode_rpjmd`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `program_renstra_neo`
--
ALTER TABLE `program_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rab_paket_neo`
--
ALTER TABLE `rab_paket_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rab_kontrak` (`kontrak_id`),
  ADD KEY `idx_rab_kontrak_item` (`kontrak_item_id`);

--
-- Indeks untuk tabel `ref_jenis_naskah`
--
ALTER TABLE `ref_jenis_naskah`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ref_jenis_naskah_dinas`
--
ALTER TABLE `ref_jenis_naskah_dinas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ref_kelompok_naskah`
--
ALTER TABLE `ref_kelompok_naskah`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ref_klasifikasi_keamanan`
--
ALTER TABLE `ref_klasifikasi_keamanan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ref_template_naskah`
--
ALTER TABLE `ref_template_naskah`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `register_naskah_dinas`
--
ALTER TABLE `register_naskah_dinas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rekanan_akta`
--
ALTER TABLE `rekanan_akta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rekanan` (`rekanan_id`);

--
-- Indeks untuk tabel `rekanan_neo`
--
ALTER TABLE `rekanan_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rekening_kegiatan`
--
ALTER TABLE `rekening_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_kode_scope` (`kode`,`kd_wilayah`,`peraturan_id`),
  ADD KEY `idx_rekening_kode` (`kode`),
  ADD KEY `idx_rekening_parent` (`parent_kode`),
  ADD KEY `idx_rekening_level` (`level`);

--
-- Indeks untuk tabel `rencana_realisasi_anggaran_neo`
--
ALTER TABLE `rencana_realisasi_anggaran_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rencana_bulan` (`dokumen`,`anggaran_id`,`bulan`),
  ADD KEY `idx_rencana_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`dokumen`,`is_deleted`),
  ADD KEY `idx_rencana_sub_akun` (`kd_sub_keg`,`kd_akun`);

--
-- Indeks untuk tabel `renja_neo`
--
ALTER TABLE `renja_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_renja_source` (`source_table`,`source_id`);

--
-- Indeks untuk tabel `renja_p_neo`
--
ALTER TABLE `renja_p_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_renja_p_source` (`source_table`,`source_id`);

--
-- Indeks untuk tabel `renstra_neo`
--
ALTER TABLE `renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `riwayat_jabatan_neo`
--
ALTER TABLE `riwayat_jabatan_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rj_scope` (`kd_wilayah`,`kd_opd`,`tahun`),
  ADD KEY `idx_rj_pegawai` (`pegawai_id`);

--
-- Indeks untuk tabel `riwayat_pangkat_neo`
--
ALTER TABLE `riwayat_pangkat_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rp_scope` (`kd_wilayah`,`kd_opd`,`tahun`),
  ADD KEY `idx_rp_pegawai` (`pegawai_id`);

--
-- Indeks untuk tabel `rka_neo`
--
ALTER TABLE `rka_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rka_source` (`source_table`,`source_id`);

--
-- Indeks untuk tabel `rka_p_neo`
--
ALTER TABLE `rka_p_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rka_p_source` (`source_table`,`source_id`);

--
-- Indeks untuk tabel `rkpd_neo`
--
ALTER TABLE `rkpd_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rkpd_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`is_deleted`),
  ADD KEY `idx_rkpd_sub` (`kd_sub_keg`),
  ADD KEY `idx_rkpd_renstra` (`renstra_sub_kegiatan_id`);

--
-- Indeks untuk tabel `rkpd_p_neo`
--
ALTER TABLE `rkpd_p_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rkpd_p_source` (`source_rkpd_id`),
  ADD KEY `idx_rkpd_p_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`is_deleted`),
  ADD KEY `idx_rkpd_p_sub` (`kd_sub_keg`);

--
-- Indeks untuk tabel `sasaran_renstra_neo`
--
ALTER TABLE `sasaran_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `satuan_neo`
--
ALTER TABLE `satuan_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sk_asn_neo`
--
ALTER TABLE `sk_asn_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sk_pegawai_neo`
--
ALTER TABLE `sk_pegawai_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sk_scope` (`kd_wilayah`,`kd_opd`,`tahun`),
  ADD KEY `idx_sk_pegawai` (`pegawai_id`);

--
-- Indeks untuk tabel `sub_kegiatan_renstra_neo`
--
ALTER TABLE `sub_kegiatan_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sub_keg_dpa_neo`
--
ALTER TABLE `sub_keg_dpa_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sub_keg_renja_neo`
--
ALTER TABLE `sub_keg_renja_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sumber_dana_neo`
--
ALTER TABLE `sumber_dana_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tapd_penugasan_neo`
--
ALTER TABLE `tapd_penugasan_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tapd_berlaku` (`kd_wilayah`,`tahun`,`tanggal_mulai`,`tanggal_selesai`,`aktif`,`is_deleted`);

--
-- Indeks untuk tabel `trx_naskah_dinas`
--
ALTER TABLE `trx_naskah_dinas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `trx_naskah_meta`
--
ALTER TABLE `trx_naskah_meta`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `trx_naskah_status_history`
--
ALTER TABLE `trx_naskah_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_naskah_status_history` (`naskah_id`,`created_at`);

--
-- Indeks untuk tabel `trx_naskah_struktur`
--
ALTER TABLE `trx_naskah_struktur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_naskah_struktur` (`naskah_id`);

--
-- Indeks untuk tabel `trx_nomor_counter`
--
ALTER TABLE `trx_nomor_counter`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik` (`klasifikasi_id`,`tahun`);

--
-- Indeks untuk tabel `tujuan_renstra_neo`
--
ALTER TABLE `tujuan_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `user_sesendok_biila`
--
ALTER TABLE `user_sesendok_biila`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `user_subkegiatan_neo`
--
ALTER TABLE `user_subkegiatan_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_sub_periode` (`user_id`,`kd_sub_keg`,`peran`,`berlaku_mulai`),
  ADD KEY `idx_user_sub_scope` (`kd_wilayah`,`kd_opd`,`tahun`);

--
-- Indeks untuk tabel `wallchat`
--
ALTER TABLE `wallchat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`),
  ADD KEY `idx_wallchat_receiver` (`receiver_id`,`type`,`is_deleted`,`created_at`),
  ADD KEY `idx_wallchat_feed` (`type`,`parent_id`,`is_deleted`,`created_at`),
  ADD KEY `idx_wallchat_private_visibility` (`type`,`user_id`,`receiver_id`,`is_deleted`,`deleted_by_sender`,`deleted_by_receiver`,`created_at`),
  ADD KEY `idx_wallchat_owner_feed` (`user_id`,`type`,`is_deleted`,`created_at`),
  ADD KEY `idx_wall_feed` (`type`,`is_deleted`,`parent_id`,`created_at`),
  ADD KEY `idx_wall_private_sender` (`type`,`is_deleted`,`user_id`,`deleted_by_sender`,`created_at`),
  ADD KEY `idx_wall_private_receiver` (`type`,`is_deleted`,`receiver_id`,`deleted_by_receiver`,`created_at`);

--
-- Indeks untuk tabel `wilayah_neo`
--
ALTER TABLE `wilayah_neo`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi_pegawai_neo`
--
ALTER TABLE `absensi_pegawai_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `akun_neo`
--
ALTER TABLE `akun_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `analisa_rab`
--
ALTER TABLE `analisa_rab`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `anggaran_copy_log`
--
ALTER TABLE `anggaran_copy_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `anggaran_perubahan_template`
--
ALTER TABLE `anggaran_perubahan_template`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `anggaran_program_renstra_neo`
--
ALTER TABLE `anggaran_program_renstra_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `anggaran_template`
--
ALTER TABLE `anggaran_template`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `anggaran_workflow_log`
--
ALTER TABLE `anggaran_workflow_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `aset_neo`
--
ALTER TABLE `aset_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cuti_pegawai_neo`
--
ALTER TABLE `cuti_pegawai_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `daftar_paket_neo`
--
ALTER TABLE `daftar_paket_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `daftar_realisasi_neo`
--
ALTER TABLE `daftar_realisasi_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `daftar_uraian_paket`
--
ALTER TABLE `daftar_uraian_paket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `db_asn_pemda_neo`
--
ALTER TABLE `db_asn_pemda_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `dokumen_pegawai_neo`
--
ALTER TABLE `dokumen_pegawai_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `dpa_neo`
--
ALTER TABLE `dpa_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `dppa_neo`
--
ALTER TABLE `dppa_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `group_rekap_akun`
--
ALTER TABLE `group_rekap_akun`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `group_sub_kegiatan`
--
ALTER TABLE `group_sub_kegiatan`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `halaman_berita`
--
ALTER TABLE `halaman_berita`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `indikator_program_renstra_neo`
--
ALTER TABLE `indikator_program_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `indikator_sasaran_renstra_neo`
--
ALTER TABLE `indikator_sasaran_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kd_wilayah_neo`
--
ALTER TABLE `kd_wilayah_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kegiatan_renstra_neo`
--
ALTER TABLE `kegiatan_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kontrak_dokumen_neo`
--
ALTER TABLE `kontrak_dokumen_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kontrak_item_neo`
--
ALTER TABLE `kontrak_item_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kontrak_jadwal_neo`
--
ALTER TABLE `kontrak_jadwal_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kontrak_neo`
--
ALTER TABLE `kontrak_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kop_surat_neo`
--
ALTER TABLE `kop_surat_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `log_activity`
--
ALTER TABLE `log_activity`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `master_biaya`
--
ALTER TABLE `master_biaya`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `master_biaya_akun`
--
ALTER TABLE `master_biaya_akun`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `misi_renstra_neo`
--
ALTER TABLE `misi_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `organisasi_neo`
--
ALTER TABLE `organisasi_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pejabat_tahunan_neo`
--
ALTER TABLE `pejabat_tahunan_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengaturan_neo`
--
ALTER TABLE `pengaturan_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `peraturan_neo`
--
ALTER TABLE `peraturan_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `periode_rpjmd`
--
ALTER TABLE `periode_rpjmd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `program_renstra_neo`
--
ALTER TABLE `program_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rab_paket_neo`
--
ALTER TABLE `rab_paket_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_jenis_naskah`
--
ALTER TABLE `ref_jenis_naskah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_jenis_naskah_dinas`
--
ALTER TABLE `ref_jenis_naskah_dinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_kelompok_naskah`
--
ALTER TABLE `ref_kelompok_naskah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_klasifikasi_keamanan`
--
ALTER TABLE `ref_klasifikasi_keamanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_template_naskah`
--
ALTER TABLE `ref_template_naskah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `register_naskah_dinas`
--
ALTER TABLE `register_naskah_dinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rekanan_akta`
--
ALTER TABLE `rekanan_akta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rekanan_neo`
--
ALTER TABLE `rekanan_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rekening_kegiatan`
--
ALTER TABLE `rekening_kegiatan`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rencana_realisasi_anggaran_neo`
--
ALTER TABLE `rencana_realisasi_anggaran_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `renja_neo`
--
ALTER TABLE `renja_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `renja_p_neo`
--
ALTER TABLE `renja_p_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `renstra_neo`
--
ALTER TABLE `renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `riwayat_jabatan_neo`
--
ALTER TABLE `riwayat_jabatan_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `riwayat_pangkat_neo`
--
ALTER TABLE `riwayat_pangkat_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rka_neo`
--
ALTER TABLE `rka_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rka_p_neo`
--
ALTER TABLE `rka_p_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rkpd_neo`
--
ALTER TABLE `rkpd_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rkpd_p_neo`
--
ALTER TABLE `rkpd_p_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sasaran_renstra_neo`
--
ALTER TABLE `sasaran_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `satuan_neo`
--
ALTER TABLE `satuan_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sk_asn_neo`
--
ALTER TABLE `sk_asn_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sk_pegawai_neo`
--
ALTER TABLE `sk_pegawai_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sub_kegiatan_renstra_neo`
--
ALTER TABLE `sub_kegiatan_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sub_keg_dpa_neo`
--
ALTER TABLE `sub_keg_dpa_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sub_keg_renja_neo`
--
ALTER TABLE `sub_keg_renja_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sumber_dana_neo`
--
ALTER TABLE `sumber_dana_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tapd_penugasan_neo`
--
ALTER TABLE `tapd_penugasan_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `trx_naskah_dinas`
--
ALTER TABLE `trx_naskah_dinas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `trx_naskah_meta`
--
ALTER TABLE `trx_naskah_meta`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT untuk tabel `trx_naskah_status_history`
--
ALTER TABLE `trx_naskah_status_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `trx_naskah_struktur`
--
ALTER TABLE `trx_naskah_struktur`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `trx_nomor_counter`
--
ALTER TABLE `trx_nomor_counter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tujuan_renstra_neo`
--
ALTER TABLE `tujuan_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `user_sesendok_biila`
--
ALTER TABLE `user_sesendok_biila`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `user_subkegiatan_neo`
--
ALTER TABLE `user_subkegiatan_neo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `wallchat`
--
ALTER TABLE `wallchat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `wilayah_neo`
--
ALTER TABLE `wilayah_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `kontrak_item_neo`
--
ALTER TABLE `kontrak_item_neo`
  ADD CONSTRAINT `fk_kontrak_item_header` FOREIGN KEY (`kontrak_id`) REFERENCES `kontrak_neo` (`id`);

--
-- Ketidakleluasaan untuk tabel `master_biaya_akun`
--
ALTER TABLE `master_biaya_akun`
  ADD CONSTRAINT `fk_master_biaya` FOREIGN KEY (`master_biaya_id`) REFERENCES `master_biaya` (`id`);

--
-- Ketidakleluasaan untuk tabel `rekanan_akta`
--
ALTER TABLE `rekanan_akta`
  ADD CONSTRAINT `1` FOREIGN KEY (`rekanan_id`) REFERENCES `rekanan_neo` (`id`);

--
-- Ketidakleluasaan untuk tabel `trx_naskah_status_history`
--
ALTER TABLE `trx_naskah_status_history`
  ADD CONSTRAINT `fk_naskah_status_history` FOREIGN KEY (`naskah_id`) REFERENCES `trx_naskah_dinas` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `trx_naskah_struktur`
--
ALTER TABLE `trx_naskah_struktur`
  ADD CONSTRAINT `fk_naskah_struktur` FOREIGN KEY (`naskah_id`) REFERENCES `trx_naskah_dinas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
