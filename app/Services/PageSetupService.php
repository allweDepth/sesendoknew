<?php

require_once __DIR__.'/../Core/DB.php';

/** Satu sumber konfigurasi halaman untuk seluruh generator dokumen. */
final class PageSetupService
{
    public static function current(?array $user=null):array
    {
        $user=$user??($_SESSION['user']??[]);
        $defaults=['paper'=>'A4','orientation'=>'AUTO','font'=>'helvetica','font_size'=>10.0];
        try{
            $row=DB::getInstance()->query(
                'SELECT ukuran_kertas,orientasi_kertas,font_pdf,ukuran_font_pdf FROM pengaturan_neo WHERE kd_wilayah=? AND tahun=? AND is_deleted=0 ORDER BY id DESC LIMIT 1',
                [$user['kd_wilayah']??'',(int)($user['tahun']??date('Y'))]
            )->fetch();
            if(!$row)return$defaults;
            $paper=in_array(strtoupper((string)($row['ukuran_kertas']??'')),['A4','F4','LEGAL'],true)?strtoupper($row['ukuran_kertas']):'A4';
            $orientation=in_array(strtoupper((string)($row['orientasi_kertas']??'')),['AUTO','P','L'],true)?strtoupper($row['orientasi_kertas']):'AUTO';
            $font=in_array(strtolower((string)($row['font_pdf']??'')),['helvetica','times','courier'],true)?strtolower($row['font_pdf']):'helvetica';
            $size=max(6,min(18,(float)($row['ukuran_font_pdf']??10)));
            return ['paper'=>$paper,'orientation'=>$orientation,'font'=>$font,'font_size'=>$size];
        }catch(Throwable){return$defaults;}
    }

    public static function tcpdfFormat(array $settings):string|array
    {
        return ($settings['paper']??'A4')==='F4'?[210,330]:($settings['paper']??'A4');
    }

    public static function applyPdf(TCPDF $pdf,array $settings):void
    {
        $pdf->SetFont((string)($settings['font']??'helvetica'),'',(float)($settings['font_size']??10));
    }

    public static function applyExcel(object $sheet,array $settings,string $fallback='P'):void
    {
        if(!method_exists($sheet,'getPageSetup'))return;
        $setup=$sheet->getPageSetup();
        $orientation=self::orientation($settings,$fallback)==='L'
            ? \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            : \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT;
        $paper=match($settings['paper']??'A4'){
            'LEGAL'=>\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LEGAL,
            'F4'=>\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO,
            default=>\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4,
        };
        $setup->setOrientation($orientation)->setPaperSize($paper)->setFitToWidth(1)->setFitToHeight(0);
    }

    public static function orientation(array $settings,string $fallback='P'):string
    {
        return ($settings['orientation']??'AUTO')==='AUTO'?$fallback:$settings['orientation'];
    }
}
