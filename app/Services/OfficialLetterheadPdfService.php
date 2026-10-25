<?php
require_once __DIR__.'/../Core/DB.php';

final class OfficialLetterheadPdfService
{
    public static function settings(array $user):array
    {
        $db=DB::getInstance();
        $kop=$db->query('SELECT * FROM kop_surat_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND aktif=1 AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$user['kd_wilayah']??'', $user['kd_opd']??'', $user['tahun']??date('Y')])->fetch()?:[];
        if(!self::imagePath($kop['logo_kiri']??'')){
            $region=$db->query('SELECT logo FROM wilayah_neo WHERE kode=? AND is_deleted=0 LIMIT 1',[$user['kd_wilayah']??''])->fetch();
            if(self::imagePath($region['logo']??''))$kop['logo_kiri']=$region['logo'];
            elseif(($user['kd_wilayah']??'')==='76.01')$kop['logo_kiri']='public/assets/img/umum/logo.png';
        }
        return $kop;
    }

    public static function imagePath(string $path):?string
    {
        if($path==='')return null;$root=dirname(__DIR__,2).'/';
        foreach([$root.ltrim($path,'/'),$root.'public/'.ltrim($path,'/')] as $candidate){
            $real=realpath($candidate);
            if($real&&str_starts_with($real,$root)&&is_file($real)&&is_readable($real))return $real;
        }
        return null;
    }

    public static function image(TCPDF $pdf,string $path,float $x,float $y,float $w,float $h=0):void
    {
        $level=error_reporting();error_reporting($level & ~E_DEPRECATED);
        try{$pdf->Image($path,$x,$y,$w,$h,'','','',false,300);}finally{error_reporting($level);}
    }

    public static function draw(TCPDF $pdf,array $user):void
    {
        try{$kop=self::settings($user);}catch(Throwable $e){$kop=[];}
        $left=(float)$pdf->getMargins()['left'];$right=(float)$pdf->getMargins()['right'];$width=$pdf->getPageWidth()-$left-$right;$y=max((float)$pdf->GetY(),10.0);
        $full=self::imagePath($kop['gambar_kop']??'');
        if(!empty($kop['gunakan_gambar_kop'])&&$full){self::image($pdf,$full,$left,$y,$width);$pdf->SetXY($left,$y+30);return;}
        $logoWidth=20.0;
        $leftLogo=self::imagePath($kop['logo_kiri']??'');$rightLogo=self::imagePath($kop['logo_kanan']??'');
        foreach([[$leftLogo,$left],[$rightLogo,$left+$width-$logoWidth]] as [$path,$x])if($path)self::image($pdf,$path,$x,$y,$logoWidth,$logoWidth);
        $font=$user['_letterhead_font']??'helvetica';
        $textLeft=$left+($leftLogo?$logoWidth+3:0);$textWidth=max(40,$width-($leftLogo?$logoWidth+3:0)-($rightLogo?$logoWidth+3:0));
        $pdf->SetXY($textLeft,$y);$pdf->SetFont($font,'B',11);$pdf->MultiCell($textWidth,5,strtoupper((string)($kop['nama_pemerintah']??$user['nama_pemda']??'PEMERINTAH DAERAH')),0,'C');
        $pdf->SetX($textLeft);$pdf->SetFont($font,'B',14);$pdf->MultiCell($textWidth,6,strtoupper((string)($kop['nama_opd']??$user['nama_opd']??$user['nama_org']??'PERANGKAT DAERAH')),0,'C');
        $contact=trim(implode(' · ',array_filter([$kop['telepon']??null,$kop['email']??null,$kop['website']??null])));$address=trim((string)($kop['alamat']??$user['alamat_opd']??'').($contact?' · '.$contact:'').(!empty($kop['kode_pos'])?' · '.$kop['kode_pos']:''));
        $pdf->SetX($textLeft);$pdf->SetFont($font,'',8);$pdf->MultiCell($textWidth,4,$address,0,'C');
        $hex=ltrim((string)($kop['warna_garis']??'#000000'),'#');$rgb=preg_match('/^[0-9a-f]{6}$/i',$hex)?[hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))]:[0,0,0];
        $lineY=max($y+23,(float)$pdf->GetY()+1);$pdf->SetDrawColor(...$rgb);$pdf->SetLineWidth(.7);$pdf->Line($left,$lineY,$left+$width,$lineY);$pdf->SetLineWidth(.2);$pdf->Line($left,$lineY+1,$left+$width,$lineY+1);$pdf->SetDrawColor(0,0,0);$pdf->SetXY($left,$lineY+5);
    }
}
