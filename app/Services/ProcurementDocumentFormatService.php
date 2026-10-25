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
        $pdf->SetFont(self::BODY_FONT,'',self::BODY_SIZE);
        $s=ContractPrintSettingsService::validate($settings);
        $pdf->setCellHeightRatio($s['line_spacing']);
        $pdf->setHtmlVSpace(['p'=>[0=>['h'=>$s['paragraph_before']*25.4/72,'n'=>$s['paragraph_before']>0?1:0],1=>['h'=>$s['paragraph_after']*25.4/72,'n'=>$s['paragraph_after']>0?1:0]]]);
        $pdf->setListIndentWidth(6);
        if($pdf instanceof PageSetupPdf)$pdf->configureContractTypography();
    }
    public static function paragraphHtml(string $html):string
    {
        // A manual break before another number starts a new list item, not a
        // continuation of the preceding item's hanging-indent cell.
        $html=preg_replace_callback('/<p([^>]*)>(.*?)<\/p>/su',static fn($p)=>'<p'.$p[1].'>'.preg_replace('/<br\s*\/?>(\s*\d+(?:\.\d+)*[.)]\s+)/iu','</p><p'.$p[1].'>$1',$p[2]).'</p>',$html);
        $html=preg_replace_callback('/<p>(\d+(?:\.\d+)*[.)])\s+(.*?)<\/p>/su',static function($m){
            $nested=substr_count(rtrim($m[1],'.)'),'.')>0;
            $marker=$nested?5:4;
            return '<table width="100%" cellpadding="0"><tr>'.($nested?'<td width="4%"></td>':'').'<td width="'.$marker.'%" align="left">'.$m[1].'</td><td width="'.(100-$marker-($nested?4:0)).'%"><p align="justify">'.$m[2].'</p></td></tr></table>';
        },$html);
        return preg_replace('/<p(?![^>]*\balign=)([^>]*)>/u','<p align="justify"$1>',$html);
    }
    public static function registerFonts(TCPDF $pdf):void
    {
        $root=__DIR__.'/../../resources/fonts/tata_naskah/';
        foreach([''=>'arial','B'=>'arialbd','I'=>'ariali','BI'=>'arialbi'] as $style=>$file)$pdf->AddFont(self::BODY_FONT,$style,$root.$file.'.php');
    }
    public static function supports(string $code,array $c):bool
    {
        return ($c['kd_wilayah']??'')==='76.01'&&($c['kd_opd']??'')==='1.03.0.00.0.00.01.0000'&&(float)($c['total_anggaran']??0)<=100000000&&(str_contains($c['jenis_pengadaan']??'','KONSULTANSI')||str_contains($c['nomor_kontrak']??'','Konsultansi'));
    }
    public static function setup(array $setup,array $settings=[]):array
    {
        return array_merge($setup,['paper'=>'LEGAL','orientation'=>'AUTO','font'=>self::BODY_FONT,'font_size'=>self::BODY_SIZE,'margin_left'=>18,'margin_right'=>18,'margin_top'=>25,'margin_bottom'=>20,'header_enabled'=>false,'footer_enabled'=>false],ContractPrintSettingsService::validate($settings));
    }
    public static function documentNumber(array $c,string $code):string
    {
        if(str_starts_with($code,'SPK_')||$code==='SURAT_PERJANJIAN')return $c['nomor_kontrak'];
        if($code==='SPMK')return $c['nomor_spmk'];
        $suffix=['UNDANGAN_PL'=>'UND','BA_EVALUASI'=>'BA-EVALUASI','BA_KLARIFIKASI_NEGOSIASI'=>'KLAR-NEG','LAMPIRAN_NEGOSIASI'=>'KLAR-NEG','DAFTAR_HADIR_KLARIFIKASI'=>'KLAR-NEG','BAHP'=>'BAHPL','PENETAPAN_PENYEDIA'=>'BAHPL','PENGUMUMAN_PENYEDIA'=>'BAHPL','SPPBJ'=>'SPPBJ','RAPAT_PRA_KONTRAK'=>'PAM-PC'][$code]??str_replace('_','-',$code);
        return ($c['nomor_rup']?:$c['id']).'/'.$suffix.'/'.$c['tahun'];
    }
}
