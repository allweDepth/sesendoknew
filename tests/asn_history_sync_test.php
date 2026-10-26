<?php
require_once __DIR__.'/../app/Core/DB.php';require_once __DIR__.'/../app/Services/AsnHistoryService.php';
$config=require __DIR__.'/../config/database.php';if(!str_starts_with($config['dbname'],'qa_'))throw new RuntimeException('Test requires isolated qa_ database');$db=DB::getInstance();
function checkHistory(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
function employeeHistory(DB $db):array{return$db->query('SELECT * FROM db_asn_pemda_neo WHERE id=1001')->fetch();}
$db->begin();try{
 $e=employeeHistory($db);$seed=AsnHistoryService::seed($db,$e,2026);checkHistory(count($seed)===2,'Baseline both histories');checkHistory(AsnHistoryService::seed($db,$e,2026)===[],'Seed idempotent');
 $new=function(string$table,array$extra)use($db){$v=AsnHistoryService::normalize($db,$table,$extra+['pegawai_id'=>1001,'kd_wilayah'=>'QA','kd_opd'=>'QA.OPD','tmt'=>'2026-06-01','tahun'=>2026]);$id=(int)$db->insert($table,$v);AsnHistoryService::afterHistory($db,$table,$id);return$id;};
 $job=$new(AsnHistoryService::JOB,['jabatan'=>'Kepala Bidang QA','kelompok'=>3]);checkHistory(employeeHistory($db)['jabatan']==='Kepala Bidang QA','New job projects to master');
 $grade=$new(AsnHistoryService::GRADE,['golongan'=>'IV','ruang'=>'b']);checkHistory(employeeHistory($db)['golongan']==='4'&&employeeHistory($db)['ruang']==='b','Roman grade normalizes and projects');
 $older=$new(AsnHistoryService::GRADE,['golongan'=>'II','ruang'=>'a','tmt'=>'2024-01-01']);checkHistory(employeeHistory($db)['golongan']==='4','Late entry of older history cannot replace newer TMT');
 $future=$new(AsnHistoryService::GRADE,['golongan'=>'IV','ruang'=>'d','tmt'=>'2099-01-01']);checkHistory(employeeHistory($db)['ruang']==='b','Future history not active now');AsnHistoryService::sync($db,1001,'2099-01-01');checkHistory(employeeHistory($db)['ruang']==='d','Future history takes effect on TMT');AsnHistoryService::sync($db,1001);
 $old=$db->query('SELECT * FROM riwayat_pangkat_neo WHERE id=?',[$grade])->fetch();$db->update('riwayat_pangkat_neo',['ruang'=>'c'],'WHERE id=?',[$grade]);AsnHistoryService::afterHistory($db,AsnHistoryService::GRADE,$grade,$old);checkHistory(employeeHistory($db)['ruang']==='c','Edit recomputes current projection');
 $db->update('riwayat_pangkat_neo',['is_deleted'=>1],'WHERE id=?',[$grade]);AsnHistoryService::afterHistory($db,AsnHistoryService::GRADE,$grade,$old);checkHistory(employeeHistory($db)['golongan']==='3'&&employeeHistory($db)['ruang']==='a','Delete current falls back to baseline');
 $same=$new(AsnHistoryService::JOB,['jabatan'=>'Kepala Bidang QA Terbaru','kelompok'=>3]);checkHistory(employeeHistory($db)['jabatan']==='Kepala Bidang QA Terbaru','Same TMT newest row deterministic');
 $db->update('riwayat_jabatan_neo',['disable'=>1],'WHERE id=?',[$same]);AsnHistoryService::afterHistory($db,AsnHistoryService::JOB,$same);checkHistory(employeeHistory($db)['jabatan']==='Kepala Bidang QA','Disabled history ignored');
 $before=employeeHistory($db);$db->update('db_asn_pemda_neo',['jabatan'=>'Jabatan dari master','kelompok'=>4],'WHERE id=?',[1001]);AsnHistoryService::afterMaster($db,1001,$before);checkHistory(employeeHistory($db)['jabatan']==='Jabatan dari master','Master edits create a dated history');
 $cross=false;try{$new(AsnHistoryService::GRADE,['pegawai_id'=>1002,'golongan'=>'III','ruang'=>'b']);}catch(FieldValidationException){$cross=true;}checkHistory($cross,'Reject ASN from another OPD');
 $invalid=false;try{$new(AsnHistoryService::GRADE,['golongan'=>'III','ruang'=>'z']);}catch(FieldValidationException){$invalid=true;}checkHistory($invalid,'Reject invalid rank space');
 $db->rollback();echo "PASS: baseline, idempotency, add/edit/delete, same-TMT, future-effective, backdated, master audit, OPD isolation, field validation\n";
}catch(Throwable$e){$db->rollback();throw$e;}
