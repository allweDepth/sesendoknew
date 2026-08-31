<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class KontrakRealisasiService
{
    private DB $db; private array $user;
    public function __construct(array $user) { $this->db=DB::getInstance(); $this->user=$user; }

    private function scope(): array
    {
        $w=$this->user['kd_wilayah']??''; $o=$this->user['kd_opd']??''; $y=(int)($this->user['tahun']??0);
        if (!$w || !$y) throw new RuntimeException('Scope pengguna tidak lengkap');
        return [$w,$o,$y];
    }

    public function summary(): array
    {
        [$w,$o,$y]=$this->scope(); $opdSql=$o && $o!=='0'?' AND k.kd_opd=?':''; $params=[$w,$y]; if($opdSql)$params[]=$o;
        $totals=$this->db->query("SELECT COUNT(*) jumlah_kontrak,COALESCE(SUM(k.total_anggaran),0) total_anggaran,COALESCE(SUM(k.nilai_kontrak),0) nilai_kontrak,COALESCE(SUM(r.realisasi),0) realisasi FROM kontrak_neo k LEFT JOIN (SELECT kontrak_id,SUM(jumlah) realisasi FROM daftar_realisasi_neo WHERE is_deleted=0 GROUP BY kontrak_id) r ON r.kontrak_id=k.id WHERE k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0$opdSql",$params)->fetch();
        $monthly=array_fill(1,12,0.0);
        $rows=$this->db->query("SELECT MONTH(dr.tanggal) bulan,SUM(dr.jumlah) nilai FROM daftar_realisasi_neo dr JOIN kontrak_neo k ON k.id=dr.kontrak_id WHERE k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0 AND dr.is_deleted=0$opdSql GROUP BY MONTH(dr.tanggal)",$params)->fetchAll();
        foreach($rows as $row)$monthly[(int)$row['bulan']]=(float)$row['nilai'];
        $status=$this->db->query("SELECT COALESCE(status_kontrak,'Belum ditetapkan') label,COUNT(*) jumlah FROM kontrak_neo k WHERE k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0$opdSql GROUP BY status_kontrak",$params)->fetchAll();
        return ['totals'=>$totals,'monthly'=>array_values($monthly),'status'=>$status];
    }

    public function contractPdf(int $id): string
    {
        [$w,$o,$y]=$this->scope(); $sql="SELECT k.*,r.nama_perusahaan,r.alamat,r.npwp,r.direktur FROM kontrak_neo k LEFT JOIN rekanan_neo r ON r.id=k.rekanan_id WHERE k.id=? AND k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0"; $params=[$id,$w,$y];
        if($o&&$o!=='0'){ $sql.=' AND k.kd_opd=?'; $params[]=$o; }
        $d=$this->db->query($sql,$params)->fetch(); if(!$d)throw new RuntimeException('Kontrak tidak ditemukan');
        $pdf=new TCPDF('P','mm','A4',true,'UTF-8'); $pdf->SetMargins(18,15,18); $pdf->AddPage(); $pdf->SetFont('helvetica','B',14); $pdf->Cell(0,8,'SURAT PERJANJIAN / KONTRAK',0,1,'C'); $pdf->SetFont('helvetica','',10); $pdf->Cell(0,6,'Nomor: '.($d['nomor_kontrak']??'-'),0,1,'C'); $pdf->Ln(5);
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        $html='<table cellpadding="5"><tr><td width="32%"><b>Pekerjaan</b></td><td width="68%">'.$e($d['uraian_kontrak']).'</td></tr><tr><td><b>Sumber Anggaran</b></td><td>'.strtoupper($e($d['tahap'])).' / '.$e($d['kd_sub_keg']).'</td></tr><tr><td><b>Nilai Kontrak</b></td><td>Rp '.number_format((float)$d['nilai_kontrak'],2,',','.').'</td></tr><tr><td><b>SPK</b></td><td>'.$e($d['nomor_spk']).' tanggal '.$e($d['tanggal_spk']).'</td></tr><tr><td><b>SPMK</b></td><td>'.$e($d['nomor_spmk']).' tanggal '.$e($d['tanggal_spmk']).'</td></tr><tr><td><b>Pelaksanaan</b></td><td>'.$e($d['tanggal_mulai']).' s.d. '.$e($d['tanggal_selesai']).' ('.$e($d['waktu_pelaksanaan']).' hari)</td></tr><tr><td><b>PPK</b></td><td>'.$e($d['nama_ppk']).'</td></tr><tr><td><b>Penyedia</b></td><td>'.$e($d['nama_perusahaan']?:$d['nama_penyedia']).'<br>'.$e($d['alamat']).'<br>NPWP '.$e($d['npwp']).'<br>Direktur '.$e($d['direktur']).'</td></tr></table><br><p>Para pihak sepakat melaksanakan pekerjaan tersebut sesuai ruang lingkup, nilai, jadwal, spesifikasi, serta ketentuan peraturan perundang-undangan yang berlaku.</p>';
        $pdf->writeHTML($html,true,false,true,false,''); $pdf->Ln(15); $pdf->writeHTML('<table><tr><td width="50%" align="center">Pejabat Pembuat Komitmen<br><br><br><br><b>'.$e($d['nama_ppk']).'</b></td><td width="50%" align="center">Penyedia<br><br><br><br><b>'.$e($d['direktur']).'</b></td></tr></table>',true,false,true,false,''); return $pdf->Output('','S');
    }

    public function reportRows(): array
    {
        [$w,$o,$y]=$this->scope(); $sql="SELECT k.id,k.tahap,k.kd_sub_keg,k.nomor_kontrak,k.uraian_kontrak,COALESCE(r.nama_perusahaan,k.nama_penyedia) penyedia,k.total_anggaran,k.nilai_kontrak,COALESCE(SUM(dr.jumlah),0) realisasi,COALESCE(MAX(dr.progress_fisik),0) progress_fisik,k.status_kontrak FROM kontrak_neo k LEFT JOIN rekanan_neo r ON r.id=k.rekanan_id LEFT JOIN daftar_realisasi_neo dr ON dr.kontrak_id=k.id AND dr.is_deleted=0 WHERE k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0"; $params=[$w,$y]; if($o&&$o!=='0'){$sql.=' AND k.kd_opd=?';$params[]=$o;} return $this->db->query($sql.' GROUP BY k.id ORDER BY k.nomor_kontrak',$params)->fetchAll();
    }

    public function reportPdf(): string
    {
        $rows=$this->reportRows(); [,,$y]=$this->scope(); $summary=$this->summary(); $pdf=new TCPDF('L','mm','A4',true,'UTF-8'); $pdf->SetMargins(8,10,8); $pdf->AddPage(); $pdf->SetFont('helvetica','B',13); $pdf->Cell(0,7,'LAPORAN KONTRAK DAN REALISASI TAHUN '.$y,0,1,'C');
        $monthly=$summary['monthly']; $max=max(max($monthly),1); $baseY=48; $startX=18; $barWidth=15; $gap=6; $pdf->SetFont('helvetica','',6); $pdf->SetFillColor(33,133,208); $pdf->Line($startX,$baseY,$startX+12*($barWidth+$gap),$baseY);
        foreach($monthly as $i=>$value){$height=((float)$value/$max)*23;$x=$startX+$i*($barWidth+$gap);$pdf->Rect($x,$baseY-$height,$barWidth,$height,'F');$pdf->Text($x+2,$baseY+1,(string)($i+1));if($value>0)$pdf->Text($x,$baseY-$height-4,number_format((float)$value/1000000,1,',','.').' jt');}
        $pdf->SetY(56); $pdf->SetFont('helvetica','',7); $html='<table border="1" cellpadding="3"><tr style="font-weight:bold;background-color:#e8f1fb"><th width="4%">No</th><th width="12%">Kontrak</th><th width="22%">Pekerjaan</th><th width="14%">Penyedia</th><th width="12%">Anggaran</th><th width="12%">Kontrak</th><th width="12%">Realisasi</th><th width="6%">Fisik</th><th width="6%">Deviasi</th></tr>';
        foreach($rows as $i=>$r){$dev=(float)$r['nilai_kontrak']-(float)$r['realisasi'];$html.='<tr><td>'.($i+1).'</td><td>'.htmlspecialchars((string)$r['nomor_kontrak']).'</td><td>'.htmlspecialchars((string)$r['uraian_kontrak']).'</td><td>'.htmlspecialchars((string)$r['penyedia']).'</td><td align="right">'.number_format((float)$r['total_anggaran'],0,',','.').'</td><td align="right">'.number_format((float)$r['nilai_kontrak'],0,',','.').'</td><td align="right">'.number_format((float)$r['realisasi'],0,',','.').'</td><td align="right">'.number_format((float)$r['progress_fisik'],2,',','.').'%</td><td align="right">'.number_format($dev,0,',','.').'</td></tr>';}
        if(!$rows)$html.='<tr><td colspan="9" align="center">Tidak ada data</td></tr>'; $pdf->writeHTML($html.'</table>',true,false,true,false,''); return $pdf->Output('','S');
    }

    public function reportExcel(): string
    {
        $rows=$this->reportRows(); $book=new Spreadsheet(); $sheet=$book->getActiveSheet(); $sheet->setTitle('Kontrak dan Realisasi');
        $headers=['NO','TAHAP','SUB KEGIATAN','NOMOR KONTRAK','PEKERJAAN','PENYEDIA','ANGGARAN','NILAI KONTRAK','REALISASI','PROGRESS FISIK','DEVIASI']; $sheet->fromArray($headers,null,'A1');
        $row=2; foreach($rows as $i=>$r){$sheet->fromArray([$i+1,$r['tahap'],$r['kd_sub_keg'],$r['nomor_kontrak'],$r['uraian_kontrak'],$r['penyedia'],(float)$r['total_anggaran'],(float)$r['nilai_kontrak'],(float)$r['realisasi'],(float)$r['progress_fisik'],(float)$r['nilai_kontrak']-(float)$r['realisasi']],null,'A'.$row++);}
        $sheet->getStyle('A1:K1')->getFont()->setBold(true); $sheet->getStyle('G2:K'.$row)->getNumberFormat()->setFormatCode('#,##0.00'); foreach(range('A','K') as $c)$sheet->getColumnDimension($c)->setAutoSize(true);
        if($rows){$labels=[new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,"'Kontrak dan Realisasi'!\$H\$1",null,1),new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,"'Kontrak dan Realisasi'!\$I\$1",null,1)];$cats=[new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,"'Kontrak dan Realisasi'!\$D\$2:\$D\$".($row-1),null,count($rows))];$vals=[new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER,"'Kontrak dan Realisasi'!\$H\$2:\$H\$".($row-1),null,count($rows)),new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER,"'Kontrak dan Realisasi'!\$I\$2:\$I\$".($row-1),null,count($rows))];$series=new DataSeries(DataSeries::TYPE_BARCHART,DataSeries::GROUPING_CLUSTERED,range(0,count($vals)-1),$labels,$cats,$vals);$chart=new Chart('realisasi',new Title('Nilai Kontrak vs Realisasi'),new Legend(Legend::POSITION_RIGHT),new PlotArea(null,[$series]));$chart->setTopLeftPosition('M2');$chart->setBottomRightPosition('U18');$sheet->addChart($chart);}
        $tmp=tempnam(sys_get_temp_dir(),'phase4_').'.xlsx'; (new Xlsx($book))->setIncludeCharts(true)->save($tmp); return $tmp;
    }
}
