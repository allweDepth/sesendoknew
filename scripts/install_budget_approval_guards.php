<?php
/** Replace only temporal budget checks; retain provider, scope and spending ceilings. */
if(PHP_SAPI!=='cli')exit;require __DIR__.'/../app/Core/DB.php';$db=DB::getInstance();$out=__DIR__.'/../outputs/budget-approvals-20261029';if(!is_dir($out))mkdir($out,0775,true);
$db->query("CREATE OR REPLACE FUNCTION fn_contract_approval(w VARCHAR(60),o VARCHAR(60),y INT,d DATE) RETURNS BIGINT READS SQL DATA BEGIN DECLARE chosen BIGINT DEFAULT 0; SELECT id INTO chosen FROM anggaran_pengesahan_neo WHERE BINARY kd_wilayah=BINARY w AND BINARY kd_opd=BINARY o AND tahun=y AND status='DISAHKAN' AND tanggal<=d ORDER BY tanggal DESC,CASE jenis WHEN 'PERUBAHAN' THEN 2 WHEN 'PARSIAL' THEN 1 ELSE 0 END DESC,urutan DESC,id DESC LIMIT 1; RETURN COALESCE(chosen,0); END");
foreach($db->query("SELECT TRIGGER_NAME,ACTION_STATEMENT,EVENT_MANIPULATION,EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE IN ('kontrak_neo','kontrak_item_neo')")->fetchAll() as$t){$name=$t['TRIGGER_NAME'];$body=$t['ACTION_STATEMENT'];if(str_contains($body,'TEMPORAL_APPROVAL_V1'))continue;file_put_contents($out.'/'.$name.'-before.sql',$body);
if(str_starts_with($name,'trg_kontrak_final_budget_')){
$lines=explode("\n",$body);$lines=array_values(array_filter($lines,fn($line)=>!str_contains($line,"SET MESSAGE_TEXT='Gunakan DPPA final untuk sub kegiatan ini'")));$body=implode("\n",$lines);
$block=<<<'SQL'
  /* TEMPORAL_APPROVAL_V1 */
  SET NEW.pengesahan_anggaran_id=NULLIF(fn_contract_approval(NEW.kd_wilayah,NEW.kd_opd,NEW.tahun,NEW.tanggal_kontrak),0);
  IF NEW.pengesahan_anggaran_id IS NULL AND EXISTS(SELECT 1 FROM anggaran_pengesahan_neo WHERE BINARY kd_wilayah=BINARY NEW.kd_wilayah AND BINARY kd_opd=BINARY NEW.kd_opd AND tahun=NEW.tahun AND status='DISAHKAN') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Belum ada pengesahan DPA/DPPA yang berlaku pada tanggal kontrak'; END IF;
  IF NEW.pengesahan_anggaran_id IS NOT NULL THEN
    SET sub_code=NULL;
    SELECT s.kd_sub_keg INTO sub_code FROM anggaran_pengesahan_rincian_neo s WHERE s.pengesahan_id=NEW.pengesahan_anggaran_id AND BINARY s.tahap=BINARY NEW.tahap AND s.anggaran_id=NEW.anggaran_id LIMIT 1;
    IF sub_code IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pilih rincian DPA/DPPA sesuai pengesahan yang berlaku pada tanggal kontrak'; END IF;
  END IF;
SQL;
$pos=strpos($body,'  IF sub_code IS NULL');if($pos===false)throw new RuntimeException('Unexpected budget guard');$body=substr_replace($body,$block."\n",$pos,0);
}elseif(str_starts_with($name,'trg_kontrak_item_final_budget_')){
$block=<<<'SQL'
  /* TEMPORAL_APPROVAL_V1 */
  SELECT pengesahan_anggaran_id INTO NEW_APPROVAL_PLACEHOLDER FROM kontrak_neo WHERE id=NEW.kontrak_id;
SQL;
// A snapshot is a copy of rows approved and locked at publication time.
$block="  /* TEMPORAL_APPROVAL_V1 */\n  SET NEW.pengesahan_anggaran_id=(SELECT pengesahan_anggaran_id FROM kontrak_neo WHERE id=NEW.kontrak_id);\n  IF NEW.pengesahan_anggaran_id IS NOT NULL THEN SELECT COUNT(*) INTO final_count FROM anggaran_pengesahan_rincian_neo s JOIN anggaran_pengesahan_neo a ON a.id=s.pengesahan_id WHERE s.pengesahan_id=NEW.pengesahan_anggaran_id AND a.status='DISAHKAN' AND BINARY a.kd_wilayah=BINARY NEW.kd_wilayah AND BINARY a.kd_opd=BINARY NEW.kd_opd AND a.tahun=NEW.tahun AND BINARY s.tahap=BINARY NEW.tahap AND s.anggaran_id=NEW.anggaran_id; END IF;\n";
$body=str_replace('  IF final_count=0',$block.'  IF final_count=0',$body);
}elseif(str_starts_with($name,'trg_kontrak_item_validate_')){
$block="  /* TEMPORAL_APPROVAL_V1 */\n  SET NEW.pengesahan_anggaran_id=(SELECT pengesahan_anggaran_id FROM kontrak_neo WHERE id=NEW.kontrak_id);\n  IF NEW.pengesahan_anggaran_id IS NOT NULL THEN SET budget=NULL; SELECT pagu INTO budget FROM anggaran_pengesahan_rincian_neo WHERE pengesahan_id=NEW.pengesahan_anggaran_id AND BINARY tahap=BINARY NEW.tahap AND anggaran_id=NEW.anggaran_id LIMIT 1; END IF;\n";
$body=str_replace('  SELECT COALESCE(setujui,0)',$block.'  SELECT COALESCE(setujui,0)',$body);
}elseif($name==='trg_kontrak_validate_update'){
$block="  /* TEMPORAL_APPROVAL_V1 */\n  IF item_count=0 AND NEW.pengesahan_anggaran_id IS NOT NULL THEN SET budget=NULL; SELECT pagu,kd_sub_keg INTO budget,sub_code FROM anggaran_pengesahan_rincian_neo WHERE pengesahan_id=NEW.pengesahan_anggaran_id AND BINARY tahap=BINARY NEW.tahap AND anggaran_id=NEW.anggaran_id LIMIT 1; END IF;\n";
$body=str_replace('  IF budget IS NULL',$block.'  IF budget IS NULL',$body);
$body=str_replace('COALESCE(d.jumlah,p.jumlah)',"COALESCE((SELECT snap.pagu FROM anggaran_pengesahan_rincian_neo snap WHERE snap.pengesahan_id=COALESCE(ci.pengesahan_anggaran_id,NEW.pengesahan_anggaran_id) AND BINARY snap.tahap=BINARY ci.tahap AND snap.anggaran_id=ci.anggaran_id),d.jumlah,p.jumlah)",$body);
}else continue;
$db->query('CREATE OR REPLACE TRIGGER `'.$name.'` BEFORE '.$t['EVENT_MANIPULATION'].' ON '.$t['EVENT_OBJECT_TABLE'].' FOR EACH ROW '.$body);file_put_contents($out.'/'.$name.'-after.sql',$body);echo $name." updated\n";
}
