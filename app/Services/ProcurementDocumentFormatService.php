<?php
require_once __DIR__.'/ContractPrintSettingsService.php';
/** Format metadata only. PDF content is authored in the existing TCPDF renderers. */
final class ProcurementDocumentFormatService
{
    public const BODY_FONT='arial_tata_naskah';
    public const BODY_SIZE=12.0;
    public const LINE_HEIGHT_RATIO=1.5;
    public static function bodyOpdName(string $name):string
    {
        if(!str_contains(trim($name),' ')&&preg_match('/^[A-Z]{2,12}$/',$name))return $name;
        $name=mb_convert_case(mb_strtolower(trim($name),'UTF-8'),MB_CASE_TITLE,'UTF-8');
        return preg_replace_callback('/\b(Dan|Di|Ke|Dari|Untuk|Pada)\b/u',static fn($m)=>mb_strtolower($m[0],'UTF-8'),$name);
    }
    public static function typography(TCPDF $pdf,array $settings=[]):void
    {
        self::registerFonts($pdf);
        $pdf->SetFont($settings['font']??self::BODY_FONT,'',(float)($settings['font_size']??self::BODY_SIZE));
        $s=ContractPrintSettingsService::validate($settings);
        $pdf->setCellHeightRatio($s['line_spacing']);
        $pdf->setHtmlVSpace(['p'=>[0=>['h'=>$s['paragraph_before']*25.4/72,'n'=>$s['paragraph_before']>0?1:0],1=>['h'=>$s['paragraph_after']*25.4/72,'n'=>$s['paragraph_after']>0?1:0]]]);
        $pdf->setListIndentWidth(6);
        if($pdf instanceof PageSetupPdf)$pdf->configureContractTypography();
    }
    public static function paragraphHtml(string $html):string
    {
        // Use the same hanging columns for native HTML lists and typed markers.
        // TCPDF's default <li> alignment otherwise inherits left alignment.
        do {
            $count=0;
            $html=preg_replace_callback('/<(ol|ul)\b([^>]*)>((?:(?!<(?:ol|ul)\b).)*?)<\/\1>/isu',static function($list){
                $number=1;if(preg_match('/\bstart=["\x27]?(\d+)/i',$list[2],$start))$number=(int)$start[1];
                $type='1';if(preg_match('/\btype=["\x27]([1aAiI])["\x27]/',$list[2],$attr))$type=$attr[1];
                preg_match_all('/<li\b([^>]*)>(.*?)<\/li>/isu',$list[3],$items,PREG_SET_ORDER);
                $out='';foreach($items as $item){
                    if(preg_match('/\bvalue=["\x27]?(\d+)/i',$item[1],$value))$number=(int)$value[1];
                    $marker=$list[1]==='ul'?'•':self::listNumber($number,$type).'.';
                    $out.=self::listItemHtml($marker,$item[2]);$number++;
                }return $out;
            },$html,-1,$count);
        } while($count>0);
        // A manual break before another number starts a new list item, not a
        // continuation of the preceding item's hanging-indent cell.
        $html=preg_replace_callback('/<p([^>]*)>(.*?)<\/p>/su',static fn($p)=>'<p'.$p[1].'>'.preg_replace('/<br\s*\/?>(\s*\d+(?:\.\d+)*[.)]\s+)/iu','</p><p'.$p[1].'>$1',$p[2]).'</p>',$html);
        $html=preg_replace_callback('/<p(?:\s+align=["\x27](?:left|justify)["\x27])?>((?:\d+(?:\.\d+)*|[a-zA-Z]|[ivxIVX]+)[.)]|[•])\s+(.*?)<\/p>/su',static function($m){
            $nested=substr_count(rtrim($m[1],'.)'),'.')>0;
            return self::listItemHtml($m[1],$m[2],$nested?4:0);
        },$html);
        return preg_replace('/<p(?![^>]*\balign=)([^>]*)>/u','<p align="justify"$1>',$html);
    }
    private static function listItemHtml(string $marker,string $body,int $indent=0):string
    {
        $body=trim($body);
        if(!preg_match('/^<(?:p|table)\b/i',$body)){
            $parts=preg_split('/(?=<table\b)/i',$body,2);
            $body='<p align="justify">'.$parts[0].'</p>'.($parts[1]??'');
        }
        $width=100-5-$indent;
        return '<table width="100%" cellpadding="0" cellspacing="0"><tr>'.($indent?'<td width="'.$indent.'%"></td>':'').'<td width="5%" align="left" valign="top">'.$marker.'</td><td width="'.$width.'%" align="justify" valign="top">'.$body.'</td></tr></table>';
    }
    private static function listNumber(int $number,string $type):string
    {
        if(in_array($type,['a','A'],true)){$text='';for($n=$number;$n>0;$n=intdiv($n-1,26))$text=chr(97+($n-1)%26).$text;return $type==='A'?strtoupper($text):$text;}
        if(in_array($type,['i','I'],true)){$text='';foreach([1000=>'M',900=>'CM',500=>'D',400=>'CD',100=>'C',90=>'XC',50=>'L',40=>'XL',10=>'X',9=>'IX',5=>'V',4=>'IV',1=>'I'] as $value=>$roman)while($number>=$value){$text.=$roman;$number-=$value;}return $type==='i'?strtolower($text):$text;}
        return (string)$number;
    }
    public static function registerFonts(TCPDF $pdf):void
    {
        $root=__DIR__.'/../../resources/fonts/tata_naskah/';
        foreach([''=>'arial','B'=>'arialbd','I'=>'ariali','BI'=>'arialbi'] as $style=>$file)$pdf->AddFont(self::BODY_FONT,$style,$root.$file.'.php');
    }
    /** Shared rows keep names aligned even when a role or organisation wraps. */
    public static function signatureColumns(array $columns):string
    {
        $width=100/count($columns);
        $html='<table nobr="true" width="100%" cellpadding="4"><tr>';
        foreach($columns as $column)$html.='<td width="'.$width.'%" align="center" valign="top">'.$column['heading'].'</td>';
        $html.='</tr><tr><td colspan="'.count($columns).'"><br><br></td></tr><tr>';
        foreach($columns as $column)$html.='<td width="'.$width.'%" align="center" valign="top"><b><u>'.htmlspecialchars($column['name'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</u></b></td>';
        $html.='</tr><tr>';
        foreach($columns as $column)$html.='<td width="'.$width.'%" align="center" valign="top">'.($column['details']??'').'</td>';
        return $html.'</tr></table>';
    }
    public static function supports(string $code,array $c):bool
    {
        return ($c['kd_wilayah']??'')==='76.01'&&($c['kd_opd']??'')==='1.03.0.00.0.00.01.0000'&&(float)($c['total_anggaran']??0)<=100000000&&(str_contains($c['jenis_pengadaan']??'','KONSULTANSI')||str_contains($c['nomor_kontrak']??'','Konsultansi'));
    }
    public static function setup(array $setup,array $settings=[]):array
    {
        return array_merge(['font'=>self::BODY_FONT,'font_size'=>self::BODY_SIZE],$setup,ContractPrintSettingsService::validate($settings));
    }
    public static function documentNumber(array $c,string $code):string
    {
        if(str_starts_with($code,'SPK_')||$code==='SURAT_PERJANJIAN')return $c['nomor_kontrak'];
        if($code==='SPMK')return $c['nomor_spmk'];
        $suffix=['UNDANGAN_PL'=>'UND','BA_EVALUASI'=>'BA-EVALUASI','BA_KLARIFIKASI_NEGOSIASI'=>'KLAR-NEG','LAMPIRAN_NEGOSIASI'=>'KLAR-NEG','DAFTAR_HADIR_KLARIFIKASI'=>'KLAR-NEG','BAHP'=>'BAHPL','PENETAPAN_PENYEDIA'=>'BAHPL','PENGUMUMAN_PENYEDIA'=>'BAHPL','SPPBJ'=>'SPPBJ','RAPAT_PRA_KONTRAK'=>'PAM-PC'][$code]??str_replace('_','-',$code);
        return ($c['nomor_rup']?:$c['id']).'/'.$suffix.'/'.$c['tahun'];
    }
}
