<?php
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/OfficialLetterheadPdfService.php';
require_once __DIR__.'/ProcurementZipPdfService.php';

/** Renderer tata letak dokumen pengadaan. Pengguna tidak pernah mengedit HTML. */
final class ProcurementFixedPdfService
{
    private array $user;
    private string $font='helvetica';
    private float $baseSize=9.0;
    private bool $customFooter=true;
    public function __construct(array $user){$this->user=$user;}

    public function render(array $document,array $contract,array $setup):string
    {
        $code=strtoupper((string)($document['kode_dokumen']??''));
        if(str_starts_with($code,'SPK_'))return $this->spk($document,$contract,$setup);
        if($code==='SURAT_PERJANJIAN')return $this->contract($document,$contract,$setup);
        if($code==='SSKK')return $this->sskk($document,$contract,$setup);
        if($code==='SSUK')return $this->ssuk($document,$contract,$setup);
        $zipRenderer=new ProcurementZipPdfService($this->user);
        if($zipRenderer->supports($code))return $zipRenderer->render($code,$document,$contract,$setup);
        return $this->officialLetter($document,$contract,$setup);
    }

    public function directContract(array $contract,array $setup):string
    {
        return $this->contract(['kode_dokumen'=>'SURAT_PERJANJIAN','judul'=>'Surat Perjanjian','nomor_dokumen'=>$contract['nomor_kontrak']??'','tanggal_dokumen'=>$contract['tanggal_kontrak']??date('Y-m-d'),'sections'=>[],'attachments'=>[]],$contract,$setup);
    }

    private function pdf(array $setup,string $orientation='P'):TCPDF
    {
        $this->font=(string)($setup['font']??'helvetica');$this->baseSize=max(6,min(18,(float)($setup['font_size']??9)));$this->customFooter=false;$pdf=PageSetupService::createPdf($setup,$orientation);PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);$pdf->SetFont($this->font,'',$this->baseSize);return$pdf;
    }
    private function addOfficialPage(TCPDF $pdf):void{$pdf->AddPage();OfficialLetterheadPdfService::draw($pdf,$this->user);}
    private function e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private function value(array $c,string ...$keys):string{foreach($keys as $key)if(trim((string)($c[$key]??''))!=='')return(string)$c[$key];return'-';}
    private function money($value):string{return number_format((float)$value,2,',','.');}
    private function paragraphs(array $document,array $contract,bool $jsonOnly=false):array
    {
        $out=[];foreach($document['sections']??[] as $section){$raw=$section['isi']??$section['isi_template']??'';$decoded=is_string($raw)?json_decode($raw,true):$raw;if(is_array($decoded)){foreach($decoded as $row){if(is_string($row))$row=['text'=>$row];if(!is_array($row)||trim((string)($row['text']??''))==='')continue;$row['text']=$this->variables((string)$row['text'],$document,$contract);$out[]=$row;}continue;}if($jsonOnly)continue;$text=preg_replace('/\s+/u',' ',html_entity_decode(strip_tags(str_replace(['</p>','</li>','<br>','<br/>','<br />'],"\n",(string)$raw)),ENT_QUOTES|ENT_HTML5,'UTF-8'));foreach(preg_split('/\n+/',$text)?:[] as $line)if(trim($line)!=='')$out[]=['type'=>'paragraph','align'=>'justify','text'=>$this->variables(trim($line),$document,$contract)];}return$out;
    }
    private function variables(string $text,array $d,array $c):string
    {
        $map=['nama_opd'=>$this->user['nama_opd']??$this->user['nama_org']??$c['kd_opd']??'-','nama_dokumen'=>$d['judul']??'-','nomor_dokumen'=>$d['nomor_dokumen']??'-','tanggal_dokumen'=>$d['tanggal_dokumen']??'-','nama_paket'=>$c['uraian_kontrak']??'-','kd_sub_keg'=>$c['kd_sub_keg']??'-','sumber_dana'=>$c['sumber_dana']??'APBD','nilai_kontrak'=>$this->money($c['nilai_kontrak']??0),'nama_ppk'=>$c['nama_ppk_resmi']??$c['nama_ppk']??'-','nip_ppk'=>$c['nip_ppk_resmi']??$c['nip_ppk']??'-','nama_penyedia'=>$c['nama_penyedia_resmi']??$c['nama_penyedia']??'-'];foreach($map as $key=>$value)$text=str_replace('{{'.$key.'}}',(string)$value,$text);return$text;
    }
    private function writeParagraphs(TCPDF $pdf,array $rows):void
    {
        $sequence=[];foreach($rows as $row){$type=$row['type']??'paragraph';$style=is_array($row['style']??null)?$row['style']:[];$font=(in_array('bold',$style,true)?'B':'').(in_array('italic',$style,true)?'I':'').(in_array('underline',$style,true)?'U':'');$pdf->SetFont($this->font,$font,$this->baseSize);$align=['left'=>'L','center'=>'C','right'=>'R','justify'=>'J'][$row['align']??'justify']??'J';$prefix='';if($type==='list')$prefix='- ';elseif(in_array($type,['numbered','alpha'],true)){$sequence[$type]=($sequence[$type]??0)+1;$prefix=$type==='alpha'?chr(96+$sequence[$type]).'. ':$sequence[$type].'. ';}$text=$prefix.(string)$row['text'].($align==='J'?"\n":'');$pdf->MultiCell(0,5.2,$text,0,$align,false,1,'','',true,0,false,true);$pdf->Ln(1.2);}
    }
    private function writeJustified(TCPDF $pdf,string $text,float $height=5.0):void
    {
        // TCPDF needs a trailing line break so the last visual line is not stretched.
        $pdf->MultiCell(0,$height,rtrim($text)."\n",0,'J',false,1,'','',true,0,false,true);
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
        $pdf=$this->pdf($setup,'P');$this->addOfficialPage($pdf);$pdf->SetFont($this->font,'B',$this->baseSize+5);$pdf->MultiCell(0,7,'SURAT PERINTAH KERJA (SPK)',0,'C');$pdf->SetFont($this->font,'B',$this->baseSize+1);$pdf->MultiCell(0,6,$this->value($c,'jenis_pengadaan')."\n".strtoupper($this->value($c,'uraian_kontrak')),0,'C');$pdf->Ln(3);$pdf->SetFont($this->font,'',$this->baseSize);$pdf->MultiCell(0,5,'Nomor : '.$this->value($d,'nomor_dokumen'),0,'C');
        $pdf->AddPage();$package=$this->e($this->value($c,'uraian_kontrak'));$html='<table border="1" cellpadding="5"><tr><td width="35%" rowspan="2" align="center"><br><br><b>SURAT PERINTAH KERJA (SPK)</b></td><td width="65%"><b>SATUAN KERJA</b><br><b>'.$this->e($this->user['nama_opd']??$this->user['nama_org']??$c['kd_opd']??'-').'</b></td></tr><tr><td><b>NOMOR DAN TANGGAL SPK</b><br>Nomor &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$this->e($d['nomor_dokumen']??$c['nomor_kontrak']??'-').'<br>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;: '.$this->e($d['tanggal_dokumen']??$c['tanggal_kontrak']??'-').'</td></tr>';
        $html.='<tr><td align="center"><br><br><b>NAMA PENGGUNA JASA</b><br><br></td><td>Nama &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$this->e($this->value($c,'nama_ppk_resmi','nama_ppk')).'<br>NIP &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$this->e($this->value($c,'nip_ppk_resmi','nip_ppk')).'<br>Jabatan &nbsp;&nbsp;: Pejabat Pembuat Komitmen<br>Berkedudukan di : '.$this->e($this->user['alamat_opd']??'-').'<br><br>yang bertindak untuk dan atas nama satuan kerja, selanjutnya disebut "Pengguna Jasa".</td></tr>';
        $html.='<tr><td align="center"><br><br><b>NAMA PENYEDIA</b><br><br></td><td>Nama &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$this->e($this->value($c,'direktur','nama_penyedia_resmi','nama_penyedia')).'<br>Jabatan &nbsp;&nbsp;: Direktur<br>Berkedudukan di : '.$this->e($this->value($c,'alamat_penyedia','alamat')).'<br>NPWP &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$this->e($this->value($c,'npwp')).'<br><br>yang bertindak untuk dan atas nama '.$this->e($this->value($c,'nama_penyedia_resmi','nama_perusahaan','nama_penyedia')).', selanjutnya disebut "Penyedia".</td></tr>';
        $html.='<tr><td><b>PAKET PENGADAAN</b><br><br><div align="center"><b><u>'.$package.'</u></b></div></td><td><b>NOMOR DAN TANGGAL HASIL PENGADAAN LANGSUNG</b><br>Nomor : '.$this->e($d['nomor_dokumen']??'-').'<br>Tanggal : '.$this->e($d['tanggal_dokumen']??'-').'</td></tr><tr><td><b>SUMBER DANA</b></td><td>'.$this->e($this->value($c,'sumber_dana')).' Tahun Anggaran '.$this->e($c['tahun']??'').'</td></tr><tr><td><b>MASA PELAKSANAAN PEKERJAAN</b></td><td>'.$this->e($this->value($c,'waktu_pelaksanaan')).' hari kalender sejak tanggal mulai kerja sampai penyerahan pertama pekerjaan.</td></tr><tr><td colspan="2"><b>HARGA KONTRAK</b><br><br><b>Harga kontrak termasuk pajak adalah sebesar Rp'.$this->money($c['nilai_kontrak']??0).'</b></td></tr><tr><td colspan="2"><b>SISTEM PEMBAYARAN</b><br><br>Pembayaran prestasi pekerjaan dilakukan berdasarkan hasil yang telah diverifikasi sesuai ketentuan kontrak.</td></tr><tr><td colspan="2"><b>DOKUMEN KONTRAK</b><br><br>SPK, penawaran, hasil klarifikasi dan negosiasi, SSKK, SSUK, KAK/spesifikasi, jadwal, dan lampiran merupakan satu kesatuan yang tidak terpisahkan.</td></tr><tr><td colspan="2"><b>HAK DAN KEWAJIBAN</b><br><br>Para pihak melaksanakan hak dan kewajibannya berdasarkan SPK, ketentuan pengadaan, serta peraturan perundang-undangan.</td></tr></table>';$pdf->SetFont($this->font,'',max(6,$this->baseSize-0.8));$pdf->writeHTML($html,true,false,true,false,'');$this->footerPages($pdf,'surat perintah kerja (spk)');return$pdf->Output('','S');
    }

    private function contract(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,'P');$this->addOfficialPage($pdf);$pdf->SetFont($this->font,'B',$this->baseSize+3);$pdf->MultiCell(0,6,"SURAT PERJANJIAN\nKontrak Harga Satuan\n\nPaket ".$this->value($c,'jenis_pengadaan')."\n".strtoupper($this->value($c,'uraian_kontrak')),0,'C');$pdf->SetFont($this->font,'',$this->baseSize);$pdf->MultiCell(0,5,'Nomor : '.$this->value($d,'nomor_dokumen','nomor_kontrak'),0,'C');$pdf->Ln(4);
        $intro='SURAT PERJANJIAN ini berikut semua lampirannya adalah kontrak yang selanjutnya disebut "Kontrak", dibuat dan ditandatangani di '.$this->value($this->user,'nama_wilayah').' pada tanggal '.$this->value($d,'tanggal_dokumen').', antara:';$this->writeJustified($pdf,$intro,5);$party='<table cellpadding="3"><tr><td width="8%"></td><td width="24%">Nama<br>NIP<br>Jabatan<br>Berkedudukan di</td><td width="4%">:<br>:<br>:<br>:</td><td width="64%">'.$this->e($this->value($c,'nama_ppk_resmi','nama_ppk')).'<br>'.$this->e($this->value($c,'nip_ppk_resmi','nip_ppk')).'<br>Pejabat Pembuat Komitmen<br>'.$this->e($this->user['alamat_opd']??'-').'</td></tr></table><p>yang bertindak untuk dan atas nama '.$this->e($this->user['nama_opd']??$this->user['nama_org']??'-').' selanjutnya disebut "Pengguna Jasa", dengan:</p><table cellpadding="3"><tr><td width="8%"></td><td width="24%">Nama<br>Jabatan<br>Berkedudukan di<br>NPWP</td><td width="4%">:<br>:<br>:<br>:</td><td width="64%">'.$this->e($this->value($c,'direktur')).'<br>Direktur<br>'.$this->e($this->value($c,'alamat_penyedia','alamat')).'<br>'.$this->e($this->value($c,'npwp')).'</td></tr></table><p>yang bertindak untuk dan atas nama '.$this->e($this->value($c,'nama_penyedia_resmi','nama_perusahaan','nama_penyedia')).' selanjutnya disebut "Penyedia".</p>';$pdf->writeHTML($party,true,false,true,false,'');
        $defaults=[['type'=>'numbered','text'=>'Undang-Undang Nomor 2 Tahun 2017 tentang Jasa Konstruksi beserta perubahannya;'],['type'=>'numbered','text'=>'Kitab Undang-Undang Hukum Perdata, khususnya ketentuan mengenai perikatan;'],['type'=>'numbered','text'=>'Peraturan Presiden Nomor 16 Tahun 2018 beserta perubahannya tentang Pengadaan Barang/Jasa Pemerintah;'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'PARA PIHAK MENERANGKAN TERLEBIH DAHULU BAHWA:'],['type'=>'alpha','text'=>'telah dilakukan proses pemilihan Penyedia sesuai Dokumen Pemilihan;'],['type'=>'alpha','text'=>'Pengguna Jasa telah menunjuk Penyedia melalui SPPBJ untuk melaksanakan pekerjaan;'],['type'=>'alpha','text'=>'Penyedia memiliki keahlian, tenaga, dan sumber daya untuk menyelesaikan pekerjaan;'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 1 - ISTILAH DAN UNGKAPAN'],['type'=>'paragraph','text'=>'Istilah dan ungkapan dalam Surat Perjanjian ini memiliki arti yang sama seperti dalam lampiran Kontrak.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 2 - RUANG LINGKUP PEKERJAAN'],['type'=>'paragraph','text'=>'Ruang lingkup pekerjaan adalah '.$this->value($c,'uraian_kontrak').'.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 3 - HARGA KONTRAK, SUMBER PEMBIAYAAN DAN PEMBAYARAN'],['type'=>'numbered','text'=>'Nilai Kontrak termasuk pajak adalah Rp'.$this->money($c['nilai_kontrak']??0).'.'],['type'=>'numbered','text'=>'Kontrak ini dibiayai dari '.$this->value($c,'sumber_dana').'.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 4 - DOKUMEN KONTRAK'],['type'=>'paragraph','text'=>'Surat Perjanjian, penawaran, daftar kuantitas dan harga, SSKK, SSUK, spesifikasi/KAK, gambar, jadwal, jaminan, berita acara, dan adendum merupakan satu kesatuan Kontrak.'],['type'=>'paragraph','align'=>'center','style'=>['bold'],'text'=>'Pasal 5 - MASA KONTRAK'],['type'=>'paragraph','text'=>'Masa pelaksanaan pekerjaan adalah '.$this->value($c,'waktu_pelaksanaan').' hari kalender sesuai SPMK.']];$rows=$this->paragraphs($d,$c);$this->writeParagraphs($pdf,$rows?:$defaults);$pdf->Ln(5);$pdf->writeHTML('<table><tr><td width="50%" align="center">Untuk dan atas nama Penyedia<br><br><br><br><b>'.$this->e($this->value($c,'direktur','nama_penyedia_resmi','nama_penyedia')).'</b></td><td width="50%" align="center">Pejabat Pembuat Komitmen<br><br><br><br><b>'.$this->e($this->value($c,'nama_ppk_resmi','nama_ppk')).'</b><br>NIP. '.$this->e($this->value($c,'nip_ppk_resmi','nip_ppk')).'</td></tr></table>',true,false,true,false,'');$this->footerPages($pdf,'surat perjanjian');return$pdf->Output('','S');
    }

    private function sskk(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,'P');$pdf->AddPage();$pdf->SetFont($this->font,'B',$this->baseSize+5);$pdf->MultiCell(0,8,'SYARAT-SYARAT KHUSUS KONTRAK',0,'C');$rows=$this->paragraphs($d,$c);if(!$rows)$rows=[['text'=>'4.1 & 4.2 | Korespondensi | Alamat para pihak dan wakil sah mengikuti data kontrak.'],['text'=>'27.1 | Masa Pelaksanaan | Masa pelaksanaan '.$this->value($c,'waktu_pelaksanaan').' hari kalender sejak SPMK.'],['text'=>'45.b | Pembayaran Tagihan | Pembayaran dilakukan atas prestasi pekerjaan yang terverifikasi.'],['text'=>'70.4 | Denda Keterlambatan | Denda sebesar 1/1000 per hari sesuai bagian kontrak yang belum diserahterimakan.'],['text'=>'Lainnya | Penyelesaian Perselisihan | Diselesaikan melalui musyawarah atau layanan penyelesaian sengketa yang disepakati.']];$html='<table cellpadding="3"><thead><tr style="font-weight:bold"><th width="13%">Pasal dalam SSUK</th><th width="22%">Ketentuan</th><th width="65%">Data</th></tr></thead><tbody>';foreach($rows as $row){$parts=array_map('trim',explode('|',(string)$row['text'],3));$html.='<tr><td width="13%"><b>'.$this->e($parts[0]??'').'</b></td><td width="22%"><b>'.$this->e($parts[1]??'').'</b></td><td width="65%">'.$this->e($parts[2]??$parts[0]??'').'</td></tr>';}$html.='</tbody></table>';$pdf->SetFont($this->font,'',max(6,$this->baseSize-0.5));$pdf->writeHTML($html,true,false,true,false,'');$this->footerPages($pdf,'syarat-syarat khusus kontrak');return$pdf->Output('','S');
    }
    private function ssuk(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,'P');$pdf->AddPage();$pdf->SetFont($this->font,'B',$this->baseSize+5);$pdf->MultiCell(0,7,"SYARAT UMUM\nSURAT PERINTAH KERJA (SPK)",0,'C');$rows=$this->paragraphs($d,$c);if(!$rows)$rows=[['type'=>'numbered','style'=>['bold'],'text'=>'LINGKUP PEKERJAAN'],['text'=>'Penyedia wajib menyelesaikan pekerjaan sesuai volume, KAK/spesifikasi, mutu, waktu, dan harga dalam SPK.'],['type'=>'numbered','style'=>['bold'],'text'=>'HUKUM YANG BERLAKU'],['text'=>'Keabsahan, interpretasi, dan pelaksanaan SPK didasarkan pada hukum Republik Indonesia.'],['type'=>'numbered','style'=>['bold'],'text'=>'LARANGAN KKN DAN PENIPUAN'],['text'=>'Para pihak dilarang melakukan korupsi, kolusi, nepotisme, penyalahgunaan wewenang, benturan kepentingan, dan penipuan.']];$this->writeParagraphs($pdf,$rows);$this->footerPages($pdf,'syarat umum spk');return$pdf->Output('','S');
    }
    private function officialLetter(array $d,array $c,array $setup):string
    {
        $pdf=$this->pdf($setup,($d['orientasi']??'P'));$this->addOfficialPage($pdf);$title=strtoupper((string)($d['judul']??$d['nama_master']??'DOKUMEN PENGADAAN'));$pdf->SetFont($this->font,'B',$this->baseSize+4);$pdf->MultiCell(0,7,$title,0,'C');$pdf->SetFont($this->font,'',$this->baseSize);$pdf->MultiCell(0,5,'Nomor : '.($d['nomor_dokumen']??'-'),0,'C');$pdf->Ln(4);$info='<table cellpadding="3"><tr><td width="25%">Nama Paket</td><td width="3%">:</td><td width="72%">'.$this->e($this->value($c,'uraian_kontrak')).'</td></tr><tr><td>Nilai</td><td>:</td><td>Rp'.$this->money($c['nilai_kontrak']??0).'</td></tr><tr><td>Sumber Dana</td><td>:</td><td>'.$this->e($this->value($c,'sumber_dana')).'</td></tr><tr><td>Penyedia</td><td>:</td><td>'.$this->e($this->value($c,'nama_penyedia_resmi','nama_penyedia')).'</td></tr></table>';$pdf->writeHTML($info,true,false,true,false,'');$rows=$this->paragraphs($d,$c);if(!$rows)$rows=[['text'=>'Pada tanggal '.$this->value($d,'tanggal_dokumen').' telah disusun '.$title.' untuk paket tersebut di atas.'],['text'=>'Dokumen ini dibuat berdasarkan hasil pemeriksaan, klarifikasi, evaluasi, atau kesepakatan para pihak sesuai tahapan pengadaan yang berlaku.'],['text'=>'Demikian dokumen ini dibuat untuk dipergunakan sebagaimana mestinya.']];$this->writeParagraphs($pdf,$rows);$this->footerPages($pdf,$title);return$pdf->Output('','S');
    }
}
