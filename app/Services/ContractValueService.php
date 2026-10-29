<?php
/** Allocation of an explicitly entered contract total, without changing RAB prices. */
final class ContractValueService
{
    public static function allocate(array $items,float $value):array
    {
        if(!is_finite($value)||$value<=0)throw new InvalidArgumentException('Nilai kontrak harus lebih besar dari nol.');
        $total=array_sum(array_column($items,'nilai_kontrak'));
        if(!$items||$total<=0)return [];
        $remaining=(int)round($value*100);$result=[];
        foreach($items as $i=>$row){$cents=$i===array_key_last($items)?$remaining:(int)round($value*100*(float)$row['nilai_kontrak']/$total);$remaining-=$cents;$amount=$cents/100;if($amount<=0||$amount>(float)$row['pagu'])throw new InvalidArgumentException('Nilai kontrak per uraian harus positif dan tidak melampaui pagu.');$result[]=['id'=>$row['id'],'nilai_kontrak'=>$amount];}
        return $result;
    }
    public static function sync(DB $db,int $id,float $value):void
    {
        $rows=$db->query('SELECT id,pagu,nilai_kontrak FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY id FOR UPDATE',[$id])->fetchAll();
        foreach(self::allocate($rows,$value) as $r)$db->update('kontrak_item_neo',['nilai_kontrak'=>$r['nilai_kontrak']],'WHERE id=?',[$r['id']]);
    }
}
