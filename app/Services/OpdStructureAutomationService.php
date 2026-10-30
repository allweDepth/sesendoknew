<?php
require_once __DIR__.'/../Core/DB.php';

/** Builds structural positions from effective ASN history within one authenticated OPD. */
final class OpdStructureAutomationService
{
    private static function scope(array $user): array
    {
        if (!in_array($user['type_user'] ?? '', ['admin_wilayah','admin_opd','kepala_opd','pa_kpa'], true)) throw new RuntimeException('Anda tidak memiliki hak menyusun struktur OPD.');
        if (empty($user['kd_wilayah']) || empty($user['kd_opd']) || in_array($user['kd_opd'], ['0','SETDA','BUPATI','GUBERNUR','WALIKOTA'], true) || ($user['structure_scope'] ?? '') === 'regional') throw new RuntimeException('Pilih OPD yang akan disusun. Pimpinan wilayah dikelola melalui form role daerah.');
        if (empty($user['tahun']) || !preg_match('/^\d{4}$/', (string)$user['tahun'])) throw new RuntimeException('Tahun anggaran tidak valid.');
        return [$user['kd_wilayah'], $user['kd_opd'], (int)$user['tahun']];
    }
    public static function level(string $job): ?int
    {
        $job=mb_strtolower(trim($job));
        if (preg_match('/^(kepala (dinas|badan|opd)|camat|direktur)\b/u',$job)) return 1;
        if (preg_match('/^(sekretaris|sekertaris)\b/u',$job)) return 2;
        if (preg_match('/^(kabid|kepala bidang|kepala bagian|kabag|kepala uptd|kepala upt)\b/u',$job)) return 3;
        if (preg_match('/^(kasubag|kasubbag|kepala subbagian|kepala sub bagian|kepala seksi|kasi)\b/u',$job)) return 4;
        return null;
    }
    private static function unit(string $name): string
    {
        $name=mb_strtolower($name);
        $name=preg_replace('/\b(kepala|bidang|kabid|bagian|kabag|uptd|upt)\b/u','',$name);
        return preg_replace('/[^\p{L}\p{N}]/u','',$name);
    }
    public static function plan(DB $db,array $user): array
    {
        [$region,$opd,$year]=self::scope($user);
        $today=date('Y-m-d');
        $employees=$db->query('SELECT * FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd=? AND is_deleted=0 AND disable=0 AND COALESCE(aktif,1)=1 ORDER BY id',[$region,$opd])->fetchAll();
        $existing=$db->query("SELECT * FROM struktur_jabatan_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 AND status_jabatan='AKTIF' AND (berlaku_sampai IS NULL OR berlaku_sampai>=?) ORDER BY id",[$region,$opd,$year,$today])->fetchAll();
        $rows=[];$skipped=0;
        foreach($employees as$e){
            $h=$db->query('SELECT * FROM riwayat_jabatan_neo WHERE pegawai_id=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0 AND disable=0 AND tmt<=? AND (tanggal_sk IS NULL OR tanggal_sk<=?) AND (tanggal_selesai IS NULL OR tanggal_selesai>=?) ORDER BY tmt DESC,COALESCE(tanggal_sk,tmt) DESC,id DESC LIMIT 1',[$e['id'],$region,$opd,$today,$today,$today])->fetch();
            $job=trim((string)($h['jabatan']??$e['jabatan']));$level=self::level($job);
            if($level===null){$skipped++;continue;}
            $old=null;foreach($existing as$s)if((int)$s['pegawai_id']===(int)$e['id']){$old=$s;break;}
            $conflict=false;foreach($existing as$s)if(!$old&&mb_strtolower($s['nama_jabatan'])===mb_strtolower($job))$conflict=true;
            $date=$h['tmt']??$e['tgl_tmt_akhir']??null;
            $rows[]=['employee_id'=>(int)$e['id'],'name'=>trim($e['nama'].($e['gelar']?', '.$e['gelar']:'')),'job'=>$job,'unit'=>$e['unit_kerja']??'','level'=>$level,'parent_employee_id'=>null,'existing_id'=>$old?(int)$old['id']:null,'number'=>$h['nomor_sk']??$e['nmr_sk_terakhir']??null,'sk_date'=>$h['tanggal_sk']??$e['tgl_sk_terakhir']??null,'tmt'=>$date,'status'=>$old?'existing':($conflict?'conflict':(!$date?'missing_tmt':'new')),'review'=>[]];
        }
        $jobs=[];
        foreach($rows as$r)$jobs[mb_strtolower($r['job'])]=($jobs[mb_strtolower($r['job'])]??0)+1;
        foreach($rows as&$r)if($r['status']==='new'&&$jobs[mb_strtolower($r['job'])]>1){$r['status']='duplicate_job';$r['review'][]='Lebih dari satu ASN tercatat untuk jabatan ini; periksa riwayat yang berlaku';}unset($r);
        usort($rows,fn($a,$b)=>[$a['level'],$a['job'],$a['employee_id']]<=>[$b['level'],$b['job'],$b['employee_id']]);
        $heads=array_values(array_filter($rows,fn($r)=>$r['level']===1));
        foreach($rows as&$r){
            if($r['level']>1){
                $parents=[];
                if($r['level']===4){
                    foreach($rows as$p){if($p['level']>=$r['level'])continue;
                        if(preg_match('/^(kasubag|kasubbag|kepala sub)/iu',$r['job'])&&!preg_match('/\buptd?\b/iu',$r['job'])&&$p['level']===2)$parents[]=$p;
                        elseif($p['level']===3&&preg_match('/\buptd?\b/iu',$r['job'])&&preg_match('/\buptd?\b/iu',$p['job'])&&str_contains(str_replace('air','',self::unit($r['job'])),str_replace('air','',self::unit($p['job']))))$parents[]=$p;
                        elseif($p['level']===3&&self::unit($r['unit'])!==''&&self::unit($r['unit'])===self::unit($p['job']))$parents[]=$p;
                    }
                }
                if(!$parents)$parents=$heads;
                if(count($parents)===1)$r['parent_employee_id']=$parents[0]['employee_id'];
                else{$r['review'][]='Atasan perlu dipilih manual';if($r['status']==='new')$r['status']='ambiguous_parent';}
            }
            if(!$r['number']||!$r['sk_date'])$r['review'][]='Lengkapi nomor/tanggal SK';
        }unset($r);
        return ['rows'=>$rows,'skipped'=>$skipped,'scope'=>['kd_wilayah'=>$region,'kd_opd'=>$opd,'tahun'=>$year]];
    }
    /** Caller owns the transaction. Lock the scope before replanning to prevent duplicates. */
    public static function apply(DB $db,array $user): array
    {
        [$region,$opd,$year]=self::scope($user);
        $db->query('SELECT id FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd=? ORDER BY id FOR UPDATE',[$region,$opd])->fetchAll();
        $plan=self::plan($db,$user);$ids=[];$added=0;
        foreach($plan['rows']as$r)if($r['existing_id'])$ids[$r['employee_id']]=$r['existing_id'];
        foreach($plan['rows']as$r){
            if($r['status']!=='new')continue;
            $parent=$r['parent_employee_id']?($ids[$r['parent_employee_id']]??null):null;
            if($r['level']>1&&!$parent)continue;
            $id=$db->insert('struktur_jabatan_opd_neo',['kd_wilayah'=>$region,'kd_opd'=>$opd,'tahun'=>$year,'pegawai_id'=>$r['employee_id'],'parent_id'=>$parent,'parent_kd_opd'=>$parent?$opd:null,'nama_jabatan'=>$r['job'],'kelompok_jabatan'=>'Struktural','eselon'=>null,'nomor_sk_pengangkatan'=>$r['number']?:null,'tanggal_sk_pengangkatan'=>$r['sk_date']?:null,'tmt_jabatan'=>$r['tmt'],'berlaku_mulai'=>$r['tmt'],'status_jabatan'=>'AKTIF','urutan'=>$r['level']*100+$added,'keterangan'=>'Disusun otomatis dari jabatan ASN/riwayat berlaku. '.implode('; ',$r['review']),'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$user['username']??'AUTOMASI_STRUKTUR','is_deleted'=>0]);
            $ids[$r['employee_id']]=(int)$id;$added++;
        }
        return ['added'=>$added,'preserved'=>count(array_filter($plan['rows'],fn($r)=>$r['status']==='existing')),'pending'=>count(array_filter($plan['rows'],fn($r)=>!in_array($r['status'],['new','existing'],true))),'skipped'=>$plan['skipped']];
    }
}
