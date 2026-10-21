<?php
ob_start();
require_once __DIR__.'/../app/Services/BudgetScheduleService.php';
function ok($value, $message) { if (!$value) throw new RuntimeException('FAIL: '.$message); echo "PASS: $message\n"; }
function rejects(callable $call, string $contains, string $message) { try {$call();} catch (Throwable $e) {ok(str_contains($e->getMessage(),$contains),$message.' ['.$e->getMessage().']');return;} throw new RuntimeException('FAIL: '.$message.' tidak ditolak'); }
$settings=['awal_renja'=>'2026-10-21 08:00:00','akhir_renja'=>'2026-10-21 16:00:00','kunci'=>0,'kunci_renja'=>0];
$now = new DateTimeImmutable('2026-10-21 12:00:00', new DateTimeZone('Asia/Makassar'));
foreach (['2026-10-21 08:00:00'=>true,'2026-10-21 16:00:00'=>true,'2026-10-21 07:59:59'=>false,'2026-10-21 16:00:01'=>false] as $time=>$expected) ok(BudgetScheduleService::evaluate('renja',$settings,new DateTimeImmutable($time,BudgetScheduleService::timezone()))['enabled']===$expected,'Batas waktu inklusif '.$time);
ok(!BudgetScheduleService::evaluate('renja',array_replace($settings,['kunci'=>1]),$now)['enabled'],'Kunci global mengalahkan kunci tahap yang terbuka');
ok(!BudgetScheduleService::evaluate('renja',array_replace($settings,['kunci_renja'=>1]),$now)['enabled'],'Kunci tahap menutup jadwal aktif');
ok(!BudgetScheduleService::evaluate('rka', $settings,$now)['enabled'],'Tahap tanpa jadwal ditutup');
ok(!BudgetScheduleService::evaluate('renja',array_replace($settings,['awal_renja'=>'2026-02-30 12:00:00']),$now)['enabled'],'Tanggal tidak valid ditutup');
ok(BudgetScheduleService::evaluate('renja',$settings,$now->setTimezone(new DateTimeZone('UTC')))['enabled'],'Perbandingan waktu konsisten antar zona waktu');
rejects(fn()=>BudgetScheduleService::validate(array_replace($settings,['akhir_renja'=>'2026-10-20 16:00:00'])),'selesai harus','Menolak jadwal terbalik');
rejects(fn()=>BudgetScheduleService::save(null,['type_user'=>'admin_opd'],[]),'Hanya TAPD','Role OPD tidak dapat menetapkan jadwal');
ok(BudgetScheduleService::stage('indikator_program_renstra_neo')==='renstra','Jadwal Renstra mencakup rincian hierarki');
$defaults=BudgetScheduleDefaults::generate(2026,'76.01');
ok($defaults['fields']['awal_rka']==='2025-08-22 00:00:00','Anggaran murni dijadwalkan pada tahun sebelumnya');
ok($defaults['fields']['akhir_rkpd']==='2025-07-07 23:59:59','Default RKPD kabupaten mengikuti target pedoman 2026');
ok(BudgetScheduleDefaults::generate(2026,'76')['fields']['akhir_rkpd']==='2025-06-30 23:59:59','Default RKPD provinsi dibedakan dari kabupaten');
ok($defaults['fields']['akhir_apbd_perubahan']==='2026-09-30 23:59:59','Perubahan APBD mengikuti tahun berjalan');
ok(!isset($defaults['fields']['awal_dpa']) && !isset($defaults['fields']['awal_renstra']),'Jadwal berbasis peristiwa menunggu tanggal dokumen sebenarnya');
$anchored=BudgetScheduleDefaults::generate(2026,'76.01',['rpjmd'=>'2025-01-31','surat_dpa'=>'2025-12-29','dpa'=>'2026-01-05','rkpd'=>'2025-06-20']);
ok($anchored['fields']['akhir_renstra']==='2025-02-28 23:59:59','Satu bulan dihitung tanpa melompati Februari');
ok($anchored['fields']['akhir_dpa']==='2026-01-04 23:59:59','DPA dihitung dari penerimaan surat dan dapat melewati pergantian tahun');
ok($anchored['fields']['akhir_rak']==='2026-01-08 23:59:59','Anggaran kas mengikuti tanggal pengesahan DPA');
ok($anchored['fields']['akhir_renja']==='2025-07-20 23:59:59','Renja mengikuti tanggal RKPD aktual');
$special=BudgetScheduleDefaults::generate(2026,'76.01',['rkpd_berikutnya'=>'2026-07-10']);
ok($special['fields']['akhir_rkpd_perubahan']==='2026-07-03 23:59:59','Batas perubahan RKPD 2026 mengikuti penetapan RKPD berikutnya');
$manual=BudgetScheduleDefaults::fillMissing(['awal_rka'=>'2025-07-01 00:00:00','akhir_rka'=>'2025-07-31 23:59:59','kunci_rka'=>1],2026,'76.01');
ok($manual['awal_rka']==='2025-07-01 00:00:00' && $manual['kunci_rka']===1,'Default tidak menimpa rentang manual atau membuka kunci');
if (!in_array('--database',$argv,true)) exit;
require_once __DIR__.'/../app/Services/DynamicTableService.php';
require_once __DIR__.'/../app/Services/AnggaranCopyService.php';
require_once __DIR__.'/../app/Services/AnggaranDocumentService.php';
$db=DB::getInstance();$db->begin();
try {
    $template=$db->query('SELECT * FROM pengaturan_neo WHERE is_deleted=0 ORDER BY id DESC LIMIT 1')->fetch();
    unset($template['id']);$template['kd_wilayah']='SCHEDULE.TEST';$template['tahun']=2099;$template['kunci']=0;$template['username_insert']='SCHEDULE_TEST';
    foreach(BudgetScheduleService::STAGES as [$label,$period,$lock]) {$template['awal_'.$period]='2000-01-01 00:00:00';$template['akhir_'.$period]='2099-12-31 23:59:59';if($lock)$template[$lock]=0;}
    $id=$db->insert('pengaturan_neo',$template);
    $tapd=['type_user'=>'tapd','username'=>'SCHEDULE_TEST','kd_wilayah'=>'SCHEDULE.TEST','tahun'=>2099,'kd_opd'=>'0'];
    $_SESSION['user']=$tapd;
    ob_start();require __DIR__.'/../app/Views/pengaturan/form.php';$tapdView=ob_get_clean();
    ok(str_contains($tapdView,'tapd-schedule-only') && str_contains($tapdView,'id="applyScheduleDefaults"') && !str_contains($tapdView,'name="awal_rka" readonly disabled'),'Form TAPD menampilkan jadwal dan default yang dapat diubah');
    BudgetScheduleService::save($db,$tapd,['kunci_rka'=>1,'kd_wilayah'=>'76.01','tahun'=>2026,'aturan_anggaran'=>'INJECTION']);
    $saved=BudgetScheduleService::settings($db,$tapd);
    ok((int)$saved['kunci_rka']===1 && $saved['kd_wilayah']==='SCHEDULE.TEST' && $saved['aturan_anggaran']===$template['aturan_anggaran'],'TAPD hanya mengubah jadwal pada wilayah/tahun sesi');
    foreach(['admin_opd','kepala_opd','pa_kpa','pptk','ppk','staf_opd','bendahara','ppk_skpd'] as $role) {
        $opd=array_replace($tapd,['type_user'=>$role,'kd_opd'=>'OPD.A']);
        rejects(fn()=>BudgetScheduleService::assertOpen($db,$opd,'rka_neo'),'Dikunci','RKA ditutup untuk '.$role);
        BudgetScheduleService::assertOpen($db,$opd,'renja_neo');
    }
    ok(true,'Renja tetap dibuka saat RKA dikunci');
    $opd=array_replace($tapd,['type_user'=>'admin_opd','kd_opd'=>'OPD.A']);
    $otherOpd=array_replace($opd,['kd_opd'=>'OPD.B']);
    rejects(fn()=>BudgetScheduleService::assertOpen($db,$otherOpd,'rka'),'Dikunci','Seluruh OPD dalam wilayah menggunakan jadwal yang sama');
    rejects(fn()=>BudgetScheduleService::assertOpen($db,array_replace($opd,['kd_wilayah'=>'SCHEDULE.OTHER']),'renja'),'Belum diatur','Jadwal tidak berlaku di wilayah lain');
    rejects(fn()=>BudgetScheduleService::assertOpen($db,array_replace($opd,['tahun'=>2098]),'renja'),'Belum diatur','Jadwal tidak berlaku di tahun lain');
    $_SESSION['user']=$opd;unset($_SESSION['scope_kd_opd'],$_SESSION['scope_kd_wilayah']);
    $dynamic=new DynamicTableService();
    foreach(['add','add_json','edit','edit_json','delete','import'] as $action){
        $response=json_decode($dynamic->handle(['action'=>$action,'tbl'=>'rka','id_row'=>1,'mode'=>'update','struktur_json'=>'{}']),true);
        ok(!$response['success'] && str_contains($response['message'],'Dikunci'),'Server menolak jalur '.$action.' di luar jadwal');
    }
    rejects(fn()=>$dynamic->importStrict('rka','/file-tidak-ada.xlsx'),'Dikunci','Impor langsung tidak melewati jadwal');
    rejects(fn()=>(new AnggaranCopyService($opd))->copy('renja','rka',2099),'Dikunci','Penyalinan menuju tahap tertutup ditolak sebelum menulis');
    BudgetScheduleService::save($db,$tapd,['kunci_dpa'=>1]);
    $document=new AnggaranDocumentService($opd);
    rejects(fn()=>$document->saveMonthlyPlan('dpa',1,[]),'Dikunci','Rencana bulanan mengikuti jadwal DPA');
    rejects(fn()=>$document->saveAccountMonthly('dpa','SUB','5',[]),'Dikunci','Rencana rekening mengikuti jadwal DPA');
    BudgetScheduleService::save($db,$tapd,['kunci'=>1]);
    rejects(fn()=>BudgetScheduleService::assertOpen($db,$opd,'renja'),'Dikunci','Kunci global menutup Renja yang sebelumnya aktif');
    BudgetScheduleService::save($db,$tapd,['kunci'=>0,'kunci_rka'=>0]);
    BudgetScheduleService::assertOpen($db,$opd,'rka');ok(true,'TAPD membuka kembali input OPD');
    $db->update('pengaturan_neo',['awal_rka'=>null,'akhir_rka'=>null,'kunci_rka'=>1],'WHERE id=?',[$id]);
    BudgetScheduleService::applyDefaults($db,$tapd,['rpjmd'=>'2098-01-31']);
    $autofilled=BudgetScheduleService::settings($db,$tapd);
    ok($autofilled['awal_rka']==='2098-08-22 00:00:00' && (int)$autofilled['kunci_rka']===1,'Default tersimpan untuk tahap kosong tanpa membuka kunci');
    ok($autofilled['awal_renja']===$template['awal_renja'],'Jadwal OPD yang ada tetap dipertahankan saat isi default');
    BudgetScheduleService::save($db,$tapd,['kunci_rka_p'=>1]);
    BudgetScheduleService::assertOpen($db,$opd,'renja_p');
    rejects(fn()=>BudgetScheduleService::assertOpen($db,$opd,'rka_p'),'Dikunci','RKA Perubahan terpisah dari Renja Perubahan');
    rejects(fn()=>BudgetScheduleService::save($db,$tapd,['awal_rka'=>'2099-02-30 12:00:00']),'tidak valid','TAPD tidak dapat menyimpan tanggal salah');
    rejects(fn()=>BudgetScheduleService::save($db,array_replace($tapd,['kd_wilayah'=>'NO.CONFIG']),['kunci'=>0]),'belum tersedia','Wilayah tanpa konfigurasi ditolak dengan pesan yang jelas');
} finally {$db->rollback();}
echo "BUDGET SCHEDULE TESTS COMPLETE (ROLLED BACK)\n";
