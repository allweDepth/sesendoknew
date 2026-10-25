<?php
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/OfficialLetterheadPdfService.php';
require_once __DIR__.'/ProcurementDocumentFormatService.php';

/** Layout khusus untuk setiap dokumen pada ZIP contoh pengguna. */
final class ProcurementZipPdfService
{
    private const CODES=['UNDANGAN_PL','JADWAL_DOKUMEN','BA_SURVEY_HARGA','PAKTA_INTEGRITAS','BA_KLARIFIKASI_NEGOSIASI','LAMPIRAN_NEGOSIASI','DAFTAR_HADIR_KLARIFIKASI','PENETAPAN_PENYEDIA','PENGUMUMAN_PENYEDIA','BA_EVALUASI','BAHP','SPPBJ','SPMK','RAPAT_PRA_KONTRAK'];
    private array $user; private string $font='helvetica'; private float $size=9;
    private ?TCPDF $sharedPdf=null;
    private string $orientation='P';
    private array $printSettings=[];
    public function __construct(array $user){$this->user=$user;foreach(['nama_opd','nama_org'] as $key)if(!empty($this->user[$key]))$this->user[$key]=ProcurementDocumentFormatService::bodyOpdName($this->user[$key]);}
    public function supports(string $code):bool{return in_array(strtoupper($code),self::CODES,true);}
    public function renderInto(TCPDF $pdf,string $code,array $d,array $c,array $setup):void
    {
        $this->sharedPdf=$pdf;
        try{$this->render($code,$d,$c,$setup);}finally{$this->sharedPdf=null;}
    }
    private function finish(TCPDF $pdf):string{return $this->sharedPdf?'':$pdf->Output('','S');}
    public function render(string $code,array $d,array $c,array $setup):string
    {
        $this->printSettings=ContractPrintSettingsService::validate($setup);
        $this->font=(string)($setup['font']??'helvetica');$this->size=(float)($setup['font_size']??10);
        return match(strtoupper($code)){
            'UNDANGAN_PL'=>$this->invitation($d,$c,$setup),'JADWAL_DOKUMEN'=>$this->documentSchedule($d,$c,$setup),
            'BA_SURVEY_HARGA'=>$this->survey($d,$c,$setup),'PAKTA_INTEGRITAS'=>$this->integrity($d,$c,$setup),
            'BA_KLARIFIKASI_NEGOSIASI'=>$this->negotiation($d,$c,$setup),'LAMPIRAN_NEGOSIASI'=>$this->negotiationAttachment($d,$c,$setup),
            'DAFTAR_HADIR_KLARIFIKASI'=>$this->attendance($d,$c,$setup),'PENETAPAN_PENYEDIA'=>$this->providerDecision($d,$c,$setup),
            'PENGUMUMAN_PENYEDIA'=>$this->announcement($d,$c,$setup),'BA_EVALUASI'=>$this->evaluation($d,$c,$setup,false),
            'BAHP'=>$this->evaluation($d,$c,$setup,true),'SPPBJ'=>$this->sppbj($d,$c,$setup),
            'SPMK'=>$this->spmk($d,$c,$setup),'RAPAT_PRA_KONTRAK'=>$this->preContract($d,$c,$setup)
        };
    }
    private function pdf(array $setup,string $orientation='P'):TCPDF{$pdf=$this->sharedPdf??PageSetupService::createPdf($setup,$orientation);ProcurementDocumentFormatService::registerFonts($pdf);PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);$this->orientation=$orientation;ProcurementDocumentFormatService::typography($pdf,$setup);$pdf->SetFont($this->font,'',$this->size);return$pdf;}
    private function page(array $setup,string $orientation='P',bool $letterhead=true):TCPDF{$pdf=$this->pdf($setup,$orientation);$pdf->AddPage($this->orientation);if($letterhead)OfficialLetterheadPdfService::draw($pdf,array_merge($this->user,['_letterhead_font'=>ProcurementDocumentFormatService::BODY_FONT]));$pdf->SetFont($this->font,'',$this->size);return$pdf;}
    private function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private function v(array $data,string ...$keys):string{foreach($keys as $key){$value=trim((string)($data[$key]??''));if($value!=='')return$value;}return'-';}
    private function money(mixed $v):string{return'Rp'.number_format((float)$v,2,',','.');}
    private function date(array $d,array $c):string{$raw=$d['tanggal_dokumen']??$c['tanggal_kontrak']??date('Y-m-d');$time=strtotime((string)$raw);if(!$time)return(string)$raw;$months=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return date('j',$time).' '.$months[(int)date('n',$time)].' '.date('Y',$time);}
    private function title(TCPDF $pdf,string $title,string $number=''):void{$pdf->SetFont($this->font,'B',$this->size+1);$pdf->MultiCell(0,6,strtoupper($title),0,'C');if($number!==''){$pdf->SetFont($this->font,'',$this->size);$pdf->MultiCell(0,5,'Nomor : '.$number,0,'C');}$pdf->Ln(3);$pdf->SetFont($this->font,'',$this->size);}
    private function para(TCPDF $pdf,string $text,float $height=0):void
    {
        $pdf->SetFont($this->font,'',$this->size);
        $pdf->Ln($this->printSettings['paragraph_before']*25.4/72);
        $pdf->MultiCell(0,0,rtrim($text)."\n",0,'J',false,1,'','',true,0,false,true);
        $pdf->Ln($this->printSettings['paragraph_after']*25.4/72);
    }
    private function fields(array $rows,array $widths=[30,4,66]):string{$html='<br><table width="100%" cellpadding="3">';foreach($rows as [$label,$value])$html.='<tr nobr="true"><td width="'.$widths[0].'%">'.$this->e($label).'</td><td width="'.$widths[1].'%">:</td><td width="'.$widths[2].'%">'.$this->e($value).'</td></tr>';$html.='</table>';return$html;}
    private function signature(TCPDF $pdf,string $role,string $name,string $nip=''):void{$pdf->Ln(6);$html='<table nobr="true"><tr><td width="55%"></td><td width="45%" align="center">'.nl2br($this->e($role)).'<br><br><br><br><b><u>'.$this->e($name).'</u></b>'.($nip!==''?'<br>NIP. '.$this->e($nip).'<br>'.$this->e($this->user['pangkat_pejabat_pengadaan']??''):'').'</td></tr></table>';$this->signatureBlock($pdf,$html);}
    private function signatureBlock(TCPDF $pdf,string $html):void
    {
        $html=str_replace(' nobr="true"','',$html);
        $start=$pdf->getPage();$pdf->startTransaction();$pdf->writeHTML($html,true,false,true,false,'');
        if($pdf->getPage()>$start){$pdf->rollbackTransaction(true);$pdf->AddPage($this->orientation);$pdf->writeHTML($html,true,false,true,false,'');}
        else $pdf->commitTransaction();
    }
    private function package(array $c):array{return[['Sub Kegiatan',$this->v($c,'nama_sub_kegiatan','nama_sub_keg','kd_sub_keg')],['Nama Paket',$this->v($c,'uraian_kontrak')],['Sumber Dana',$this->v($c,'sumber_dana')],['Tahun Anggaran',$this->v($c,'tahun')],['Kode Rekening',$this->v($c,'kd_sub_keg')],['Nilai HPS/OE',$this->money($c['nilai_hps']??0)]];}
    private function provider(array $c):array
    {
        $offer=array_sum(array_column($c['rab']??[],'jumlah_penawaran'));$nego=$c['nilai_kontrak']??null;
        return [['Nama Penyedia Barang/Jasa',$this->v($c,'nama_penyedia_resmi','nama_perusahaan','nama_penyedia')],['Nama Direktur Utama',$this->v($c,'direktur')],['Alamat',$this->v($c,'alamat_penyedia','alamat')],['NPWP',$this->v($c,'npwp')],['Harga Penawaran',$this->money($offer).' ('.ucwords($this->words((int)round($offer))).' Rupiah)'],['Harga Klarifikasi dan Negosiasi',isset($nego)?$this->money($nego).' ('.ucwords($this->words((int)round($nego))).' Rupiah)':'Belum ditetapkan'],['Jangka waktu pelaksanaan',$this->v($c,'waktu_pelaksanaan').' ('.ucwords($this->words((int)($c['waktu_pelaksanaan']??0))).') Hari Kalender']];
    }
    private function invitation(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$date=$this->date($d,$c);$provider=$this->v($c,'nama_penyedia_resmi','nama_penyedia');
        $pdf->writeHTML('<table width="100%" cellpadding="2"><tr><td width="62%">Nomor: '.$this->e($d['nomor_dokumen']??'-').'<br>Lampiran: 1 (satu) berkas</td><td width="38%">Pasangkayu, '.$this->e($date).'</td></tr></table><p align="left">Kepada Yth.<br>Direktur '.$this->e($provider).'<br>di-<br>'.$this->e($this->v($c,'alamat_penyedia','alamat')).'</p><p>Perihal: Undangan pengadaan Langsung '.$this->e($c['uraian_kontrak']).' - pada Kegiatan '.$this->e($this->v($c,'nama_sub_keg')).'</p>',true,false,true,false,'');
        $this->para($pdf,'Dengan ini, perusahaan Saudara kami undang untuk mengikuti proses Pengadaan Langsung Jasa Konsultansi paket Pekerjaan sebagai berikut:');
        $this->sectionHeading($pdf,'1.','Paket Pekerjaan');
        $pdf->writeHTML($this->fields([['Sub Kegiatan',$this->v($c,'nama_sub_keg')],['Nama Paket Pekerjaan',$c['uraian_kontrak']],['Detail Pekerjaan','']]),true,false,true,false,'');
        $pdf->writeHTML('<table width="100%" border="1" cellpadding="4"><thead><tr><td width="72%">Pekerjaan</td><td width="28%">Pagu Anggaran</td></tr></thead><tr nobr="true"><td width="72%">'.$this->e($c['uraian_kontrak']).'</td><td width="28%" align="right">'.$this->money($c['total_anggaran']).'</td></tr></table>',true,false,true,false,'');
        $pdf->writeHTML($this->fields([['Nilai HPS/OE',$this->money($c['nilai_hps']??0)]]),true,false,true,false,'');
        $this->sectionHeading($pdf,'2.','Pelaksanaan Pengadaan');
        $pdf->writeHTML($this->fields([['Tempat dan alamat',$this->v($this->user,'nama_opd').' '.$this->v($this->user,'alamat_opd')]]),true,false,true,false,'');
        $this->para($pdf,'Saudara diminta untuk memasukan penawaran administrasi, teknis dan harga, secara langsung sesuai dengan jadwal pelaksanaan sebagai berikut');
        $html='<table width="100%" border="1" cellpadding="4"><thead><tr><td width="6%">No.</td><td width="44%">Kegiatan</td><td width="25%">Hari/Tanggal</td><td width="25%">Waktu</td></tr></thead><tbody>';
        foreach(['Undangan','Pemasukan Dokumen Penawaran','Pembukaan Dokumen Penawaran','Klarifikasi Teknis dan Negosiasi Harga','Penandatanganan SPK'] as $i=>$event)$html.='<tr nobr="true"><td width="6%">'.($i+1).'</td><td width="44%">'.$event.'</td><td width="25%">'.$this->e($date).'</td><td width="25%">s.d. selesai</td></tr>';
        $pdf->writeHTML($html.'</tbody></table>',true,false,true,false,'');
        $this->para($pdf,'Apabila Saudara butuh keterangan dan penjelasan lebih lanjut, dapat menghubungi Pejabat Pengadaan sesuai alamat tersebut di atas sampai dengan batas akhir pemasukan Dokumen Penawaran.');
        $this->para($pdf,'Demikian disampaikan untuk diketahui.');
        $this->signature($pdf,'Pejabat Pengadaan Barang/Jasa',$this->v($c,'nama_pejabat_pengadaan'),$this->v($c,'nip_pejabat_pengadaan'));return $this->finish($pdf);
    }
    private function sectionHeading(TCPDF $pdf,string $number,string $title):void
    {
        $m=$pdf->getMargins();$pdf->SetX($m['left']);$pdf->SetFont($this->font,'B',$this->size);
        $pdf->MultiCell(0,0,$number.' '.$title,0,'L',false,1);$pdf->SetX($m['left']);$pdf->SetFont($this->font,'',$this->size);
    }
    private function documentSchedule(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup,'P',false);$this->title($pdf,"JADWAL DAN KELENGKAPAN DOKUMEN PENGADAAN KONSULTANSI\nMELALUI PROSES PENGADAAN LANGSUNG");$date=$this->date($d,$c);
        $items=[['Undangan mengikuti Pengadaan Langsung','UNDANGAN_PL'],['Upload Dokumen Penawaran',''],['Pembukaan Dokumen Penawaran',''],['Evaluasi Penawaran','BA_EVALUASI'],['Evaluasi Kualifikasi','BA_EVALUASI'],['BA Klarifikasi dan Negosiasi','BA_KLARIFIKASI_NEGOSIASI'],['Daftar Hadir Klarifikasi dan Negosiasi','BA_KLARIFIKASI_NEGOSIASI'],['Surat Pernyataan kesanggupan menurunkan Biaya','BA_KLARIFIKASI_NEGOSIASI'],['Lampiran Biaya setelah Klarifikasi dan Negosiasi','BA_KLARIFIKASI_NEGOSIASI'],['BA Hasil Pengadaan Langsung (BAHPL)','BAHP'],['Penetapan Pelaksana pengadaan Langsung','BAHP'],['Pengumuman dan pemberitahuan','BAHP'],['SPPBJ','SPPBJ'],['Surat Perintah Mulai Kerja (SPMK)','SPMK'],['Surat Perintah kerja (SPK)','SPK']];
        $html='<table border="1" cellpadding="4"><thead><tr style="font-weight:bold"><td width="5%">No.</td><td width="37%">URAIAN</td><td width="19%">HARI/TANGGAL</td><td width="29%">NOMOR</td><td width="5%">ADA</td><td width="5%">TDK</td></tr></thead><tbody>';
        foreach($items as $i=>[$label,$code]){
            $number=match($code){'SPK'=>$c['nomor_kontrak'],'SPMK'=>$c['nomor_spmk'],''=>'',default=>$c['dokumen_referensi'][$code]['nomor_dokumen']??ProcurementDocumentFormatService::documentNumber($c,$code)};
            $html.='<tr nobr="true"><td width="5%">'.($i+1).'</td><td width="37%">'.$this->e($label).'</td><td width="19%">'.$this->e($date).'</td><td width="29%">'.$this->e($number).'</td><td width="5%"></td><td width="5%"></td></tr>';
        }
        $pdf->writeHTML($html.'</tbody></table><br><table nobr="true"><tr><td width="50%" align="center">Pejabat Pembuat Komitmen<br><br><br><b><u>'.$this->e($this->v($c,'nama_ppk_resmi','nama_ppk')).'</u></b><br>NIP. '.$this->e($this->v($c,'nip_ppk_resmi','nip_ppk')).'</td><td width="50%" align="center">Pejabat Pengadaan Barang/Jasa<br><br><br><b><u>'.$this->e($this->v($c,'nama_pejabat_pengadaan')).'</u></b><br>NIP. '.$this->e($this->v($c,'nip_pejabat_pengadaan')).'</td></tr></table>',true,false,true,false,'');return $this->finish($pdf);
    }
    private function survey(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,'BERITA ACARA SURVEY HARGA');
        $this->para($pdf,'Pada hari ini '.$this->dayWords($d,$c).', yang bertanda tangan dibawah ini Pejabat Pengadaan Barang/Jasa pada '.$this->v($this->user,'nama_opd').' yang ditetapkan berdasarkan Surat Keputusan Kepala '.$this->v($this->user,'nama_opd').' nomor '.$this->v($c,'nomor_sk_pejabat_pengadaan').' tanggal '.$this->date(['tanggal_dokumen'=>$c['tanggal_sk_pejabat_pengadaan']??''],$c).' tentang Penunjukan Pejabat Pengadaan Bidang Kepala Dinas Selaku Pengguna Anggaran, '.$this->v($this->user,'nama_opd').' Tahun '.$c['tahun'].', telah mengadakan survey harga, untuk:');
        $pdf->writeHTML($this->fields([['Sub Kegiatan',$this->v($c,'nama_sub_keg')],['Nama Paket',$c['uraian_kontrak']],['Ruas Pekerjaan',$c['uraian_kontrak']]]).'<p>Dengan hasil sebagai berikut:</p><p>1. Survey pertama dilakukan dengan hasil terlampir.</p><p>2. Survey pertama dilakukan dengan hasil terlampir.</p><p>Demikian Berita Acara ini dibuat dalam rangkap 3 (tiga) untuk dapat dipergunakan sebagaimana mestinya.</p>',true,false,true,false,'');
        $this->signature($pdf,'Pejabat Pengadaan Barang/Jasa',$c['nama_pejabat_pengadaan']??'-',$c['nip_pejabat_pengadaan']??'');return $this->finish($pdf);
    }
    private function integrity(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,'PAKTA INTEGRITAS');
        $this->para($pdf,'Saya yang bertanda tangan ini, dalam rangka '.$c['uraian_kontrak'].' Kegiatan '.$this->v($c,'nama_sub_keg').' - APBD Kabupaten Pasangkayu T.A. '.$c['tahun'].' - '.$this->v($this->user,'nama_opd').' Tahun Anggaran '.$c['tahun'].', dengan ini menyatakan bahwa saya:');
        $pdf->writeHTML('<p>Dengan hasil sebagai berikut:</p><ol><li>Tidak akan melakukan praktek KKN</li><li>Akan melaporkan kepada pihak yang berwenang/berwajib apabila mengetahui ada indikasi KKN di dalam proses pengadaan ini.</li><li>Dalam proses pengadaan ini, berjanji akan melaksanakan tugas secara bersih, transparan dan profesional dalam arti akan mengerahkan segala kemampuan dan sumber daya secara optimal untuk memberikan hasil kerja terbaik mulai dari penyiapan penawaran, pelaksanaan dan penyelesaian pekerjaan/kegiatan ini.</li><li>Apabila saya melanggar hal-hal yang telah saya nyatakan dalam PAKTA INTEGRITAS ini, saya bersedia dikenakan sanksi moral, sanksi administrasi serta dituntut ganti rugi dan pidana sesuai dengan ketentuan perundang-undangan yang berlaku.</li></ol><p>Demikian Berita Acara ini dibuat dengan sebenarnya dan mengikat bagi calon Penyedia untuk dipergunakan sesuai maksudnya.</p><p align="right">Pasangkayu, '.$this->e($this->date($d,$c)).'</p>',true,false,true,false,'');
        $people=[['Pejabat Pembuat Komitmen',$this->v($c,'nama_ppk_resmi','nama_ppk'),$this->v($c,'nip_ppk_resmi','nip_ppk')],['Pejabat Pelaksana Teknis Kegiatan (PPTK)',$this->v($c,'nama_pptk'),$this->v($c,'nip_pptk')],['Pejabat Pengadaan Barang/Jasa',$this->v($c,'nama_pejabat_pengadaan'),$this->v($c,'nip_pejabat_pengadaan')],['Penyedia Jasa '.$this->v($c,'nama_penyedia_resmi','nama_penyedia'),$this->v($c,'direktur'),$this->v($c,'jabatan_pelaksana')]];
        $html='<table cellpadding="6">';foreach($people as $i=>[$role,$name,$detail])$html.='<tr nobr="true"><td width="43%">'.($i+1).'. '.$this->e($role).'</td><td width="37%">'.$this->e($name).'<br>'.$this->e($detail).'</td><td width="20%">........................</td></tr>'; $pdf->writeHTML($html.'</table>',true,false,true,false,'');return $this->finish($pdf);
    }
    private function negotiation(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,"BERITA ACARA\nHASIL KLARIFIKASI DAN NEGOSIASI",$d['nomor_dokumen']??'');
        $this->para($pdf,'Pada hari ini '.$this->dayWords($d,$c).', bertempat di Kantor '.$this->v($this->user,'nama_opd').', yang bertanda tangan dibawah ini Pejabat Pengadaan Barang/Jasa pada '.$this->v($this->user,'nama_opd').' yang ditetapkan berdasarkan Surat Keputusan Kepala '.$this->v($this->user,'nama_opd').' nomor '.$this->v($c,'nomor_sk_pejabat_pengadaan').' tanggal '.$this->date(['tanggal_dokumen'=>$c['tanggal_sk_pejabat_pengadaan']??''],$c).', telah melakukan klarifikasi dan negosiasi untuk:');
        $offer=array_sum(array_column($c['rab']??[],'jumlah_penawaran'));$nego=$c['nilai_kontrak']??null;
        $pdf->writeHTML($this->fields($this->package($c)).'<p>Dengan hasil sebagai berikut:</p><p>1. Klarifikasi dan Negosiasi dilaksanakan pada hari '.$this->e($this->dayWords($d,$c)).' sampai selesai, antara Pejabat Pengadaan Barang/Jasa dengan Direktur '.$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia')).'</p><p>2. Klarifikasi dan Negosiasi dilakukan untuk mendapatkan kesepakatan besarnya biaya dengan tetap mempertahankan hasil yang ingin dicapai dengan penawaran teknis yang diajukan.</p><p>3. Sebagai hasil klarifikasi dan negosiasi adalah sebagai berikut:</p><p>3.1. Dokumen penawaran dari penyedia jasa telah sesuai dengan persyaratan dokumen pemilihan / RKS</p><p>3.2. Sebagai calon penyedia jasa, untuk pekerjaan tersebut, '.$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia')).' sepakat dan bersedia menurunkan biaya, yang semula penawarannya sebesar '.$this->money($offer).' ('.$this->e(ucwords($this->words((int)round($offer)))).' Rupiah) diturunkan menjadi '.(isset($nego)?$this->money($nego).' ('.$this->e(ucwords($this->words((int)round($nego)))).' Rupiah)':'Belum ditetapkan').'. Lampiran I Berita Acara Klarifikasi dan Negoisasi serta daftar kuantitas harga setelah klarifikasi dan negosiasi merupakan bagian yang tidak terpisahkan dari berita acara ini.</p><p>3.3. Jika terjadi penurunan biaya yang disepakati tetap memenuhi persyaratan teknis yang telah ditentukan dalam dokumen pemilihan / RKS.</p><p>Demikian Berita Acara Hasil Klarifikasi dan Negosiasi ini dibuat dengan sebenarnya, ditandatangani oleh Pejabat Pengadaan Barang / Jasa, untuk dipergunakan sebagaimana mestinya.</p>',true,false,true,false,'');
        $this->signatureBlock($pdf,$this->pairedSignatures($c));return $this->finish($pdf);
    }
    private function negotiationAttachment(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup,'L',false);$pdf->SetFont($this->font,'B',$this->size);
        $ref=$c['dokumen_referensi']['BA_KLARIFIKASI_NEGOSIASI']??$d;
        $pdf->MultiCell(0,5,"LAMPIRAN I\nBerita Acara Klarifikasi dan Negosiasi\nNomor: ".($ref['nomor_dokumen']??'-').'   Tanggal: '.$this->date($ref,$c),0,'L');
        $this->title($pdf,'Rincian Penawaran Setelah Klarifikasi dan Negosiasi');
        $widths=[3,20,5,5,5,4,9,9,9,4,9,9,9];
        $html='<table cellpadding="2"><thead><tr style="font-weight:bold;text-align:center"><td width="3%">No.</td><td width="20%">Uraian Pekerjaan</td><td colspan="3" width="15%">Kuantitas</td><td width="4%">Satuan</td><td colspan="3" width="27%">Harga Satuan</td><td width="4%">Pajak</td><td colspan="3" width="27%">JUMLAH</td></tr><tr style="font-weight:bold;text-align:center">';
        foreach(['','','HPS','Penawaran','Negosiasi','','HPS','Penawaran','Negosiasi','','HPS','Penawaran','Negosiasi'] as $i=>$label)$html.='<td width="'.$widths[$i].'%">'.$label.'</td>';
        $html.='</tr></thead><tbody><tr><td colspan="13" style="border-top:0.5px solid black">Biaya perencanaan teknis</td></tr>';
        $totals=['hps'=>[0,0,0],'penawaran'=>[0,0,0],'negoisasi'=>[0,0,0]];
        foreach($c['rab']??[] as $r){
            $values=[$r['nomor'],$r['uraian']];
            foreach(array_keys($totals) as $k)$values[]=number_format((float)$r['vol_'.$k],2,',','.');$values[]=$r['satuan'];
            foreach(array_keys($totals) as $k)$values[]=number_format((float)$r['harga_sat_'.$k],2,',','.');$values[]=number_format((float)$r['pajak'],2,',','.');
            foreach(array_keys($totals) as $k){$raw=round((float)$r['vol_'.$k]*(float)$r['harga_sat_'.$k],2);$vat=round((float)$r['jumlah_'.$k]-$raw,2);$values[]=number_format($raw,2,',','.');$totals[$k][0]+=$raw;$totals[$k][1]+=(float)$r['pajak']>0?$raw:0;$totals[$k][2]+=$vat;}
            $html.='<tr nobr="true"'.(((int)$r['nomor']%2)?' bgcolor="#eeeeee"':'').'>';foreach($values as $i=>$value)$html.='<td width="'.$widths[$i].'%"'.($i>5?' align="right"':'').'>'.$this->e($value).'</td>';$html.='</tr>';
        }
        if(empty($c['rab']))$html.='<tr><td colspan="13">Rincian RAB belum diisi.</td></tr>';
        $pdf->SetFont($this->font,'',$this->size);$pdf->writeHTML($html.'</tbody></table>',true,false,true,false,'');
        $summary='<table cellpadding="2">';
        foreach(['Jumlah Total (A)','Jumlah Terkena Pajak (B)','PPN sesuai rincian (C)','Jumlah Total termasuk PPN (D)','Jumlah Total dibulatkan (E)'] as $i=>$label){
            $summary.='<tr nobr="true"><td width="73%" align="right" style="border-top:0.5px solid black">'.$label.'</td>';
            foreach($totals as $t){$amount=$i<3?$t[$i]:$t[0]+$t[2];$summary.='<td width="9%" align="right" style="border-top:0.5px solid black">'.number_format($amount,2,',','.').'</td>';}$summary.='</tr>';
        }
        $nego=$c['nilai_kontrak']??null;
        $summary.='</table><p><b>Terbilang ## '.(isset($nego)?$this->e(ucwords($this->words((int)round($nego)))).' Rupiah':'Belum ditetapkan').' ##</b></p><p>Keterangan:<br>Harga sudah memperhitungkan pajak yang berlaku</p>';
        // Continue in the available space; only individual rows and signatures stay intact.
        $pdf->writeHTML($summary,true,false,true,false,'');
        $this->signatureBlock($pdf,$this->pairedSignatures($c));
        return $this->finish($pdf);
    }
    private function attendance(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,"Daftar Hadir\nKlarifikasi dan Negosiasi");$pdf->writeHTML($this->fields(array_merge($this->package($c),[['Hari/Tanggal',$this->date($d,$c)],['Tempat',$this->v($this->user,'nama_opd').' - '.$this->v($this->user,'alamat_opd')]])).'<table cellpadding="2"><tr><td><b>PEJABAT PENGADAAN BARANG/JASA</b></td></tr></table><table border="1" cellpadding="6"><tr><th width="8%">No.</th><th width="42%">Nama</th><th width="25%">Jabatan</th><th width="25%">Tanda Tangan</th></tr><tr><td>1</td><td>'.$this->e($this->v($this->user,'nama_pejabat_pengadaan','nama_user')).'</td><td>Pejabat Pengadaan</td><td></td></tr></table><br><table cellpadding="2"><tr><td><b>PENYEDIA BARANG/JASA</b></td></tr></table><table border="1" cellpadding="6"><tr><th width="8%">No.</th><th width="42%">Nama</th><th width="25%">Jabatan</th><th width="25%">Tanda Tangan</th></tr><tr><td>1</td><td>'.$this->e($this->v($c,'direktur')).'</td><td>Direktur</td><td></td></tr></table>',true,false,true,false,'');return$this->finish($pdf);
    }
    private function providerDecision(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,'PENETAPAN PENYEDIA JASA KONSULTANSI',$d['nomor_dokumen']??'');
        $this->para($pdf,'Berdasarkan Berita Acara Hasil Pengadaan Langsung (BAHPL) '.$this->dayWords($d,$c).' dengan ini Pejabat Pengadaan Barang/Jasa Bidang Kepala Dinas Selaku Pengguna Anggaran '.$this->v($this->user,'nama_opd').' yang ditetapkan berdasarkan keputusan Kepala '.$this->v($this->user,'nama_opd').' Nomor: '.$this->v($c,'nomor_sk_pejabat_pengadaan').' tanggal '.$this->date(['tanggal_dokumen'=>$c['tanggal_sk_pejabat_pengadaan']??''],$c).' menetapkan Penyedia Barang/Jasa Pengadaan Langsung Pekerjaan Konstruksi:');
        $pdf->writeHTML($this->fields($this->package($c)).'<p>Adalah sebagai berikut:</p>'.$this->fields($this->provider($c)).'<p>Demikian Penetapan Penyedia Barang/Jasa Pengadaan Langsung ini dibuat untuk dipergunakan sebagaimana mestinya.</p>',true,false,true,false,'');
        $this->signature($pdf,"Ditetapkan di Pasangkayu\nPada Tanggal: ".$this->date($d,$c)."\nPejabat Pengadaan Barang/Jasa",$this->v($c,'nama_pejabat_pengadaan'),$this->v($c,'nip_pejabat_pengadaan'));return $this->finish($pdf);
    }
    private function announcement(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,'PENGUMUMAN PENYEDIA JASA KONSULTANSI',$d['nomor_dokumen']??'');
        $ref=$c['dokumen_referensi']['BAHP']['nomor_dokumen']??'-';
        $this->para($pdf,'Berdasarkan Surat Penetapan dari Pejabat Pengadaan Barang Jasa Nomor: '.$ref.' tanggal '.$this->date($d,$c).' tentang Pelaksana Pengadaan Langsung, dengan ini diberitahukan bahwa pelaksana Pengadaan Langsung Jasa Konsultansi:');
        $pdf->writeHTML($this->fields(array_slice($this->package($c),0,2)).'<p>Adalah sebagai berikut:</p>'.$this->fields($this->provider($c)).'<p>Demikian Pengumuman Penyedia Barang/Jasa Pengadaan Langsung ini dibuat untuk dipergunakan sebagaimana mestinya.</p>',true,false,true,false,'');
        $this->signature($pdf,"Diumumkan di Pasangkayu\nPada Tanggal: ".$this->date($d,$c)."\nPejabat Pengadaan Barang/Jasa",$this->v($c,'nama_pejabat_pengadaan'),$this->v($c,'nip_pejabat_pengadaan'));return $this->finish($pdf);
    }
    private function evaluation(array $d,array $c,array $setup,bool $final):string
    {
        $pdf=$this->page($setup);$this->title($pdf,$final?"Berita Acara\nHasil Pemilihan":"Berita Acara\nHasil Evaluasi Penawaran",$d['nomor_dokumen']??'');$pdf->SetFont($this->font,'',$this->size);
        $this->para($pdf,'Pada hari dan tanggal '.$this->date($d,$c).' telah dibuat '.($final?'Berita Acara Hasil Pemilihan':'Berita Acara Hasil Evaluasi Penawaran').' untuk paket pekerjaan berikut:',$final?4.2:5);
        $provider=$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia'));$address=$this->e($this->v($c,'alamat_penyedia','alamat'));$price=$this->money(array_sum(array_column($c['rab']??[],'jumlah_penawaran')));
        $html=$this->fields([['Kode Paket',$this->v($c,'kode_paket','kd_sub_keg')],['Nama Paket',$this->v($c,'uraian_kontrak')],['Nilai Total HPS',$this->money($c['nilai_hps']??0)],['Sumber Dana',$this->v($c,'sumber_dana')],['Metode Evaluasi','Pengadaan Langsung']]);
        $html.='<p><b>Dengan hasil sebagai berikut:</b></p><p><b>A. Pembukaan Penawaran</b><br>Dari 1 (satu) peserta yang mendaftar terdapat 1 (satu) peserta yang memasukkan dokumen penawaran, yaitu:</p><table border="1" cellpadding="4"><tr><th width="8%">No.</th><th width="32%">Nama Peserta</th><th width="40%">Alamat Peserta</th><th width="20%">Nilai Penawaran</th></tr><tr><td>1</td><td>'.$provider.'</td><td>'.$address.'</td><td>'.$price.'</td></tr></table>';
        foreach(['Evaluasi Administrasi','Evaluasi Teknis','Evaluasi Harga'] as $i=>$stage){$html.='<p><b>'.($i+1).'. '.$stage.'</b></p><table border="1" cellpadding="'.($final?'3':'4').'"><tr><th width="8%">No.</th><th width="'.($i===2?'32':'42').'%">Nama Peserta</th>'.($i===2?'<th width="22%">Penawaran</th>':'').'<th width="'.($i===2?'18':'25').'%">Hasil Evaluasi</th><th width="'.($i===2?'20':'25').'%">Keterangan</th></tr><tr><td>1</td><td>'.$provider.'</td>'.($i===2?'<td>'.$price.'</td>':'').'<td>LULUS</td><td></td></tr></table>';}
        if($final)$html.='<p><b>3. Hasil Negosiasi Biaya sebagai berikut:</b></p>'.$this->fields([['Nama Penyedia',$this->v($c,'nama_penyedia_resmi','nama_penyedia')],['Nilai Penawaran',$price],['Nilai Penawaran Terkoreksi',$price],['Nilai Negosiasi Biaya',$this->money($c['nilai_kontrak']??0)]]);
        $html.='<p>Demikian berita acara dibuat dan ditandatangani pada hari, tanggal, dan bulan sebagaimana tersebut di atas untuk dipergunakan sebagaimana mestinya.</p>';$pdf->writeHTML($html,true,false,true,false,'');$this->signature($pdf,'Pejabat Pengadaan Barang/Jasa',$this->v($this->user,'nama_pejabat_pengadaan','nama_user'),$this->v($this->user,'nip_pejabat_pengadaan','nip'));return$this->finish($pdf);
    }
    private function sppbj(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$date=$this->date($d,$c);$amount=$c['nilai_kontrak']??null;
        $pdf->writeHTML('<table><tr><td width="60%">Nomor: '.$this->e($d['nomor_dokumen']??'-').'<br>Lampiran: -</td><td width="40%">Pasangkayu, '.$this->e($date).'</td></tr></table><p>Kepada Yth.<br>Direktur '.$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia')).'<br>di-<br>'.$this->e($this->v($c,'alamat_penyedia','alamat')).'</p><p>Perihal: Penunjukan Penyedia untuk Pelaksanaan Paket Pekerjaan: '.$this->e($c['uraian_kontrak']).' - pada Kegiatan '.$this->e($this->v($c,'nama_sub_keg')).'</p>',true,false,true,false,'');
        $this->para($pdf,'Dengan ini kami beritahukan bahwa penawaran Saudara nomor: SPSE Tanggal '.$date.' perihal Penawaran Pekerjaan '.$c['uraian_kontrak'].'. dengan nilai Negoisasi Harga sebesar '.(isset($amount)?$this->money($amount).' ('.ucwords($this->words((int)round($amount))).' Rupiah)':'Belum ditetapkan').'. kami nyatakan diterima/disetujui.');
        $this->para($pdf,'Sebagai tindak lanjut dari Surat Penunjukan Penyedia Barang/Jasa (SPPBJ) ini Saudara diharuskan untuk menandatangani Surat Perjanjian paling lambat 14 (empat belas) hari kerja setelah diterbitkannya SPPBJ.');
        $this->para($pdf,'Penunjukan ini diberikan berdasarkan hasil evaluasi terhadap penawaran Saudara tersebut diatas, apabila Saudara tidak bersedia menerima penunjukan ini akan dikenakan sanksi sesuai ketentuan dalam Peraturan Presiden No. 16 Tahun 2018 tentang Pengadaan Barang/Jasa beserta petunjuk teknisnya.');
        $this->signature($pdf,'Pejabat Pembuat Komitmen',$this->v($c,'nama_ppk_resmi','nama_ppk'),$this->v($c,'nip_ppk_resmi','nip_ppk'));
        $pdf->writeHTML('<p>Tembusan Yth.:<br>1. Bupati Pasangkayu di Pasangkayu (sebagai laporan);<br>2. LPSE Pasangkayu di Pasangkayu<br>3. Pejabat Pengadaan<br>4. Pertinggal</p>',true,false,true,false,'');return $this->finish($pdf);
    }
    private function spmk(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,'Surat Perintah Mulai Kerja (SPMK)',$d['nomor_dokumen']??'');$pdf->SetFont($this->font,'',$this->size);$pdf->MultiCell(0,5,'Tanggal : '.$this->date($d,$c),0,'C');$pdf->SetFont($this->font,'B',$this->size+1);$pdf->MultiCell(0,6,"PAKET PEKERJAAN:\n".$this->v($c,'uraian_kontrak'),0,'C');$pdf->SetFont($this->font,'',$this->size);
        $pdf->writeHTML('<p>Yang bertanda tangan di bawah ini:</p>'.$this->fields([['Nama',$this->v($c,'nama_ppk_resmi','nama_ppk')],['Jabatan','PPK '.$this->v($this->user,'nama_opd')],['Alamat',$this->v($this->user,'alamat_opd')]]).'<p>Selanjutnya disebut Pejabat Pembuat Komitmen;</p><p>berdasarkan Surat Perintah Kerja (SPK)</p>'.$this->fields([['Nomor',$c['nomor_kontrak']??'-'],['Tanggal',$this->date($d,$c)]]).'<p>Bersama ini memerintahkan:</p>'.$this->fields([['Nama Penyedia',$this->v($c,'nama_penyedia_resmi','nama_penyedia')],['Alamat',$this->v($c,'alamat_penyedia','alamat')],['yang dalam hal ini diwakili oleh',$this->v($c,'direktur')]]).'<p>Selanjutnya disebut sebagai Penyedia;</p><p>untuk segera memulai pelaksanaan pekerjaan dengan memperhatikan ketentuan-ketentuan sebagai berikut:</p>'.$this->fields([['1. Macam Pekerjaan',$this->v($c,'jenis_pengadaan').' '.$this->v($c,'nama_sub_kegiatan','nama_sub_keg','kd_sub_keg')],['2. Tanggal Mulai Kerja',$this->date($d,$c)],['3. Syarat-Syarat Pekerjaan','sesuai dengan persyaratan dan ketentuan Kontrak'],['4. Waktu Penyelesaian',$this->v($c,'waktu_pelaksanaan').' ('.ucwords($this->words((int)($c['waktu_pelaksanaan']??0))).') Hari Kalender dan Pekerjaan sudah harus selesai pada tanggal '.$this->date(['tanggal_dokumen'=>$c['tanggal_selesai']??''],$c)],['5. Hasil Pekerjaan','1 (satu) paket '.$this->v($c,'uraian_kontrak').' sesuai Kerangka Acuan Kerja/Spesifikasi Teknis']]),true,false,true,false,'');
        $pdf->AddPage($this->orientation);$pdf->writeHTML($this->fields([['6. Denda','Terhadap keterlambatan penyerahan hasil kerja dan laporan akhir, pembayaran kepada Penyedia dapat dikenakan denda atau dihentikan sesuai ketentuan dalam SPK.']]),true,false,true,false,'');$pdf->writeHTML('<br><table><tr><td width="50%" align="center">Untuk dan atas nama<br>'.$this->e($this->v($this->user,'nama_opd')).'<br>Pejabat Pembuat Komitmen<br><br><br><b><u>'.$this->e($this->v($c,'nama_ppk_resmi','nama_ppk')).'</u></b><br>NIP. '.$this->e($this->v($c,'nip_ppk_resmi','nip_ppk')).'</td><td width="50%" align="center">Menerima dan Menyetujui<br>Untuk dan atas nama<br>'.$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia')).'<br><br><br><b><u>'.$this->e($this->v($c,'direktur')).'</u></b><br>Direktur</td></tr></table>',true,false,true,false,'');return$this->finish($pdf);
    }
    private function preContract(array $d,array $c,array $setup):string
    {
        $pdf=$this->page($setup);$this->title($pdf,"Berita Acara Rapat Persiapan\nPenandatanganan SPK dan Pelaksanaan Pekerjaan",$d['nomor_dokumen']??'');
        $this->para($pdf,'Pada hari ini '.$this->dayWords($d,$c).', yang bertanda tangan dibawah ini Pejabat Pembuat Komitmen pada '.$this->v($this->user,'nama_opd').' yang ditetapkan berdasarkan Surat Keputusan Pengguna Anggaran '.$this->v($this->user,'nama_opd').' tentang Penunjukan Pejabat Pembuat Komitmen '.$this->v($this->user,'nama_opd').' Tahun '.$c['tahun'].', diadakan Rapat Persiapan Penandatanganan Kontrak dan Persiapan Pelaksanaan Pekerjaan (Pre Award Meeting/Pre Construction) Pengadaan Jasa Konsultansi:',5);
        $pdf->writeHTML($this->fields(array_merge($this->package($c),[['Penyedia Jasa',$this->v($c,'nama_penyedia_resmi','nama_penyedia')],['Alamat',$this->v($c,'alamat_penyedia','alamat')]])).'<p>Pelaksanaan dan hasil rapat persiapan penandatanganan kontrak dan persiapan pelaksanaan pekerjaan penyedia jasa, berikut hal-hal pokok dan informasi yang diperoleh pada saat rapat tersebut adalah sebagai berikut:</p><ol><li>Rapat dipimpin oleh '.$this->e($this->v($c,'nama_ppk_resmi','nama_ppk')).' selaku PPK dan dihadiri oleh unsur Pengguna Anggaran/PPK, Penyedia Jasa/Rekanan, PPTK/Tim Teknis, serta Konsultan Supervisi/Pengawasan apabila ada.</li><li>Rapat dimulai Jam 10:00 Wita;</li><li>Nilai Kontrak termasuk Pajak Pertambahan Nilai (PPN) yang diperoleh berdasarkan total harga penawaran terkoreksi sebagaimana tercantum dalam Daftar Kuantitas dan Harga adalah sebesar '.$this->money($c['nilai_kontrak']??0).'.</li><li>Hasil pembahasan secara lengkap adalah terlampir;</li><li>Hasil rapat persiapan penandatanganan kontrak dan persiapan pelaksanaan pekerjaan ini beserta lampirannya merupakan bagian dari dokumen kontrak.</li></ol><p>Demikian Berita Acara ini dibuat dengan sebenar-benarnya untuk dipergunakan sebagaimana mestinya.</p>',true,false,true,false,'');$this->signatureBlock($pdf,'<table><tr><td width="50%" align="center">Menyetujui Penyedia Barang/Jasa<br>'.$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia')).'<br><br><br><b><u>'.$this->e($this->v($c,'direktur')).'</u></b><br>Direktur</td><td width="50%" align="center">Untuk dan atas nama<br>'.$this->e($this->v($this->user,'nama_opd')).'<br>Pejabat Pembuat Komitmen<br><br><br><b><u>'.$this->e($this->v($c,'nama_ppk_resmi','nama_ppk')).'</u></b><br>NIP. '.$this->e($this->v($c,'nip_ppk_resmi','nip_ppk')).'</td></tr></table>');
        $pdf->AddPage($this->orientation);$pdf->SetFont($this->font,'',$this->size);$pdf->writeHTML($this->fields([['Lampiran','Berita Acara Rapat Persiapan Penandatanganan SPK dan Persiapan Pelaksanaan Pekerjaan'],['Nomor',$d['nomor_dokumen']??'-'],['Tanggal',$this->date($d,$c)],['Pekerjaan',$this->v($c,'uraian_kontrak')]]),true,false,true,false,'');
        $agreements=['Nilai Kontrak termasuk Pajak Pertambahan Nilai (PPN) yang diperoleh berdasarkan total harga penawaran terkoreksi sebagaimana tercantum dalam Daftar Kuantitas dan Harga adalah sebesar '.$this->money($c['nilai_kontrak']??0).'.','Harga Satuan pekerjaan adalah sesuai hasil koreksi Aritmatik atau hasil Negosiasi Rekanan Penyedia Jasa pengadaan adalah tetap','Syarat-syarat umum SPK adalah tetap.','kerangka acuan kerja adalah tetap;','Berita Acara Rapat Persiapan ini merupakan bagian dari Dokumen Kontrak.'];$html='<table border="1" cellpadding="5"><tr style="font-weight:bold"><th width="8%">No.</th><th width="72%">KESEPAKATAN</th><th width="20%">PERNYATAAN PENYEDIA JASA</th></tr>';foreach($agreements as $i=>$agreement)$html.='<tr><td>'.($i+1).'</td><td>'.$this->e($agreement).'</td><td align="center">Setuju</td></tr>';$html.='</table><br><table border="1" cellpadding="6"><tr style="font-weight:bold"><th width="8%">No.</th><th width="42%">NAMA</th><th width="28%">JABATAN</th><th width="22%">TANDA TANGAN</th></tr><tr><td>1</td><td>'.$this->e($this->v($c,'nama_ppk_resmi','nama_ppk')).'<br>NIP. '.$this->e($this->v($c,'nip_ppk_resmi','nip_ppk')).'</td><td>Pengguna Jasa/Pejabat Pembuat Komitmen</td><td></td></tr><tr><td>2</td><td>'.$this->e($this->v($c,'direktur')).'</td><td>Direktur '.$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia')).'</td><td></td></tr><tr><td>3</td><td>'.$this->e($this->v($c,'nama_pptk')).'</td><td>PPTK/Tim Teknis</td><td></td></tr></table>';$pdf->writeHTML($html,true,false,true,false,'');return$this->finish($pdf);
    }
    private function dayWords(array $d,array $c):string
    {
        $t=strtotime($d['tanggal_dokumen']??$c['tanggal_kontrak']);$days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];$months=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return $days[(int)date('w',$t)].' tanggal '.ucwords($this->words((int)date('j',$t))).' Bulan '.$months[(int)date('n',$t)].' Tahun '.ucwords($this->words((int)date('Y',$t)));
    }
    private function words(int $n):string
    {
        $a=['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];if($n<12)return $a[$n];if($n<20)return $this->words($n-10).' belas';if($n<100)return $this->words(intdiv($n,10)).' puluh'.($n%10?' '.$this->words($n%10):'');if($n<200)return 'seratus'.($n%100?' '.$this->words($n%100):'');if($n<1000)return $this->words(intdiv($n,100)).' ratus'.($n%100?' '.$this->words($n%100):'');if($n<2000)return 'seribu'.($n%1000?' '.$this->words($n%1000):'');foreach([1000000000=>'miliar',1000000=>'juta',1000=>'ribu'] as $unit=>$label)if($n>=$unit)return $this->words(intdiv($n,$unit)).' '.$label.($n%$unit?' '.$this->words($n%$unit):'');return '';
    }
    private function pairedSignatures(array $c,bool $ppk=false):string
    {
        $name=$ppk?$this->v($c,'nama_ppk_resmi','nama_ppk'):$this->v($c,'nama_pejabat_pengadaan');$nip=$ppk?$this->v($c,'nip_ppk_resmi','nip_ppk'):$this->v($c,'nip_pejabat_pengadaan');$rank=$ppk?($c['pangkat_ppk']??''):($c['pangkat_pejabat_pengadaan']??'');
        return '<table nobr="true" cellpadding="4"><tr><td width="50%" align="center">Penyedia Barang/Jasa<br>'.$this->e($this->v($c,'nama_penyedia_resmi','nama_penyedia')).'<br><br><br><br><b><u>'.$this->e($this->v($c,'direktur')).'</u></b><br>'.$this->e($this->v($c,'jabatan_pelaksana')).'</td><td width="50%" align="center">'.($ppk?'Pejabat Pembuat Komitmen':'Pejabat Pengadaan Barang/Jasa').'<br><br><br><br><br><b><u>'.$this->e($name).'</u></b><br>NIP. '.$this->e($nip).'<br>'.$this->e($rank).'</td></tr></table>';
    }
}
