<?php

class AnggaranPerubahanService
{

    /*
    |--------------------------------------------------------------------------
    | Update Rincian Perubahan
    |--------------------------------------------------------------------------
    */

    public static function update($table,$id,$data)
    {

        $db = DB::getInstance();

        // ambil data lama
        $row = $db->query("
            SELECT *
            FROM {$table}
            WHERE id=?
        ",[$id])->fetch();

        /*
        ---------------------------------------------------------
        jika ada perubahan nilai
        ---------------------------------------------------------
        */

        if(
            $row['volume'] != $data['volume'] ||
            $row['harga_satuan'] != $data['harga_satuan']
        ){
            $data['status_perubahan'] = 'ubah';
        }

        /*
        ---------------------------------------------------------
        update data
        ---------------------------------------------------------
        */

        $db->update($table,$data,"id=?",[$id]);

    }



    /*
    |--------------------------------------------------------------------------
    | Tambah Rincian Baru
    |--------------------------------------------------------------------------
    */

    public static function tambah($table,$data)
    {

        $db = DB::getInstance();

        $data['status_perubahan']='tambah';

        $db->insert($table,$data);

    }



    /*
    |--------------------------------------------------------------------------
    | Hapus Rincian
    |--------------------------------------------------------------------------
    */

    public static function hapus($table,$id)
    {

        $db = DB::getInstance();

        $db->update($table,[
            'status_perubahan'=>'hapus',
            'jumlah'=>0
        ],"id=?",[$id]);

    }

}