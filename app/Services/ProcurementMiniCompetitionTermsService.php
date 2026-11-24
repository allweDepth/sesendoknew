<?php
require_once __DIR__.'/ProcurementContractFormService.php';

/** Paired SSUK/SSKK from the user's SE Dirjen Bina Marga 03/SE/Db/2025 reference. */
final class ProcurementMiniCompetitionTermsService
{
    public static function variant(array $c):string
    {
        $v=(string)($c['terms_variant']??$c['print_settings']['terms_variant']??'STANDARD');
        if(!in_array($v,['STANDARD','MINI_KOMPETISI'],true))throw new InvalidArgumentException('Pilihan syarat kontrak tidak valid');
        if($v==='MINI_KOMPETISI'&&!self::eligible($c))throw new InvalidArgumentException('Format mini kompetisi tersedia untuk surat perjanjian atau surat pesanan pekerjaan konstruksi');
        return $v;
    }
    public static function eligible(array $c):bool
    {
        return ($c['cara_pengadaan']??'PENYEDIA')==='PENYEDIA'&&($c['jenis_pengadaan']??'')==='PEKERJAAN_KONSTRUKSI'
            &&in_array(ProcurementContractFormService::recommend($c),['SURAT_PERJANJIAN','SURAT_PESANAN'],true);
    }
    public static function rows(string $code,array $c):array
    {
        if(!in_array($code,['SSUK','SSKK'],true))return [];
        self::variant(array_replace($c,['terms_variant'=>'MINI_KOMPETISI']));
        $file=__DIR__.'/../../resources/procurement/reference/'.strtolower($code).'-mini-kompetisi-bina-marga.json';
        $rows=json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
        return $code==='SSKK'?self::fill($rows,$c):$rows;
    }
    /** Draft SSUK is the normative source, never an old SPK snapshot with a new label. */
    public static function ssukDocument(array $document,array $contract):array
    {
        if(($document['kode_dokumen']??'')!=='SSUK'||($document['status']??'DRAFT')!=='DRAFT')return $document;
        $data=$document['data_isian']??[];
        if(is_string($data))$data=json_decode($data,true,512,JSON_THROW_ON_ERROR);
        $context=array_replace($contract,['terms_variant'=>$data['terms_variant']??'STANDARD']);
        if(self::variant($context)!=='MINI_KOMPETISI')return $document;
        $sections=$document['sections']??[];
        if(!$sections)$sections=[['kode_bagian'=>'ISI','judul'=>'Isi Dokumen','urutan'=>1]];
        foreach($sections as $i=>&$section)$section['isi']=json_encode($i===0?self::rows('SSUK',$context):[],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        unset($section);
        $data['ssuk_source']=self::ssukSource();
        $document['data_isian']=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $document['sections']=$sections;
        return $document;
    }
    public static function ssukSource():array
    {
        $source=json_decode((string)file_get_contents(__DIR__.'/../../resources/procurement/reference/mini-kompetisi-source.json'),true,512,JSON_THROW_ON_ERROR);
        return $source['ssuk_verified_pdf'];
    }
    public static function fill(array $rows,array $c):array
    {
        $profile=self::profile($c);
        $template=json_decode((string)file_get_contents(__DIR__.'/../../resources/procurement/reference/sskk-mini-kompetisi-bina-marga.json'),true,512,JSON_THROW_ON_ERROR);
        // A snapshot made with the standard numbering must not be mixed with SSUK mini kompetisi.
        foreach($rows as $row)if(preg_match('/^(27\.1|33\.8|70\.1)\b/',trim((string)($row['text']??'')))){$rows=$template;break;}
        $templates=[];foreach($template as $row){$p=self::parts($row);$templates[self::key($p[0],$p[1])]=$p[2];}
        $source=[];foreach($profile['rows']??[] as $row)$source[self::key($row['clause'],$row['title'])]=$row;
        $get=static function(string $key,string $fallback='Belum tercatat pada data kontrak')use($c):string{return trim((string)($c[$key]??''))?:$fallback;};
        $ppk=$get('nama_ppk_resmi',$get('nama_ppk'));
        $provider=$get('nama_penyedia_resmi',$get('nama_penyedia'));
        $contact=$get('email_opd',!empty($profile)?'Sarana komunikasi resmi paket pada https://katalog.inaproc.id/':'Alamat korespondensi elektronik belum tercatat');
        $pptk=trim((string)($c['nama_pptk']??''));
        $representative=$pptk!==''?"Untuk Pejabat Penandatangan Kontrak:\nNama: {$pptk}\nJabatan: Pejabat Pelaksana Teknis Kegiatan (PPTK)\nNIP: ".$get('nip_pptk')."\nDasar penugasan: ".(!empty($c['nomor_sk_pptk'])?'SK PPTK Nomor '.$c['nomor_sk_pptk'].(!empty($c['tanggal_sk_pptk'])?' tanggal '.$c['tanggal_sk_pptk']:'; tanggal SK belum tercatat'):'Penetapan PPTK pada master pejabat kontrak; nomor dan tanggal SK belum tercatat, wajib dilengkapi sebelum penandatanganan').".\nRuang kewenangan mengikuti penetapan atau pelimpahan tertulis PPK; kewenangan mengubah kontrak tidak otomatis melekat pada jabatan PPTK.":"Untuk Pejabat Penandatangan Kontrak: {$ppk}, bertindak langsung. PPTK/wakil tambahan belum tercatat pada kontrak.";
        $representative.="\n\nUntuk Penyedia: ".$get('direktur')."\nBadan usaha: {$provider}\nDasar kewenangan: ".(!empty($c['no_akta_pendirian'])?'Akta Nomor '.$c['no_akta_pendirian'].(!empty($c['tgl_akta_pendirian'])?' tanggal '.$c['tgl_akta_pendirian']:''):'Akta atau kuasa tertulis penyedia yang telah diverifikasi; nomor akta belum tercatat').'.';
        $maintenance=!empty($c['masa_pemeliharaan_bulan'])?$c['masa_pemeliharaan_bulan'].' bulan':(!empty($c['masa_pemeliharaan'])?$c['masa_pemeliharaan'].' hari kalender':null);
        $values=[
            '4.1 & 4.2'=>"Alamat Para Pihak:\nPejabat Penandatangan Kontrak: ".$get('nama_opd')."\nNama PPK: {$ppk}\nAlamat: ".$get('alamat_opd')."\nKorespondensi elektronik: {$contact}\n\nPenyedia: {$provider}\nNama: ".$get('direktur')."\nAlamat: ".$get('alamat_penyedia')."\nE-mail: ".$get('email_pelaksana'),
            '4.2 & 5.1'=>$representative,
        ];
        if(!empty($c['lokasi_pekerjaan'])||!empty($c['lokasi']))$values['6']=$get('lokasi_pekerjaan',$get('lokasi'));
        if(!empty($c['waktu_pelaksanaan']))$values['28.1']='Masa Pelaksanaan selama '.$c['waktu_pelaksanaan'].' hari kalender terhitung sejak Tanggal Mulai Kerja yang tercantum dalam SPMK.'.(!empty($c['tanggal_mulai'])?' Mulai '.$c['tanggal_mulai']:'').(!empty($c['tanggal_selesai'])?' sampai '.$c['tanggal_selesai'].'.':'');
        if($maintenance!==null&&(!isset($profile['maintenance_months'])||(string)($c['masa_pemeliharaan_bulan']??'')!==(string)$profile['maintenance_months']))$values['34.8']='Masa Pemeliharaan selama '.$maintenance.' terhitung sejak Tanggal Penyerahan Pertama Pekerjaan (PHO).';
        $seen=[];
        foreach($rows as &$r){
            $p=self::parts($r);$clause=preg_replace('/\s+/u',' ',$p[0]);$key=self::key($p[0],$p[1]);$seen[$key]=true;
            $unchanged=isset($r['sskk_default_hash'])&&hash_equals($r['sskk_default_hash'],hash('sha256',$r['text']));
            $default=$unchanged||self::unfilled($p[2])||($templates[$key]??null)===$p[2];
            $value=$values[$clause]??null;
            if($value===null&&$default&&isset($source[$key]))$value=$source[$key]['value'];
            if($value===null&&self::unfilled($p[2]))$value='Belum ditetapkan dalam dokumen pengadaan yang terhubung untuk paket ini. Lengkapi ketentuan '.$p[1].' beserta sumber penetapannya sebelum penandatanganan; nilai tidak diasumsikan dari paket lain.';
            if($value===null&&isset($r['sskk_default_hash'])&&!$unchanged){unset($r['sskk_source'],$r['sskk_default_hash']);$r['sskk_manual']=true;}
            if($value!==null){$r['text']=$p[0].' | '.$p[1].' | '.$value;$r['sskk_default_hash']=hash('sha256',$r['text']);if(isset($source[$key]))$r['sskk_source']=['rup'=>$profile['rup'],'document'=>$profile['source']['file_name'],'clause'=>$clause,'table'=>$source[$key]['source_table'],'row'=>$source[$key]['source_row'],'sha256'=>$profile['source']['sha256']];}
        }unset($r);
        foreach($source as $key=>$r)if(!isset($seen[$key]))$rows[]=['type'=>'paragraph','align'=>'left','format'=>'sskk-row','style'=>[],'text'=>$r['clause'].' | '.$r['title'].' | '.$r['value'],'sskk_source'=>['rup'=>$profile['rup'],'document'=>$profile['source']['file_name'],'clause'=>$r['clause'],'table'=>$r['source_table'],'row'=>$r['source_row'],'sha256'=>$profile['source']['sha256']]];
        if($profile&&!isset($seen[self::key('Dasar','Dasar Pengisian SSKK')]))$rows[]=['type'=>'paragraph','align'=>'left','format'=>'sskk-row','style'=>[],'text'=>'Dasar | Dasar Pengisian SSKK | '.$profile['source']['title'].', Kode RUP '.$profile['rup'].', '.$profile['source']['section'].'. Identitas, PPTK, nomor, tanggal dan masa pelaksanaan mengikuti data kontrak serta master pejabat/penyedia. Ketentuan sumber tetap berlaku; nilai yang belum tersedia harus dilengkapi sebelum penandatanganan.'];
        return $rows;
    }
    public static function profile(array $c):array
    {
        $rup=(string)($c['nomor_rup']??'');if(!preg_match('/^\d{8}$/D',$rup))return [];
        $file=__DIR__.'/../../resources/procurement/sskk-profiles/'.$rup.'.json';if(!is_file($file))return [];
        $p=json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
        if((string)($c['kd_wilayah']??'')!==$p['region']||(int)($c['tahun']??0)!==$p['year'])return [];
        return $p;
    }
    private static function parts(array $r):array{return array_pad(array_map('trim',explode('|',(string)($r['text']??''),3)),3,'');}
    private static function key(string $clause,string $title):string{return preg_replace('/\s+/u',' ',trim($clause)).'|'.preg_replace('/\s+/u',' ',trim($title));}
    private static function unfilled(string $text):bool{return preg_match('/\.{3,}|…|\[(?:diisi|dipilih)|\{\{/iu',$text)===1;}
}
