<?php
/** Membuat RPJMD dan salinan struktur Renstra PUPR periode historis 2020-2025. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__.'/../app/Core/DB.php';

$db=DB::getInstance();
$wilayah='76.01';
$opd='1.03.0.00.0.00.01.0000';
$actor='CREATE_RPJMD_RENSTRA_2020_2025';
$source=$db->query(
    'SELECT r.* FROM renstra_neo r JOIN periode_rpjmd p ON p.id=r.periode_id
     WHERE r.kd_wilayah=? AND r.kd_opd=? AND p.periode_mulai=2026 AND p.periode_selesai=2030
       AND r.is_deleted=0 ORDER BY r.id DESC LIMIT 1',
    [$wilayah,$opd]
)->fetch();
if(!$source)throw new RuntimeException('Renstra sumber 2026-2030 tidak ditemukan');

$fresh=static function(array $row,string $actor):array{
    unset($row['id'],$row['tgl_update'],$row['username_update']);
    $row['tgl_insert']=date('Y-m-d H:i:s');
    $row['username_insert']=$actor;
    if(array_key_exists('is_deleted',$row))$row['is_deleted']=0;
    return$row;
};

$db->begin();
try{
    $period=$db->query('SELECT id FROM periode_rpjmd WHERE kd_wilayah=? AND periode_mulai=2020 AND periode_selesai=2025 LIMIT 1',[$wilayah])->fetch();
    if($period){
        $periodId=(int)$period['id'];
        $db->update('periode_rpjmd',['status_aktif'=>1,'keterangan'=>'RPJMD Kabupaten Pasangkayu 2020-2025','tgl_update'=>date('Y-m-d H:i:s'),'username_update'=>$actor],'WHERE id=?',[$periodId]);
    }else{
        $periodId=(int)$db->insert('periode_rpjmd',['kd_wilayah'=>$wilayah,'periode_mulai'=>2020,'periode_selesai'=>2025,'status_aktif'=>1,'keterangan'=>'RPJMD Kabupaten Pasangkayu 2020-2025','tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$actor]);
    }

    $existing=$db->query('SELECT id,username_insert FROM renstra_neo WHERE kd_wilayah=? AND kd_opd=? AND periode_id=? AND is_deleted=0 LIMIT 1',[$wilayah,$opd,$periodId])->fetch();
    if($existing && (string)$existing['username_insert']!==$actor)throw new RuntimeException('Renstra 2020-2025 sudah ada dan bukan hasil generator; proses dihentikan agar data pengguna tidak tertimpa');
    if($existing){
        $oldRenstra=(int)$existing['id'];
        $oldMissions=array_column($db->query('SELECT id FROM misi_renstra_neo WHERE renstra_id=?',[$oldRenstra])->fetchAll(),'id');
        foreach($oldMissions as$mission){
            $oldGoals=array_column($db->query('SELECT id FROM tujuan_renstra_neo WHERE misi_id=?',[$mission])->fetchAll(),'id');
            foreach($oldGoals as$goal){
                $oldTargets=array_column($db->query('SELECT id FROM sasaran_renstra_neo WHERE tujuan_id=?',[$goal])->fetchAll(),'id');
                foreach($oldTargets as$target){
                    $oldPrograms=array_column($db->query('SELECT id FROM program_renstra_neo WHERE sasaran_id=?',[$target])->fetchAll(),'id');
                    foreach($oldPrograms as$program){
                        $oldActivities=array_column($db->query('SELECT id FROM kegiatan_renstra_neo WHERE program_id=?',[$program])->fetchAll(),'id');
                        foreach($oldActivities as$activity)$db->query('DELETE FROM sub_kegiatan_renstra_neo WHERE kegiatan_renstra_id=?',[$activity]);
                        $db->query('DELETE FROM kegiatan_renstra_neo WHERE program_id=?',[$program]);
                        $db->query('DELETE FROM indikator_program_renstra_neo WHERE program_id=?',[$program]);
                    }
                    $db->query('DELETE FROM program_renstra_neo WHERE sasaran_id=?',[$target]);
                    $db->query('DELETE FROM indikator_sasaran_renstra_neo WHERE sasaran_id=?',[$target]);
                }
                $db->query('DELETE FROM sasaran_renstra_neo WHERE tujuan_id=?',[$goal]);
            }
            $db->query('DELETE FROM tujuan_renstra_neo WHERE misi_id=?',[$mission]);
        }
        $db->query('DELETE FROM misi_renstra_neo WHERE renstra_id=?',[$oldRenstra]);
        $db->query('DELETE FROM iku_opd_neo WHERE renstra_id=?',[$oldRenstra]);
        $db->query('DELETE FROM renstra_neo WHERE id=?',[$oldRenstra]);
    }

    $renstraRow=$fresh($source,$actor);
    $renstraRow['periode_id']=$periodId;
    $renstraRow['status']='draft';
    $renstraRow['kunci']=0;$renstraRow['setujui']=0;$renstraRow['disable']=0;
    $renstraRow['keterangan']='Struktur historis 2020-2025 diturunkan dari Renstra PUPR 2026-2030; baseline mewakili 2020 dan target T1-T5 mewakili 2021-2025.';
    $renstraId=(int)$db->insert('renstra_neo',$renstraRow);

    $missionMap=[];$goalMap=[];$targetMap=[];$programMap=[];$activityMap=[];
    foreach($db->query('SELECT * FROM misi_renstra_neo WHERE renstra_id=? AND is_deleted=0 ORDER BY id',[(int)$source['id']])->fetchAll()as$row){$old=(int)$row['id'];$copy=$fresh($row,$actor);$copy['renstra_id']=$renstraId;$missionMap[$old]=(int)$db->insert('misi_renstra_neo',$copy);}
    foreach($missionMap as$oldMission=>$newMission)foreach($db->query('SELECT * FROM tujuan_renstra_neo WHERE misi_id=? AND is_deleted=0 ORDER BY id',[$oldMission])->fetchAll()as$row){$old=(int)$row['id'];$copy=$fresh($row,$actor);$copy['misi_id']=$newMission;$goalMap[$old]=(int)$db->insert('tujuan_renstra_neo',$copy);}
    foreach($goalMap as$oldGoal=>$newGoal)foreach($db->query('SELECT * FROM sasaran_renstra_neo WHERE tujuan_id=? AND is_deleted=0 ORDER BY id',[$oldGoal])->fetchAll()as$row){$old=(int)$row['id'];$copy=$fresh($row,$actor);$copy['tujuan_id']=$newGoal;$targetMap[$old]=(int)$db->insert('sasaran_renstra_neo',$copy);}
    foreach($targetMap as$oldTarget=>$newTarget)foreach($db->query('SELECT * FROM program_renstra_neo WHERE sasaran_id=? AND is_deleted=0 ORDER BY id',[$oldTarget])->fetchAll()as$row){
        $old=(int)$row['id'];$copy=$fresh($row,$actor);$copy['sasaran_id']=$newTarget;$programMap[$old]=(int)$db->insert('program_renstra_neo',$copy);
        foreach($db->query('SELECT * FROM indikator_program_renstra_neo WHERE program_id=? AND is_deleted=0 ORDER BY id',[$old])->fetchAll()as$indicator){$indicator=$fresh($indicator,$actor);$indicator['program_id']=$programMap[$old];$db->insert('indikator_program_renstra_neo',$indicator);}
    }
    foreach($programMap as$oldProgram=>$newProgram)foreach($db->query('SELECT * FROM kegiatan_renstra_neo WHERE program_id=? AND is_deleted=0 ORDER BY id',[$oldProgram])->fetchAll()as$row){$old=(int)$row['id'];$copy=$fresh($row,$actor);$copy['program_id']=$newProgram;$activityMap[$old]=(int)$db->insert('kegiatan_renstra_neo',$copy);}
    foreach($activityMap as$oldActivity=>$newActivity)foreach($db->query('SELECT * FROM sub_kegiatan_renstra_neo WHERE kegiatan_renstra_id=? AND is_deleted=0 ORDER BY id',[$oldActivity])->fetchAll()as$row){$copy=$fresh($row,$actor);$copy['kegiatan_renstra_id']=$newActivity;$db->insert('sub_kegiatan_renstra_neo',$copy);}

    $ikuCount=0;
    foreach($db->query('SELECT * FROM iku_opd_neo WHERE renstra_id=? AND is_deleted=0 ORDER BY id',[(int)$source['id']])->fetchAll()as$row){
        $copy=$fresh($row,$actor);$copy['renstra_id']=$renstraId;
        $copy['sasaran_renstra_id']=$targetMap[(int)$row['sasaran_renstra_id']]??null;
        $copy['program_renstra_id']=$row['program_renstra_id']?($programMap[(int)$row['program_renstra_id']]??null):null;
        $copy['status']='draft';$copy['nomor_penetapan']=null;$copy['tanggal_penetapan']=null;
        $copy['keterangan']='IKU periode 2020-2025 mengikuti indikator dan target Renstra PUPR 2026-2030 sesuai arahan pengguna.';
        $db->insert('iku_opd_neo',$copy);$ikuCount++;
    }

    $missionNames=array_column($db->query('SELECT nama_misi FROM misi_renstra_neo WHERE renstra_id=? AND is_deleted=0 ORDER BY id',[$renstraId])->fetchAll(),'nama_misi');
    $targetNames=array_column($db->query('SELECT s.nama_sasaran FROM sasaran_renstra_neo s JOIN tujuan_renstra_neo t ON t.id=s.tujuan_id JOIN misi_renstra_neo m ON m.id=t.misi_id WHERE m.renstra_id=? AND s.is_deleted=0 ORDER BY s.id',[$renstraId])->fetchAll(),'nama_sasaran');
    $indicatorNames=array_column($db->query('SELECT nama_indikator FROM iku_opd_neo WHERE renstra_id=? AND is_deleted=0 ORDER BY kode_iku',[$renstraId])->fetchAll(),'nama_indikator');
    $rpjmd=$db->query('SELECT id FROM rpjmd_kabupaten_neo WHERE kd_wilayah=? AND berlaku_mulai=? AND berlaku_sampai=? LIMIT 1',[$wilayah,'2020-01-01','2025-12-31'])->fetch();
    $rpjmdData=['nama_dokumen'=>'RPJMD Kabupaten Pasangkayu Tahun 2020-2025','nomor_perda'=>null,'tanggal_perda'=>null,'berlaku_mulai'=>'2020-01-01','berlaku_sampai'=>'2025-12-31','visi'=>$source['visi'],'misi'=>json_encode($missionNames,JSON_UNESCAPED_UNICODE),'sasaran'=>json_encode($targetNames,JSON_UNESCAPED_UNICODE),'indikator'=>json_encode($indicatorNames,JSON_UNESCAPED_UNICODE),'status'=>'draft','keterangan'=>'Data historis untuk navigasi profil; substansi mengikuti struktur Renstra PUPR 2026-2030 dan belum dinyatakan sebagai salinan Perda resmi.','is_deleted'=>0,'tgl_update'=>date('Y-m-d H:i:s'),'username_update'=>$actor];
    if($rpjmd)$db->update('rpjmd_kabupaten_neo',$rpjmdData,'WHERE id=?',[(int)$rpjmd['id']]);
    else{$rpjmdData+=['kd_wilayah'=>$wilayah,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$actor];unset($rpjmdData['tgl_update'],$rpjmdData['username_update']);$db->insert('rpjmd_kabupaten_neo',$rpjmdData);}

    $counts=['misi'=>count($missionMap),'tujuan'=>count($goalMap),'sasaran'=>count($targetMap),'program'=>count($programMap),'kegiatan'=>count($activityMap),'sub_kegiatan'=>(int)$db->query('SELECT COUNT(*) n FROM sub_kegiatan_renstra_neo sk JOIN kegiatan_renstra_neo k ON k.id=sk.kegiatan_renstra_id JOIN program_renstra_neo p ON p.id=k.program_id JOIN sasaran_renstra_neo s ON s.id=p.sasaran_id JOIN tujuan_renstra_neo t ON t.id=s.tujuan_id JOIN misi_renstra_neo m ON m.id=t.misi_id WHERE m.renstra_id=? AND sk.is_deleted=0',[$renstraId])->fetch()['n'],'iku'=>$ikuCount];
    if($counts!==['misi'=>1,'tujuan'=>1,'sasaran'=>1,'program'=>10,'kegiatan'=>23,'sub_kegiatan'=>73,'iku'=>7])throw new RuntimeException('Rekonsiliasi struktur Renstra historis gagal: '.json_encode($counts));
    $db->commit();
    echo json_encode(['periode_id'=>$periodId,'renstra_id'=>$renstraId,'periode'=>'2020-2025','status'=>'draft','struktur'=>$counts],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
}catch(Throwable$e){$db->rollback();throw$e;}
