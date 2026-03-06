<?php

class AnggaranCopyService
{

    /*
    |--------------------------------------------------------------------------
    | Copy Dokumen Anggaran
    |--------------------------------------------------------------------------
    | $from = tabel asal
    | $to   = tabel tujuan
    | $tahun
    */

    public static function copy($from,$to,$tahun)
    {

        $db = DB::getInstance();

        /*
        -----------------------------------------------------------
        Copy data anggaran
        -----------------------------------------------------------
        */

        $sql="

        INSERT INTO {$to}
        (
            kd_wilayah,
            kd_opd,
            tahun,
            kd_sub_keg,
            kd_akun,
            kel_rek,
            objek_belanja,
            uraian,
            jenis_kelompok,
            kelompok,
            jenis_standar_harga,
            id_standar_harga,
            komponen,
            spesifikasi,
            tkdn,
            pajak,
            harga_satuan,
            vol_1,
            vol_2,
            vol_3,
            vol_4,
            vol_5,
            sat_1,
            sat_2,
            sat_3,
            sat_4,
            sat_5,
            volume,
            jumlah,
            sumber_dana_id,
            keterangan,
            disable,
            tgl_insert,
            username_insert
        )

        SELECT
            kd_wilayah,
            kd_opd,
            tahun,
            kd_sub_keg,
            kd_akun,
            kel_rek,
            objek_belanja,
            uraian,
            jenis_kelompok,
            kelompok,
            jenis_standar_harga,
            id_standar_harga,
            komponen,
            spesifikasi,
            tkdn,
            pajak,
            harga_satuan,
            vol_1,
            vol_2,
            vol_3,
            vol_4,
            vol_5,
            sat_1,
            sat_2,
            sat_3,
            sat_4,
            sat_5,
            volume,
            jumlah,
            sumber_dana_id,
            keterangan,
            disable,
            NOW(),
            'system'

        FROM {$from}

        WHERE tahun=?

        ";

        $stmt=$db->query($sql,[$tahun]);

        /*
        -----------------------------------------------------------
        Hitung jumlah data yang dicopy
        -----------------------------------------------------------
        */

        $jumlah=$db->query("
        SELECT COUNT(*) jml
        FROM {$to}
        WHERE tahun=?
        ",[$tahun])->fetch()['jml'];


        /*
        -----------------------------------------------------------
        Simpan log copy
        -----------------------------------------------------------
        */

        $db->insert("anggaran_copy_log",[

            'from_tahap'=>$from,
            'to_tahap'=>$to,
            'tahun'=>$tahun,
            'jumlah_data'=>$jumlah,
            'username'=>'system',
            'tgl_copy'=>date('Y-m-d H:i:s')

        ]);

    }

}