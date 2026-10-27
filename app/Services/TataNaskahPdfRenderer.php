<?php

require_once __DIR__.'/TataNaskahFormatService.php';
require_once __DIR__.'/PdfTemplateService.php';

/** Native TCPDF layouts based on the annex of Permendagri 1/2023; never imports PDF pages. */
final class TataNaskahPdfRenderer
{
    private TCPDF $pdf;
    private array $header;
    private array $data;
    private array $setup;
    private array $used=[];
    private array $schema;
    private PdfTemplateService $body;
    private float $left;
    private float $width;

    public function render(TCPDF $pdf,array $header,array $schema,array $data,array $setup,?string $logo): void
    {
        $this->pdf=$pdf;$this->header=$header;$this->data=$data;$this->setup=$setup;$this->schema=$schema;$this->body=new PdfTemplateService($setup);
        $m=$pdf->getMargins();$this->left=(float)$m['left'];$this->width=$pdf->getPageWidth()-$m['left']-$m['right'];
        $p=TataNaskahFormatService::profile((int)$header['jenis_id']);$layout=$p['layout']??'general';
        $this->letterhead($logo);
        switch($layout){
            case 'letter': $this->letter();break;
            case 'memo':case 'review': $this->memo($p);break;
            case 'disposition':$this->disposition();break;
            case 'delivery':$this->delivery();break;
            case 'assignment':$this->assignment();break;
            case 'power':$this->power();break;
            case 'agreement':case 'minutes':$this->agreement($p);break;
            case 'report':case 'meeting':$this->structured($p);break;
            case 'certificate':case 'training':case 'award':$this->certificate($p);break;
            case 'statement':$this->statement();break;
            case 'circular':
                $this->kv('Yth.',$this->value('kepada'));$this->title($p['title']);$this->about();$this->field('isi');break;
            case 'instruction':
                $this->title($p['title']);$this->about();$this->field('dasar','Dasar');$this->field('menimbang','Menimbang');$this->heading('MENGINSTRUKSIKAN:');$this->kv('Kepada',$this->value('kepada'));$this->field('memerintahkan','Untuk');break;
            case 'sop':
                $this->title($p['title']);$rows=[];foreach($p['fields'] as $k=>$label)if($k!=='langkah')$rows[]=[$label==='Peralatan/Perlengkapan'?'Peralatan / Perlengkapan':$label,$this->value($k)];$this->table(['Identitas SOP','Uraian'],$rows,[35,65]);$this->field('langkah','Langkah SOP');break;
            default:
                $this->title($p['title']??$header['jenis_naskah']);$this->about();foreach($p['fields']??[] as $k=>$label)if($k!=='tentang')$this->field($k,in_array($k,['isi'],true)?'':$label);
        }
        // Historical schema/cache differences must never silently drop a saved body field.
        $extra=TataNaskahFormatService::withStoredFields([],$data);
        foreach($extra as $f){$k=$f['name'];if(!isset($this->used[$k])&&$k!=='tembusan'&&$k!=='tema'&&$k!=='agenda_pembelajaran')$this->field($k,$k==='isi'?'':$f['label']);}
        if(!in_array($layout,['disposition','power','agreement','minutes','delivery'],true))$this->signature();
        if(!in_array($layout,['memo'],true))$this->field('tembusan','Tembusan');
        if($layout==='training'&&($this->has('agenda_pembelajaran')||$this->has('tema'))){
            $pdf->AddPage();$this->heading('AGENDA PEMBELAJARAN',true);$this->field('agenda_pembelajaran');$this->heading('TEMA',true);$this->field('tema');$this->signature();
        }
    }

    private function text(mixed $v):string
    {
        if(is_array($v)){if(isset($v['text']))return $this->text($v['text']);return implode("\n",array_map(fn($x)=>$this->text($x),array_filter($v,fn($x)=>$x!==null)));}
        return trim(html_entity_decode(strip_tags(str_replace(['<br>','<br/>','<br />'],"\n",(string)$v)),ENT_QUOTES|ENT_HTML5,'UTF-8'));
    }
    private function value(string $key):string{$this->used[$key]=true;return $this->text($this->data[$key]??'');}
    private function has(string $key):bool{return trim($this->text($this->data[$key]??''))!=='';}
    private function font(string $style='',float $delta=0):void{$this->pdf->SetFont($this->setup['font'],$style,$this->setup['font_size']+$delta);}
    private function date(string $date):string
    {
        if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$date,$m))return (int)$m[3].' '.['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][(int)$m[2]].' '.$m[1];return $date;
    }
    private function locationDate():string{return trim($this->text($this->data['tempat_ditetapkan']??'').', '.$this->date((string)$this->header['tanggal_surat']),', ');}
    private function paragraph(string $text,string $align='J'):void
    {
        if($text==='')return;$this->font();$this->pdf->SetX($this->left);$this->pdf->MultiCell($this->width,6,$text.($align==='J'?"\n":''),0,$align);$this->pdf->Ln(2);
    }
    private function heading(string $text,bool $center=false):void
    {
        $this->font('B');$this->pdf->SetX($this->left);$this->pdf->MultiCell($this->width,6,$text,0,$center?'C':'L');$this->pdf->Ln(2);
    }
    private function title(string $title):void
    {
        $this->heading($title,true);$this->paragraph('NOMOR '.$this->header['nomor'],'C');$this->pdf->Ln(2);
    }
    private function about():void
    {
        $about=$this->value('tentang')?:$this->text($this->header['perihal']);if($about==='')return;$this->paragraph('TENTANG','C');$this->paragraph(mb_strtoupper($about),'C');$this->pdf->Ln(2);
    }
    private function kv(string $label,string $text):void
    {
        if($text==='')return;$this->font();$pdf=$this->pdf;$lw=$this->width*.32;$vw=$this->width-$lw-4;
        $height=max($pdf->getStringHeight($lw,$label),$pdf->getStringHeight($vw,$text),6);
        if($height<$pdf->getPageHeight()-$pdf->getBreakMargin()-$pdf->getMargins()['top']&&$pdf->GetY()+$height>$pdf->getPageHeight()-$pdf->getBreakMargin())$pdf->AddPage();
        $y=$pdf->GetY();$pdf->MultiCell($lw,6,$label,0,'L',false,0,$this->left,$y);$pdf->SetXY($this->left+$lw,$y);$pdf->Cell(4,6,':',0,0);$pdf->MultiCell($vw,6,$text,0,'L',false,1,$this->left+$lw+4,$y);$pdf->SetXY($this->left,max($pdf->GetY(),$y+$height));
    }
    private function field(string $key,string $label=''):void
    {
        $this->used[$key]=true;$raw=$this->data[$key]??'';
        if(is_string($raw)&&str_starts_with(trim($raw),'[')){$json=json_decode($raw,true);if(is_array($json))$raw=$json;}
        if($this->text($raw)==='')return;
        if($label!=='')$this->heading($label);
        $f=['type'=>is_array($raw)?'editable_table':'textarea','name'=>$key,'label'=>$label,'row_options'=>['hanging_indent'=>true,'default_align'=>'justify']];
        if(!is_array($raw)){$this->paragraph($this->text($raw));return;}
        $this->pdf->SetX($this->left);$this->body->render($this->pdf,[$f],[$key=>$raw]);$this->pdf->SetX($this->left);
    }
    private function letterhead(?string $logo):void
    {
        $u=$_SESSION['user']??[];$k=[];
        try{$k=DB::getInstance()->query('SELECT * FROM kop_surat_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND aktif=1 AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$this->header['kd_wilayah'],$this->header['kd_opd'],$this->header['tahun']])->fetch()?:[];}catch(Throwable){}
        $y=$this->pdf->GetY();$height=0;
        if(!empty($k['gunakan_gambar_kop'])&&!empty($k['gambar_kop'])){
            $path=dirname(__DIR__,2).'/'.ltrim($k['gambar_kop'],'/');if(is_file($path)){$size=getimagesize($path);$height=$size?$this->width*$size[1]/$size[0]:30;$height=min(45,$height);$this->image($path,$this->left,$y,$this->width,$height);$this->pdf->SetY($y+$height+12);return;}
        }
        $logos=[];foreach(['logo_kiri','logo_kanan'] as $side){$path=empty($k[$side])?'':dirname(__DIR__,2).'/'.ltrim($k[$side],'/');$logos[$side]=is_file($path)?$path:null;}
        $logos['logo_kiri']=$logos['logo_kiri']?:$logo;$sz=21;
        if($logos['logo_kiri'])$this->image($logos['logo_kiri'],$this->left,$y,$sz,$sz);
        if($logos['logo_kanan'])$this->image($logos['logo_kanan'],$this->left+$this->width-$sz,$y,$sz,$sz);
        $x=$this->left+($logos['logo_kiri']?$sz+3:0);$w=$this->width-($x-$this->left)-($logos['logo_kanan']?$sz+3:0);
        foreach([[mb_strtoupper($k['nama_pemerintah']??$u['nama_pemda']??'PEMERINTAH DAERAH'),'B',1],[mb_strtoupper($k['nama_opd']??$u['nama_opd']??'PERANGKAT DAERAH'),'B',2],[$k['alamat']??$u['alamat_opd']??'','',-2]] as [$text,$style,$delta]){
            if($text==='')continue;$this->font($style,$delta);$this->pdf->SetX($x);$this->pdf->MultiCell($w,6,$text,0,'C');
        }
        $contact=implode(' · ',array_filter([$k['telepon']??'',$k['email']??'',$k['website']??'']));if($contact!==''){$this->font('',-2);$this->pdf->SetX($x);$this->pdf->MultiCell($w,5,$contact,0,'C');}
        $line=max($y+$sz+2,$this->pdf->GetY()+2);$this->pdf->SetLineWidth(.6);$this->pdf->Line($this->left,$line,$this->left+$this->width,$line);$this->pdf->SetLineWidth(.2);$this->pdf->Line($this->left,$line+1,$this->left+$this->width,$line+1);$this->pdf->SetY($line+13);
    }
    private function image(string $path,float $x,float $y,float $w,float $h):void
    {
        // TCPDF's PNG cleanup calls imagedestroy(), deprecated on PHP 8.5.
        // Scope suppression to this vendor call so notices cannot corrupt the PDF stream.
        $level=error_reporting();try{error_reporting($level & ~E_DEPRECATED);$this->pdf->Image($path,$x,$y,$w,$h);}finally{error_reporting($level);}
    }
    private function signature(?array $leftParty=null,?array $rightParty=null):void
    {
        $pdf=$this->pdf;$this->font();$rightParty=$rightParty??['role'=>$this->data['jabatan_penandatangan']??'','name'=>$this->data['nama_penandatangan']??'','rank'=>$this->data['pangkat_penandatangan']??'','nip'=>$this->data['nip_penandatangan']??''];
        $gap=max(20,min(45,(float)($this->setup['signature_height']??30)));$h=30+$gap;
        $bottom=$pdf->getPageHeight()-$pdf->getBreakMargin();if($pdf->GetY()+$h>$bottom)$pdf->AddPage();
        $y=$pdf->GetY()+4;$w=$this->width*.47;$rightX=$this->left+$this->width-$w;$end=$y;
        foreach([[$leftParty,$this->left],[$rightParty,$rightX]] as [$party,$x]){
            if(!$party)continue;$this->font();$pdf->SetXY($x,$y);$pdf->MultiCell($w,6,$this->locationDate(),0,'C');$pdf->SetX($x);$pdf->MultiCell($w,6,(string)($party['role']??''),0,'C');$end=max($end,$pdf->GetY());
        }
        $baseline=$end+$gap;
        foreach([[$leftParty,$this->left],[$rightParty,$rightX]] as [$party,$x]){
            if(!$party)continue;$this->font('B');$pdf->SetXY($x,$baseline);$pdf->MultiCell($w,6,(string)($party['name']??''),0,'C');$this->font();foreach(['rank','nip'] as $k)if(!empty($party[$k])){$pdf->SetX($x);$pdf->MultiCell($w,6,($k==='nip'?'NIP. ':'').$party[$k],0,'C');}$end=max($end,$pdf->GetY());
        }$pdf->SetXY($this->left,$end+3);
    }
    private function table(array $labels,array $rows,array $widths):void
    {
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$this->font();$html='<table border="1" cellpadding="4"><thead><tr>';
        foreach($labels as $i=>$label)$html.='<th width="'.$widths[$i].'%"><b>'.$e($label).'</b></th>';$html.='</tr></thead><tbody>';
        foreach($rows as $row){$html.='<tr nobr="true">';foreach($labels as $i=>$label)$html.='<td width="'.$widths[$i].'%">'.nl2br($e($row[$i]??'')).'</td>';$html.='</tr>';}$html.='</tbody></table>';$this->pdf->writeHTML($html,true,false,true,false,'');
    }
    private function letter():void
    {
        $this->paragraph($this->locationDate(),'R');foreach(['nomor'=>'Nomor','sifat'=>'Sifat','lampiran'=>'Lampiran','perihal'=>'Hal'] as $k=>$label){$this->used[$k]=true;$this->kv($label,$this->text($this->header[$k]??$this->data[$k]??''));}$this->pdf->Ln(4);
        $to=$this->value('kepada');if($to!=='')$this->paragraph('Yth. '.$to,'L');$address=$this->value('alamat_tujuan');if($address!=='')$this->paragraph("di\n".$address,'L');
        $this->field('pembuka');$this->field('isi');foreach(['hari_tanggal'=>'Hari/Tanggal','waktu'=>'Waktu','tempat'=>'Tempat'] as $k=>$label)$this->kv($label,$this->value($k));$this->field('agenda','Acara');$this->field('penutup');
    }
    private function memo(array $p):void
    {
        $this->heading($p['title'],true);foreach(['kepada'=>'Yth.','dari'=>'Dari','tembusan'=>'Tembusan','tanggal_surat'=>'Tanggal','nomor'=>'Nomor','sifat'=>'Sifat','lampiran'=>'Lampiran','perihal'=>'Hal'] as $k=>$label){$v=$this->text($this->header[$k]??$this->data[$k]??'');$this->used[$k]=true;$this->kv($label,$k==='tanggal_surat'?$this->date($v):$v);}$this->pdf->Ln(5);
        if($p['layout']==='review')foreach(['persoalan','praanggapan','fakta','analisis','kesimpulan','rekomendasi'] as $k)$this->field($k,$p['fields'][$k]);else $this->field('isi');
    }
    private function disposition():void
    {
        $this->heading('LEMBAR DISPOSISI',true);$rows=[];foreach(['surat_dari'=>'Surat dari','nomor_surat_masuk'=>'Nomor Surat','tanggal_surat_masuk'=>'Tanggal Surat','tanggal_diterima'=>'Diterima Tanggal','nomor_agenda'=>'Nomor Agenda','sifat'=>'Sifat'] as $k=>$label)$rows[]=[$label,$this->value($k)];$rows[]=['Hal',$this->header['perihal']];$this->table(['Surat Masuk','Keterangan'],$rows,[35,65]);$this->table(['Diteruskan kepada','Dengan Hormat Harap'],[[$this->value('diteruskan_kepada'),$this->value('tindak_lanjut')]],[50,50]);$this->field('isi','Catatan');$this->signature();
    }
    private function assignment():void
    {
        $this->title('SURAT TUGAS');$this->field('dasar','Dasar');$this->heading('MEMERINTAHKAN:',true);$this->heading('Kepada');$people=$this->data['petugas']??$this->data['nama_ditugaskan']??[];$this->used['petugas']=$this->used['nama_ditugaskan']=true;
        foreach(is_array($people)?$people:[] as $i=>$row){$this->heading(($i+1).'.');foreach(['nama'=>'Nama','pangkat'=>'Pangkat/Golongan','nip'=>'NIP','jabatan'=>'Jabatan'] as $k=>$label)$this->kv($label,$this->text($row[$k]??''));}$this->field('isi','Untuk');
    }
    private function delivery():void
    {
        $this->paragraph($this->locationDate(),'R');$this->kv('Yth.',$this->value('kepada'));$this->kv('Alamat',$this->value('alamat_tujuan'));$this->title('SURAT PENGANTAR');$raw=$this->data['barang']??[];$this->used['barang']=true;$rows=[];foreach(is_array($raw)?$raw:[] as $i=>$r)$rows[]=[$i+1,$r['uraian']??$r['text']??'',$r['banyaknya']??'',$r['keterangan']??''];$this->table(['No.','Naskah Dinas/Barang yang Dikirimkan','Banyaknya','Keterangan'],$rows,[7,49,18,26]);$this->field('isi');$this->kv('Diterima tanggal',$this->date($this->value('tanggal_diterima')));$this->signature(['role'=>'Penerima'.($this->has('jabatan_penerima')?"\n".$this->value('jabatan_penerima'):''),'name'=>$this->value('penerima'),'nip'=>$this->value('nip_penerima')]);$this->kv('Nomor telepon',$this->value('telepon_penerima'));
    }
    private function power():void
    {
        $this->title('SURAT KUASA');$this->paragraph('Yang bertanda tangan di bawah ini:','L');foreach(['pemberi'=>'Nama','jabatan_pemberi'=>'Jabatan','alamat_pemberi'=>'Alamat'] as $k=>$l)$this->kv($l,$this->value($k));$this->paragraph('memberi kuasa kepada:','L');foreach(['penerima'=>'Nama','jabatan_penerima'=>'Jabatan','alamat_penerima'=>'Alamat'] as $k=>$l)$this->kv($l,$this->value($k));$this->field('isi','Untuk');$this->paragraph('Surat Kuasa ini dibuat untuk dipergunakan sebagaimana mestinya.');$this->signature(['role'=>'Penerima Kuasa','name'=>$this->text($this->data['penerima']??'')],['role'=>'Pemberi Kuasa','name'=>$this->text($this->data['pemberi']??$this->data['nama_penandatangan']??''),'rank'=>$this->data['pangkat_penandatangan']??'','nip'=>$this->data['nip_penandatangan']??'']);
    }
    private function agreement(array $p):void
    {
        $this->title($p['title']);if($p['layout']==='agreement')$this->about();$this->kv('Nomor Pihak Kedua',$this->value('nomor_pihak2'));$this->field('pembuka');foreach([1,2] as $i)foreach(['pihak'.$i=>'Nama Pihak '.$i,'jabatan_pihak'.$i=>'Jabatan','alamat_pihak'.$i=>'Alamat'] as $k=>$l)$this->kv($l,$this->value($k));$this->kv('Kegiatan',$this->value('kegiatan'));$this->kv('Tanggal',$this->date($this->value('tanggal')));$this->field($p['layout']==='agreement'?'pasal':'hasil');$this->signature(['role'=>'Pihak Kedua'.($this->has('jabatan_pihak2')?"\n".$this->text($this->data['jabatan_pihak2']):''),'name'=>$this->text($this->data['pihak2']??'')],['role'=>'Pihak Pertama'.($this->has('jabatan_pihak1')?"\n".$this->text($this->data['jabatan_pihak1']):''),'name'=>$this->text($this->data['pihak1']??$this->data['nama_penandatangan']??'')]);$this->field('saksi','Saksi-Saksi');
    }
    private function structured(array $p):void
    {
        $this->heading($p['title'],true);
        if($p['layout']==='meeting'){
            foreach(['rapat'=>'Sidang/Rapat','hari_tanggal'=>'Hari/Tanggal','surat_undangan'=>'Surat Undangan','waktu'=>'Waktu Sidang/Rapat'] as $k=>$l)$this->kv($l,$this->value($k));
            $this->field('agenda','Acara');$this->heading('Pimpinan Sidang/Rapat');foreach(['ketua'=>'Ketua','sekretaris'=>'Sekretaris','pencatat'=>'Pencatat','peserta'=>'Peserta Sidang/Rapat'] as $k=>$l)$this->kv($l,$this->value($k));$this->field('hasil','Kegiatan Sidang/Rapat');return;
        }
        $this->about();if($this->has('latar_belakang')||$this->has('dasar')||$this->has('maksud_tujuan'))$this->heading('A. Pendahuluan');foreach($p['fields'] as $k=>$l)if($k!=='tentang')$this->field($k,preg_replace('/^A\./','',$l));
    }
    private function certificate(array $p):void
    {
        $this->heading($p['title'],true);$this->paragraph('Nomor: '.$this->header['nomor'],'C');$this->pdf->Ln(4);$this->field('dasar');$this->paragraph($p['layout']==='award'?'Dengan ini memberikan penghargaan kepada:':($p['layout']==='training'?'Diberikan kepada:':'Diberikan kepada:'),'C');
        $name=$this->value('nama');if($name!==''){$this->font('B',4);$this->pdf->MultiCell($this->width,9,$name,0,'C');$this->pdf->Ln(3);}
        foreach(['tempat_tanggal_lahir'=>'Tempat/Tanggal Lahir','nip'=>'NIP/NRP','pangkat'=>'Pangkat/Golongan Ruang','jabatan'=>'Jabatan','instansi'=>'Instansi','kualifikasi'=>'Kualifikasi'] as $k=>$l)$this->kv($l,$this->value($k));
        $this->field('tentang');$this->field('isi');$this->kv($p['layout']==='training'?'Pendidikan dan Pelatihan':'Kegiatan',$this->value($p['layout']==='training'?'pelatihan':'kegiatan'));$this->kv('Sebagai',$this->value('peran'));$this->kv('Penyelenggara',$this->value('penyelenggara'));$this->kv('Tempat',$this->value('tempat'));foreach(['tanggal','tanggal_mulai','tanggal_selesai'] as $k)$this->kv($k==='tanggal_selesai'?'Sampai dengan':'Tanggal',$this->date($this->value($k)));
    }
    private function statement():void
    {
        $this->title('SURAT KETERANGAN');$this->paragraph('Yang bertanda tangan di bawah ini:','L');$this->kv('Nama',$this->text($this->data['nama_penandatangan']??''));$this->kv('Jabatan',$this->text($this->data['jabatan_penandatangan']??''));$this->paragraph('Dengan ini menerangkan bahwa:','L');foreach(['nama'=>'Nama','nip'=>'NIP','pangkat'=>'Pangkat/Golongan','jabatan'=>'Jabatan'] as $k=>$l)$this->kv($l,$this->value($k));$this->field('isi');
    }
}
