<?php

class PdfTemplateService
{
  private string $font='times';
  private float $baseSize=11.0;
  private array $pageSetup=[];

  public function __construct(?array $pageSetup=null)
  {
    $this->pageSetup=$pageSetup??[];if($pageSetup){$this->font=(string)($pageSetup['font']??'times');$this->baseSize=max(6,min(18,(float)($pageSetup['font_size']??11)));}
  }

  private function fs(float $delta=0):float{return max(5,$this->baseSize+$delta);}
  public function renderOfficial(TCPDF $pdf,array $header,array $schema,array $data,?string $logo=null):void
  {
    $startY=max(14.0,(float)$pdf->GetY());
    $kop=$this->officialLetterhead();
    if(!empty($kop['gunakan_gambar_kop'])&&!empty($kop['gambar_kop'])){$full=dirname(__DIR__,2).'/'.ltrim($kop['gambar_kop'],'/');if(is_file($full)){$pdf->Image($full,25,$startY-4,165,30,'','','',false,300);$pdf->SetY($startY+28);$logo=null;}}
    if(empty($kop['gunakan_gambar_kop'])&&!empty($kop['logo_kiri'])){$custom=dirname(__DIR__,2).'/'.ltrim($kop['logo_kiri'],'/');if(is_file($custom))$logo=$custom;}
    if($logo){$level=error_reporting();error_reporting($level & ~E_DEPRECATED);$pdf->Image($logo,25,$startY,22,22,'','','',false,300);error_reporting($level);}
    if(empty($kop['gunakan_gambar_kop'])&&!empty($kop['logo_kanan'])){$right=dirname(__DIR__,2).'/'.ltrim($kop['logo_kanan'],'/');if(is_file($right))$pdf->Image($right,165,$startY,22,22,'','','',false,300);}
    $pdf->SetFont($this->font,'B',$this->fs(2));
    $pdf->SetXY(50,$startY+1);
    $pdf->MultiCell(137,6,strtoupper($kop['nama_pemerintah']??$_SESSION['user']['nama_pemda']??'PEMERINTAH DAERAH'),0,'C');
    $pdf->SetFont($this->font,'B',$this->fs(4));
    $pdf->SetX(50);$pdf->MultiCell(137,7,strtoupper($kop['nama_opd']??$_SESSION['user']['nama_opd']??'PERANGKAT DAERAH'),0,'C');
    $pdf->SetFont($this->font,'',$this->fs(-2));
    $address=$kop['alamat']??$_SESSION['user']['alamat_opd']??'';$contact=trim(implode(' · ',array_filter([$kop['telepon']??null,$kop['email']??null,$kop['website']??null])));$pdf->SetX(50);$pdf->MultiCell(137,5,trim($address.($contact?' · '.$contact:'')),0,'C');
    $rgb=$this->hexColor($kop['warna_garis']??'#000000');$pdf->SetDrawColor(...$rgb);$pdf->SetLineWidth(.7);$pdf->Line(25,$startY+25,190,$startY+25);$pdf->SetLineWidth(.2);$pdf->Line(25,$startY+26,190,$startY+26);$pdf->SetDrawColor(0,0,0);$pdf->SetY($startY+32);

    $pdf->SetFont($this->font,'B',$this->fs(2));
    $pdf->MultiCell(0,7,strtoupper($header['jenis_naskah']??'NASKAH DINAS'),0,'C');
    $pdf->SetFont($this->font,'',$this->fs());
    $pdf->MultiCell(0,6,'NOMOR: '.($header['nomor']??'-'),0,'C');$pdf->Ln(5);
    $info=[['Sifat',trim(($header['kode_keamanan']??'').' - '.($header['klasifikasi_keamanan']??''))],['Tanggal',$header['tanggal_surat']??''],['Hal',$header['perihal']??'']];
    foreach($info as [$label,$value]){if($value==='')continue;$pdf->SetFont($this->font,'',$this->fs());$pdf->Cell(25,6,$label,0,0);$pdf->Cell(4,6,':',0,0);$pdf->MultiCell(0,6,(string)$value,0,'L');}
    $pdf->Ln(4);
    $this->render($pdf,$schema,$data);

    $penanda=$this->findValue($data,['nama_penandatangan','penanda_tangan','nama_pejabat','nama_pemberi_tugas']);
    $jabatan=$this->findValue($data,['jabatan_penandatangan','jabatan_pejabat','jbt_pemberi_tgs']);
    $pangkat=$this->findValue($data,['pangkat_penandatangan','pangkat_pemberi_tgs']);
    $nip=$this->findValue($data,['nip_penandatangan','nip_pemberi_tgs']);
    if($penanda||$jabatan){$height=max(10,(float)($this->pageSetup['signature_height']??35));$position=(string)($this->pageSetup['signature_position']??'kanan');$x=$position==='kiri'?25:($position==='tengah'?70:112);$caption=trim((string)($this->pageSetup['signature_text']??''));$pdf->Ln(9);$pdf->SetX($x);$pdf->SetFont($this->font,'',$this->fs());$pdf->MultiCell(75,6,$caption!==''?$caption:(string)$jabatan,0,'C');$pdf->Ln(max(8,$height-12));$pdf->SetX($x);$pdf->SetFont($this->font,'B',$this->fs());$pdf->MultiCell(75,6,strtoupper((string)$penanda),0,'C');if($pangkat!==''){$pdf->SetX($x);$pdf->SetFont($this->font,'',$this->fs(-1));$pdf->MultiCell(75,5,(string)$pangkat,0,'C');}if($nip!==''){$pdf->SetX($x);$pdf->SetFont($this->font,'',$this->fs(-1));$pdf->MultiCell(75,5,'NIP. '.(string)$nip,0,'C');}}
    $this->renderAssignmentAttachment($pdf,$header,$data);
  }

  public function render(TCPDF $pdf, array $schema, array $data)
  {
    foreach ($schema as $item) {
      $this->renderItem($pdf,$item,$data);
    }
  }

  private function renderItem(TCPDF $pdf,array $item,array $data):void
  {
    $type=$item['type']??'text';
    if($type==='message')return;
    if($type==='fields'){foreach(($item['fields']??[]) as $child)$this->renderItem($pdf,$child,$data);return;}
    $field=$item['field']??$item['name']??'';$label=$item['label']??strtoupper((string)$field);
    if(in_array($field,['nomor','tanggal_surat','perihal','file','penandatangan','penanda_tangan','nama_penandatangan','jabatan_penandatangan','jabatan_pejabat','nama_pejabat','nama_pemberi_tugas','pangkat_penandatangan','pangkat_pemberi_tgs','nip_penandatangan','nip_pemberi_tgs','asn','nama_ditugaskan','bentuk_lampiran','disable','keterangan','jbt_pemberi_tgs'],true))return;
    if($type==='section'){$pdf->Ln(3);$pdf->SetFont($this->font,'B',$this->fs());$pdf->MultiCell(0,6,strtoupper((string)$label),0,'C');return;}
    $raw=$field!==''?($data[$field]??''):($item['value']??$item['text']??'');
    if(in_array($type,['editable_table','table'],true)){$this->renderCollection($pdf,(string)$label,$raw,$item['columns']??[]);return;}
    $value=$this->stringValue($raw);if($value==='')return;
    if(in_array($type,['title','paragraph','textarea'],true)){$pdf->SetFont($this->font,$type==='title'?'B':'',$this->fs());$pdf->MultiCell(0,6,$value,0,$type==='title'?'C':'J');$pdf->Ln(2);return;}
    $pdf->SetFont($this->font,'',$this->fs());$pdf->Cell(42,6,(string)$label,0,0);$pdf->Cell(4,6,':',0,0);$pdf->MultiCell(0,6,$value,0,'L');
  }

  private function renderCollection(TCPDF $pdf,string $label,$rows,array $columns):void
  {
    if(!is_array($rows)||$rows===[])return;
    $sequenceType=null;$sequenceNo=0;
    foreach($rows as $row){
      if(!is_array($row)){$text=$this->stringValue($row);}elseif(isset($row['text'])){$text=$this->stringValue($row['text']);}else{$parts=[];foreach($columns?:array_keys($row) as $column)if(!str_starts_with((string)$column,'_')&&!empty($row[$column]))$parts[]=ucwords(str_replace('_',' ',(string)$column)).': '.$this->stringValue($row[$column]);$text=implode('; ',$parts);}
      if($text==='')continue;
      $type=is_array($row)?strtolower((string)($row['type']??'paragraph')):'paragraph';
      $type=['bullet'=>'list','ordered'=>'numbered','letter'=>'alpha'][$type]??$type;
      $align=is_array($row)?strtoupper((string)($row['align']??'justify')):'J';
      $align=['L'=>'L','C'=>'C','R'=>'R','J'=>'J','LEFT'=>'L','CENTER'=>'C','RIGHT'=>'R','JUSTIFY'=>'J'][$align]??'J';
      $styles=[];
      if(is_array($row)){
        $rawStyles=$row['style']??[];
        $styles=is_array($rawStyles)?$rawStyles:array_filter(array_map('trim',explode(',',(string)$rawStyles)));
      }
      $font=(in_array('bold',$styles,true)?'B':'').(in_array('italic',$styles,true)?'I':'').(in_array('underline',$styles,true)?'U':'');
      $pdf->SetFont($this->font,$font,$this->fs());
      if(is_array($row)&&($row['format']??'')==='label'&&str_contains($text,':')){[$caption,$body]=array_pad(explode(':',$text,2),2,'');$text=trim($caption).' : '.trim($body);}

      $marker='';
      if(in_array($type,['numbered','alpha'],true)){
        if($sequenceType!==$type){$sequenceType=$type;$sequenceNo=1;}else{$sequenceNo++;}
        $marker=$type==='alpha'?$this->alphaMarker($sequenceNo).'.':$sequenceNo.'.';
      }elseif($type==='list'){
        $sequenceType='list';$sequenceNo=0;$marker="\xE2\x80\xA2";
      }else{
        // Paragraf memutus rangkaian nomor/abjad. Baris numbered berikutnya mulai lagi dari 1/a.
        $sequenceType=null;$sequenceNo=0;
      }
      if($type==='list'){
        // Core font TCPDF tidak selalu memiliki glyph bullet. Gambar titik agar
        // hasilnya konsisten pada semua font PDF yang dapat dipilih pengguna.
        $x=$pdf->GetX();$y=$pdf->GetY();
        $pdf->SetFillColor(0,0,0);$pdf->Circle($x+2.2,$y+3,0.65,0,360,'F');
        $pdf->SetXY($x+8,$y);
      }elseif($marker!==''){
        $pdf->Cell(8,6,$marker,0,0,'L');
      }
      // TCPDF menerapkan alignment dari metadata pada saat ekspor. Newline
      // terminal menandai akhir paragraf sehingga baris terakhir justify tetap
      // rata kiri dan tidak ikut direnggangkan.
      $renderText=$align==='J'?rtrim($text)."\n":$text;
      $pdf->MultiCell(0,6,$renderText,0,$align,false,1,'','',true,0,false,true,0,'T',false);
    }
    $pdf->Ln(2);
  }

  private function alphaMarker(int $number):string
  {
    $value='';
    while($number>0){$number--; $value=chr(97+($number%26)).$value; $number=intdiv($number,26);}
    return $value;
  }

  /** Nama yang ditugaskan pada SK selalu dicetak sebagai lampiran halaman baru. */
  private function renderAssignmentAttachment(TCPDF $pdf,array $header,array $data):void
  {
    $rows=$data['nama_ditugaskan']??[];if(!is_array($rows)||!array_is_list($rows)||!is_array($rows[0]??null))return;
    $jenis=strtolower((string)($header['jenis_naskah']??''));if(!str_contains($jenis,'penetapan')&&!str_contains($jenis,'keputusan'))return;
    $pdf->AddPage();$pdf->SetFont($this->font,'',$this->fs());
    $title=$this->escape((string)($header['jenis_naskah']??'Keputusan Kepala Dinas'));$number=$this->escape((string)($header['nomor']??'-'));$date=$this->escape((string)($header['tanggal_surat']??'-'));$about=$this->escape((string)($header['perihal']??'-'));
    $html='<table cellpadding="2"><tr><td width="14%">Lampiran</td><td width="3%">:</td><td width="83%">'.$title.'</td></tr><tr><td>Nomor</td><td>:</td><td>'.$number.'</td></tr><tr><td>Tanggal</td><td>:</td><td>'.$date.'</td></tr><tr><td>Tentang</td><td>:</td><td>'.$about.'</td></tr></table><hr>';
    if($this->truthy($data['bentuk_lampiran']??false)){
      $html.='<br><table border="1" cellpadding="5"><thead><tr style="font-weight:bold;text-align:center"><th width="7%">NO.</th><th width="35%">NAMA</th><th width="26%">JABATAN</th><th width="32%">KETERANGAN</th></tr></thead><tbody>';
      foreach($rows as $i=>$row){$name=$this->escape((string)($row['nama']??''));$rank=$this->escape((string)($row['pangkat']??''));$nip=$this->escape((string)($row['nip']??''));$position=$this->escape((string)($row['jabatan']??''));$note=$this->escape((string)($row['jabatan_sk']??$row['keterangan']??''));$html.='<tr><td width="7%">'.($i+1).'.</td><td width="35%">'.$name.($rank!==''?'<br>'.$rank:'').($nip!==''?'<br>NIP. '.$nip:'').'</td><td width="26%">'.$position.'</td><td width="32%">'.$note.'</td></tr>';}
      $html.='</tbody></table>';
    }else{
      foreach($rows as $i=>$row){$items=[['Nama',$row['nama']??''],['Pangkat/Gol',$row['pangkat']??''],['NIP',$row['nip']??''],['Jabatan',$row['jabatan']??''],['Keterangan',$row['jabatan_sk']??$row['keterangan']??'']];$html.='<br><table cellpadding="3"><tr><td width="6%">'.($i+1).'.</td><td width="94%"><table cellpadding="2">';foreach($items as [$label,$value])$html.='<tr><td width="22%">'.$label.'</td><td width="4%">:</td><td width="74%">'.$this->escape((string)$value).'</td></tr>';$html.='</table></td></tr></table>';}
    }
    $html.='<br><br>'.$this->attachmentSignature($data);$pdf->writeHTML($html,true,false,true,false,'');
  }

  private function attachmentSignature(array $data):string
  {
    $name=$this->escape($this->findValue($data,['nama_penandatangan','penanda_tangan','nama_pejabat','nama_pemberi_tugas']));$position=$this->escape($this->findValue($data,['jabatan_penandatangan','jabatan_pejabat','jbt_pemberi_tgs']));$rank=$this->escape($this->findValue($data,['pangkat_penandatangan','pangkat_pemberi_tgs']));$nip=$this->escape($this->findValue($data,['nip_penandatangan','nip_pemberi_tgs']));
    $where=(string)($this->pageSetup['signature_position']??'kanan');$left=$where==='kiri'?'0%':($where==='tengah'?'30%':'60%');$space=max(2,(int)round(((float)($this->pageSetup['signature_height']??35))/7));$caption=$this->escape(trim((string)($this->pageSetup['signature_text']??'')));return '<table cellpadding="2"><tr><td width="'.$left.'"></td><td width="40%">Ditetapkan di '.htmlspecialchars((string)($_SESSION['user']['nama_wilayah']??'Pasangkayu'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'<br>'.($caption!==''?$caption:$position).'<br>'.str_repeat('<br>',$space).'<b><u>'.strtoupper($name).'</u></b>'.($rank!==''?'<br>'.$rank:'').($nip!==''?'<br>NIP. '.$nip:'').'</td></tr></table>';
  }

  private function truthy(mixed $value):bool{return in_array(strtolower(trim((string)$value)),['1','true','yes','on','tabel'],true);}
  private function escape(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}

  private function stringValue($value):string
  {
    if(is_bool($value))return $value?'Ya':'Tidak';
    if(is_array($value))return implode("\n",array_map(function($v){if(is_array($v)&&isset($v['text']))return (string)$v['text'];return is_scalar($v)?(string)$v:implode('; ',array_filter(array_map(fn($x)=>is_scalar($x)?(string)$x:'',$v)));},$value));
    if(!is_scalar($value))return '';
    $text=html_entity_decode(strip_tags((string)$value),ENT_QUOTES|ENT_HTML5,'UTF-8');return trim(str_replace("\xC2\xA0",' ',$text));
  }

  private function findValue(array $data,array $keys):string
  {
    foreach($keys as $key)if(!empty($data[$key]))return $this->stringValue($data[$key]);
    return '';
  }

  private function officialLetterhead():array
  {
    try{$u=$_SESSION['user']??[];$row=DB::getInstance()->query('SELECT * FROM kop_surat_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND aktif=1 AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$u['kd_wilayah']??'', $u['kd_opd']??'', $u['tahun']??date('Y')])->fetch();return $row?:[];}catch(Throwable $e){return [];}
  }
  private function hexColor(string $hex):array{$hex=ltrim($hex,'#');if(!preg_match('/^[0-9a-f]{6}$/i',$hex))return [0,0,0];return [hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))];}
}
