<?php
require_once __DIR__.'/ProcurementTermsLayoutService.php';
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/OfficialLetterheadPdfService.php';
require_once __DIR__.'/ProcurementZipPdfService.php';
require_once __DIR__.'/ProcurementReferenceTermsService.php';
require_once __DIR__.'/ProcurementParagraphLayoutService.php';
require_once __DIR__.'/ProcurementContractFormService.php';
require_once __DIR__.'/ProcurementDocumentFormatService.php';
require_once __DIR__.'/ProcurementContractTypeService.php';

/** Renderer tata letak dokumen pengadaan. Pengguna tidak pernah mengedit HTML. */
final class ProcurementFixedPdfService
{
    private array $user;
    private string $font='helvetica';
    private float $baseSize=9.0;
    private bool $customFooter=true;
    private ?TCPDF $sharedPdf=null;
    private string $orientation='P';
    private array $printSettings=[];
    public function __construct(array $user){$this->user=$user;foreach(['nama_opd','nama_org'] as $key)if(!empty($this->user[$key]))$this->user[$key]=ProcurementDocumentFormatService::bodyOpdName($this->user[$key]);}

    public function render(array $document,array $contract,array $setup):string
    {
        $this->user=OfficialLetterheadPdfService::forContract($this->user,$contract);
        foreach(['nama_opd','alamat_opd'] as $field)if(empty($contract[$field]))$contract[$field]=$this->user[$field]??'';
        $this->user=array_merge($this->user,array_intersect_key($contract,array_flip(['nama_pejabat_pengadaan','nip_pejabat_pengadaan','pangkat_pejabat_pengadaan'])));
        $code=strtoupper((string)($document['kode_dokumen']??''));
        $contract['print_settings']=ContractPrintSettingsService::validate($contract['print_settings']??ContractPrintSettingsService::get((int)($contract['id']??0)));
        $data=$document['data_isian']??[];if(is_string($data))$data=json_decode($data,true)?:[];
        $contract['terms_variant']=$data['terms_variant']??(!empty($document['id'])?'STANDARD':$contract['print_settings']['terms_variant']);
        $setup=array_merge($setup,$contract['print_settings']);
        if(ProcurementDocumentFormatService::supports($code,$contract)){$setup=ProcurementDocumentFormatService::setup($setup,$contract['print_settings']??[]);$this->user['_letterhead_font']=ProcurementDocumentFormatService::BODY_FONT;}
        if(str_starts_with($code,'SPK_'))return $this->spk($document,$contract,$setup);
        if($code==='SURAT_PERJANJIAN')return $this->contract($document,$contract,$setup);
        if($code==='SSKK')return $this->sskk($document,$contract,$setup);
        if($code==='SSUK')return $this->ssuk($document,$contract,$setup);
        $zipRenderer=new ProcurementZipPdfService($this->user);
        if($zipRenderer->supports($code)){
            if($this->sharedPdf){$zipRenderer->renderInto($this->sharedPdf,$code,$document,$contract,$setup);return '';}
            return $zipRenderer->render($code,$document,$contract,$setup);
        }
        return $this->officialLetter($document,$contract,$setup);
    }

    public function renderInto(TCPDF $pdf,array $document,array $contract,array $setup):void
    {
        $this->sharedPdf=$pdf;
        try{$this->render($document,$contract,$setup);}finally{$this->sharedPdf=null;}
    }
    private function finish(TCPDF $pdf):string{return $this->sharedPdf?'':$pdf->Output('','S');}

    public function cover(TCPDF $pdf,array $c,array $setup,?string $form=null):void
    {
        $this->user=OfficialLetterheadPdfService::forContract($this->user,$c);
        $form=$form??ProcurementContractFormService::recommend($c);
        ProcurementDocumentFormatService::registerFonts($pdf);PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);$pdf->AddPage(PageSetupService::orientation($setup,'P'));
        $coverFont=$setup['font']??ProcurementDocumentFormatService::BODY_FONT;
        $m=$pdf->getMargins();$x=$m['left'];$y=$m['top'];$w=$pdf->getPageWidth()-$x-$m['right'];$bottom=$pdf->getPageHeight()-$m['bottom']-3;$h=$bottom-$y;$layoutScale=min(1.0,$h/270,$w/174);$fontScale=(float)($setup['font_size']??12)/12;
        // Measure the native letterhead so its background ends at the lower rule.
        $letterheadUser=array_merge($this->user,['_letterhead_font'=>$coverFont]);
        $pdf->SetMargins($x+4,$m['top'],$m['right']+4);$pdf->SetXY($x+4,$y+3);
        $pdf->startTransaction();OfficialLetterheadPdfService::draw($pdf,$letterheadUser);$headerBottom=$pdf->GetY()-4;$pdf->rollbackTransaction(true);
        $pdf->SetFillColor(183,211,159);$pdf->Rect($x,$y,$w,$headerBottom-$y,'F');
        $pdf->SetXY($x+4,$y+3);OfficialLetterheadPdfService::draw($pdf,$letterheadUser);$pdf->SetMargins($x,$m['top'],$m['right']);
        $pdf->SetTextColor(0,0,0);$pdf->SetFont($coverFont,'B',23*$fontScale);$pdf->SetXY($x+3,$y+44*$layoutScale);$pdf->MultiCell($w-6,10,ProcurementContractFormService::label($form),0,'C');
        $pdf->SetFont($coverFont,'B',11*$fontScale);$pdf->MultiCell($w-6,6,'Kontrak '.ProcurementContractTypeService::label($c),0,'C');
        $kind=strtoupper(trim((string)($c['jenis_pengadaan']??'')));
        $packageLabel=match(true){
            strtoupper($c['cara_pengadaan']??'')==='SWAKELOLA'=>'Paket Swakelola',
            str_starts_with($kind,'KONSULTANSI')=>'Paket Pengadaan Konsultansi',
            $kind==='PEKERJAAN_KONSTRUKSI'=>'Paket Pengadaan Pekerjaan Konstruksi',
            $kind==='BARANG'=>'Paket Pengadaan Barang',
            $kind==='JASA_LAINNYA'=>'Paket Pengadaan Jasa Lainnya',
            default=>'Paket Pengadaan',
        };
        $pdf->SetFillColor(255,211,0);$pdf->Rect($x,$y+72*$layoutScale,$w,10*$layoutScale,'F');$pdf->SetXY($x,$y+74*$layoutScale);$pdf->SetFont($coverFont,'B',12*$fontScale);$pdf->MultiCell($w,6,$packageLabel,0,'C');
        $pdf->SetFillColor(255,224,126);$pdf->Rect($x,$y+82*$layoutScale,$w,26*$layoutScale,'F');$pdf->SetXY($x+6,$y+88*$layoutScale);$pdf->SetFont($coverFont,'B',13*$fontScale);$pdf->MultiCell($w-12,6,$this->value($c,'uraian_kontrak'),0,'C');
        $pdf->SetXY($x+12,$y+120*$layoutScale);$pdf->SetFont($coverFont,'B',12*$fontScale);
        $fields=[['Nomor',$this->value($c,'nomor_kontrak')],['Tanggal',$this->displayDate($c['tanggal_kontrak']??'')],['Sumber Dana',$c['sumber_dana']??'APBD'],['Uraian Kontrak',$this->value($c,'uraian_kontrak')],['Nilai Kontrak',isset($c['nilai_kontrak'])?$this->coverAmount($c['nilai_kontrak']):'Belum ditetapkan'],['Waktu Pelaksanaan',isset($c['waktu_pelaksanaan'])?$c['waktu_pelaksanaan']." hari kalender\n".$this->displayDate($c['tanggal_mulai']??'').' s.d. '.$this->displayDate($c['tanggal_selesai']??''):'Belum ditetapkan']];
        foreach($fields as [$label,$value]){
            $at=$pdf->GetY();$pdf->SetXY($x+10,$at);$pdf->MultiCell(48*$layoutScale,6*$layoutScale,$label,0,'L');$labelBottom=$pdf->GetY();
            $pdf->SetXY($x+58*$layoutScale,$at);$pdf->MultiCell(4*$layoutScale,6*$layoutScale,':',0,'C');
            $pdf->SetXY($x+62*$layoutScale,$at);$pdf->MultiCell($w-72*$layoutScale,6*$layoutScale,$value,0,'L');
            $pdf->SetY(max($labelBottom,$pdf->GetY())+3*$layoutScale);
        }
        $pdf->SetXY($x,$bottom-48*$layoutScale);$pdf->SetFont($coverFont,'B',14*$fontScale);$pdf->MultiCell($w,7,'PENYEDIA',0,'C');$pdf->SetFont($coverFont,'B',17*$fontScale);$pdf->MultiCell($w,8,$this->value($c,'nama_penyedia_resmi','nama_perusahaan','nama_penyedia'),0,'C');
        $pdf->SetFillColor(255,224,126);$pdf->Rect($x,$bottom-15*$layoutScale,$w,10*$layoutScale,'F');$pdf->SetXY($x,$bottom-14*$layoutScale);$pdf->SetFont($coverFont,'B',17*$fontScale);$pdf->MultiCell($w,8,'TAHUN ANGGARAN '.$c['tahun'],0,'C');
        $pdf->SetDrawColor(0,0,0);$pdf->SetLineWidth(.3);
        foreach([$y+72*$layoutScale,$y+82*$layoutScale,$y+108*$layoutScale,$bottom-15*$layoutScale,$bottom-5*$layoutScale] as $ruleY)$pdf->Line($x,$ruleY,$x+$w,$ruleY);
        $pdf->SetLineWidth(1);$pdf->Rect($x,$y,$w,$h);$pdf->SetLineWidth(.2);
    }
    private function displayDate(string $date):string
    {
        if(!$date)return '-';$t=strtotime($date);if(!$t)return $date;$months=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return date('j',$t).' '.$months[(int)date('n',$t)].' '.date('Y',$t);
    }

    public function directContract(array $contract,array $setup):string
    {
        $contract['print_settings']=$contract['print_settings']??ContractPrintSettingsService::get((int)($contract['id']??0));
        $data=$document['data_isian']??[];if(is_string($data))$data=json_decode($data,true)?:[];
        $contract['terms_variant']=$data['terms_variant']??(!empty($document['id'])?'STANDARD':$contract['print_settings']['terms_variant']);
        $setup=array_merge($setup,$contract['print_settings']);
        $form=ProcurementContractFormService::recommend($contract);
        if($form==='SURAT_PESANAN')return $this->render(['kode_dokumen'=>'SURAT_PESANAN','judul'=>'Surat Pesanan','nomor_dokumen'=>$contract['nomor_kontrak']??'','tanggal_dokumen'=>$contract['tanggal_kontrak']??''],$contract,$setup);
        if($form==='SPK')return $this->spk(['kode_dokumen'=>'SPK_'.($contract['jenis_pengadaan']==='PEKERJAAN_KONSTRUKSI'?'KONSTRUKSI':$contract['jenis_pengadaan']),'nomor_dokumen'=>$contract['nomor_kontrak'],'tanggal_dokumen'=>$contract['tanggal_kontrak']],$contract,ProcurementDocumentFormatService::setup($setup,$contract['print_settings']??[]));
        return $this->contract(['kode_dokumen'=>'SURAT_PERJANJIAN','judul'=>'Surat Perjanjian','nomor_dokumen'=>$contract['nomor_kontrak']??'','tanggal_dokumen'=>$contract['tanggal_kontrak']??date('Y-m-d'),'sections'=>[],'attachments'=>[]],$contract,$setup);
    }

    private function pdf(array $setup,string $orientation='P'):TCPDF
    {
        $this->printSettings=ContractPrintSettingsService::validate($setup);$this->font=(string)($setup['font']??'helvetica');$this->baseSize=max(6,min(18,(float)($setup['font_size']??9)));$this->customFooter=false;$pdf=$this->sharedPdf??PageSetupService::createPdf($setup,$orientation);ProcurementDocumentFormatService::registerFonts($pdf);PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);$this->orientation=PageSetupService::orientation($setup,$orientation);ProcurementDocumentFormatService::typography($pdf,$setup);$pdf->SetFont($this->font,'',$this->baseSize);return$pdf;
    }
    private function addOfficialPage(TCPDF $pdf):void{$pdf->AddPage($this->orientation);OfficialLetterheadPdfService::draw($pdf,array_merge($this->user,['_letterhead_font'=>$this->font]));}
    private function e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private function value(array $c,string ...$keys):string{foreach($keys as $key)if(trim((string)($c[$key]??''))!=='')return(string)$c[$key];return'-';}
    private function money($value):string{return number_format((float)$value,2,',','.');}
    private function coverAmount($value):string
    {
        [$rupiah,$sen]=explode('.',number_format((float)$value,2,'.',''));
        $words=ucwords(trim($this->numberWords((int)$rupiah))).' Rupiah';
        if((int)$sen>0)$words.=' '.ucwords(trim($this->numberWords((int)$sen))).' Sen';
        return 'Rp'.$this->money($value).' ('.$words.')';
    }
    private function paragraphs(array $document,array $contract,bool $jsonOnly=false):array
    {
        $code=strtoupper((string)($document['kode_dokumen']??''));
        if(empty($document['id'])&&in_array($code,['SURAT_PERJANJIAN','SSKK','SSUK'],true)){$reference=ProcurementReferenceTermsService::rows($code,$contract);foreach($reference as &$row)$row['text']=$this->variables((string)($row['text']??''),$document,$contract);unset($row);if($reference)return$reference;}$out=[];foreach($document['sections']??[] as $section){$raw=$section['isi']??$section['isi_template']??'';$decoded=is_string($raw)?json_decode($raw,true):$raw;if(is_array($decoded)){foreach($decoded as $row){if(is_string($row))$row=['text'=>$row];if(!is_array($row)||trim((string)($row['text']??''))==='')continue;$row['text']=$this->variables((string)$row['text'],$document,$contract);$out[]=$row;}continue;}if($jsonOnly)continue;$text=preg_replace('/[ \t]+/u',' ',html_entity_decode(strip_tags(str_replace(['</p>','</li>','<br>','<br/>','<br />'],"\n",(string)$raw)),ENT_QUOTES|ENT_HTML5,'UTF-8'));foreach(preg_split('/\n+/',$text)?:[] as $line)if(trim($line)!=='')$out[]=['type'=>'paragraph','align'=>'justify','text'=>$this->variables(trim($line),$document,$contract)];}return$out;
    }
    private function variables(string $text,array $d,array $c):string
    {
        $map=['nama_opd'=>$this->user['nama_opd']??$this->user['nama_org']??$c['kd_opd']??'-','nama_dokumen'=>$d['judul']??'-','nomor_dokumen'=>$d['nomor_dokumen']??'-','tanggal_dokumen'=>$d['tanggal_dokumen']??'-','nama_paket'=>$c['uraian_kontrak']??'-','kd_sub_keg'=>$c['kd_sub_keg']??'-','sumber_dana'=>$c['sumber_dana']??'APBD','nilai_kontrak'=>$this->money($c['nilai_kontrak']??0),'nama_ppk'=>$c['nama_ppk_resmi']??$c['nama_ppk']??'-','nip_ppk'=>$c['nip_ppk_resmi']??$c['nip_ppk']??'-','nama_penyedia'=>$c['nama_penyedia_resmi']??$c['nama_penyedia']??'-'];foreach($map as $key=>$value)$text=str_replace('{{'.$key.'}}',(string)$value,$text);return$text;
    }
    private function writeParagraphs(TCPDF $pdf,array $rows):void
    {
        $sequence=[];
        foreach(ProcurementParagraphLayoutService::normalize($rows) as $row){
            if(in_array($row['format']??'',['clause-heading','hanging-list','body'],true)){$this->writeTermsParagraphs($pdf,[$row]);continue;}
            $type=$row['type']??'paragraph';$style=is_array($row['style']??null)?$row['style']:[];
            $font=(in_array('bold',$style,true)?'B':'').(in_array('italic',$style,true)?'I':'').(in_array('underline',$style,true)?'U':'');
            $pdf->SetFont($this->font,$font,$this->baseSize);
            $align=['left'=>'L','center'=>'C','right'=>'R','justify'=>'J'][$row['align']??'justify']??'J';$prefix='';
            if($type==='list')$prefix='•';
            elseif(in_array($type,['numbered','alpha'],true)){$sequence[$type]=($sequence[$type]??0)+1;$prefix=$type==='alpha'?chr(96+$sequence[$type]).'.':$sequence[$type].'.';}
            $pdf->Ln(($this->printSettings['paragraph_before']??0)*25.4/72);
            $margin=$pdf->getMargins();$x=$margin['left'];$width=$pdf->getPageWidth()-$x-$margin['right'];
            if($prefix!==''){
                $lineHeight=$pdf->getCellHeight($this->baseSize*25.4/72);
                if($pdf->GetY()+$lineHeight>$pdf->getPageHeight()-$pdf->getBreakMargin())$pdf->AddPage($this->orientation);
                $y=$pdf->GetY();$pdf->SetXY($x,$y);$pdf->Cell(6,0,$prefix,0,0,'L');$x+=6;$width-=6;$pdf->SetXY($x,$y);
            }
            $pdf->MultiCell($width,0,rtrim((string)$row['text'])."\n",0,$align,false,1,$x,'',true,0,false,true);
            $pdf->SetX($margin['left']);$pdf->Ln(($this->printSettings['paragraph_after']??6)*25.4/72);
        }
    }
    private function writeJustified(TCPDF $pdf,string $text,float $height=5.0):void
    {
        // TCPDF needs a trailing line break so the last visual line is not stretched.
        $pdf->MultiCell(0,$height,rtrim($text)."\n",0,'J',false,1,'','',true,0,false,true);
    }
    private function writeTermsParagraphs(TCPDF $pdf,array $rows):void
    {
        foreach($rows as $row){
            $text=trim((string)($row['text']??''));if($text==='')continue;
            $format=(string)($row['format']??'body');
            if($format==='clause-heading'){
                $needed=preg_match('/^Pasal\s+\d+/i',$text)?24:14;
                if($pdf->GetY()+$needed>$pdf->getPageHeight()-$pdf->getBreakMargin())$pdf->AddPage($this->orientation);
                $pdf->Ln(preg_match('/^Pasal\s+\d+/i',$text)?3:1);$pdf->SetFont($this->font,'B',$this->baseSize);
                $align=['left'=>'L','center'=>'C','right'=>'R'][$row['align']??'left']??'L';
                $pdf->MultiCell(0,5.2,$text,0,$align,false,1);continue;
            }
            $pdf->SetFont($this->font,'',$this->baseSize);
            if($format==='hanging-list'&&preg_match('/^(\([a-z0-9]+\)|\d+\.|[•●▪])\s+(.+)$/isu',$text,$m)){
                $this->hangingParagraph($pdf,$m[1],$m[2]);continue;
            }
            if($format==='hanging-list'&&preg_match('/^((?:[a-z][.)]|\d+\)|[ivx]+\.))\s+(.+)$/isu',$text,$m)){
                $this->hangingParagraph($pdf,$m[1],$m[2],8);continue;
            }
            $pdf->MultiCell(0,5.0,rtrim($text)."\n",0,'J',false,1,'','',true,0,false,true);$pdf->Ln(.8);
        }
    }
    private function hangingParagraph(TCPDF $pdf,string $prefix,string $text,float $indent=0):void
    {
        $margin=$pdf->getMargins();$x=$margin['left']+$indent;
        $width=$pdf->getPageWidth()-$margin['right']-($x+8);
        $body=rtrim($text)."\n";
        $height=max(5.0,$pdf->getStringHeight($width,$body));
        $bottom=$pdf->getPageHeight()-$pdf->getBreakMargin();
        $required=min($height,$bottom-$margin['top']);
        // Move the label and its paragraph together before Cell can change pages.
        if($pdf->GetY()+$required>$bottom)$pdf->AddPage($this->orientation);
        $y=$pdf->GetY();$pdf->SetXY($x,$y);$pdf->Cell(6,5.0,$prefix,0,0,'L');
        $pdf->SetXY($x+8,$y);$pdf->MultiCell($width,5.0,$body,0,'J',false,1);$pdf->SetX($margin['left']);$pdf->Ln(1.5);
    }
    private function footerPages(TCPDF $pdf,string $label):void
    {
        if(!$this->customFooter)return;
        $pages=$pdf->getNumPages();
        $pdf->SetAutoPageBreak(false,0);
        for($page=1;$page<=$pages;$page++){
            $pdf->setPage($page);
            $pdf->SetAutoPageBreak(false,0);
            $m=$pdf->getMargins();
            $usable=$pdf->getPageWidth()-$m['left']-$m['right'];
            $pdf->SetFont($this->font,'I',7);
            $pdf->SetXY($m['left'],$pdf->getPageHeight()-12);
            $pdf->Cell($usable/2,4,strtolower($label),0,0,'L');
            $pdf->Cell($usable/2,4,'hal '.$page.' dari '.$pages,0,0,'R');
        }
        $pdf->setPage($pages);
    }

    private function spk(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,'P');
        if($d['include_cover']??true)$this->cover($pdf,$c,$setup,'SPK');
        $pdf->AddPage(PageSetupService::orientation($setup,'P'));$first=$pdf->getPage();ProcurementDocumentFormatService::typography($pdf,$setup);
        $name=$this->e($this->user['nama_opd']??'-');
        $provider=$this->e($this->value($c,'nama_penyedia_resmi','nama_penyedia'));
        $date=$this->e($this->displayDate($d['tanggal_dokumen']??$c['tanggal_kontrak']));
        $fields=function(array $rows):string{
            $html='<br><table width="100%" cellpadding="2">';
            foreach($rows as [$label,$value])$html.='<tr><td width="27%">'.$label.'</td><td width="3%">:</td><td width="70%">'.$value.'</td></tr>';
            return $html.'</table>';
        };
        $row=static fn(string $label,string $body):string=>'<tr nobr="true"><td width="35%" valign="middle"><b>'.$label.'</b></td><td width="65%">'.$body.'</td></tr>';
        $html='<table border="1" cellpadding="4">';
        $html.=$row('SURAT PERINTAH KERJA (SPK)','<b>SATUAN KERJA<br>'.$name.'</b><br><br>NOMOR DAN TANGGAL SPK'.$fields([['Nomor',$this->e($c['nomor_kontrak'])],['Tanggal',$date]]));
        $html.=$row('NAMA PENGGUNA JASA',$fields([
            ['Nama',$this->e($this->value($c,'nama_ppk_resmi','nama_ppk'))],['Nip.',$this->e($this->value($c,'nip_ppk_resmi','nip_ppk'))],
            ['Jabatan','Pejabat Pembuat Komitmen'],['Berkedudukan di','Pasangkayu']
        ]).'<br>yang bertindak untuk dan atas nama '.$name.' berdasarkan Surat Keputusan Nomor '.$this->e($this->value($c,'nomor_sk_ppk')).' tanggal '.$this->e($this->displayDate($c['tanggal_sk_ppk']??'')).' tentang Pengangkatan Pejabat pembuat komitmen (PPK) '.$name.' tahun '.$c['tahun'].' selanjutnya disebut &quot;Pengguna Jasa&quot;, dengan:');
        $html.=$row('NAMA PENYEDIA',$fields([
            ['Nama',$this->e($this->value($c,'direktur'))],['Jabatan',$this->e($this->value($c,'jabatan_pelaksana'))],['Berkedudukan di',$this->e($this->value($c,'alamat_penyedia','alamat'))]
        ]).'<br><u>Akta Notaris</u>'.$fields([['Nomor',$this->e($this->value($c,'no_akta_pendirian'))],['Tanggal',$this->e($this->displayDate($c['tgl_akta_pendirian']??''))],['Notaris',$this->e($this->value($c,'nama_notaris_pendirian'))]]).'<br>yang bertindak untuk '.$provider.' selanjutnya disebut &quot;Penyedia&quot;.');
        $inv=$c['dokumen_referensi']['UNDANGAN_PL']??[];$result=$c['dokumen_referensi']['BAHP']??[];
        $html.=$row('PAKET PENGADAAN<br><br><div align="center"><u>'.$this->e($c['uraian_kontrak']).'</u></div>',
            'NOMOR DAN TANGGAL SURAT UNDANGAN PENGADAAN LANGSUNG:'.$fields([['Nomor',$this->e($inv['nomor_dokumen']??'-')],['Tanggal',$this->e($this->displayDate($inv['tanggal_dokumen']??''))]]).'<br>NOMOR DAN TANGGAL BERITA ACARA HASIL PENGADAAN LANGSUNG:'.$fields([['Nomor',$this->e($result['nomor_dokumen']??'-')],['Tanggal',$this->e($this->displayDate($result['tanggal_dokumen']??''))]]));
        $html.=$row('SUMBER DANA',$this->e($c['sumber_dana']??'APBD').' Dibebankan atas '.strtoupper($c['tahap']??'DPA').' '.$name.' Tahun Anggaran '.$c['tahun']);
        $duration=$this->e($this->value($c,'waktu_pelaksanaan'));
        $html.=$row('MASA PELAKSANAAN PEKERJAAN:',$duration.' ('.$this->e(ucwords($this->numberWords((int)($c['waktu_pelaksanaan']??0)))).') hari kalender dihitung sejak Tanggal Mulai Kerja yang tercantum dalam SPMK sampai dengan Tanggal Penyerahan Pertama Pekerjaan');
        $amount=isset($c['nilai_kontrak'])?'Rp'.$this->money($c['nilai_kontrak']):'Belum ditetapkan';
        $words=isset($c['nilai_kontrak'])?ucwords($this->numberWords((int)round($c['nilai_kontrak']))).' Rupiah':'Belum ditetapkan';
        $html.='<tr nobr="true"><td colspan="2"><b>HARGA KONTRAK<br><br>Harga Kontrak termasuk Pajak Pertambahan Nilai (PPN) adalah sebesar '.$amount.' ('.$this->e($words).') yang diperoleh berdasarkan total harga penawaran terkoreksi aritmatik sebagaimana tercantum dalam Daftar Kuantitas dan Harga Penawaran. (Melalui koreksi aritmatik)</b></td></tr>';
        $html.='<tr nobr="true"><td colspan="2"><p><b>SISTEM PEMBAYARAN</b></p><p>1) Pembayaran untuk kontrak ini dilakukan ke Bank '.$this->e($this->value($c,'bank_rekening')).' rekening nomor: '.$this->e($this->value($c,'no_rekening')).' atas nama Penyedia: '.$provider.'</p><p>2) Pembayaran prestasi pekerjaan dilakukan dengan cara: Bulanan</p></td></tr></table>';
        // Keep one flowing SPK table. TCPDF moves a row only when the remaining
        // printable height cannot contain it; no document section forces a page.
        $html=substr($html,0,-strlen('</table>'));
        $html.='<tr nobr="true"><td colspan="2"><p>Dokumen penunjang yang disyaratkan untuk mengajukan tagihan pembayaran prestasi pekerjaan:</p><ol type="a"><li>Laporan Bulanan;</li><li>Sertifikat Bulanan;</li><li>Mempersiapkan Data Pendukung;<ol><li>Identifikasi aspek legal</li><li>Perhitungan kuantitas</li><li>Hasil uji mutu (Jika diperlukan)</li></ol></li><li>Berkas Tagihan;</li><li value="8">Dokumen lainnya yang diperlukan.</li></ol></td></tr><tr nobr="true"><td colspan="2"><b>BESARAN UANG MUKA</b><br>Kontrak ini diberikan uang muka sebesar 50% (lima puluh perseratus) dari Harga Pekerjaan.</td></tr><tr nobr="true"><td width="35%"><b>JENIS KONTRAK</b></td><td width="65%">'. $this->e(ProcurementContractTypeService::label($c)) .'</td></tr><tr nobr="true"><td colspan="2">'.ProcurementDocumentFormatService::signatureColumns([
            ['heading'=>'Untuk dan atas nama '.$name.'<br>PPK/Pengguna Anggaran','name'=>$this->value($c,'nama_ppk_resmi','nama_ppk'),'details'=>'NIP. '.$this->e($this->value($c,'nip_ppk_resmi','nip_ppk')).'<br>'.$this->e($c['pangkat_ppk']??'')],
            ['heading'=>'Untuk dan atas nama Penyedia<br>'.$provider,'name'=>$this->value($c,'direktur'),'details'=>$this->e($this->value($c,'jabatan_pelaksana'))]
        ]).'</td></tr></table>';
        $pdf->writeHTML($html,true,false,true,false,'');
        return $this->finish($pdf);
    }
    private function numberWords(int $n):string
    {
        $a=['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
        if($n<12)return $a[$n];if($n<20)return $this->numberWords($n-10).' belas';if($n<100)return $this->numberWords(intdiv($n,10)).' puluh'.($n%10?' '.$this->numberWords($n%10):'');if($n<200)return 'seratus'.($n%100?' '.$this->numberWords($n%100):'');if($n<1000)return $this->numberWords(intdiv($n,100)).' ratus'.($n%100?' '.$this->numberWords($n%100):'');if($n<2000)return 'seribu'.($n%1000?' '.$this->numberWords($n%1000):'');foreach([1000000000=>'miliar',1000000=>'juta',1000=>'ribu'] as $unit=>$label)if($n>=$unit)return $this->numberWords(intdiv($n,$unit)).' '.$label.($n%$unit?' '.$this->numberWords($n%$unit):'');return '';
    }

    private function contractIdentity(TCPDF $pdf,array $fields):void
    {
        $html='<table cellpadding="3">';
        foreach($fields as $label=>$value)$html.='<tr nobr="true"><td width="8%"></td><td width="27%">'.$this->e($label).'</td><td width="3%">:</td><td width="62%">'.nl2br($this->e($value),false).'</td></tr>';
        $pdf->writeHTML($html.'</table>',true,false,true,false,'');
    }
    private function contract(array $d,array $c,array $setup):string
    {
        $kind=strtoupper($c['jenis_pengadaan']??'');$construction=in_array($kind,['PEKERJAAN_KONSTRUKSI','KONSULTANSI_KONSTRUKSI'],true);
        $label=['PEKERJAAN_KONSTRUKSI'=>'Pekerjaan Konstruksi','KONSULTANSI_KONSTRUKSI'=>'Pekerjaan Jasa Konsultansi Konstruksi','KONSULTANSI_NON_KONSTRUKSI'=>'Pengadaan Jasa Konsultansi','KONSULTANSI_NONKONSTRUKSI'=>'Pengadaan Jasa Konsultansi','BARANG'=>'Pengadaan Barang','JASA_LAINNYA'=>'Pengadaan Jasa Lainnya'][$kind]??'Pengadaan Jasa Konsultansi';
        $role=$construction?'Pengguna Jasa':'Pejabat Penandatangan Kontrak';
        $pdf=$this->pdf($setup,'P');if($d['include_cover']??true)$this->cover($pdf,$c,$setup,'SURAT_PERJANJIAN');$this->addOfficialPage($pdf);
        $pdf->SetFont($this->font,'B',$this->baseSize+2);$pdf->MultiCell(0,6,"SURAT PERJANJIAN\nKontrak ".ProcurementContractTypeService::label($c),0,'C');$pdf->Ln(3);
        $pdf->SetFont($this->font,'B',$this->baseSize);$pdf->MultiCell(0,5,'Paket '.$label,0,'C');$pdf->MultiCell(0,5,strtoupper($this->value($c,'uraian_kontrak')),0,'C');
        $pdf->SetFont($this->font,'',$this->baseSize);$pdf->MultiCell(0,5,'Nomor : '.$this->value($d,'nomor_dokumen','nomor_kontrak'),0,'C');$pdf->Ln(4);
        $place=trim((string)($c['tempat_ttd']??$this->user['nama_wilayah']??''));
        if($place==='')$place=preg_replace('/^Pemerintah\s+(?:Kabupaten|Kota)\s+/iu','',trim((string)($this->user['nama_pemda']??'')));
        if($place==='')$place='........................';
        $date=$this->value($d,'tanggal_dokumen');$stamp=strtotime($date);$week=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $dateText=$stamp?'hari '.$week[(int)date('w',$stamp)].' tanggal '.$this->numberWords((int)date('j',$stamp)).' bulan '.explode(' ',$this->displayDate($date))[1].' tahun '.$this->numberWords((int)date('Y',$stamp)):'tanggal ........................';
        $this->writeJustified($pdf,'SURAT PERJANJIAN ini berikut semua lampirannya adalah '.($construction?'Kontrak Kerja Konstruksi ':'Kontrak ').ProcurementContractTypeService::label($c).', yang selanjutnya disebut “Kontrak”, dibuat dan ditandatangani di '.$place.' pada '.$dateText.', antara:');
        $this->contractIdentity($pdf,['Nama'=>$this->value($c,'nama_ppk_resmi','nama_ppk'),'NIP'=>$this->value($c,'nip_ppk_resmi','nip_ppk'),'Jabatan'=>'Pejabat Pembuat Komitmen','Berkedudukan di'=>$this->user['alamat_opd']??'-']);
        $this->writeJustified($pdf,'yang bertindak untuk dan atas nama '.($this->user['nama_opd']??$this->user['nama_org']??'-').' selanjutnya disebut “'.$role.'”, dengan:');
        $fields=['Nama'=>$this->value($c,'direktur'),'Jabatan'=>$this->value($c,'jabatan_pelaksana','jabatan'),'Berkedudukan di'=>$this->value($c,'alamat_penyedia','alamat')];
        if($construction)$fields+=['Akta Notaris Nomor'=>$this->value($c,'no_akta_pendirian'),'Tanggal'=>$this->displayDate($c['tgl_akta_pendirian']??''),'Notaris'=>$this->value($c,'nama_notaris_pendirian')];
        else $fields+=['Akta Pendirian Nomor'=>$this->value($c,'no_akta_pendirian'),'Tanggal'=>$this->displayDate($c['tgl_akta_pendirian']??'')];
        $fields['NPWP']=$this->value($c,'npwp');
        $this->contractIdentity($pdf,$fields);
        $this->writeJustified($pdf,'yang bertindak untuk dan atas nama '.$this->value($c,'nama_penyedia_resmi','nama_perusahaan','nama_penyedia').' selanjutnya disebut “Penyedia”.');
        $rows=$this->paragraphs($d,$c);
        if(!$rows){$rows=ProcurementReferenceTermsService::rows('SURAT_PERJANJIAN',$c);foreach($rows as &$row)$row['text']=$this->variables($row['text'],$d,$c);unset($row);}
        $this->writeParagraphs($pdf,$rows);$pdf->Ln(5);
        $pdf->writeHTML(ProcurementDocumentFormatService::signatureColumns([
            ['heading'=>'Untuk dan atas nama Penyedia','name'=>$this->value($c,'direktur','nama_penyedia_resmi','nama_penyedia'),'details'=>$this->e($this->value($c,'jabatan_pelaksana','jabatan'))],
            ['heading'=>'Untuk dan atas nama '.$role,'name'=>$this->value($c,'nama_ppk_resmi','nama_ppk'),'details'=>'NIP. '.$this->e($this->value($c,'nip_ppk_resmi','nip_ppk'))]
        ]),true,false,true,false,'');
        $this->footerPages($pdf,'surat perjanjian');return$this->finish($pdf);
    }

    /** The same formal typography is used for direct downloads and Generate Dokumen. */
    private function termsPage(array $setup,array $c,string $title):TCPDF
    {
        $mini=ProcurementMiniCompetitionTermsService::variant($c)==='MINI_KOMPETISI';
        $setup=array_replace($setup,['font'=>$mini?'times':ProcurementDocumentFormatService::BODY_FONT,'font_size'=>12]);
        if($mini)$setup=array_replace($setup,['margin_left'=>25,'margin_right'=>22,'margin_top'=>22,'margin_bottom'=>25]);
        $pdf=$this->pdf($setup,'P');$pdf->AddPage($this->orientation);
        $pdf->SetFont($this->font,'B',$mini?$this->baseSize:$this->baseSize+2);
        if($mini){
            if(str_contains($title,'UMUM')){
                $catalog=preg_match('/katalog|mini[ _-]?kompetisi|e[ _-]?(?:purchas|catalog)/iu',$c['metode_pemilihan']??'');
                $pdf->MultiCell(0,0,'II. LAMPIRAN KONTRAK ('.($catalog?'SURAT PESANAN':'SURAT PERJANJIAN').')',0,'L',false,1);$pdf->Ln(4);
                $pdf->MultiCell(0,0,$title.' (SSUK)',0,'C',false,1);
            }else{$pdf->MultiCell(0,0,'III. '.$title,0,'L',false,1);}
            $pdf->Ln(4);
        }else{
            $pdf->MultiCell(0,0,$title,0,'C',false,1);$pdf->Ln(3);
            $pdf->SetFont($this->font,'',$this->baseSize);
            $package=trim((string)($c['uraian_kontrak']??''));
            if($package!==''){$pdf->MultiCell(0,0,$package,0,'C',false,1);$pdf->Ln(4);}
        }
        return $pdf;
    }

    private function writeContractTerms(TCPDF $pdf,array $rows):void
    {
        $rows=ProcurementTermsLayoutService::rows($rows);
        $before=(float)$this->printSettings['paragraph_before']*25.4/72;
        $after=(float)$this->printSettings['paragraph_after']*25.4/72;
        foreach($rows as $i=>$row){
            $margin=$pdf->getMargins();$left=(float)$margin['left'];
            $bottom=$pdf->getPageHeight()-$pdf->getBreakMargin();
            $pdf->SetFont($this->font,$row['kind']==='heading'?'B':'',$this->baseSize);
            $line=$pdf->getCellHeight($this->baseSize*25.4/72);
            if($row['kind']==='heading'){
                $height=$pdf->getStringHeight($pdf->getPageWidth()-$left-$margin['right'],$row['text']);
                if($pdf->GetY()+$height+2*$line+max(2,$before)>$bottom)$pdf->AddPage($this->orientation);
                $pdf->Ln(max(2,$before));
                $align=($row['align']==='center')?'C':'L';
                $pdf->MultiCell(0,0,$row['text'],0,$align,false,1);$pdf->Ln(1.5);continue;
            }
            $pdf->Ln($before);
            if($pdf->GetY()+$line>$bottom)$pdf->AddPage($this->orientation);
            $x=$left+$row['indent'];$markerWidth=0;
            if($row['marker']!=='')$markerWidth=preg_match('/^\d/',$row['marker'])?12:max(8,$pdf->GetStringWidth($row['marker'])+2.5);
            $width=$pdf->getPageWidth()-$margin['right']-$x-$markerWidth;
            $height=$pdf->getStringHeight($width,rtrim($row['text'])."\n");
            $needed=$height<=60?$height:3*$line;
            if($pdf->GetY()+$needed>$bottom)$pdf->AddPage($this->orientation);
            $y=$pdf->GetY();
            if($markerWidth){$pdf->SetXY($x,$y);$pdf->Cell($markerWidth,0,$row['marker'],0,0,'L');}
            $pdf->MultiCell($width,0,rtrim($row['text'])."\n",0,'J',false,1,$x+$markerWidth,$y,true,0,false,true);
            $pdf->SetX($left);$pdf->Ln($after);
        }
    }

    private function writeMiniContractTerms(TCPDF $pdf,array $rows):void
    {
        $caption=null;$labelWidth=51.0;
        $after=(float)$this->printSettings['paragraph_after']*25.4/72;
        foreach(ProcurementTermsLayoutService::rows($rows) as $row){
            $margins=$pdf->getMargins();$left=(float)$margins['left'];
            $right=$pdf->getPageWidth()-(float)$margins['right'];
            $bottom=$pdf->getPageHeight()-$pdf->getBreakMargin();
            $pdf->SetFont($this->font,'',$this->baseSize);
            $line=$pdf->getCellHeight($this->baseSize*25.4/72);
            if($row['kind']==='heading'){
                if(preg_match('/^(\d+)\.\s+(.+)$/su',$row['text'],$m)){$caption=['number'=>$m[1].'.','text'=>$m[2]];continue;}
                if($pdf->GetY()+4*$line>$bottom)$pdf->AddPage($this->orientation);
                $pdf->Ln(2);$pdf->SetFont($this->font,'B',$this->baseSize);
                $pdf->MultiCell(0,0,$row['text'],0,'L',false,1);$pdf->Ln(3);continue;
            }
            $x=$left+$labelWidth+$row['indent'];
            $marker=$row['marker'];$markerWidth=$marker!==''?12.5:0;
            $width=$right-$x-$markerWidth;
            $height=$pdf->getStringHeight($width,$row['text']);
            $captionHeight=$caption?$pdf->getStringHeight($labelWidth-8,$caption['text']):0;
            $needed=$caption?max($captionHeight,min($height,3*$line)):min($height,2*$line);
            if($pdf->GetY()+$needed>$bottom)$pdf->AddPage($this->orientation);
            $startPage=$pdf->getPage();$y=$pdf->GetY();
            if($caption){
                $pdf->SetXY($left,$y);$pdf->Cell(7,0,$caption['number'],0,0,'L');
                $pdf->MultiCell($labelWidth-8,0,$caption['text'],0,'L',false,1,$left+7,$y);
            }
            if($marker!==''){$pdf->SetXY($x,$y);$pdf->Cell($markerWidth,0,$marker,0,0,'L');}
            $pdf->MultiCell($width,0,rtrim($row['text'])."\n",0,'J',false,1,$x+$markerWidth,$y,true,0,false,true);
            if($caption&&$pdf->getPage()===$startPage)$pdf->SetY(max($pdf->GetY(),$y+$captionHeight));
            $pdf->SetX($left);$pdf->Ln(max(1.5,$after));$caption=null;
        }
    }

    private function sskk(array $d,array $c,array $setup):string
    {
        $pdf=$this->termsPage($setup,$c,'SYARAT-SYARAT KHUSUS KONTRAK');
        $rows=$this->paragraphs($d,$c);
        if(empty($d['id'])||!$rows)$rows=ProcurementReferenceTermsService::rows('SSKK',$c);
        $data=$d['data_isian']??[];if(is_string($data))$data=json_decode($data,true)?:[];
        $context=array_replace($c,array_intersect_key($data,array_flip(['masa_pemeliharaan','masa_pemeliharaan_bulan'])));
        $rows=ProcurementReferenceTermsService::fillSskkRows($rows,$context);
        $mini=ProcurementMiniCompetitionTermsService::variant($c)==='MINI_KOMPETISI';
        $widths=$mini?[12.5,20.8,66.7]:[15,25,60];
        $tableOpen='<table border="1" bordercolor="#999999" cellpadding="5" cellspacing="0" width="100%">';
        $header=$tableOpen.'<tr nobr="true" bgcolor="'.($mini?'#ffffff':'#eeeeee').'" style="font-weight:bold">';
        foreach(['Pasal dalam SSUK','Ketentuan','Data'] as $i=>$label)$header.='<th width="'.$widths[$i].'%" align="center">'.$label.'</th>';
        $header.='</tr></table>';
        $pdf->SetFont($this->font,'',$this->baseSize);
        $margins=$pdf->getMargins();$tableWidth=$pdf->getPageWidth()-$margins['left']-$margins['right'];
        $lineHeight=$pdf->getCellHeight($this->baseSize*25.4/72);
        $headerHeight=3*$lineHeight+6;
        $maxRowHeight=$pdf->getPageHeight()-$margins['top']-$pdf->getBreakMargin()-$headerHeight-10;
        $maxLines=max(1,(int)floor(($maxRowHeight-6)/$lineHeight));
        $dataWidth=$tableWidth*$widths[2]/100-5;
        $writeHeader=function()use($pdf,$header):void{
            $pdf->SetFont($this->font,'',$this->baseSize);
            $pdf->writeHTML($header,false,false,true,false,'');
        };
        $writeHeader();
        foreach($rows as $row){
            $text=$this->variables((string)($row['text']??''),$d,$c);
            $parts=array_map('trim',explode('|',$text,3));
            $lines=preg_split('/\R+/u',$parts[2]??$parts[0]??'')?:[];
            $pdf->SetFont($this->font,'',$this->baseSize);
            $wrapped=[];
            foreach($lines as $line){
                $buffer='';
                foreach(preg_split('/\s+/u',trim($line))?:[] as $word){
                    $candidate=$buffer===''?$word:$buffer.' '.$word;
                    if($buffer!==''&&$pdf->GetStringWidth($candidate)>$dataWidth){$wrapped[]=$buffer;$buffer=$word;}else $buffer=$candidate;
                }
                $wrapped[]=$buffer;
            }
            // Each row is a separate closed table. We page-break before writing it,
            // avoiding TCPDF's rollback stretching borders of the preceding row.
            foreach(array_chunk($wrapped,$maxLines) as $chunk){
                $pdf->SetFont($this->font,'B',$this->baseSize);
                $height=max($pdf->getStringHeight($tableWidth*$widths[0]/100-5,$parts[0]??''),$pdf->getStringHeight($tableWidth*$widths[1]/100-5,$parts[1]??''),count($chunk)*$lineHeight)+6;
                if($pdf->GetY()+$height>$pdf->getPageHeight()-$pdf->getBreakMargin()){$pdf->AddPage($this->orientation);$writeHeader();}
                $body=implode('<br>',array_map(fn($line)=>$this->e($line),$chunk));
                $html=$tableOpen.'<tr nobr="true"><td width="'.$widths[0].'%" align="left"><b>'.$this->e(preg_replace('/\s+/u',' ',$parts[0]??'')).'</b></td><td width="'.$widths[1].'%" align="left"><b>'.$this->e(preg_replace('/\s+/u',' ',$parts[1]??'')).'</b></td><td width="'.$widths[2].'%" align="left">'.$body.'</td></tr></table>';
                $pdf->SetFont($this->font,'',$this->baseSize);
                $pdf->writeHTML($html,false,false,true,false,'');
            }
        }
        $this->footerPages($pdf,'syarat-syarat khusus kontrak');return $this->finish($pdf);
    }

    private function ssuk(array $d,array $c,array $setup):string
    {
        $d=ProcurementMiniCompetitionTermsService::ssukDocument($d,$c);
        $form=ProcurementContractFormService::recommend($c);
        $pdf=$this->termsPage($setup,$c,$form==='SPK'?"SYARAT UMUM\nSURAT PERINTAH KERJA (SPK)":'SYARAT-SYARAT UMUM KONTRAK');
        $rows=$this->paragraphs($d,$c);
        if(empty($d['id'])||!$rows)$rows=ProcurementReferenceTermsService::rows('SSUK',$c);
        if(ProcurementMiniCompetitionTermsService::variant($c)==='MINI_KOMPETISI')$this->writeMiniContractTerms($pdf,$rows);else $this->writeContractTerms($pdf,$rows);
        $this->footerPages($pdf,$form==='SPK'?'syarat umum spk':'syarat-syarat umum kontrak');return $this->finish($pdf);
    }
    private function officialLetter(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,($d['orientasi']??'P'));if(($d['kode_dokumen']??'')==='SURAT_PESANAN'&&($d['include_cover']??true))$this->cover($pdf,$c,$setup,'SURAT_PESANAN');$this->addOfficialPage($pdf);$title=strtoupper((string)($d['judul']??$d['nama_master']??'DOKUMEN PENGADAAN'));$pdf->SetFont($this->font,'B',$this->baseSize+4);$pdf->MultiCell(0,7,$title,0,'C');$pdf->SetFont($this->font,'',$this->baseSize);$pdf->MultiCell(0,5,'Nomor : '.($d['nomor_dokumen']??'-'),0,'C');$pdf->Ln(4);$info='<table cellpadding="3"><tr><td width="25%">Nama Paket</td><td width="3%">:</td><td width="72%">'.$this->e($this->value($c,'uraian_kontrak')).'</td></tr><tr><td>Nilai</td><td>:</td><td>Rp'.$this->money($c['nilai_kontrak']??0).'</td></tr><tr><td>Sumber Dana</td><td>:</td><td>'.$this->e($this->value($c,'sumber_dana')).'</td></tr><tr><td>Penyedia</td><td>:</td><td>'.$this->e($this->value($c,'nama_penyedia_resmi','nama_penyedia')).'</td></tr></table>';$pdf->writeHTML($info,true,false,true,false,'');$rows=$this->paragraphs($d,$c);if(!$rows)$rows=[['text'=>'Pada tanggal '.$this->value($d,'tanggal_dokumen').' telah disusun '.$title.' untuk paket tersebut di atas.'],['text'=>'Dokumen ini dibuat berdasarkan hasil pemeriksaan, klarifikasi, evaluasi, atau kesepakatan para pihak sesuai tahapan pengadaan yang berlaku.'],['text'=>'Demikian dokumen ini dibuat untuk dipergunakan sebagaimana mestinya.']];$this->writeParagraphs($pdf,$rows);$this->footerPages($pdf,$title);return$this->finish($pdf);
    }
}
