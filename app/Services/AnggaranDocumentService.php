<?php
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;use PhpOffice\PhpSpreadsheet\Writer\Xlsx;use PhpOffice\PhpSpreadsheet\Style\Alignment;use PhpOffice\PhpSpreadsheet\Style\Border;

class AnggaranDocumentService
{
    private DB $db; private array $user;
    private const TABLES=['rkpd'=>'rkpd_neo','renja'=>'renja_neo','rka'=>'rka_neo','dpa'=>'dpa_neo','rkpd_p'=>'rkpd_p_neo','renja_p'=>'renja_p_neo','rka_p'=>'rka_p_neo','dppa'=>'dppa_neo'];
    public function __construct(array $user){$this->db=DB::getInstance();$this->user=$user;}
    private function table(string $logical):string{if(!isset(self::TABLES[$logical]))throw new InvalidArgumentException('Dokumen anggaran tidak valid');return self::TABLES[$logical];}
    private function scope(string $alias='a'):array{$w=$this->user['kd_wilayah']??'';$o=$this->user['kd_opd']??'';$y=(int)($this->user['tahun']??0);if(!$w||!$y)throw new RuntimeException('Scope pengguna tidak lengkap');$sql="$alias.kd_wilayah=? AND $alias.tahun=? AND $alias.is_deleted=0";$p=[$w,$y];if($o&&$o!=='0'){$sql.=" AND $alias.kd_opd=?";$p[]=$o;}return[$sql,$p];}
    public function groups(string $logical):array
    {
        $table=$this->table($logical);[$scope,$params]=$this->scope();$amount=str_starts_with($logical,'rkpd')?'a.pagu':'a.jumlah';$description=str_starts_with($logical,'rkpd')?'MAX(a.indikator)':'MAX(a.uraian)';
        $prefix="CONVERT(a.kd_sub_keg USING utf8mb4) COLLATE utf8mb4_general_ci";
        return $this->db->query("SELECT a.kd_sub_keg,COALESCE(r.uraian,$description) nama_sub_kegiatan,COUNT(*) jumlah_uraian,COALESCE(SUM($amount),0) total,MAX(a.setujui) setujui,MAX(a.kunci) kunci,
          (SELECT CONCAT(x.kode,' ',x.uraian) FROM rekening_kegiatan x WHERE x.level='program' AND $prefix LIKE CONCAT(x.kode,'.%') COLLATE utf8mb4_general_ci ORDER BY CHAR_LENGTH(x.kode) DESC LIMIT 1) program,
          (SELECT CONCAT(x.kode,' ',x.uraian) FROM rekening_kegiatan x WHERE x.level='kegiatan' AND $prefix LIKE CONCAT(x.kode,'.%') COLLATE utf8mb4_general_ci ORDER BY CHAR_LENGTH(x.kode) DESC LIMIT 1) kegiatan,
          (SELECT CONCAT(x.kode,' ',x.uraian) FROM rekening_kegiatan x WHERE x.level='bidang' AND $prefix LIKE CONCAT(x.kode,'.%') COLLATE utf8mb4_general_ci ORDER BY CHAR_LENGTH(x.kode) DESC LIMIT 1) bidang
          FROM `$table` a LEFT JOIN rekening_kegiatan r ON r.kode=$prefix AND r.level='sub_kegiatan' WHERE $scope GROUP BY a.kd_sub_keg,r.uraian ORDER BY a.kd_sub_keg",$params)->fetchAll();
    }
    public function details(string $logical,string $code):array
    {
        $table=$this->table($logical);[$scope,$params]=$this->scope();$params[]=$code;
        if(str_starts_with($logical,'rkpd'))$select='a.id,a.kd_sub_keg,a.indikator AS uraian,a.target AS volume,a.pagu AS jumlah,a.lokasi,a.kelompok_sasaran,a.kunci,a.setujui,a.keterangan';
        else {$columns=array_column($this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field');$awal=in_array('jumlah_awal',$columns,true)?'a.volume_awal,a.harga_satuan_awal,a.jumlah_awal,':'NULL volume_awal,NULL harga_satuan_awal,NULL jumlah_awal,';$select="a.id,a.kd_sub_keg,a.kd_akun,a.kel_rek,a.jenis_kelompok,a.kelompok,a.komponen,a.spesifikasi,a.uraian,a.volume,a.harga_satuan,a.jumlah,$awal a.sumber_dana_id,a.kunci,a.setujui,a.keterangan";}
        return $this->db->query("SELECT $select FROM `$table` a WHERE $scope AND a.kd_sub_keg=? ORDER BY a.id",$params)->fetchAll();
    }

    private function isChange(string $logical):bool{return in_array($logical,['rkpd_p','renja_p','rka_p','dppa'],true);}
    private function documentLabel(string $logical):string{return match($logical){'renja'=>'RENJA','rka'=>'RKA','dpa'=>'DPA','renja_p'=>'RENJA PERUBAHAN','rka_p'=>'RKA PERUBAHAN','dppa'=>'DPPA',default=>strtoupper($logical)};}
    private function e(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private function money(mixed $value):string{return number_format((float)$value,2,',','.');}
    private function metadataRows(array $group):string
    {
        $opd=$this->user['nama_opd']??$this->user['opd']??$this->user['kd_opd']??'-';$year=(int)($this->user['tahun']??0);
        $rows=[['Urusan Pemerintahan',$group['bidang']??'-'],['Program',$group['program']??'-'],['Kegiatan',$group['kegiatan']??'-'],['Organisasi',$opd],['Sub Kegiatan',($group['kd_sub_keg']??'').' '.($group['nama_sub_kegiatan']??'')],['Tahun Anggaran',(string)$year]];
        $html='';foreach($rows as [$label,$value])$html.='<tr><td width="23%"><b>'.$this->e($label).'</b></td><td width="2%">:</td><td width="75%">'.$this->e($value).'</td></tr>';return $html;
    }

    public function exportPdf(string $logical):string
    {
        $groups=$this->groups($logical);$change=$this->isChange($logical);$orientation=$change?'L':'P';
        require_once __DIR__.'/../../vendor/tecnickcom/tcpdf/tcpdf.php';
        $pdf=new TCPDF($orientation,'mm','A4',true,'UTF-8',false);$pdf->SetCreator('seSendok');$pdf->SetAuthor((string)($this->user['nama_opd']??'Pemerintah Daerah'));$pdf->SetTitle($this->documentLabel($logical));$pdf->setPrintHeader(false);$pdf->setPrintFooter(true);$pdf->SetMargins(8,8,8);$pdf->SetAutoPageBreak(true,12);
        if(!$groups){$pdf->AddPage();$pdf->SetFont('helvetica','B',12);$pdf->Cell(0,10,'TIDAK ADA DATA DALAM LINGKUP PENGGUNA',0,1,'C');return $pdf->Output('','S');}
        foreach($groups as $group){$details=$this->details($logical,(string)$group['kd_sub_keg']);$pdf->AddPage();$pdf->SetFont('helvetica','',7.2);
            $title=$change?'DOKUMEN PELAKSANAAN PERGESERAN ANGGARAN':'DOKUMEN PELAKSANAAN ANGGARAN<br>SATUAN KERJA PERANGKAT DAERAH';
            $form=$change?'FORMULIR '.$this->documentLabel($logical).'<br>RINCIAN BELANJA SKPD':'FORMULIR '.$this->documentLabel($logical).'<br>RINCIAN BELANJA SKPD';
            $html='<table border="1" cellpadding="3"><tr><td width="72%" align="center" style="font-size:12px"><b>'.$title.'</b></td><td width="28%" align="center" style="font-size:10px"><b>'.$form.'</b></td></tr></table>';
            $html.='<table border="1" cellpadding="2">'.$this->metadataRows($group).'</table>';
            $html.='<table border="1" cellpadding="2"><tr><td width="25%"><b>Indikator</b></td><td width="45%"><b>Tolak Ukur Kinerja</b></td><td width="30%"><b>Target Kinerja</b></td></tr><tr><td>Capaian Sub Kegiatan</td><td>'.$this->e($group['nama_sub_kegiatan']??'-').'</td><td>'.$this->e(count($details)).' rincian belanja</td></tr></table>';
            if($change){$html.='<table border="1" cellpadding="2"><thead><tr style="background-color:#e8e8e8;font-weight:bold"><th width="11%" rowspan="2">Kode Rekening</th><th width="25%" rowspan="2">Uraian</th><th width="25%" colspan="3" align="center">Sebelum Perubahan</th><th width="25%" colspan="3" align="center">Sesudah Perubahan</th><th width="14%" rowspan="2">Bertambah/<br>(Berkurang)</th></tr><tr style="background-color:#e8e8e8;font-weight:bold"><th width="7%">Volume</th><th width="8%">Harga</th><th width="10%">Jumlah</th><th width="7%">Volume</th><th width="8%">Harga</th><th width="10%">Jumlah</th></tr></thead><tbody>';
                $before=0;$after=0;foreach($details as $d){$ba=(float)($d['jumlah_awal']??0);$aa=(float)($d['jumlah']??0);$before+=$ba;$after+=$aa;$html.='<tr><td>'.$this->e($d['kd_akun']??'-').'</td><td><b>'.$this->e($d['komponen']??$d['uraian']??'-').'</b><br>'.$this->e($d['spesifikasi']??'').'</td><td align="right">'.$this->money($d['volume_awal']??0).'</td><td align="right">'.$this->money($d['harga_satuan_awal']??0).'</td><td align="right">'.$this->money($ba).'</td><td align="right">'.$this->money($d['volume']??0).'</td><td align="right">'.$this->money($d['harga_satuan']??0).'</td><td align="right">'.$this->money($aa).'</td><td align="right">'.$this->money($aa-$ba).'</td></tr>';}$html.='<tr style="font-weight:bold"><td colspan="2">Jumlah Anggaran Sub Kegiatan</td><td colspan="3" align="right">'.$this->money($before).'</td><td colspan="3" align="right">'.$this->money($after).'</td><td align="right">'.$this->money($after-$before).'</td></tr></tbody></table>';
            }else{$html.='<table border="1" cellpadding="2"><thead><tr style="background-color:#e8e8e8;font-weight:bold"><th width="16%">Kode Rekening</th><th width="37%">Uraian</th><th width="12%">Koefisien/Volume</th><th width="10%">Satuan</th><th width="12%">Harga</th><th width="13%">Jumlah</th></tr></thead><tbody>';$total=0;foreach($details as $d){$total+=(float)($d['jumlah']??0);$html.='<tr><td>'.$this->e($d['kd_akun']??'-').'</td><td><b>'.$this->e($d['komponen']??$d['uraian']??'-').'</b><br>'.$this->e($d['spesifikasi']??'').'</td><td align="right">'.$this->money($d['volume']??0).'</td><td>'.$this->e($d['sat_1']??'-').'</td><td align="right">'.$this->money($d['harga_satuan']??0).'</td><td align="right">'.$this->money($d['jumlah']??0).'</td></tr>';}$html.='<tr style="font-weight:bold"><td colspan="5">Jumlah Anggaran Sub Kegiatan</td><td align="right">'.$this->money($total).'</td></tr></tbody></table>';}
            $signature=$this->e($this->user['nama_lengkap']??$this->user['nama']??'KEPALA SKPD');$html.='<br><table cellpadding="3"><tr><td width="50%"><b>Rencana Penarikan Dana per Bulan</b><br>Januari s.d. Desember sesuai jadwal pelaksanaan kegiatan.</td><td width="50%" align="center">'.date('d-m-Y').'<br>Kepala SKPD<br><br><br><b><u>'.$signature.'</u></b><br>NIP. ................................</td></tr></table><br><table border="1" cellpadding="2"><tr style="font-weight:bold;background-color:#e8e8e8"><td width="8%">No.</td><td width="42%">Nama</td><td width="25%">NIP</td><td width="25%">Jabatan</td></tr><tr><td>1</td><td></td><td></td><td>Tim Anggaran Pemerintah Daerah</td></tr></table>';
            $pdf->writeHTML($html,true,false,true,false,'');
        }return $pdf->Output('','S');
    }
    public function exportExcel(string $logical):string
    {
        $groups=$this->groups($logical);$book=new Spreadsheet();$book->removeSheetByIndex(0);$used=[];$change=$this->isChange($logical);
        foreach($groups as $index=>$group){$code=(string)$group['kd_sub_keg'];$name=str_replace(['\\','/','?','*','[',']',':'],'-',substr($code,0,25))?:'SubKegiatan-'.($index+1);while(isset($used[$name]))$name=substr($name,0,28).'-'.($index+1);$used[$name]=1;$sheet=$book->createSheet();$sheet->setTitle($name);$details=$this->details($logical,$code);$last=$change?'I':'F';$sheet->mergeCells("A1:{$last}1")->setCellValue('A1','DOKUMEN PELAKSANAAN '.($change?'PERGESERAN ':'').'ANGGARAN — '.$this->documentLabel($logical));$sheet->mergeCells("A2:{$last}2")->setCellValue('A2','FORMULIR RINCIAN BELANJA SKPD');$sheet->mergeCells("A3:{$last}3")->setCellValue('A3','Sub Kegiatan: '.$code.' '.($group['nama_sub_kegiatan']??''));$sheet->mergeCells("A4:{$last}4")->setCellValue('A4','Program: '.($group['program']??'-').' | Kegiatan: '.($group['kegiatan']??'-'));
            if($change){$sheet->mergeCells('A6:A7')->mergeCells('B6:B7')->mergeCells('C6:E6')->mergeCells('F6:H6')->mergeCells('I6:I7');$sheet->fromArray(['KODE REKENING','URAIAN','SEBELUM PERUBAHAN','','','SESUDAH PERUBAHAN','','','BERTAMBAH/(BERKURANG)'],null,'A6');$sheet->fromArray(['','','VOLUME','HARGA','JUMLAH','VOLUME','HARGA','JUMLAH',''],null,'A7');$row=8;$before=$after=0;foreach($details as $d){$ba=(float)($d['jumlah_awal']??0);$aa=(float)($d['jumlah']??0);$before+=$ba;$after+=$aa;$sheet->fromArray([$d['kd_akun']??'-',trim(($d['komponen']??$d['uraian']??'').' '.($d['spesifikasi']??'')),(float)($d['volume_awal']??0),(float)($d['harga_satuan_awal']??0),$ba,(float)($d['volume']??0),(float)($d['harga_satuan']??0),$aa,$aa-$ba],null,'A'.$row++);}$sheet->fromArray(['JUMLAH ANGGARAN SUB KEGIATAN','',$before,'','',$after,'','',$after-$before],null,'A'.$row);$header='A6:I7';$range="A6:I$row";$money="C8:I$row";
            }else{$sheet->fromArray(['KODE REKENING','URAIAN','KOEFISIEN/VOLUME','SATUAN','HARGA','JUMLAH'],null,'A6');$row=7;$total=0;foreach($details as $d){$total+=(float)($d['jumlah']??0);$sheet->fromArray([$d['kd_akun']??'-',trim(($d['komponen']??$d['uraian']??'').' '.($d['spesifikasi']??'')),(float)($d['volume']??0),$d['sat_1']??'-',(float)($d['harga_satuan']??0),(float)($d['jumlah']??0)],null,'A'.$row++);}$sheet->fromArray(['JUMLAH ANGGARAN SUB KEGIATAN','','','','',$total],null,'A'.$row);$header='A6:F6';$range="A6:F$row";$money="C7:F$row";}
            $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle("A1:{$last}1")->getFill()->setFillType('solid')->getStartColor()->setARGB('FF174A68');$sheet->getStyle('A2:'.$last.'4')->getFont()->setBold(true);$sheet->getStyle($header)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle($header)->getFill()->setFillType('solid')->getStartColor()->setARGB('FF2185D0');$sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF708090');$sheet->getStyle($money)->getNumberFormat()->setFormatCode('#,##0.00');$sheet->getStyle("A1:{$last}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);$sheet->getStyle("A$row:{$last}{$row}")->getFont()->setBold(true);$sheet->freezePane($change?'A8':'A7');foreach(['A'=>19,'B'=>48,'C'=>15,'D'=>15,'E'=>18,'F'=>15,'G'=>15,'H'=>18,'I'=>20] as $col=>$width)$sheet->getColumnDimension($col)->setWidth($width);$sheet->getPageSetup()->setOrientation($change?'landscape':'portrait')->setFitToWidth(1)->setFitToHeight(0);}
        if(!$groups){$sheet=$book->createSheet();$sheet->setTitle('Kosong');$sheet->setCellValue('A1','Tidak ada data dalam lingkup pengguna.');}$book->setActiveSheetIndex(0);$tmp=tempnam(sys_get_temp_dir(),'anggaran_').'.xlsx';(new Xlsx($book))->save($tmp);return $tmp;
    }
}
