<?php
require_once __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/** Shared presentation for application-generated Excel downloads (not uploaded source files). */
final class ExcelPresentationService
{
    public static function apply(Spreadsheet $book, array $user = [], string $title = ''): void
    {
        $book->getProperties()->setCreator('seSendok')->setTitle($title ?: 'Ekspor seSendok');
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        foreach ($book->getAllSheets() as $sheet) {
            $lastRow=max(1,$sheet->getHighestDataRow());
            $last=$sheet->getHighestDataColumn();
            $count=Coordinate::columnIndexFromString($last);
            $sheet->setShowGridlines(false);
            $sheet->getStyle("A1:$last$lastRow")->getAlignment()->setVertical('top')->setWrapText(true);
            $headers=[];
            for($r=1;$r<=min($lastRow,1500);$r++) {
                $labels=[];
                for($c=1;$c<=$count;$c++) {
                    $v=$sheet->getCell([$c,$r])->getValue();
                    if(is_string($v)&&$v!==''&&$v[0]!=='=')$labels[$c]=$v;
                }
                $upper=array_filter($labels,fn($v)=>mb_strtoupper($v)===$v&&preg_match('/[A-Z]/',$v));
                if(count($upper)>=min(3,$count)&&count($upper)>=count($labels)*.7)$headers[$r]=$labels;
            }
            if(!$headers)$headers[1]=array_combine(range(1,$count),array_map(fn($c)=>(string)$sheet->getCell([$c,1])->getValue(),range(1,$count)));
            $first=(int)array_key_first($headers);
            foreach($headers as $r=>$labels) {
                $sheet->getStyle("A$r:$last$r")->applyFromArray([
                    'font'=>['bold'=>true,'color'=>['argb'=>'FFFFFFFF']],
                    'fill'=>['fillType'=>'solid','startColor'=>['argb'=>'FF176B87']],
                    'borders'=>['bottom'=>['borderStyle'=>'medium','color'=>['argb'=>'FF0E4355']]],
                ]);
                $sheet->getRowDimension($r)->setRowHeight(34);
            }
            $labels=$headers[$first];
            $repeatEnd=$first;
            while(isset($headers[$repeatEnd+1])){ $repeatEnd++; foreach($headers[$repeatEnd] as$c=>$v)if($v!=='')$labels[$c]=$v; }
            $identifierColumns=[];
            for($c=1;$c<=$count;$c++) {
                $col=Coordinate::stringFromColumnIndex($c);
                $label=str_replace('_',' ',mb_strtolower((string)($labels[$c]??'')));
                $identifierColumns[$c]=(bool)preg_match('/npwp|(?:^| )(?:nip|kode|nomor)(?: |$)|^kd |rekening/',$label);
                $width=self::width($label);
                $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth($width);
                $body=$col.($first+1).':'.$col.max($first+1,$lastRow);
                if($identifierColumns[$c]){
                    $sheet->getStyle($body)->getNumberFormat()->setFormatCode('@');
                    $sheet->getStyle($body)->getAlignment()->setHorizontal('left');
                } elseif(preg_match('/harga|nilai|pagu|anggaran|jumlah|total|realisasi|deviasi|sisa|volume|target|pajak|ppn|bobot|^no$|^id$/',$label)) {
                    $sheet->getStyle($body)->getAlignment()->setWrapText(false)->setHorizontal('right');
                    if(preg_match('/harga|nilai|pagu|anggaran|jumlah|total|realisasi|deviasi|sisa/',$label)&&$sheet->getStyle($col.($first+1))->getNumberFormat()->getFormatCode()==='General')$sheet->getStyle($body)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
            $sheet->getDefaultRowDimension()->setRowHeight(23);
            if($lastRow>2000){
                $stripe=new \PhpOffice\PhpSpreadsheet\Style\Conditional();
                $stripe->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION)->addCondition('MOD(ROW(),2)=0');
                $stripe->getStyle()->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF1F6FA');
                $sheet->getStyle('A'.($first+1).':'.$last.$lastRow)->setConditionalStyles([$stripe]);
            }
            for($r=1;$r<$first;$r++) {
                if($sheet->getCell([1,$r])->getValue()!==null)$sheet->getRowDimension($r)->setRowHeight($r===1?34:28);
            }
            for($r=$first+1;$r<=$lastRow;$r++) {
                if(isset($headers[$r]))continue;
                $lines=1;$hasData=false;
                for($c=1;$c<=$count;$c++) {
                    $cell=$sheet->getCell([$c,$r]);$value=$cell->getValue();
                    if($value===null||$value==='')continue;
                    $hasData=true;
                    if(!empty($identifierColumns[$c])&&$cell->getDataType()!==\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)$cell->setValueExplicit((string)$value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    if(is_string($value)&&$value[0]!=='=') {
                        $width=$sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->getWidth();
                        $lines=max($lines,(int)ceil(mb_strlen($value)/max(8,$width-3))+substr_count($value,"\n"));
                    }
                }
                if(!$hasData)continue;
                $range="A$r:$last$r";
                if($lastRow<=2000)$sheet->getStyle($range)->getBorders()->getBottom()->setBorderStyle('hair')->getColor()->setARGB('FFD4E1E8');
                if($lastRow<=2000&&$r%2===0&&$sheet->getStyle("A$r")->getFill()->getFillType()==='none')$sheet->getStyle($range)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF1F6FA');
                if($lines>1)$sheet->getRowDimension($r)->setRowHeight($lines*14+8);
            }
            $heading=$title ?: $sheet->getTitle();
            $safe=fn($v)=>str_replace('&','&&',preg_replace('/[\r\n]+/',' ',(string)$v));
            $opd=$user['nama_opd']??$user['nama_org']??$user['kd_opd']??'';
            $sheet->getHeaderFooter()->setOddHeader('&L&BseSendok&B&C'.$safe($heading).'&RTahun '.$safe($user['tahun']??date('Y')));
            $sheet->getHeaderFooter()->setOddFooter('&L'.$safe($opd).'&C&D &T&RHalaman &P dari &N');
            $sheet->getHeaderFooter()->setEvenHeader($sheet->getHeaderFooter()->getOddHeader())->setEvenFooter($sheet->getHeaderFooter()->getOddFooter());
            $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation($count>6?'landscape':'portrait')->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd(1,$repeatEnd)->setPrintArea("A1:$last$lastRow");
            $sheet->getPageMargins()->setTop(.65)->setBottom(.6)->setLeft(.3)->setRight(.3)->setHeader(.25)->setFooter(.25);
            if(!$sheet->getFreezePane())$sheet->freezePane('A'.($first+1));
        }
    }
    private static function width(string $label): float
    {
        if(preg_match('/uraian|pekerjaan|komponen|spesifikasi|indikator|sasaran|tujuan|misi|visi|program\/|daftar uraian/',$label))return 54;
        if(preg_match('/^no\.?$|^id$|^minggu$|^(?:pajak|ppn|bobot)(?: |%|$)/',$label))return 9;
        if(preg_match('/harga|nilai|pagu|anggaran|jumlah|total|realisasi|sisa|deviasi|sebelum|setelah|bertambah/',$label))return 23;
        if(preg_match('/satuan|volume|koefisien|target|awal|progres|progress|capaian|persen/',$label))return 14;
        if(preg_match('/tanggal|mulai|selesai|tgl/',$label))return 19;
        if(preg_match('/nomor|nip|npwp|kode|^kd |rekening/',$label))return 27;
        if(preg_match('/nama|penyedia|organisasi/',$label))return 36;
        return 22;
    }
}
