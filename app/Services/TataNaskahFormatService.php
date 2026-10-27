<?php

/** Form and print share these field names. SK (jenis 5) deliberately stays separate. */
final class TataNaskahFormatService
{
    public static function profile(int $id): array
    {
        return match ($id) {
            1 => ['title'=>'PERATURAN', 'layout'=>'regulation', 'fields'=>['tentang'=>'Tentang','menimbang'=>'Menimbang','mengingat'=>'Mengingat','menetapkan'=>'Menetapkan']],
            2 => ['title'=>'INSTRUKSI', 'layout'=>'instruction', 'fields'=>['tentang'=>'Tentang','dasar'=>'Dasar','kepada'=>'Kepada','menimbang'=>'Menimbang','memerintahkan'=>'Untuk']],
            3 => ['title'=>'SURAT EDARAN', 'layout'=>'circular', 'fields'=>['kepada'=>'Yth.','tentang'=>'Tentang','isi'=>'Isi Edaran']],
            4 => ['title'=>'STANDAR OPERASIONAL PROSEDUR', 'layout'=>'sop', 'fields'=>['tentang'=>'Nama SOP','tanggal_pembuatan'=>'Tanggal Pembuatan','tanggal_revisi'=>'Tanggal Revisi','tanggal_efektif'=>'Tanggal Efektif','dasar'=>'Dasar Hukum','kualifikasi'=>'Kualifikasi Pelaksana','keterkaitan'=>'Keterkaitan','peralatan'=>'Peralatan/Perlengkapan','peringatan'=>'Peringatan','pencatatan'=>'Pencatatan dan Pendataan','langkah'=>'Langkah SOP']],
            6 => ['title'=>'SURAT TUGAS', 'layout'=>'assignment', 'fields'=>['dasar'=>'Dasar','petugas'=>'Pegawai yang Ditugaskan','isi'=>'Untuk']],
            7 => ['title'=>'NOTA DINAS', 'layout'=>'memo', 'fields'=>['kepada'=>'Yth.','dari'=>'Dari','tembusan'=>'Tembusan','sifat'=>'Sifat','lampiran'=>'Lampiran','isi'=>'Isi Nota']],
            8 => ['title'=>'MEMO', 'layout'=>'memo', 'fields'=>['kepada'=>'Yth.','dari'=>'Dari','isi'=>'Isi Memo']],
            9 => ['title'=>'LEMBAR DISPOSISI', 'layout'=>'disposition', 'fields'=>['surat_dari'=>'Surat dari','nomor_surat_masuk'=>'Nomor Surat Masuk','tanggal_surat_masuk'=>'Tanggal Surat Masuk','tanggal_diterima'=>'Diterima Tanggal','nomor_agenda'=>'Nomor Agenda','sifat'=>'Sifat','diteruskan_kepada'=>'Diteruskan kepada','tindak_lanjut'=>'Dengan Hormat Harap','isi'=>'Catatan']],
            10 => ['title'=>'SURAT UNDANGAN', 'layout'=>'letter', 'fields'=>['kepada'=>'Yth.','alamat_tujuan'=>'Alamat Tujuan','sifat'=>'Sifat','lampiran'=>'Lampiran','pembuka'=>'Pembuka','hari_tanggal'=>'Hari/Tanggal Acara','waktu'=>'Waktu','tempat'=>'Tempat','agenda'=>'Acara','penutup'=>'Penutup']],
            11 => ['title'=>'SURAT DINAS', 'layout'=>'letter', 'fields'=>['kepada'=>'Yth.','alamat_tujuan'=>'Alamat Tujuan','sifat'=>'Sifat','lampiran'=>'Lampiran','isi'=>'Isi Surat']],
            12 => ['title'=>'SURAT PERJANJIAN', 'layout'=>'agreement', 'fields'=>['tentang'=>'Tentang','nomor_pihak2'=>'Nomor Pihak Kedua','pihak1'=>'Pihak Pertama','jabatan_pihak1'=>'Jabatan Pihak Pertama','alamat_pihak1'=>'Alamat Pihak Pertama','pihak2'=>'Pihak Kedua','jabatan_pihak2'=>'Jabatan Pihak Kedua','alamat_pihak2'=>'Alamat Pihak Kedua','pembuka'=>'Pembuka','pasal'=>'Pasal-Pasal','saksi'=>'Saksi-Saksi']],
            13 => ['title'=>'SURAT KUASA', 'layout'=>'power', 'fields'=>['pemberi'=>'Pemberi Kuasa','jabatan_pemberi'=>'Jabatan Pemberi Kuasa','alamat_pemberi'=>'Alamat Pemberi Kuasa','penerima'=>'Penerima Kuasa','jabatan_penerima'=>'Jabatan Penerima Kuasa','alamat_penerima'=>'Alamat Penerima Kuasa','isi'=>'Untuk']],
            14 => ['title'=>'BERITA ACARA', 'layout'=>'minutes', 'fields'=>['kegiatan'=>'Kegiatan','tanggal'=>'Tanggal Kegiatan','pihak1'=>'Pihak Pertama','jabatan_pihak1'=>'Jabatan Pihak Pertama','pihak2'=>'Pihak Kedua','jabatan_pihak2'=>'Jabatan Pihak Kedua','pembuka'=>'Pembuka','hasil'=>'Hasil','saksi'=>'Saksi-Saksi']],
            15 => ['title'=>'SURAT KETERANGAN', 'layout'=>'statement', 'fields'=>['nama'=>'Nama yang Diterangkan','nip'=>'NIP','pangkat'=>'Pangkat/Golongan','jabatan'=>'Jabatan','isi'=>'Keterangan']],
            16 => ['title'=>'SURAT PENGANTAR', 'layout'=>'delivery', 'fields'=>['kepada'=>'Yth.','alamat_tujuan'=>'Alamat Tujuan','barang'=>'Naskah Dinas/Barang yang Dikirimkan','isi'=>'Keterangan Tambahan','tanggal_diterima'=>'Diterima Tanggal','penerima'=>'Nama Penerima','jabatan_penerima'=>'Jabatan Penerima','nip_penerima'=>'NIP Penerima','telepon_penerima'=>'Nomor Telepon Penerima']],
            17 => ['title'=>'PENGUMUMAN', 'layout'=>'announcement', 'fields'=>['tentang'=>'Tentang','isi'=>'Isi Pengumuman']],
            18 => ['title'=>'LAPORAN', 'layout'=>'report', 'fields'=>['tentang'=>'Tentang','latar_belakang'=>'A.1. Umum/Latar Belakang','dasar'=>'A.2. Landasan Hukum','maksud_tujuan'=>'A.3. Maksud dan Tujuan','kegiatan'=>'B. Kegiatan yang Dilaksanakan','isi'=>'C. Hasil yang Dicapai','kesimpulan'=>'D. Kesimpulan dan Saran','penutup'=>'E. Penutup']],
            19 => ['title'=>'TELAAHAN STAF', 'layout'=>'review', 'fields'=>['kepada'=>'Yth.','dari'=>'Dari','lampiran'=>'Lampiran','persoalan'=>'I. Persoalan','praanggapan'=>'II. Praanggapan','fakta'=>'III. Fakta-Fakta yang Mempengaruhi','analisis'=>'IV. Analisis','kesimpulan'=>'V. Kesimpulan','rekomendasi'=>'VI. Saran']],
            20 => ['title'=>'NOTULA', 'layout'=>'meeting', 'fields'=>['rapat'=>'Sidang/Rapat','hari_tanggal'=>'Hari/Tanggal','surat_undangan'=>'Surat Undangan','waktu'=>'Waktu Sidang/Rapat','agenda'=>'Acara','ketua'=>'Ketua','sekretaris'=>'Sekretaris','pencatat'=>'Pencatat','peserta'=>'Peserta Sidang/Rapat','hasil'=>'Kegiatan Sidang/Rapat']],
            21 => ['title'=>'SAMBUTAN', 'layout'=>'speech', 'fields'=>['tentang'=>'Acara','isi'=>'Isi Sambutan']],
            22 => ['title'=>'SIARAN PERS', 'layout'=>'press', 'fields'=>['tentang'=>'Judul','isi'=>'Isi Berita','kontak'=>'Kontak Informasi']],
            23 => ['title'=>'SERTIFIKAT', 'layout'=>'certificate', 'fields'=>['nama'=>'Nama','nip'=>'NIP','instansi'=>'Instansi','peran'=>'Sebagai/Atas Partisipasinya','kegiatan'=>'Kegiatan','penyelenggara'=>'Penyelenggara','tanggal'=>'Tanggal Mulai','tanggal_selesai'=>'Tanggal Selesai','tempat'=>'Tempat','isi'=>'Uraian']],
            24 => ['title'=>'SURAT TANDA TAMAT PENDIDIKAN DAN PELATIHAN', 'layout'=>'training', 'fields'=>['dasar'=>'Dasar','nama'=>'Nama','tempat_tanggal_lahir'=>'Tempat/Tanggal Lahir','nip'=>'NIP/NRP','pangkat'=>'Pangkat/Golongan Ruang','jabatan'=>'Jabatan','instansi'=>'Instansi','kualifikasi'=>'Kualifikasi','pelatihan'=>'Pendidikan dan Pelatihan','penyelenggara'=>'Penyelenggara','tempat'=>'Tempat','tanggal_mulai'=>'Tanggal Mulai','tanggal_selesai'=>'Tanggal Selesai','tentang'=>'Tentang','isi'=>'Uraian','agenda_pembelajaran'=>'Agenda Pembelajaran','tema'=>'Tema']],
            25 => ['title'=>'PIAGAM PENGHARGAAN', 'layout'=>'award', 'fields'=>['nama'=>'Nama','tempat_tanggal_lahir'=>'Tempat/Tanggal Lahir','nip'=>'NIP/NRP','jabatan'=>'Jabatan','instansi'=>'Instansi','kegiatan'=>'Penghargaan Atas','tanggal'=>'Tanggal Kegiatan','isi'=>'Uraian']],
            default => [],
        };
    }

    public static function isDecision(array $type): bool
    {
        return (int)($type['jenis_id']??$type['id']??0)===5 || preg_match('/penetapan|keputusan/iu',(string)($type['jenis_naskah']??$type['nama']??''))===1;
    }

    public static function schema(int $id, array $existing=[]): array
    {
        $p=self::profile($id);if(!$p)return $existing;
        $input=static fn($name,$label,$type='input',$required=false)=>['type'=>$type,'name'=>$name,'label'=>$label,'required'=>$required];
        $rows=static fn($name,$label)=>['type'=>'editable_table','name'=>$name,'label'=>$label,'columns'=>['URAIAN'],'row_options'=>['types'=>['paragraph','list','numbered','alpha'],'alignments'=>['left','center','right','justify'],'styles'=>['bold','italic','underline'],'formats'=>['label'],'default_type'=>'paragraph','default_align'=>'justify','hanging_indent'=>true]];
        $s=[['type'=>'fields','size'=>'three','fields'=>[$input('nomor','Nomor Surat','input',true),$input('tanggal_surat','Tanggal Surat','calendar',true),$input('file','Pilih File Dokumen','file')]],$input('perihal','Perihal','textarea',true),['type'=>'fields','size'=>'three','fields'=>[['type'=>'dropdown_ajax','name'=>'penandatangan','label'=>'Penandatangan','source'=>'penandatangan','required'=>true],$input('jbt_pemberi_tgs','Jabatan','input',true),$input('pangkat_pemberi_tgs','Pangkat','input',true)]]];
        $collections=['dasar','menimbang','mengingat','menetapkan','memerintahkan','isi','langkah','agenda','pasal','hasil','kesimpulan','analisis','rekomendasi','latar_belakang','maksud_tujuan','penutup','agenda_pembelajaran'];
        foreach($p['fields'] as $name=>$label){
            if($name==='barang'){$s[]=['type'=>'table','name'=>$name,'label'=>$label,'columns'=>['uraian','banyaknya','keterangan'],'editable_rows'=>true];continue;}
            if($name==='petugas'){$s[]=['type'=>'dropdown_ajax','name'=>'asn','label'=>'Pilih Pegawai','source'=>'asn','target'=>'petugas'];$s[]=['type'=>'table','name'=>'petugas','label'=>$label,'columns'=>['nama','pangkat','nip','jabatan']];continue;}
            $s[]=in_array($name,$collections,true)?$rows($name,$label):$input($name,$label,str_starts_with($name,'tanggal')?'calendar':(in_array($name,['tentang','pembuka','pihak1','pihak2','pemberi','penerima'],true)?'textarea':'input'));
        }
        if(!isset($p['fields']['tembusan']))$s[]=$rows('tembusan','Tembusan');
        return $s;
    }

    /** Retain old user-entered fields even when a historical form used a different name. */
    public static function withStoredFields(array $schema,array $data): array
    {
        $names=[];$walk=static function($items)use(&$walk,&$names){foreach($items as $i){if(!is_array($i))continue;if(isset($i['name']))$names[]=$i['name'];if(isset($i['field']))$names[]=$i['field'];foreach(['fields','sections'] as $k)if(isset($i[$k]))$walk($i[$k]);}};$walk($schema);
        $hidden=['jenis_id','klasifikasi_id','nomor','tanggal_surat','perihal','file','penandatangan','jbt_pemberi_tgs','pangkat_pemberi_tgs','nama_penandatangan','jabatan_penandatangan','pangkat_penandatangan','nip_penandatangan','nip_pemberi_tgs','nama_pemberi_tgs','nama_pemberi_tugas','penanda_tangan','nama_pejabat','jabatan_pejabat','asn','disable','keterangan','tempat_ditetapkan','bentuk_lampiran','personnel_rows_are_individual'];
        foreach($data as $k=>$v){if(in_array($k,$names,true)||in_array($k,$hidden,true)||str_starts_with((string)$k,'_')||$v===null||$v==='')continue;$schema[]=['name'=>$k,'label'=>ucwords(str_replace('_',' ',$k)),'type'=>is_array($v)?'editable_table':'textarea'];}
        return $schema;
    }

    public static function rank(string $rank,string $group='',string $room=''): string
    {
        if(preg_match('/^(?:(I{1,3}|IV)|([1-4]))\s*[\/., -]?\s*([a-e])$/i',trim($rank),$m)){$group=$m[1]?:$m[2];$room=$m[3];}elseif(trim($rank)!=='')return $rank;
        $group=['1'=>'I','2'=>'II','3'=>'III','4'=>'IV'][trim($group)]??strtoupper(trim($group));$room=strtolower(trim($room));
        $key=$group.'/'.$room;$names=['I/a'=>'Juru Muda','I/b'=>'Juru Muda Tingkat I','I/c'=>'Juru','I/d'=>'Juru Tingkat I','II/a'=>'Pengatur Muda','II/b'=>'Pengatur Muda Tingkat I','II/c'=>'Pengatur','II/d'=>'Pengatur Tingkat I','III/a'=>'Penata Muda','III/b'=>'Penata Muda Tingkat I','III/c'=>'Penata','III/d'=>'Penata Tingkat I','IV/a'=>'Pembina','IV/b'=>'Pembina Tingkat I','IV/c'=>'Pembina Utama Muda','IV/d'=>'Pembina Utama Madya','IV/e'=>'Pembina Utama'];
        return isset($names[$key])?$names[$key].', '.$key:trim($rank);
    }
}
