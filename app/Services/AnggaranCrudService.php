<?php

class AnggaranCrudService
{

    public static function store($table,$data)
    {

        $db = DB::getInstance();

        $data['tgl_insert']=date('Y-m-d H:i:s');

        return $db->insert($table,$data);

    }


    public static function update($table,$id,$data)
    {

        $db = DB::getInstance();

        $data['tgl_update']=date('Y-m-d H:i:s');

        return $db->update($table,$data,"id=?",[$id]);

    }


    public static function delete($table,$id)
    {

        $db = DB::getInstance();

        return $db->update($table,[
            'is_deleted'=>1
        ],"id=?",[$id]);

    }

}