<?php
require_once __DIR__.'/../app/Services/RekananQualificationService.php';
$db=DB::getInstance();$db->begin();$fail=0;
try{
 $actor=['type_user'=>'admin_opd','kd_wilayah'=>'QUAL_TEST','kd_opd'=>'TEST','tahun'=>2026];
 $db->insert('rekanan_neo',['kd_wilayah'=>'QUAL_TEST','nama_perusahaan'=>'TEST <COMPANY>','alamat'=>'TEST ADDRESS','npwp'=>'TEST-NPWP','direktur'=>'TEST DIRECTOR','jabatan'=>'Direktur','username_insert'=>'QUALIFICATION_TEST']);$id=(int)$db->lastInsertId();
 $service=new RekananQualificationService($actor);
 foreach(['konstruksi'=>['konstruksi'],'barang_jasa'=>['barang','jasa_lainnya'],'konsultansi'=>['konstruksi','nonkonstruksi']] as $jenis=>$details)foreach($details as $subjenis)foreach(['langsung','tender','mini'] as $metode){$pdf=$service->pdf($id,compact('jenis','subjenis','metode'));if(!str_starts_with($pdf,'%PDF-')||strlen($pdf)<10000)throw new RuntimeException('Invalid PDF '.$jenis.'/'.$subjenis.'/'.$metode);}
 foreach([['jenis'=>'bad'],['metode'=>'bad'],['tanggal'=>'2026-02-30'],['tanggal'=>'2026-11-23<script>'],['jenis'=>'konsultansi','subjenis'=>'barang'],['kontrak_id'=>-1],['kontrak_id'=>776]] as $invalid){try{$service->data($id,$invalid);throw new LogicException('Invalid input accepted');}catch(InvalidArgumentException $e){}catch(RuntimeException $e){if($e instanceof LogicException)throw $e;}}
 try{(new RekananQualificationService($actor+[]))->options(598);throw new LogicException('Cross-region access accepted');}catch(RuntimeException $e){if($e instanceof LogicException)throw $e;}
 try{(new RekananQualificationService(array_replace($actor,['type_user'=>'viewer'])))->options($id);throw new LogicException('Unauthorized role accepted');}catch(RuntimeException $e){if($e instanceof LogicException)throw $e;}
 echo "PASS: 15 format/method combinations; invalid inputs, role, contract and regional boundaries.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);$fail=1;}finally{$db->rollback();}
exit($fail);
