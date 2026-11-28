<?php
/** Database defaults only; saved documents remain independent snapshots. */
final class SwakelolaDefaultService
{
    public static function blocks(DB $db,string $code,string $section):?array
    {
        try{$rows=$db->query('SELECT * FROM swakelola_teks_default_neo WHERE kode_dokumen=? AND kode_bagian=? AND aktif=1 ORDER BY urutan,kode_lokasi',[$code,$section])->fetchAll();}
        catch(DatabaseError $e){if(in_array((string)$e->getPrevious()->getCode(),['42S02','42P01'],true))return null;throw $e;}
        if(!$rows)return null;
        $children=[];foreach($rows as$r)$children[$r['lokasi_induk']??''][]=$r;
        $build=function(string$parent)use(&$build,$children):array{$out=[];foreach($children[$parent]??[]as$r){$b=json_decode($r['format_json']??'{}',true,512,JSON_THROW_ON_ERROR)?:[];$b['type']=$r['jenis_blok'];if($b['type']==='paragraph')$b['text']=$r['teks_default']??'';elseif($b['type']==='table'){$b['rows']=[];foreach($children[$r['kode_lokasi']]??[]as$row){$cells=[];foreach($children[$row['kode_lokasi']]??[]as$cell){$meta=json_decode($cell['format_json']??'{}',true)?:[];$cells[]=$meta+['blocks'=>$build($cell['kode_lokasi'])];}$b['rows'][]=$cells;}}$out[]=$b;}return$out;};
        return$build('');
    }

    public static function settings(DB$db,string$code):array
    {
        $type=explode('_',$code)[1]??'';$out=[];
        try{foreach($db->query("SELECT kunci,nilai_json FROM swakelola_pengaturan_default_neo WHERE tipe IN ('SEMUA',?) ORDER BY (tipe='SEMUA') DESC",[$type])->fetchAll()as$r)$out[$r['kunci']]=json_decode($r['nilai_json'],true,512,JSON_THROW_ON_ERROR);}
        catch(DatabaseError$e){if(!in_array((string)$e->getPrevious()->getCode(),['42S02','42P01'],true))throw$e;}
        return$out;
    }

    /** Resolve defaults at draft creation so later global edits cannot change saved layouts. */
    public static function apply(array$blocks,array$settings):array
    {
        foreach($blocks as&$b){if($b['type']==='paragraph')$b['word_format']=($b['word_format']??[])+($settings['paragraf']??[]);else foreach($b['rows']as&$row)foreach($row as&$cell)$cell['blocks']=self::apply($cell['blocks'],$settings);unset($row,$cell);}unset($b);return$blocks;
    }
}
