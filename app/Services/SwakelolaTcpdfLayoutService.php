<?php
require_once __DIR__.'/PageSetupService.php';

/** Editable Word layout metadata rendered directly by TCPDF, never a cached Word PDF. */
final class SwakelolaTcpdfLayoutService
{
    private static function model(string $code):array
    {
        if(!preg_match('/^SW_[A-Z0-9_]+$/D',$code))return [];
        $file=dirname(__DIR__,2).'/resources/procurement/swakelola/layout/'.$code.'.json.gz';
        return is_file($file)?json_decode(gzdecode(file_get_contents($file)),true,512,JSON_THROW_ON_ERROR):[];
    }

    public static function reference(array $document):array
    {
        $data=is_string($document['data_isian']??null)?json_decode($document['data_isian'],true):($document['data_isian']??[]);
        if(($data['swakelola_layout']['engine']??'')==='TCPDF')return [];
        $directory=(string)($data['swakelola_word']['directory']??'');
        if($directory!==''&&preg_match('~^'.(int)($document['kontrak_id']??0).'/[A-Za-z0-9_-]+$~D',$directory)){
            $base=realpath(dirname(__DIR__,2).'/storage/swakelola-word');$path=realpath($base.'/'.$directory.'/layout.json');
            if($base&&$path&&str_starts_with($path,$base.DIRECTORY_SEPARATOR))return json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        }
        return self::model((string)($document['kode_dokumen']??$document['kode']??''));
    }

    /** Keep user text and manual widths; attach defaults only where no format was saved. */
    public static function decorate(string $code,array $blocks,?array $reference=null):array
    {
        $ref=$reference??self::model($code);$source=$ref['blocks']??[];
        $byPath=[];$byText=[];
        $index=function(array $items)use(&$index,&$byPath,&$byText){foreach($items as$b){
            if(isset($b['source_path']))$byPath[$b['source_path']]=$b;
            if($b['type']==='paragraph')$byText[trim($b['text'])]??=$b;
            else foreach($b['rows']as$row)foreach($row as$c)$index($c['blocks']);
        }};$index($source);
        $walk=function(array $items,array $refs=[])use(&$walk,$byPath,$byText):array{
            $mapped=count(array_filter($items,static fn($b)=>isset($b['source_path'])||isset($b['word_path'])||isset($b['word_format'])||isset($b['word_table'])))>0;
            foreach($items as$i=>&$b){
                $path=$b['source_path']??(isset($b['word_path'])?implode('.',$b['word_path']):null);
                $r=($path!==null?($byPath[$path]??null):null)??(!$mapped?($refs[$i]??null):null);
                if(!$r||$r['type']!==$b['type'])$r=$b['type']==='paragraph'?($byText[trim($b['text'])]??[]):[];
                if(!$r){
                    if($b['type']==='table')foreach($b['rows']as&$row)foreach($row as&$cell)$cell['blocks']=$walk($cell['blocks']);unset($row,$cell);
                    continue;
                }
                if($b['type']==='paragraph'){
                    if(!isset($b['word_format'])&&isset($r['word_format'])){
                        $b['word_format']=$r['word_format'];
                        // The old importer marked a whole paragraph bold if any word was bold.
                        if(($b['bold']??false)===($r['bold']??false)&&!isset($b['style']))$b['bold']=$r['word_format']['bold']??false;
                    }
                    $b['source_path']??=$r['source_path']??null;
                }else{
                    $hadLayout=isset($b['word_table']);$b['word_table']??=$r['word_table']??[];$b['source_path']??=$r['source_path']??null;
                    // Manual column_widths always take precedence, including after row/column edits.
                    if(empty($b['column_widths']))$b['column_widths']=$r['column_widths']??[];
                    if(!isset($b['borderless']))$b['borderless']=$r['borderless']??false;
                    foreach($b['rows']as$ri=>&$row)foreach($row as$ci=>&$cell){
                        $rc=$r['rows'][$ri][$ci]??[];$cell['word_cell']??=$hadLayout?[]:($rc['word_cell']??[]);
                        $cell['blocks']=$walk($cell['blocks'],$rc['blocks']??[]);
                    }unset($row,$cell);
                }
            }unset($b);return$items;
        };
        return $walk($blocks,$source);
    }

    private static function font(string $name):string
    {
        $n=strtolower($name);
        if(str_contains($n,'helvetica neue'))return 'helveticaneue';
        if(str_contains($n,'antiqua')||str_contains($n,'gentium'))return 'bookantiqua';
        if(str_contains($n,'times'))return 'timesnewroman';
        if(str_contains($n,'courier'))return 'courier';
        if(str_contains($n,'dejavu')||str_contains($n,'symbol')||str_contains($n,'wingding'))return 'dejavusans';
        return 'arial_tata_naskah';
    }
    private static function escape(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private static function fmt(array $b):array
    {
        $f=($b['word_format']??[])+['font'=>'Arial','size'=>11,'before'=>0,'after'=>0,'line_height'=>1,'bold'=>false,'italic'=>false,'underline'=>false,'left'=>0,'right'=>0,'first'=>0];
        if(isset($b['style']))foreach(['bold','italic','underline']as$key)$f[$key]=in_array($key,$b['style'],true);
        return$f;
    }
    private static function span(string $text,array $f):string
    {
        $size=max(6,min(40,(float)($f['size']??11)));$style='font-family:'.self::font($f['font']??'Arial').';font-size:'.$size.'pt;';
        if(preg_match('/^[0-9a-f]{6}$/i',$f['color']??''))$style.='color:#'.$f['color'].';';
        if(!empty($f['bold']))$style.='font-weight:bold;';if(!empty($f['italic']))$style.='font-style:italic;';if(!empty($f['underline']))$style.='text-decoration:underline;';
        $text=self::escape($text);
        $text=preg_replace('/[\x{2190}-\x{21ff}\x{25a0}-\x{25ff}\x{2610}-\x{2612}]/u','<span style="font-family:dejavusans">$0</span>',$text);
        $text=str_replace(["\t","\n"],['&#160;&#160;&#160;','<br />'],$text);
        return'<span style="'.$style.'">'.$text.'</span>';
    }
    private static function paragraph(array $b,float $widthMm=160,bool $signatureSpace=false,bool $inCell=false):string
    {
        $f=self::fmt($b);$text=(string)$b['text'];if(trim($text)==='')return '<p style="font-size:'.($signatureSpace?max(8,min(12,(float)$f['size'])):6).'pt;line-height:'.($signatureSpace?max(1,min(1.5,(float)$f['line_height'])):1).'">&#160;</p>';$align=in_array($b['align']??'', ['left','right','center','justify'],true)?$b['align']:'left';
        if($align==='justify'&&mb_strlen(trim($text))<70)$align='left';
        $base='font-family:'.self::font($f['font']).';font-size:'.max(6,min(40,(float)$f['size'])).'pt;line-height:'.max(0.9,min(2.5,(float)$f['line_height'])).';text-align:'.$align.';';
        $prefix=$f['prefix']??'';$marker='';
        if($prefix!==''&&str_starts_with($text,$prefix)&&preg_match('/^(?:[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)*[.)]?|\([A-Za-z0-9]+\)|[•●▪◦–-])\s*$/u',trim($prefix)))$marker=$prefix;
        elseif(preg_match('/^((?:[0-9]+(?:\.[0-9]+)*[.)]?|[a-zA-Z][.)]|[ivxlcdmIVXLCDM]+[.)]|\([a-zA-Z0-9]+\)|[•●▪◦])\s+)/u',$text,$m))$marker=$m[1];
        $hanging=$marker!==''?max(14,-(float)$f['first'],mb_strlen(trim($marker))*max(3.5,(float)$f['size']*0.6)+(float)$f['size']*0.25):0;
        $left=max(0,(float)$f['left']+((float)$f['first']<0?(float)$f['first']:0));$right=max(0,(float)$f['right']);
        $widthPt=max(80,$widthMm/25.4*72);$limit=$widthPt*0.7;if($left+$right+$hanging>$limit){$left=0;$right=0;$hanging=min($hanging,25);}
        $body='';
        if(isset($f['runs_text'])&&$text===$f['runs_text']&&!isset($b['style'])&&!empty($f['runs'])){
            if($marker===''&&$prefix!=='')$body=self::span($prefix,$f);
            $strip=$marker!==''&&$prefix!==$marker?mb_strlen($marker):0;
            $runs=[];foreach($f['runs']as$r){$style=$r;unset($style['text']);$last=count($runs)-1;$previous=$last>=0?$runs[$last]:[];unset($previous['text']);if($last>=0&&$previous===$style)$runs[$last]['text'].=$r['text'];else$runs[]=$r;}
            foreach($runs as$r){$value=$r['text'];if($strip>0){$length=mb_strlen($value);$value=mb_substr($value,min($strip,$length));$strip=max(0,$strip-$length);}$body.=self::span($value,$r);}
        }else $body=self::span($marker!==''?mb_substr($text,mb_strlen($marker)):$text,$f);
        // Short bold labels already live in their own grid column. Avoid a second,
        // nested four-column grid squeezing words such as "Pengadaan" in narrow cells.
        if($inCell&&!empty($f['bold'])&&$marker!==''&&$widthMm<65)return '<p style="'.$base.'">'.self::span($text,$f).'</p>';
        if($marker===''&&(float)$f['first']>0)$body='<p style="text-indent:'.min(110,(float)$f['first']).'pt;">'.$body.'</p>';
        $gap=static function(float $pt):string{return $pt>0?'<tr><td colspan="4" style="font-size:'.$pt.'pt;line-height:1;">&#160;</td></tr>':'';};
        $html='<table border="0" cellpadding="0" cellspacing="0" width="100%" nested="true">'.$gap(min(24,max(0,(float)$f['before']))).'<tr>';
        $pct=static fn(float $pt)=>max(0.1,$pt/$widthPt*100);
        $html.='<td width="'.$pct($left).'%"></td><td width="'.$pct($hanging).'%" style="'.$base.'text-align:left;">'.($marker!==''?self::span(trim($marker),$f):'').'</td>';
        $bodyWidth=100-$pct($left)-$pct($hanging)-$pct($right);
        $html.='<td width="'.$bodyWidth.'%" style="'.$base.'">'.$body.'</td><td width="'.$pct($right).'%"></td></tr>'.$gap(min(24,max(0,(float)$f['after']))).'</table>';
        return$html;
    }
    private static function plain(array $blocks):string
    {
        $parts=[];foreach($blocks as$b){if($b['type']==='paragraph')$parts[]=$b['text'];else foreach($b['rows']as$r)foreach($r as$c)$parts[]=self::plain($c['blocks']);}return implode(' ',$parts);
    }
    private static function signature(array $b):bool
    {
        if($b['type']!=='table')return false;
        if(!empty($b['signature_group']))return true;
        $text=self::plain([$b]);
        return count($b['rows'])<=2&&mb_strlen($text)<1400&&preg_match('/tanda tangan|\bttd\b|untuk dan atas nama|ditetapkan oleh|tim pengawas|ketua tim|pejabat pembuat komitmen|pimpinan pelaksana/iu',$text);
    }
    private static function keepWithNext(array $b):bool
    {
        if($b['type']!=='paragraph')return false;$f=self::fmt($b);$text=trim($b['text']);
        return !empty($f['keep_next'])||(!empty($f['bold'])&&$text!==''&&mb_strlen($text)<180&&str_word_count($text)<20&&!preg_match('/[.;:]$/u',$text));
    }
    private static function signatureCell(array $cell):bool
    {
        $text=self::plain($cell['blocks']);
        return mb_strlen($text)<700&&preg_match('/pejabat pembuat komitmen|pimpinan pelaksana|ketua.*(?:lembaga|tim|pelaksana)|tim persiapan|tim pengawas/iu',$text);
    }
    public static function html(array $blocks,array $context=[],bool $cell=false):string
    {
        $html='';$empty=0;$previousAfter=0;$last=count($blocks)-1;
        // Ignore trailing source page breaks/empty paragraphs instead of creating blank pages.
        while($last>=0&&$blocks[$last]['type']==='paragraph'&&trim($blocks[$last]['text'])==='')$last--;
        foreach($blocks as$i=>$b){if($i>$last)break;
            if($b['type']==='paragraph'){
                if(trim($b['text'])===''){if(++$empty>(!empty($context['_signature_space'])?5:2))continue;}else$empty=0;
                if(preg_match('/^\s*\[(?:Kop Surat|Logo)[^\]]*\]\s*$/iu',$b['text'])){
                    if(str_contains(mb_strtolower($b['text']),'logo'))continue;
                    $b['text']=$context['nama_opd']??'';$b['align']='center';$b['style']=['bold'];
                }
                $f=self::fmt($b);$b['word_format']=array_replace($f,['before'=>max(0,(float)$f['before']-$previousAfter)]);$previousAfter=(float)$f['after'];$html.=self::paragraph($b,(float)($context['_content_width_mm']??160),!empty($context['_signature_space']),$cell);continue;
            }
            $empty=0;$previousAfter=0;$widths=$b['column_widths']??[];$columns=max(array_map(fn($row)=>array_sum(array_column($row,'colspan')),$b['rows'])?:[1]);
            if(count($widths)!==$columns||array_sum($widths)<=0)$widths=array_fill(0,$columns,100/$columns);
            $total=array_sum($widths);$widths=array_map(fn($v)=>max(0.2,(float)$v)/$total*100,$widths);
            $table=$b['word_table']??[];$borderless=$b['borderless']??false;
            $style='border-collapse:collapse;';
            $html.='<table cellpadding="'.($borderless?'0':'2').'" cellspacing="0" border="0" width="100%" style="'.$style.'"'.(self::signature($b)?' nobr="true"':'').'>';
            $active=[];$skip=[];$spans=[];
            foreach($b['rows']as$ri=>$row){$col=0;foreach($row as$ci=>$c){$n=(int)($c['colspan']??1);$merge=$c['word_cell']['vmerge']??null;
                if($merge==='continue'&&isset($active[$col])){[$sr,$sc]=$active[$col];$spans[$sr][$sc]=($spans[$sr][$sc]??1)+1;$skip[$ri][$ci]=true;}elseif($merge==='restart')$active[$col]=[$ri,$ci];else unset($active[$col]);$col+=$n;}}
            foreach($b['rows']as$ri=>$row){
                $rowText=self::plain([['type'=>'table','rows'=>[$row]]]);$html.='<tr'.((!$borderless||self::signature($b))?' nobr="true"':'').'>'; $col=0;
                foreach($row as$ci=>$c){$span=max(1,(int)($c['colspan']??1));$width=array_sum(array_slice($widths,$col,$span));$col+=$span;if(isset($skip[$ri][$ci]))continue;
                    $cs='vertical-align:'.(($c['word_cell']['valign']??'top')==='center'?'middle':($c['word_cell']['valign']??'top')).';';
                    $borders=($table['borders']??[]);$borders=array_replace($borders,$c['word_cell']['borders']??[]);
                    foreach(['top','bottom','left','right']as$edge){$v=$borders[$edge]??($borderless?['style'=>'none']:['style'=>'single','size'=>0.5,'color'=>'000000']);if(!in_array($v['style'],['none','nil'],true))$cs.='border-'.$edge.':'.max(0.25,(float)($v['size']??0.5)).'pt solid #'.(preg_match('/^[0-9a-f]{6}$/i',$v['color']??'')?$v['color']:'000000').';';}
                    $fill=$c['word_cell']['fill']??'';if(preg_match('/^[0-9a-f]{6}$/i',$fill))$cs.='background-color:#'.$fill.';';
                    $html.='<td width="'.$width.'%" colspan="'.$span.'"'.(isset($spans[$ri][$ci])?' rowspan="'.$spans[$ri][$ci].'"':'').' style="'.$cs.'">'.self::html($c['blocks'],array_replace($context,['_content_width_mm'=>($context['_content_width_mm']??160)*$width/100,'_signature_space'=>self::signature($b)||self::signatureCell($c)]),true).'</td>';
                }$html.='</tr>';
            }$html.='</table>';
        }
        return$html;
    }

    private static function makePdf(array $d,array $setup,array $page):TCPDF
    {
        $data=is_string($d['data_isian']??null)?json_decode($d['data_isian'],true):($d['data_isian']??[]);
        // Paper, margins, header and footer follow the OPD Page Setup.
        $pdf=PageSetupService::createPdf($setup,$d['orientasi']??'P');PageSetupService::applyPdf($pdf,$setup);$pdf->setCellPaddings(0,0,0,0);$pdf->setCellHeightRatio(1.0);
        $fontdir=dirname(__DIR__,2).'/resources/fonts/swakelola/';foreach([''=>'helveticaneue','B'=>'helveticaneueb','I'=>'helveticaneuei','BI'=>'helveticaneuebi']as$style=>$file)$pdf->AddFont('helveticaneue',$style,$fontdir.$file.'.php');
        foreach(json_decode(file_get_contents($fontdir.'families.json'),true)as$family=>$files){$alias=$family==='Book Antiqua'?'bookantiqua':'timesnewroman';foreach($files as$style=>$file)$pdf->AddFont($alias,$style,$fontdir.$file.'.php');}
        $pdf->SetTitle($d['judul']??'Dokumen Swakelola');$pdf->SetCreator('seSendok — TCPDF');$pdf->setFontSubsetting(true);$pdf->SetFont('arial_tata_naskah','',11);$pdf->setHtmlVSpace(['p'=>[0=>['h'=>0,'n'=>0],1=>['h'=>0,'n'=>0]]]);return$pdf;
    }
    private static function flowContainer(array $b):bool
    {
        if(!empty($b['keep_together']))return false;
        if($b['type']!=='table'||empty($b['rows'])||count(array_filter($b['rows'],static fn($row)=>count($row)!==1)))return false;
        foreach($b['rows']as$row)foreach($row[0]['blocks']as$child)if($child['type']==='table')return true;
        return mb_strlen(self::plain([$b]))>1200;
    }
    private static function writeContainer(TCPDF $pdf,array $b,array $context):void
    {
        // A Word frame is a flowing container, not one enormous unbreakable TCPDF row.
        // Keeping paragraphs as independent blocks avoids nested-table overlap on page breaks.
        $blocks=[];foreach($b['rows']as$row)foreach($row as$cell)array_push($blocks,...$cell['blocks']);
        $m=$pdf->getMargins();$frame=!($b['borderless']??false);$startPage=$pdf->getPage();$startY=$pdf->GetY();
        $pdf->SetMargins($m['left']+1,$m['top'],$m['right']+1);$pdf->SetXY($m['left']+1,$startY+1);
        self::write($pdf,$blocks,array_replace($context,['_content_width_mm'=>($context['_content_width_mm']??160)-2]));
        $endPage=$pdf->getPage();$endY=$pdf->GetY()+1;$pdf->SetMargins($m['left'],$m['top'],$m['right'],true);
        if($frame){
            for($page=$startPage;$page<=$endPage;$page++){$pdf->setPage($page);$top=$page===$startPage?$startY:$m['top'];$bottom=$page===$endPage?$endY:$pdf->getPageHeight()-$pdf->getBreakMargin();$pdf->SetLineWidth(0.15);$pdf->Rect($m['left'],$top,$pdf->getPageWidth()-$m['left']-$m['right'],max(0.1,$bottom-$top));}
        }
        $pdf->setPage($endPage);$pdf->SetXY($m['left'],$endY);
    }
    private static function writeSections(TCPDF $pdf,array $b,array $context):void
    {
        $m=$pdf->getMargins();$w=$pdf->getPageWidth()-$m['left']-$m['right'];
        $widths=$b['column_widths']??[28,72];$labelW=$w*(float)$widths[0]/max(1,array_sum($widths));$gap=3;
        foreach($b['rows']as$row){
            if($pdf->GetY()>$pdf->getPageHeight()-$pdf->getBreakMargin()-30)$pdf->AddPage();
            $page=$pdf->getPage();$y=$pdf->GetY();
            // Measure/draw the narrow heading independently. The body flows normally over pages.
            $label=self::html($row[0]['blocks'],array_replace($context,['_content_width_mm'=>$labelW-$gap]));
            $pdf->writeHTMLCell($labelW-$gap,0,$m['left'],$y,$label,0,1,false,true,'',true);$labelEnd=$pdf->GetY();
            $pdf->setPage($page);$pdf->SetMargins($m['left']+$labelW,$m['top'],$m['right'],true);$pdf->SetXY($m['left']+$labelW,$y);
            self::write($pdf,$row[1]['blocks'],array_replace($context,['_content_width_mm'=>$w-$labelW,'_flow_left_mm'=>$m['left']+$labelW,'_flow_right_mm'=>$m['right']]));
            $endPage=$pdf->getPage();$endY=$pdf->GetY();$pdf->SetMargins($m['left'],$m['top'],$m['right'],true);
            $pdf->SetXY($m['left'],($endPage===$page?max($labelEnd,$endY):$endY)+3);
        }
    }
    private static function write(TCPDF $pdf,array $blocks,array $context):void
    {
        // Keep signatures with the closing paragraph, while allowing long contract bodies to flow.
        $previousAfter=0;
        for($i=0;$i<count($blocks);$i++){
            if(($blocks[$i]['layout']??'')==='kak_sections'){self::writeSections($pdf,$blocks[$i],$context);$previousAfter=0;continue;}
            if(!empty($blocks[$i]['official_letterhead'])){
                OfficialLetterheadPdfService::draw($pdf,$context);$previousAfter=0;continue;
            }
            $heading=$blocks[$i];$hf=$heading['word_format']??[];
            if(self::keepWithNext($heading)){
                $next=$i+1;while(isset($blocks[$next])&&$blocks[$next]['type']==='paragraph'&&trim($blocks[$next]['text'])==='')$next++;
                if(isset($blocks[$next])&&$blocks[$next]['type']==='table'){
                    $sample=$blocks[$next];$sample['rows']=array_slice($sample['rows'],0,2);
                    $probe=clone $pdf;$start=$probe->getPage();$y=$probe->GetY();
                    $probe->writeHTML('<table border="0" cellpadding="0" nobr="true"><tr><td>'.self::html([$heading,$sample],$context).'</td></tr></table>',true,false,true,false,'');
                    if($probe->getPage()>$start&&$y>$pdf->getMargins()['top']+1)$pdf->AddPage();unset($probe);
                }
            }
            $b=$blocks[$i];if($b['type']==='paragraph'){$f=self::fmt($b);$b['word_format']=array_replace($f,['before'=>max(0,(float)$f['before']-$previousAfter)]);$previousAfter=(float)$f['after'];}else $previousAfter=0;if($b['type']==='paragraph'&&trim($b['text'])===''){
                // Only explicit, small ink space; no inherited empty row/paragraph height.
                $n=1;while(isset($blocks[$i+1])&&$blocks[$i+1]['type']==='paragraph'&&trim($blocks[$i+1]['text'])===''){$i++;$n++;}
                if($i<count($blocks)-1)$pdf->Ln(min(5,$n*2));continue;
            }
            if(self::flowContainer($b)){self::writeContainer($pdf,$b,$context);$previousAfter=0;continue;}
            if(isset($context['_flow_left_mm'])&&$b['type']==='table'&&count($b['rows'])>1&&empty($context['_flow_grid_chunk'])){
                $chunks=[array_slice($b['rows'],0,2)];foreach(array_slice($b['rows'],2)as$row)$chunks[]=[$row];
                foreach($chunks as$rows){$part=$b;$part['rows']=$rows;self::write($pdf,[$part],array_replace($context,['_flow_grid_chunk'=>true]));}
                continue;
            }
            if($b['type']==='table'&&count($b['rows'])>1){
                $header=self::plain([['type'=>'table','rows'=>[$b['rows'][0]]]]);
                if(mb_strlen($header)<240&&preg_match('/\b(?:No\.?|Unsur Evaluasi|Kriteria|Uraian|Volume|Jabatan|Nama|Tanggal)\b/iu',$header)){
                    // Probe the first two rows on a clone: never leave a grid header alone.
                    $sample=$b;$sample['rows']=array_slice($b['rows'],0,2);
                    $probe=clone $pdf;$start=$probe->getPage();$y=$probe->GetY();
                    $probe->writeHTML(self::html([$sample],$context),true,false,true,false,'');
                    $m=$pdf->getMargins();
                    if($probe->getPage()>$start&&$y>$m['top']+1)$pdf->AddPage();
                    unset($probe);
                }
            }
            $group=[$b];$j=$i+1;
            // Word's keep-with-next applies to headings, not to an entire chapter.
            if(self::keepWithNext($b)){
                while(isset($blocks[$j])&&$j-$i<8){
                    $next=$blocks[$j];
                    if($next['type']!=='paragraph'||mb_strlen($next['text'])>2200)break;
                    $group[]=$next;$j++;
                    if(trim($next['text'])!==''&&!self::keepWithNext($next))break;
                }
                if(count($group)>1)$i=$j-1;
            }
            if($b['type']==='paragraph'&&preg_match('/demikian|dengan demikian/iu',$b['text'])){
                while(isset($blocks[$j])){$next=$blocks[$j];
                    // Word may contain many empty spacer paragraphs before signatures.
                    // Scan past all of them without losing the closing/signature association.
                    if($next['type']==='paragraph'&&trim($next['text'])===''){if(count($group)<3)$group[]=$next;$j++;continue;}
                    if(self::signature($next)){$group[]=$next;$i=$j;break;}
                    break;
                }
            }
            $html=self::html($group,$context);
            // TCPDF may repeat part of a nested paragraph row when it splits at a page edge.
            // Ordinary paragraphs fit a fresh OPD page; keep each intact, oversized text may flow.
            if(count($group)>1||!empty($context['_flow_grid_chunk'])||($b['type']==='paragraph'&&mb_strlen($b['text'])<4500))$html='<table border="0" cellpadding="0" width="100%" nobr="true"><tr><td>'.$html.'</td></tr></table>';
            if(isset($context['_flow_left_mm'])){
                $left=(float)$context['_flow_left_mm'];$right=(float)$context['_flow_right_mm'];$top=$pdf->getMargins()['top'];
                $pdf->SetMargins($left,$top,$right,true);$pdf->SetX($left);
                $probe=clone $pdf;$page=$probe->getPage();$y=$probe->GetY();$probe->writeHTML($html,true,false,true,false,'');
                if($probe->getPage()>$page&&$y>$top+1){$pdf->AddPage();$pdf->SetMargins($left,$top,$right,true);$pdf->SetX($left);}unset($probe);
            }elseif(count($group)>1){
                // Nested nobr tables can still move only the signature table in TCPDF.
                // Preflight the whole group so its closing paragraph moves with it.
                $probe=clone $pdf;$page=$probe->getPage();$y=$probe->GetY();$probe->writeHTML($html,true,false,true,false,'');
                if($probe->getPage()>$page&&$y>$pdf->getMargins()['top']+1)$pdf->AddPage();unset($probe);
            }
            $pdf->writeHTML($html,empty($context['_flow_grid_chunk']),false,true,false,'');
        }
    }
    public static function renderBundle(array $documents,array $c,array $user,array $setup):string
    {
        if(!$documents)throw new InvalidArgumentException('Belum ada dokumen Swakelola.');
        $first=$documents[0];$pdf=self::makePdf($first,$setup,[]);
        foreach($documents as$d){
            // Each document starts with the same TCPDF state as an individual print.
            PageSetupService::applyPdf($pdf,$setup);$pdf->setCellPaddings(0,0,0,0);$pdf->setCellHeightRatio(1.0);$pdf->SetFont('arial_tata_naskah','',11);
            $pdf->AddPage();$m=$pdf->getMargins();$context=$user+['_content_width_mm'=>$pdf->getPageWidth()-$m['left']-$m['right']];$ref=self::reference($d);
            foreach($d['sections']as$section){$blocks=is_string($section['isi'])?json_decode($section['isi'],true,512,JSON_THROW_ON_ERROR):$section['isi'];self::write($pdf,self::decorate($d['kode_dokumen']??'',$blocks,$ref),$context);}
        }
        return$pdf->Output('','S');
    }
    public static function render(array $d,array $c,array $user,array $setup):string
    {
        $ref=self::reference($d);$pdf=self::makePdf($d,$setup,$ref['page']??[]);$pdf->AddPage();$m=$pdf->getMargins();$user['_content_width_mm']=$pdf->getPageWidth()-$m['left']-$m['right'];
        foreach($d['sections']as$s){$blocks=is_string($s['isi'])?json_decode($s['isi'],true,512,JSON_THROW_ON_ERROR):$s['isi'];$blocks=self::decorate($d['kode_dokumen']??'', $blocks,$ref);self::write($pdf,$blocks,$user);}
        return$pdf->Output('','S');
    }
}
