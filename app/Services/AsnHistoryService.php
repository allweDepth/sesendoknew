<?php
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/FieldValidationException.php';
/** History is authoritative; master ASN is the current effective projection. */
final class AsnHistoryService
{
    public const JOB='riwayat_jabatan_neo';
    public const GRADE='riwayat_pangkat_neo';
    public static function isHistory(string $table):bool{return in_array($table,[self::JOB,self::GRADE],true);}
    public static function today():string{return (new DateTimeImmutable('now',new DateTimeZone('Asia/Makassar')))->format('Y-m-d');}
    public static function normalize(DB $db,string $table,array $data,array $old=[],array $user=[]):array
    {
        if(!self::isHistory($table))return$data;
        $row=array_replace($old,$data);$id=(int)($row['pegawai_id']??0);
        $employee=$db->query('SELECT * FROM db_asn_pemda_neo WHERE id=? AND is_deleted=0',[$id])->fetch();
        if(!$employee)throw new FieldValidationException('pegawai_id','ASN tidak ditemukan.');
        $regional=in_array($user['type_user']??'', ['super_admin','admin_wilayah','tapd'],true);
        if($regional && (empty($user['kd_opd']) || $user['kd_opd']==='0')) {
            if(($user['type_user']??'')!=='super_admin' && (string)($user['kd_wilayah']??'')!==(string)$employee['kd_wilayah']) throw new FieldValidationException('pegawai_id','ASN di luar wilayah pengguna.');
            $data['kd_opd']=$row['kd_opd']=$employee['kd_opd'];
            if(($user['type_user']??'')==='super_admin' && empty($user['kd_wilayah'])) $data['kd_wilayah']=$row['kd_wilayah']=$employee['kd_wilayah'];
        }
        foreach(['kd_wilayah','kd_opd']as$key)if((string)($row[$key]??'')!==(string)$employee[$key])throw new FieldValidationException('pegawai_id','ASN harus berada di wilayah dan OPD riwayat ini.');
        foreach(['tmt','tanggal_sk','tanggal_selesai']as$key){if(!array_key_exists($key,$row))continue;$value=trim((string)$row[$key]);if($value===''){if($key==='tmt')throw new FieldValidationException($key,'Isi tanggal mulai berlaku.');$data[$key]=null;continue;}$value=preg_replace('/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}:\d{2}$/','$1',$value);$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);if(!$date||$date->format('Y-m-d')!==$value)throw new FieldValidationException($key,'Tanggal harus valid.');$data[$key]=$value;$row[$key]=$value;}
        $data['tahun']=(int)substr($row['tmt'],0,4);
        if(!empty($row['tanggal_selesai'])&&$row['tanggal_selesai']<$row['tmt'])throw new FieldValidationException('tanggal_selesai','Tanggal selesai tidak boleh sebelum TMT.');
        if($table===self::GRADE){$grade=strtoupper(trim((string)($row['golongan']??'')));$grade=['I'=>'1','II'=>'2','III'=>'3','IV'=>'4'][$grade]??$grade;if(!in_array($grade,['1','2','3','4'],true))throw new FieldValidationException('golongan','Pilih golongan I sampai IV.');$space=strtolower(trim((string)($row['ruang']??'')));if(!in_array($space,['a','b','c','d','e'],true))throw new FieldValidationException('ruang','Pilih ruang a sampai e.');$data['golongan']=$grade;$data['ruang']=$space;}
        if($table===self::JOB && isset($user['type_user']) && !in_array($user['type_user'],['super_admin','admin_wilayah','tapd'],true)) $data['unit_kerja']=$employee['kd_opd'];
        if($table===self::JOB){$job=trim((string)($row['jabatan']??''));if($job===''||mb_strlen($job)>255)throw new FieldValidationException('jabatan','Isi jabatan maksimal 255 karakter.');$data['jabatan']=$job;$group=$row['kelompok']??null;if($group!==null&&$group!==''&&!in_array((int)$group,[1,2,3,4,5],true))throw new FieldValidationException('kelompok','Kelompok jabatan tidak valid.');$data['kelompok']=$group===null||$group===''?self::group($job):(int)$group;}
        return$data;
    }
    private static function group(string $job):int
    {
        $j=mb_strtolower(trim($job));if(preg_match('/^(kepala (dinas|badan|opd)|camat|direktur)/u',$j))return 1;if(preg_match('/^se(k|ke)r?etaris|^sekertaris/u',$j))return 2;if(preg_match('/^(kabid|kepala bidang)/u',$j))return 3;return 4;
    }
    public static function sync(DB $db,int $id,?string $today=null):void
    {
        $today??=self::today();$employee=$db->query('SELECT * FROM db_asn_pemda_neo WHERE id=? AND is_deleted=0 FOR UPDATE',[$id])->fetch();if(!$employee)return;
        $changes=[];
        foreach([self::JOB,self::GRADE]as$table){
            $where='pegawai_id=? AND kd_wilayah=? AND kd_opd=?';$args=[$id,$employee['kd_wilayah'],$employee['kd_opd']];
            $end=$table===self::JOB?' AND (tanggal_selesai IS NULL OR tanggal_selesai>=?)':'';
            $params=[...$args,$today,$today];if($end)$params[]=$today;
            $r=$db->query("SELECT * FROM $table WHERE $where AND is_deleted=0 AND disable=0 AND tmt<=? AND (tanggal_sk IS NULL OR tanggal_sk<=?)$end ORDER BY tmt DESC,COALESCE(tanggal_sk,tmt) DESC,id DESC LIMIT 1",$params)->fetch();
            if(!$r){$ever=$db->query("SELECT id FROM $table WHERE $where LIMIT 1",$args)->fetch();if(!$ever)continue;$values=$table===self::JOB?['jabatan'=>'','kelompok'=>4]:['golongan'=>'','ruang'=>''];}
            else{$values=$table===self::JOB?['jabatan'=>$r['jabatan'],'kelompok'=>$r['kelompok']?:self::group($r['jabatan'])]:['golongan'=>$r['golongan'],'ruang'=>$r['ruang']];if($table===self::JOB&&trim((string)$r['unit_kerja'])!=='')$values['unit_kerja']=$r['unit_kerja'];}
            foreach($values as$key=>$v)if((string)$employee[$key] !== (string)$v)$changes[$key]=$v;
        }
        if($changes)$db->update('db_asn_pemda_neo',$changes+['username_update'=>'SINKRON_RIWAYAT_ASN','tgl_update'=>date('Y-m-d H:i:s')],'WHERE id=?',[$id]);
    }
    public static function afterHistory(DB $db,string $table,int $id,array $old=[]):void
    {
        if(!self::isHistory($table))return;$row=$db->query("SELECT pegawai_id FROM $table WHERE id=?",[$id])->fetch();$ids=array_unique(array_filter([(int)($row['pegawai_id']??0),(int)($old['pegawai_id']??0)]));sort($ids);foreach($ids as$employee)self::sync($db,$employee);
    }
    /** Record only known present values, with an explicit assumed January-1 TMT. */
    public static function seed(DB $db,array $employee,int $year,string $actor='RIWAYAT_AWAL_ASN'):array
    {
        $result=[];$base=['tahun'=>$year,'kd_wilayah'=>$employee['kd_wilayah'],'kd_opd'=>$employee['kd_opd'],'pegawai_id'=>$employee['id'],'tmt'=>sprintf('%04d-01-01',$year),'nomor_sk'=>null,'tanggal_sk'=>null,'keterangan'=>'Data awal dari master ASN; TMT 1 Januari asumsi pengguna, bukan tanggal/SK historis terverifikasi.','username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0,'disable'=>0];
        foreach([self::JOB,self::GRADE]as$table){$exists=$db->query("SELECT id FROM $table WHERE pegawai_id=? AND kd_wilayah=? AND kd_opd=? AND is_deleted=0 LIMIT 1",[$employee['id'],$employee['kd_wilayah'],$employee['kd_opd']])->fetch();if($exists)continue;if($table===self::JOB){if(in_array(trim((string)$employee['jabatan']),['','-','0','non'],true))continue;$fields=['jabatan'=>$employee['jabatan'],'kelompok'=>$employee['kelompok']?:self::group($employee['jabatan']),'unit_kerja'=>$employee['unit_kerja']];}else{if(!in_array((string)$employee['golongan'],['1','2','3','4'],true)||!in_array(strtolower((string)$employee['ruang']),['a','b','c','d','e'],true))continue;$fields=['golongan'=>$employee['golongan'],'ruang'=>strtolower($employee['ruang'])];}$result[$table]=(int)$db->insert($table,$base+$fields);}
        return$result;
    }
    /** Edits through the master form are recorded rather than silently losing history. */
    public static function afterMaster(DB $db,int $id,array $old=[]):void
    {
        $e=$db->query('SELECT * FROM db_asn_pemda_neo WHERE id=? AND is_deleted=0',[$id])->fetch();if(!$e)return;$year=(int)substr(self::today(),0,4);self::seed($db,$old?:$e,$year);
        if(!$old)return;
        foreach([self::JOB=>['jabatan','kelompok','unit_kerja'],self::GRADE=>['golongan','ruang']]as$table=>$keys){$changed=false;foreach($keys as$key)if((string)($old[$key]??'')!==(string)($e[$key]??''))$changed=true;if(!$changed)continue;$data=['pegawai_id'=>$id,'kd_wilayah'=>$e['kd_wilayah'],'kd_opd'=>$e['kd_opd'],'tahun'=>$year,'tmt'=>self::today(),'tanggal_sk'=>null,'nomor_sk'=>null,'keterangan'=>'Perubahan dari form master ASN; lengkapi nomor/tanggal SK jika tersedia.','username_insert'=>$e['username_update']?:'FORM_ASN'];foreach($keys as$key)$data[$key]=$e[$key];$data=self::normalize($db,$table,$data);$db->insert($table,$data);}
        self::sync($db,$id);
    }
    public static function refreshScope(DB $db,array $user):void
    {
        if(empty($user['kd_wilayah']))return;
        $where='kd_wilayah=? AND is_deleted=0';$args=[$user['kd_wilayah']];
        if(!empty($user['kd_opd'])&&$user['kd_opd']!=='0'){$where.=' AND kd_opd=?';$args[]=$user['kd_opd'];}
        // One scoped read replaces seed/sync SELECTs for every ASN on each table request.
        $employees=$db->query("SELECT * FROM db_asn_pemda_neo WHERE $where ORDER BY id FOR UPDATE",$args)->fetchAll();
        if(!$employees)return;
        $byId=array_column($employees,null,'id');$histories=[];$today=self::today();
        foreach([self::JOB,self::GRADE]as$table){
            $hw='h.kd_wilayah=?';$ha=[$user['kd_wilayah']];
            if(!empty($user['kd_opd'])&&$user['kd_opd']!=='0'){$hw.=' AND h.kd_opd=?';$ha[]=$user['kd_opd'];}
            $histories[$table]=[];
            foreach($db->query("SELECT h.* FROM $table h WHERE $hw ORDER BY h.tmt DESC,COALESCE(h.tanggal_sk,h.tmt) DESC,h.id DESC",$ha)->fetchAll()as$h){
                $e=$byId[$h['pegawai_id']]??null;
                if($e&&(string)$h['kd_opd']===(string)$e['kd_opd'])$histories[$table][$h['pegawai_id']][]=$h;
            }
        }
        foreach($employees as$e){
            $needSeed=false;
            foreach([self::JOB,self::GRADE]as$table){
                $active=array_filter($histories[$table][$e['id']]??[],fn($h)=>!(int)$h['is_deleted']);
                $known=$table===self::JOB?!in_array(trim((string)$e['jabatan']),['','-','0','non'],true):(in_array((string)$e['golongan'],['1','2','3','4'],true)&&in_array(strtolower((string)$e['ruang']),['a','b','c','d','e'],true));
                if(!$active&&$known)$needSeed=true;
            }
            if($needSeed){
                $inserted=self::seed($db,$e,(int)substr($today,0,4));
                foreach($inserted as$table=>$id)$histories[$table][$e['id']][]=$db->query("SELECT * FROM $table WHERE id=?",[$id])->fetch();
            }
            $changes=[];
            foreach([self::JOB,self::GRADE]as$table){
                $history=$histories[$table][$e['id']]??[];$current=null;
                foreach($history as$h){
                    if((int)$h['is_deleted']||(int)$h['disable']||$h['tmt']>$today||(!empty($h['tanggal_sk'])&&$h['tanggal_sk']>$today))continue;
                    if($table===self::JOB&&!empty($h['tanggal_selesai'])&&$h['tanggal_selesai']<$today)continue;
                    $current=$h;break;
                }
                if(!$history)continue;
                if($table===self::JOB){$values=$current?['jabatan'=>$current['jabatan'],'kelompok'=>$current['kelompok']?:self::group($current['jabatan'])]:['jabatan'=>'','kelompok'=>4];if($current&&trim((string)$current['unit_kerja'])!=='')$values['unit_kerja']=$current['unit_kerja'];}
                else $values=$current?['golongan'=>$current['golongan'],'ruang'=>$current['ruang']]:['golongan'=>'','ruang'=>''];
                foreach($values as$k=>$v)if((string)$e[$k] !== (string)$v)$changes[$k]=$v;
            }
            if($changes)$db->update('db_asn_pemda_neo',$changes+['username_update'=>'SINKRON_RIWAYAT_ASN','tgl_update'=>date('Y-m-d H:i:s')],'WHERE id=?',[$e['id']]);
        }
    }
}
