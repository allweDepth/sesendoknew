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
$out=__DIR__.'/../outputs/anggaran-hierarchy-20261029';
if(!is_dir($out))mkdir($out,0775,true);
$checks=0;
function expectHierarchy(bool $ok,string $message):void{global$checks;if(!$ok)throw new RuntimeException($message);$checks++;}
foreach(['renja','rka','dpa','renja_p','rka_p','dppa']as$logical){
    $change=in_array($logical,['renja_p','rka_p','dppa'],true);
    $file=$svc->exportExcel($logical);$book=\PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    expectHierarchy($book->getSheetCount()===2,"$logical: both sub-activities exported");
    foreach($book->getAllSheets()as$sheet){
        $details=$svc->details($logical,$sheet->getTitle());$expected=[];$printed=[];$detailRows=0;
        foreach($details as$d)foreach($d['akun_hierarchy']as$n){$key=$n['kode'];$expected[$key]['after']=($expected[$key]['after']??0)+(float)$d['jumlah'];$expected[$key]['before']=($expected[$key]['before']??0)+(float)($d['jumlah_awal']??0);}
        for($r=11;$r<=$sheet->getHighestRow();$r++){
            $key=(string)$sheet->getCell("A$r")->getValue();
            if(!isset($expected[$key])){if($key===''&&trim((string)$sheet->getCell("B$r")->getValue())!=='')$detailRows++;continue;}
            expectHierarchy(!isset($printed[$key]),"$logical {$sheet->getTitle()}: duplicate account $key");$printed[$key]=true;
            expectHierarchy(abs((float)$sheet->getCell(($change?'L':'G').$r)->getValue()-$expected[$key]['after'])<.01,"$logical: subtotal after $key");
            if($change){
                expectHierarchy(abs((float)$sheet->getCell('G'.$r)->getValue()-$expected[$key]['before'])<.01,"$logical: subtotal before $key");
                expectHierarchy(abs((float)$sheet->getCell('M'.$r)->getValue()-($expected[$key]['after']-$expected[$key]['before']))<.01,"$logical: subtotal difference $key");
            }
        }
        expectHierarchy(count($printed)===count($expected),"$logical: every account preserved");
        expectHierarchy($detailRows===count($details),"$logical: every detail including zero amounts preserved");
    }
    copy($file,"$out/$logical.xlsx");unlink($file);$book->disconnectWorksheets();
    file_put_contents("$out/$logical.pdf",$svc->exportRegulatoryPdf($logical));
    echo "$logical: Excel hierarchy and subtotals verified; PDF generated\n";
}
// Other PDF entry points must use the same hierarchy, too.
file_put_contents("$out/dppa-official.pdf",$svc->exportOfficialPdf('dppa'));
file_put_contents("$out/renja-legacy.pdf",$svc->exportPdf('renja'));
echo "PASS $checks checks. Live database read only.\n";
