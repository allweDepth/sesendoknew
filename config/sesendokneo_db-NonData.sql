-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 21 Feb 2026 pada 22.10
-- Versi server: 12.2.2-MariaDB
-- Versi PHP: 8.5.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `sesendokneo_db`
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
  `peraturan` varchar(255) NOT NULL,
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
-- Struktur dari tabel `asb_neo`
--

CREATE TABLE `asb_neo` (
  `id` int(8) NOT NULL,
  `kd_wilayah` varchar(25) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_aset` varchar(25) NOT NULL,
  `uraian_barang` varchar(400) NOT NULL,
  `spesifikasi` text DEFAULT NULL,
  `satuan` varchar(400) NOT NULL,
  `harga_satuan` decimal(12,0) NOT NULL,
  `tkdn` varchar(400) DEFAULT NULL,
  `merek` varchar(400) DEFAULT NULL,
  `kd_akun` text NOT NULL,
  `peraturan` varchar(255) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `aksi` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `peraturan` varchar(255) NOT NULL,
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
-- Struktur dari tabel `berita_neo`
--

CREATE TABLE `berita_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(50) NOT NULL,
  `judul` varchar(400) NOT NULL,
  `id_pengenal` varchar(255) NOT NULL,
  `kelompok` varchar(50) NOT NULL,
  `uraian_html` text NOT NULL,
  `uraian_singkat` text DEFAULT NULL,
  `tanggal` date NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `keterangan` varchar(255) NOT NULL,
  `urutan` int(11) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bidang`
--

CREATE TABLE `bidang` (
  `kode` varchar(10) NOT NULL,
  `kode_urusan` varchar(10) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `peraturan` int(11) NOT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar_paket_neo`
--

CREATE TABLE `daftar_paket_neo` (
  `id` int(11) NOT NULL,
  `kd_rup` varchar(25) DEFAULT NULL,
  `kd_paket` varchar(25) DEFAULT NULL,
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
  `tahun` year(4) NOT NULL,
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
  `kd_wilayah` varchar(100) NOT NULL,
  `kd_opd` varchar(100) NOT NULL,
  `kd_sub_keg` varchar(100) NOT NULL,
  `kd_akun` varchar(100) NOT NULL,
  `id_paket` int(11) NOT NULL,
  `ket_paket` text NOT NULL,
  `id_uraian_paket` int(11) NOT NULL,
  `ket_uraian_paket` varchar(400) NOT NULL,
  `id_dok_anggaran` int(11) NOT NULL,
  `dok` varchar(50) NOT NULL,
  `vol` decimal(36,12) NOT NULL,
  `jumlah` decimal(36,12) NOT NULL,
  `tanggal` date NOT NULL,
  `file` varchar(400) DEFAULT NULL,
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `keterangan` varchar(400) NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
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
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
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
-- Struktur dari tabel `dpa_neo`
--

CREATE TABLE `dpa_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(22) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_sub_keg` varchar(50) NOT NULL,
  `kd_akun` varchar(50) NOT NULL,
  `kel_rek` varchar(50) NOT NULL,
  `objek_belanja` varchar(255) NOT NULL,
  `uraian` text NOT NULL,
  `jenis_kelompok` varchar(255) NOT NULL,
  `kelompok` varchar(255) NOT NULL,
  `jenis_standar_harga` varchar(6) NOT NULL,
  `id_standar_harga` int(11) DEFAULT NULL,
  `komponen` varchar(400) DEFAULT NULL,
  `spesifikasi` varchar(400) DEFAULT NULL,
  `tkdn` decimal(36,12) DEFAULT NULL,
  `pajak` tinyint(1) DEFAULT NULL,
  `harga_satuan` decimal(36,12) NOT NULL,
  `vol_1` decimal(36,12) NOT NULL,
  `vol_2` decimal(36,12) DEFAULT NULL,
  `vol_3` decimal(36,12) DEFAULT NULL,
  `vol_4` decimal(36,12) DEFAULT NULL,
  `vol_5` decimal(36,12) DEFAULT NULL,
  `sat_1` varchar(50) NOT NULL,
  `sat_2` varchar(50) DEFAULT NULL,
  `sat_3` varchar(50) DEFAULT NULL,
  `sat_4` varchar(50) DEFAULT NULL,
  `sat_5` varchar(50) DEFAULT NULL,
  `volume` decimal(36,12) DEFAULT NULL,
  `jumlah` decimal(36,12) NOT NULL,
  `sumber_dana` varchar(255) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui` tinyint(1) DEFAULT 0,
  `id_renja` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dppa_neo`
--

CREATE TABLE `dppa_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(22) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_sub_keg` varchar(50) NOT NULL,
  `kd_akun` varchar(50) NOT NULL,
  `kel_rek` varchar(50) NOT NULL,
  `objek_belanja` varchar(255) NOT NULL,
  `uraian` text NOT NULL,
  `jenis_kelompok` varchar(255) NOT NULL,
  `kelompok` varchar(255) NOT NULL,
  `jenis_standar_harga` varchar(6) NOT NULL,
  `id_standar_harga` int(11) DEFAULT NULL,
  `komponen` varchar(400) DEFAULT NULL,
  `spesifikasi` varchar(400) DEFAULT NULL,
  `tkdn` decimal(36,12) DEFAULT NULL,
  `pajak` tinyint(1) DEFAULT NULL,
  `harga_satuan` decimal(36,12) NOT NULL,
  `vol_1` decimal(36,12) NOT NULL,
  `vol_2` decimal(36,12) DEFAULT NULL,
  `vol_3` decimal(36,12) DEFAULT NULL,
  `vol_4` decimal(36,12) DEFAULT NULL,
  `vol_5` decimal(36,12) DEFAULT NULL,
  `sat_1` varchar(50) NOT NULL,
  `sat_2` varchar(50) DEFAULT NULL,
  `sat_3` varchar(50) DEFAULT NULL,
  `sat_4` varchar(50) DEFAULT NULL,
  `sat_5` varchar(50) DEFAULT NULL,
  `volume` decimal(36,12) DEFAULT NULL,
  `jumlah` decimal(36,12) NOT NULL,
  `sumber_dana` varchar(255) DEFAULT NULL,
  `vol_1_p` decimal(36,12) DEFAULT NULL,
  `vol_2_p` decimal(36,12) DEFAULT NULL,
  `vol_3_p` decimal(36,12) DEFAULT NULL,
  `vol_4_p` decimal(36,12) DEFAULT NULL,
  `vol_5_p` decimal(36,12) DEFAULT NULL,
  `sat_1_p` varchar(50) DEFAULT NULL,
  `sat_2_p` varchar(50) DEFAULT NULL,
  `sat_3_p` varchar(50) DEFAULT NULL,
  `sat_4_p` varchar(50) DEFAULT NULL,
  `sat_5_p` varchar(50) DEFAULT NULL,
  `volume_p` decimal(36,12) DEFAULT NULL,
  `jumlah_p` decimal(36,12) DEFAULT NULL,
  `sumber_dana_p` varchar(255) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui` tinyint(1) DEFAULT 0,
  `id_dpa` int(11) DEFAULT NULL,
  `id_renja_p` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `hspk_neo`
--

CREATE TABLE `hspk_neo` (
  `id` int(8) NOT NULL,
  `kd_wilayah` varchar(25) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_aset` varchar(25) NOT NULL,
  `uraian_barang` text NOT NULL,
  `spesifikasi` text DEFAULT NULL,
  `satuan` varchar(400) NOT NULL,
  `harga_satuan` decimal(18,6) NOT NULL,
  `tkdn` varchar(400) DEFAULT NULL,
  `merek` varchar(400) DEFAULT NULL,
  `kd_akun` text NOT NULL,
  `peraturan` varchar(255) NOT NULL,
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
-- Struktur dari tabel `kegiatan`
--

CREATE TABLE `kegiatan` (
  `kode` varchar(20) NOT NULL,
  `kode_program` varchar(15) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `peraturan` int(11) NOT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kegiatan_renstra_neo`
--

CREATE TABLE `kegiatan_renstra_neo` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `kode_kegiatan` varchar(20) DEFAULT NULL,
  `nama_kegiatan` text NOT NULL,
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
-- Struktur dari tabel `mapping_aset_akun`
--

CREATE TABLE `mapping_aset_akun` (
  `id` int(11) NOT NULL,
  `kd_aset` varchar(25) NOT NULL,
  `uraian_aset` varchar(400) NOT NULL,
  `kd_akun` varchar(25) DEFAULT NULL,
  `uraian_akun` varchar(400) DEFAULT NULL,
  `kelompok` varchar(5) DEFAULT NULL,
  `disable` int(11) NOT NULL DEFAULT 0,
  `aksi` tinyint(1) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `peraturan` varchar(255) NOT NULL,
  `tgl_insert` datetime NOT NULL,
  `username_insert` varchar(255) NOT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
-- Struktur dari tabel `naskah_dinas_neo`
--

CREATE TABLE `naskah_dinas_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(150) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
  `jenis_naskah_dinas` varchar(255) NOT NULL,
  `nomor` varchar(255) NOT NULL,
  `tgl_surat_dibuat` date NOT NULL,
  `tentang` varchar(400) NOT NULL,
  `klasifikasi_keamanan` varchar(255) NOT NULL,
  `pemberi_tgs` varchar(18) NOT NULL,
  `jbt_pemberi_tgs` varchar(150) DEFAULT NULL,
  `pangkat_pemberi_tgs` varchar(150) DEFAULT NULL,
  `nama_pemberi_tgs` varchar(255) NOT NULL,
  `alinea_1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`alinea_1`)),
  `alinea_2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_2`)),
  `alinea_3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_3`)),
  `alinea_4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_4`)),
  `alinea_5` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_5`)),
  `alinea_6` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_6`)),
  `alinea_7` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_7`)),
  `alinea_8` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_8`)),
  `alinea_9` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_9`)),
  `alinea_10` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_10`)),
  `alinea_11` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alinea_11`)),
  `text_1` text DEFAULT NULL,
  `text_2` text DEFAULT NULL,
  `text_3` text DEFAULT NULL,
  `text_4` text DEFAULT NULL,
  `text_5` text DEFAULT NULL,
  `text_6` text DEFAULT NULL,
  `text_7` text DEFAULT NULL,
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
-- Struktur dari tabel `organisasi_neo`
--

CREATE TABLE `organisasi_neo` (
  `id` int(8) NOT NULL,
  `kd_wilayah` varchar(25) NOT NULL,
  `kode` varchar(30) NOT NULL,
  `uraian` varchar(400) NOT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `nama_kepala` varchar(255) DEFAULT NULL,
  `nip_kepala` varchar(19) DEFAULT NULL,
  `singkatan` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `peraturan` varchar(255) NOT NULL,
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
  `kd_wilayah` varchar(50) NOT NULL,
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
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `peraturan_neo`
--

CREATE TABLE `peraturan_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(25) NOT NULL,
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
  `kd_wilayah` varchar(60) NOT NULL,
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
-- Struktur dari tabel `program`
--

CREATE TABLE `program` (
  `kode` varchar(15) NOT NULL,
  `kode_bidang` varchar(10) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `peraturan` int(11) NOT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
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
  `nama_program` text NOT NULL,
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
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
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
  `sub_kategori` varchar(200) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `schema_json` longtext DEFAULT NULL,
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ref_jenis_naskah`
--

INSERT INTO `ref_jenis_naskah` (`id`, `kelompok_id`, `nama`, `sub_kategori`, `urutan`, `schema_json`, `kd_wilayah`, `kd_opd`, `username_insert`, `tgl_insert`, `username_update`, `tgl_update`, `keterangan`) VALUES
(1, 1, 'Peraturan Arsip Nasional Republik Indonesia', 'Pengaturan', 1, '[\r\n  {\"type\":\"auto_nomor\",\"label\":\"Nomor\",\"name\":\"nomor\"},\r\n  {\"type\":\"text\",\"label\":\"Tentang\",\"name\":\"tentang\"},\r\n  {\"type\":\"date\",\"label\":\"Tanggal\",\"name\":\"tanggal\"}\r\n]', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 'Instruksi', 'Pengaturan', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 1, 'Surat Edaran', 'Pengaturan', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, 'Standar Operasional Prosedur Administrasi Pemerintah', 'Pengaturan', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 1, 'Naskah Dinas Penetapan', 'Penetapan', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1, 'Naskah Dinas Penugasan', 'Penugasan', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 2, 'Nota Dinas', 'Internal', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 2, 'Memorandum', 'Internal', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 2, 'Disposisi', 'Internal', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 2, 'Surat Undangan Internal', 'Internal', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 2, 'Naskah Dinas Korespondensi Eksternal', 'Eksternal', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 3, 'Surat Perjanjian', '', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 3, 'Surat Kuasa', '', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 3, 'Berita Acara', '', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 3, 'Surat Keterangan', '', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 3, 'Surat Pengantar', '', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 3, 'Pengumuman', '', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 3, 'Laporan', '', 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 3, 'Telaah Staf', '', 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 3, 'Notula', '', 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 3, 'Sambutan Tertulis', '', 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 3, 'Siaran Pers', '', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 3, 'Sertifikat', '', 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 3, 'Surat Tanda Tamat Pelatihan', '', 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 3, 'Piagam Penghargaan', '', 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
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
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ref_kelompok_naskah`
--

INSERT INTO `ref_kelompok_naskah` (`id`, `kode`, `nama`, `urutan`, `kd_wilayah`, `kd_opd`, `username_insert`, `tgl_insert`, `username_update`, `tgl_update`, `keterangan`) VALUES
(1, 'A', 'Naskah Dinas Arahan', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'B', 'Naskah Dinas Korespondensi', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'C', 'Naskah Dinas Khusus', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_klasifikasi_keamanan`
--

CREATE TABLE `ref_klasifikasi_keamanan` (
  `id` int(11) NOT NULL,
  `kode` varchar(5) DEFAULT NULL,
  `nama` varchar(50) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
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
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_insert` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ref_template_naskah`
--

INSERT INTO `ref_template_naskah` (`id`, `jenis_id`, `nama_template`, `form_schema`, `kd_wilayah`, `kd_opd`, `username_insert`, `tgl_insert`, `username_update`, `tgl_update`, `keterangan`) VALUES
(1, 5, 'Template Penetapan', '[\r\n        {\"type\":\"auto_nomor\",\"label\":\"Nomor Surat\",\"name\":\"nomor\"},\r\n        {\"type\":\"date\",\"label\":\"Tanggal Surat\",\"name\":\"tanggal_surat\"},\r\n        {\"type\":\"text\",\"label\":\"Perihal\",\"name\":\"perihal\"},\r\n        {\"type\":\"dropdown_klasifikasi\",\"label\":\"Klasifikasi\",\"name\":\"klasifikasi_id\"},\r\n        {\"type\":\"editor\",\"label\":\"Isi Penetapan\",\"name\":\"isi\"}\r\n    ]', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `register_naskah_dinas`
--

CREATE TABLE `register_naskah_dinas` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(150) NOT NULL,
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
-- Struktur dari tabel `rekanan_neo`
--

CREATE TABLE `rekanan_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(25) NOT NULL,
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
  `notaris_perubahan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notaris_perubahan`)),
  `data_lain` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_lain`)),
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
-- Struktur dari tabel `renja_neo`
--

CREATE TABLE `renja_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(22) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_sub_keg` varchar(50) NOT NULL,
  `kd_akun` varchar(50) NOT NULL,
  `kel_rek` varchar(50) NOT NULL,
  `objek_belanja` varchar(255) NOT NULL,
  `uraian` text NOT NULL,
  `jenis_kelompok` varchar(255) NOT NULL,
  `kelompok` varchar(255) NOT NULL,
  `jenis_standar_harga` varchar(6) NOT NULL,
  `id_standar_harga` int(11) DEFAULT NULL,
  `komponen` varchar(400) DEFAULT NULL,
  `spesifikasi` varchar(400) DEFAULT NULL,
  `tkdn` decimal(36,12) DEFAULT NULL,
  `pajak` tinyint(1) DEFAULT NULL,
  `harga_satuan` decimal(36,12) NOT NULL,
  `vol_1` decimal(36,12) NOT NULL,
  `vol_2` decimal(36,12) DEFAULT NULL,
  `vol_3` decimal(36,12) DEFAULT NULL,
  `vol_4` decimal(36,12) DEFAULT NULL,
  `vol_5` decimal(36,12) DEFAULT NULL,
  `sat_1` varchar(50) NOT NULL,
  `sat_2` varchar(50) DEFAULT NULL,
  `sat_3` varchar(50) DEFAULT NULL,
  `sat_4` varchar(50) DEFAULT NULL,
  `sat_5` varchar(50) DEFAULT NULL,
  `volume` decimal(36,12) DEFAULT NULL,
  `jumlah` decimal(36,12) NOT NULL,
  `sumber_dana` varchar(255) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `renja_p_neo`
--

CREATE TABLE `renja_p_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(60) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_sub_keg` varchar(50) NOT NULL,
  `kd_akun` varchar(50) NOT NULL,
  `kel_rek` varchar(50) NOT NULL,
  `objek_belanja` varchar(255) NOT NULL,
  `uraian` text NOT NULL,
  `jenis_kelompok` varchar(255) NOT NULL,
  `kelompok` varchar(255) NOT NULL,
  `jenis_standar_harga` varchar(6) NOT NULL,
  `id_standar_harga` int(11) DEFAULT NULL,
  `komponen` varchar(400) DEFAULT NULL,
  `spesifikasi` varchar(400) DEFAULT NULL,
  `tkdn` decimal(36,12) DEFAULT NULL,
  `pajak` tinyint(1) DEFAULT NULL,
  `harga_satuan` decimal(36,12) NOT NULL,
  `vol_1` decimal(36,12) NOT NULL,
  `vol_2` decimal(36,12) DEFAULT NULL,
  `vol_3` decimal(36,12) DEFAULT NULL,
  `vol_4` decimal(36,12) DEFAULT NULL,
  `vol_5` decimal(36,12) DEFAULT NULL,
  `sat_1` varchar(50) NOT NULL,
  `sat_2` varchar(50) DEFAULT NULL,
  `sat_3` varchar(50) DEFAULT NULL,
  `sat_4` varchar(50) DEFAULT NULL,
  `sat_5` varchar(50) DEFAULT NULL,
  `volume` decimal(36,12) DEFAULT NULL,
  `jumlah` decimal(36,12) NOT NULL,
  `sumber_dana` varchar(255) DEFAULT NULL,
  `vol_1_p` decimal(36,12) DEFAULT NULL,
  `vol_2_p` decimal(36,12) DEFAULT NULL,
  `vol_3_p` decimal(36,12) DEFAULT NULL,
  `vol_4_p` decimal(36,12) DEFAULT NULL,
  `vol_5_p` decimal(36,12) DEFAULT NULL,
  `sat_1_p` varchar(50) DEFAULT NULL,
  `sat_2_p` varchar(50) DEFAULT NULL,
  `sat_3_p` varchar(50) DEFAULT NULL,
  `sat_4_p` varchar(50) DEFAULT NULL,
  `sat_5_p` varchar(50) DEFAULT NULL,
  `volume_p` decimal(36,12) DEFAULT NULL,
  `jumlah_p` decimal(36,12) DEFAULT NULL,
  `sumber_dana_p` varchar(255) DEFAULT NULL,
  `keterangan` varchar(400) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `username_update` varchar(100) DEFAULT NULL,
  `kunci` tinyint(1) DEFAULT 0,
  `setujui` tinyint(1) DEFAULT 0,
  `id_dpa` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `renstra_neo`
--

CREATE TABLE `renstra_neo` (
  `id` int(11) NOT NULL,
  `kd_wilayah` varchar(10) NOT NULL,
  `kd_opd` varchar(60) NOT NULL,
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
  `item` varchar(255) NOT NULL,
  `sebutan_lain` varchar(255) DEFAULT NULL,
  `disable` tinyint(1) NOT NULL,
  `aksi` varchar(50) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `peraturan` varchar(255) NOT NULL,
  `tgl_insert` datetime NOT NULL DEFAULT current_timestamp(),
  `username_insert` varchar(100) NOT NULL,
  `tgl_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username_update` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sbu_neo`
--

CREATE TABLE `sbu_neo` (
  `id` int(8) NOT NULL,
  `kd_wilayah` varchar(25) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_aset` varchar(25) NOT NULL,
  `uraian_barang` text NOT NULL,
  `spesifikasi` text DEFAULT NULL,
  `satuan` varchar(400) NOT NULL,
  `harga_satuan` decimal(18,6) NOT NULL,
  `tkdn` varchar(400) DEFAULT NULL,
  `merek` varchar(400) DEFAULT NULL,
  `kd_akun` text NOT NULL,
  `peraturan` varchar(255) NOT NULL,
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
-- Struktur dari tabel `sk_asn_neo`
--

CREATE TABLE `sk_asn_neo` (
  `id` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_wilayah` varchar(150) NOT NULL,
  `kd_opd` varchar(50) NOT NULL,
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
-- Struktur dari tabel `ssh_neo`
--

CREATE TABLE `ssh_neo` (
  `id` int(8) NOT NULL,
  `kd_wilayah` varchar(25) NOT NULL,
  `tahun` year(4) NOT NULL,
  `kd_aset` varchar(25) NOT NULL,
  `uraian_barang` text NOT NULL,
  `spesifikasi` text DEFAULT NULL,
  `satuan` varchar(400) NOT NULL,
  `harga_satuan` decimal(18,6) NOT NULL,
  `tkdn` varchar(400) DEFAULT NULL,
  `merek` varchar(400) DEFAULT NULL,
  `kd_akun` text NOT NULL,
  `peraturan` varchar(255) NOT NULL,
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
-- Struktur dari tabel `sub_kegiatan`
--

CREATE TABLE `sub_kegiatan` (
  `kode` varchar(25) NOT NULL,
  `kode_kegiatan` varchar(20) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `indikator` text DEFAULT NULL,
  `satuan` varchar(100) DEFAULT NULL,
  `peraturan` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sub_kegiatan_neo`
--

CREATE TABLE `sub_kegiatan_neo` (
  `id` int(8) NOT NULL,
  `urusan` varchar(2) NOT NULL,
  `bidang` varchar(2) NOT NULL,
  `prog` int(11) DEFAULT NULL,
  `keg` varchar(8) DEFAULT NULL,
  `sub_keg` int(11) DEFAULT NULL,
  `kode` varchar(25) NOT NULL,
  `nomenklatur_urusan` text NOT NULL,
  `kinerja` text DEFAULT NULL,
  `indikator` text DEFAULT NULL,
  `satuan` varchar(100) DEFAULT NULL,
  `peraturan` varchar(255) NOT NULL,
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
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(22) NOT NULL,
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
  `kd_wilayah` varchar(50) NOT NULL,
  `kd_opd` varchar(22) NOT NULL,
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
  `peraturan` varchar(255) NOT NULL,
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
  `jenis_id` int(11) DEFAULT NULL,
  `nomor` varchar(100) DEFAULT NULL,
  `nomor_urut` int(11) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `klasifikasi_id` int(11) DEFAULT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `perihal` varchar(255) DEFAULT NULL,
  `status` enum('draft','final') DEFAULT 'draft',
  `file_pdf` varchar(255) DEFAULT NULL,
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
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
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
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
  `kd_wilayah` varchar(20) DEFAULT NULL,
  `kd_opd` varchar(20) DEFAULT NULL,
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
-- Struktur dari tabel `urusan`
--

CREATE TABLE `urusan` (
  `kode` varchar(10) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `peraturan` int(11) NOT NULL,
  `tgl_insert` datetime DEFAULT current_timestamp(),
  `username_insert` varchar(100) DEFAULT NULL,
  `tgl_update` datetime DEFAULT NULL,
  `username_update` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `kd_opd` varchar(30) NOT NULL,
  `nama_org` varchar(400) NOT NULL,
  `kd_wilayah` varchar(255) NOT NULL,
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

INSERT INTO `user_sesendok_biila` (`id`, `username`, `email`, `nama`, `nip`, `password`, `remember_token`, `kd_opd`, `nama_org`, `kd_wilayah`, `type_user`, `photo`, `signature_image`, `signature_verified`, `tgl_daftar`, `tgl_login`, `tahun`, `kontak_person`, `alamat`, `font_size`, `theme`, `warna_tbl`, `scrolling_table`, `disable_login`, `disable_anggaran`, `disable_kontrak`, `disable_realisasi`, `disable_chat`, `ket`, `disable`) VALUES
(1, 'alwi_mansyur', 'alwi@gmail.com', 'Alwi Mansyur', '1980', '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, '1.03.0.00.0.00.01.0000', 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG', '76.01', 'admin_wilayah', 'images/avatar/default.jpeg', NULL, 0, '2018-06-04 21:57:05', '2024-10-23 15:03:04', '2024', 'pasangkayu ji', NULL, 90.00, 'auto', 'non', 'short', 0, 0, 0, 0, 1, 'apa yang dapat saya berikan', 0),
(2, 'nabiila', 'nabiila@gmail.com', 'Najwan Nabiila', '123456789012345678', '$2y$12$1qb72gQsUL.UlMLmkOZ8KOtPjhZhxDIf.AiY7kaD7zqs90GaAZJdy', NULL, '1.03.0.00.0.00.01.0000', 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG', '76.01', 'admin_opd', 'img/avatar/username(nabiila)_dok(photo)_wilayah(76.01)_2305070e99916190687b3774c0d56f134b954d74_2.jpg', NULL, 0, '2018-06-09 15:54:29', '2026-02-22 04:54:17', '2026', '08128888', NULL, 80.00, 'auto', 'non', 'short', 0, 0, 0, 0, 1, 'Apa yang dapat saya berikan untuk Pasangkayu', 0),
(3, 'inayah', 'inayah@gmail.com', 'Inayah Nadhilah', NULL, '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, '1.03.0.00.0.00.01.0000', 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG', '76.01', 'super_admin', 'images/avatar/default.jpeg', NULL, 0, '2018-06-22 22:04:17', '2020-03-08 02:30:41', '2026', '', NULL, 80.00, 'auto', NULL, 'short', 0, 0, 0, 0, 1, 'dimana mana hatiku senang oke', 0),
(4, 'Arlinda', 'arlinda@gmail.com', 'Arlinda Achmad', NULL, '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, '', 'Prof', '', 'admin_opd', 'images/avatar/default.jpeg', NULL, 0, '2018-07-10 14:27:06', '2018-10-21 12:23:09', '2024', '', NULL, 80.00, 'auto', NULL, 'short', 0, 0, 0, 0, 1, 'Apa yang dapat saya berikan untuk Pasangkayu.', 0),
(5, 'administrator', 'alwi.mansyur@gmail.com', 'administrator', NULL, '$2y$10$wkIJCe8dk3YaLaaIScBOBOAY4M8cLEyDsFm66Xhwo9U3p/wcik9Bi', NULL, '', 'administrator AHSP', '', 'user', 'images/avatar/c14719a7f71e46badf2cf93ae373ae9797281782_9.png', NULL, 0, '2023-02-09 23:41:34', '2023-02-23 00:05:26', '2024', '08128886665', NULL, 80.00, 'auto', 'non', 'short', 0, 0, 0, 0, 1, 'Apa yang dapat saya berikan untuk mu', 0);

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
  `is_deleted` tinyint(1) DEFAULT 0
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
-- Indeks untuk tabel `anggaran_program_renstra_neo`
--
ALTER TABLE `anggaran_program_renstra_neo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indeks untuk tabel `asb_neo`
--
ALTER TABLE `asb_neo`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `asb_neo` ADD FULLTEXT KEY `uraian_barang` (`uraian_barang`);

--
-- Indeks untuk tabel `aset_neo`
--
ALTER TABLE `aset_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `berita_neo`
--
ALTER TABLE `berita_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `bidang`
--
ALTER TABLE `bidang`
  ADD PRIMARY KEY (`kode`),
  ADD KEY `idx_kode_urusan` (`kode_urusan`);

--
-- Indeks untuk tabel `daftar_paket_neo`
--
ALTER TABLE `daftar_paket_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `daftar_realisasi_neo`
--
ALTER TABLE `daftar_realisasi_neo`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `dppa_neo`
--
ALTER TABLE `dppa_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `hspk_neo`
--
ALTER TABLE `hspk_neo`
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
-- Indeks untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`kode`),
  ADD KEY `idx_kode_program` (`kode_program`);

--
-- Indeks untuk tabel `kegiatan_renstra_neo`
--
ALTER TABLE `kegiatan_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `log_activity`
--
ALTER TABLE `log_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indeks untuk tabel `mapping_aset_akun`
--
ALTER TABLE `mapping_aset_akun`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `misi_renstra_neo`
--
ALTER TABLE `misi_renstra_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `naskah_dinas_neo`
--
ALTER TABLE `naskah_dinas_neo`
  ADD PRIMARY KEY (`id`);

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
-- Indeks untuk tabel `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`kode`),
  ADD KEY `idx_kode_bidang` (`kode_bidang`);

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
-- Indeks untuk tabel `rekanan_neo`
--
ALTER TABLE `rekanan_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `renja_neo`
--
ALTER TABLE `renja_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `renja_p_neo`
--
ALTER TABLE `renja_p_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `renstra_neo`
--
ALTER TABLE `renstra_neo`
  ADD PRIMARY KEY (`id`);

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
-- Indeks untuk tabel `sbu_neo`
--
ALTER TABLE `sbu_neo`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `sbu_neo` ADD FULLTEXT KEY `uraian_barang` (`uraian_barang`);

--
-- Indeks untuk tabel `sk_asn_neo`
--
ALTER TABLE `sk_asn_neo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ssh_neo`
--
ALTER TABLE `ssh_neo`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `ssh_neo` ADD FULLTEXT KEY `uraian_barang` (`uraian_barang`);

--
-- Indeks untuk tabel `sub_kegiatan`
--
ALTER TABLE `sub_kegiatan`
  ADD PRIMARY KEY (`kode`),
  ADD KEY `idx_kode_kegiatan` (`kode_kegiatan`);

--
-- Indeks untuk tabel `sub_kegiatan_neo`
--
ALTER TABLE `sub_kegiatan_neo`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `sub_kegiatan_neo` ADD FULLTEXT KEY `nomenklatur_urusan` (`nomenklatur_urusan`);

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
-- Indeks untuk tabel `urusan`
--
ALTER TABLE `urusan`
  ADD PRIMARY KEY (`kode`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

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
-- AUTO_INCREMENT untuk tabel `anggaran_program_renstra_neo`
--
ALTER TABLE `anggaran_program_renstra_neo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `asb_neo`
--
ALTER TABLE `asb_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `aset_neo`
--
ALTER TABLE `aset_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `berita_neo`
--
ALTER TABLE `berita_neo`
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
-- AUTO_INCREMENT untuk tabel `dpa_neo`
--
ALTER TABLE `dpa_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `dppa_neo`
--
ALTER TABLE `dppa_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `hspk_neo`
--
ALTER TABLE `hspk_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT untuk tabel `log_activity`
--
ALTER TABLE `log_activity`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `mapping_aset_akun`
--
ALTER TABLE `mapping_aset_akun`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `misi_renstra_neo`
--
ALTER TABLE `misi_renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `naskah_dinas_neo`
--
ALTER TABLE `naskah_dinas_neo`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `ref_jenis_naskah_dinas`
--
ALTER TABLE `ref_jenis_naskah_dinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_kelompok_naskah`
--
ALTER TABLE `ref_kelompok_naskah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ref_klasifikasi_keamanan`
--
ALTER TABLE `ref_klasifikasi_keamanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_template_naskah`
--
ALTER TABLE `ref_template_naskah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `register_naskah_dinas`
--
ALTER TABLE `register_naskah_dinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rekanan_neo`
--
ALTER TABLE `rekanan_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `renja_neo`
--
ALTER TABLE `renja_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `renja_p_neo`
--
ALTER TABLE `renja_p_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `renstra_neo`
--
ALTER TABLE `renstra_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT untuk tabel `sbu_neo`
--
ALTER TABLE `sbu_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sk_asn_neo`
--
ALTER TABLE `sk_asn_neo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ssh_neo`
--
ALTER TABLE `ssh_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sub_kegiatan_neo`
--
ALTER TABLE `sub_kegiatan_neo`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `user_sesendok_biila`
--
ALTER TABLE `user_sesendok_biila`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Ketidakleluasaan untuk tabel `trx_naskah_struktur`
--
ALTER TABLE `trx_naskah_struktur`
  ADD CONSTRAINT `fk_naskah_struktur` FOREIGN KEY (`naskah_id`) REFERENCES `trx_naskah_dinas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
