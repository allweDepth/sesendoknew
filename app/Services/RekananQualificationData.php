<?php
/** JSON document schema shared by the editor and PDF; repeated rows retain their order. */
final class RekananQualificationData
{
    private static function fields(array $spec):array
    {
        $out=[];foreach($spec as $key=>$label){$parts=explode('|',$label);$out[$key]=['label'=>$parts[0],'type'=>$parts[1]??'text'];}return $out;
    }
    public static function profile():array
    {
        return self::fields(['status'=>'Status kantor (Pusat/Cabang)','telepon'=>'Telepon','faks'=>'Faks','alamat_pusat'=>'Alamat kantor pusat','alamat_cabang'=>'Alamat kantor cabang','bukti_kantor'=>'Bukti penguasaan tempat usaha','pengesahan'=>'Nomor pengesahan/pendaftaran pendirian','bukti_perubahan'=>'Bukti pengesahan/perubahan terakhir','dasar_kuasa'=>'Dasar kewenangan/kuasa/KSO (nomor dan tanggal)','status_pajak'=>'Status kepatuhan / hasil KSWP','tanggal_pajak'=>'Tanggal konfirmasi pajak|date','sumber_pajak'=>'Sumber konfirmasi pajak','kualifikasi_usaha'=>'Kualifikasi usaha','n_paket'=>'N: paket bersamaan terbanyak 5 tahun terakhir|number','kp'=>'Kemampuan Paket (KP)|number','skp'=>'Sisa Kemampuan Paket (SKP)|number','npt'=>'Nilai pengalaman tertinggi relevan (NPt)|money','kd'=>'Kemampuan Dasar (KD)|money','acuan_kd'=>'Paket/subklasifikasi acuan KD']);
    }
    public static function sections():array
    {
        $definitions=[
            'pengurus'=>['Pengurus Tambahan (selain direktur di data rekanan)',['nama'=>'Nama','identitas'=>'KTP/Paspor','jabatan'=>'Jabatan']],
            'komisaris'=>['Komisaris (untuk PT)',['nama'=>'Nama','identitas'=>'KTP/Paspor','jabatan'=>'Jabatan']],
            'izin'=>['NIB / Perizinan Berusaha',['jenis_nomor'=>'Jenis dan nomor izin','tanggal'=>'Tanggal terbit|date','berlaku'=>'Berlaku sampai|date','penerbit'=>'Instansi penerbit','klasifikasi'=>'KBLI / Klasifikasi / Kualifikasi']],
            'sbu'=>['SBU / Sertifikat Standar',['nomor'=>'Nomor sertifikat','subklasifikasi'=>'Subklasifikasi / Kualifikasi','penerbit'=>'Penerbit','tanggal'=>'Tanggal terbit|date','berlaku'=>'Berlaku sampai|date']],
            'izin_lain'=>['Izin Lainnya',['jenis_nomor'=>'Jenis dan nomor','penerbit'=>'Penerbit','tanggal'=>'Tanggal terbit|date','berlaku'=>'Berlaku sampai|date']],
            'pemilik'=>['Pemegang Saham / Pesero',['nama'=>'Nama','identitas'=>'Identitas','alamat'=>'Alamat','persen'=>'Kepemilikan (%)|percent']],
            'pemilik_manfaat'=>['Pemilik Manfaat',['nama_identitas'=>'Nama dan identitas','alamat'=>'Kewarganegaraan / Alamat','dasar'=>'Dasar pengendalian/manfaat','bukti'=>'Bukti pelaporan sistem']],
            'personel'=>['Personel / Tenaga Ahli',['nama'=>'Nama','lahir'=>'Tanggal lahir|date','pendidikan'=>'Pendidikan','jabatan_status'=>'Jabatan / Status','pengalaman'=>'Pengalaman (tahun)|number','keahlian'=>'Keahlian / Kompetensi','sertifikat'=>'Nomor / Tahun / Masa berlaku sertifikat']],
            'pengalaman_personel'=>['Pengalaman Tenaga Ahli',['nama'=>'Nama tenaga ahli','paket'=>'Paket / Pemberi kerja','lingkup'=>'Lingkup / Posisi','periode'=>'Periode penugasan','bukti'=>'Bukti pengalaman']],
            'peralatan'=>['Fasilitas / Peralatan',['jenis'=>'Jenis','jumlah'=>'Jumlah|number','kapasitas'=>'Kapasitas / Output','merek'=>'Merek / Tipe','tahun'=>'Tahun pembuatan','kondisi_lokasi'=>'Kondisi / Lokasi','bukti'=>'Status / Bukti penguasaan']],
            'pengalaman'=>['Pengalaman Badan Usaha',['paket'=>'Nama paket / Lingkup / KBKI / Subklasifikasi','lokasi'=>'Lokasi','pemberi'=>'Pemberi kerja / Alamat / Telepon','kontrak'=>'Nomor / Tanggal kontrak','nilai'=>'Nilai kontrak|money','status'=>'Status Penyedia / KSO / Subkontrak','selesai'=>'Selesai kontrak|date','bast'=>'Tanggal BAST|date']],
            'pekerjaan_berjalan'=>['Pekerjaan yang Sedang Dilaksanakan',['paket'=>'Nama paket / Lingkup','lokasi'=>'Lokasi','pemberi'=>'Pemberi kerja / Kontak','kontrak'=>'Nomor / Tanggal kontrak','nilai'=>'Nilai kontrak|money','rencana'=>'Progres rencana (%)|percent','realisasi'=>'Progres realisasi (%)|percent','status'=>'Status penyedia','selesai'=>'Perkiraan selesai|date']]
        ];
        $out=[];foreach($definitions as $key=>[$label,$fields])$out[$key]=['label'=>$label,'fields'=>self::fields($fields)];return $out;
    }
    private static function cleanFields(array $input,array $schema,string $context):array
    {
        if(array_diff(array_keys($input),array_keys($schema)))throw new InvalidArgumentException('Kolom tidak dikenal pada '.$context.'.');
        $out=[];foreach($schema as $key=>$field){$raw=$input[$key]??'';if(!is_scalar($raw)&&$raw!==null)throw new InvalidArgumentException('Isian '.$field['label'].' tidak valid.');$v=trim((string)$raw);if(mb_strlen($v)>1000)throw new InvalidArgumentException($field['label'].' maksimal 1000 karakter.');
            if($v!==''&&$field['type']==='date'&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)||!checkdate((int)substr($v,5,2),(int)substr($v,8,2),(int)substr($v,0,4))))throw new InvalidArgumentException('Tanggal '.$field['label'].' tidak valid.');
            if($v!==''&&in_array($field['type'],['number','money','percent'],true)){if(!preg_match('/^\d{1,18}(?:\.\d{1,4})?$/D',$v))throw new InvalidArgumentException($field['label'].' harus berupa angka positif; gunakan titik untuk desimal.');if($field['type']==='percent'&&(float)$v>100)throw new InvalidArgumentException($field['label'].' harus 0 sampai 100.');}
            $out[$key]=$v;
        }return $out;
    }
    public static function clean(array $input):array
    {
        if(!is_array($input['profil']??null)||!is_array($input['rincian']??null))throw new InvalidArgumentException('Profil dan rincian kualifikasi harus berupa objek.');
        $schema=self::sections();if(array_diff(array_keys($input['rincian']),array_keys($schema)))throw new InvalidArgumentException('Jenis rincian kualifikasi tidak dikenal.');
        $out=['profil'=>self::cleanFields($input['profil'],self::profile(),'profil'),'rincian'=>[]];
        foreach($schema as $key=>$section){$rows=$input['rincian'][$key]??[];if(!is_array($rows)||!array_is_list($rows)||count($rows)>100)throw new InvalidArgumentException($section['label'].' maksimal 100 baris.');$out['rincian'][$key]=[];foreach($rows as $r){if(!is_array($r))throw new InvalidArgumentException('Baris '.$section['label'].' tidak valid.');$row=self::cleanFields($r,$section['fields'],$section['label']);if(implode('',$row)==='')continue;$out['rincian'][$key][]=$row;}}
        return $out;
    }
}
