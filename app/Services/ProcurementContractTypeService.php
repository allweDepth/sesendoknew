<?php
require_once __DIR__.'/FieldValidationException.php';
/** Jenis kontrak (Pasal 27) is separate from its document form (Pasal 28). */
final class ProcurementContractTypeService
{
    public const LABELS=['LUMSUM'=>'Lumsum','HARGA_SATUAN'=>'Harga Satuan','GABUNGAN'=>'Gabungan Lumsum dan Harga Satuan','PAYUNG'=>'Kontrak Payung','BIAYA_PLUS_IMBALAN'=>'Biaya Plus Imbalan','BERBASIS_KINERJA'=>'Kontrak Berbasis Kinerja','PUTAR_KUNCI'=>'Putar Kunci','MODIFIKASI_PUTAR_KUNCI'=>'Modifikasi Putar Kunci','WAKTU_PENUGASAN'=>'Waktu Penugasan','PERJANJIAN_SWAKELOLA'=>'Perjanjian Swakelola'];
    public static function options(array $contract):array
    {
        if(strtoupper($contract['cara_pengadaan']??'PENYEDIA')==='SWAKELOLA')$keys=['PERJANJIAN_SWAKELOLA'];
        elseif(str_starts_with(strtoupper($contract['jenis_pengadaan']??''),'KONSULTANSI'))$keys=['LUMSUM','WAKTU_PENUGASAN','PAYUNG','BERBASIS_KINERJA'];
        elseif(strtoupper($contract['jenis_pengadaan']??'')==='PEKERJAAN_KONSTRUKSI')$keys=['HARGA_SATUAN','LUMSUM','GABUNGAN','PUTAR_KUNCI','BIAYA_PLUS_IMBALAN','MODIFIKASI_PUTAR_KUNCI','PAYUNG','BERBASIS_KINERJA'];
        else $keys=['LUMSUM','HARGA_SATUAN','GABUNGAN','PAYUNG','BIAYA_PLUS_IMBALAN','BERBASIS_KINERJA'];
        return array_intersect_key(self::LABELS,array_flip($keys));
    }
    public static function normalize(array $contract):string
    {
        $options=self::options($contract);$value=strtoupper(trim((string)($contract['jenis_kontrak']??'')));
        if($value==='')return isset($options['PERJANJIAN_SWAKELOLA'])?'PERJANJIAN_SWAKELOLA':(($contract['jenis_pengadaan']??'')==='PEKERJAAN_KONSTRUKSI'?'HARGA_SATUAN':'LUMSUM');
        if(!isset($options[$value]))throw new FieldValidationException('jenis_kontrak','Jenis kontrak tidak sesuai cara dan jenis pengadaan. Pilih kembali jenis kontrak.');
        return $value;
    }
    public static function label(array $contract):string{return self::LABELS[self::normalize($contract)];}
}
