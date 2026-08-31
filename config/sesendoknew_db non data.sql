-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 31 Agu 2026 pada 03.47
-- Versi server: 12.3.2-MariaDB
-- Versi PHP: 8.5.8

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
  `kd_wilayah` varchar(60) DEFAULT NULL,
  `kd_opd` varchar(60) DEFAULT NULL,
  `kd_sub_keg` varchar(100) NOT NULL,
  `kd_akun` varchar(100) NOT NULL,
  `id_paket` int(11) NOT NULL,
  `kontrak_id` bigint(20) DEFAULT NULL,
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

--
-- Dumping data untuk tabel `db_asn_pemda_neo`
--

INSERT INTO `db_asn_pemda_neo` (`id`, `kd_wilayah`, `kd_opd`, `nama`, `gelar_depan`, `gelar`, `nip`, `t4_lahir`, `tgl_lahir`, `file_akta_lahir`, `golongan`, `ruang`, `agama`, `kelamin`, `jenis_kepeg`, `status_kepeg`, `jabatan`, `id_jabatan`, `no_ktp`, `file_ktp`, `no_kk`, `tgl_kk`, `file_kk`, `npwp`, `file_npwp`, `alamat`, `kontak_person`, `email`, `status`, `no_buku_nikah`, `tgl_nikah`, `file_buku_nikah`, `nama_anak`, `nik_anak`, `nama_ayah`, `nama_ibu`, `nama_pasangan`, `no_karpeg`, `tgl_karpeg`, `file_karpeg`, `no_taspen`, `tgl_taspen`, `no_karsi_karsu`, `tgl_karsi_karsu`, `file_karsi_karsu`, `nmr_sk_terakhir`, `tgl_tmt_akhir`, `pj_sk_terakhir`, `nmr_sk_cpns`, `tgl_sk_cpns`, `pj_sk_cpns`, `nmr_sk_pns`, `tgl_sk_pns`, `pj_sk_pns`, `sk_pangkat_terakhir`, `tgl_sk_terakhir`, `pend_sekolah_sd`, `pend_ijasah_sd`, `pend_tgl_tmt_sd`, `pend_t4_sd`, `pend_file_sd`, `pend_sekolah_smp`, `pend_ijasah_smp`, `pend_tgl_tmt_smp`, `pend_t4_smp`, `pend_file_smp`, `pend_sekolah_smu`, `pend_ijasah_smu`, `pend_tgl_tmt_smu`, `pend_t4_smu`, `pend_file_smu`, `pend_sekolah_s1`, `pend_ijasah_s1`, `pend_tgl_tmt_s1`, `pend_t4_s1`, `pend_file_s1`, `pend_sekolah_s2`, `pend_ijasah_s2`, `pend_tgl_tmt_s2`, `pend_t4_s2`, `pend_file_s2`, `pend_sekolah_s3`, `pend_ijasah_s3`, `pend_tgl_tmt_s3`, `pend_t4_s3`, `pend_file_s3`, `pend_sekolah_akhir`, `pend_ijasah_akhir`, `pend_tgl_tmt_akhir`, `pend_t4_akhir`, `pend_file_akhir`, `file_photo`, `gapok`, `aktif`, `no_urut`, `unit_kerja`, `no_rekening`, `nama_bank`, `urutan`, `kelompok`, `suka`, `follow`, `keterangan`, `disable`, `tgl_insert`, `tgl_update`, `username_insert`, `username_update`, `is_deleted`) VALUES
(1, '76.01', '1.03.0.00.0.00.01.0000', 'sumarlin', NULL, 'ST.M.AP', '198006152006041018', 'patobong', '1980-06-15', NULL, '4', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', '-', NULL, '0000', NULL, NULL, NULL, NULL, '674092978814000', NULL, 'Jl.Imam bonjol', '081341310181', 'anchupumatra@gmail.com', 'menikah', '322/41/VIII/2009', '2009-08-18', NULL, 'alula khiffa ayah;aluna tapanasyifa', '', 'H. Dulu Pangngawa', 'Hj.Saming', 'Echi Abd. Samad', '331807', '2009-02-17', NULL, 'P 3000241100', '2011-01-26', 'AA 04023929', '2016-11-22', NULL, '823.3/277/BKDD', '2024-01-01', '', '813.3/254/BKD', '2006-12-29', '', '821.13/42/BkD', '2008-03-04', '', '', '1970-01-01', 'Madrasah Ibtidaiyah Pinrang', 'E.IV/t/MI-6/161/92', '1992-05-07', '', NULL, 'Madrasah Tsanawiyah Negeri Pare-Pare', 'E.IV/t/MTs-403/1033/1995', '1995-06-01', '', NULL, 'Madrash Aliyah Swasta diakui DDI Patobong', 'E.IV/t/MA 154/235/98', '1998-05-22', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI -Makassaar', '034/A.08/FTE/SL-UMI/2003', '2003-06-19', 'Teknik Elektro', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2026-01-08 02:30:35', 'nabiila', 'nabiila', 0),
(2, '76.01', '1.03.0.00.0.00.01.0000', 'MAHMUD', NULL, 'SP.,M.SI', '196901272003121004', '', '1969-01-27', NULL, '4', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'asiten', NULL, '0', NULL, NULL, NULL, NULL, '0', NULL, '0', '0', '1@gmail.com', 'menikah', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2026-01-08 02:33:19', 'nabiila', 'nabiila', 0),
(3, '76.01', '1.03.0.00.0.00.01.0000', 'SYAMSUNAR', NULL, 'SP.,M.M', '197503102009031001', '', '1975-03-10', NULL, '4', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Kepala Dinas', NULL, '1', NULL, NULL, NULL, NULL, '1', NULL, '-', '08', '1@gmail.com', 'janda-duda', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2026-01-08 02:30:49', 'nabiila', 'nabiila', 0),
(4, '76.01', '1.03.0.00.0.00.01.0000', 'kamaruddin', NULL, 'ST', '197308142005021003', 'panyampa', '1970-01-01', NULL, '4', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Kepala bidang pembinaan jasa konstruksi', NULL, '7601021408730004', NULL, NULL, NULL, NULL, '674092895814000', NULL, 'JL. Mangga Pasangkayu', '081280890175', 'kama99697@gmail.com', 'menikah', '696/16/XI/2006', '2006-05-11', NULL, 'afifa kirani;muhammad murli almubarak', '', 'abdul rahim', 'ST Maemuna', 'Rachma.S', 'M 139774', '1970-01-01', NULL, '580036396', '1970-01-01', 'AA 04024229', '1970-01-01', NULL, '823.4-01', '2024-01-01', 'Gubernur Sulawesi Barat', '813.2/393/BKD', '1970-01-01', 'Bupati Mamuju Utara', '821.13/41/BKD', '1970-01-01', 'Bupati Mamuju Utara', '', '2018-05-03', 'SD Negeri 008 Panyampa', 'No 06 OA oa 0049421', '1970-01-01', 'Panyampa,Campalagiang', NULL, 'Madrasah Tsanawiyah Negeri Tinambung Polmas', 'No E.IV/t/MTs-405/7614/89', '1970-01-01', 'Tinambung,Polmas', NULL, 'STM Wonomulyo', 'No.06 OB or 0020391', '1992-12-06', 'Wonomulyo,Polewali Mamasa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNIVERSITAS 45', 'Nomor 630-45/153-04/A/2002', '1970-01-01', 'Tehnik Arsitektur', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(5, '76.01', '1.03.0.00.0.00.01.0000', 'nardin', NULL, 'ST', '197910012011011004', 'kasoloang', '1979-01-10', NULL, '3', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Kepala bidang cipta karya', NULL, '7601020110790004', NULL, NULL, NULL, NULL, '159834555814000', NULL, 'Jl.Andi Bandaco', '085342567877', 'binamarga11.55@gmail.com', 'menikah', '131/01/X/2009', '2009-09-26', NULL, 'lingga prayata widyatna;apta widyatna anindya', '', 'Ruddin', 'Nurlina', 'Anggra', '', '1904-01-01', NULL, '791001110110040', '2012-04-19', 'AA 04023596', '2016-11-22', NULL, '823.3/624/BKDD', '2024-01-01', '', '813.3/475/BKDD', '2011-03-21', '', '821.13/533/BKDD', '2012-03-30', '', '', '2015-04-13', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI Makassar', '045/A.08/FTK-SI/S1-UMI/2004', '2004-06-26', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(6, '76.01', '1.03.0.00.0.00.01.0000', 'i nyoman sumerta', NULL, 'ST', '198301012010011020', 'gunung sari', '1983-01-01', NULL, '3', 'c', 'hindu', 'pria', 'pnsd2', 'peg_tetap', 'Kepala sumber daya air', NULL, '7601020101830012', NULL, NULL, NULL, NULL, '582268744814000', NULL, 'Dusun Bambaraba', '082290193339', 'putrasumerta@gmail.com', 'menikah', '454-006/PHDI-DG/XI/2013', '2013-02-11', NULL, 'i wayan darma saputra;ni kadek gesilla dharmayanti', '', 'I Wayan Malen', 'Ni Wayan Kerau', 'Ni Kadek Suparni,SKM', 'P 461892', '2011-10-31', NULL, '870101100110200', '2011-05-30', 'AA 04023892', '2016-11-22', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/376/BKDD', '2010-02-22', '', '821.13/534/BKDD', '2011-03-31', '', '', '1970-01-01', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNTAD Palu', '16030/41.06.S1/2006', '2006-06-21', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(7, '76.01', '1.03.0.00.0.00.01.0000', 'nurmadina', NULL, 'S.Sos.M.Si', '198208022003122003', 'panggeleroang', '1982-02-08', NULL, '3', 'd', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Kepala bidang penataan dan pemanfaatan ruang', NULL, '7601024208820003', NULL, NULL, NULL, NULL, '496903535814000', NULL, 'Jl.Urip Sumiharjo psky', '085397760008', 'madian.butik@gmail.com', 'menikah', '39/39/I/2008', '2007-12-28', NULL, 'jihan athira nasrillah;sherien ramadhani nasrillah;savira misha nasrillah', '', 'Abd.Mandir', 'Tani', 'Khaerul Nasrilla', 'M 005229', '2005-09-23', NULL, '580029116', '2005-03-08', 'BA 04015069', '1970-01-01', NULL, '823.3/10079/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2-104', '2004-02-01', '', '821.12/105/III/2005/BKD', '2005-03-28', '', '', '2017-07-09', 'SD INP NO 38 Pengaleroang', '06 OA oa 0034190', '1995-05-06', '', NULL, 'SMP N 4 Sendana', '06 DI 0058482', '1998-06-02', '', NULL, 'SMK N 2 Majene', '06. MK 0264746', '2001-06-15', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'STIA PANCA MARGA PALU', '220/stia-pm/2008', '2008-04-10', 'ilmu administrasi negara', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(8, '76.01', '1.03.0.00.0.00.01.0000', 'budiyansa', NULL, 'ST', '197508022006041011', 'pangkep', '1975-02-08', NULL, '4', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '674092960814000', NULL, 'Jl PDAM Pasangkayu', '081354877266', 'budiyansa.matra@gmail.com', 'menikah', '168/IX/VII/2007', '2007-08-11', NULL, 'muh.daffa dhiyaul haq;aesar aulayainqaila', '', 'Burhan galilo', 'Hj.Rudiah', 'Andi Damayanti', '306777', '2008-10-10', NULL, 'P 30002760', '2009-04-02', 'aa 04023593', '2016-11-22', NULL, '823.4-01', '2024-01-01', 'Gubernur Sulawesi Barat', '813.3/615/BKD', '2006-12-30', '', '821.13/42/BKD', '2008-03-04', '', '', '2019-05-08', 'SD Negeri 1 Kemaraya', 'No 23 OA oa 0000368', '1988-06-15', '', NULL, 'Swasta Frater diakui kendari', 'No 23 OA ob 0185456', '1991-06-07', '', NULL, 'SMA 1 Kendari', 'No 23 OB oe 0362975', '1994-05-28', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI - Makassar', '177/FTS/SL-UMI/2002', '2002-01-29', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(9, '76.01', '1.03.0.00.0.00.01.0000', 'abd. rasyid', NULL, 'S.pd,M.pd', '196704051991031015', 'bone', '1967-05-04', NULL, '4', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '473005973813000', NULL, '', '085213782745', 'rasyid050467@gmail.com', 'menikah', '399,11,XI,95', '1995-02-11', NULL, 'nurlaylah qadria;muh.safaruddin;ajeng nur azisah rasyid', '', 'Tarenre', 'St.Abang', 'Ardiany', 'F 305019', '1993-09-12', NULL, '131953066', '2000-02-10', 'AA 04024222', '2016-11-22', NULL, 'PD.823.4-36', '2024-01-01', '', '01944/106.D1/C.41/1991', '1991-08-08', '', '00495/106.D1/C.41/1993', '1993-01-30', '', '', '2004-11-15', 'SD No 116 Timurung', 'No XXIII Aa 39730', '1980-05-20', '', NULL, 'Smp Negeri Pompanua', 'No 06 OB ob 0242072', '1983-05-24', '', NULL, 'SMA Negeri 2 Watampone', 'No 06 OC oh 0062382', '1986-05-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UKM-Malang', '1901-09210216', '2009-04-18', 'Pendidikan Sosial', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(10, '76.01', '1.03.0.00.0.00.01.0000', 'herry hasanuddin. m', NULL, 'ST', '197101152007011019', 'kota baru', '1971-01-15', NULL, '3', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '7601021501710002', NULL, NULL, NULL, NULL, '674093018814000', NULL, 'Jl.Andi depu', '08529931112508124117532', 'herrymunier@gmail.com', 'menikah', '276/22/I/2000', '2000-01-23', NULL, 'ananda fachrizal munier;reza fairuzzabadi munier;faiz faturrahman munier;rayhan kanza munier;rizky amalia munier', '', 'Zainuddin Munier', 'ST. Nurhayati', 'Rosmawati, SE', 'N 460752', '2009-11-09', NULL, 'P 3000504200', '2010-03-02', 'AA 04023592', '2016-11-22', NULL, '823.3/507/BKPP', '2024-01-01', 'bupati mamuju utara', '813.3/255/BKD', '2007-12-12', '', '821.13/354/BKDD', '2008-12-31', '', '', '1970-01-01', 'SDN Centre Candrakila Ni.987', '15 OA oa 015913', '1984-05-19', '', NULL, 'SMP N 1 Palaihara', '15 OB ob 0497909', '1987-06-06', '', NULL, 'SMA N 8 Wung Pandang', '06 OC oh 0027045', '1990-05-26', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI-MAKASSAR', '0052/FTM/SL-UMI/1996', '1996-02-13', 'Teknik Mesin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(11, '76.01', '1.03.0.00.0.00.01.0000', 'muammar nur', NULL, 'ST', '197501192007011013', 'majene', '1975-01-19', NULL, '3', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '7601021901750001', NULL, NULL, NULL, NULL, '674093000814000', NULL, 'Jl. Trans Sulawesi', '085241210978', 'muammar_noer@yahoo.co.id', 'menikah', '187/27/VI/2013', '2013-06-16', NULL, 'muhammad afham nur', '', 'H. Muh. As&#39;ad Nur', 'Hj. Siti Safah', 'Hj. Adibah Putri Nas, Sos', 'N 460754', '2009-11-09', NULL, 'P 3000494200', '2010-01-30', 'AA 04023891', '2016-11-22', NULL, '823.3/631/BKPP', '2024-01-01', 'bupati mamuju utara', '813.3/255/BKD', '2007-12-12', '', '821.13/354/BKDD', '2008-12-31', '', '', '1970-01-01', 'SDN 01 Saleppa', '06 OA oa 0126505', '1988-06-24', '', NULL, 'SMP N 2 Majene', '06 OA ob 0448497', '1991-06-08', '', NULL, 'SMA N 1 Majene', '06 OB oe 0360014', '1994-05-23', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI-MAKASSAR', '083/FTS/SL-UMI/2002', '2002-08-07', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(12, '76.01', '1.03.0.00.0.00.01.0000', 'pusmahasib', NULL, 'S.Si', '197908252009031003', 'panincong', '1979-08-25', NULL, '3', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '7601022608790003', NULL, NULL, NULL, NULL, '145104808814000', NULL, 'Jl.Trans Sulawesi', '085256612009', 'pusmatra34@gmail.com', 'menikah', '91/08/VII/2010', '2010-07-14', NULL, 'miftah arrahmah', '', 'H.Sukarta', 'Hj Murniati', 'Rahmatia,ST', 'P 333298', '2011-01-19', NULL, '790825090310030', '2011-01-26', 'AA 04023594', '2016-11-22', NULL, '823.3/507/BKPP', '2024-01-01', 'bupati mamuju utara', '813.3/75/BKDD', '2009-03-17', '', '821.13/518.A/BKDD', '2010-01-03', '', '', '1970-01-01', 'SDN 60 Panincong', '06 OA oa 0071254', '1991-06-10', '', NULL, 'SMPN Panincong', '06 OA ob 1623533', '1994-06-06', '', NULL, 'SMA N 1 Dori-dori', '06 OB 0f 102 215539', '1997-05-31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'IPB', '1009030031', '1904-01-01', 'Sains Agrometeorologi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(13, '76.01', '1.03.0.00.0.00.01.0000', 'patahuddin', NULL, 'ST.M.AP', '197110122009031001', 'pare-pare', '1971-12-10', NULL, '3', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '7601021210710002', NULL, NULL, NULL, NULL, '145104303814000', NULL, 'Jl.Trans Sulawesi', '082191484601', 'patahuddin.husain@gmail.com', 'menikah', '120/08/VIII/2000', '2000-07-08', NULL, 'reyka nurfathona;reyna nurafidah;riuana ananda putri', '', 'H. Husain. SB', 'Hj. Andi Ona', 'Ramnawati. M Ramli, SE', 'P 081722', '2010-03-05', NULL, '711012090310010', '2011-01-24', 'AA 04023597', '2016-11-22', NULL, '823.3/435/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/75/BKDD', '2009-03-17', '', '821.13/518.A/BKDD', '2010-01-03', '', '', '1970-01-01', 'SDN Kayumate', '06 OA oa 009101', '1984-05-21', '', NULL, 'SMP N Kalukku', '06 OB ob 0409776', '1987-06-06', '', NULL, 'SMA N 1 Pare-Pare', 'o6 OC oh 0188376', '1990-05-26', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI', '039/FTM/SLN-UMI/1996', '1996-12-05', 'Teknik Mesin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(14, '76.01', '1.03.0.00.0.00.01.0000', 'irmawati', NULL, 'SE', '197601032006042007', 'bambaira', '1976-03-01', NULL, '3', 'c', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Kasubag umum dan kepegawaian', NULL, '7601024301760002', NULL, NULL, NULL, NULL, '700417587814000', NULL, 'Jl.Kakatua', '082189737950', 'irmawatiidris1976@gmail.com', 'menikah', '792.79.VIII.2000', '2000-08-13', NULL, 'reynaldy faizal;kavriansyah', '', 'M.Idris H.Amin', 'Hj Mar&#39;ani M.Acap', 'Sumaila', 'n 306521', '2006-10-10', NULL, 'P 30000745', '2009-05-02', 'ba 04025319', '2016-10-18', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/215/BKD', '2006-01-11', '', '821.12/41/BkD', '2008-04-03', '', '', '1970-01-01', 'SDN Pasangkayu', '06 OA oa 0128569', '1988-06-24', '', NULL, 'SMPN Pasangkayu', '06 OA ob 0453237', '1991-06-08', 'Pasangkayu', NULL, 'SMAN Donggala', '24 OB oe 0728990', '1994-05-30', 'Donggala', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNISMUH Palu', 'B.6597.1.1.2011', '2011-08-10', 'Ekonomi Manajemen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(15, '76.01', '1.03.0.00.0.00.01.0000', 'syahruddin', NULL, 'ST.M.AP', '198012062009031001', 'sikkuale', '1980-06-12', NULL, '3', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Tehnik pengairan', NULL, '7601020612800001', NULL, NULL, NULL, NULL, '145104287814000', NULL, 'Jl.Kakatua', '085242788045', 'syahruddin032umi@gmail.com', 'menikah', '154/5/V/2010', '2010-05-05', NULL, 'naurah nur assyifa', '', 'Tarre', 'Baharia', 'Almaidah Nur Z Paraga', 'P 333311', '2011-01-19', NULL, '801206090310010', '2011-01-24', 'AA 04023926', '1970-01-01', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/75/BKDD', '2009-03-17', '', '821.13/518.A/BKDD', '2010-01-03', '', '', '1970-01-01', 'SD N 265 Pinrang', '06 OA oa 0014832', '1993-06-07', '', NULL, 'SMP N Cempa', '06 OA ob 1915796', '1996-05-28', '', NULL, 'SMUN 2 Pare-Pare', '06 Mu 102 0236162', '1999-05-24', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI /MAKASSAR', '074/A.08/FTK.MS/S1-UMI-2004', '2004-08-12', 'Teknik Mesin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(16, '76.01', '1.03.0.00.0.00.01.0000', 'rahmania', NULL, 'ST.MT', '198406172009032002', 'ujung pandang', '1984-06-17', NULL, '3', 'd', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Tehnik penyehatan lingkungan', NULL, '7601025706840005', NULL, NULL, NULL, NULL, '145104394814000', NULL, 'Jl.Budi Utomo', '085241224724', 'neeyh4@gmail.com', 'menikah', '0052/001/V/2017', '2017-03-05', NULL, 'muhammad alvisnanda malik', '7601021804180001', 'Abd Rahman Bangi', 'Maragau Mustamin', 'Abdul Malik,ST', 'P 081661', '2010-03-05', NULL, '840617090320020', '2011-01-27', '', '1904-01-01', NULL, '823.3/506/BKPP', '2024-01-01', 'bupati mamuju utara', '813.3/75/BKDD', '2009-03-17', '', '821.13/518/A/BKDD', '2010-01-03', '', '', '1970-01-01', 'Terbakar', 'Terbakar', '1904-01-01', '', NULL, 'Terbakar', 'Terbakar', '1904-01-01', '', NULL, 'Terbakar', 'Terbakar', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNTAD PALU', '18903/41.06.S1/2008', '2008-07-06', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2025-10-07 00:06:32', 'nabiila', 'nabiila', 0),
(17, '76.01', '1.03.0.00.0.00.01.0000', 'felix datuan', NULL, 'ST', '198407252009031001', 'leatung', '1984-07-25', NULL, '3', 'd', 'kristen', 'pria', 'pnsd2', 'peg_tetap', 'Kasubag keuangan', NULL, '760107250780001', NULL, NULL, NULL, NULL, '799343108814000', NULL, 'Jl.Andi depu', '082292228184', 'datuanfelix@gmail.com', 'menikah', 'AK.976.0012668', '1970-01-01', NULL, 'louis genesaret giring datuan', '', 'Fransiskus Giring Sampe', 'Agustina Barung', 'Stephanie', 'P 081664', '2010-03-05', NULL, '840725090310010', '2011-01-25', '', '1904-01-01', NULL, '823.3/506/BKPP', '2024-01-01', 'bupati mamuju utara', '813.3/75/BKDD', '2009-03-17', '', '821.13/5818.A/BKDD', '2010-01-04', '', '', '1970-01-01', 'SDN  235 Inpres Buntu Salombe', '06 OA oa 0059843', '1996-06-10', '', NULL, 'SMP Swasta Katolik Sangalla', '06 DI 0055250', '1999-05-31', '', NULL, 'SMU Swasta Katolik Makale', '06 Mu 0229179', '2002-06-17', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNHAS /MAKASSAR', '109562-H4-D/12….-211-2007', '2007-06-11', 'Teknik Mesin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2025-10-06 06:13:36', 'nabiila', 'nabiila', 0),
(18, '76.01', '1.03.0.00.0.00.01.0000', 'rajamang', NULL, 'ST', '197507272010011004', 'ujung pandang', '1975-07-27', NULL, '3', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Tehnik pengairan', NULL, '76010227070002', NULL, NULL, NULL, NULL, '582268751814000', NULL, 'Jl.Andi depu', '082291026999', 'bisma.rajab@gmail.com', 'menikah', '164/12/X/2007', '1970-01-01', NULL, 'muh. aqiz mushia;muh. abias akbar;muh. azam mahrut', '', 'H. Abd. Rahman. L', 'Hj. Yasreng', 'Bismawati Mahmud, ST', 'P 454811', '2011-10-05', NULL, '750727100110040', '2011-05-30', 'AA 04023925', '2016-11-22', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/376/BKDD', '2010-02-22', '', '821.13/534/BKDD', '2011-03-31', '', '', '1970-01-01', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI /MAKASSAR', '351/A.08/FTS/SL-UMI/2003', '2004-05-15', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(19, '76.01', '1.03.0.00.0.00.01.0000', 'i ketut suriyana', NULL, 'ST', '198404262011011011', 'gunung sari', '1984-04-26', NULL, '3', 'c', 'hindu', 'pria', 'pnsd2', 'peg_tetap', 'Tehnik pengaiTehnik penyehatan lingkungan', NULL, '7601022604840001', NULL, NULL, NULL, NULL, '159834993814000', NULL, 'Dusun Baturiti', '082314576631', 'suryanaketut975@yahoo.co.id', 'menikah', '7601-KW-13012014-0001', '2013-09-10', NULL, 'febrian dede s', '', 'I Wayan Kartu', 'Ni Wayan Gadri', 'Kadek Dewi Purnawati, ST', 'Q 206725', '2013-02-01', NULL, '840426110110110', '2012-04-18', '', '1904-01-01', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/475/BKDD', '2011-03-21', '', '821.13/533/BKDD', '2012-03-30', '', '', '1970-01-01', 'SD Inpres Baras VI', '06 Dd 0113793', '1998-06-10', '', NULL, 'SLTPN 2 Bambalomotu', '06 DI 1669187', '2001-06-28', '', NULL, 'SMA N 4 Palu', 'DN-18 Mu 0378639', '2004-06-14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNTAD PALU', '19816/41.06.S1/2009', '2009-02-28', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(20, '76.01', '1.03.0.00.0.00.01.0000', 'muh. hirman madjid', NULL, 'ST', '197511282011011002', 'ujung pandang', '1975-11-28', NULL, '3', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Tehnik tata bangunan dan perumahan', NULL, '7601112811750001', NULL, NULL, NULL, NULL, '494904717814000', NULL, 'Jl.Trans Sulawesi', '08124284246', 'hirman.majid75@gmail.com', 'menikah', '384/44/P/2008', '2008-11-05', NULL, '', '', 'Abd. Madjid Saleh', 'Hj. Sapatia Said', 'Rosdiana Susie', 'Q 206764', '2013-02-01', NULL, '751128110110020', '2012-08-18', 'AA 04023922', '2016-11-22', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/475/BKDD', '2011-03-21', '', '821.13/533/BKDD', '2012-03-30', '', '', '1970-01-01', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Unhas Makassar', '44484-J.04-D/E/014-511-2000', '2000-06-03', 'Teknik Arsitekur', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(21, '76.01', '1.03.0.00.0.00.01.0000', 'hasmia', NULL, 'S.Sos', '198712042011012008', 'labean', '1987-04-12', NULL, '3', 'c', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '7601024912870004', NULL, NULL, NULL, NULL, '161690136814000', NULL, 'Jl Manggis', '085341152805', 'hasmiahalide@gmail.com', 'menikah', '441/04/IX/2012', '2012-02-09', NULL, 'nirina rafifah risal', '', 'Halide', 'Masse', 'Risal', 'Q 206790', '2013-02-01', NULL, '871204110120080', '2012-04-17', '', '1904-01-01', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/475/BKDD', '2011-03-21', '', '821.13/533/BKDD', '2012-03-30', '', '', '1970-01-01', 'SDN Labean', '24 Dd 0011674', '2000-06-23', '', NULL, 'SLTPN 1 Balaesang', '18 DI 2080121', '2003-06-16', '', NULL, 'Madrash Aliyah 1 Palu', 'MA 0125193', '2006-06-19', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNTAD Palu', '23033/41.02.1/S1/2010', '2010-02-24', 'ILMU SOSIAL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(22, '76.01', '1.03.0.00.0.00.01.0000', 'suriyana', NULL, 'ST', '198001282012122003', 'sidrap', '1980-01-28', NULL, '3', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Tehnik tata bangunan dan perumahan', NULL, '731409680180001', NULL, NULL, NULL, NULL, '550792998814000', NULL, 'Jl.Imam bonjol', '081343973223085299275556', 'suriyanadpu@gmail.com', 'menikah', '0061/008/IV/2016', '2016-09-04', NULL, 'rafi', '', 'Ansar Tappa', 'Namri', 'Mu&#39;az,SE', 'A 04001277', '2015-06-04', NULL, '800128121220030', '2013-06-19', '', '1904-01-01', NULL, '823.3/626/BKPP', '2024-01-01', 'bupati mamuju utara', '813.3/014/BKDD', '2013-03-04', '', '821.13/707/BKDD', '2014-12-05', '', '', '2017-04-13', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI /MAKASSAR', '341/A.08/FTS/SL-UMI/2003', '2004-04-27', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(23, '76.01', '1.03.0.00.0.00.01.0000', 'hamka', NULL, 'SH', '198410152010011003', 'salubiro', '1984-10-15', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pembina jasa konstruksi', NULL, '7601021510840004', NULL, NULL, NULL, NULL, '594708885814000', NULL, 'Jl.Fatmawati', '081343525624', 'caspersatu@gmail.com', 'menikah', '299/69/IV/2010', '2010-04-25', NULL, '', '', 'Hammaruddin', 'Sitti Farah', 'Rina Ariani', 'P 454867', '2011-10-05', NULL, '841015100110030', '2011-05-30', 'AA 04023911', '2016-11-22', NULL, '823.3/72/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/516/BKDD', '2009-12-14', '', '821.12/533/BKDD', '2011-12-31', '', '', '1970-01-01', 'SDN Salubiro', '06 OA oa 0110216', '1997-06-10', '', NULL, 'MTSN Bunanga Mamuju', 'E.IV/t/MTs.012/185/2001', '2001-06-28', '', NULL, 'SMAN 1 Budongbudong', 'DN-19 Mu 0239504', '2004-06-14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNISA Palu', 'B.8803.4.1.2014', '2014-10-16', 'HUKUM', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(24, '76.01', '1.03.0.00.0.00.01.0000', 'evi yanti', NULL, 'ST', '197701272008042001', 'palu', '1977-01-27', NULL, '3', 'd', 'kristen', 'wanita', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '', NULL, NULL, NULL, NULL, '780215885814000', NULL, '', '085256956400', 'evi_yanti77@yahoo.co.id', 'menikah', '801 000 1489', '2011-08-09', NULL, 'davine gracio danun  ;gavriel alharon danun ;chantika keisha arung', '', 'Julius Tandi', 'Esther Rante', 'Inri Danun, S.Pd', 'N 458109', '2009-03-09', NULL, 'P30007197', '2010-04-02', 'BA 04014818', '2016-06-30', NULL, '823.3/1526/BKDD', '2024-01-01', '', '813.3/79/BKD', '2008-05-30', '', '821.13/83/BKDD', '2009-03-23', '', '', '2015-11-20', 'SDN Inpres Bumi Sagu II', '24 OA oa 0001581', '1989-06-14', '', NULL, 'SMPN 4 Palu', '24 OA ob 1565627', '1992-06-04', '', NULL, 'SMAN 1 Palu', '24 OB oe 0011439', '1995-05-22', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNTAD Palu', '20026 03384', '2002-09-18', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(25, '76.01', '1.03.0.00.0.00.01.0000', 'zakaria', NULL, 'ST', '197410142009031000', 'luwu', '1974-10-14', NULL, '3', 'd', 'kristen', 'pria', 'pnsd2', 'peg_tetap', 'Analis Jalan dan Jembatan', NULL, '', NULL, NULL, NULL, NULL, '151436078814000', NULL, '', '82189730016', 'zakariast115@gmail.com', 'menikah', 'CSK 1027903', '2000-04-10', NULL, 'frichilya intan lawa padang ;erghy dwi putra lawa padang ;chirany austriesta lawa padang', '', 'Daniel Pangala', 'Marthina Rante Bungin', 'Yayanti Malino', 'P 333317', '2011-01-19', NULL, '197410142009031001', '2011-01-27', '', '1904-01-01', NULL, '', '2024-01-01', '', '813.3/75/BKDD', '2009-03-17', '', '821.13/518.A/BKDD', '2010-01-03', '', '', '2018-01-08', 'SD N 223 Wawondula', 'OA oa 0075978', '1986-05-21', '', NULL, 'SMP N Wawondula', '06 OB ob 0451031', '1989-06-01', '', NULL, 'SMA N Sanggalangi', '06 OB og 0128279', '1992-06-12', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UKIPM Makassar', '9776.01.01042000', '2000-01-04', 'Teknik Mesin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(26, '76.01', '1.03.0.00.0.00.01.0000', 'sulsia husen', NULL, 'SE', '198209172010012009', 'bente', '1982-09-17', NULL, '3', 'c', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Analis Pengelolaan SDA', NULL, '7601045709820002', NULL, NULL, NULL, NULL, '582268769814000', NULL, 'Jl.Rusa', '081359302095', 'sulsia_husen82@yahoo.co.id', 'menikah', '15/15/1/2007', '2007-01-22', NULL, 'charisa rezqi zabrina', '', 'Husen Buse', 'ST.Saleha', 'Irham Tapala', 'P 454784', '2011-10-05', NULL, '820917100120090', '2011-05-30', '', '1904-01-01', NULL, '823.3/202/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.3/376/BKDD', '2010-02-22', '', '821.13/534/BKDD', '2011-03-31', '', '', '1970-01-01', 'SDN Bente', '24 OA oa 0026348', '1995-05-27', '', NULL, 'SD N 1 Bungku Tengah', '24 DI 0004213', '1998-05-25', '', NULL, 'SMU 3 Luwuk', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'STIE PANCA BAKTI Palu', '347/Akr-B/XI/2005', '2005-11-21', 'EKONOMI AKUNTANSI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(27, '76.01', '1.03.0.00.0.00.01.0000', 'alwi mansyur', NULL, 'A.Md', '198007112006041010', 'mamuju', '1980-07-11', NULL, '3', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Verifikator Keuangan', NULL, '', NULL, NULL, NULL, NULL, '674093125814000', NULL, 'Jl. Tuna', '081288866665', 'mail.id.idn@mail.com', 'menikah', '1438/63/XI/2008', '2008-10-26', NULL, 'najwa nabila ;inayah nadila', '', 'Mansyur', 'Kamaria', 'Arlinda achmad', 'p 454901', '2011-10-05', NULL, '', '1904-01-01', 'aa 04023588', '2016-11-22', NULL, '823.3/778/BKDD', '2024-01-01', 'bupati mamuju utara', '813.2/190/BKD', '2006-01-11', '', '821.12/41/BKD', '2008-04-03', '', '', '2016-05-16', 'SDN Inpres Tampotora', '06 OA oa 0108389', '1993-06-07', '', NULL, 'SMP N 1 Mamuju', '06 OA ob 1942480', '1996-05-28', '', NULL, 'SMKN 2 Ujungpandang', '06 Mk 210 0014450', '1999-05-24', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Samarinda', 'P.3/N20.R/SP-D3/2004', '2004-11-10', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(28, '76.01', '1.03.0.00.0.00.01.0000', 'i made sulawa', NULL, 'SE', '197307132009031001', 'bali', '1973-07-13', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Teknik Penyehatan Lingkungan Ahli Muda', NULL, '7601021307730001', NULL, NULL, NULL, NULL, '889771416814000', NULL, 'Dusun Bukit sari', '085231669912', 'abangsulawa@gmail.com', 'menikah', '', '1995-12-25', NULL, 'ni wayan sulasmini;imade adyirawan;artha wedha', '', 'I Made Dimbut', 'Ni nyoman Munkir', 'Ninyoman Jamin', 'P 081668', '2010-03-05', NULL, '', '1904-01-01', 'AA 04023931', '2016-11-22', NULL, '823.3/1077/BKPPD', '2024-01-01', '', '813.2/76/BKDD', '2009-03-17', '', '821.12/519/BKDD', '2010-01-03', '', '', '2017-07-09', 'SD N Inpres Salumoni', '06 OA oa 0123632', '1987-06-02', '', NULL, 'SMPN Bambalomotu', '06 OB ob 0475088', '1990-06-02', '', NULL, 'STMN Palu', '24  OB op 0001502', '1993-05-28', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNISMUH Palu', 'B7345.1.1.2012', '2012-09-24', 'Ekonomi Manajemen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(29, '76.01', '1.03.0.00.0.00.01.0000', 'kamaruddin', NULL, 'SE', '197009302012121003', 'gowa', '1974-06-03', NULL, '3', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Penyusun Rencana Tata Ruang', NULL, '', NULL, NULL, NULL, NULL, '550863641814000', NULL, '', '081341295554', 'komarammar1070@gmail.com', 'menikah', '396/33/VII/1998', '1998-09-07', NULL, '', '', 'H. Abd. Korim DG Nuru', 'H.Dahlia DG. Ngiji', 'Roslina Andi Pangeran DG. Malindu', 'A 04001242', '2015-04-04', NULL, '700930121210030', '2013-06-17', 'AA 04023605', '2016-11-22', NULL, '823.3/311/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/150/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SDN Inpres Sungguminasa', '06 OA oa 099913', '1985-05-31', '', NULL, 'SMPN 2 Sungguminasa', '06 OB ob 0392212', '1988-06-11', '', NULL, 'SMA N 159 Sungguminasa', '06 OB og 0023187', '1991-06-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNISA Palu', '946-UA/A.7-FE/A/2012', '2012-07-05', 'Ekonomi Manajemen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(30, '76.01', '1.03.0.00.0.00.01.0000', 'rasdiana', NULL, 'ST', '197512312012122005', 'pinrang', '1975-12-31', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Teknik Penyehatan Lingkungan Ahli Muda', NULL, '760106711275006', NULL, NULL, NULL, NULL, '550793020814000', NULL, 'Dusun Mekar sari', '085256443655', 'anaidamindj@gmail.com', 'menikah', '', '1904-01-01', NULL, '', '', 'H. Muh Amin', 'Hj. Jawasa', '', 'A 04001269', '2015-06-04', NULL, '751231121220050', '2013-06-19', '', '1904-01-01', NULL, '823.3/626/BKPP', '2024-01-01', 'bupati mamuju utara', '813.3/045/BKDD', '2013-03-04', '', '821.13/598/BKDD', '2014-03-28', '', '', '2017-04-13', 'SDN No.127 Pinrang', '06 OA oa 0015643', '1988-06-24', '', NULL, 'SMP Swasta PGRI Mattiro Deceng', '06 OA ob 0425964', '1991-08-06', '', NULL, 'STMN Pare', '06 OB on 0021296', '1994-05-23', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '45 MAKASSAR', '221-45/087-04/A/2000', '2000-10-10', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(31, '76.01', '1.03.0.00.0.00.01.0000', 'santi', NULL, 'SE', '198509192010012002', 'parepare', '1985-09-19', NULL, '3', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601025909850001', NULL, NULL, NULL, NULL, '151438082814000', NULL, 'Jl.Urip Sumiharjo psky', '081355561176', 'santi_pu@yahoo.com', 'menikah', '', '1904-01-01', NULL, 'musdhalifa;muh. fachry ismail;mufidah salsabila ismail', '', 'Abdul Latif', 'Sumarni', 'Muhammad Ismail Ilyas', 'P 454757', '2011-10-05', NULL, '850919100120020', '2011-05-30', 'BA 04023537', '2016-10-18', NULL, '823.3/72/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/516/BKDD', '2009-01-14', '', '821.12/533/BKDD', '2011-03-31', '', '', '1970-01-01', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNISA Palu', '110-A/A.    E/A/2014', '2014-06-30', 'Ekonomi Manajemen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(32, '76.01', '1.03.0.00.0.00.01.0000', 'fuady kahar', NULL, 'SH', '198308082012121002', 'bulukumba', '1983-08-08', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Kasubag Keuangan', NULL, '7371130808830008', NULL, NULL, NULL, NULL, '550869945814000', NULL, 'Jl.Muh.hatta', '085241234646', 'adyka.fk@gmail.com', 'menikah', '19/06/II/2014', '2014-02-02', NULL, 'mutiara rahadhani ;airha islamadina ;airyn putri zulaikha', '', 'Kahar Zainuddin', 'Syuriati Yasin', 'Dewi Nolfianti', 'A 04001220', '2015-06-04', NULL, '830808121210020', '2013-06-17', 'AA 04023920', '2016-11-22', NULL, '823.3/311/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/118/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SD Inpres Tidung 1', '06 OA oa 0132038', '1996-06-10', '', NULL, 'SLTP Swasta Mitrasyah Diakui', '06 DI 0079472', '1999-05-31', '', NULL, 'SMUN 1  Palangka Raya', '25 Mu 0559795', '2002-06-14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNISMUH Palu', 'B.4279.4.1.2008', '2008-07-18', 'HUKUM', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(33, '76.01', '1.03.0.00.0.00.01.0000', 'andi jamil zainal', NULL, 'ST', '197504022014091001', 'bantaeng', '1975-02-04', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pemeriksa Jalan dan Jembatan', NULL, '7601020204750005', NULL, NULL, NULL, NULL, '726856305814000', NULL, 'Jl.Sultan Hasanuddin', '081342268999', 'andijamil35@gmail.com', 'menikah', '', '2003-01-06', NULL, 'a. keisya aulia;a. naura kaila', '', 'Zainal Abidin', 'Asnani Lakki', 'Suryanti Sabir', '', '1904-01-01', NULL, '7304022014091001', '2015-06-30', 'AA 04023581', '2016-11-22', NULL, '823.3/668/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.3/061/III-15/BKDD', '2015-03-23', '', '821.13/1261/BKDD', '2016-06-29', '', '', '2018-01-08', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMI Makassar', '368/FTS/SL-UMI/2001', '2002-09-16', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(34, '76.01', '1.03.0.00.0.00.01.0000', 'yulita veryanti siahaan', NULL, 'SE', '197510032014102002', 'medan', '1975-03-10', NULL, '3', 'b', 'kristen', 'wanita', 'pnsd2', 'peg_tetap', 'Pemeriksa Jalan dan Jembatan', NULL, '7601024310750003', NULL, NULL, NULL, NULL, '726190614814000', NULL, 'Jl.Trans Sulawesi', '085241115603', '123yuliyulita321@gmail.com', 'menikah', '1008/UMUM/2005', '2005-10-12', NULL, 'obed martin d.p. napitupulu;queen maria m.t. napitupulu;togi orlando l.r. napitupulu', '', 'Drs. O. Ricardo Siahaan', 'Surti Nurbaya Pardede', 'Gulopong Napitupulu, ST', '', '1904-01-01', NULL, '197510032014102062', '2015-06-08', 'BA 04025323', '2016-10-18', NULL, '823.3/668/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.3/043/III-15/BKDD', '2015-03-23', '', '821.13/1262/BKDD', '2016-06-29', '', '', '2018-01-08', 'SDN 5 Palu', '24 OA oa 0028813', '1988-06-17', '', NULL, 'SMPN 1 Palu', '24 OA ob 0173188', '1991-06-06', '', NULL, 'SMA N 1 Palu', '24 OB oe 0122344', '1994-05-30', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNTAD', '10223/41.03.S1/2000', '2000-09-10', 'Ekonomi Pembangunan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(35, '76.01', '1.03.0.00.0.00.01.0000', 'as&#39;at', NULL, 'ST', '197109072014091001', 'donggala', '1971-07-09', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601020709710002', NULL, NULL, NULL, NULL, '705156651814000', NULL, 'Jl.Trans Sulawesi', '085342882007', 'asat44245@gmail.com', 'menikah', '140/16/X/2000', '2000-10-27', NULL, 'sabrina arfanindya;mitha safira;riza fatmahira;fikri dan iswara', '', 'Moh.Syam', 'Salmiha', 'Lasminiwati', '', '1904-01-01', NULL, '197109072014091001', '2015-01-07', 'AA 04014536', '2016-06-20', NULL, '823.3/668/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.3/004/III-15/BKDD', '2015-03-23', '', '821.13/1261/BKDD', '2016-06-29', '', '', '2018-01-08', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UGM', '30053/HYN-WBS', '2000-08-19', 'Teknik Arsitek', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(36, '76.01', '1.03.0.00.0.00.01.0000', 'hertasming', NULL, 'SE', '197106222014091001', 'wajo', '1971-06-22', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Perencanaan dan Program', NULL, '7371122206710002', NULL, NULL, NULL, NULL, '784757619808000', NULL, 'Jl.Ir Soekarno', '08114499688', 'hery.ning22@g.mail.com', 'menikah', '311/22/XII/2012', '2012-06-12', NULL, 'ariqah khaerunnisa;nisrina fatina;aidah dzakiyah alfatunnisa;nailah', '', 'H. Pate', 'Hj.Maddanreng', 'Fatmawati', '', '1904-01-01', NULL, '197106222014091001', '1904-01-01', 'AA 04014310', '2016-06-20', NULL, '823.3/668/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.3/006/III-15/BKDD', '2015-03-23', '', '821.13/1261/BKDD', '2016-06-29', '', '', '2018-01-08', 'SD No. 190 Kaluku', '06 OA oa 081455', '1984-05-21', '', NULL, 'SMP N Siwa', '06 OB ob 0365829', '1987-09-06', '', NULL, 'SMA N 9 Ujung Pandang', '06 OC oh 0485551', '1990-05-26', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UNHAS', '37351-039-01/047-151-98', '1998-03-13', 'Ekonomi Pembangunan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(37, '76.01', '1.03.0.00.0.00.01.0000', 'awan muliawan', NULL, 'ST', '199108152015031006', 'mamuju', '1991-08-15', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Analis Perencanaan', NULL, '', NULL, NULL, NULL, NULL, '731805867814000', NULL, 'Jl.PDAM', '085298889599', 'awanplanologi09@gmail.com', 'menikah', '', '1904-01-01', NULL, '', '', 'Sapruddin', 'Hj.nurjannah', '', '', '1904-01-01', NULL, '910815150310080', '2015-07-31', '', '1904-01-01', NULL, '823.3/72/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.3/016/V-15/BKDD', '2015-05-27', '', '', '1904-01-01', '', '', '1970-01-01', 'SDN Inpres Binaga III Mamuju', '06 Dd 0150045', '2003-06-28', '', NULL, 'SMP N 2 Mamuju', 'DN.31 DI 2583988', '2006-06-26', '', NULL, 'SMA N 1 Mamuju', 'DN-32 Ma0000806', '2009-06-15', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UIN ALAUDDIN MAKASSAR', 'UN.6/ST/PP.01.1/813-PWK/2013', '1904-01-01', 'Teknik Perencanaan wilayah dan kota', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(38, '76.01', '1.03.0.00.0.00.01.0000', 'tahmil', NULL, 'SH', '197804012006041024', 'pasangkayu', '1978-01-04', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601020104780002', NULL, NULL, NULL, NULL, '674093158814000', NULL, 'Jl.Andi depu', '082188537171', 'tahmil78egos@gmail.com', 'menikah', '74/03/03/2007', '2007-07-03', NULL, 'kaizar devan mirza;arzanq reza', '', 'Abd. Haid', 'H. Murni', 'Kusbaena', 'n 306458', '2008-10-10', NULL, 'P 3000250900', '2010-01-22', 'aa 04023945', '2016-11-22', NULL, '823.3/1077/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/435/BKD', '2006-12-29', '', '821.12/41/BKD', '2008-04-04', '', '', '2017-07-09', 'SDN Pasangkayu', '06 OA 0141103', '1991-06-10', '', NULL, 'SMP N Pasangkayu', '06 OA ob 1628078', '1994-06-06', '', NULL, 'SMUN 1 Pasangkayu', '06 OB of 103 269722', '1997-05-31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pasangkayu', '123/KEP/106/HK/96', '1997-05-31', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(39, '76.01', '1.03.0.00.0.00.01.0000', 'muhammad dirgantara', NULL, 'ST', '198502112015031004', 'parigi moutong', '1985-11-02', NULL, '3', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Penata Ruang Ahli Muda', NULL, '7605011102850001', NULL, NULL, NULL, NULL, '702685371813000', NULL, 'Jl.Muh.hatta', '082259974616', 'muhammaddirgantara85@gmail.com', 'menikah', '0431/087/IX/2017', '1970-01-01', NULL, 'izzan hanif alfarizqi', '', 'Cecep gunawan nur', 'kususiah', 'ika pramesti.S.km', '', '1904-01-01', NULL, '850211150310040', '1904-01-01', '', '1904-01-01', NULL, '823.3/72/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.3/017/V-15/BKDD', '1970-01-01', 'Bupati Mamuju Utara', '821.13/1669/BKDD', '1970-01-01', 'Bupati Mamuju Utara', '', '1970-01-01', 'SDN NEGERI 4 Pontianak Barat', '14 Oaoa 0001798', '1970-01-01', 'Pontianak Barat kotamadya Pontianak', NULL, 'SMP NEG 1 Watulimo Kab.Trenggalek', '04 DI 0262658', '1970-01-01', 'Watulimo', NULL, 'SMU Neg.1 Palu', '195/124.5.2/SMU.1/NN/2002', '1970-01-01', 'Palu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Universitas Tadulako', '0110/41.06.S1/2013', '1970-01-01', 'Tehnik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(40, '76.01', '1.03.0.00.0.00.01.0000', 'mawaddah', NULL, 'ST', '198406072019032008', 'palu', '1984-07-06', NULL, '3', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Sekretaris Dinas', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '', '081218218590', '1@gmail.com', 'menikah', '', '1904-01-01', NULL, '', '', '', '', '', '', '1904-01-01', NULL, '', '1904-01-01', '', '1904-01-01', NULL, '', '2024-01-01', '', '821.13/71/BKPPD', '1970-01-01', 'Bupati Pasangkayu', '', '1904-01-01', '', '', '1904-01-01', 'SD Inpres Bumi bahari kec.Palu barat', '24 OAoa0018274', '1996-06-06', 'Palu', NULL, 'SMP Neg.1 Palu Timur', '24 DI 0012001', '1970-01-01', 'Palu', NULL, 'SMU Neg.4 Palu', '225/R.01/02', '1970-01-01', 'Palu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Universitas Tadulako', '18051/41.06.S1/2007', '1970-01-01', 'Tehnik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(41, '76.01', '1.03.0.00.0.00.01.0000', 'vilma erhid salla', NULL, 'ST', '198504172019032010', 'ujung pandang', '1985-04-17', NULL, '3', 'a', 'kristen', 'wanita', 'pnsd2', 'peg_tetap', 'Pemeriksa Jalan dan Jembatan', NULL, '7371026704860001', NULL, NULL, NULL, NULL, '758952543804000', NULL, 'Jl.Kakatua makassar', '081218218590', '1@gmail.com', 'lajang', '', '1904-01-01', NULL, '', '', 'Drs.Luther Salla Boreang', 'Maria Sumiati.S.Kep', '', '', '1904-01-01', NULL, '', '1904-01-01', '', '1904-01-01', NULL, '', '2024-01-01', '', '821.13/70/BKPPD', '2019-03-29', 'Bupati Pasangkayu', '', '1904-01-01', '', '', '1904-01-01', 'SD Neg.Mangkura 1 Kec.U.pandang', '06 OA oa 0121402', '1997-10-06', 'Ujung Pandang', NULL, 'SLTP Swasta Frater Disamakam', '06 DI 1613332', '1970-01-01', 'Makassar', NULL, 'SMU Neg.2 Makassar', 'DN.19 MU 0343913', '1970-01-01', 'Makassar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Unhas Makassar', '124055-H4-D/14541-511-2010', '1970-01-01', 'Arsitektur', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0);
INSERT INTO `db_asn_pemda_neo` (`id`, `kd_wilayah`, `kd_opd`, `nama`, `gelar_depan`, `gelar`, `nip`, `t4_lahir`, `tgl_lahir`, `file_akta_lahir`, `golongan`, `ruang`, `agama`, `kelamin`, `jenis_kepeg`, `status_kepeg`, `jabatan`, `id_jabatan`, `no_ktp`, `file_ktp`, `no_kk`, `tgl_kk`, `file_kk`, `npwp`, `file_npwp`, `alamat`, `kontak_person`, `email`, `status`, `no_buku_nikah`, `tgl_nikah`, `file_buku_nikah`, `nama_anak`, `nik_anak`, `nama_ayah`, `nama_ibu`, `nama_pasangan`, `no_karpeg`, `tgl_karpeg`, `file_karpeg`, `no_taspen`, `tgl_taspen`, `no_karsi_karsu`, `tgl_karsi_karsu`, `file_karsi_karsu`, `nmr_sk_terakhir`, `tgl_tmt_akhir`, `pj_sk_terakhir`, `nmr_sk_cpns`, `tgl_sk_cpns`, `pj_sk_cpns`, `nmr_sk_pns`, `tgl_sk_pns`, `pj_sk_pns`, `sk_pangkat_terakhir`, `tgl_sk_terakhir`, `pend_sekolah_sd`, `pend_ijasah_sd`, `pend_tgl_tmt_sd`, `pend_t4_sd`, `pend_file_sd`, `pend_sekolah_smp`, `pend_ijasah_smp`, `pend_tgl_tmt_smp`, `pend_t4_smp`, `pend_file_smp`, `pend_sekolah_smu`, `pend_ijasah_smu`, `pend_tgl_tmt_smu`, `pend_t4_smu`, `pend_file_smu`, `pend_sekolah_s1`, `pend_ijasah_s1`, `pend_tgl_tmt_s1`, `pend_t4_s1`, `pend_file_s1`, `pend_sekolah_s2`, `pend_ijasah_s2`, `pend_tgl_tmt_s2`, `pend_t4_s2`, `pend_file_s2`, `pend_sekolah_s3`, `pend_ijasah_s3`, `pend_tgl_tmt_s3`, `pend_t4_s3`, `pend_file_s3`, `pend_sekolah_akhir`, `pend_ijasah_akhir`, `pend_tgl_tmt_akhir`, `pend_t4_akhir`, `pend_file_akhir`, `file_photo`, `gapok`, `aktif`, `no_urut`, `unit_kerja`, `no_rekening`, `nama_bank`, `urutan`, `kelompok`, `suka`, `follow`, `keterangan`, `disable`, `tgl_insert`, `tgl_update`, `username_insert`, `username_update`, `is_deleted`) VALUES
(42, '76.01', '1.03.0.00.0.00.01.0000', 'abdul haris rustam', NULL, 'A.Md', '199908212019031010', 'ujung pandang', '1988-08-21', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengawas Jalan dan Jembatan', NULL, '7371012106880003', NULL, NULL, NULL, NULL, '816691372804000', NULL, 'JL……', '085255088455', '1@gmail.com', 'menikah', '0041/010/III/2015', '1970-01-01', NULL, '', '', '', '', '', '', '1904-01-01', NULL, '', '1904-01-01', '', '1904-01-01', NULL, '', '2024-01-01', '', '821.12/10/BKPPD', '1970-01-01', 'Bupati Pasangkayu', '', '1904-01-01', '', '', '1904-01-01', 'SD', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Politeknik Negri Ujung Pandang', '9354/PL10/30012/2011', '2011-07-11', 'Diploma Tiga Politehnik', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(43, '76.01', '1.03.0.00.0.00.01.0000', 'wawan prasetya karbayah', NULL, 'ST', '199201272019031000', 'tarakan', '1992-01-27', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengawas Jalan dan Jembatan', NULL, '7271012701920003', NULL, NULL, NULL, NULL, '752220026831000', NULL, 'Jl Andi Bandaco', '085394308437', '1@gmail.com', 'lajang', '', '1904-01-01', NULL, '', '', 'Abdul kadir latief', 'Arbayah', '', '', '1904-01-01', NULL, '', '1904-01-01', '', '1904-01-01', NULL, '', '2024-01-01', '', '821.13/93/BKPPD', '1970-01-01', 'Bupati Pasangkayu', '', '1904-01-01', '', '', '1904-01-01', 'SD Neg.118 Patampanua', '06 Dd 0116314', '1970-01-01', 'Patampanua,Pinrang', NULL, 'SMP Neg.2 Pinrang', 'DN.-19 DI 2164228', '1970-01-01', 'Pinrang', NULL, 'SMA Neg.1 Pasangkayu', 'DN-32 Na 0001527', '1970-01-01', 'Mamuju Utara', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Universitas Tadulako', '0016/41.06.S1/2015', '2015-04-03', 'Teknik Sipil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(44, '76.01', '1.03.0.00.0.00.01.0000', 'syamsuddin sangkala', NULL, '', '197201221995041001', 'ujung pandang', '1972-01-22', NULL, '2', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601040202720003', NULL, NULL, NULL, NULL, '151436748814000', NULL, 'Jl.Bakti Husada', '081341611699', 'udincaca3@gmail.com', 'menikah', '272/40/XII/1993', '1993-12-25', NULL, 'hasmira syam;rahmawati sari;sahabuddin;putri salsabila sam  s', '', 'Sangkala DG. Situju', 'Hasati', 'Nasuhatul Mu&#39;minah', 'G 258939', '1997-03-15', NULL, '132113712', '1997-03-13', 'AA 04023595', '2016-11-22', NULL, '823.2/206/BKDD', '2024-01-01', 'bupati mamuju utara', 'GR.813.1-28', '1995-03-30', '', '823.2/279/BKDD', '2014-03-28', '', '', '2014-03-28', 'SDN No.109 Ujung Pandang', 'OA oa 121384', '1984-05-21', '', NULL, '', '', '1904-01-01', '', NULL, 'Paket C', '33PC0500106', '2008-01-07', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sarudu', '33PC0500106', '2008-07-01', 'SMA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(45, '76.01', '1.03.0.00.0.00.01.0000', 'i made tamayasa', NULL, '', '196804122007011074', 'antapan', '1968-12-04', NULL, '2', 'd', 'hindu', 'pria', 'pnsd2', 'peg_tetap', 'Kepala UPTD Pengelolaan Limbah Domestik', NULL, '7601021204680004', NULL, NULL, NULL, NULL, '145104378814000', NULL, 'Jl.Pendididkan', '082292050166', 'tamayasaimade@gmail.com', 'menikah', '42/PHDI/DB/2009', '2009-06-23', NULL, 'i made andreas anggara;ni putu maylina;putu alif', '', 'I Made merta', 'Ni ketut kati', 'Sunarsi', 'N 460554', '2009-11-09', NULL, '', '1904-01-01', '', '1904-01-01', NULL, '823.2/70/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/256/BKD', '2007-12-12', '', '821.12/355/BKDD', '2008-12-31', '', '', '1970-01-01', 'SDN No.2 Antapan', '19 OA oa 14290', '1982-05-24', '', NULL, 'SMP Swasta Yudistira', '19 OB ob 0506725', '1985-05-11', '', NULL, 'STM Swasta Nasional', '19 OC ou 0007863', '1988-05-13', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Denpasar', '86/1.19/Kep/1.07', '1988-05-13', 'Bangunan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(46, '76.01', '1.03.0.00.0.00.01.0000', 'rasmi lumai', NULL, '', '198004112007011015', 'tombong', '1980-11-04', NULL, '2', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601021104800004', NULL, NULL, NULL, NULL, '470535402814000', NULL, 'Tikke,', '085241454868', 'rasmi.lumai@yahoo.com', 'menikah', '521,12,III,2007', '2007-05-15', NULL, 'aisyah lumai;sofiyah lumai;zubair lumai', '', 'Rustam', 'Ramlah', 'Hanalia, S.Pd', 'N 460555', '2009-11-09', NULL, 'P 3000502500', '1904-01-01', 'AA 04023909', '2016-11-22', NULL, '823.2/70/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/256/BKD', '2007-12-12', '', '821.12/355/BKDD', '2008-12-31', '', '', '2019-02-15', 'SDN No. 93 Tombang', '06 OA oa 0037483', '1992-05-20', '', NULL, 'SMP Swasta Diakui Harapan Keraten', '06 OA ob 0154245', '1995-05-29', '', NULL, 'SMK Dewantara Ulama Diakui Palopo', '06 Mk 226 041211', '1998-05-22', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'palopo', '06 MK 226 041211', '1998-05-22', 'Automotif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(47, '76.01', '1.03.0.00.0.00.01.0000', 'anzar', NULL, '', '198204092006041012', 'palu', '1982-09-04', NULL, '2', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601020904820003', NULL, NULL, NULL, NULL, '674093166814000', NULL, 'Jl. Andi bandaco', '082293277470', 'ansarsaputra82@gmail.com', 'menikah', '247,18,XII,2008', '2008-12-27', NULL, 'nur afiza indi', '', 'Am Saputra', 'Asmawartin', 'Delvianti', 'N 331798', '2009-02-17', NULL, '', '1904-01-01', '', '1904-01-01', NULL, '823.2/1020/BKDD', '2024-01-01', 'bupati mamuju utara', '813.2/402/BKD', '2006-12-29', '', '821.12/40/BKD', '2008-04-03', '', '', '2014-10-20', 'SDN Nunu', '06 OA oa 0105823', '1993-06-07', '', NULL, 'SMP N 2 Pasangkayu', '06 OA ob 1943358', '1996-06-07', '', NULL, 'SMKN 3 Palu', '24 Mk 226 0079550', '1999-05-24', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'palu', '24 MK 226 0079550', '1999-05-24', 'Automotif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(48, '76.01', '1.03.0.00.0.00.01.0000', 'munawir z', NULL, 'SH', '198305022007011008', 'pinrang', '1983-02-05', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Analis Pengamanan Lingkungan', NULL, '7601020205830001', NULL, NULL, NULL, NULL, '145104345814000', NULL, 'Perum btn griya matra', '081343714950', 'thitabid@gmail.com', 'menikah', '63,13,IV,2008', '2008-04-26', NULL, 'qanitah;m. abidzal;m. afif', '', 'Zainuddin', 'ST. Aminah', 'Evi', 'N 460552', '2009-11-09', NULL, 'P 30005655', '2010-03-02', 'AA 04023586', '2016-11-22', NULL, '823.3/1077/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/63 A/BKD', '2008-07-04', '', '821.12/355/BKDD', '2008-12-31', '', '', '2017-07-09', '', '', '1904-01-01', '', NULL, '', '', '1904-01-01', '', NULL, 'SMK', '06 Mk 0508496', '2002-06-18', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UMP', 'B.9937.4.1.2015', '2015-11-28', 'Hukum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(49, '76.01', '1.03.0.00.0.00.01.0000', 'budi rahmadi basri', NULL, '', '197905062009021008', 'makassar', '1979-06-05', NULL, '2', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601020605790001', NULL, NULL, NULL, NULL, '547068940814000', NULL, 'Pasangkayu', '081245231196', 'budirahmadibasri@gmail.com', 'menikah', '71,07,VI,2009', '2009-06-21', NULL, 'muh.syaril budi agung;khalisa zahra almaira', '', 'Basri yunus-', 'Hj.Rahmatiah', 'Nurlinda', 'P 636304', '2011-10-31', NULL, '19/905062009021008', '2010-08-03', 'AA 04023908', '2016-11-22', NULL, '823.2/628/BKPP', '2024-01-01', 'bupati mamuju utara', '813.2/1102-CP/BKD/2009', '2009-01-03', '', '821.2/431/PNS/BKD/2011', '2011-01-31', '', '', '1970-01-01', 'SD Sekolah Luar Biasa/ SLB-C', '24 OA oa 0026431', '1994-06-03', '', NULL, 'SLTP N 1 Pamboang', '06 OA oe 0055983', '1997-06-05', '', NULL, 'SMU Swasta Swadaya Palu', '24 Mu 0569798', '2000-06-16', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', '24 MU 0569798', '2000-06-16', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(50, '76.01', '1.03.0.00.0.00.01.0000', 'asham', NULL, '', '197807012010011002', 'guntarano', '1978-01-07', NULL, '2', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601020107780249', NULL, NULL, NULL, NULL, '582268686814000', NULL, 'Jl.R.A.Kartini', '085241225366', 'ashamungkepalu@gmail.com', 'menikah', '165,20,XI,2010', '1970-01-01', NULL, 'didik ikrawan;adibah kanza azzahrah;ariqa afsheen ramadani', '', 'Lasami', 'Indo adi', 'Susi', 'P 484657', '2011-06-16', NULL, '197807012010011000', '2011-05-31', 'AA 04023907', '2016-11-22', NULL, '823.2/206/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/516/BKDD', '2009-12-14', '', '821.12/533/BKDD', '1970-01-01', '', '', '1970-01-01', 'SDN Gorontalo', '24 OA oa 0008519', '1990-06-07', '', NULL, 'SMPN Mamboro', '24 OA ob 1583583', '1993-06-05', '', NULL, 'STM Muhammadiyah Palu', '24 OB on 0022759', '1996-05-25', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', '24 OB 0022759', '1996-05-25', 'Bangunan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(51, '76.01', '1.03.0.00.0.00.01.0000', 'erwin', NULL, '', '198405252010011003', 'ujung pangang', '1984-05-25', NULL, '2', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601032606840001', NULL, NULL, NULL, NULL, '582268678814000', NULL, 'Jl. Andi Depu', '082189301151', 'ewhin.471@yahoo.com', 'menikah', '0331,33,X,2003', '2003-10-18', NULL, 'anggun aulia ramadani;alya dwi mella;dino aquah', '', 'Edy-', 'Rosmiati', 'Amnati', 'P 454848', '2011-10-05', NULL, '198405252010011003', '2011-05-31', 'AA 04023910', '2016-11-22', NULL, '823.2/206/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/516/BKDD', '2009-12-14', '', '821.12/533/BKDD', '2011-03-31', '', '', '2018-02-28', 'SD Inpres Binanga III', '06 OA oa 0139725', '1996-06-05', '', NULL, 'SLTP N 1Mamuju', '06 DI 0065154', '1999-05-31', '', NULL, 'Madrasah Aliyah Negeri Mamuju', 'Dt.II.I/t/MA.18.01/041/2002', '2002-06-17', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mamuju', 'DT II/t/MA.18.01/041/2002', '2002-06-17', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(52, '76.01', '1.03.0.00.0.00.01.0000', 'ariyadi', NULL, '', '197603042011011003', 'donggala', '1976-04-03', NULL, '2', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '', NULL, NULL, NULL, NULL, '159834290814000', NULL, 'Jl. Trans Sulawesi Pangiang', '082112569920', 'ariyadimustafa@gmail.com', 'menikah', '110,24,IV,2010', '2010-12-04', NULL, 'raissa amira ariyadi', '', 'H. Mustafa-', 'Hj. Hadri', 'Ikeu Krisnawati', 'Q 206586', '2013-02-01', NULL, '197603042011011003', '2012-04-17', 'AA 04023587', '2016-11-22', NULL, '823.2/70/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/474/BKDD', '2011-03-21', '', '821.12/534/BKDD', '2012-03-30', '', '', '1970-01-01', 'SD Inpres Tatura 1', '24 OA0014595 oa', '1988-06-17', '', NULL, '', '', '1904-01-01', '', NULL, 'STM N Palu', '24 OB on 0002653', '1994-05-30', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', '24 OB 0002653', '1994-05-30', 'Bangunan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(53, '76.01', '1.03.0.00.0.00.01.0000', 'safaruddin bakry', NULL, '', '198012142011011005', 'ujung pandang', '1980-12-14', NULL, '2', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601022812760001', NULL, NULL, NULL, NULL, '159834480814000', NULL, 'Jl. RA Kartini', '085331265680', 'safaruddin094@gmail.com', 'menikah', '362,25,VII,2004', '2004-10-07', NULL, 'muh.imran s.b;muh.rifqa s.b;muh.rafah;nur qaireen', '', 'H.Muh.Bakry-', 'Hj Masnia Saleh', 'Masni', 'Q 206585', '2013-02-01', NULL, '198012101101100542', '2012-04-17', 'AA 04023893', '2016-11-22', NULL, '823.2/70/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/474/BKDD', '2011-03-21', '', '821.12/534/BKDD', '2012-03-30', '', '', '1970-01-01', 'SDN No. 002 Bontang', '26 OA oa 0034373', '1994-05-31', '', NULL, 'SLTPN 1 Bontang', '26 OA oe 0023974', '1997-06-04', '', NULL, 'SMK N 2 Makassar', '06 Mk 0244393', '2000-06-12', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Makassar', '06 Mk 0244393', '2000-12-06', 'Gambar bangunan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(54, '76.01', '1.03.0.00.0.00.01.0000', 'NURPATIMA', NULL, 'ST', '199708152022032010', 'ujung pandang', '1997-08-15', NULL, '3', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Tehnik Pengairan', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '085163590519', 'nurpatima@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(55, '76.01', '1.03.0.00.0.00.01.0000', 'muh.rushan rusli', NULL, '', '197612282009031001', 'makassar', '1976-12-28', NULL, '2', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '7601022812760001', NULL, NULL, NULL, NULL, '780270906814000', NULL, 'Jl. Trans Sulawesi', '085255339932', 'rushanrusli@gmail.com', 'menikah', '17,17,I,2007', '2007-01-19', NULL, 'muh.mursidan baldan;sakira aftani rushan', '', 'Muh.Rusli-', 'Hasnah', 'Jusniati,Ama', 'P 333248', '2011-01-19', NULL, '197612282009031001', '2011-01-24', 'AA 04014498', '2016-06-20', NULL, '823.2/1080/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/76/BKDD', '2009-03-17', '', '821.12/519/BKDD', '2010-01-04', '', '', '2017-07-09', 'Hilang', 'Hilang', '1904-01-01', '', NULL, 'Hilang', 'Hilang', '1904-01-01', '', NULL, 'Hilang', 'Hilang', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Makassar', '', '1904-01-01', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(56, '76.01', '1.03.0.00.0.00.01.0000', 'daharia', NULL, '', '196712312012122006', 'enrekang', '1967-12-31', NULL, '2', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '7601025507710002', NULL, NULL, NULL, NULL, '550988455814000', NULL, 'Tikke,', '085395083935', '1@gmail.com', 'menikah', '220,07,III,1992', '1992-01-03', NULL, 'rita andriani ;risky amalia ;ahmad makbul  abd muttaqina;nurhikmasari ;nurfadillah afif', '', 'Damang -', 'Ralli', 'Kalman', 'A 04001217', '2015-06-04', NULL, '196120121220067123', '2013-06-19', 'BA 04025336', '2016-10-18', NULL, '823.2/628/BKPP', '2024-01-01', 'bupati mamuju utara', '813.2/077/BKD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'Hilang', 'Hilang', '1904-01-01', '', NULL, 'SMPN 1201 Tanrutedong', '', '1983-05-24', '', NULL, 'SMA Swasta Ampera Ujung Pandang', '06 OC oh 0071191', '1986-05-07', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ujung Pandang', '06 OC 0071191', '1986-01-05', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(57, '76.01', '1.03.0.00.0.00.01.0000', 'nur rachmadsyah', NULL, '', '196910042012121001', 'palu', '1969-04-10', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7271020410000001', NULL, NULL, NULL, NULL, '550873129814000', NULL, 'Palu', '081341002252', 'rachmadsyah1969@gmail.com', 'menikah', '108,12,III,1995', '1995-03-15', NULL, 'nur firmansyah;abd.agam gani;nur fadhansyah;nanda syumayah;syawal mubarak', '', 'Alm. H.M.Nur djusdinsyah -', 'Hj Murni DG. Pawatta', 'Daelira A.Pangeran', 'A 04001256', '2015-06-04', NULL, '196910042012121001', '2013-06-13', 'AA 04001256', '2015-06-04', NULL, '823.2/628/BKPP', '2024-01-01', 'bupati mamuju utara', '813.2/079/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SDN 6 Palu', '24 OA oa 16951', '1983-05-31', '', NULL, 'SMPN 3 Palu', '24 OB ob 0141594', '1986-05-05', '', NULL, 'SMA N 4 Palu', '24 OC oh 0412030', '1989-05-11', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', '24 OC oh 0412030', '1989-11-05', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(58, '76.01', '1.03.0.00.0.00.01.0000', 'adil bakry', NULL, '', '197408262012121002', 'ujung pandang', '1974-08-26', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Kepegawaian', NULL, '7601012608740002', NULL, NULL, NULL, NULL, '550811764814000', NULL, 'Jl. RA Kartini', '082345097929', 'adilbakriq@gmail.com', 'menikah', '356,16,VII,2004', '2004-10-07', NULL, 'afifah nur adilah adil bakri;azkiyah nur aulia adil bakri;azraf nizar ahmad adil bakri', '', 'H.Muh.Bakry-', 'Hj Masnia Saleh', 'Wahidah', 'A 04001210', '2015-06-04', NULL, '197408262012121002', '2013-06-14', 'AA 04023606', '2016-11-22', NULL, '823.2/628/BKPP', '2024-01-01', 'bupati mamuju utara', '813.2/067/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SDN Pontiku 2', '06 OA oa 0140563', '1988-06-24', '', NULL, 'SMPN 4 Ujung Pandang', '06 OA ob 0458075', '1991-06-08', '', NULL, 'STM N Balikpapan', '26 OB on 0063906', '1994-05-17', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Balikpapan', '26 OB on 0063906', '1994-05-17', 'Listrik', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(59, '76.01', '1.03.0.00.0.00.01.0000', 'a. pajarungi', '', '', '197707242012121003', 'lariang', '1997-07-24', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601022407770002', NULL, NULL, NULL, NULL, '550865422814000', NULL, 'Jl. Muh Hatta', '082290840790', 'andipajarungi1977pu@gmail.com', 'menikah', 'kk.31.05.01/pw.01/231/2011', '2011-11-16', NULL, 'andi kipra;andi kasmin;andi nur handalani;andi alif ramadhan', '', 'Andi Sabbi-', 'Andi Ampa', 'Sunarti', 'A 04001202', '2015-06-04', NULL, '197707242012121003', '2013-11-07', '', '1904-01-01', NULL, '823.2/312/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/031/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SD Inpres Kalukombeo', 'Hilang', '1904-01-01', '', NULL, 'SMPN 1 Banawa', 'Hilang', '1904-01-01', '', NULL, 'SMA Paket C', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mamuju Utara', '33PC0500017', '2008-07-01', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2026-02-19 10:06:39', 'nabiila', 'nabiila', 0),
(60, '76.01', '1.03.0.00.0.00.01.0000', 'rachmiy. s', NULL, '', '197709302012122002', 'luwu', '1977-09-30', NULL, '2', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '760102700970004', NULL, NULL, NULL, NULL, '555073972814000', NULL, 'Jl.Ponegoro', '081354318227', 'rachmiy.s@g.mail.com', 'menikah', '99,13,III,2012', '2012-07-03', NULL, 'qhairia arqanina', '', 'Siradjuddin-', 'Nur Huda', 'Muh.Syafriel', 'A 04001268', '2015-06-04', NULL, '197709302012122002', '2014-02-28', 'BA 04025339', '2016-10-18', NULL, '823.2/628/BKPP', '2024-01-01', 'Bupati Pasangkayu', '813.2/307/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SDN No.234 Temmalebba', '06 OA oa 0032499', '1990-06-07', '', NULL, 'Madrasah Tsanawiyah Negeri Palopo', 'E.IV/t/Mts-411/0002/93', '1993-06-07', '', NULL, 'Madrasah Aliyah Palopo', 'E.IV/t/MA 09/0224/1996', '1996-05-27', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palopo', 'E.IV/t/MA-09/0224/1996', '1996-05-27', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(61, '76.01', '1.03.0.00.0.00.01.0000', 'lelianti', NULL, '', '197712242012122001', 'mamuju', '1977-12-24', NULL, '2', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Pengadministrasi Keuangan', NULL, '760102641270003', NULL, NULL, NULL, NULL, '550860217814000', NULL, 'Jl.Ir Soekarno', '082190466956', 'lelianti@gmail.com', 'menikah', '237/20,X,2005', '2005-10-11', NULL, 'fathur pratama tamsil;farahmi fuanrianagari tamsil', '', 'Ahsan-', 'Saripa', 'Tamsil', 'A 04001244', '2015-06-04', NULL, '197712242012122001', '2013-07-17', 'BA 04025328', '2016-10-18', NULL, '823.2/628/BKPP', '2024-01-01', 'Bupati Pasangkayu', '813.2/211/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SDN Pangiang', '06 OA oa 0085179', '1990-06-07', '', NULL, 'SMPN 2 Donggala', '24 OA ob 1587602', '1993-06-05', '', NULL, 'SMEA N Donggala', '24 OB om 0186677', '1996-05-25', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Donggala', '24 OB om 0186677', '1996-05-25', 'Perkantoran', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(62, '76.01', '1.03.0.00.0.00.01.0000', 'muhammad mustaqim nur', NULL, '', '198303262012121004', 'gresik', '1983-03-26', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601042603830001', NULL, NULL, NULL, NULL, '550913420814000', NULL, 'Jl. Husni Thamrin', '081354937272', 'mustaqimnur@gmail.com', 'menikah', '31,08,III,2006', '2006-08-03', NULL, 'hesiah fhati muttaqim', '', 'H.Muchtar nur-', 'Hj.Ratna', 'Nur asiah', 'A 04001248', '2015-06-04', NULL, '198303262012121004', '2013-06-17', 'AA 04023894', '2016-11-22', NULL, '823.2/628/BKPP', '2024-01-01', 'Bupati Pasangkayu', '813.2/294/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '1970-01-01', 'SDN 1 Marauke', '18 OA oa 0020824', '1997-06-12', '', NULL, 'SLTPN 2 Marauke', '18 DI 2207483', '2000-06-16', '', NULL, 'SMK Karya Bakti', 'DN-05 Mk 0360531', '2003-06-05', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GRESIK', 'DN -05 Mk 0360531', '2003-05-06', 'Teknik Mesin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(63, '76.01', '1.03.0.00.0.00.01.0000', 'dina astuty', NULL, '', '198506172012122001', 'jakarta', '1985-06-17', NULL, '2', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601025706850006', NULL, NULL, NULL, NULL, '550792568814000', NULL, 'Jl. Trans Sulawesi', '081354937272', 'alifsakha2410@gmail.com', 'menikah', '0138,010,XII,2018', '2018-12-21', NULL, 'maulidya kaysha', '', 'Muslimin-', 'Syahriyah', 'Arman', 'A 04001215', '2015-06-04', NULL, '198506172012122001', '2013-06-17', '', '1904-01-01', NULL, '823.2/628/BKPP', '2024-01-01', 'Bupati Pasangkayu', '813.2/151/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '2017-04-13', 'SD Inpres Setingkat Donggala', '24 OA oa 0009939', '1997-06-10', '', NULL, 'SLTP 2 Banawa Donggala', '24 DI 2397259', '2000-06-23', '', NULL, 'SMU 1 Banawa', 'DN-18 Mu 0328909', '2003-06-07', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Donggala', 'DN -18 Mu 0328909', '2003-07-07', 'Ipa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(64, '76.01', '1.03.0.00.0.00.01.0000', 'abd. rifai', NULL, 'SH', '198610102012121001', 'lampa', '1986-10-10', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601101010860003', NULL, NULL, NULL, NULL, '550887947814000', NULL, 'Jl.Manggis', '081357350881', 'abd.rifai86@gmail.com', 'menikah', '112,112,I,2014', '2014-01-19', NULL, 'mayyadah putri rifai', '', 'Aco Saleh-', 'Syamsiah', 'Suryani, SH', 'A 04001203', '2015-06-04', NULL, '198610102012121001', '2013-07-14', 'AA 04023919', '2016-11-22', NULL, '823.3/204/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/300/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '2018-02-28', 'SDN No.057 Inpres Siratuang', '06 OA oa 0104160', '1997-06-12', '', NULL, 'Madrasah Tsanawiyah DDI Putra Mangkoso', 'E.IV/t/MTs.05/49/2001', '2001-06-28', '', NULL, 'SMA Alkhairaat Palu', 'DN-18 Mu 0636231', '2004-06-14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu Barat', 'DN -18 Mu 0636231', '2004-06-14', 'Ips', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(65, '76.01', '1.03.0.00.0.00.01.0000', 'rijal', NULL, '', '198602082012121004', 'mamuju', '1986-08-02', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601080802860001', NULL, NULL, NULL, NULL, '550792899814000', NULL, 'Jl. RA Kartini', '085396211346', 'rijalyuni08@gmail.com', 'menikah', '129,4,XII,2009', '2009-01-12', NULL, 'husnul khotimah;muh.luthfi hamid', '', 'Rusman-', 'Hj.Hadra', 'Wahyuni', 'A 04001270', '2015-06-04', NULL, '198607082012121004', '2013-06-19', 'AA 04023918', '2016-11-22', NULL, '823.2/628/BKPP', '2024-01-01', 'Bupati Pasangkayu', '813.2/223/BKDD', '2013-03-04', '', '821.12/597/BKDD', '2014-03-28', '', '', '2017-04-13', '', '', '1904-01-01', '', NULL, 'SLTPN 1 Pasangkayu', 'No 06 DI 1669093', '2001-06-28', '', NULL, 'SMA N 4 Palu', 'No DN-18 Mu 0592196', '2004-06-14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu Barat', 'DN -18 Mu 0592196', '2004-06-14', 'Ips', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(66, '76.01', '1.03.0.00.0.00.01.0000', 'asnawir', NULL, '', '197804022014051001', 'bambaira', '1978-02-04', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601020204780007', NULL, NULL, NULL, NULL, '704747559814000', NULL, 'Jl.Ir Soekarno', '082301910899', 'awieasnawir@gmail.com', 'menikah', '0008,008/1/2017', '2017-01-14', NULL, 'moh.rahim fatahillah', '', 'Daeng manggawing-', 'Kamaria', 'Hasnani', '', '1904-01-01', NULL, '760402140510010', '2014-08-28', '', '1904-01-01', NULL, '823.2/669/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/033/V-14/BKDD', '2014-05-30', '', '821.12/1054/BKDD', '2015-08-06', '', '', '2018-01-08', 'SD N Bambaira', 'No 06 OA oa 0085227', '1990-06-01', '', NULL, 'SLTPN 3 PALU', 'No    OA ob 1542828', '1994-06-23', '', NULL, 'SMK Muhammadiya 1 PALU', 'No 24 Mk 210 0024717', '1999-05-04', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', '24 Mk 210 0024717', '1999-05-24', 'Bangunan gedung', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(67, '76.01', '1.03.0.00.0.00.01.0000', 'syamsuri rustam', NULL, '', '198410172014051001', 'wotu', '1984-10-17', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601021710840002', NULL, NULL, NULL, NULL, '465936383814000', NULL, 'Jl.Kakatua', '081241250130', 'samsuriajadeh@gmail.com', 'menikah', '136,16,VI,2013', '2013-06-23', NULL, 'alifka hafis rusam ;aisyah nur a&#39;ilah rustam', '', 'Rustam Baba-', 'Muhalli', 'Kamaria', '', '1904-01-01', NULL, '841017140510010', '1904-01-01', 'AA 04023895', '2016-11-22', NULL, '823.2/669/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/023/V-14/BKDD', '2014-05-30', '', '823.2/669/BKPPD', '2018-01-08', '', '', '2018-01-09', 'SDN No.199 Dauloloe', '06 OA oa 0076201', '1997-06-10', '', NULL, 'SLTPN 1 Wotu', '06 DI 1588132', '2000-06-20', '', NULL, 'SMKN 2 Palopo', 'DN-19Mk 0668479', '2003-05-30', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palopo', 'DN-19 Mk 0668479', '2003-05-30', 'Tehnik Bangunan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(68, '76.01', '1.03.0.00.0.00.01.0000', 'asril', NULL, 'ST', '197412312014051001', 'polewali mandar', '1974-12-31', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '', NULL, NULL, NULL, NULL, '705445427814000', NULL, '', '085299978957', 'asril02021974@g.mail.com', 'menikah', '112,27,III,2001', '2001-03-25', NULL, 'fhirsya udani;mahatir muhammad;faiz asril', '', 'Abd. Asis-', 'Helwi', 'Sulfiani', '', '1904-01-01', NULL, '741231140510010', '1904-01-01', 'AA 04023580', '2016-11-22', NULL, '823.3/311/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/001/VI-14/BKDD', '2014-10-06', '', '821.12/1054/BKDD', '2015-08-06', '', '', '1970-01-01', 'SDN No.004 Sabang Subbi', '06 OA oa 0019783', '1987-06-02', '', NULL, 'SMPN Pambusuang', '06  OB ob 0472941', '1990-06-02', '', NULL, 'SMUN Tinambing', '06 OB og 0370771', '1993-05-29', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Polewali Mamasa', 'No,06 OB og 0370771', '1993-05-29', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(69, '76.01', '1.03.0.00.0.00.01.0000', 'muharram', NULL, '', '196807112014101001', 'bone', '1968-11-07', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7308100107680009', NULL, NULL, NULL, NULL, '726673676814000', NULL, 'Jl.Rusa', '081355054312', 'muharrammatra@gmail.com', 'menikah', '23,23,IV,1999', '1999-04-04', NULL, 'andi muh. fiqran;andi muh. fiqrin;andi muhammad fausan', '', 'Bennu-', 'A. kembang', 'Hijrawati', '', '1904-01-01', NULL, '19687112014101001', '2015-01-08', 'AA 04024249', '2016-11-22', NULL, '823.2/669/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/076/III-15/BKDD', '2015-03-23', '', '821.12/1259/BKDD', '2016-06-29', '', '', '2018-01-08', 'SDN No. 198 Arasoe', 'XXIII Aa 36046', '1980-05-12', '', NULL, 'SMPN Cina', '06 OB ob 0243468', '1983-05-24', '', NULL, 'SMUN 372 Mara', '06 OC oh 0398792', '1986-05-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mara', 'No,06 OC oh 0398792', '1986-01-05', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(70, '76.01', '1.03.0.00.0.00.01.0000', 'surya ros', NULL, '', '197708022014102002', 'tawau', '1977-02-08', NULL, '2', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', 'Pembina Jasa Konstruksi Ahli Muda', NULL, '7601024208770002', NULL, NULL, NULL, NULL, '470532854814000', NULL, 'Jl.Pattimura', '082189390167', 'surya.ros77@gmail.com', 'menikah', '171,06,IX,2003', '2003-05-10', NULL, 'muh syahrul adrian;mandhara dwi hapsari;muh. tegar sahril', '', 'H. Moh. Issa-', 'Hj. Deha', 'Saharuruddin', '', '1904-01-01', NULL, '', '1904-01-01', '', '1904-01-01', NULL, '823.2/669/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/092/III-15/BKDD', '2015-03-23', '', '821.12/1259/BKDD', '2016-06-29', '', '', '2018-01-08', 'SDN Gunung Lingkas 012', '26 OA oa 0041145', '1990-05-31', '', NULL, 'SMPN 5 Tarakan', '26 OA ob 1509981', '1993-05-29', '', NULL, 'SMAN 1 Palu', '24 OB oe 0774360', '1996-05-25', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', 'No,26 OB oc 0774360', '1996-05-25', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(71, '76.01', '1.03.0.00.0.00.01.0000', 'nur intan', NULL, '', '198211282014102001', 'donggala', '1982-11-28', NULL, '2', 'b', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '0', NULL, '7203086811820005', NULL, NULL, NULL, NULL, '550892905814000', NULL, 'Jl. Trans Sulawesi', '085398485550', 'nurintanabdun28@gmail.com', 'lajang', '', '1904-01-01', NULL, '', '', 'Faizal Abdun-', 'Hj Zainab Abdun', '', '', '1904-01-01', NULL, '198211252014102001', '2015-01-08', '', '1904-01-01', NULL, '823.2/669/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/208/III-15/BKDD', '2015-03-23', '', '821.12/1259/BKDD', '2016-06-29', '', '', '2018-01-08', 'SDN No.3 Donggala', '24 OA oa 0032849', '1994-06-03', '', NULL, 'SLTPN 2 Donggala', '24 OA oe 0009084', '1997-06-02', '', NULL, 'SMUN 1 Banawa Donggala', '24 Mu 0372974', '2000-06-16', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Banawa', 'No,24 Mu 0372974', '2000-06-16', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(72, '76.01', '1.03.0.00.0.00.01.0000', 'muh. ari gunawan syahputra', NULL, 'S.Sos', '198608182014091003', 'ujung pandang', '1986-08-18', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengawas Jalan dan Jembatan', NULL, '7371131808860011', NULL, NULL, NULL, NULL, '766191125814000', NULL, 'Jl.Urip Sumiharjo psky', '085242498018', 'arigunawan.gempa@yahoo.com', 'menikah', '74,08,IV,2013', '2013-04-19', NULL, 'muh. faith dzaky', '', 'Abd. Rajab Ibnu-', 'Hj. Hasniah Abidin', 'Andi Nur Indasari', '', '1904-01-01', NULL, '860818140910030', '1904-01-01', 'AA 04023583', '2016-11-22', NULL, '823.2/669/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/190/III-15/BKDD', '2015-03-23', '', '821.12/1258/BKDD', '2016-06-29', '', '', '2018-01-08', 'SD Inpres Bertingkat Mamajang 2', '06 Dd 0120166', '1998-06-10', '', NULL, 'SLTPN 24 Makassar', '06 DI 1684112', '2001-06-28', '', NULL, 'SMAN 3  Makassar', 'DN-19 Mu 0242440', '2004-06-14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Makassar', 'DN -19 Mu 0242440', '2004-06-14', 'IPA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(73, '76.01', '1.03.0.00.0.00.01.0000', 'iswanto', NULL, 'ST', '198509092014101003', 'bandar lampung', '1985-09-09', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Kasubag Umum Dan Kepegawaian', NULL, '7601040909850001', NULL, NULL, NULL, NULL, '169869724814000', NULL, 'Jl Manggis', '08524142776908111017769', 'is.archi04@gmail.com', 'menikah', '491,49,VI,2013', '2013-06-16', NULL, '', '', 'Ngadil', 'Ernawati', '', '', '1904-01-01', NULL, '850909141010030', '1904-01-01', 'AA 04024221', '2016-11-22', NULL, '823.2/226/BKPPD', '2024-01-01', 'Bupati Pasangkayu', '813.2/267/III-15/BKDD', '2015-03-23', '', '821.12/1259/BKDD', '2016-06-29', '', '', '2018-01-08', 'SDN Inpres Sarudu 1', '06 Dd 0114143', '1998-06-10', '', NULL, 'SLTPN 4 Palu', '24 DI 2517304', '2001-06-25', '', NULL, 'SMAN 1 Palu Timur', 'DN-18 Mu 0378202', '2004-06-14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', 'DN -18 Mu 0378202', '2004-06-14', 'IPA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(74, '76.01', '1.03.0.00.0.00.01.0000', 'darmawi', NULL, '', '196705152006041014', 'bambaira', '1967-05-15', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601021605670001', NULL, NULL, NULL, NULL, '674110523814000', NULL, 'Jl.Nangka', '082293556053', '1@gmail.com', 'menikah', '49/13/V/1998', '1998-02-05', NULL, 'risma yanti ;sugandi ;winda yanti ;irham', '', 'Abu Bacoki', 'Tiha', 'Wildan', '', '1904-01-01', NULL, '', '1904-01-01', 'AA 04024548', '2016-11-22', NULL, '823.2/1080/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.2/458/BKD', '2006-12-29', '', '821.12/41/BkD', '2008-04-03', '', '', '2017-07-09', 'SDN Bambaira', 'OA ou 109429', '1982-06-30', '', NULL, 'SMP 2 Donggala', '24 OB ob 0102023', '1985-05-21', '', NULL, 'SMA N 2 Palu', '24 OC ob 0152549', '1982-06-02', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Palu', 'No.24 Oc.ob 0152549', '1988-02-06', 'IPA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(75, '76.01', '1.03.0.00.0.00.01.0000', 'boby s. pakaya', NULL, '', '197706132014091002', 'donggala', '1977-06-13', NULL, '2', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601031306770002', NULL, NULL, NULL, NULL, '467303616814000', NULL, 'Jl. Husni Thamrin', '082344778891', '1@gmail.com', 'menikah', '35,02,III,2009', '2009-01-03', NULL, 'bain alfarezy;nizam alif al rizky', '', 'Sudirman. S. Pakaya-', 'Irmawati Ahmad', 'Hasnawia', '', '1904-01-01', NULL, '197706132014091002', '2015-07-13', 'AA 04023582', '2016-11-22', NULL, '823.2/1080/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.1/004/III-15/BKDD', '2015-03-23', '', '821.11/1255/BKDD', '2016-06-29', '', '', '2017-07-09', 'SDN Buhias', '16 OA oa 0000516', '1989-06-14', '', NULL, 'SMPN 1 Donggala', '24 OA ob 1570637', '1992-06-04', '', NULL, 'SMA  Paket C', '33PC0500216', '2008-07-31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Mamuju', '33PC0500216', '2008-07-31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(76, '76.01', '1.03.0.00.0.00.01.0000', 'i ketut repet', NULL, '', '198510062014101001', 'bangli', '1985-06-10', NULL, '1', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Kepegawaian', NULL, '7601030610850001', NULL, NULL, NULL, NULL, '551504541815000', NULL, 'Baras', '085255971461', 'ketutrepet@gmail.com', 'menikah', '7601-KW-15042016-0001', '2016-04-15', NULL, 'i putu raka danendra', '', 'I Ketut Murti-', 'Ni wayan soko', 'Ni luh Budiyasni', '', '1904-01-01', NULL, '198510062014101001', '2015-07-29', 'AA 04024236', '2016-11-22', NULL, '823.1/671/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.1/082/III-15/BKDD', '2015-03-23', '', '821.11/1256/BKDD', '2016-06-29', '', '', '2018-01-08', 'SD Impres Baras VII', '06 Dd 0113551', '1998-06-10', '', NULL, 'SLTP Neg. 4', 'NO 06 DI 1515924', '2002-06-24', '', NULL, '', '', '1904-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pasangkayu', 'NO 06 DI 1515924', '2002-06-24', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(77, '76.01', '1.03.0.00.0.00.01.0000', 'agussalim t', NULL, '', '198510092014091002', 'tikke', '1985-09-10', NULL, '2', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Analis Pengelolaan SDA', NULL, '7601120910850001', NULL, NULL, NULL, NULL, '555997105814000', NULL, 'jl.Diponegoro', '085298192223', 'agussalimt85@gmail.com', 'menikah', '0086,003,IX,2016', '2016-09-22', NULL, 'askana ratifah', '', 'Abd. Talib-', 'Ruhnia', 'Gustina', '', '1904-01-01', NULL, '851009140910020', '1904-01-01', '', '1904-01-01', NULL, '823.2/1080/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.1/067/III-15/BKDD', '2015-03-23', '', '821.11/1255/BKDD', '2016-06-29', '', '', '2017-07-09', 'SDN No.003 Masimbu', '06 Dd 0102021', '1999-05-31', '', NULL, 'SLTPN 3 Pasangkayu', '06 DI 1515869', '2002-06-24', '', NULL, 'SMA Paket C', '33PC0500219', '2008-07-31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Baras', '33PC0500219', '2008-07-31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(78, '76.01', '1.03.0.00.0.00.01.0000', 'ahmad yani', NULL, '', '198205252014091001', 'donggala', '1982-05-25', NULL, '2', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '7601042606820003', NULL, NULL, NULL, NULL, '756595690814000', NULL, 'Dusun Nunu', '082344633123', '1@gmail.com', 'menikah', '09,09,I,2009', '2009-01-16', NULL, 'alif syafar saputra;adnan akbar', '', 'Ridwan Abd. Halim', 'Harma Ambo djiwa', 'Dini Resqiyah', 'A 04018082', '2017-01-13', NULL, '198203232014091001', '2013-06-26', 'AA 04018072', '2009-11-09', NULL, '823.2/206/BKPPD', '2024-01-01', 'bupati mamuju utara', '813.1/017/III-15/BKDD', '2015-03-23', '', '823.2/206/BKPPD', '2018-02-28', '', '', '2018-02-28', 'SD Inpres Bertingkat Donggala', '24 OA oa 0008650', '1995-05-27', '', NULL, 'MTSN Palu Barat', 'E. W/5/MTS.8/002/98', '1998-05-26', '', NULL, 'SMA Paket C', 'DN-32 PC 0001275', '2017-05-02', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Donggala', 'E.W/5/MG.8/002/98', '1998-05-26', 'IPS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(79, '76.01', '1.03.0.00.0.00.01.0000', 'Serius pahu&#39;u', NULL, 'ST', '198311112014101003', '', '1983-11-11', NULL, '3', 'a', 'kristen', 'pria', 'pnsd2', 'peg_tetap', 'Analis Pengelolaan SDA', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '', '', '1@gmail.com', 'menikah', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(80, '76.01', '1.03.0.00.0.00.01.0000', 'Darwan', NULL, '', '198209182009031002', '', '1982-09-18', NULL, '2', 'd', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Umum', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '', '', '1@gmail.com', 'menikah', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(81, '76.01', '1.03.0.00.0.00.01.0000', 'Abd.Jabbar', NULL, '', '197809202014091003', '', '1988-04-21', NULL, '2', 'b', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengawas Jalan dan Jembatan', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '', '', '1@gmail.com', 'menikah', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(82, '76.01', '1.03.0.00.0.00.01.0000', 'HERMAN HADDANG', NULL, '', '198301022007011006', '', '1983-01-02', NULL, '3', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Pengadministrasi Perencanaan dan Program', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '', '', '1@gmail.com', 'menikah', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(83, '76.01', '1.03.0.00.0.00.01.0000', 'Nurafni Rusdin', NULL, 'SE', '807006633669587000', 'pasangkayu', '1988-04-21', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '082347290792', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(84, '76.01', '1.03.0.00.0.00.01.0000', 'Musdalipa', NULL, '', '849554402730169000', 'lamu', '1987-12-06', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '082347290792', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0);
INSERT INTO `db_asn_pemda_neo` (`id`, `kd_wilayah`, `kd_opd`, `nama`, `gelar_depan`, `gelar`, `nip`, `t4_lahir`, `tgl_lahir`, `file_akta_lahir`, `golongan`, `ruang`, `agama`, `kelamin`, `jenis_kepeg`, `status_kepeg`, `jabatan`, `id_jabatan`, `no_ktp`, `file_ktp`, `no_kk`, `tgl_kk`, `file_kk`, `npwp`, `file_npwp`, `alamat`, `kontak_person`, `email`, `status`, `no_buku_nikah`, `tgl_nikah`, `file_buku_nikah`, `nama_anak`, `nik_anak`, `nama_ayah`, `nama_ibu`, `nama_pasangan`, `no_karpeg`, `tgl_karpeg`, `file_karpeg`, `no_taspen`, `tgl_taspen`, `no_karsi_karsu`, `tgl_karsi_karsu`, `file_karsi_karsu`, `nmr_sk_terakhir`, `tgl_tmt_akhir`, `pj_sk_terakhir`, `nmr_sk_cpns`, `tgl_sk_cpns`, `pj_sk_cpns`, `nmr_sk_pns`, `tgl_sk_pns`, `pj_sk_pns`, `sk_pangkat_terakhir`, `tgl_sk_terakhir`, `pend_sekolah_sd`, `pend_ijasah_sd`, `pend_tgl_tmt_sd`, `pend_t4_sd`, `pend_file_sd`, `pend_sekolah_smp`, `pend_ijasah_smp`, `pend_tgl_tmt_smp`, `pend_t4_smp`, `pend_file_smp`, `pend_sekolah_smu`, `pend_ijasah_smu`, `pend_tgl_tmt_smu`, `pend_t4_smu`, `pend_file_smu`, `pend_sekolah_s1`, `pend_ijasah_s1`, `pend_tgl_tmt_s1`, `pend_t4_s1`, `pend_file_s1`, `pend_sekolah_s2`, `pend_ijasah_s2`, `pend_tgl_tmt_s2`, `pend_t4_s2`, `pend_file_s2`, `pend_sekolah_s3`, `pend_ijasah_s3`, `pend_tgl_tmt_s3`, `pend_t4_s3`, `pend_file_s3`, `pend_sekolah_akhir`, `pend_ijasah_akhir`, `pend_tgl_tmt_akhir`, `pend_t4_akhir`, `pend_file_akhir`, `file_photo`, `gapok`, `aktif`, `no_urut`, `unit_kerja`, `no_rekening`, `nama_bank`, `urutan`, `kelompok`, `suka`, `follow`, `keterangan`, `disable`, `tgl_insert`, `tgl_update`, `username_insert`, `username_update`, `is_deleted`) VALUES
(85, '76.01', '1.03.0.00.0.00.01.0000', 'Sudirman', NULL, '', '459672525374271000', 'watatu', '1987-11-17', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '082347290792', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(86, '76.01', '1.03.0.00.0.00.01.0000', 'Asrafil', NULL, 'SH', '911566096258177000', 'labuang', '1987-06-15', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '082347290792', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(87, '76.01', '1.03.0.00.0.00.01.0000', 'Ni Made Wiryanti', NULL, 'S.Pd', '846803304754119000', 'gunung sari', '1992-12-08', NULL, '1', 'a', 'hindu', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '082347290792', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(88, '76.01', '1.03.0.00.0.00.01.0000', 'Sri Suhartina', NULL, 'S.AP', '135742790342525000', 'tanrutedong', '1996-12-06', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '082347290792', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(89, '76.01', '1.03.0.00.0.00.01.0000', 'Muh. Fikrih Anugrah', NULL, '', '188085191659485000', 'bulukumba', '1997-02-13', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '082347290792', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(90, '76.01', '1.03.0.00.0.00.01.0000', 'Kasriadi', NULL, '', '717031895479988000', 'pasangkayu', '1998-04-25', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(91, '76.01', '1.03.0.00.0.00.01.0000', 'St. Rahma Aris', NULL, 'S,Pd', '414212358906171000', 'pinrang', '1995-05-10', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(92, '76.01', '1.03.0.00.0.00.01.0000', 'Herlin', NULL, '', '865453108557349000', 'donggala', '1981-03-02', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(93, '76.01', '1.03.0.00.0.00.01.0000', 'rosmayani', NULL, '', '352782285393646000', 'ujung pandang', '1985-06-02', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(94, '76.01', '1.03.0.00.0.00.01.0000', 'gusti ayu nastuti', NULL, '', '819950307868545000', '', '1985-06-02', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(95, '76.01', '1.03.0.00.0.00.01.0000', 'Budiman', NULL, 'S,A.P', '828466953145709000', 'belawa', '1994-03-17', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(96, '76.01', '1.03.0.00.0.00.01.0000', 'Zainal Abidin', NULL, '', '410139934548878000', 'polmas', '1981-03-02', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(97, '76.01', '1.03.0.00.0.00.01.0000', 'sukri', NULL, '', '974997149712197000', 'panggalasiang', '1988-09-05', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(98, '76.01', '1.03.0.00.0.00.01.0000', 'Adiyansah', NULL, '', '333040658412001000', '', '1985-06-02', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(99, '76.01', '1.03.0.00.0.00.01.0000', 'Rahma', NULL, '', '413285156152683000', 'pangiang', '1991-03-13', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(100, '76.01', '1.03.0.00.0.00.01.0000', 'Ahyar', NULL, '', '938047800766538000', 'tomo', '1992-06-05', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(101, '76.01', '1.03.0.00.0.00.01.0000', 'Joni loe', NULL, '', '701471335940569000', 'atambua', '1986-07-13', NULL, '1', 'a', 'khatolik', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(102, '76.01', '1.03.0.00.0.00.01.0000', 'Agustan', NULL, '', '871635074514566000', 'watampone', '1998-08-25', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(103, '76.01', '1.03.0.00.0.00.01.0000', 'I ketut martayasa', NULL, '', '815947482934781000', 'puntari makmur', '1994-12-12', NULL, '1', 'a', 'hindu', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(104, '76.01', '1.03.0.00.0.00.01.0000', 'Fitra ramadan putra', NULL, '', '407723030603133000', 'makassar', '1996-01-26', NULL, '1', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(105, '76.01', '1.03.0.00.0.00.01.0000', 'St.hajar', NULL, '', '671000122295757000', 'tampaure', '1995-01-10', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(106, '76.01', '1.03.0.00.0.00.01.0000', 'SALDI', NULL, '', '671000125295757000', 'tampaure', '1995-01-10', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(107, '76.01', '1.03.0.00.0.00.01.0000', 'SUAIB', NULL, '', '670000125195757000', 'tampaure', '1995-01-10', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(108, '76.01', '1.03.0.00.0.00.01.0000', 'SUDIRMAN', NULL, '', '670030125185757000', 'tampaure', '1995-01-10', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(109, '76.01', '1.03.0.00.0.00.01.0000', 'ABD WAHID', NULL, '', '670033125189757000', 'tampaure', '1995-01-10', NULL, '1', 'a', 'islam', 'wanita', 'pnsd2', 'peg_tetap', '', NULL, '', NULL, NULL, NULL, NULL, '', NULL, 'Pasangkayu', '0812', '1@gmail.com', 'lajang', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2024-04-06 16:23:40', 'nabiila', 'nabiila', 0),
(110, '76.01', '1.03.0.00.0.00.01.0000', 'Bachtiar B.Sumay', NULL, 'SH', '197409192006041007', '', '1974-09-19', NULL, '4', 'a', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'Kepala Bidang Binamarga', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '-', '0000', '1@gmail.com', 'janda-duda', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2025-10-07 00:07:36', 'nabiila', 'nabiila', 0),
(111, '76.01', '1.03.0.00.0.00.01.0000', 'SRI IRDA AYU', NULL, 'SP.,M.Si', '198306252005022003', '', '1983-06-25', NULL, '4', 'a', 'islam', 'Wanita', 'pnsd2', 'peg_tetap', 'Kepala Bidang Binamarga', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '-', '0000', '1@gmail.com', 'janda-duda', '', '1970-01-01', NULL, '', '', '', '', '', '', '1970-01-01', NULL, '', '1970-01-01', '', '1970-01-01', NULL, '', '2024-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '1970-01-01', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, '', 0, '2024-04-06 16:23:40', '2025-10-07 00:07:36', 'nabiila', 'nabiila', 0),
(112, '76.01', '1.03.0.00.0.00.01.0000', 'sakonne', '', '', '123456789012345671', 'Mamuju', '2026-02-22', NULL, '4', 'c', 'islam', 'pria', 'pnsd2', 'peg_tetap', 'non', NULL, '', NULL, NULL, NULL, NULL, '', NULL, '', '', 'alwi@gmail', 'menikah', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, '', 0, '2026-02-21 16:17:25', '2026-02-22 00:17:25', 'inayah', NULL, 0);

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

--
-- Trigger `kontrak_neo`
--
DELIMITER $$
CREATE TRIGGER `trg_kontrak_validate_insert` BEFORE INSERT ON `kontrak_neo` FOR EACH ROW BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL;
  DECLARE sub_code VARCHAR(50) DEFAULT NULL;
  DECLARE provider VARCHAR(255) DEFAULT NULL;
  IF NEW.tahap='dpa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSEIF NEW.tahap='dppa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  END IF;
  IF budget IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Sumber kontrak harus DPA/DPPA yang disetujui'; END IF;
  IF NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Nilai kontrak melebihi anggaran DPA/DPPA'; END IF;
  SELECT nama_perusahaan INTO provider FROM rekanan_neo WHERE id=NEW.rekanan_id AND kd_wilayah=NEW.kd_wilayah AND is_deleted=0 LIMIT 1;
  IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;
  SET NEW.total_anggaran=budget,NEW.kd_sub_keg=sub_code,NEW.nama_penyedia=provider;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_kontrak_validate_update` BEFORE UPDATE ON `kontrak_neo` FOR EACH ROW BEGIN
  DECLARE budget DECIMAL(20,2) DEFAULT NULL; DECLARE sub_code VARCHAR(50) DEFAULT NULL; DECLARE provider VARCHAR(255) DEFAULT NULL;
  IF NEW.tahap='dpa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dpa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1;
  ELSEIF NEW.tahap='dppa' THEN SELECT jumlah,kd_sub_keg INTO budget,sub_code FROM dppa_neo WHERE id=NEW.anggaran_id AND kd_wilayah=NEW.kd_wilayah AND kd_opd=NEW.kd_opd AND tahun=NEW.tahun AND setujui=1 AND is_deleted=0 LIMIT 1; END IF;
  IF budget IS NULL OR NEW.nilai_kontrak>budget THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kontrak tidak valid atau nilainya melebihi DPA/DPPA'; END IF;
  SELECT nama_perusahaan INTO provider FROM rekanan_neo WHERE id=NEW.rekanan_id AND kd_wilayah=NEW.kd_wilayah AND is_deleted=0 LIMIT 1;
  IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;
  SET NEW.total_anggaran=budget,NEW.kd_sub_keg=sub_code,NEW.nama_penyedia=provider;
END
$$
DELIMITER ;

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

-- --------------------------------------------------------

--
-- Struktur dari tabel `wallchat`
--

CREATE TABLE `wallchat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `type` enum('status','pesan','komentar') NOT NULL DEFAULT 'status',
  `content` text NOT NULL,
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
  ADD KEY `idx_realisasi_kontrak` (`kontrak_id`,`tanggal`,`is_deleted`);

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
-- Indeks untuk tabel `kontrak_neo`
--
ALTER TABLE `kontrak_neo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kontrak_nomor_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`nomor_kontrak`,`is_deleted`),
  ADD KEY `idx_kontrak_scope` (`kd_wilayah`,`kd_opd`,`tahun`,`is_deleted`),
  ADD KEY `idx_kontrak_anggaran` (`tahap`,`anggaran_id`),
  ADD KEY `idx_kontrak_rekanan` (`rekanan_id`);

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
  ADD PRIMARY KEY (`id`);

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
-- Indeks untuk tabel `wallchat`
--
ALTER TABLE `wallchat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`);

--
-- Indeks untuk tabel `wilayah_neo`
--
ALTER TABLE `wilayah_neo`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

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
-- AUTO_INCREMENT untuk tabel `kontrak_neo`
--
ALTER TABLE `kontrak_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT untuk tabel `trx_naskah_dinas`
--
ALTER TABLE `trx_naskah_dinas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `trx_naskah_meta`
--
ALTER TABLE `trx_naskah_meta`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Ketidakleluasaan untuk tabel `trx_naskah_struktur`
--
ALTER TABLE `trx_naskah_struktur`
  ADD CONSTRAINT `fk_naskah_struktur` FOREIGN KEY (`naskah_id`) REFERENCES `trx_naskah_dinas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
