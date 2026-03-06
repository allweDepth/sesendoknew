<?php

class AnggaranHierarchyService
{

    public static function getSubKegiatan($table,$tahun,$opd)
    {

        $db = DB::getInstance();

        $sql = "
        SELECT
            kd_sub_keg,
            SUM(jumlah) total
        FROM {$table}
        WHERE tahun=?
        AND kd_opd=?
        AND is_deleted=0
        GROUP BY kd_sub_keg
        ORDER BY kd_sub_keg
        ";

        return $db->query($sql,[$tahun,$opd])->fetchAll();

    }



    public static function getRekapAkun($table,$kd_sub_keg)
    {

        $db = DB::getInstance();

        $sql = "
        SELECT
            kd_akun,
            uraian,
            SUM(jumlah) total
        FROM {$table}
        WHERE kd_sub_keg=?
        AND is_deleted=0
        GROUP BY kd_akun
        ORDER BY kd_akun
        ";

        return $db->query($sql,[$kd_sub_keg])->fetchAll();

    }



    public static function getRincian($table,$kd_sub_keg,$kd_akun)
    {

        $db = DB::getInstance();

        $sql = "
        SELECT
            id,
            komponen,
            volume,
            sat_1,
            harga_satuan,
            jumlah
        FROM {$table}
        WHERE kd_sub_keg=?
        AND kd_akun=?
        AND is_deleted=0
        ORDER BY id
        ";

        return $db->query($sql,[$kd_sub_keg,$kd_akun])->fetchAll();

    }

}