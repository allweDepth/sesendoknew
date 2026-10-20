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
    $isDecision=$this->isDecision($header);
    $pageBox=$this->pageBox($pdf);
    $startY=max((float)$pageBox['top'],(float)$pdf->GetY());
    $kop=$this->officialLetterhead();
    if(!empty($kop['gunakan_gambar_kop'])&&!empty($kop['gambar_kop'])){$full=dirname(__DIR__,2).'/'.ltrim($kop['gambar_kop'],'/');if(is_file($full)){$imageWidth=min(165.0,$pageBox['width']);$pdf->Image($full,$pageBox['left'],$startY-4,$imageWidth,30,'','','',false,300);$pdf->SetY($startY+28);$logo=null;}}
    if(empty($kop['gunakan_gambar_kop'])&&!empty($kop['logo_kiri'])){$custom=dirname(__DIR__,2).'/'.ltrim($kop['logo_kiri'],'/');if(is_file($custom))$logo=$custom;}
    $leftLogo=!empty($logo);
    $rightLogoPath='';
    if(empty($kop['gunakan_gambar_kop'])&&!empty($kop['logo_kanan'])){$right=dirname(__DIR__,2).'/'.ltrim($kop['logo_kanan'],'/');if(is_file($right))$rightLogoPath=$right;}
    $rightLogo=$rightLogoPath!=='';
    $logoSize=min(22.0,$pageBox['width']*.12);
    if($leftLogo){$level=error_reporting();error_reporting($level & ~E_DEPRECATED);$pdf->Image($logo,$pageBox['left'],$startY,$logoSize,$logoSize,'','','',false,300);error_reporting($level);}
    if($rightLogo)$pdf->Image($rightLogoPath,$pageBox['right']-$logoSize,$startY,$logoSize,$logoSize,'','','',false,300);
    $headerLeft=$pageBox['left']+($leftLogo?min($logoSize+3,$pageBox['width']*.2):0);
    $headerRight=$pageBox['right']-($rightLogo?min($logoSize+3,$pageBox['width']*.2):0);
    $headerWidth=max(1,$headerRight-$headerLeft);
    $pdf->SetFont($this->font,'B',$this->fs(2));
    $pdf->SetXY($headerLeft,$startY+1);
    $pdf->MultiCell($headerWidth,6,strtoupper($kop['nama_pemerintah']??$_SESSION['user']['nama_pemda']??'PEMERINTAH DAERAH'),0,'C');
    $pdf->SetFont($this->font,'B',$this->fs(4));
    $pdf->SetX($headerLeft);$pdf->MultiCell($headerWidth,7,strtoupper($kop['nama_opd']??$_SESSION['user']['nama_opd']??'PERANGKAT DAERAH'),0,'C');
    $pdf->SetFont($this->font,'',$this->fs(-2));
    $address=$kop['alamat']??$_SESSION['user']['alamat_opd']??'';$contact=trim(implode(' · ',array_filter([$kop['telepon']??null,$kop['email']??null,$kop['website']??null])));$pdf->SetX($headerLeft);$pdf->MultiCell($headerWidth,5,trim($address.($contact?' · '.$contact:'')),0,'C');
    $lineY=$startY+25;$rgb=$this->hexColor($kop['warna_garis']??'#000000');$pdf->SetDrawColor(...$rgb);$pdf->SetLineWidth(.7);$pdf->Line($pageBox['left'],$lineY,$pageBox['right'],$lineY);$pdf->SetLineWidth(.2);$pdf->Line($pageBox['left'],$lineY+1,$pageBox['right'],$lineY+1);$pdf->SetDrawColor(0,0,0);$pdf->SetY($startY+32);

    $pdf->SetFont($this->font,'B',$this->fs(2));
    $pdf->MultiCell(0,7,strtoupper($isDecision?$this->decisionTitle($header,$data):($header['jenis_naskah']??'NASKAH DINAS')),0,'C');
    $pdf->SetFont($this->font,'',$this->fs());
    $pdf->MultiCell(0,6,'NOMOR: '.($header['nomor']??'-'),0,'C');$pdf->Ln(5);
    $info=$isDecision?[]:[['Sifat',trim(($header['kode_keamanan']??'').' - '.($header['klasifikasi_keamanan']??''))],['Tanggal',$header['tanggal_surat']??''],['Hal',$header['perihal']??'']];
    foreach($info as [$label,$value]){if($value==='')continue;$pdf->SetFont($this->font,'',$this->fs());$pdf->Cell(25,6,$label,0,0);$pdf->Cell(4,6,':',0,0);$pdf->MultiCell(0,6,(string)$value,0,'L');}
    $pdf->Ln(4);
    if($isDecision){
      $pdf->MultiCell(0,6,'TENTANG',0,'C');
      $pdf->MultiCell(0,6,strtoupper($this->findValue($data,['tentang'])?:($header['perihal']??'')),0,'C');
      $pdf->Ln(5);
      $position=$this->findValue($data,['jabatan_penandatangan','jabatan_pejabat','jbt_pemberi_tgs']);
      if($position!=='')$pdf->MultiCell(0,6,strtoupper($position),0,'C');
      $pdf->Ln(5);
      $this->renderDecisionBody($pdf,$data,$schema);
    }else{$this->render($pdf,$schema,$data);}

    $penanda=$this->findValue($data,['nama_penandatangan','penanda_tangan','nama_pejabat','nama_pemberi_tugas']);
    $jabatan=$this->findValue($data,['jabatan_penandatangan','jabatan_pejabat','jbt_pemberi_tgs']);
    $pangkat=$this->findValue($data,['pangkat_penandatangan','pangkat_pemberi_tgs']);
    $nip=$this->findValue($data,['nip_penandatangan','nip_pemberi_tgs']);
    if($isDecision){
      $pdf->Ln(6);
      $this->renderAttachmentSignature($pdf,$data,$header);
      if(!empty($data['tembusan'])){
        $pdf->Ln(4);$pdf->SetFont($this->font,'',$this->fs());
        $pdf->MultiCell(0,6,'Tembusan: Kepada Yth.',0,'L');
        $options=$this->schemaRowOptions($schema,'tembusan');
        $rows=$this->decisionRows($data['tembusan'],$options['default_type']??'numbered');
        $this->renderCollection($pdf,'Tembusan',$rows,['URAIAN'],$options);
      }
    }else{
    if($penanda||$jabatan){$this->renderStandardSignature($pdf,$penanda,$jabatan,$pangkat,$nip);}
    }
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
    if(in_array($type,['editable_table','table'],true)){$options=is_array($item['row_options']??null)?$item['row_options']:[];$this->renderCollection($pdf,(string)$label,$raw,$item['columns']??[],$options);return;}
    $value=$this->stringValue($raw);if($value==='')return;
    if(in_array($type,['title','paragraph','textarea'],true)){$pdf->SetFont($this->font,$type==='title'?'B':'',$this->fs());$pdf->MultiCell(0,6,$value,0,$type==='title'?'C':'J');$pdf->Ln(2);return;}
    $pdf->SetFont($this->font,'',$this->fs());$pdf->Cell(42,6,(string)$label,0,0);$pdf->Cell(4,6,':',0,0);$pdf->MultiCell(0,6,$value,0,'L');
  }

  private function renderCollection(TCPDF $pdf,string $label,$rows,array $columns,array $rowOptions=[]):void
  {
    if(!is_array($rows)||$rows===[])return;
    $sequenceType=null;$sequenceNo=0;
    $defaultType=strtolower((string)($rowOptions['default_type']??'paragraph'));
    $defaultAlign=strtoupper((string)($rowOptions['default_align']??'justify'));
    $hangingIndent=($rowOptions['hanging_indent']??false)===true;
    $collectionX=$pdf->GetX();
    foreach($rows as $row){
      if(!is_array($row)){$text=$this->stringValue($row);}elseif(isset($row['text'])){$text=$this->stringValue($row['text']);}else{$parts=[];foreach($columns?:array_keys($row) as $column)if(!str_starts_with((string)$column,'_')&&!empty($row[$column]))$parts[]=ucwords(str_replace('_',' ',(string)$column)).': '.$this->stringValue($row[$column]);$text=implode('; ',$parts);}
      if($text==='')continue;
      $type=is_array($row)?strtolower((string)($row['type']??$defaultType)):$defaultType;
      $type=['bullet'=>'list','ordered'=>'numbered','letter'=>'alpha'][$type]??$type;
      $align=is_array($row)?strtoupper((string)($row['align']??$defaultAlign)):$defaultAlign;
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
        $pdf->SetFillColor(0,0,0);$pdf->Circle($x+($hangingIndent?4:2.2),$y+3,0.65,0,360,'F');
        $pdf->SetXY($x+8,$y);
      }elseif($marker!==''){
        $pdf->Cell(8,6,$marker,0,0,$hangingIndent?'R':'L');
      }
      // TCPDF menerapkan alignment dari metadata pada saat ekspor. Newline
      // terminal menandai akhir paragraf sehingga baris terakhir justify tetap
      // rata kiri dan tidak ikut direnggangkan.
      $renderText=$align==='J'?rtrim($text)."\n":$text;
      $pdf->MultiCell(0,6,$renderText,0,$align,false,1,'','',true,0,false,true,0,'T',false);
      if($hangingIndent)$pdf->SetX($collectionX);
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
    $rows=$data['nama_ditugaskan']??[];
    if(is_string($rows))$rows=json_decode($rows,true);
    if(!is_array($rows)||!array_is_list($rows)||!is_array($rows[0]??null))return;
    if(!$this->isDecision($header))return;
    $pdf->AddPage();$pdf->SetFont($this->font,'',$this->fs());
    $title=$this->escape((string)$this->decisionTitle($header,$data));$number=$this->escape((string)($header['nomor']??'-'));$date=$this->escape($this->decisionDate((string)($header['tanggal_surat']??'-')));$about=$this->escape((string)($this->findValue($data,['tentang'])?:($header['perihal']??'-')));
    $html='<table cellpadding="2"><tr><td width="14%">Lampiran</td><td width="3%">:</td><td width="83%">'.$title.'</td></tr><tr><td>Nomor</td><td>:</td><td>'.$number.'</td></tr><tr><td>Tanggal</td><td>:</td><td>'.$date.'</td></tr><tr><td>Tentang</td><td>:</td><td>'.$about.'</td></tr></table><hr>';
    if($this->truthy($data['bentuk_lampiran']??false)){
      $html.='<br><table border="1" cellpadding="5"><thead><tr style="font-weight:bold;text-align:center"><th width="7%">NO.</th><th width="35%">NAMA</th><th width="26%">JABATAN</th><th width="32%">KETERANGAN</th></tr></thead><tbody>';
      foreach($rows as $i=>$row){$name=$this->escape((string)($row['nama']??''));$rank=$this->escape((string)($row['pangkat']??''));$nip=$this->escape((string)($row['nip']??''));$position=$this->escape((string)($row['jabatan']??''));$note=$this->escape((string)($row['jabatan_sk']??$row['keterangan']??''));$html.='<tr><td width="7%">'.($i+1).'.</td><td width="35%">'.$name.($rank!==''?'<br>'.$rank:'').($nip!==''?'<br>NIP. '.$nip:'').'</td><td width="26%">'.$position.'</td><td width="32%">'.$note.'</td></tr>';}
      $html.='</tbody></table>';
    }else{
      foreach($rows as $i=>$row){$items=[['Nama',$row['nama']??''],['Pangkat/Gol',$row['pangkat']??''],['NIP',$row['nip']??''],['Jabatan',$row['jabatan']??''],['Keterangan',$row['jabatan_sk']??$row['keterangan']??'']];$html.='<br><table cellpadding="3"><tr><td width="6%">'.($i+1).'.</td><td width="94%"><table cellpadding="2">';foreach($items as [$label,$value])$html.='<tr><td width="22%">'.$label.'</td><td width="4%">:</td><td width="74%">'.$this->escape((string)$value).'</td></tr>';$html.='</table></td></tr></table>';}
    }
    $pdf->writeHTML($html,true,false,true,false,'');
    $this->renderAttachmentSignature($pdf,$data,$header);
  }

  private function isDecision(array $header):bool
  {
    $type=strtolower((string)($header['jenis_naskah']??''));
    return str_contains($type,'penetapan')||str_contains($type,'keputusan');
  }

  private function decisionTitle(array $header,array $data):string
  {
    $type=(string)($header['jenis_naskah']??'');
    if(str_contains(strtolower($type),'keputusan'))return $type;
    $position=$this->findValue($data,['jabatan_penandatangan','jabatan_pejabat','jbt_pemberi_tgs']);
    return trim('Keputusan '.$position);
  }

  private function decisionRows(mixed $value,string $defaultType='numbered'):array
  {
    if(is_string($value)){
      $decoded=json_decode($value,true);
      $value=is_array($decoded)?$decoded:[['text'=>$value]];
    }
    if(!is_array($value))return [];
    if(!array_is_list($value))$value=[$value];
    $rows=[];
    foreach($value as $row){
      if(!is_array($row))$row=['text'=>(string)$row];
      if(!isset($row['text']))$row['text']=$row['URAIAN']??$row['uraian']??'';
      if(!isset($row['type']))$row['type']=$defaultType;
      $rows[]=$row;
    }
    return $rows;
  }

  private function renderDecisionBody(TCPDF $pdf,array $data,array $schema):void
  {
    foreach(['menimbang'=>'Menimbang','mengingat'=>'Mengingat','menetapkan'=>'Menetapkan',
      'menetapkan_1'=>'KESATU','menetapkan_2'=>'KEDUA','menetapkan_3'=>'KETIGA','menetapkan_4'=>'KEEMPAT'] as $key=>$label){
      if($key==='menetapkan'){
        $pdf->Ln(3);$pdf->SetFont($this->font,'B',$this->fs());
        $pdf->MultiCell(0,6,'MEMUTUSKAN:',0,'C');$pdf->Ln(3);
      }
      $options=$this->schemaRowOptions($schema,$key);
      $defaultType=(string)($options['default_type']??(str_starts_with($key,'menetapkan')?'paragraph':'numbered'));
      $rows=$this->decisionRows($data[$key]??[],$defaultType);
      if(!$rows)continue;
      $margins=$pdf->getMargins();$left=(float)$margins['left'];$right=(float)$margins['right'];
      if($pdf->GetY()+12>$pdf->getPageHeight()-$pdf->getBreakMargin())$pdf->AddPage();
      $pdf->SetFont($this->font,'',$this->fs());
      $y=$pdf->GetY();$page=$pdf->getPage();
      $pdf->MultiCell(27,6,$label,0,'L');$labelBottom=$pdf->GetY();
      $pdf->SetXY($left+27,$y);$pdf->Cell(4,6,':',0,0);
      $pdf->SetLeftMargin($left+31);$pdf->SetX($left+31);
      $this->renderCollection($pdf,$label,$rows,['URAIAN'],$options);
      $pdf->SetLeftMargin($left);$pdf->SetRightMargin($right);$pdf->SetX($left);
      if($pdf->getPage()===$page)$pdf->SetY(max($labelBottom,$pdf->GetY()));
      $pdf->Ln(3);
    }
  }

  private function schemaRowOptions(array $schema,string $fieldName):array
  {
    foreach($schema as $item){
      if(!is_array($item))continue;
      if(($item['name']??'')===$fieldName&&is_array($item['row_options']??null))return $item['row_options'];
      foreach(['fields','sections'] as $key){
        if(isset($item[$key])&&is_array($item[$key])){
          $options=$this->schemaRowOptions($item[$key],$fieldName);
          if($options)return $options;
        }
      }
    }
    return [];
  }

  private function renderAttachmentSignature(TCPDF $pdf,array $data,array $header):void
  {
    $name=$this->findValue($data,['nama_penandatangan','penanda_tangan','nama_pejabat','nama_pemberi_tugas']);$position=$this->findValue($data,['jabatan_penandatangan','jabatan_pejabat','jbt_pemberi_tgs']);$rank=$this->findValue($data,['pangkat_penandatangan','pangkat_pemberi_tgs']);$nip=$this->findValue($data,['nip_penandatangan','nip_pemberi_tgs']);
    $location=$this->findValue($data,['tempat_ditetapkan'])?:($_SESSION['user']['nama_wilayah']??'');
    $positionSetting=(string)($this->pageSetup['signature_position']??'kanan');$pageBox=$this->pageBox($pdf);$box=$this->signatureBoxFromPageBox($pageBox,$positionSetting,min(75.0,$pageBox['width']*.4));$align=$this->signatureAlignment((string)($this->pageSetup['signature_alignment']??'C'));
    $height=max(10.0,(float)($this->pageSetup['signature_height']??35));$space=max(2,(int)round($height/7));$caption=trim((string)($this->pageSetup['signature_text']??''));$required=24+($space*6)+($rank!==''?5:0)+($nip!==''?5:0);
    if($pdf->GetY()+$required>$pdf->getPageHeight()-$pdf->getBreakMargin())$pdf->AddPage();
    $pdf->Ln(6);$pdf->SetXY($box['x'],$pdf->GetY());$pdf->SetFont($this->font,'',$this->fs());
    $pdf->MultiCell($box['width'],6,'Ditetapkan di '.$location,0,$align);
    $pdf->SetX($box['x']);$pdf->MultiCell($box['width'],6,'pada tanggal '.$this->decisionDate((string)($header['tanggal_surat']??'')),0,$align);
    $pdf->SetX($box['x']);$pdf->MultiCell($box['width'],6,$caption!==''?$caption:$position,0,$align);
    $pdf->Ln(($space-1)*6);$pdf->SetX($box['x']);$pdf->SetFont($this->font,'B',$this->fs());
    $pdf->MultiCell($box['width'],6,strtoupper($name),0,$align);
    if($rank!==''){$pdf->SetX($box['x']);$pdf->SetFont($this->font,'',$this->fs(-1));$pdf->MultiCell($box['width'],5,$rank,0,$align);}
    if($nip!==''){$pdf->SetX($box['x']);$pdf->MultiCell($box['width'],5,'NIP. '.$nip,0,$align);}
  }

  private function pageBox(TCPDF $pdf):array
  {
    $margins=$pdf->getMargins();$pageWidth=(float)$pdf->getPageWidth();
    $left=max(0.0,min($pageWidth,(float)($margins['left']??0)));
    $right=max($left,min($pageWidth,$pageWidth-(float)($margins['right']??0)));
    return ['left'=>$left,'right'=>$right,'width'=>$right-$left,'top'=>max(0.0,(float)($margins['top']??0))];
  }

  private function signatureBoxFromPageBox(array $pageBox,string $position,?float $requestedWidth=null):array
  {
    $width=min(max(1.0,(float)($pageBox['width']??1)),max(1.0,$requestedWidth??75.0));
    $left=(float)($pageBox['left']??0);$right=(float)($pageBox['right']??($left+$width));
    $x=match($position){'kiri'=>$left,'tengah'=>$left+($right-$left-$width)/2,default=>$right-$width};
    return ['x'=>max($left,min($right-$width,$x)),'width'=>$width];
  }

  private function signatureBox(TCPDF $pdf):array
  {
    return $this->signatureBoxFromPageBox($this->pageBox($pdf),(string)($this->pageSetup['signature_position']??'kanan'));
  }

  private function signatureAlignment(string $position):string
  {
    return in_array($position,['L','C','R','J'],true)?$position:'C';
  }

  private function renderStandardSignature(TCPDF $pdf,string $name,string $position,string $rank,string $nip):void
  {
    $layout=$this->signatureBox($pdf);$align=$this->signatureAlignment((string)($this->pageSetup['signature_alignment']??'C'));$height=max(10.0,(float)($this->pageSetup['signature_height']??35));
    $caption=trim((string)($this->pageSetup['signature_text']??''));$gap=max(8.0,$height-12.0);
    $required=9+6+$gap+6+($rank!==''?5:0)+($nip!==''?5:0);
    if($pdf->GetY()+$required>$pdf->getPageHeight()-$pdf->getBreakMargin())$pdf->AddPage();
    $pdf->Ln(9);$pdf->SetXY($layout['x'],$pdf->GetY());$pdf->SetFont($this->font,'',$this->fs());
    $pdf->MultiCell($layout['width'],6,$caption!==''?$caption:$position,0,$align);
    $pdf->Ln($gap);$pdf->SetX($layout['x']);$pdf->SetFont($this->font,'B',$this->fs());
    $pdf->MultiCell($layout['width'],6,strtoupper($name),0,$align);
    if($rank!==''){$pdf->SetX($layout['x']);$pdf->SetFont($this->font,'',$this->fs(-1));$pdf->MultiCell($layout['width'],5,$rank,0,$align);}
    if($nip!==''){$pdf->SetX($layout['x']);$pdf->SetFont($this->font,'',$this->fs(-1));$pdf->MultiCell($layout['width'],5,'NIP. '.$nip,0,$align);}
  }

  private function decisionDate(string $date):string
  {
    if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$date,$parts))return $date;
    if(!checkdate((int)$parts[2],(int)$parts[3],(int)$parts[1]))return $date;
    $months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return (int)$parts[3].' '.$months[(int)$parts[2]-1].' '.$parts[1];
  }

  private function truthy(mixed $value):bool{return in_array(strtolower(trim((string)$value)),['1','true','yes','on','tabel','table'],true);}
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
