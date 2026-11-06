<?php
/** Read-only regression: actual export renderers, two sub-activities with shared ancestors. */
if(PHP_SAPI!=='cli')exit;
require_once __DIR__.'/../app/Services/AnggaranDocumentService.php';

class HierarchyExportCheck extends AnggaranDocumentService
{
    public function groups(string $logical):array
    {
        return array_values(array_filter(parent::groups($logical),fn($g)=>in_array($g['kd_sub_keg'],['1.03.01.2.09.0006','1.03.09.2.01.0011'],true)));
    }
}
$user=['type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026,'nama_opd'=>'Dinas Pekerjaan Umum dan Penataan Ruang'];
$svc=new HierarchyExportCheck($user);
$db=DB::getInstance();
$hasRevenue=$db->query("SHOW TABLES LIKE 'rka\_pendapatan\_neo'")->fetchColumn()
    && $db->query('SELECT 1 FROM rka_pendapatan_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? LIMIT 1',
        [$user['kd_wilayah'],$user['kd_opd'],$user['tahun']])->fetchColumn();
$out=$argv[1]??__DIR__.'/../outputs/anggaran-hierarchy-20261029';
if(!is_dir($out))mkdir($out,0775,true);
$checks=0;
function expectHierarchy(bool $ok,string $message):void{global$checks;if(!$ok)throw new RuntimeException($message);$checks++;}
foreach(['renja','rka','dpa','renja_p','rka_p','dppa']as$logical){
    $change=in_array($logical,['renja_p','rka_p','dppa'],true);
    $file=$svc->exportExcel($logical);$book=\PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $recapIndex=$hasRevenue?2:1;
    expectHierarchy($book->getSheetCount()===$recapIndex+3,"$logical: expected sheets exported");
    $recap=$book->getSheet($recapIndex);
    expectHierarchy($recap->getTitle()==='Rekapitulasi',"$logical: expenditure recap is present");
    expectHierarchy($book->getSheet(0)->getTitle()==='Rekapitulasi SKPD',"$logical: SKPD recap is first");
    expectHierarchy(($book->getSheetByName('Pendapatan')!==null)===$hasRevenue,"$logical: revenue sheet reflects data availability");
    $recapRows=[];
    $grandRow=$recap->getHighestDataRow();
    $recapColumn=$change?'D':'C';
    for($r=4;$r<$grandRow;$r++){
        $code=(string)$recap->getCell("A$r")->getValue();
        expectHierarchy(!isset($recapRows[$code]),"$logical: recap code appears once");
        $recapRows[$code]=$r;
    }
    $grandTotal=0;
    $sample=null;
    $expectedRecap=[];
    foreach(array_slice($book->getAllSheets(),$recapIndex+1)as$sheet){
        $afterColumn=$change?'L':'G';
        $code=$sheet->getTitle();
        expectHierarchy(isset($recapRows[$code]),"$logical: sub-activity appears in recap");
        $recapRow=$recapRows[$code];
        $totalRow=$sheet->getHighestDataRow();
        $belanjaRow=null;
        $details=$svc->details($logical,$sheet->getTitle());$expected=[];$printed=[];$detailRows=0;
        foreach($details as$d)foreach($d['akun_hierarchy']as$n){$key=$n['kode'];$expected[$key]['after']=($expected[$key]['after']??0)+(float)$d['jumlah'];$expected[$key]['before']=($expected[$key]['before']??0)+(float)($d['jumlah_awal']??0);}
        for($r=11;$r<=$sheet->getHighestRow();$r++){
            $key=(string)$sheet->getCell("A$r")->getValue();
            if(!isset($expected[$key])){if($key===''&&trim((string)$sheet->getCell("B$r")->getValue())!==''){
                $detailRows++;
                $sample??=[$sheet,$afterColumn.$r,$afterColumn.$totalRow,$recapColumn.$recapRow];
            }continue;}
            expectHierarchy(!isset($printed[$key]),"$logical {$sheet->getTitle()}: duplicate account $key");$printed[$key]=true;
            if($key==='5')$belanjaRow=$r;
            $subtotal=$sheet->getCell($afterColumn.$r);
            expectHierarchy($subtotal->isFormula()&&abs((float)$subtotal->getCalculatedValue()-$expected[$key]['after'])<.01,"$logical: subtotal formula after $key");
            if($change){
                $before=$sheet->getCell('G'.$r);
                expectHierarchy($before->isFormula()&&abs((float)$before->getCalculatedValue()-$expected[$key]['before'])<.01,"$logical: subtotal formula before $key");
                $difference=$sheet->getCell('M'.$r);
                expectHierarchy($difference->isFormula()&&abs((float)$difference->getCalculatedValue()-($expected[$key]['after']-$expected[$key]['before']))<.01,"$logical: subtotal difference formula $key");
            }
        }
        expectHierarchy(count($printed)===count($expected),"$logical: every account preserved");
        expectHierarchy($detailRows===count($details),"$logical: every detail including zero amounts preserved");
        $expectedTotal=array_sum(array_column($details,'jumlah'));
        $total=$sheet->getCell($afterColumn.$totalRow);
        expectHierarchy($total->isFormula()&&abs($total->getCalculatedValue()-$expectedTotal)<.01,"$logical: sub-activity total formula");
        $link=$recap->getCell($recapColumn.$recapRow);
        expectHierarchy($belanjaRow!==null&&$link->getValue()==="='".$sheet->getTitle()."'!".$afterColumn.$belanjaRow
            &&abs($link->getCalculatedValue()-$expectedTotal)<.01,"$logical: recap links directly to Belanja Daerah");
        $parts=explode('.',$code);
        foreach([1,2,3,5]as$length){
            $parent=implode('.',array_slice($parts,0,$length));
            expectHierarchy(isset($recapRows[$parent])&&$recapRows[$parent]<$recapRow,"$logical: recap contains parent $parent before $code");
            $expectedRecap[$parent]=($expectedRecap[$parent]??0)+$expectedTotal;
        }
        $grandTotal+=$expectedTotal;
    }
    foreach($expectedRecap as $parent=>$total){
        $subtotal=$recap->getCell($recapColumn.$recapRows[$parent]);
        expectHierarchy($subtotal->isFormula()&&abs($subtotal->getCalculatedValue()-$total)<.01,"$logical: recap rolls up $parent");
    }
    $grandCell=$recapColumn.$grandRow;
    expectHierarchy($recap->getCell($grandCell)->isFormula()&&abs($recap->getCell($grandCell)->getCalculatedValue()-$grandTotal)<.01,"$logical: recap grand total");
    $skpd=$book->getSheet(0);
    for($r=6;$r<=$skpd->getHighestDataRow();$r++){
        if($skpd->getCell("B$r")->getValue()!=='JUMLAH BELANJA DENGAN RINCIAN')continue;
        expectHierarchy(abs($skpd->getCell("$recapColumn$r")->getCalculatedValue()-$grandTotal)<.01,"$logical: SKPD total matches expense recap");
        if ($change) {
            $before=$recap->getCell("C$grandRow")->getCalculatedValue();
            expectHierarchy(abs($skpd->getCell("C$r")->getCalculatedValue()-$before)<.01,"$logical: SKPD total before matches expense recap");
            expectHierarchy(abs($skpd->getCell("E$r")->getCalculatedValue()-($grandTotal-$before))<.01,"$logical: SKPD difference matches expense recap");
        }
        break;
    }
    expectHierarchy($r<=$skpd->getHighestDataRow()," $logical: SKPD summary contains the expense total");
    [$sampleSheet,$input,$sheetTotal,$recapSubtotal]=$sample;
    $previous=$sampleSheet->getCell($input)->getValue();
    $previousSubtotal=$recap->getCell($recapSubtotal)->getCalculatedValue();
    $sampleSheet->setCellValue($input,$previous+123.45);
    \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($book)->clearCalculationCache();
    expectHierarchy(abs($sampleSheet->getCell($sheetTotal)->getCalculatedValue()-$previousSubtotal-123.45)<.01,"$logical: detail edit reaches sheet total once");
    expectHierarchy(abs($recap->getCell($recapSubtotal)->getCalculatedValue()-$previousSubtotal-123.45)<.01,"$logical: detail edit reaches recap subtotal");
    expectHierarchy(abs($recap->getCell($grandCell)->getCalculatedValue()-$grandTotal-123.45)<.01,"$logical: detail edit reaches grand total once");
    copy($file,"$out/$logical.xlsx");unlink($file);$book->disconnectWorksheets();
    file_put_contents("$out/$logical.pdf",$svc->exportRegulatoryPdf($logical));
    echo "$logical: Excel hierarchy and subtotals verified; PDF generated\n";
}
// Other PDF entry points must use the same hierarchy, too.
file_put_contents("$out/dppa-official.pdf",$svc->exportOfficialPdf('dppa'));
file_put_contents("$out/renja-legacy.pdf",$svc->exportPdf('renja'));
echo "PASS $checks checks. Live database read only.\n";
