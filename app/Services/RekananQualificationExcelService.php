<?php
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/RekananQualificationService.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class RekananQualificationExcelService
{
    private Spreadsheet $book;
    private RekananQualificationService $source;
    public function __construct(array $user=[]){$this->source=new RekananQualificationService($user);}
    private function put(Worksheet $s,string $cell,$value):void{$s->setCellValueExplicit($cell,(string)$value,DataType::TYPE_STRING);}
    private function sheet(string $name,string $title):Worksheet
    {
        $s=$this->book->createSheet();$s->setTitle($name);$s->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);$s->getPageMargins()->setTop(.5)->setBottom(.5)->setLeft(.5)->setRight(.5);$s->mergeCells('A1:H1');$this->put($s,'A1',$title);$s->getStyle('A1')->getFont()->setBold(true)->setSize(13);$s->getStyle('A1')->getAlignment()->setHorizontal('center')->setWrapText(true);$s->getRowDimension(1)->setRowHeight(32);foreach(range('A','H') as $c)$s->getColumnDimension($c)->setWidth(14);$s->getHeaderFooter()->setOddFooter('&RHalaman &P / &N');return $s;
    }
    private function block(Worksheet $s,int $row,string $text,int $height=0):int
    {
        $s->mergeCells("A$row:H$row");$this->put($s,"A$row",$text);$s->getStyle("A$row:H$row")->getAlignment()->setWrapText(true)->setVertical('top');$s->getRowDimension($row)->setRowHeight($height?:max(24,ceil(mb_strlen($text)/105)*17+9));return $row+1;
    }
    private function kv(Worksheet $s,int $row,array $values):int
    {
        foreach($values as $label=>$value){$s->mergeCells("A$row:C$row");$s->mergeCells("D$row:H$row");$this->put($s,"A$row",$label);$this->put($s,"D$row",trim((string)$value)!==''?$value:'........................................................');$s->getStyle("A$row:H$row")->getAlignment()->setWrapText(true)->setVertical('top');$s->getRowDimension($row)->setRowHeight(max(28,ceil(mb_strlen((string)$value)/60)*16+10));$row++;}return $row;
    }
    private function signature(Worksheet $s,int $row,array $d):void
    {
        $row=$this->block($s,$row+1,($d['place']?:'........................').', '.($d['date']?:'........................'));
        $row=$this->block($s,$row,'Untuk dan atas nama '.$d['vendor']['nama_perusahaan']);$row=$this->block($s,$row,'Meterai sesuai ketentuan',65);$row=$this->block($s,$row,$d['vendor']['direktur']?:'........................');$this->block($s,$row,$d['vendor']['jabatan']?:'........................');
    }
    public function excel(int $id,array $input=[]):string
    {
        $d=$this->source->data($id,$input);$v=$d['master_vendor'];$signed=$d['vendor'];$saved=$this->source->qualification($id)['data'];$pr=$saved['profil'];$q=$saved['rincian'];$texts=$this->source->declarations($d,$pr);
        $this->book=new Spreadsheet();$this->book->removeSheetByIndex(0);$this->book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);$this->book->getProperties()->setCreator('seSendok')->setTitle('Isian Kualifikasi - '.$v['nama_perusahaan']);
        foreach(['integrity'=>['Pakta Integritas','PAKTA INTEGRITAS'],'statements'=>['Pernyataan','SURAT PERNYATAAN PESERTA DAN KEBENARAN DATA KUALIFIKASI']] as $key=>[$name,$title]){
            $s=$this->sheet($name,$title);$r=$this->kv($s,3,['Nama'=>$signed['direktur'],'No. Identitas'=>$signed['no_ktp'],'Jabatan'=>$signed['jabatan'],'Bertindak untuk dan atas nama'=>$v['nama_perusahaan'],'Alamat'=>$v['alamat'],'E-mail'=>$v['email']]);
            $r=$this->block($s,$r+1,'Dalam rangka '.RekananQualificationService::METHODS[$d['method']].' untuk paket '.($d['package']?:'........................................................').', menyatakan bahwa:');
            foreach($texts[$key] as $i=>$text){$s->mergeCells('B'.$r.':H'.$r);$this->put($s,'A'.$r,($i+1).'.');$this->put($s,'B'.$r,$text);$s->getStyle('A'.$r.':H'.$r)->getAlignment()->setWrapText(true)->setVertical('top');$s->getRowDimension($r)->setRowHeight(max(26,ceil(mb_strlen($text)/90)*17+10));$r++;}$this->signature($s,$r+1,$d);
        }
        $s=$this->sheet('Identitas dan Legalitas','DATA ADMINISTRASI DAN LANDASAN HUKUM');$r=$this->kv($s,3,['Jenis Formulir'=>RekananQualificationService::TYPES[$d['type']],'Subjenis'=>['barang'=>'Barang','jasa_lainnya'=>'Jasa Lainnya','konstruksi'=>'Konstruksi','nonkonstruksi'=>'Nonkonstruksi'][$d['detail']]??$d['detail'],'Metode Pemilihan'=>RekananQualificationService::METHODS[$d['method']],'Paket Pekerjaan'=>$d['package'],'Nomor RUP'=>$d['contract']['nomor_rup']??'','Nama Badan Usaha'=>$v['nama_perusahaan'],'Alamat'=>$v['alamat'],'E-mail'=>$v['email'],'NPWP'=>$v['npwp'],'Direktur'=>$v['direktur'],'Jabatan'=>$v['jabatan'],'Identitas Direktur'=>$v['no_ktp'],'Nomor Akta Pendirian'=>$v['no_akta_pendirian'],'Tanggal Akta Pendirian'=>$v['tgl_akta_pendirian'],'Notaris Pendirian'=>$v['nama_notaris_pendirian'],'Nomor Akta Perubahan'=>$v['no_akta_perubahan'],'Tanggal Akta Perubahan'=>$v['tgl_akta_perubahan'],'Notaris Perubahan'=>$v['nama_notaris_perubahan']]);
        $profile=[];foreach(RekananQualificationData::profile() as $k=>$f)if($k!=='alamat_pusat')$profile[$f['label']]=$pr[$k]??'';$this->kv($s,$r+1,$profile);
        $construction=$d['type']==='konstruksi'||($d['type']==='konsultansi'&&$d['detail']==='konstruksi');
        $sheets=['pengurus'=>'Pengurus','komisaris'=>'Komisaris','izin'=>'Perizinan','sbu'=>'SBU dan Standar','izin_lain'=>'Izin Lainnya','pemilik'=>'Pemegang Saham Pesero','pemilik_manfaat'=>'Pemilik Manfaat','personel'=>'Personel','pengalaman_personel'=>'Pengalaman Tenaga Ahli','peralatan'=>'Peralatan','pengalaman'=>'Pengalaman Badan Usaha','pekerjaan_berjalan'=>'Pekerjaan Berjalan'];
        foreach($sheets as $kind=>$name){if($kind==='sbu'&&!$construction)continue;if($kind==='izin_lain'&&$construction)continue;if($kind==='pengalaman_personel'&&$d['type']!=='konsultansi')continue;
            $schema=RekananQualificationData::sections()[$kind]['fields'];$rows=$q[$kind]??[];
            if($kind==='pengurus'&&!empty($v['direktur'])){$rows=array_values(array_filter($rows,fn($x)=>trim($x['nama']??'')!==trim($v['direktur'])));array_unshift($rows,['nama'=>$v['direktur'],'identitas'=>$v['no_ktp'],'jabatan'=>$v['jabatan']]);}
            $s=$this->sheet($name,mb_strtoupper($name));$count=count($schema)+1;$last=Coordinate::stringFromColumnIndex(max(8,$count));if($last!=='H'){$s->unmergeCells('A1:H1');$s->mergeCells('A1:'.$last.'1');}if($count>=6)$s->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $heads=array_merge(['No'],array_column($schema,'label'));foreach($heads as $i=>$h){$cell=Coordinate::stringFromColumnIndex($i+1).'3';$this->put($s,$cell,$h);$s->getColumnDimension(Coordinate::stringFromColumnIndex($i+1))->setWidth($i===0?6:23);}$tableLast=Coordinate::stringFromColumnIndex($count);$s->getStyle('A3:'.$tableLast.'3')->getFont()->setBold(true);$s->getStyle('A3:'.$tableLast.'3')->getFill()->setFillType('solid')->getStartColor()->setRGB('E7EEF5');$s->getRowDimension(3)->setRowHeight(45);
            $n=max(5,count($rows)+1);for($i=0;$i<$n;$i++){$r=4+$i;$this->put($s,'A'.$r,$i+1);$col=2;foreach($schema as $key=>$field){$cell=Coordinate::stringFromColumnIndex($col++).$r;$value=$rows[$i][$key]??'';if($value!==''&&in_array($field['type'],['number','money','percent'],true)){$s->setCellValue($cell,(float)$value);$s->getStyle($cell)->getNumberFormat()->setFormatCode($field['type']==='money'?'#,##0.00':'0.####');}else{$this->put($s,$cell,$value);}}$longest=0;foreach($schema as $key=>$field){$parts=explode("\n",(string)($rows[$i][$key]??''));$lines=0;foreach($parts as $part)$lines+=max(1,(int)ceil(mb_strlen($part)/21));$longest=max($longest,$lines);}$s->getRowDimension($r)->setRowHeight(min(409,max(42,$longest*15+10)));}
            $range='A3:'.$tableLast.(3+$n);$s->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);$s->getStyle($range)->getAlignment()->setWrapText(true)->setVertical('top');$s->freezePane('B4');$s->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(3,3);$s->getPageSetup()->setPrintArea('A1:'.$tableLast.(3+$n));
        }
        $this->book->setActiveSheetIndex(0);$temp=tempnam(sys_get_temp_dir(),'qualification-');try{(new Xlsx($this->book))->save($temp);return file_get_contents($temp);}finally{if(is_file($temp))unlink($temp);$this->book->disconnectWorksheets();}
    }
}
