<?php
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/FieldValidationException.php';

/** Per-stage document identities. Missing dates/numbers are never invented. */
final class ProcurementProcessService
{
    public static function flow(array $c):string
    {
        if(($c['cara_pengadaan']??'')==='SWAKELOLA')return 'SWAKELOLA';
        $method=mb_strtolower(trim($c['metode_pemilihan']??''));
        if(preg_match('/katalog|e[ -]?purchas|e[ -]?catalog/u',$method))return 'KATALOG';
        if(preg_match('/tender|lelang|seleksi/u',$method))return 'TENDER';
        if(str_contains($method,'pengadaan langsung'))return 'LANGSUNG';
        return 'LAINNYA';
    }
    public static function catalog():array
    {
        $common=[['KONTRAK','Penandatanganan Kontrak / SPK','header'],['SPMK','Surat Perintah Mulai Kerja (SPMK)','header'],['RAPAT_PRA_KONTRAK','Rapat Persiapan Kontrak'],['SSKK','Syarat-Syarat Khusus Kontrak'],['SSUK','Syarat-Syarat Umum Kontrak']];
        $profiles=[
            'LANGSUNG'=>['label'=>'Pengadaan Langsung','steps'=>array_merge([
                ['JADWAL_DOKUMEN','Jadwal dan Kelengkapan Dokumen'],['BA_SURVEY_HARGA','Survei Harga'],['PAKTA_INTEGRITAS','Pakta Integritas'],['UNDANGAN_PL','Undangan Pengadaan Langsung'],['UPLOAD_PENAWARAN','Pemasukan Dokumen Penawaran'],['PEMBUKAAN_PENAWARAN','Pembukaan Dokumen Penawaran'],['BA_EVALUASI','Evaluasi Penawaran'],['EVALUASI_KUALIFIKASI','Evaluasi Kualifikasi'],['BA_KLARIFIKASI_NEGOSIASI','Klarifikasi dan Negosiasi'],['DAFTAR_HADIR_KLARIFIKASI','Daftar Hadir Klarifikasi dan Negosiasi'],['PERNYATAAN_PENURUNAN','Pernyataan Kesanggupan Menurunkan Biaya'],['LAMPIRAN_NEGOSIASI','Lampiran Biaya Setelah Negosiasi'],['BAHP','Berita Acara Hasil Pengadaan'],['PENETAPAN_PENYEDIA','Penetapan Penyedia'],['PENGUMUMAN_PENYEDIA','Pengumuman Penyedia'],['SPPBJ','Surat Penunjukan Penyedia (SPPBJ)']],$common)],
            'TENDER'=>['label'=>'Tender / Seleksi / Lelang','steps'=>array_merge([['PENGUMUMAN_TENDER','Pengumuman Tender / Seleksi'],['PAKTA_INTEGRITAS','Pakta Integritas'],['BA_AANWIJZING','Pemberian Penjelasan / Aanwijzing'],['UPLOAD_PENAWARAN','Pemasukan Dokumen Penawaran'],['PEMBUKAAN_PENAWARAN','Pembukaan Dokumen Penawaran'],['BA_EVALUASI','Evaluasi Penawaran'],['EVALUASI_KUALIFIKASI','Evaluasi Kualifikasi'],['BA_KLARIFIKASI_NEGOSIASI','Klarifikasi / Negosiasi (jika dilakukan)'],['BAHP','Berita Acara Hasil Pemilihan'],['PENETAPAN_PENYEDIA','Penetapan Pemenang'],['PENGUMUMAN_PENYEDIA','Pengumuman Pemenang'],['SANGGAH','Masa Sanggah'],['SPPBJ','Surat Penunjukan Penyedia (SPPBJ)']],$common)],
            'KATALOG'=>['label'=>'E-purchasing / E-katalog','steps'=>[['PERSIAPAN_PESANAN','Persiapan / Permintaan Pembelian'],['BA_KLARIFIKASI_NEGOSIASI','Negosiasi E-purchasing (jika dilakukan)'],['SURAT_PESANAN','Surat / Bukti Pesanan'],['KONTRAK','Tanggal dan Nomor Kontrak / Pesanan','header'],['PENGIRIMAN','Pengiriman / Pelaksanaan'],['BAST','Serah Terima']]],
            'LAINNYA'=>['label'=>'Pengadaan Melalui Penyedia','steps'=>$common],
            'SWAKELOLA'=>['label'=>'Swakelola','steps'=>[]],
        ];
        foreach(DB::getInstance()->query("SELECT kode,nama,tipe_swakelola FROM master_dokumen_pengadaan_neo WHERE cara_pengadaan='SWAKELOLA' AND aktif=1 AND is_deleted=0 ORDER BY kode")->fetchAll() as $m){
            $profiles['SWAKELOLA']['steps'][]=[$m['kode'],$m['nama'],'document',$m['tipe_swakelola']];
        }
        return $profiles;
    }
    public static function steps(array $c,?array $catalog=null):array
    {
        $steps=($catalog??self::catalog())[self::flow($c)]['steps'];
        if(self::flow($c)==='SWAKELOLA')$steps=array_values(array_filter($steps,static function($s)use($c){
            if($s[3]!==($c['tipe_swakelola']??''))return false;
            if(!in_array($s[3],['III','IV'],true))return true;
            $variant=preg_match('/pemilihan|usulan/iu',$c['metode_pemilihan']??'')===1;
            preg_match('/^SW_[IV]+_(\d+)_/',$s[0],$match);$source=$match[1]??'';
            return $source==='05'||in_array($source,$variant?['03','04']:['01','02'],true);
        }));
        return $steps;
    }
    public static function decode($raw):array
    {
        if($raw===null||$raw==='')return [];
        if(is_array($raw))return $raw;
        try{$value=json_decode((string)$raw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){throw new FieldValidationException('proses_pengadaan','Data nomor/tanggal proses tidak valid');}
        if(!is_array($value))throw new FieldValidationException('proses_pengadaan','Data proses harus berupa daftar');
        return $value;
    }
    public static function normalize($raw,array $c):string
    {
        $given=self::decode($raw);$out=[];
        foreach(self::steps($c) as $step){
            [$code]=$step;if(($step[2]??'')==='header')continue;
            if(!array_key_exists($code,$given))continue;
            $row=$given[$code];if(!is_array($row))throw new FieldValidationException('proses_pengadaan','Data tahap '.$code.' tidak valid');
            $date=trim((string)($row['tanggal']??''));$number=trim((string)($row['nomor']??''));$time=trim((string)($row['waktu']??''));
            if($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))))throw new FieldValidationException('proses_pengadaan','Tanggal '.$step[1].' tidak valid (YYYY-MM-DD)');
            if(mb_strlen($number)>255||mb_strlen($time)>100)throw new FieldValidationException('proses_pengadaan','Nomor atau waktu tahap terlalu panjang');
            $out[$code]=['nomor'=>$number,'tanggal'=>$date,'waktu'=>$time];
        }
        return json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    public static function values(array $c):array
    {
        $values=self::decode($c['proses_pengadaan']??null);
        // Existing document edits remain authoritative; no legacy dates are overwritten on read.
        foreach($c['dokumen_referensi']??[] as $code=>$ref)$values[$code]=array_merge($values[$code]??[],['nomor'=>$ref['nomor_dokumen']??'','tanggal'=>$ref['tanggal_dokumen']??'']);
        $values['KONTRAK']=['nomor'=>$c['nomor_kontrak']??'','tanggal'=>$c['tanggal_kontrak']??'','waktu'=>''];
        $values['SPMK']=array_merge($values['SPMK']??[],['nomor'=>$c['nomor_spmk']??'','tanggal'=>$c['tanggal_spmk']??'']);
        return $values;
    }
    public static function documentValue(array $c,string $code):array
    {
        if(str_starts_with($code,'SPK_')||$code==='SURAT_PERJANJIAN')$code='KONTRAK';
        return self::values($c)[$code]??['nomor'=>'','tanggal'=>'','waktu'=>''];
    }
    public static function syncDocuments(DB $db,int $id,array $c,string $actor):void
    {
        $values=self::decode($c['proses_pengadaan']??null);
        $values['SPMK']=['nomor'=>$c['nomor_spmk']??'','tanggal'=>$c['tanggal_spmk']??''];
        $values['KONTRAK']=['nomor'=>$c['nomor_kontrak']??'','tanggal'=>$c['tanggal_kontrak']??''];
        $docs=$db->query('SELECT id,kode_dokumen,status,nomor_dokumen,tanggal_dokumen FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0',[$id])->fetchAll();
        foreach($docs as $doc){
                $code=$doc['kode_dokumen'];if(str_starts_with($code,'SPK_')||$code==='SURAT_PERJANJIAN')$code='KONTRAK';
                if(!isset($values[$code]))continue;$v=$values[$code];
                $date=$v['tanggal']??'';$number=$v['nomor']??'';
                if($date===''&&($doc['tanggal_dokumen']??'')!=='')throw new FieldValidationException('proses_pengadaan','Tanggal '.$code.' wajib diisi karena dokumennya sudah dibuat');
                if(($doc['tanggal_dokumen']??'')===$date&&($doc['nomor_dokumen']??'')===$number)continue;
                if($doc['status']!=='DRAFT'&&($doc['tanggal_dokumen']!==$date||($doc['nomor_dokumen']??'')!==$number))throw new FieldValidationException('proses_pengadaan','Dokumen '.$code.' sudah diproses; ubah melalui alur dokumen');
                $data=['nomor_dokumen'=>$number?:null,'username_update'=>$actor,'tgl_update'=>date('Y-m-d H:i:s')];
                if($date!=='')$data['tanggal_dokumen']=$date;
                $db->update('dokumen_pengadaan_neo',$data,'WHERE id=?',[$doc['id']]);
        }
    }
}
