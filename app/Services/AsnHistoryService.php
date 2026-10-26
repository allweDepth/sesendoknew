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
        if(empty($user['kd_wilayah']))return;$sql='SELECT * FROM db_asn_pemda_neo WHERE kd_wilayah=? AND is_deleted=0';$args=[$user['kd_wilayah']];if(!empty($user['kd_opd'])&&$user['kd_opd']!=='0'){$sql.=' AND kd_opd=?';$args[]=$user['kd_opd'];}
        foreach($db->query($sql,$args)->fetchAll()as$r){ self::seed($db,$r,(int)substr(self::today(),0,4)); self::sync($db,(int)$r['id']); }
    }
}
