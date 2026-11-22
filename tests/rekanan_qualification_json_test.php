<?php
require_once __DIR__.'/../app/Services/RekananQualificationService.php';
require_once __DIR__.'/../app/Services/RekananQualificationExcelService.php';
$db=DB::getInstance();$id=0;$failed=0;
$user=['type_user'=>'admin_opd','username'=>'QUALIFICATION_JSON_TEST','kd_wilayah'=>'QUAL_JSON_TEST','kd_opd'=>'TEST','tahun'=>2026];
function qassert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
try{
 $db->insert('rekanan_neo',['kd_wilayah'=>$user['kd_wilayah'],'nama_perusahaan'=>'QUAL_JSON_TEST','alamat'=>'Test','npwp'=>'TEST','direktur'=>'DIREKTUR DEFAULT','jabatan'=>'Direktur','username_insert'=>$user['username']]);$id=(int)$db->lastInsertId();$s=new RekananQualificationService($user);
 $input=['versi'=>0,'profil'=>['telepon'=>'08123456789','dasar_kuasa'=>'Akta No. 12'],'rincian'=>['pekerjaan_berjalan'=>[['paket'=>'PAKET SATU','nilai'=>'100000000','rencana'=>'70','realisasi'=>'65'],['paket'=>'PAKET DUA','nilai'=>'200000000','selesai'=>'2026-12-30']],'personel'=>[['nama'=>'TENAGA AHLI TEST','pengalaman'=>'5']],'peralatan'=>[['jenis'=>'EXCAVATOR TEST','jumlah'=>'2']],'pengalaman'=>[['paket'=>'PENGALAMAN TEST','nilai'=>'90000000']]]];
 $saved=$s->saveQualification($id,$input)['data'];qassert($saved['versi']===1,'Version not advanced');qassert(count($saved['rincian']['pekerjaan_berjalan'])===2,'Repeated rows not retained');
 $row=$db->query('SELECT kualifikasi_json,pekerjaan_berjalan_json,personel_json,peralatan_json,pengalaman_json FROM rekanan_neo WHERE id=?',[$id])->fetch();qassert(count(json_decode($row['pekerjaan_berjalan_json'],true))===2,'Wrong dedicated JSON column');qassert(!isset(json_decode($row['kualifikasi_json'],true)['rincian']['pekerjaan_berjalan']),'Duplicate storage in metadata');
 try{$s->saveQualification($id,$input);throw new LogicException('Stale write accepted');}catch(RuntimeException $e){qassert(str_contains($e->getMessage(),'berubah'),'Wrong concurrency error');}
 $next=$saved;$next['rincian']['pekerjaan_berjalan']=[$next['rincian']['pekerjaan_berjalan'][1]];$s->saveQualification($id,$next);qassert(count($s->qualification($id)['data']['rincian']['pekerjaan_berjalan'])===1,'Remove row failed');
 $pdf=$s->pdf($id,['jenis'=>'konstruksi','metode'=>'tender','penandatangan_nama'=>'PENERIMA KUASA TEST','penandatangan_jabatan'=>'Penerima Kuasa','penandatangan_identitas'=>'IDENTITAS KUASA','dasar_kewenangan'=>'Surat Kuasa 99/2026']);file_put_contents(sys_get_temp_dir().'/qualification-persistence.pdf',$pdf);
 $xlsx=(new RekananQualificationExcelService($user))->excel($id,['jenis'=>'konstruksi','metode'=>'tender','penandatangan_nama'=>'PENERIMA KUASA TEST','penandatangan_jabatan'=>'Penerima Kuasa','penandatangan_identitas'=>'IDENTITAS KUASA','dasar_kewenangan'=>'Surat Kuasa 99/2026']);$xp=sys_get_temp_dir().'/qualification-persistence.xlsx';file_put_contents($xp,$xlsx);$book=\PhpOffice\PhpSpreadsheet\IOFactory::load($xp);qassert($book->getSheetByName('Pekerjaan Berjalan')->getCell('B4')->getValue()==='PAKET DUA','Saved rows missing from Excel');qassert($book->getSheetByName('Pakta Integritas')->getCell('D3')->getValue()==='PENERIMA KUASA TEST','Signer missing from Excel');$book->disconnectWorksheets();
 foreach([['rencana'=>'101'],['selesai'=>'2026-02-30'],['nilai'=>'1e10']] as $bad){try{RekananQualificationData::clean(['profil'=>[],'rincian'=>['pekerjaan_berjalan'=>[$bad]]]);throw new LogicException('Invalid detail accepted');}catch(InvalidArgumentException $e){}}
 qassert($s->options($id)['penandatangan']['nama']==='DIREKTUR DEFAULT','Default signer does not use director');
 echo "PASS: separate JSON columns; add/load/remove rows; version conflicts; dates/numbers; default and overridden signer.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);$failed=1;}
finally{if($id)$db->query('DELETE FROM rekanan_neo WHERE id=? AND username_insert=?',[$id,$user['username']]);}
exit($failed);
