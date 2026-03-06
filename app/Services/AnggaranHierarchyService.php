<?php

/*
|--------------------------------------------------------------------------
| HIERARCHY SERVICE
|--------------------------------------------------------------------------
| Struktur SIPD
|
| Sub Kegiatan
|   ↓
| Rekap Akun
|   ↓
| Rincian Komponen
|--------------------------------------------------------------------------
*/

class AnggaranHierarchyService
{

    public static function subKegiatan($table)
    {

        $db = DB::getInstance();

        $sql = "
        SELECT
        kd_sub_keg,
        SUM(jumlah) total
        FROM {$table}
        WHERE is_deleted=0
        GROUP BY kd_sub_keg
        ORDER BY kd_sub_keg
        ";

        return $db->query($sql)->fetchAll();

    }



    public static function rekapAkun($table,$sub)
    {

        $db = DB::getInstance();

        $sql = "
        SELECT
        kd_akun,
        uraian,
        SUM(jumlah) total
        FROM {$table}
        WHERE kd_sub_keg=?
        GROUP BY kd_akun
        ";

        return $db->query($sql,[$sub])->fetchAll();

    }



    public static function rincian($table,$sub,$akun)
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
        ORDER BY id
        ";

        return $db->query($sql,[$sub,$akun])->fetchAll();

    }

}