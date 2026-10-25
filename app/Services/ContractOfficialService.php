<?php
require_once __DIR__.'/../Core/DB.php';

/** Resolve signatories from the contract's own OPD/year, never from the viewer's name. */
final class ContractOfficialService
{
    public static function employeeName(array $e):string
    {
        $name=trim((string)($e['nama']??''));
        if($name===mb_strtolower($name)||$name===mb_strtoupper($name))$name=mb_convert_case($name,MB_CASE_TITLE,'UTF-8');
        $name=trim(trim((string)($e['gelar_depan']??'')).' '.$name);
        if(trim((string)($e['gelar']??''))!=='')$name.=', '.trim($e['gelar']);return $name;
    }
    public static function resolve(array $contract):array
    {
        if(($contract['status_kontrak']??'')==='draft'&&($contract['username_insert']??'')==='IMPORT_PERUBAHAN_PUPR_2026'){
            $name=mb_strtolower($contract['uraian_kontrak']??'');$start=$contract['tanggal_mulai']??$contract['tanggal_kontrak']??null;
            if($start&&preg_match('/stock|stok|perencanaan|penyusunan dokumen perencanaan|^sid /u',$name)){$contract['waktu_pelaksanaan']=30;$contract['tanggal_selesai']=date('Y-m-d',strtotime($start.' +29 days'));}
            elseif($start&&($contract['jenis_pengadaan']??'')==='PEKERJAAN_KONSTRUKSI'){$contract['tanggal_selesai']=$contract['tahun'].'-12-31';$contract['waktu_pelaksanaan']=(int)(new DateTimeImmutable($start))->diff(new DateTimeImmutable($contract['tanggal_selesai']))->days+1;}
        }
        $date=$contract['tanggal_kontrak']?:$contract['tahun'].'-12-31';
        $rows=DB::getInstance()->query("SELECT p.*,e.nama nama_asn,e.gelar_depan,e.gelar,e.golongan,e.ruang FROM pejabat_tahunan_neo p JOIN db_asn_pemda_neo e ON e.id=p.pegawai_id AND BINARY e.kd_wilayah=BINARY p.kd_wilayah AND BINARY e.kd_opd=BINARY p.kd_opd AND e.is_deleted=0 WHERE p.kd_wilayah=? AND p.kd_opd=? AND p.tahun=? AND p.is_deleted=0 AND p.berlaku_mulai<=? AND p.berlaku_sampai>=? AND (p.kd_sub_keg=? OR p.kd_sub_keg IS NULL OR p.kd_sub_keg='') ORDER BY p.id DESC",[$contract['kd_wilayah'],$contract['kd_opd'],$contract['tahun'],$date,$date,$contract['kd_sub_keg']])->fetchAll();
        foreach(['PPK'=>['ppk_id','nama_ppk_resmi','nip_ppk_resmi'],'PPTK'=>['pptk_id','nama_pptk','nip_pptk'],'PEJABAT_PENGADAAN'=>['pejabat_pengadaan_id','nama_pejabat_pengadaan','nip_pejabat_pengadaan']] as $type=>$fields){
            $candidates=array_values(array_filter($rows,fn($r)=>$r['jenis_pejabat']===$type&&($type==='PEJABAT_PENGADAAN'||$r['kd_sub_keg']===$contract['kd_sub_keg'])));
            $chosen=null;foreach($candidates as $r)if((int)$r['id']===(int)($contract[$fields[0]]??0)){$chosen=$r;break;}
            $chosen??=$candidates[0]??null;
            if($chosen){$name=self::employeeName(['nama'=>$chosen['nama_asn'],'gelar_depan'=>$chosen['gelar_depan'],'gelar'=>$chosen['gelar']]);$contract[$fields[0]]=(int)$chosen['id'];$contract[$fields[1]]=$name;$contract[$fields[2]]=$chosen['nip'];$contract['golongan_'.strtolower($type)]=$chosen['golongan'];$contract['ruang_'.strtolower($type)]=$chosen['ruang'];$contract['pangkat_'.strtolower($type)]=self::rank($chosen['golongan'],$chosen['ruang']);$contract['nomor_sk_'.strtolower($type)]=$chosen['nomor_sk'];$contract['tanggal_sk_'.strtolower($type)]=$chosen['tanggal_sk'];}
        }
        return $contract;
    }
    private static function rank($group,$space):string
    {
        $g=(int)$group;$r=strtolower((string)$space);$r=['1'=>'a','2'=>'b','3'=>'c','4'=>'d','5'=>'e'][$r]??$r;
        $names=['3a'=>'Penata Muda','3b'=>'Penata Muda Tingkat I','3c'=>'Penata','3d'=>'Penata Tingkat I','4a'=>'Pembina','4b'=>'Pembina Tingkat I','4c'=>'Pembina Utama Muda','4d'=>'Pembina Utama Madya','4e'=>'Pembina Utama'];$roman=[1=>'I',2=>'II',3=>'III',4=>'IV'][$g]??'';
        return isset($names[$g.$r])?$names[$g.$r].', '.$roman.'/'.$r:trim($roman.'/'.$r,'/');
    }
}
