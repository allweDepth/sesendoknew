<?php
/** Scoped user correction: Nardin is PPTK for PUPR water and sanitation in 2026. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Services/ContractOfficialService.php';
$db=DB::getInstance();$w='76.01';$o='1.03.0.00.0.00.01.0000';$actor='PUPR_OFFICIALS_2026';
$employee=$db->query('SELECT * FROM db_asn_pemda_neo WHERE id=5 AND kd_wilayah=? AND kd_opd=? AND is_deleted=0',[$w,$o])->fetch();
if(!$employee||mb_strtolower(trim($employee['nama']))!=='nardin')throw new RuntimeException('ASN identity mismatch');
$name=ContractOfficialService::employeeName($employee);
$contracts=$db->query("SELECT * FROM kontrak_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=2026 AND is_deleted=0 AND (kd_sub_keg LIKE '1.03.03.%' OR kd_sub_keg LIKE '1.03.05.%' OR kd_sub_keg LIKE '1.03.07.%')",[$w,$o])->fetchAll();
$db->begin();try{
 $assigned=[];$saved=0;$resolved=0;
 foreach($contracts as $c){
  $code=$c['kd_sub_keg'];
  if(!isset($assigned[$code])){
   $p=$db->query("SELECT id FROM pejabat_tahunan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=2026 AND jenis_pejabat='PPTK' AND pegawai_id=5 AND kd_sub_keg=? AND is_deleted=0",[$w,$o,$code])->fetch();
   $assigned[$code]=$p?(int)$p['id']:(int)$db->insert('pejabat_tahunan_neo',['kd_wilayah'=>$w,'kd_opd'=>$o,'tahun'=>2026,'jenis_pejabat'=>'PPTK','pegawai_id'=>5,'nama_pegawai'=>$name,'nip'=>$employee['nip'],'nomor_sk'=>'','tanggal_sk'=>null,'berlaku_mulai'=>'2026-01-01','berlaku_sampai'=>'2026-12-31','kd_sub_keg'=>$code,'keterangan'=>'PPTK air bersih/sanitasi sesuai instruksi pengguna 25-10-2026; nomor/tanggal SK belum tersedia.','username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);
  }
  if($c['rekanan_id']!==null){$db->update('kontrak_neo',['pptk_id'=>$assigned[$code],'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')],'WHERE id=?',[$c['id']]);$c['pptk_id']=$assigned[$code];$saved++;}
  // Incomplete-provider drafts retain their validation guard and resolve annual officials on read.
  $actual=ContractOfficialService::resolve($c);
  if((int)($actual['pptk_id']??0)!==$assigned[$code]||$actual['nama_pptk']!==$name)throw new RuntimeException('PPTK resolution mismatch');
  $resolved++;
 }
 $db->commit();echo json_encode(['name'=>$name,'annual_subactivities'=>count($assigned),'contract_references_saved'=>$saved,'contracts_verified'=>$resolved],JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
