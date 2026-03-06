<?php

class AnggaranGroupService
{

    /*
    |--------------------------------------------------------------------------
    | Generate Group Sub Kegiatan
    |--------------------------------------------------------------------------
    */

    public static function generateSubKegiatan($table)
    {

        // ambil instance database
        $db = DB::getInstance();

        // hapus group lama
        $db->query(
            "DELETE FROM group_sub_kegiatan WHERE tahap=?",
            [$table]
        );

        // generate ulang
        $sql = "

        INSERT INTO group_sub_kegiatan
        (
            kd_sub_keg,
            total_anggaran,
            tahap
        )

        SELECT
            kd_sub_keg,
            SUM(jumlah),
            ?

        FROM {$table}

        WHERE disable=0

        GROUP BY kd_sub_keg

        ";

        $db->query($sql, [$table]);

    }



    /*
    |--------------------------------------------------------------------------
    | Generate Rekap Akun
    |--------------------------------------------------------------------------
    */

    public static function generateRekapAkun($table)
    {

        $db = DB::getInstance();

        $db->query(
            "DELETE FROM group_rekap_akun WHERE tahap=?",
            [$table]
        );

        $sql = "

        INSERT INTO group_rekap_akun
        (
            kd_sub_keg,
            kd_akun,
            total_anggaran,
            tahap
        )

        SELECT
            kd_sub_keg,
            kd_akun,
            SUM(jumlah),
            ?

        FROM {$table}

        WHERE disable=0

        GROUP BY kd_sub_keg,kd_akun

        ";

        $db->query($sql, [$table]);

    }

}