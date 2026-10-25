<?php
/** User-authorized annual PPTK / procurement officer assignments, PUPR Pasangkayu 2026. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../app/Core/DB.php';
require_once __DIR__.'/../app/Services/ContractOfficialService.php';
$db=DB::getInstance();$cfg=require __DIR__.'/../config/database.php';
$admin=new PDO('mysql:unix_socket='.$cfg['socket'].';dbname='.$cfg['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if(!$db->query("SHOW COLUMNS FROM kontrak_neo LIKE 'pejabat_pengadaan_id'")->fetch())$admin->exec('ALTER TABLE kontrak_neo ADD COLUMN pejabat_pengadaan_id BIGINT NULL AFTER pptk_id, ADD INDEX idx_kontrak_pejabat_pengadaan (pejabat_pengadaan_id)');
// Read-only compatibility checks: never modify validation triggers in this importer.
$trigger=$admin->query('SHOW CREATE TRIGGER trg_kontrak_final_budget_update')->fetch()['SQL Original Statement'];
$protected=['kd_wilayah','kd_opd','tahun','kd_sub_keg','tahap','anggaran_id','total_anggaran','nilai_hps','nilai_kontrak','rekanan_id','setujui','kunci'];
foreach($protected as $f)if(!str_contains($trigger,"NOT (NEW.`$f` <=> OLD.`$f`)"))throw new RuntimeException('Trigger metadata tidak kompatibel; tidak ada data diubah');
if(!str_contains($trigger,"setujui=1 AND kunci=1")||!str_contains($trigger,"SET MESSAGE_TEXT='Gunakan DPPA final untuk sub kegiatan ini'"))throw new RuntimeException('Validasi anggaran final tidak lengkap');
$providerTrigger=$admin->query('SHOW CREATE TRIGGER trg_kontrak_validate_update')->fetch()['SQL Original Statement'];
if(!str_contains($providerTrigger,"IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;"))throw new RuntimeException('Validasi penyedia tidak sesuai');
$w='76.01';$o='1.03.0.00.0.00.01.0000';$actor='PUPR_OFFICIALS_2026';
$employees=[];foreach([110,27,5,6,3] as $id){$e=$db->query('SELECT * FROM db_asn_pemda_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0',[$id,$w,$o])->fetch();if(!$e)throw new RuntimeException('ASN tidak ditemukan');$employees[$id]=$e;}
$contracts=$db->query('SELECT * FROM kontrak_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=2026 AND is_deleted=0',[$w,$o])->fetchAll();
$db->begin();try{
 $annual=function(int $employee,string $type,?string $code)use($db,$employees,$w,$o,$actor):int{
  $e=$employees[$employee];$name=ContractOfficialService::employeeName($e);
  $existing=$db->query('SELECT id,username_insert FROM pejabat_tahunan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=2026 AND jenis_pejabat=? AND pegawai_id=? AND kd_sub_keg <=> ? AND is_deleted=0',[$w,$o,$type,$employee,$code])->fetch();if($existing){if($existing['username_insert']===$actor)$db->update('pejabat_tahunan_neo',['nama_pegawai'=>$name],'WHERE id=?',[$existing['id']]);return(int)$existing['id'];}
  $knownSk=$type==='PEJABAT_PENGADAAN'?'06 TAHUN 2026':($type==='PPK'&&$employee===6?'01 TAHUN 2026':'');
  return(int)$db->insert('pejabat_tahunan_neo',['kd_wilayah'=>$w,'kd_opd'=>$o,'tahun'=>2026,'jenis_pejabat'=>$type,'pegawai_id'=>$employee,'nama_pegawai'=>$name,'nip'=>$e['nip'],'nomor_sk'=>$knownSk,'tanggal_sk'=>$knownSk?'2026-01-05':null,'berlaku_mulai'=>$knownSk?'2026-01-05':'2026-01-01','berlaku_sampai'=>'2026-12-31','kd_sub_keg'=>$code,'keterangan'=>$knownSk?'Sumber sk_asn_neo id '.($type==='PPK'?'130':'133').'.':'Penugasan tahun 2026 sesuai instruksi pengguna; nomor/tanggal SK '.$type.' belum tersedia, perlu dilengkapi.','username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);
 };
 $pp=$annual(27,'PEJABAT_PENGADAAN',null);$assignments=[];$counts=[];
 foreach($contracts as $c){
  $code=$c['kd_sub_keg'];$employee=str_starts_with($code,'1.03.10.')?110:((str_starts_with($code,'1.03.09.')||str_starts_with($code,'1.03.03.')||str_starts_with($code,'1.03.05.')||str_starts_with($code,'1.03.07.')||$code==='1.03.01.2.09.0009')?5:((str_starts_with($code,'1.03.02.')||str_starts_with($code,'1.03.06.'))?6:null));
  $data=['pejabat_pengadaan_id'=>$pp,'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')];
  $ppkEmployee=(str_starts_with($code,'1.03.02.')||str_starts_with($code,'1.03.06.'))?6:3;
  $data['ppk_id']=$annual($ppkEmployee,'PPK',$code);$data['nama_ppk']=$db->query('SELECT nama_pegawai FROM pejabat_tahunan_neo WHERE id=?',[$data['ppk_id']])->fetch()['nama_pegawai'];
  if($c['username_insert']==='IMPORT_PERUBAHAN_PUPR_2026'&&$c['status_kontrak']==='draft'){
   $name=mb_strtolower($c['uraian_kontrak']);$start=$c['tanggal_mulai']?:$c['tanggal_kontrak'];
   if(preg_match('/stock|stok|perencanaan|penyusunan dokumen perencanaan|^sid /u',$name)){$data['waktu_pelaksanaan']=30;$data['tanggal_selesai']=date('Y-m-d',strtotime($start.' +29 days'));}
   elseif($c['jenis_pengadaan']==='PEKERJAAN_KONSTRUKSI'){$data['tanggal_selesai']='2026-12-31';$data['waktu_pelaksanaan']=(int)(new DateTimeImmutable($start))->diff(new DateTimeImmutable('2026-12-31'))->days+1;}
  }
  if($employee){$key=$employee.':'.$code;$assignments[$key]??=$annual($employee,'PPTK',$code);$data['pptk_id']=$assignments[$key];$counts[$employees[$employee]['nama']]=($counts[$employees[$employee]['nama']]??0)+1;}
  // Provider validation remains intact. Incomplete drafts resolve their annual
  // officials at document-read time until their provider has been supplied.
  if($c['rekanan_id']!==null)$db->update('kontrak_neo',$data,'WHERE id=?',[$c['id']]);
 }
 $db->commit();echo json_encode(['contracts'=>count($contracts),'pptk_assignments'=>count($assignments),'contracts_by_pptk'=>$counts,'procurement_official_id'=>$pp],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollback();throw $e;}
