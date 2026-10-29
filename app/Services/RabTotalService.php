<?php
require_once __DIR__.'/../Core/DB.php';
final class RabTotalService
{
    public static function totals(int $id):array
    {
        $db=DB::getInstance();$raw=$db->query("SELECT COUNT(*) baris,ROUND(SUM(COALESCE(jumlah_hps,0)),2) hps,ROUND(SUM(COALESCE(jumlah_penawaran,jumlah_negoisasi,0)),2) penawaran,ROUND(SUM(COALESCE(jumlah_negoisasi,0)),2) negoisasi FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 AND COALESCE(type,'')<>'KETERANGAN'",[$id])->fetch();
        $result=[];foreach(['hps','penawaran','negoisasi']as$s)$result[$s]=['raw'=>(float)($raw[$s]??0),'total'=>(float)($raw[$s]??0),'manual'=>false];
        foreach($db->query('SELECT tahap,nilai FROM kontrak_rab_total_neo WHERE kontrak_id=?',[$id])->fetchAll()as$r){$result[$r['tahap']]['total']=(float)$r['nilai'];$result[$r['tahap']]['manual']=true;}
        return ['baris'=>(int)($raw['baris']??0),'stages'=>$result];
    }
    public static function validate(float $value,float $raw,float $pagu):void
    {
        if(!is_finite($value)||$value<=0||$value>$pagu||$raw<=0)throw new InvalidArgumentException('Nilai pembulatan harus positif, memiliki rincian RAB, dan tidak melampaui pagu.');
    }
}
