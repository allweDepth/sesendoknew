<?php
require_once __DIR__.'/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
final class RabImportService
{
    public const HEADERS=['No','Uraian','Satuan','Volume HPS','Volume Penawaran','Volume Negosiasi','Harga Satuan HPS','Harga Satuan Penawaran','Harga Satuan Negosiasi','Pajak (%)'];
    public static function parse(string $path):array
    {
        $book=IOFactory::load($path);$sheet=$book->getSheetByName('RAB')??$book->getActiveSheet();$rows=$sheet->toArray(null,true,false,false);$header=null;$map=[];
        $norm=fn($x)=>preg_replace('/[^a-z0-9]/','',strtolower((string)$x));
        foreach(array_slice($rows,0,50,true) as$i=>$r){$candidate=[];foreach($r as$c=>$value)$candidate[$norm($value)]=$c;if(isset($candidate['volumehps'],$candidate['hargasatuanhps'],$candidate['uraian'])){$header=$i;$map=$candidate;break;}}
        if($header===null)return []; // Existing single-stage format is still supported by its original parser.
        $keys=['nomor'=>'no','uraian'=>'uraian','satuan'=>'satuan','vol_hps'=>'volumehps','vol_penawaran'=>'volumepenawaran','vol_negoisasi'=>'volumenegosiasi','harga_sat_hps'=>'hargasatuanhps','harga_sat_penawaran'=>'hargasatuanpenawaran','harga_sat_negoisasi'=>'hargasatuannegosiasi','pajak'=>'pajak'];
        foreach($keys as$k=>$h)if(!isset($map[$h]))throw new InvalidArgumentException('Kolom Excel wajib: '.implode(', ',self::HEADERS));
        $items=[];foreach($rows as$i=>$row){if($i<=$header)continue;$r=[];foreach($keys as$k=>$h)$r[$k]=$row[$map[$h]]??null;$r['uraian']=trim((string)$r['uraian']);if($r['uraian']==='')continue;
            if(trim((string)$r['satuan'])===''){foreach(['hps','penawaran','negoisasi']as$s)if(($r['vol_'.$s]??'')!==''&&$r['vol_'.$s]!==null||($r['harga_sat_'.$s]??'')!==''&&$r['harga_sat_'.$s]!==null)throw new InvalidArgumentException('Baris '.($i+1).': satuan wajib diisi untuk pekerjaan.');$r['type']='KETERANGAN';}
            else{foreach(['vol_hps','vol_penawaran','vol_negoisasi','harga_sat_hps','harga_sat_penawaran','harga_sat_negoisasi','pajak']as$k){$v=$r[$k];if(!is_numeric($v))throw new InvalidArgumentException('Baris '.($i+1).': '.$k.' harus angka Excel, bukan teks.');$r[$k]=(float)$v;if($r[$k]<0||($k==='pajak'&&$r[$k]>100)||($k!=='pajak'&&$r[$k]<=0))throw new InvalidArgumentException('Baris '.($i+1).': '.$k.' tidak valid.');}$r['type']='PEKERJAAN';}
            $items[]=$r;
        }if(!$items)throw new InvalidArgumentException('Rincian RAB kosong.');return$items;
    }
    public static function template():string
    {
        $book=new Spreadsheet();$s=$book->getActiveSheet()->setTitle('RAB');$s->mergeCells('A1:J1')->setCellValue('A1','FORMAT IMPOR RAB • HPS / PENAWARAN / NEGOSIASI');$s->mergeCells('A2:J2')->setCellValue('A2','Isi angka sebagai angka Excel. Jumlah dihitung aplikasi: volume × harga satuan × (1 + pajak/100).');$s->mergeCells('A3:J3')->setCellValue('A3','Baris keterangan/divisi: isi No dan Uraian; kosongkan Satuan serta seluruh angka. Hapus contoh sebelum memakai data sendiri.');$s->fromArray(self::HEADERS,null,'A5');$s->fromArray([['1','DIVISI 1. UMUM',null,null,null,null,null,null,null,null],['1.2','Mobilisasi','Ls',1,1,1,1000000,995000,990000,11]],null,'A6');
        $s->getStyle('A1:J1')->applyFromArray(['font'=>['bold'=>true,'size'=>16,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>'solid','startColor'=>['rgb'=>'183A54']]]);$s->getStyle('A5:J5')->applyFromArray(['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>'solid','startColor'=>['rgb'=>'2185D0']]]);$s->getStyle('A1:J7')->getAlignment()->setVertical('center')->setWrapText(true);$s->getStyle('A5:J7')->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setRGB('D8E2EC');$s->getStyle('A6:J6')->getFill()->setFillType('solid')->getStartColor()->setRGB('EBF3F8');$s->getStyle('D6:I1000')->getNumberFormat()->setFormatCode('#,##0.00');$s->getColumnDimension('A')->setWidth(10);$s->getColumnDimension('B')->setWidth(55);$s->getColumnDimension('C')->setWidth(12);foreach(range('D','J')as$c)$s->getColumnDimension($c)->setWidth(19);$s->getRowDimension(1)->setRowHeight(38);$s->getRowDimension(2)->setRowHeight(32);$s->getRowDimension(3)->setRowHeight(32);$s->getRowDimension(5)->setRowHeight(36);$s->freezePane('C6');$s->getPageSetup()->setOrientation('landscape')->setPaperSize(5)->setFitToWidth(1)->setFitToHeight(0);$s->getHeaderFooter()->setOddHeader('&LseSendok&RFormat Impor RAB')->setOddFooter('&LTemplate HPS / Penawaran / Negosiasi&RHalaman &P dari &N');$s->setShowGridlines(false);$s->getPageSetup()->setPrintArea('A1:J7');$f=tempnam(sys_get_temp_dir(),'rab-template-').'.xlsx';(new Xlsx($book))->save($f);return$f;
    }
}
