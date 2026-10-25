<?php

/** Resolve MDS model fields from scoped contract data, never from invented facts. */
final class SwakelolaAutoFillService
{
    private array $values;
    private array $missing=[];
    private array $rab=[];
    private array $schedule=[];

    public function __construct(DB $db,array $c,array $user,array $document=[])
    {
        $this->values=SwakelolaDocumentService::identity($c,$user);
        $date=$document['tanggal_dokumen']??$c['tanggal_kontrak']??date('Y-m-d');
        $this->values+=['nomor_dokumen'=>$document['nomor_dokumen']??$c['nomor_kontrak']??'',
            'tanggal_dokumen'=>$this->date($date),'nomor_kontrak'=>$c['nomor_kontrak']??'',
            'tanggal_kontrak'=>$this->date($c['tanggal_kontrak']??''),
            'waktu_pelaksanaan'=>(string)($c['waktu_pelaksanaan']??''),
            'tanggal_mulai'=>$this->date($c['tanggal_mulai']??''),'tanggal_selesai'=>$this->date($c['tanggal_selesai']??''),
            'alamat_opd'=>$user['alamat_opd']??'','nama_wilayah'=>$user['nama_wilayah']??'',
            'nip_ppk'=>$c['nip_ppk_resmi']??'','nama_pa_kpa'=>'','nomor_sk_ppk'=>'','nama_pengawas'=>'',
            'nip_pengawas'=>'','nomor_sk_pengawas'=>'','nama_tim_pelaksana'=>'','nomor_sk_pelaksana'=>'',
            'jabatan_pelaksana'=>$c['jabatan_pelaksana']??'', 'nik'=>$c['nik_pelaksana']??'',
            'email'=>$c['email_pelaksana']??'','no_akta'=>$c['no_akta_pendirian']??'',
            'tanggal_akta'=>$this->date($c['tgl_akta_pendirian']??''),
            'nama_kegiatan'=>$c['nama_sub_keg']??$c['uraian_kontrak']??''];
        $id=(int)$c['id'];
        $this->rab=$db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY COALESCE(NULLIF(urutan,0),id),id',[$id])->fetchAll();
        $this->schedule=$db->query('SELECT * FROM kontrak_jadwal_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY minggu_ke,id',[$id])->fetchAll();
        $kop=$db->query('SELECT * FROM kop_surat_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND aktif=1 AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$c['kd_wilayah'],$c['kd_opd'],$c['tahun']])->fetch()?:[];
        $this->values['alamat_opd']=$kop['alamat']??$this->values['alamat_opd'];
        $this->values['nama_opd']=$kop['nama_opd']??$this->values['nama_opd'];
        $this->values['jadwal_pelaksanaan']=implode('; ',array_map(fn($row)=>'Minggu '.$row['minggu_ke'].': '.$this->date($row['tanggal_mulai']).' s.d. '.$this->date($row['tanggal_selesai']),$this->schedule));
        $officials=$db->query('SELECT * FROM pejabat_tahunan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 AND berlaku_mulai<=? AND berlaku_sampai>=? ORDER BY id DESC',[$c['kd_wilayah'],$c['kd_opd'],$c['tahun'],$date,$date])->fetchAll();
        foreach($officials as $official){
            if($official['jenis_pejabat']==='PA_KPA'&&$this->values['nama_pa_kpa']==='')$this->values['nama_pa_kpa']=$official['nama_pegawai']??'';
            if((int)$official['id']===(int)($c['ppk_id']??0))$this->values['nomor_sk_ppk']=$official['nomor_sk']??'';
        }
        // Named technical staff are read as recorded, without assigning them new roles.
        $staff=json_decode($c['nama_tim_teknis']??'[]',true)?:[];
        $this->values['nama_tim_teknis']=implode(', ',array_filter($staff,'is_string'));
        $this->values['lingkup_pekerjaan']=implode('; ',array_column($this->rab,'uraian'))?:($c['uraian_kontrak']??'');
        $this->values['latar_belakang']='Pelaksanaan paket '.$this->values['nama_paket'].' pada '.$this->values['nama_opd'].' Tahun Anggaran '.$this->values['tahun'].'.';
        $this->values['tujuan']='Menyelesaikan pekerjaan '.$this->values['nama_paket'].' sesuai ruang lingkup, RAB, dan jadwal pelaksanaan kontrak.';
        $this->values['keluaran']='Hasil pekerjaan '.$this->values['nama_paket'].' sesuai ruang lingkup kontrak.';
    }

    private function date(string $date):string
    {
        if(!$date||$date==='0000-00-00')return '';
        $t=strtotime($date);if(!$t)return $date;
        $months=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return date('j',$t).' '.$months[(int)date('n',$t)].' '.date('Y',$t);
    }

    private function value(string $key):string
    {
        $value=trim((string)($this->values[$key]??''));
        if($value===''){$this->missing[$key]=true;return '—';}
        return $value;
    }

    private function key(string $hint):?string
    {
        $h=mb_strtolower(preg_replace('/\s+/u',' ',trim($hint)));
        $rules=[
            'kop|logo'=>null,
            'nip.*pengawas'=>'nip_pengawas','nip.*pelaksana'=>'nip_pelaksana','nip'=>'nip_ppk',
            '(?:nik|nomor identitas)'=>'nik','npwp'=>'npwp',
            'sk.*pengawas|penetapan tim pengawas'=>'nomor_sk_pengawas',
            'sk.*pelaksana|penetapan tim pelaksana|surat pengukuhan'=>'nomor_sk_pelaksana',
            'no\.? sk|nomor sk'=>'nomor_sk_ppk',
            'nama.*pengawas'=>'nama_pengawas',
            'nama.*(?:ketua|pimpinan)|wakil sah organisasi'=>'pimpinan_pelaksana',
            'nama.*tim.*pelaksana'=>'nama_tim_pelaksana',
            'nama.*personel|asistensi'=>'nama_tim_teknis',
            'nama pa/kpa'=>'nama_pa_kpa',
            'nama.*(?:ppk|pejabat penandatangan kontrak|pejabat pembuat komitmen)|sesuai sk pengangkatan'=>'nama_ppk',
            'alamat.*(?:pejabat penandatangan|penanggung jawab)|alamat lengkap k/l/pd'=>'alamat_opd',
            'alamat'=>'alamat_pelaksana',
            'satuan kerja.*pejabat penandatangan|nama.*penanggung.?jawab|nama k/l/pd$|satuan kerja$'=>'nama_opd',
            'nama.*(?:paket|pekerjaan)|uraian.*pekerjaan|jenis pekerjaan|judul kesepakatan|paket pengadaan|^pengadaan$'=>'nama_paket',
            'tahun anggaran|^tahun$'=>'tahun',
            'nama kegiatan'=>'nama_kegiatan',
            'nama.*(?:pokmas|ormas|organisasi profesi|pelaksana swakelola|blu|ptn|pts|k/l/pd lain)|tulis nama.*(?:k/l/pd|pokmas|ormas)|satuan kerja.*(?:pnbp|blu|perguruan)'=>'nama_pelaksana',
            'nama satuan kerja|nama k/l/pd|nama kementerian'=>'nama_opd',
            'sumber.*dana'=>'sumber_dana',
            'nilai|total biaya|mata uang'=>'nilai_kontrak',
            'nomor.*kontrak'=>'nomor_kontrak',
            'nomor|penomoran'=>'nomor_dokumen',
            'tanggal.*kontrak|tanggal.*penandatanganan'=>'tanggal_kontrak',
            'tanggal.*mulai'=>'tanggal_mulai',
            'tanggal|hari dan|hari,|bulan|batas waktu'=>'tanggal_dokumen',
            'jumlah hari|waktu.*(?:menyelesaikan|pelaksanaan)|jangka waktu'=>'waktu_pelaksanaan',
            'jadwal pelaksanaan'=>'jadwal_pelaksanaan',
            'jabatan'=>'jabatan_pelaksana',
            'nama lengkap'=>'pimpinan_pelaksana',
            'tempat|kota'=>'nama_wilayah',
            'latar belakang|penjelasan secara singkat'=>'latar_belakang',
            'tujuan'=>'tujuan','sasaran|output|keluaran'=>'keluaran',
            'lingkup|spesifikasi|metodologi|karakteristik'=>'lingkup_pekerjaan',
            'email'=>'email','akta'=>'no_akta',
        ];
        foreach($rules as $pattern=>$key)if(preg_match('~'.$pattern.'~iu',$h))return $key;
        return null;
    }

    private function text(string $text,string $context=''):string
    {
        // The model's hints are data labels; they are never executed as instructions.
        if(preg_match('/^\s*\[(?:Kop|Logo)[^\]]*\]\s*$/iu',$text))return '';
        $text=preg_replace('/^\s*(?:[A-Z0-9]+[.)]\s*)?(?:Contoh|C ontoh)\s+/iu','',$text);
        $text=preg_replace('/\(\s*tanda tangan\s*\)/iu',"\n\n",$text);
        $token='(?:<([^<>]+)>|\(([^()]*(?:\bisi\b|diisi|ditulis|nama lengkap)[^()]*)\)|\[([^\[\]]+)\])';
        $text=preg_replace_callback('~(?:[_\.]{3,}|[…\.·]{2,})?\s*'.$token.'~iu',function($m)use($context){
            $hint=$m[1]?:($m[2]??'');if($hint==='')$hint=$m[3]??'';
            if(preg_match('/tanda tangan|meterai|pilih salah satu/iu',$hint))return '';
            if(preg_match('/disesuaikan|sesuaikan|dikenakan pajak/iu',$hint))return '';
            $key=$this->key($hint);
            if(preg_match('/^\s*nama lengkap\s*$/iu',$hint)){
                if(str_contains($context,'SIGNATURE_PPK'))$key='nama_ppk';
                elseif(str_contains($context,'SIGNATURE_PA'))$key='nama_pa_kpa';
                elseif(str_contains($context,'SIGNATURE_PELAKSANA'))$key='pimpinan_pelaksana';
                else $key='nama_penandatangan';
            }
            if($key===null){$this->missing['isian: '.mb_substr(trim($hint),0,100)]=true;return ' —';}
            return ' '.$this->value($key);
        },$text);
        preg_match_all('/(?:_{2,}|\.{3,}|…{2,}|(?:\.\s*){4,})/u',$text,$matches,PREG_OFFSET_CAPTURE);
        $assembled='';$last=0;
        foreach($matches[0] as [$blank,$offset]){
            $segment=substr($text,$last,$offset-$last);
            $prefix=mb_substr($segment,-100);
            $key=$this->key($prefix)??$this->key($context);
            if(!$key)$this->missing['isian: '.mb_substr(trim($context?:$prefix),0,100)]=true;
            $assembled.=$segment.($key?$this->value($key):'—');$last=$offset+strlen($blank);
        }
        if($matches[0])$text=$assembled.substr($text,$last);
        $text=preg_replace_callback('/<([^<>]*)$/u',function($m){$key=$this->key($m[1]);return $key?$this->value($key):'—';},$text);
        $text=preg_replace('/\*?\s*pilih salah satu/iu','',$text);
        return trim($text);
    }

    private function plain(array $blocks):string
    {
        $out=[];foreach($blocks as $b){if($b['type']==='paragraph')$out[]=$b['text'];else foreach($b['rows'] as $row)foreach($row as $cell)$out[]=$this->plain($cell['blocks']);}
        return implode(' ',$out);
    }

    public function blocks(array $blocks,string $context=''):array
    {
        // Some Word hints span two paragraphs (e.g. "<diisi nama Pelaksana" / "Swakelola>").
        $joined=[];
        for($i=0;$i<count($blocks);$i++){
            $block=$blocks[$i];
            if($block['type']==='paragraph'){
                while(substr_count($block['text'],'<')>substr_count($block['text'],'>') && isset($blocks[$i+1]) && $blocks[$i+1]['type']==='paragraph'){
                    $block['text'].=' '.$blocks[++$i]['text'];
                }
            }
            $joined[]=$block;
        }
        $blocks=$joined;
        if(isset($blocks[0])&&$blocks[0]['type']==='paragraph'&&preg_match('/^\s*(?:[A-Z0-9]+[.)]\s*)?(?:Contoh|C ontoh)\s+/iu',$blocks[0]['text']))array_shift($blocks);
        $result=[];$signature='';
        foreach($blocks as $block){
            if($block['type']==='paragraph'){
                $original=$block['text'];
                if(mb_strlen($original)<160){
                    if(preg_match('/pejabat (?:pembuat komitmen|penandatangan kontrak)|pengguna anggaran.*ppk/iu',$original))$signature='SIGNATURE_PPK';
                    elseif(preg_match('/pengguna anggaran|kuasa pengguna/iu',$original))$signature='SIGNATURE_PA';
                    elseif(preg_match('/(?:ketua|pimpinan).*pelaksana|pimpinan.*(?:k\/l\/pd|ormas|pokmas)/iu',$original))$signature='SIGNATURE_PELAKSANA';
                }
                $block['text']=$this->text($original,$context.' '.$signature);
                if($block['text']!==''||trim($original)==='')$result[]=$block;
            }else{
                $beforeMissing=$this->missing;
                foreach($block['rows'] as &$row){
                    $label=$this->plain($row[0]['blocks']??[]);
                    foreach($row as $i=>&$cell){
                        if($i>0 && trim($this->plain($cell['blocks']))===''){
                            $key=$this->key($label);
                            if($key && ($cell['blocks'][0]['type']??'')==='paragraph')$cell['blocks'][0]['text']=$this->value($key);
                        }
                        $cell['blocks']=$this->blocks($cell['blocks'],$i>0?$label:$context);
                    }
                    unset($cell);
                }unset($row);
                // Populate the source RAB grid from the same RAB rows used by contracts.
                $header=-1;
                foreach($block['rows'] as $i=>$row){
                    $labels=mb_strtolower(implode(' ',array_map(fn($cell)=>$this->plain($cell['blocks']),$row)));
                    if(str_contains($labels,'uraian pekerjaan')&&str_contains($labels,'volume')&&str_contains($labels,'harga')){$header=$i;break;}
                }
                if($header>=0){
                    // Sample RAB rows are discarded; their blanks are not missing package data.
                    $this->missing=$beforeMissing;
                    $this->value('nama_pelaksana');$this->value('pimpinan_pelaksana');
                    if(!$this->rab)$this->missing['rincian_rab']=true;
                    $rows=array_slice($block['rows'],0,$header);
                    $rows[]=array_map(fn($label)=>['colspan'=>1,'blocks'=>[['type'=>'paragraph','text'=>$label,'bold'=>true]]],['No','Uraian Pekerjaan','Satuan','Volume','Harga Satuan (Rp)','Jumlah Harga (Rp)']);
                    foreach($this->rab as $i=>$item){
                        if(($item['type']??'')==='KETERANGAN'){$rows[]=[['colspan'=>1,'blocks'=>[['type'=>'paragraph','text'=>$item['nomor']]]],['colspan'=>5,'blocks'=>[['type'=>'paragraph','text'=>$item['uraian'],'bold'=>true]]]];continue;}
                        $volume=rtrim(rtrim(number_format((float)($item['vol_negoisasi']??$item['vol_hps']??0),4,',','.'),'0'),',');
                        $values=[(string)($i+1),$item['uraian'],$item['satuan'],$volume,number_format((float)($item['harga_sat_negoisasi']??$item['harga_sat_hps']??0),2,',','.'),number_format((float)($item['jumlah_negoisasi']??$item['jumlah_hps']??0),2,',','.')];
                        $rows[]=array_map(fn($v)=>['colspan'=>1,'blocks'=>[['type'=>'paragraph','text'=>$v]]],$values);
                    }
                    if($this->rab)$rows[]=[['colspan'=>5,'blocks'=>[['type'=>'paragraph','text'=>'Total','bold'=>true]]],['colspan'=>1,'blocks'=>[['type'=>'paragraph','text'=>number_format(array_sum(array_column($this->rab,'jumlah_negoisasi')),2,',','.'),'bold'=>true]]]];
                    // Retain signatures and notes following the source total row.
                    $total=-1;foreach($block['rows'] as $i=>$row)if(preg_match('/total biaya/iu',implode(' ',array_map(fn($cell)=>$this->plain($cell['blocks']),$row))))$total=$i;
                    if($total>=0){
                        foreach(array_slice($block['rows'],$total+1) as $tail){
                            $remaining=6;$count=count($tail);
                            foreach($tail as $n=>&$cell){$span=$n===$count-1?$remaining:max(1,(int)floor(6/$count));$cell['colspan']=$span;$remaining-=$span;}unset($cell);
                            $rows[]=$tail;
                        }
                    }
                    $block['rows']=$rows;
                    $block['column_widths']=[6,30,9,9,23,23];
                    $block['layout']='rab';
                    $block['data_binding']='rab_paket_neo';
                    $block['readonly']=true;
                }
                $result[]=$block;
            }
        }
        return $result;
    }

    public function missing():array{return array_keys($this->missing);}
    public function data():array{return $this->values;}
}
