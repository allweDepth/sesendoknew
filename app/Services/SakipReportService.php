<?php
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/PageSetupService.php';

/** Laporan resmi SAKIP dengan seluruh foreign key diterjemahkan menjadi uraian. */
class SakipReportService
{
    private DB $db;
    private array $user;
    public function __construct(array $user) { $this->db=DB::getInstance(); $this->user=$user; }

    public function reportPdf(string $type): string
    {
        $definitions=$this->definitions();
        if (!isset($definitions[$type])) throw new InvalidArgumentException('Jenis laporan SAKIP tidak tersedia');
        $definition=$definitions[$type];
        $rows=$this->db->query($definition['sql'],$this->scopeParams($definition['annual']??true))->fetchAll();
        $pdf=$this->newPdf($definition['orientation']??'L',$definition['title']);
        $this->heading($pdf,$definition['title']);
        $html='<table border="1" cellpadding="3"><thead><tr style="background-color:#d9eaf3;font-weight:bold">';
        foreach($definition['columns'] as $field=>$label)$html.='<th width="'.$definition['widths'][$field].'%">'.$this->e($label).'</th>';
        $html.='</tr></thead><tbody>';
        foreach($rows as $row){$html.='<tr>';foreach($definition['columns'] as $field=>$label){$value=$row[$field]??'-';if(str_contains($field,'anggaran')||str_contains($field,'pagu')||str_contains($field,'realisasi_biaya'))$value='Rp '.number_format((float)$value,2,',','.');$html.='<td width="'.$definition['widths'][$field].'%">'.$this->e($value).'</td>';}$html.='</tr>';}
        if(!$rows)$html.='<tr><td colspan="'.count($definition['columns']).'" align="center">Belum ada data pada wilayah, OPD, dan tahun aktif.</td></tr>';
        $pdf->SetFont('helvetica','',7);$pdf->writeHTML($html.'</tbody></table>',true,false,true,false,'');
        return $pdf->Output('','S');
    }

    public function performanceTreePdf(): string
    {
        [$wilayah,$opd,$year]=$this->scope();
        $ikus=$this->db->query('SELECT id,kode_iku,nama_indikator,satuan,target_akhir FROM iku_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND is_deleted=0 ORDER BY kode_iku',[$wilayah,$opd])->fetchAll();
        $nodes=$this->db->query('SELECT p.*,COALESCE(a.nama,CAST(p.penanggung_jawab_pegawai_id AS CHAR),"-") penanggung_jawab FROM pohon_kinerja_neo p LEFT JOIN db_asn_pemda_neo a ON a.id=p.penanggung_jawab_pegawai_id AND a.is_deleted=0 WHERE p.kd_wilayah=? AND p.kd_opd=? AND p.tahun=? AND p.is_deleted=0 ORDER BY p.parent_id,p.id',[$wilayah,$opd,$year])->fetchAll();
        $activities=$this->db->query('SELECT r.*,COALESCE(k.uraian,r.kd_sub_keg) nama_sub_kegiatan FROM renja_sub_kegiatan_kinerja_neo r LEFT JOIN rekening_kegiatan k ON k.kode COLLATE utf8mb4_general_ci=r.kd_sub_keg COLLATE utf8mb4_general_ci AND k.kd_wilayah COLLATE utf8mb4_general_ci=r.kd_wilayah COLLATE utf8mb4_general_ci WHERE r.kd_wilayah=? AND r.kd_opd=? AND r.tahun=? AND r.is_deleted=0 ORDER BY r.kd_sub_keg',[$wilayah,$opd,$year])->fetchAll();
        $byIku=[];$children=[];$activityByNode=[];
        foreach($nodes as $node){if(!empty($node['iku_id']))$byIku[(int)$node['iku_id']][]=$node;if(!empty($node['parent_id']))$children[(int)$node['parent_id']][]=$node;}
        foreach($activities as $activity)$activityByNode[(int)($activity['pohon_kinerja_id']??0)][]=$activity;
        $pdf=$this->newPdf('L','Pohon Kinerja');
        $first=true;
        foreach($ikus as $iku){if(!$first)$pdf->AddPage();$first=false;$this->heading($pdf,'POHON KINERJA TAHUN '.$year);$pdf->SetFont('helvetica','',8);$pdf->writeHTML($this->box('IKU',($iku['kode_iku'].' — '.$iku['nama_indikator']),'Indikator: '.$iku['nama_indikator'].' | Target: '.$this->number($iku['target_akhir']).' '.$iku['satuan'],'#17324d','#ffffff'),true,false,true,false,'');
            $roots=array_values(array_filter($byIku[(int)$iku['id']]??[],fn($n)=>empty($n['parent_id'])));
            if(!$roots)$roots=$byIku[(int)$iku['id']]??[];
            if(!$roots)$pdf->writeHTML('<p align="center"><i>Belum ada simpul kinerja yang dihubungkan ke IKU ini.</i></p>',true,false,true,false,'');
            foreach($roots as $root)$this->renderNode($pdf,$root,$children,$activityByNode,0);
        }
        if($first){$this->heading($pdf,'POHON KINERJA TAHUN '.$year);$pdf->writeHTML('<p align="center">Belum ada IKU OPD.</p>',true,false,true,false,'');}
        return $pdf->Output('','S');
    }

    private function renderNode($pdf,array $node,array $children,array $activities,int $depth):void
    {
        $colors=['#2185d0','#00a79d','#7f8c8d'];$color=$colors[min($depth,2)];
        $pdf->writeHTML('<div align="center" style="color:#777;font-size:13px">&#8595;</div>'.$this->box(strtoupper((string)$node['jenjang']),$node['kode_kinerja'].' — '.$node['uraian_kinerja'],'Indikator: '.($node['indikator']?:'-').' | Target: '.$this->number($node['target']).' '.($node['satuan']?:'').' | Penanggung jawab: '.$node['penanggung_jawab'],$color,'#ffffff'),true,false,true,false,'');
        foreach($children[(int)$node['id']]??[] as $child)$this->renderNode($pdf,$child,$children,$activities,$depth+1);
        foreach($activities[(int)$node['id']]??[] as $activity){$pdf->writeHTML('<div align="center" style="color:#777;font-size:13px">&#8595;</div>'.$this->box('SUB KEGIATAN',$activity['kd_sub_keg'].' — '.$activity['nama_sub_kegiatan'],'Keluaran: '.$activity['indikator_keluaran'].' | Target: '.$this->number($activity['target']).' '.$activity['satuan'].' | Pagu: Rp '.number_format((float)$activity['pagu_indikatif'],2,',','.'),'#f2c037','#222222'),true,false,true,false,'');}
    }

    private function box(string $level,string $title,string $detail,string $background,string $foreground):string{return '<table align="center" cellpadding="5" width="78%" style="background-color:'.$background.';color:'.$foreground.'"><tr><td><b>'.$this->e($level).'</b><br><b>'.$this->e($title).'</b><br><span style="font-size:8px">'.$this->e($detail).'</span></td></tr></table>';}
    private function newPdf(string $orientation,string $title):TCPDF{$setup=PageSetupService::current($this->user);$pdf=PageSetupService::createPdf($setup,$orientation);$pdf->SetTitle($title);PageSetupService::applyPdf($pdf,$setup,[10,12,10,12]);$pdf->AddPage($orientation);return$pdf;}
    private function heading(TCPDF $pdf,string $title):void{[$wilayah,$opd,$year]=$this->scope();$meta=$this->db->query('SELECT COALESCE(w.uraian,?) wilayah,COALESCE(o.uraian,?) opd FROM (SELECT 1) x LEFT JOIN wilayah_neo w ON w.kode=? LEFT JOIN organisasi_neo o ON o.kode=? AND o.kd_wilayah=? LIMIT 1',[$wilayah,$opd,$wilayah,$opd,$wilayah])->fetch();$pdf->SetFont('helvetica','B',12);$pdf->Cell(0,6,$title,0,1,'C');$pdf->SetFont('helvetica','',9);$pdf->Cell(0,5,($meta['opd']??$opd).' — '.($meta['wilayah']??$wilayah).' — '.$year,0,1,'C');$pdf->Ln(3);}
    private function scope():array{$w=(string)($this->user['kd_wilayah']??'');$o=(string)($this->user['kd_opd']??'');$y=(int)($this->user['tahun']??0);if($w===''||$o===''||!$y)throw new RuntimeException('Scope wilayah, OPD, atau tahun belum lengkap');return[$w,$o,$y];}
    private function scopeParams(bool $annual):array{[$w,$o,$y]=$this->scope();return $annual?[$w,$o,$y]:[$w,$o];}
    private function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
    private function number(mixed $v):string{return $v===null||$v===''?'-':number_format((float)$v,2,',','.');}

    private function definitions():array{return[
      'iku_opd'=>['title'=>'DAFTAR INDIKATOR KINERJA UTAMA (IKU)','annual'=>false,'columns'=>['kode_iku'=>'Kode IKU','sasaran'=>'Sasaran Renstra','program'=>'Program Pendukung','nama_indikator'=>'Indikator Kinerja','formula_perhitungan'=>'Formula','satuan'=>'Satuan','target_akhir'=>'Target Akhir','sumber_data'=>'Sumber Data'],'widths'=>['kode_iku'=>8,'sasaran'=>16,'program'=>18,'nama_indikator'=>20,'formula_perhitungan'=>14,'satuan'=>7,'target_akhir'=>7,'sumber_data'=>10],'sql'=>'SELECT i.kode_iku,COALESCE(s.nama_sasaran,"-") sasaran,COALESCE(p.uraian,"-") program,i.nama_indikator,i.formula_perhitungan,i.satuan,i.target_akhir,i.sumber_data FROM iku_opd_neo i LEFT JOIN sasaran_renstra_neo s ON s.id=i.sasaran_renstra_id LEFT JOIN program_renstra_neo p ON p.id=i.program_renstra_id WHERE i.kd_wilayah=? AND i.kd_opd=? AND i.is_deleted=0 ORDER BY i.kode_iku'],
      'perjanjian_kinerja_detail'=>['title'=>'PERJANJIAN KINERJA','columns'=>['nomor_dokumen'=>'Nomor Dokumen','sasaran_kinerja'=>'Sasaran Kinerja','indikator_kinerja'=>'Indikator Kinerja','target'=>'Target','satuan'=>'Satuan','program_kegiatan'=>'Program/Kegiatan','sub_kegiatan'=>'Sub Kegiatan','anggaran'=>'Anggaran'],'widths'=>['nomor_dokumen'=>11,'sasaran_kinerja'=>17,'indikator_kinerja'=>18,'target'=>7,'satuan'=>7,'program_kegiatan'=>15,'sub_kegiatan'=>15,'anggaran'=>10],'sql'=>'SELECT p.nomor_dokumen,d.sasaran_kinerja,d.indikator_kinerja,d.target,d.satuan,d.program_kegiatan,CONCAT_WS(" — ",d.kd_sub_keg,r.uraian) sub_kegiatan,d.anggaran FROM perjanjian_kinerja_detail_neo d JOIN perjanjian_kinerja_neo p ON p.id=d.perjanjian_kinerja_id LEFT JOIN rekening_kegiatan r ON r.kode COLLATE utf8mb4_general_ci=d.kd_sub_keg COLLATE utf8mb4_general_ci AND r.kd_wilayah COLLATE utf8mb4_general_ci=d.kd_wilayah COLLATE utf8mb4_general_ci WHERE d.kd_wilayah=? AND d.kd_opd=? AND d.tahun=? AND d.is_deleted=0 ORDER BY p.nomor_dokumen,d.nomor_urut'],
      'pengukuran_kinerja'=>['title'=>'PENGUKURAN KINERJA','columns'=>['indikator_kinerja'=>'Indikator Kinerja','periode_label'=>'Periode','target_periode'=>'Target','realisasi_periode'=>'Realisasi','capaian_persen'=>'Capaian (%)','analisis_capaian'=>'Analisis Capaian','kendala'=>'Kendala','tindak_lanjut'=>'Tindak Lanjut'],'widths'=>['indikator_kinerja'=>20,'periode_label'=>9,'target_periode'=>8,'realisasi_periode'=>8,'capaian_persen'=>8,'analisis_capaian'=>18,'kendala'=>14,'tindak_lanjut'=>15],'sql'=>'SELECT d.indikator_kinerja,CONCAT(u.periode," ",u.nomor_periode) periode_label,u.target_periode,u.realisasi_periode,u.capaian_persen,u.analisis_capaian,u.kendala,u.tindak_lanjut FROM pengukuran_kinerja_neo u JOIN perjanjian_kinerja_detail_neo d ON d.id=u.perjanjian_kinerja_detail_id WHERE u.kd_wilayah=? AND u.kd_opd=? AND u.tahun=? AND u.is_deleted=0 ORDER BY d.indikator_kinerja,u.periode,u.nomor_periode'],
      'evaluasi_renstra'=>['title'=>'EVALUASI KINERJA RENSTRA','columns'=>['indikator'=>'Indikator','target_kumulatif'=>'Target Kumulatif','realisasi_kumulatif'=>'Realisasi Kumulatif','capaian_persen'=>'Capaian (%)','pagu_anggaran'=>'Pagu','realisasi_anggaran'=>'Realisasi Anggaran','faktor_pendorong'=>'Pendorong','faktor_penghambat'=>'Penghambat','tindak_lanjut'=>'Tindak Lanjut'],'widths'=>['indikator'=>18,'target_kumulatif'=>8,'realisasi_kumulatif'=>8,'capaian_persen'=>8,'pagu_anggaran'=>11,'realisasi_anggaran'=>11,'faktor_pendorong'=>12,'faktor_penghambat'=>12,'tindak_lanjut'=>12],'sql'=>'SELECT indikator,target_kumulatif,realisasi_kumulatif,capaian_persen,pagu_anggaran,realisasi_anggaran,faktor_pendorong,faktor_penghambat,tindak_lanjut FROM evaluasi_renstra_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun_evaluasi=? AND is_deleted=0 ORDER BY indikator'],
      'evaluasi_renja'=>['title'=>'EVALUASI KINERJA RENJA','columns'=>['sub_kegiatan'=>'Sub Kegiatan','indikator'=>'Indikator','triwulan'=>'Triwulan','target_tahunan'=>'Target Tahunan','realisasi_kumulatif'=>'Realisasi Kumulatif','capaian_persen'=>'Capaian (%)','pagu_anggaran'=>'Pagu','realisasi_anggaran_kumulatif'=>'Realisasi Anggaran','tindak_lanjut'=>'Tindak Lanjut'],'widths'=>['sub_kegiatan'=>18,'indikator'=>17,'triwulan'=>6,'target_tahunan'=>8,'realisasi_kumulatif'=>8,'capaian_persen'=>7,'pagu_anggaran'=>10,'realisasi_anggaran_kumulatif'=>10,'tindak_lanjut'=>16],'sql'=>'SELECT CONCAT_WS(" — ",e.kd_sub_keg,r.uraian) sub_kegiatan,e.indikator,e.triwulan,e.target_tahunan,e.realisasi_kumulatif,CASE WHEN e.target_tahunan=0 THEN CASE WHEN e.realisasi_kumulatif=0 THEN 100 ELSE 0 END ELSE ROUND(e.realisasi_kumulatif/e.target_tahunan*100,2) END capaian_persen,e.pagu_anggaran,e.realisasi_anggaran_kumulatif,e.tindak_lanjut FROM evaluasi_renja_neo e LEFT JOIN rekening_kegiatan r ON r.kode COLLATE utf8mb4_general_ci=e.kd_sub_keg COLLATE utf8mb4_general_ci AND r.kd_wilayah COLLATE utf8mb4_general_ci=e.kd_wilayah COLLATE utf8mb4_general_ci WHERE e.kd_wilayah=? AND e.kd_opd=? AND e.tahun=? AND e.is_deleted=0 ORDER BY e.kd_sub_keg,e.triwulan']
    ];}
}
