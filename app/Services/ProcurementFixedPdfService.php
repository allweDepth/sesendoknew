<?php
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/OfficialLetterheadPdfService.php';
require_once __DIR__.'/ProcurementZipPdfService.php';
require_once __DIR__.'/ProcurementReferenceTermsService.php';
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
        $this->user=array_merge($this->user,array_intersect_key($contract,array_flip(['nama_pejabat_pengadaan','nip_pejabat_pengadaan','pangkat_pejabat_pengadaan'])));
        $code=strtoupper((string)($document['kode_dokumen']??''));
        $contract['print_settings']=ContractPrintSettingsService::validate($contract['print_settings']??ContractPrintSettingsService::get((int)($contract['id']??0)));
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

    public function cover(TCPDF $pdf,array $c,array $setup):void
    {
        ProcurementDocumentFormatService::registerFonts($pdf);PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);$pdf->AddPage(PageSetupService::orientation($setup,'P'));
        $coverFont=$setup['font']??ProcurementDocumentFormatService::BODY_FONT;
        $m=$pdf->getMargins();$x=$m['left'];$y=$m['top'];$w=$pdf->getPageWidth()-$x-$m['right'];$bottom=$pdf->getPageHeight()-$m['bottom']-3;$h=$bottom-$y;$layoutScale=min(1.0,$h/270,$w/174);$fontScale=(float)($setup['font_size']??12)/12;
        // Measure the native letterhead so its background ends at the lower rule.
        $letterheadUser=array_merge($this->user,['_letterhead_font'=>$coverFont]);
        $pdf->SetMargins($x+4,$m['top'],$m['right']+4);$pdf->SetXY($x+4,$y+3);
        $pdf->startTransaction();OfficialLetterheadPdfService::draw($pdf,$letterheadUser);$headerBottom=$pdf->GetY()-4;$pdf->rollbackTransaction(true);
        $pdf->SetFillColor(183,211,159);$pdf->Rect($x,$y,$w,$headerBottom-$y,'F');
        $pdf->SetXY($x+4,$y+3);OfficialLetterheadPdfService::draw($pdf,$letterheadUser);$pdf->SetMargins($x,$m['top'],$m['right']);
        $pdf->SetTextColor(0,0,0);$pdf->SetFont($coverFont,'B',23*$fontScale);$pdf->SetXY($x+3,$y+44*$layoutScale);$pdf->MultiCell($w-6,10,'SURAT PERINTAH KERJA (SPK)',0,'C');
        $pdf->SetFont($coverFont,'B',11*$fontScale);$pdf->MultiCell($w-6,6,'SPK '.ProcurementContractTypeService::label($c),0,'C');
        $pdf->SetFillColor(255,211,0);$pdf->Rect($x,$y+72*$layoutScale,$w,10*$layoutScale,'F');$pdf->SetXY($x,$y+74*$layoutScale);$pdf->SetFont($coverFont,'B',12*$fontScale);$pdf->MultiCell($w,6,str_contains($c['jenis_pengadaan']??'','KONSULTANSI')?'Paket Jasa Konsultansi':'Paket Pengadaan',0,'C');
        $pdf->SetFillColor(255,224,126);$pdf->Rect($x,$y+82*$layoutScale,$w,26*$layoutScale,'F');$pdf->SetXY($x+6,$y+88*$layoutScale);$pdf->SetFont($coverFont,'B',13*$fontScale);$pdf->MultiCell($w-12,6,$this->value($c,'uraian_kontrak'),0,'C');
        $pdf->SetXY($x+12,$y+120*$layoutScale);$pdf->SetFont($coverFont,'B',12*$fontScale);
        $fields=[['Nomor',$this->value($c,'nomor_kontrak')],['Tanggal',$this->displayDate($c['tanggal_kontrak']??'')],['Sumber Dana',$c['sumber_dana']??'APBD'],['Uraian Kontrak',$this->value($c,'uraian_kontrak')],['Nilai Kontrak',isset($c['nilai_kontrak'])?'Rp'.$this->money($c['nilai_kontrak']):'Belum ditetapkan'],['Waktu Pelaksanaan',isset($c['waktu_pelaksanaan'])?$c['waktu_pelaksanaan']." hari kalender\n".$this->displayDate($c['tanggal_mulai']??'').' s.d. '.$this->displayDate($c['tanggal_selesai']??''):'Belum ditetapkan']];
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
        $setup=array_merge($setup,$contract['print_settings']);
        if(ProcurementDocumentFormatService::supports('SPK_KONSULTANSI_KONSTRUKSI',$contract))return $this->spk(['kode_dokumen'=>'SPK_KONSULTANSI_KONSTRUKSI','nomor_dokumen'=>$contract['nomor_kontrak'],'tanggal_dokumen'=>$contract['tanggal_kontrak']],$contract,ProcurementDocumentFormatService::setup($setup,$contract['print_settings']??[]));
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
    private function paragraphs(array $document,array $contract,bool $jsonOnly=false):array
    {
        $code=strtoupper((string)($document['kode_dokumen']??''));
        if(empty($document['id'])&&in_array($code,['SURAT_PERJANJIAN','SSKK','SSUK'],true)){$reference=ProcurementReferenceTermsService::rows($code,$contract);foreach($reference as &$row)$row['text']=$this->variables((string)($row['text']??''),$document,$contract);unset($row);if($reference)return$reference;}$out=[];foreach($document['sections']??[] as $section){$raw=$section['isi']??$section['isi_template']??'';$decoded=is_string($raw)?json_decode($raw,true):$raw;if(is_array($decoded)){foreach($decoded as $row){if(is_string($row))$row=['text'=>$row];if(!is_array($row)||trim((string)($row['text']??''))==='')continue;$row['text']=$this->variables((string)$row['text'],$document,$contract);$out[]=$row;}continue;}if($jsonOnly)continue;$text=preg_replace('/\s+/u',' ',html_entity_decode(strip_tags(str_replace(['</p>','</li>','<br>','<br/>','<br />'],"\n",(string)$raw)),ENT_QUOTES|ENT_HTML5,'UTF-8'));foreach(preg_split('/\n+/',$text)?:[] as $line)if(trim($line)!=='')$out[]=['type'=>'paragraph','align'=>'justify','text'=>$this->variables(trim($line),$document,$contract)];}return$out;
    }
    private function variables(string $text,array $d,array $c):string
    {
        $map=['nama_opd'=>$this->user['nama_opd']??$this->user['nama_org']??$c['kd_opd']??'-','nama_dokumen'=>$d['judul']??'-','nomor_dokumen'=>$d['nomor_dokumen']??'-','tanggal_dokumen'=>$d['tanggal_dokumen']??'-','nama_paket'=>$c['uraian_kontrak']??'-','kd_sub_keg'=>$c['kd_sub_keg']??'-','sumber_dana'=>$c['sumber_dana']??'APBD','nilai_kontrak'=>$this->money($c['nilai_kontrak']??0),'nama_ppk'=>$c['nama_ppk_resmi']??$c['nama_ppk']??'-','nip_ppk'=>$c['nip_ppk_resmi']??$c['nip_ppk']??'-','nama_penyedia'=>$c['nama_penyedia_resmi']??$c['nama_penyedia']??'-'];foreach($map as $key=>$value)$text=str_replace('{{'.$key.'}}',(string)$value,$text);return$text;
    }
    private function writeParagraphs(TCPDF $pdf,array $rows):void
    {
        $sequence=[];
        foreach($rows as $row){
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
            if($format==='clause-heading'){$pdf->Ln(1.5);$pdf->SetFont($this->font,'B',$this->baseSize);$pdf->MultiCell(0,5.2,$text,0,'L',false,1);continue;}
            $pdf->SetFont($this->font,'',$this->baseSize);
            if($format==='hanging-list'&&preg_match('/^(\([a-z0-9]+\)|\d+\.)\s+(.+)$/isu',$text,$m)){
                $margin=$pdf->getMargins();$x=$margin['left'];$y=$pdf->GetY();$pdf->SetXY($x,$y);$pdf->Cell(8,5.0,$m[1],0,0,'R');$pdf->SetXY($x+10,$y);$pdf->MultiCell($pdf->getPageWidth()-$margin['right']-($x+10),5.0,rtrim($m[2])."\n",0,'J',false,1);continue;
            }
            if($format==='hanging-list'&&preg_match('/^((?:[a-z]\.|\d+\)|[ivx]+\.))\s+(.+)$/isu',$text,$m)){
                $margin=$pdf->getMargins();$x=$margin['left'];$y=$pdf->GetY();$pdf->SetXY($x,$y);$pdf->Cell(8,5.0,$m[1],0,0,'R');$pdf->SetXY($x+10,$y);$pdf->MultiCell($pdf->getPageWidth()-$margin['right']-($x+10),5.0,rtrim($m[2])."\n",0,'J',false,1);continue;
            }
            $pdf->MultiCell(0,5.0,rtrim($text)."\n",0,'J',false,1,'','',true,0,false,true);$pdf->Ln(.8);
        }
    }
    private function footerPages(TCPDF $pdf,string $label):void
    {
        if(!$this->customFooter)return;
        $pages=$pdf->getNumPages();
        $pdf->SetAutoPageBreak(false,0);
        for($page=1;$page<=$pages;$page++){
            $pdf->setPage($page);
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
        if($d['include_cover']??true)$this->cover($pdf,$c,$setup);
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

    private function contract(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,'P');$this->addOfficialPage($pdf);$pdf->SetFont($this->font,'B',$this->baseSize+3);$pdf->MultiCell(0,6,"SURAT PERJANJIAN\nKontrak ".ProcurementContractTypeService::label($c)."\n\nPaket ".$this->value($c,'jenis_pengadaan')."\n".strtoupper($this->value($c,'uraian_kontrak')),0,'C');$pdf->SetFont($this->font,'',$this->baseSize);$pdf->MultiCell(0,5,'Nomor : '.$this->value($d,'nomor_dokumen','nomor_kontrak'),0,'C');$pdf->Ln(4);
        $intro='SURAT PERJANJIAN ini berikut semua lampirannya adalah kontrak yang selanjutnya disebut "Kontrak", dibuat dan ditandatangani di '.$this->value($this->user,'nama_wilayah').' pada tanggal '.$this->value($d,'tanggal_dokumen').', antara:';$this->writeJustified($pdf,$intro,5);$party='<table cellpadding="3"><tr><td width="8%"></td><td width="24%">Nama<br>NIP<br>Jabatan<br>Berkedudukan di</td><td width="4%">:<br>:<br>:<br>:</td><td width="64%">'.$this->e($this->value($c,'nama_ppk_resmi','nama_ppk')).'<br>'.$this->e($this->value($c,'nip_ppk_resmi','nip_ppk')).'<br>Pejabat Pembuat Komitmen<br>'.$this->e($this->user['alamat_opd']??'-').'</td></tr></table><p>yang bertindak untuk dan atas nama '.$this->e($this->user['nama_opd']??$this->user['nama_org']??'-').' selanjutnya disebut "Pengguna Jasa", dengan:</p><table cellpadding="3"><tr><td width="8%"></td><td width="24%">Nama<br>Jabatan<br>Berkedudukan di<br>NPWP</td><td width="4%">:<br>:<br>:<br>:</td><td width="64%">'.$this->e($this->value($c,'direktur')).'<br>Direktur<br>'.$this->e($this->value($c,'alamat_penyedia','alamat')).'<br>'.$this->e($this->value($c,'npwp')).'</td></tr></table><p>yang bertindak untuk dan atas nama '.$this->e($this->value($c,'nama_penyedia_resmi','nama_perusahaan','nama_penyedia')).' selanjutnya disebut "Penyedia".</p>';$pdf->writeHTML($party,true,false,true,false,'');
        $defaults=[['type'=>'numbered','text'=>'Undang-Undang Nomor 2 Tahun 2017 tentang Jasa Konstruksi beserta perubahannya;'],['type'=>'numbered','text'=>'Kitab Undang-Undang Hukum Perdata, khususnya ketentuan mengenai perikatan;'],['type'=>'numbered','text'=>'Peraturan Presiden Nomor 16 Tahun 2018 beserta perubahannya tentang Pengadaan Barang/Jasa Pemerintah;'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'PARA PIHAK MENERANGKAN TERLEBIH DAHULU BAHWA:'],['type'=>'alpha','text'=>'telah dilakukan proses pemilihan Penyedia sesuai Dokumen Pemilihan;'],['type'=>'alpha','text'=>'Pengguna Jasa telah menunjuk Penyedia melalui SPPBJ untuk melaksanakan pekerjaan;'],['type'=>'alpha','text'=>'Penyedia memiliki keahlian, tenaga, dan sumber daya untuk menyelesaikan pekerjaan;'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 1 - ISTILAH DAN UNGKAPAN'],['type'=>'paragraph','text'=>'Istilah dan ungkapan dalam Surat Perjanjian ini memiliki arti yang sama seperti dalam lampiran Kontrak.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 2 - RUANG LINGKUP PEKERJAAN'],['type'=>'paragraph','text'=>'Ruang lingkup pekerjaan adalah '.$this->value($c,'uraian_kontrak').'.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 3 - HARGA KONTRAK, SUMBER PEMBIAYAAN DAN PEMBAYARAN'],['type'=>'numbered','text'=>'Nilai Kontrak termasuk pajak adalah Rp'.$this->money($c['nilai_kontrak']??0).'.'],['type'=>'numbered','text'=>'Kontrak ini dibiayai dari '.$this->value($c,'sumber_dana').'.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 4 - DOKUMEN KONTRAK'],['type'=>'paragraph','text'=>'Surat Perjanjian, penawaran, daftar kuantitas dan harga, SSKK, SSUK, spesifikasi/KAK, gambar, jadwal, jaminan, berita acara, dan adendum merupakan satu kesatuan Kontrak.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 5 - MASA KONTRAK'],['type'=>'paragraph','text'=>'Masa pelaksanaan pekerjaan adalah '.$this->value($c,'waktu_pelaksanaan').' hari kalender sesuai SPMK.']];$rows=$this->paragraphs($d,$c);$this->writeParagraphs($pdf,$rows?:$defaults);$pdf->Ln(5);$pdf->writeHTML(ProcurementDocumentFormatService::signatureColumns([
            ['heading'=>'Untuk dan atas nama Penyedia','name'=>$this->value($c,'direktur','nama_penyedia_resmi','nama_penyedia')],
            ['heading'=>'Pejabat Pembuat Komitmen','name'=>$this->value($c,'nama_ppk_resmi','nama_ppk'),'details'=>'NIP. '.$this->e($this->value($c,'nip_ppk_resmi','nip_ppk'))]
        ]),true,false,true,false,'');$this->footerPages($pdf,'surat perjanjian');return$this->finish($pdf);
    }

    private function sskk(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,'P');$pdf->AddPage($this->orientation);$pdf->SetFont($this->font,'B',$this->baseSize+5);$pdf->MultiCell(0,8,'SYARAT-SYARAT KHUSUS KONTRAK',0,'C');$rows=$this->paragraphs($d,$c);if(empty($d['id']))$rows=ProcurementReferenceTermsService::rows('SSKK',$c);if(!$rows)$rows=ProcurementReferenceTermsService::rows('SSKK',$c);foreach($rows as &$row)$row['text']=$this->variables((string)($row['text']??''),$d,$c);unset($row);$html='<table cellpadding="3"><thead><tr style="font-weight:bold"><th width="13%">Pasal dalam SSUK</th><th width="22%">Ketentuan</th><th width="65%">Data</th></tr></thead><tbody>';foreach($rows as $row){$parts=array_map('trim',explode('|',(string)$row['text'],3));$html.='<tr><td width="13%"><b>'.nl2br($this->e($parts[0]??''),false).'</b></td><td width="22%"><b>'.nl2br($this->e($parts[1]??''),false).'</b></td><td width="65%">'.nl2br($this->e($parts[2]??$parts[0]??''),false).'</td></tr>';}$html.='</tbody></table>';$pdf->SetFont($this->font,'',max(6,$this->baseSize-0.5));$pdf->writeHTML($html,true,false,true,false,'');$this->footerPages($pdf,'syarat-syarat khusus kontrak');return$this->finish($pdf);
    }
    private function ssuk(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,'P');$pdf->AddPage($this->orientation);$pdf->SetFont($this->font,'B',$this->baseSize+5);$pdf->MultiCell(0,7,"SYARAT UMUM\nSURAT PERINTAH KERJA (SPK)",0,'C');$rows=$this->paragraphs($d,$c);if(empty($d['id']))$rows=ProcurementReferenceTermsService::rows('SSUK',$c);if(!$rows)$rows=ProcurementReferenceTermsService::rows('SSUK',$c);$this->writeTermsParagraphs($pdf,$rows);$this->footerPages($pdf,'syarat umum spk');return$this->finish($pdf);
    }
    private function officialLetter(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,($d['orientasi']??'P'));$this->addOfficialPage($pdf);$title=strtoupper((string)($d['judul']??$d['nama_master']??'DOKUMEN PENGADAAN'));$pdf->SetFont($this->font,'B',$this->baseSize+4);$pdf->MultiCell(0,7,$title,0,'C');$pdf->SetFont($this->font,'',$this->baseSize);$pdf->MultiCell(0,5,'Nomor : '.($d['nomor_dokumen']??'-'),0,'C');$pdf->Ln(4);$info='<table cellpadding="3"><tr><td width="25%">Nama Paket</td><td width="3%">:</td><td width="72%">'.$this->e($this->value($c,'uraian_kontrak')).'</td></tr><tr><td>Nilai</td><td>:</td><td>Rp'.$this->money($c['nilai_kontrak']??0).'</td></tr><tr><td>Sumber Dana</td><td>:</td><td>'.$this->e($this->value($c,'sumber_dana')).'</td></tr><tr><td>Penyedia</td><td>:</td><td>'.$this->e($this->value($c,'nama_penyedia_resmi','nama_penyedia')).'</td></tr></table>';$pdf->writeHTML($info,true,false,true,false,'');$rows=$this->paragraphs($d,$c);if(!$rows)$rows=[['text'=>'Pada tanggal '.$this->value($d,'tanggal_dokumen').' telah disusun '.$title.' untuk paket tersebut di atas.'],['text'=>'Dokumen ini dibuat berdasarkan hasil pemeriksaan, klarifikasi, evaluasi, atau kesepakatan para pihak sesuai tahapan pengadaan yang berlaku.'],['text'=>'Demikian dokumen ini dibuat untuk dipergunakan sebagaimana mestinya.']];$this->writeParagraphs($pdf,$rows);$this->footerPages($pdf,$title);return$this->finish($pdf);
    }
}
