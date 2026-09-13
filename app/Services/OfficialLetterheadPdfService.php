<?php
require_once __DIR__.'/../Core/DB.php';

final class OfficialLetterheadPdfService
{
    public static function draw(TCPDF $pdf,array $user):void
    {
        try{$kop=DB::getInstance()->query('SELECT * FROM kop_surat_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND aktif=1 AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$user['kd_wilayah']??'', $user['kd_opd']??'', $user['tahun']??date('Y')])->fetch()?:[];}catch(Throwable $e){$kop=[];}
        $left=(float)$pdf->getMargins()['left'];$right=(float)$pdf->getMargins()['right'];$width=$pdf->getPageWidth()-$left-$right;$y=max((float)$pdf->GetY(),10.0);
        $root=dirname(__DIR__,2).'/';
        if(!empty($kop['gunakan_gambar_kop'])&&!empty($kop['gambar_kop'])&&is_file($root.ltrim($kop['gambar_kop'],'/'))){$pdf->Image($root.ltrim($kop['gambar_kop'],'/'),$left,$y,$width,0,'','','',false,300);$pdf->SetY($y+30);return;}
        $logoWidth=20.0;
        foreach([['logo_kiri',$left],['logo_kanan',$left+$width-$logoWidth]] as [$field,$x])if(!empty($kop[$field])&&is_file($root.ltrim($kop[$field],'/')))$pdf->Image($root.ltrim($kop[$field],'/'),$x,$y,$logoWidth,$logoWidth,'','','',false,300);
        $textLeft=$left+$logoWidth+3;$textWidth=max(40,$width-(2*($logoWidth+3)));
        $pdf->SetXY($textLeft,$y);$pdf->SetFont('helvetica','B',11);$pdf->MultiCell($textWidth,5,strtoupper((string)($kop['nama_pemerintah']??$user['nama_pemda']??'PEMERINTAH DAERAH')),0,'C');
        $pdf->SetX($textLeft);$pdf->SetFont('helvetica','B',14);$pdf->MultiCell($textWidth,6,strtoupper((string)($kop['nama_opd']??$user['nama_opd']??$user['nama_org']??'PERANGKAT DAERAH')),0,'C');
        $contact=trim(implode(' · ',array_filter([$kop['telepon']??null,$kop['email']??null,$kop['website']??null])));$address=trim((string)($kop['alamat']??$user['alamat_opd']??'').($contact?' · '.$contact:'').(!empty($kop['kode_pos'])?' · '.$kop['kode_pos']:''));
        $pdf->SetX($textLeft);$pdf->SetFont('helvetica','',8);$pdf->MultiCell($textWidth,4,$address,0,'C');
        $hex=ltrim((string)($kop['warna_garis']??'#000000'),'#');$rgb=preg_match('/^[0-9a-f]{6}$/i',$hex)?[hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))]:[0,0,0];
        $lineY=max($y+23,(float)$pdf->GetY()+1);$pdf->SetDrawColor(...$rgb);$pdf->SetLineWidth(.7);$pdf->Line($left,$lineY,$left+$width,$lineY);$pdf->SetLineWidth(.2);$pdf->Line($left,$lineY+1,$left+$width,$lineY+1);$pdf->SetDrawColor(0,0,0);$pdf->SetY($lineY+5);
    }
}
