<?php
/** Pasang trigger akumulasi kontrak disetujui per uraian DPA/DPPA. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../app/Core/DB.php';
$db=DB::getInstance();
$path=__DIR__.'/../database/migrations/20260907_phase50_approved_contract_budget_guards.sql';
$lines=file($path,FILE_IGNORE_NEW_LINES); $delimiter=';'; $buffer='';
foreach($lines as $line){
    $trim=trim($line);
    if(str_starts_with($trim,'DELIMITER ')){ $delimiter=trim(substr($trim,10)); continue; }
    if($trim===''||str_starts_with($trim,'--'))continue;
    $buffer.=$line."\n";
    if(str_ends_with(rtrim($line),$delimiter)){
        $sql=trim(substr(rtrim($buffer),0,-strlen($delimiter)));
        if($sql!=='')$db->query($sql);
        $buffer='';
    }
}
if(trim($buffer)!=='')throw new RuntimeException('SQL migrasi tidak lengkap');
$invalid=$db->query("SELECT COUNT(*) n FROM (SELECT ci.tahap,ci.anggaran_id,SUM(ci.nilai_kontrak) nilai,MAX(COALESCE(d.jumlah,p.jumlah)) pagu FROM kontrak_item_neo ci JOIN kontrak_neo k ON k.id=ci.kontrak_id AND k.setujui=1 AND k.is_deleted=0 LEFT JOIN dpa_neo d ON BINARY ci.tahap=BINARY 'dpa' AND d.id=ci.anggaran_id AND d.is_deleted=0 LEFT JOIN dppa_neo p ON BINARY ci.tahap=BINARY 'dppa' AND p.id=ci.anggaran_id AND p.is_deleted=0 WHERE ci.is_deleted=0 GROUP BY ci.tahap,ci.anggaran_id HAVING nilai>pagu) x")->fetch();
if((int)($invalid['n']??0)>0)throw new RuntimeException('Ada akumulasi kontrak disetujui yang sudah melebihi pagu');
echo "Trigger akumulasi kontrak disetujui terpasang; pelanggaran aktif: 0.\n";
