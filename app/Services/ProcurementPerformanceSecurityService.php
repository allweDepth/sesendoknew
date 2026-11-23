<?php

/** Ordinary construction: Perpres 46/2025 consolidation, Articles 30 and 33. */
final class ProcurementPerformanceSecurityService
{
    public static function construction(array $c):?array
    {
        $contract=(float)($c['nilai_kontrak']??0);
        if(($c['jenis_pengadaan']??'')!=='PEKERJAAN_KONSTRUKSI'||$contract<=200000000)return null;
        $hps=(float)($c['nilai_hps']??0);
        if($hps<=0)throw new InvalidArgumentException('Nilai HPS wajib diisi untuk menghitung Jaminan Pelaksanaan pada SPPBJ.');
        $below=$contract < 0.8*$hps;
        $days=(int)($c['waktu_pelaksanaan']??0);
        if($days<=0)throw new InvalidArgumentException('Jangka waktu pelaksanaan wajib diisi untuk masa berlaku Jaminan Pelaksanaan pada SPPBJ.');
        return ['amount'=>round(0.05*($below?$hps:$contract),2),'basis'=>$below?'HPS':'nilai Kontrak','days'=>$days];
    }
}
