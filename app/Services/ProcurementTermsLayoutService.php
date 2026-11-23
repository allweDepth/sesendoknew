<?php

/** Semantic layout shared by standalone and bundled construction contract terms. */
final class ProcurementTermsLayoutService
{
    public static function rows(array $rows):array
    {
        $out=[];
        foreach($rows as $row){
            if(is_string($row))$row=['text'=>$row];
            $text=trim((string)($row['text']??''));if($text==='')continue;
            $format=$row['format']??'body';
            $heading=$format==='clause-heading';
            $marker='';$indent=0.0;
            if(!$heading&&preg_match('/^(\d+(?:\.\d+)*(?:[.)])?|\([a-zA-Z0-9]+\)|[a-zA-Z][.)]|[ivxlcdm]+[.)]|[•●▪])\s+(.+)$/su',$text,$m)){
                $marker=$m[1];$text=$m[2];$format='hanging-list';
                $indent=preg_match('/^\d/',$marker)?0.0:(preg_match('/^[ivxlcdm]{2,}[.)]$/i',$marker)?16.0:8.0);
            }
            // The converted Word reference contains line fragments; keep complete sentences together.
            $last=array_key_last($out);
            if($format==='body'&&$last!==null&&$out[$last]['kind']!=='heading'&&!preg_match('/[.;:!?]$/u',$out[$last]['text'])){
                $out[$last]['text'].=' '.$text;continue;
            }
            $out[]=['kind'=>$heading?'heading':'paragraph','marker'=>$marker,'indent'=>$indent,'text'=>$text,'align'=>$heading?($row['align']??'left'):'justify'];
        }
        // Some converted Word pages place the next caption ahead of the preceding clause's continuation.
        // Anchor those captions to their own first numbered subclause, keeping every sentence intact.
        $numbers=[];foreach($out as $row)if($row['kind']==='heading'&&preg_match('/^(\d+)\.\s/u',$row['text'],$m))$numbers[]=(int)$m[1];
        foreach($numbers as $number){
            $heading=null;$first=null;
            foreach($out as $i=>$row){if($row['kind']==='heading'&&preg_match('/^'.preg_quote((string)$number,'/').'\.\s/u',$row['text']))$heading=$i;if($row['marker']===$number.'.1'){$first=$i;break;}}
            if($heading===null||$first===null||$heading>=$first)continue;
            $misplaced=false;
            for($i=$heading+1;$i<$first;$i++)if(($out[$i]['kind']==='heading'&&preg_match('/^\d+\./u',$out[$i]['text']))||(preg_match('/^(\d+)\.\d+/u',$out[$i]['marker'],$m)&&(int)$m[1]!==$number)){$misplaced=true;break;}
            if($misplaced){$caption=$out[$heading];array_splice($out,$heading,1);array_splice($out,$first-1,0,[$caption]);}
        }
        return $out;
    }
}
