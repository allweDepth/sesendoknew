<?php
/** Permit only canonical DPPA labels to be corrected on incomplete drafts.
 * Every non-label contract column remains identical, including all financial,
 * provider, scope, status, approval and locking fields. No provider is invented.
 * CLI: php ... [--apply]. Uses the local setup administrator for trigger DDL.
 */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$config=require __DIR__.'/../../config/database.php';
$pdo=new PDO('mysql:unix_socket='.$config['socket'].';dbname='.$config['dbname'].';charset=utf8mb4',getenv('SESENDOK_SETUP_ADMIN')?:'root',getenv('SESENDOK_SETUP_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$trigger=$pdo->query('SHOW CREATE TRIGGER trg_kontrak_validate_update')->fetch()['SQL Original Statement'];
if(str_contains($trigger,'label_source.uraian=BINARY NEW.uraian_kontrak')){
    // A block comment inside mariadb-dump's versioned CREATE comment would
    // prematurely terminate the exported trigger. Keep stored SQL comment-free.
    $clean=rtrim(preg_replace('~/\* canonical_dppa_label_only:.*?\*/\s*~s','',$trigger));
    if($clean!==$trigger && in_array('--apply',$argv,true))$pdo->exec(preg_replace('/^CREATE\s+/','CREATE OR REPLACE ',$clean,1));
    echo "Canonical label guard installed; dump-safe trigger verified\n";exit;
}
$columns=array_column($pdo->query('SHOW COLUMNS FROM kontrak_neo')->fetchAll(),'Field');
$allowed=['uraian_kontrak','nama_sub_keg','tgl_update','username_update'];
$unchanged=implode("\n        AND ",array_map(static fn($c)=>'(NEW.`'.$c.'` <=> OLD.`'.$c.'`)',array_values(array_diff($columns,$allowed))));
$old="IF provider IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;";
$new="IF provider IS NULL THEN
    IF NOT (
        OLD.rekanan_id IS NULL AND OLD.status_kontrak='draft'
        AND OLD.setujui=0 AND OLD.kunci=0
        AND $unchanged
        AND NEW.tahap='dppa' AND item_count=1
        AND EXISTS (SELECT 1 FROM dppa_neo label_source
          WHERE label_source.id=NEW.anggaran_id AND label_source.is_deleted=0
            AND BINARY label_source.kd_wilayah=BINARY NEW.kd_wilayah
            AND BINARY label_source.kd_opd=BINARY NEW.kd_opd
            AND label_source.tahun=NEW.tahun
            AND BINARY label_source.uraian=BINARY NEW.uraian_kontrak)
        AND ((NEW.nama_sub_keg <=> OLD.nama_sub_keg) OR EXISTS
          (SELECT 1 FROM rekening_kegiatan_historis label_sub
           WHERE label_sub.tahun=NEW.tahun
             AND BINARY label_sub.kd_wilayah=BINARY NEW.kd_wilayah
             AND BINARY label_sub.kode=BINARY NEW.kd_sub_keg
             AND label_sub.level='sub_kegiatan'
             AND BINARY label_sub.uraian=BINARY NEW.nama_sub_keg))
    ) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Penyedia referensi tidak valid'; END IF;
    SET provider=OLD.nama_penyedia;
  END IF;";
if(substr_count($trigger,$old)!==1)throw new RuntimeException('Trigger berbeda; pemeriksaan manual diperlukan');
$sql=rtrim(preg_replace('/^CREATE\s+/','CREATE OR REPLACE ',str_replace($old,$new,$trigger),1));
if(!in_array('--apply',$argv,true)){echo $sql.";\n";exit;}
file_put_contents('/private/tmp/konstruksi2026/label-trigger-before.txt',$trigger);
$pdo->exec($sql);
// No invalid financial/provider/status change may pass the narrowly scoped guard.
$id=(int)$pdo->query("SELECT k.id FROM kontrak_neo k JOIN dppa_neo d ON d.id=k.anggaran_id WHERE k.rekanan_id IS NULL AND k.status_kontrak='draft' AND k.tahap='dppa' AND k.tahun=2026 AND k.kd_wilayah='76.01' AND k.kd_opd='1.03.0.00.0.00.01.0000' AND k.uraian_kontrak LIKE '%Rusun%' LIMIT 1")->fetchColumn();
if(!$id)throw new RuntimeException('No matching draft to validate');
$pdo->beginTransaction();
try{
 $pdo->exec("UPDATE kontrak_neo k JOIN dppa_neo d ON d.id=k.anggaran_id SET k.uraian_kontrak=d.uraian WHERE k.id=$id");
 foreach(['nilai_hps=1','nilai_kontrak=1','rekanan_id=0','setujui=1','kunci=1',"status_kontrak='aktif'","uraian_kontrak='Nama tanpa sumber DPPA'"] as $change){
  $rejected=false;
  try{$pdo->exec("UPDATE kontrak_neo SET $change WHERE id=$id");}catch(PDOException $e){if($e->getCode()!=='45000')throw $e;$rejected=true;}
  if(!$rejected)throw new RuntimeException('Non-label mutation passed: '.$change);
 }
 $pdo->rollBack();
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$pdo->exec(preg_replace('/^CREATE\s+/','CREATE OR REPLACE ',$trigger,1));throw $e;}
echo "PASS canonical DPPA label correction; financial, provider, approval, locking, status and unsupported label changes rejected\n";
