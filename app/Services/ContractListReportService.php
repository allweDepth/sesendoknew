<?php
require_once __DIR__.'/ProcurementProcessService.php';
require_once __DIR__.'/ExcelPresentationService.php';

/** Complete, scoped contract list shared by Excel and PDF reports. */
final class ContractListReportService
{
    public const COLUMNS=[
        'nomor_rup'=>'KODE RUP','uraian_kontrak'=>'NAMA PEKERJAAN','penyedia'=>'PENYEDIA',
        'total_anggaran'=>'PAGU','nilai_hps'=>'NILAI HPS','nilai_kontrak'=>'NILAI KONTRAK',
        'nomor_kontrak'=>'NOMOR KONTRAK / SPK','tanggal_kontrak'=>'TANGGAL KONTRAK',
        'waktu_pelaksanaan'=>'WAKTU PELAKSANAAN (HARI KALENDER)','tanggal_mulai'=>'TANGGAL MULAI','tanggal_selesai'=>'TANGGAL SELESAI',
        'nomor_spmk'=>'NOMOR SPMK','tanggal_spmk'=>'TANGGAL SPMK',
        'nomor_undangan'=>'NOMOR UNDANGAN','tanggal_undangan'=>'TANGGAL UNDANGAN',
        'tanggal_persiapan'=>'TANGGAL PERSIAPAN / MINI KOMPETISI','tanggal_penawaran'=>'TANGGAL PEMASUKAN PENAWARAN','tanggal_pembukaan'=>'TANGGAL PEMBUKAAN PENAWARAN',
        'tanggal_evaluasi'=>'TANGGAL EVALUASI','nomor_negosiasi'=>'NOMOR BA KLARIFIKASI / NEGOSIASI','tanggal_negosiasi'=>'TANGGAL KLARIFIKASI / NEGOSIASI',
        'nomor_sppbj'=>'NOMOR SPPBJ','tanggal_sppbj'=>'TANGGAL SPPBJ',
        'masa_pemeliharaan'=>'MASA PEMELIHARAAN','tahap'=>'TAHAP ANGGARAN','kd_sub_keg'=>'KODE SUBKEGIATAN',
        'jenis_pengadaan'=>'JENIS PENGADAAN','metode_pemilihan'=>'METODE PEMILIHAN','bentuk_kontrak'=>'BENTUK KONTRAK',
        'nama_ppk'=>'PPK','nama_pptk'=>'PPTK','status_kontrak'=>'STATUS KONTRAK',
    ];
    public static function enrich(array $rows,DB $db):array
    {
        if(!$rows)return [];
        $ids=array_values(array_unique(array_map('intval',array_column($rows,'id'))));
        $refs=[];
        foreach(array_chunk($ids,500) as $batch){
            $marks=implode(',',array_fill(0,count($batch),'?'));
            foreach($db->query("SELECT kontrak_id,kode_dokumen,nomor_dokumen,tanggal_dokumen FROM dokumen_pengadaan_neo WHERE kontrak_id IN ($marks) AND is_deleted=0 ORDER BY id",$batch)->fetchAll() as $d)$refs[(int)$d['kontrak_id']][$d['kode_dokumen']]=$d;
        }
        $officialIds=[];foreach($rows as $r)foreach(['ppk_id','pptk_id'] as $k)if(!empty($r[$k]))$officialIds[]=(int)$r[$k];
        $officials=[];foreach(array_chunk(array_values(array_unique($officialIds)),500) as $batch){$marks=implode(',',array_fill(0,count($batch),'?'));foreach($db->query("SELECT id,nama_pegawai FROM pejabat_tahunan_neo WHERE id IN ($marks) AND is_deleted=0",$batch)->fetchAll() as $o)$officials[(int)$o['id']]=$o['nama_pegawai'];}
        foreach($rows as &$r){$r['dokumen_referensi']=$refs[(int)$r['id']]??[];$r['nama_ppk']=$officials[(int)($r['ppk_id']??0)]??$r['nama_ppk']??'';$r['nama_pptk']=$officials[(int)($r['pptk_id']??0)]??$r['nama_pptk']??'';$r=array_replace($r,self::project($r));}unset($r);
        return $rows;
    }
    public static function project(array $c):array
    {
        $p=ProcurementProcessService::values($c);
        $stage=static fn($code,$field)=>$p[$code][$field]??'';
        $pick=static function(...$values){foreach($values as $v)if($v!==null&&$v!=='')return $v;return '';};
        $out=array_intersect_key($c,self::COLUMNS);
        $out['penyedia']=$pick($c['penyedia']??null,$c['nama_penyedia_resmi']??null,$c['nama_penyedia']??null);
        $out['tanggal_spmk']=$pick($c['tanggal_spmk']??null,$c['dokumen_referensi']['SPMK']['tanggal_dokumen']??null,$stage('SPMK','tanggal'));
        $out['nomor_spmk']=$pick($c['nomor_spmk']??null,$c['dokumen_referensi']['SPMK']['nomor_dokumen']??null,$stage('SPMK','nomor'));
        $out['tanggal_undangan']=$stage('UNDANGAN_PL','tanggal');$out['nomor_undangan']=$stage('UNDANGAN_PL','nomor');
        foreach(['tanggal_persiapan'=>'PERSIAPAN_PESANAN','tanggal_penawaran'=>'UPLOAD_PENAWARAN','tanggal_pembukaan'=>'PEMBUKAAN_PENAWARAN','tanggal_evaluasi'=>'BA_EVALUASI','tanggal_negosiasi'=>'BA_KLARIFIKASI_NEGOSIASI','tanggal_sppbj'=>'SPPBJ'] as $key=>$code)$out[$key]=$stage($code,'tanggal');
        $out['nomor_negosiasi']=$stage('BA_KLARIFIKASI_NEGOSIASI','nomor');$out['nomor_sppbj']=$stage('SPPBJ','nomor');
        $out['masa_pemeliharaan']=!empty($c['masa_pemeliharaan_bulan'])?$c['masa_pemeliharaan_bulan'].' bulan':(!empty($c['masa_pemeliharaan'])?$c['masa_pemeliharaan'].' hari kalender':'');
        return array_replace(array_fill_keys(array_keys(self::COLUMNS),''),$out);
    }
    public static function exportRows(array $rows):array
    {
        return array_map(static fn($c)=>self::project($c),$rows);
    }
    public static function addSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $book,array $rows,array $user):void
    {
        $s=$book->createSheet();$s->setTitle('Daftar Kontrak Lengkap');
        $s->fromArray(array_values(self::COLUMNS),null,'A1');
        foreach(self::exportRows($rows) as $i=>$r){
            $j=1;foreach($r as $key=>$value){
                $cell=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j++).($i+2);
                if($value!==''&&$value!==null&&str_starts_with($key,'tanggal_')){$s->setCellValue($cell,\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTimeImmutable($value)));$s->getStyle($cell)->getNumberFormat()->setFormatCode('dd/mm/yyyy');}
                elseif(in_array($key,['total_anggaran','nilai_hps','nilai_kontrak','waktu_pelaksanaan'],true)&&$value!==''&&$value!==null){$s->setCellValue($cell,(float)$value);$s->getStyle($cell)->getNumberFormat()->setFormatCode($key==='waktu_pelaksanaan'?'0':'#,##0.00');}
                else $s->setCellValueExplicit($cell,(string)$value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        $last=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::COLUMNS));
        $s->freezePane('C2');$s->setAutoFilter('A1:'.$last.max(1,count($rows)+1));$s->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,1);
        PageSetupService::applyExcel($s,PageSetupService::current($user),'L');
        $book->setActiveSheetIndex($book->getIndex($s));
    }
    public static function appendPdf(TCPDF $pdf,array $rows):void
    {
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $date=static fn($v)=>$v?date('d/m/Y',strtotime($v)):'-';
        $pdf->AddPage('L');$pdf->SetFont('helvetica','B',12);$pdf->MultiCell(0,8,'DAFTAR KONTRAK DAN JADWAL PROSES',0,'C');$pdf->Ln(3);$pdf->SetFont('helvetica','',8);
        $heads=['No','Pekerjaan / Penyedia','Nilai (Rp)','Kontrak / SPMK','Pelaksanaan','Tanggal Proses','PPK / PPTK / Status'];$widths=[4,23,12,20,13,17,11];
        $open='<table border="1" cellpadding="4" cellspacing="0" width="100%">';$header=$open.'<tr nobr="true" bgcolor="#e8f1fb">';foreach($heads as $i=>$h)$header.='<th width="'.$widths[$i].'%"><b>'.$h.'</b></th>';$header.='</tr></table>';
        $writeHeader=static fn()=>$pdf->writeHTML($header,false,false,true,false,'');$writeHeader();
        $m=$pdf->getMargins();$width=$pdf->getPageWidth()-$m['left']-$m['right'];
        foreach(self::exportRows($rows) as $i=>$r){
            $money=static fn($v)=>($v===''||$v===null)?'-':number_format((float)$v,2,',','.');
            $cells=[(string)($i+1),$r['uraian_kontrak']."\nPenyedia: ".$r['penyedia']."\nRUP: ".$r['nomor_rup'],"Pagu: ".$money($r['total_anggaran'])."\nHPS: ".$money($r['nilai_hps'])."\nKontrak: ".$money($r['nilai_kontrak']),$r['nomor_kontrak']."\nTanggal: ".$date($r['tanggal_kontrak'])."\nSPMK: ".$r['nomor_spmk']."\nTanggal SPMK: ".$date($r['tanggal_spmk']),"Durasi: ".($r['waktu_pelaksanaan']===''||$r['waktu_pelaksanaan']===null?'-':$r['waktu_pelaksanaan'])." hari\nMulai: ".$date($r['tanggal_mulai'])."\nSelesai: ".$date($r['tanggal_selesai'])."\nPemeliharaan: ".$r['masa_pemeliharaan'],"Undangan: ".$date($r['tanggal_undangan'])."\nPersiapan: ".$date($r['tanggal_persiapan'])."\nPenawaran: ".$date($r['tanggal_penawaran'])."\nPembukaan: ".$date($r['tanggal_pembukaan'])."\nEvaluasi: ".$date($r['tanggal_evaluasi'])."\nNegosiasi: ".$date($r['tanggal_negosiasi'])."\nSPPBJ: ".$date($r['tanggal_sppbj']),"PPK: ".$r['nama_ppk']."\nPPTK: ".$r['nama_pptk']."\nStatus: ".$r['status_kontrak']];
            $height=0;foreach($cells as $j=>$text)$height=max($height,$pdf->getStringHeight($width*$widths[$j]/100-4,$text));
            if($pdf->GetY()+$height+7>$pdf->getPageHeight()-$pdf->getBreakMargin()){$pdf->AddPage('L');$writeHeader();}
            $html=$open.'<tr nobr="true">';foreach($cells as $j=>$text)$html.='<td width="'.$widths[$j].'%">'.nl2br($e($text),false).'</td>';$pdf->writeHTML($html.'</tr></table>',false,false,true,false,'');
        }
        if(!$rows)$pdf->writeHTML('<p>Tidak ada data kontrak.</p>',true,false,true,false,'');
    }
}
