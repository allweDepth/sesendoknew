<?php
require_once __DIR__.'/../Core/DB.php';
final class ContractPrintSettingsService
{
    public const DEFAULTS=['paragraph_before'=>0.0,'paragraph_after'=>6.0,'line_spacing'=>1.5];
    public static function validate(array $input):array
    {
        $out=[];
        foreach(self::DEFAULTS as $key=>$default){
            $value=$input[$key]??$default;
            if(!is_numeric($value)||!is_finite((float)$value))throw new InvalidArgumentException('Pengaturan spasi harus berupa angka');
            $value=(float)$value;$min=$key==='line_spacing'?0.5:0;$max=$key==='line_spacing'?3:72;
            if($value<$min||$value>$max)throw new InvalidArgumentException('Nilai spasi di luar batas yang diizinkan');
            $out[$key]=$value;
        }
        return $out;
    }
    public static function get(int $id):array
    {
        $row=DB::getInstance()->query('SELECT settings_json FROM kontrak_print_settings_neo WHERE kontrak_id=?',[$id])->fetch();
        return self::validate($row?json_decode($row['settings_json'],true,512,JSON_THROW_ON_ERROR):[]);
    }
    public static function save(int $id,array $input,string $actor):array
    {
        $settings=self::validate($input);
        $row=DB::getInstance()->query('SELECT settings_json FROM kontrak_print_settings_neo WHERE kontrak_id=?',[$id])->fetch();
        $stored=$row?json_decode($row['settings_json'],true,512,JSON_THROW_ON_ERROR):[];
        DB::getInstance()->query('INSERT INTO kontrak_print_settings_neo (kontrak_id,settings_json,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE settings_json=VALUES(settings_json),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP',[$id,json_encode(array_replace($stored,$settings)),$actor]);
        return $settings;
    }
}
