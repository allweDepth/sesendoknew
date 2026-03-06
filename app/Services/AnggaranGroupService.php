<?php

class AnggaranGroupService
{

    public static function generateSubKegiatan($table)
    {

        $db = DB::getInstance();

        $sql="
        INSERT INTO group_sub_kegiatan
        (
            kd_sub_keg,
            total_anggaran
        )
        SELECT
            kd_sub_keg,
            SUM(jumlah)
        FROM {$table}
        WHERE is_deleted=0
        GROUP BY kd_sub_keg
        ";

        $db->query($sql);

    }



    public static function generateRekapAkun($table)
    {

        $db = DB::getInstance();

        $sql="
        INSERT INTO group_rekap_akun
        (
            kd_sub_keg,
            kd_akun,
            total_anggaran
        )
        SELECT
            kd_sub_keg,
            kd_akun,
            SUM(jumlah)
        FROM {$table}
        WHERE is_deleted=0
        GROUP BY kd_sub_keg,kd_akun
        ";

        $db->query($sql);

    }

}