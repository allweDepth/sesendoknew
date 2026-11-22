<?php

/** Recover semantic paragraphs from older editor snapshots without replacing their wording. */
final class ProcurementParagraphLayoutService
{
    public static function normalize(array $rows):array
    {
        $out=[];
        foreach($rows as $row){
            if(is_string($row))$row=['text'=>$row];
            $text=trim((string)($row['text']??''));if($text==='')continue;
            if(preg_match('/^Pasal\s+(\d+[A-Z]?)(?:\s*[-–:]?\s+)(.+)$/isu',$text,$m)){
                $out[]=array_replace($row,['type'=>'paragraph','format'=>'clause-heading','align'=>'center','style'=>['bold'],'text'=>'Pasal '.$m[1]]);
                $out[]=array_replace($row,['type'=>'paragraph','format'=>'clause-heading','align'=>'center','style'=>['bold'],'text'=>trim($m[2])]);
                continue;
            }
            if((!empty($out)&&preg_match('/^Pasal\s+\d+[A-Z]?$/iu',$out[array_key_last($out)]['text']))||preg_match('/^Pasal\s+\d+[A-Z]?$/iu',$text)||preg_match('/^[A-Z][A-Z ,\/()\-]+$/u',$text)&&(($row['align']??'')==='center'||($row['format']??'')==='clause-heading')){
                $row=array_replace($row,['format'=>'clause-heading','align'=>'center','style'=>['bold']]);
            }elseif(preg_match('/^(?:\([a-zA-Z0-9]+\)|\d+(?:\.\d+)*[.)]|[a-zA-Z][.)]|[•●▪])\s+/u',$text)){
                $row['format']='hanging-list';
            }
            $row['text']=$text;$out[]=$row;
        }
        return $out;
    }
}
