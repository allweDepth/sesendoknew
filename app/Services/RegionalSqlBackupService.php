<?php

require_once __DIR__.'/../Core/DB.php';

final class RegionalSqlBackupService
{
    private const SCOPED_TABLES = [
        'absensi_pegawai_neo', 'akun_dokumen_neo', 'anggaran_approval_log_neo', 'anggaran_pengesahan_neo',
        'anggaran_perubahan_template', 'anggaran_template', 'anggaran_workflow_log', 'batas_pagu_opd_neo',
        'cuti_pegawai_neo', 'daftar_paket_neo', 'daftar_realisasi_neo', 'daftar_realisasi_transaksi_neo',
        'daftar_uraian_paket', 'db_asn_pemda_neo', 'dokumen_pegawai_neo', 'dokumen_pengadaan_neo',
        'dpa_neo', 'dppa_neo', 'evaluasi_renja_neo', 'evaluasi_renstra_neo', 'group_rekap_akun', 'group_sub_kegiatan',
        'halaman_berita', 'iku_opd_neo', 'kontrak_dokumen_neo', 'kontrak_item_neo', 'kontrak_jadwal_neo',
        'kontrak_neo', 'kop_surat_neo', 'laporan_calk_neo', 'laporan_keuangan_transaksi_neo',
        'laporan_neraca_saldo_neo', 'maps_layers', 'master_biaya', 'master_biaya_akun', 'organisasi_neo',
        'page_setup_opd_neo', 'pejabat_tahunan_neo', 'pengaturan_neo', 'pengukuran_kinerja_neo', 'peraturan_neo',
        'periode_rpjmd', 'perjanjian_kinerja_detail_neo', 'perjanjian_kinerja_neo', 'pimpinan_wilayah_neo',
        'pohon_kinerja_neo', 'rab_paket_neo', 'realisasi_dokumen_neo', 'ref_jenis_naskah', 'ref_jenis_naskah_dinas',
        'ref_kelompok_naskah', 'ref_klasifikasi_keamanan', 'ref_template_naskah', 'register_naskah_dinas',
        'rekanan_neo', 'rekening_kegiatan', 'rekening_kegiatan_historis', 'rencana_realisasi_anggaran_neo',
        'rencana_rekening_anggaran_neo', 'renja_neo', 'renja_p_neo', 'renja_sub_kegiatan_kinerja_neo',
        'renstra_neo', 'riwayat_jabatan_neo', 'riwayat_pangkat_neo', 'rka_neo', 'rka_pendapatan_neo', 'rka_p_neo',
        'rkpd_neo', 'rkpd_p_neo', 'rpjmd_kabupaten_neo', 'sk_asn_neo', 'sk_pegawai_neo', 'struktur_jabatan_opd_neo',
        'sub_keg_dpa_neo', 'sub_keg_renja_neo', 'tapd_penugasan_neo', 'trx_naskah_dinas', 'trx_naskah_meta',
        'trx_naskah_struktur', 'user_subkegiatan_neo', 'usulan_pembangunan_neo',
    ];

    // Setiap pasangan berisi nama kolom induk dan tabel induknya.
    private const PARENTS = [
        'analisa_rab' => ['kontrak_id', 'kontrak_neo'],
        'anggaran_pengesahan_rincian_neo' => ['pengesahan_id', 'anggaran_pengesahan_neo'],
        'anggaran_program_renstra_neo' => ['program_id', 'program_renstra_neo'],
        'dokumen_pengadaan_bagian_neo' => ['dokumen_id', 'dokumen_pengadaan_neo'],
        'dokumen_pengadaan_lampiran_neo' => ['dokumen_id', 'dokumen_pengadaan_neo'],
        'dokumen_pengadaan_paragraf_neo' => ['dokumen_bagian_id', 'dokumen_pengadaan_bagian_neo'],
        'indikator_program_renstra_neo' => ['program_id', 'program_renstra_neo'],
        'indikator_sasaran_renstra_neo' => ['sasaran_id', 'sasaran_renstra_neo'],
        'kegiatan_renstra_neo' => ['program_id', 'program_renstra_neo'],
        'kontrak_print_settings_neo' => ['kontrak_id', 'kontrak_neo'],
        'kontrak_rab_total_neo' => ['kontrak_id', 'kontrak_neo'],
        'master_biaya_akun' => ['master_biaya_id', 'master_biaya'],
        'misi_renstra_neo' => ['renstra_id', 'renstra_neo'],
        'program_renstra_neo' => ['sasaran_id', 'sasaran_renstra_neo'],
        'rekanan_akta' => ['rekanan_id', 'rekanan_neo'],
        'sasaran_renstra_neo' => ['tujuan_id', 'tujuan_renstra_neo'],
        'sub_kegiatan_renstra_neo' => ['kegiatan_renstra_id', 'kegiatan_renstra_neo'],
        'trx_naskah_status_history' => ['naskah_id', 'trx_naskah_dinas'],
        'tujuan_renstra_neo' => ['misi_id', 'misi_renstra_neo'],
    ];

    /** @param resource $output */
    public static function export(string $region, $output): void
    {
        if (!preg_match('/^\d+(?:\.\d+)*$/D', $region) || !preg_match('/[1-9]/', $region)) {
            throw new InvalidArgumentException('Kode wilayah akun tidak valid.');
        }

        $db = DB::getInstance();
        $available = $db->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        $tables = array_intersect($available, array_merge(self::SCOPED_TABLES, array_keys(self::PARENTS)));

        $db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');
        $db->begin();
        try {
            self::write($output, "-- Backup SQL wilayah $region\n"
                ."-- Data seluruh OPD dan tahun untuk wilayah ini.\n"
                ."-- Impor ke database kosong. Referensi bersama, akun login, dan file unggahan disediakan terpisah.\n\n"
                ."SET NAMES utf8mb4;\n"
                ."SET @BACKUP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n"
                ."SET @BACKUP_FK_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $table) {
                $ddl = $db->query("SHOW CREATE TABLE `$table`")->fetch()['Create Table'];
                $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $ddl);
                self::write($output, $ddl.";\n\n");
            }

            self::write($output, "START TRANSACTION;\n");
            foreach ($tables as $table) {
                $columns = $db->query(
                    "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND IS_GENERATED='NEVER' ORDER BY ORDINAL_POSITION",
                    [$table]
                )->fetchAll(PDO::FETCH_KEY_PAIR);
                $names = implode(', ', array_map(fn($name) => '`'.str_replace('`', '``', $name).'`', array_keys($columns)));
                $where = self::scope($table, 'item', $available);
                $sql = "SELECT $names FROM `$table` AS item WHERE $where";
                $insert = "INSERT INTO `$table` ($names) VALUES ";
                $batch = '';
                foreach ($db->streamRows($sql, array_fill(0, substr_count($where, '?'), $region)) as $row) {
                    $values = [];
                    foreach ($row as $name => $value) {
                        $values[] = self::literal($value, $columns[$name]);
                    }
                    $tuple = '('.implode(', ', $values).')';
                    if ($batch !== '' && strlen($batch) + strlen($tuple) > 256 * 1024) {
                        self::write($output, $insert.$batch.";\n");
                        $batch = '';
                    }
                    $batch .= ($batch === '' ? '' : ",\n").$tuple;
                }
                if ($batch !== '') self::write($output, $insert.$batch.";\n");
            }
            self::write($output, "COMMIT;\nSET FOREIGN_KEY_CHECKS=@BACKUP_FK_CHECKS;\nSET SQL_MODE=@BACKUP_SQL_MODE;\n");
            $db->commit();
        } catch (Throwable $error) {
            $db->rollback();
            throw $error;
        }
    }

    private static function scope(string $table, string $alias, array $available, int $depth = 0): string
    {
        $where = in_array($table, self::SCOPED_TABLES, true) ? ["BINARY $alias.kd_wilayah = BINARY ?"] : [];
        if (isset(self::PARENTS[$table])) {
            [$column, $parent] = self::PARENTS[$table];
            if (!in_array($parent, $available, true)) return '0=1';
            $parentAlias = 'p'.$depth;
            $scope = self::scope($parent, $parentAlias, $available, $depth + 1);
            $where[] = "EXISTS (SELECT 1 FROM `$parent` AS $parentAlias WHERE $parentAlias.id=$alias.`$column` AND $scope)";
        }
        return implode(' AND ', $where);
    }

    private static function literal($value, string $type): string
    {
        if ($value === null) return 'NULL';
        if ($type === 'bit') return "b'".decbin((int)$value)."'";
        if (in_array($type, ['binary','varbinary','tinyblob','blob','mediumblob','longblob'], true)) {
            return "X'".bin2hex((string)$value)."'";
        }
        return "'".strtr((string)$value, ["\\"=>"\\\\", "'"=>"\\'", "\0"=>"\\0", "\n"=>"\\n", "\r"=>"\\r", "\x1a"=>"\\Z"])."'";
    }

    /** @param resource $output */
    private static function write($output, string $sql): void
    {
        if (fwrite($output, $sql) !== strlen($sql)) throw new RuntimeException('File backup tidak dapat ditulis.');
    }
}
