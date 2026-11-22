<?php

/** One choice shared by the recommendations, cover and bundled main document. */
final class ProcurementContractFormService
{
    public static function recommend(array $c):string
    {
        $kind=strtoupper($c['jenis_pengadaan']??'BARANG');
        $value=(float)($c['nilai_kontrak']??0);
        if(strtoupper($c['cara_pengadaan']??'PENYEDIA')==='SWAKELOLA')return 'PERJANJIAN_SWAKELOLA';
        $catalog=preg_match('/katalog|mini[ _-]?kompetisi|e[ _-]?(?:purchas|catalog)/iu',$c['metode_pemilihan']??'');
        // A construction catalogue order may carry a formal construction agreement.
        if($kind==='PEKERJAAN_KONSTRUKSI'&&$value>400000000)return 'SURAT_PERJANJIAN';
        if($catalog)return 'SURAT_PESANAN';
        if(str_contains($kind,'KONSULTANSI'))return $value<=100000000?'SPK':'SURAT_PERJANJIAN';
        if($kind==='PEKERJAAN_KONSTRUKSI')return 'SPK';
        if($value<=10000000)return 'BUKTI_PEMBELIAN';
        if($value<=50000000)return 'KUITANSI';
        return $value<=200000000?'SPK':'SURAT_PERJANJIAN';
    }
    public static function label(string $form):string
    {
        return ['SPK'=>'SURAT PERINTAH KERJA (SPK)','SURAT_PERJANJIAN'=>'SURAT PERJANJIAN','SURAT_PESANAN'=>'SURAT PESANAN','BUKTI_PEMBELIAN'=>'BUKTI PEMBELIAN','KUITANSI'=>'KUITANSI','PERJANJIAN_SWAKELOLA'=>'PERJANJIAN SWAKELOLA'][$form]??'DOKUMEN KONTRAK';
    }
}
