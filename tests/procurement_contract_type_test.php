<?php
require_once __DIR__.'/../app/Services/ProcurementContractTypeService.php';
function checkType($ok,$label){if(!$ok)throw new RuntimeException($label);}
foreach(['BARANG','JASA_LAINNYA','PEKERJAAN_KONSTRUKSI','KONSULTANSI','KONSULTANSI_KONSTRUKSI','KONSULTANSI_NON_KONSTRUKSI'] as $kind){$c=['cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>$kind];$opts=ProcurementContractTypeService::options($c);foreach($opts as $value=>$label)checkType(ProcurementContractTypeService::normalize($c+['jenis_kontrak'=>$value])===$value,'Valid choice rejected');checkType(isset($opts[ProcurementContractTypeService::normalize($c)]),'Default outside allowed choices');}
checkType(ProcurementContractTypeService::label(['jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI'])==='Harga Satuan','Construction default');
checkType(ProcurementContractTypeService::normalize(['cara_pengadaan'=>'SWAKELOLA'])==='PERJANJIAN_SWAKELOLA','Swakelola default');
foreach([['cara_pengadaan'=>'SWAKELOLA','jenis_kontrak'=>'LUMSUM'],['jenis_pengadaan'=>'KONSULTANSI','jenis_kontrak'=>'HARGA_SATUAN']] as $c){$failed=false;try{ProcurementContractTypeService::normalize($c);}catch(FieldValidationException $e){$failed=isset($e->errors['jenis_kontrak']);}checkType($failed,'Invalid choice accepted');}
echo "PASS: Contract types, defaults and field errors across procurement categories\n";
