<?php

require_once __DIR__.'/../Core/DB.php';

/** Satu sumber konfigurasi halaman untuk seluruh generator dokumen. */
final class PageSetupService
{
    public static function current(?array $user=null):array
    {
        $user=$user??($_SESSION['user']??[]);
        $defaults=['paper'=>'A4','orientation'=>'AUTO','font'=>'helvetica','font_size'=>10.0,'paper_width'=>null,'paper_height'=>null,'margin_top'=>10.0,'margin_right'=>10.0,'margin_bottom'=>12.0,'margin_left'=>10.0,'margin_header'=>5.0,'margin_footer'=>8.0,
            'header_enabled'=>false,'footer_enabled'=>false,'header_height'=>12.0,'footer_height'=>10.0,
            'signature_height'=>35.0,'signature_position'=>'kanan','signature_text'=>'',
            'header'=>self::sectionDefaults('header'),'footer'=>self::sectionDefaults('footer')];
        try{
            $opd=(string)($user['kd_opd']??'0');$row=null;
            if($opd!==''&&$opd!=='0')$row=DB::getInstance()->query('SELECT * FROM page_setup_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 LIMIT 1',[$user['kd_wilayah']??'',$opd,(int)($user['tahun']??date('Y'))])->fetch();
            if(!$row)$row=DB::getInstance()->query(
                'SELECT * FROM pengaturan_neo WHERE kd_wilayah=? AND tahun=? AND is_deleted=0 ORDER BY id DESC LIMIT 1',
                [$user['kd_wilayah']??'',(int)($user['tahun']??date('Y'))]
            )->fetch();
            if(!$row)return$defaults;
            $paper=strtoupper((string)($row['ukuran_kertas']??'A4'));
            if(!array_key_exists($paper,self::paperSizes()))$paper='A4';
            $orientation=in_array(strtoupper((string)($row['orientasi_kertas']??'')),['AUTO','P','L'],true)?strtoupper($row['orientasi_kertas']):'AUTO';
            $font=in_array(strtolower((string)($row['font_pdf']??'')),['helvetica','times','courier'],true)?strtolower($row['font_pdf']):'helvetica';
            $size=max(6,min(18,(float)($row['ukuran_font_pdf']??10)));
            $number=static fn($value,float $fallback,float $min=0,float $max=100):float=>is_numeric($value)?max($min,min($max,(float)$value)):$fallback;
            return ['paper'=>$paper,'orientation'=>$orientation,'font'=>$font,'font_size'=>$size,
                'paper_width'=>$number($row['lebar_kertas_mm']??null,210,50,2000),'paper_height'=>$number($row['tinggi_kertas_mm']??null,297,50,2000),
                'margin_top'=>$number($row['margin_atas_mm']??null,10),'margin_right'=>$number($row['margin_kanan_mm']??null,10),
                'margin_bottom'=>$number($row['margin_bawah_mm']??null,12),'margin_left'=>$number($row['margin_kiri_mm']??null,10),
                'margin_header'=>$number($row['margin_header_mm']??null,5),'margin_footer'=>$number($row['margin_footer_mm']??null,8),
                'header_enabled'=>(bool)($row['header_pdf_aktif']??false),'footer_enabled'=>(bool)($row['footer_pdf_aktif']??false),
                'header_height'=>$number($row['tinggi_header_mm']??null,12,4,80),'footer_height'=>$number($row['tinggi_footer_mm']??null,10,4,80),
                'header'=>self::decodeSection($row['header_pdf_json']??null,'header'),'footer'=>self::decodeSection($row['footer_pdf_json']??null,'footer'),
                'signature_height'=>$number($row['tinggi_tanda_tangan_mm']??null,35,10,120),'signature_position'=>$row['posisi_tanda_tangan']??'kanan','signature_text'=>$row['teks_tanda_tangan']??''];
        }catch(Throwable){return$defaults;}
    }

    public static function tcpdfFormat(array $settings):string|array
    {
        $paper=strtoupper((string)($settings['paper']??'A4'));
        if($paper==='CUSTOM')return [(float)($settings['paper_width']??210),(float)($settings['paper_height']??297)];
        return self::paperSizes()[$paper]??'A4';
    }

    public static function applyPdf(TCPDF $pdf,array $settings,?array $fallbackMargins=null):void
    {
        $pdf->SetFont((string)($settings['font']??'helvetica'),'',(float)($settings['font_size']??10));
        $fallbackMargins=$fallbackMargins??[10,10,10,12];
        [$left,$top,$right,$bottom]=array_pad($fallbackMargins,4,10);
        $left=(float)($settings['margin_left']??$left);$top=(float)($settings['margin_top']??$top);
        $right=(float)($settings['margin_right']??$right);$bottom=(float)($settings['margin_bottom']??$bottom);
        $headerEnabled=!empty($settings['header_enabled']);$footerEnabled=!empty($settings['footer_enabled']);
        if($headerEnabled)$top=max($top,(float)($settings['margin_header']??5)+(float)($settings['header_height']??12)+2);
        if($footerEnabled)$bottom=max($bottom,(float)($settings['margin_footer']??8)+(float)($settings['footer_height']??10)+2);
        $pdf->SetMargins($left,$top,$right);$pdf->SetHeaderMargin((float)($settings['margin_header']??5));
        $pdf->SetFooterMargin((float)($settings['margin_footer']??8));$pdf->SetAutoPageBreak(true,$bottom);
        $pdf->setPrintHeader($headerEnabled);$pdf->setPrintFooter($footerEnabled);
        if($pdf instanceof PageSetupPdf)$pdf->configurePageSetup($settings);
    }

    public static function createPdf(array $settings,string $fallbackOrientation='P'):TCPDF
    {
        require_once __DIR__.'/PageSetupPdf.php';
        return new PageSetupPdf(self::orientation($settings,$fallbackOrientation),'mm',self::tcpdfFormat($settings),true,'UTF-8',false);
    }

    public static function applyExcel(object $sheet,array $settings,string $fallback='P'):void
    {
        // Page Setup global sengaja hanya memengaruhi PDF. Ekspor Excel
        // mempertahankan konfigurasi spesifik workbook masing-masing.
    }

    public static function orientation(array $settings,string $fallback='P'):string
    {
        return ($settings['orientation']??'AUTO')==='AUTO'?$fallback:$settings['orientation'];
    }

    private static function paperSizes():array
    {
        return ['A0'=>'A0','A1'=>'A1','A2'=>'A2','A3'=>'A3','A3PLUS'=>[329,483],'A4'=>'A4','A5'=>'A5','A6'=>'A6',
            'PHOTO_89_127'=>[89,127],'PHOTO_102_152'=>[102,152],'PHOTO_127_178'=>[127,178],'PHOTO_127_203'=>[127,203],
            'WIDE_102_181'=>[102,181],'PHOTO_203_254'=>[203,254],'POSTCARD_100_148'=>[100,148],
            'F4'=>[216,330],'FOLIO_216_330'=>[216,330],'LEGAL'=>'LEGAL','LETTER'=>'LETTER','INDIAN_LEGAL'=>[215,345],
            'JIS_B4'=>[257,364],'JIS_B5'=>[182,257],'ENVELOPE_10'=>[105,241],'C4'=>'C4','C6'=>'C6','DL'=>'DL','CUSTOM'=>[210,297]];
    }

    private static function sectionDefaults(string $kind):array
    {
        return ['font'=>'helvetica','size'=>8.0,'color'=>'#222222','bold'=>false,'italic'=>false,'underline'=>false,'divider'=>false,
            'columns'=>[
                ['text'=>'','width'=>25.0,'align'=>'L'],
                ['text'=>'','width'=>50.0,'align'=>'C'],
                ['text'=>$kind==='footer'?'{page} / {pages}':'','width'=>25.0,'align'=>'R']
            ]];
    }

    private static function decodeSection(mixed $json,string $kind):array
    {
        $defaults=self::sectionDefaults($kind);$data=is_array($json)?$json:json_decode((string)$json,true);
        if(!is_array($data))return$defaults;
        $fonts=['helvetica','times','courier'];$data['font']=in_array(strtolower((string)($data['font']??'')),$fonts,true)?strtolower((string)$data['font']):$defaults['font'];
        $data['size']=max(5,min(24,(float)($data['size']??$defaults['size'])));$data['color']=preg_match('/^#[0-9a-f]{6}$/i',(string)($data['color']??''))?(string)$data['color']:$defaults['color'];
        foreach(['bold','italic','underline','divider'] as $flag)$data[$flag]=!empty($data[$flag]);
        $columns=[];foreach(array_slice(is_array($data['columns']??null)?$data['columns']:[],0,3) as $i=>$column){$base=$defaults['columns'][$i];$columns[]=['text'=>mb_substr(strip_tags((string)($column['text']??'')),0,500),'width'=>max(5,min(90,(float)($column['width']??$base['width']))),'align'=>in_array(strtoupper((string)($column['align']??'')),['L','C','R'],true)?strtoupper((string)$column['align']):$base['align']];}
        while(count($columns)<3)$columns[]=$defaults['columns'][count($columns)];$sum=array_sum(array_column($columns,'width'));if($sum<=0)$columns=$defaults['columns'];else foreach($columns as &$column)$column['width']=100*$column['width']/$sum;
        $data['columns']=$columns;return$data;
    }
}
