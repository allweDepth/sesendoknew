<?php

class PdfTemplateService
{
  public function renderOfficial(TCPDF $pdf,array $header,array $schema,array $data,?string $logo=null):void
  {
    $kop=$this->officialLetterhead();
    if(!empty($kop['gunakan_gambar_kop'])&&!empty($kop['gambar_kop'])){$full=dirname(__DIR__,2).'/'.ltrim($kop['gambar_kop'],'/');if(is_file($full)){$pdf->Image($full,25,10,165,30,'','','',false,300);$pdf->SetY(42);$logo=null;}}
    if(empty($kop['gunakan_gambar_kop'])&&!empty($kop['logo_kiri'])){$custom=dirname(__DIR__,2).'/'.ltrim($kop['logo_kiri'],'/');if(is_file($custom))$logo=$custom;}
    if($logo){$level=error_reporting();error_reporting($level & ~E_DEPRECATED);$pdf->Image($logo,25,14,22,22,'','','',false,300);error_reporting($level);}
    if(empty($kop['gunakan_gambar_kop'])&&!empty($kop['logo_kanan'])){$right=dirname(__DIR__,2).'/'.ltrim($kop['logo_kanan'],'/');if(is_file($right))$pdf->Image($right,165,14,22,22,'','','',false,300);}
    $pdf->SetFont('times','B',13);
    $pdf->SetXY(50,15);
    $pdf->MultiCell(137,6,strtoupper($kop['nama_pemerintah']??$_SESSION['user']['nama_pemda']??'PEMERINTAH DAERAH'),0,'C');
    $pdf->SetFont('times','B',15);
    $pdf->SetX(50);$pdf->MultiCell(137,7,strtoupper($kop['nama_opd']??$_SESSION['user']['nama_opd']??'PERANGKAT DAERAH'),0,'C');
    $pdf->SetFont('times','',9);
    $address=$kop['alamat']??$_SESSION['user']['alamat_opd']??'';$contact=trim(implode(' · ',array_filter([$kop['telepon']??null,$kop['email']??null,$kop['website']??null])));$pdf->SetX(50);$pdf->MultiCell(137,5,trim($address.($contact?' · '.$contact:'')),0,'C');
    $rgb=$this->hexColor($kop['warna_garis']??'#000000');$pdf->SetDrawColor(...$rgb);$pdf->SetLineWidth(.7);$pdf->Line(25,39,190,39);$pdf->SetLineWidth(.2);$pdf->Line(25,40,190,40);$pdf->SetDrawColor(0,0,0);$pdf->Ln(8);

    $pdf->SetFont('times','B',13);
    $pdf->MultiCell(0,7,strtoupper($header['jenis_naskah']??'NASKAH DINAS'),0,'C');
    $pdf->SetFont('times','',11);
    $pdf->MultiCell(0,6,'NOMOR: '.($header['nomor']??'-'),0,'C');$pdf->Ln(5);
    $info=[['Sifat',trim(($header['kode_keamanan']??'').' - '.($header['klasifikasi_keamanan']??''))],['Tanggal',$header['tanggal_surat']??''],['Hal',$header['perihal']??'']];
    foreach($info as [$label,$value]){if($value==='')continue;$pdf->SetFont('times','',11);$pdf->Cell(25,6,$label,0,0);$pdf->Cell(4,6,':',0,0);$pdf->MultiCell(0,6,(string)$value,0,'L');}
    $pdf->Ln(4);
    $this->render($pdf,$schema,$data);

    $penanda=$this->findValue($data,['penanda_tangan','nama_penandatangan','nama_pejabat','nama_pemberi_tugas']);
    $jabatan=$this->findValue($data,['jabatan_penandatangan','jabatan_pejabat','jbt_pemberi_tgs']);
    if($penanda||$jabatan){$pdf->Ln(9);$pdf->SetX(112);$pdf->MultiCell(75,6,(string)$jabatan,0,'C');$pdf->Ln(16);$pdf->SetX(112);$pdf->SetFont('times','B',11);$pdf->MultiCell(75,6,strtoupper((string)$penanda),0,'C');}
    $pdf->SetAutoPageBreak(false);$pdf->SetY(-14);$pdf->SetFont('times','I',8);$pdf->Cell(0,5,'Dokumen elektronik seSendok - '.($header['workflow_status']??'draft').' - Halaman '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(),0,0,'C');
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
    if(in_array($field,['nomor','tanggal_surat','perihal','file','penandatangan','penanda_tangan','nama_penandatangan','jabatan_penandatangan','jabatan_pejabat','nama_pejabat','nama_pemberi_tugas','asn','bentuk_lampiran','disable','keterangan','jbt_pemberi_tgs'],true))return;
    if($type==='section'){$pdf->Ln(3);$pdf->SetFont('times','B',11);$pdf->MultiCell(0,6,strtoupper((string)$label),0,'C');return;}
    $raw=$field!==''?($data[$field]??''):($item['value']??$item['text']??'');
    if(in_array($type,['editable_table','table'],true)){$this->renderCollection($pdf,(string)$label,$raw,$item['columns']??[]);return;}
    $value=$this->stringValue($raw);if($value==='')return;
    if(in_array($type,['title','paragraph','textarea'],true)){$pdf->SetFont('times',$type==='title'?'B':'',11);$pdf->MultiCell(0,6,$value,0,$type==='title'?'C':'J');$pdf->Ln(2);return;}
    $pdf->SetFont('times','',11);$pdf->Cell(42,6,(string)$label,0,0);$pdf->Cell(4,6,':',0,0);$pdf->MultiCell(0,6,$value,0,'L');
  }

  private function renderCollection(TCPDF $pdf,string $label,$rows,array $columns):void
  {
    if(!is_array($rows)||$rows===[])return;
    $no=1;
    foreach($rows as $row){
      if(!is_array($row)){$text=$this->stringValue($row);}elseif(isset($row['text'])){$text=$this->stringValue($row['text']);}else{$parts=[];foreach($columns?:array_keys($row) as $column)if(!str_starts_with((string)$column,'_')&&!empty($row[$column]))$parts[]=ucwords(str_replace('_',' ',(string)$column)).': '.$this->stringValue($row[$column]);$text=implode('; ',$parts);}
      if($text==='')continue;
      $type=is_array($row)?($row['type']??'paragraph'):'paragraph';
      $align=is_array($row)?strtoupper((string)($row['align']??'justify')):'J';
      $align=['LEFT'=>'L','CENTER'=>'C','RIGHT'=>'R','JUSTIFY'=>'J'][$align]??'J';
      $styles=is_array($row)&&is_array($row['style']??null)?$row['style']:[];
      $font=(in_array('bold',$styles,true)?'B':'').(in_array('italic',$styles,true)?'I':'').(in_array('underline',$styles,true)?'U':'');
      $pdf->SetFont('times',$font,11);
      if(is_array($row)&&($row['format']??'')==='label'&&str_contains($text,':')){
        [$caption,$body]=array_pad(explode(':',$text,2),2,'');
        $text=trim($caption).' : '.trim($body);
      }
      if($type==='list'){$pdf->Cell(8,6,"\xE2\x80\xA2",0,0);}
      elseif($type==='numbered'){$pdf->Cell(8,6,$no++.'.',0,0);}
      else{$pdf->Cell(8,6,'',0,0);}
      // TCPDF MultiCell dengan J membiarkan baris terakhir mengikuti alignment
      // paragraf normal; tidak memakai stretch/force-justify.
      $pdf->MultiCell(0,6,$text,0,$align,false,1,'','',true,0,false,true,0,'T',false);
    }
    $pdf->Ln(2);
  }

  private function stringValue($value):string
  {
    if(is_bool($value))return $value?'Ya':'Tidak';
    if(is_array($value))return implode("\n",array_map(function($v){if(is_array($v)&&isset($v['text']))return (string)$v['text'];return is_scalar($v)?(string)$v:implode('; ',array_filter(array_map(fn($x)=>is_scalar($x)?(string)$x:'',$v)));},$value));
    if(!is_scalar($value))return '';
    return trim(html_entity_decode(strip_tags((string)$value),ENT_QUOTES|ENT_HTML5,'UTF-8'));
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
