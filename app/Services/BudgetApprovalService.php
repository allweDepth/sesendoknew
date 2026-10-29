<?php
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/BudgetScheduleService.php';
/** Formal OPD/year approvals; published budget snapshots are immutable. */
final class BudgetApprovalService
{
    public static function label(array $r):string{return ($r['jenis']==='DPA'?'DPA':($r['jenis']==='PARSIAL'?'DPPA Parsial '.$r['urutan']:'DPPA Perubahan')).' · '.$r['nomor'].' · '.$r['tanggal'];}
    public static function stage(array $r):string{return $r['jenis']==='DPA'?'dpa':'dppa';}
    public static function latest(array $rows,string $date):?array
    {
        $eligible=array_values(array_filter($rows,fn($r)=>$r['status']==='DISAHKAN'&&$r['tanggal']<=$date));
        usort($eligible,fn($a,$b)=>[$b['tanggal'],['DPA'=>0,'PARSIAL'=>1,'PERUBAHAN'=>2][$b['jenis']],(int)$b['urutan'],(int)$b['id']]<=>[$a['tanggal'],['DPA'=>0,'PARSIAL'=>1,'PERUBAHAN'=>2][$a['jenis']],(int)$a['urutan'],(int)$a['id']]);return $eligible[0]??null;
    }
    public static function rows(array $u,string $opd=''):array
    {
        $w=$u['kd_wilayah']??'';$y=(int)($u['tahun']??0);$own=$u['kd_opd']??'';
        if(!BudgetScheduleService::canManage($u)){if($own===''||$own==='0')return [];$opd=$own;}
        $params=[$w,$y];$extra='';if($opd!==''&&$opd!=='0'){$extra=' AND a.kd_opd=?';$params[]=$opd;}
        $rows=DB::getInstance()->query("SELECT a.*,o.uraian nama_opd,(SELECT COUNT(*) FROM anggaran_pengesahan_rincian_neo s WHERE s.pengesahan_id=a.id) jumlah_rincian FROM anggaran_pengesahan_neo a LEFT JOIN organisasi_neo o ON BINARY o.kode=BINARY a.kd_opd AND BINARY o.kd_wilayah=BINARY a.kd_wilayah AND o.is_deleted=0 WHERE a.kd_wilayah=? AND a.tahun=?$extra ORDER BY a.kd_opd,a.tanggal,a.id",$params)->fetchAll();foreach($rows as&$r)$r['label']=self::label($r);return$rows;
    }
    public static function forContract(array $c,?int $requested=null):?array
    {
        $rows=DB::getInstance()->query('SELECT * FROM anggaran_pengesahan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND status="DISAHKAN"',[$c['kd_wilayah'],$c['kd_opd'],$c['tahun']])->fetchAll();
        if(!$rows){if($requested)throw new InvalidArgumentException('Pengesahan anggaran tidak ditemukan dalam OPD/tahun kontrak.');return null;}
        $date=(string)($c['tanggal_kontrak']??'');if(!self::dateValid($date))throw new InvalidArgumentException('Isi tanggal kontrak agar DPA/DPPA yang berlaku dapat ditentukan.');
        $latest=self::latest($rows,$date);if(!$latest)throw new InvalidArgumentException('Belum ada DPA/DPPA yang disahkan pada tanggal kontrak '.$date.'. Hubungi TAPD.');
        if($requested&&(int)$latest['id']!==$requested)throw new InvalidArgumentException('Tanggal kontrak '.$date.' menggunakan '.self::label($latest).'. Pilih ulang rincian anggaran.');return$latest;
    }
    public static function assertBudget(array $c,string $stage,int $sourceId,?int $approvalId=null):?array
    {
        $a=self::forContract($c,$approvalId?:null);if(!$a)return null;
        if(self::stage($a)!==$stage)throw new InvalidArgumentException('Tanggal kontrak '.$c['tanggal_kontrak'].' menggunakan '.self::label($a).'. Pilih rincian dokumen tersebut.');
        $r=DB::getInstance()->query('SELECT *,pagu jumlah FROM anggaran_pengesahan_rincian_neo WHERE pengesahan_id=? AND tahap=? AND anggaran_id=?',[$a['id'],$stage,$sourceId])->fetch();if(!$r)throw new InvalidArgumentException('Uraian tidak tercantum pada '.self::label($a));$r['pengesahan_anggaran_id']=$a['id'];return$r;
    }
    public static function dateValid(string $d):bool{$x=DateTimeImmutable::createFromFormat('!Y-m-d',$d);return$x&&$x->format('Y-m-d')===$d;}
    public static function save(array $u,array $in):array
    {
        if(!BudgetScheduleService::canManage($u))throw new RuntimeException('Hanya TAPD/admin wilayah yang dapat mengelola pengesahan anggaran.');
        $db=DB::getInstance();$opd=trim((string)($in['kd_opd']??''));$jenis=(string)($in['jenis']??'');$seq=(int)($in['urutan']??0);$number=trim((string)($in['nomor']??''));$date=trim((string)($in['tanggal']??''));$id=(int)($in['id']??0);$publish=($in['status']??'DRAFT')==='DISAHKAN';
        if(!in_array($jenis,['DPA','PARSIAL','PERUBAHAN'],true)||$number===''||mb_strlen($number)>255||!self::dateValid($date)||($jenis==='PARSIAL'&&$seq<1))throw new InvalidArgumentException('OPD, jenis, nomor, tanggal pengesahan, dan urutan parsial wajib valid.');if($jenis!=='PARSIAL')$seq=0;
        if(!$db->query('SELECT kode FROM organisasi_neo WHERE kd_wilayah=? AND kode=? AND is_deleted=0',[$u['kd_wilayah'],$opd])->fetch())throw new InvalidArgumentException('OPD tidak terdaftar pada wilayah ini.');
        $db->begin();try{
            $db->query('SELECT kode FROM organisasi_neo WHERE kd_wilayah=? AND kode=? AND is_deleted=0 FOR UPDATE',[$u['kd_wilayah'],$opd])->fetch();
            if($id){$old=$db->query('SELECT * FROM anggaran_pengesahan_neo WHERE id=? AND kd_wilayah=? AND tahun=? FOR UPDATE',[$id,$u['kd_wilayah'],$u['tahun']])->fetch();if(!$old||$old['status']==='DISAHKAN')throw new InvalidArgumentException('Pengesahan yang sudah disahkan tidak dapat diedit. Buat versi berikutnya.');}
            $other=$db->query('SELECT * FROM anggaran_pengesahan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND status="DISAHKAN" ORDER BY tanggal DESC,id DESC',[$u['kd_wilayah'],$opd,$u['tahun']])->fetchAll();
            if($publish){$previous=self::latest($other,'9999-12-31');if($previous&&($date<$previous['tanggal']||$jenis==='DPA'||($jenis==='PARSIAL'&&($previous['jenis']==='PERUBAHAN'||($previous['jenis']==='PARSIAL'&&$seq<=(int)$previous['urutan'])))))throw new InvalidArgumentException('Urutan pengesahan harus sesudah versi sebelumnya. Parsial dibuat berurutan sebelum DPPA Perubahan.');if($jenis!=='DPA'&&!array_filter($other,fn($r)=>$r['jenis']==='DPA'))throw new InvalidArgumentException('Sahkan DPA awal sebelum pengesahan parsial/perubahan.');}
            $values=['kd_wilayah'=>$u['kd_wilayah'],'kd_opd'=>$opd,'tahun'=>(int)$u['tahun'],'jenis'=>$jenis,'urutan'=>$seq,'nomor'=>$number,'tanggal'=>$date,'dasar_hukum'=>trim((string)($in['dasar_hukum']??'')),'status'=>'DRAFT'];
            if($id)$db->update('anggaran_pengesahan_neo',$values+['username_update'=>$u['username'],'tgl_update'=>date('Y-m-d H:i:s')],'WHERE id=?',[$id]);else$id=(int)$db->insert('anggaran_pengesahan_neo',$values+['username_insert'=>$u['username']]);
            if($publish){$stage=$jenis==='DPA'?'dpa':'dppa';$db->query("INSERT INTO anggaran_pengesahan_rincian_neo(pengesahan_id,tahap,anggaran_id,kd_sub_keg,kd_akun,uraian,pagu) SELECT ?,?,id,kd_sub_keg,kd_akun,COALESCE(uraian,''),jumlah FROM {$stage}_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND setujui=1 AND kunci=1 AND is_deleted=0",[$id,$stage,$u['kd_wilayah'],$opd,$u['tahun']]);if(!$db->query('SELECT id FROM anggaran_pengesahan_rincian_neo WHERE pengesahan_id=? LIMIT 1',[$id])->fetch())throw new InvalidArgumentException('Tidak ada rincian '.$stage.' yang disetujui dan dikunci untuk disahkan.');$db->query('UPDATE anggaran_pengesahan_neo SET status="DISAHKAN" WHERE id=?',[$id]);}
            $db->commit();return['id'=>$id,'status'=>$publish?'DISAHKAN':'DRAFT'];
        }catch(Throwable$e){$db->rollback();throw$e;}
    }
    public static function delete(array $u,int $id):void
    {
        if(!BudgetScheduleService::canManage($u))throw new RuntimeException('Hanya TAPD/admin wilayah.');$db=DB::getInstance();$r=$db->query('SELECT * FROM anggaran_pengesahan_neo WHERE id=? AND kd_wilayah=? AND tahun=?',[$id,$u['kd_wilayah'],$u['tahun']])->fetch();if(!$r||$r['status']!=='DRAFT')throw new InvalidArgumentException('Hanya draft pengesahan yang dapat dihapus.');$db->query('DELETE FROM anggaran_pengesahan_neo WHERE id=? AND status="DRAFT"',[$id]);
    }
}
