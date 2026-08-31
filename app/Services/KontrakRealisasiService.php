<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Core/DB.php';
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function availableSubActivities(int $contractId=0): array
    {
        [$w,$o,$y]=$this->scope(); $params=[$w,$y]; $opd='';
        if($o&&$o!=='0'){$opd=' AND b.kd_opd=?';$params[]=$o;}
        $sql="SELECT b.kd_sub_keg,COUNT(*) jumlah_uraian,SUM(b.jumlah) pagu FROM dpa_neo b WHERE b.kd_wilayah=? AND b.tahun=?$opd AND b.setujui=1 AND b.is_deleted=0 GROUP BY b.kd_sub_keg
              UNION ALL SELECT b.kd_sub_keg,COUNT(*),SUM(b.jumlah) FROM dppa_neo b WHERE b.kd_wilayah=? AND b.tahun=?$opd AND b.setujui=1 AND b.is_deleted=0 GROUP BY b.kd_sub_keg";
        $rows=$this->db->query("SELECT kd_sub_keg,SUM(jumlah_uraian) jumlah_uraian,SUM(pagu) pagu FROM ($sql) x GROUP BY kd_sub_keg ORDER BY kd_sub_keg",array_merge($params,$params))->fetchAll();
        return $rows;
    }

    public function availableItems(string $search='', int $contractId=0, string $subActivity='', int $limit=50): array
    {
        [$w,$o,$y]=$this->scope();
        $params=[]; $scope='b.kd_wilayah=? AND b.tahun=? AND b.setujui=1 AND b.is_deleted=0';
        $params[]=$w; $params[]=$y;
        if($o&&$o!=='0'){ $scope.=' AND b.kd_opd=?'; $params[]=$o; }
        $needle='%'.trim($search).'%'; $limit=max(10,min($limit,100));
        $sql=function(string $table,string $stage) use($scope,$contractId): string {
            return "SELECT '$stage' tahap,b.id anggaran_id,b.kd_sub_keg,b.kd_akun,b.uraian,b.jumlah pagu,
                    COALESCE(ci.nilai_terpakai,0) nilai_terpakai,
                    GREATEST(b.jumlah-COALESCE(ci.nilai_terpakai,0),0) pagu_tersedia
              FROM $table b
              LEFT JOIN (SELECT tahap,anggaran_id,SUM(nilai_kontrak) nilai_terpakai
                         FROM kontrak_item_neo WHERE is_deleted=0".($contractId>0?' AND kontrak_id<>'.(int)$contractId:'')."
                         GROUP BY tahap,anggaran_id) ci ON ci.tahap='$stage' AND ci.anggaran_id=b.id
             WHERE $scope AND (?='' OR b.kd_sub_keg=?) AND (b.kd_sub_keg LIKE ? OR b.kd_akun LIKE ? OR b.uraian LIKE ? OR CAST(b.jumlah AS CHAR) LIKE ?)
               AND ci.nilai_terpakai IS NULL";
        };
        $dpaParams=array_merge($params,[$subActivity,$subActivity,$needle,$needle,$needle,$needle]);
        $dppaParams=array_merge($params,[$subActivity,$subActivity,$needle,$needle,$needle,$needle]);
        $rows=$this->db->query('('.$sql('dpa_neo','dpa').') UNION ALL ('.$sql('dppa_neo','dppa').') ORDER BY kd_sub_keg,kd_akun,uraian LIMIT '.$limit,array_merge($dpaParams,$dppaParams))->fetchAll();
        return array_map(static function(array $row): array {
            $row['pagu']=(float)$row['pagu']; $row['nilai_terpakai']=(float)$row['nilai_terpakai']; $row['pagu_tersedia']=(float)$row['pagu_tersedia']; return $row;
        },$rows);
    }

    public function delivery(int $contractId): array
    {
        $header=$this->contractHeader($contractId);
        $rab=$this->db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY nomor,id',[$contractId])->fetchAll();
        $schedule=$this->db->query('SELECT * FROM kontrak_jadwal_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY minggu_ke',[$contractId])->fetchAll();
        $documents=$this->db->query('SELECT id,jenis_dokumen,nomor_dokumen,tanggal_dokumen,judul,nama_file_asli,path_file,mime_type,ukuran,versi,keterangan,tgl_insert FROM kontrak_dokumen_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY jenis_dokumen,versi DESC',[$contractId])->fetchAll();
        return ['contract'=>$header,'rab'=>$rab,'schedule'=>$schedule,'documents'=>$documents];
    }

    public function saveRab(int $contractId,array $items): array
    {
        $header=$this->contractHeader($contractId); if(!$items)throw new InvalidArgumentException('RAB minimal memiliki satu uraian');
        $total=0.0; foreach($items as $i=>&$row){$row['uraian']=trim((string)($row['uraian']??''));$row['satuan']=trim((string)($row['satuan']??''));$row['vol_negoisasi']=(float)($row['volume']??$row['vol_negoisasi']??0);$row['harga_sat_negoisasi']=(float)($row['harga_satuan']??$row['harga_sat_negoisasi']??0);$row['jumlah_negoisasi']=$row['vol_negoisasi']*$row['harga_sat_negoisasi'];if(!$row['uraian']||!$row['satuan']||$row['jumlah_negoisasi']<=0)throw new InvalidArgumentException('Uraian RAB ke-'.($i+1).' belum lengkap');$total+=$row['jumlah_negoisasi'];}
        if($total>(float)$header['nilai_kontrak']+0.01)throw new InvalidArgumentException('Total RAB Rp '.number_format($total,0,',','.').' melebihi nilai kontrak Rp '.number_format((float)$header['nilai_kontrak'],0,',','.'));
        $this->db->begin();try{$this->db->query('DELETE FROM rab_paket_neo WHERE kontrak_id=?',[$contractId]);foreach($items as $i=>$row)$this->db->insert('rab_paket_neo',['kontrak_id'=>$contractId,'kontrak_item_id'=>!empty($row['kontrak_item_id'])?(int)$row['kontrak_item_id']:null,'tahun'=>$header['tahun'],'kd_wilayah'=>$header['kd_wilayah'],'kd_opd'=>$header['kd_opd'],'id_renja_p'=>0,'id_dpa'=>0,'id_dppa'=>0,'nomor'=>(string)($row['nomor']??($i+1)),'uraian'=>$row['uraian'],'satuan'=>$row['satuan'],'type'=>(string)($row['type']??'PEKERJAAN'),'vol_negoisasi'=>$row['vol_negoisasi'],'harga_sat_negoisasi'=>$row['harga_sat_negoisasi'],'jumlah_negoisasi'=>$row['jumlah_negoisasi'],'bobot'=>$total>0?($row['jumlah_negoisasi']/$total*100):0,'keterangan'=>(string)($row['keterangan']??''),'username_insert'=>$this->user['username']??'system','tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);$this->db->commit();}catch(Throwable $e){$this->db->rollback();throw $e;}
        return ['total'=>$total,'items'=>$this->delivery($contractId)['rab']];
    }

    public function saveSchedule(int $contractId,array $weeks): array
    {
        $header=$this->contractHeader($contractId); if(!$weeks)throw new InvalidArgumentException('Time schedule minimal satu minggu');
        $planned=0.0;$actual=0.0;$this->db->begin();try{$this->db->query('DELETE FROM kontrak_jadwal_neo WHERE kontrak_id=?',[$contractId]);foreach($weeks as $i=>$week){$p=(float)($week['bobot_rencana']??0);$a=(float)($week['bobot_realisasi']??0);$planned+=$p;$actual+=$a;if($planned>100.01||$actual>100.01)throw new InvalidArgumentException('Bobot kumulatif Kurva S tidak boleh melebihi 100%');$this->db->insert('kontrak_jadwal_neo',['kontrak_id'=>$contractId,'minggu_ke'=>(int)($week['minggu_ke']??($i+1)),'tanggal_mulai'=>$week['tanggal_mulai'],'tanggal_selesai'=>$week['tanggal_selesai'],'bobot_rencana'=>$p,'bobot_realisasi'=>$a,'rencana_kumulatif'=>$planned,'realisasi_kumulatif'=>$actual,'keterangan'=>(string)($week['keterangan']??''),'kd_wilayah'=>$header['kd_wilayah'],'kd_opd'=>$header['kd_opd'],'tahun'=>$header['tahun'],'username_insert'=>$this->user['username']??'system']);}$this->db->commit();}catch(Throwable $e){$this->db->rollback();throw $e;}return $this->delivery($contractId)['schedule'];
    }

    public function rabExcel(int $contractId): string
    {
        $data=$this->delivery($contractId);$book=new Spreadsheet();$s=$book->getActiveSheet();$s->setTitle('RAB dan Kurva S');$s->fromArray(['NO','URAIAN','SATUAN','VOLUME','HARGA SATUAN','JUMLAH','BOBOT %'],null,'A1');$r=2;foreach($data['rab'] as $i=>$x)$s->fromArray([$x['nomor']?:$i+1,$x['uraian'],$x['satuan'],(float)$x['vol_negoisasi'],(float)$x['harga_sat_negoisasi'],(float)$x['jumlah_negoisasi'],(float)$x['bobot']],null,'A'.$r++);$r+=2;$s->fromArray(['MINGGU','MULAI','SELESAI','RENCANA %','REALISASI %','RENCANA KUMULATIF','REALISASI KUMULATIF'],null,'A'.$r++);foreach($data['schedule'] as $x)$s->fromArray([$x['minggu_ke'],$x['tanggal_mulai'],$x['tanggal_selesai'],(float)$x['bobot_rencana'],(float)$x['bobot_realisasi'],(float)$x['rencana_kumulatif'],(float)$x['realisasi_kumulatif']],null,'A'.$r++);foreach(range('A','G') as $c)$s->getColumnDimension($c)->setAutoSize(true);$s->getStyle('A1:G1')->getFont()->setBold(true);$tmp=tempnam(sys_get_temp_dir(),'rab_').'.xlsx';(new Xlsx($book))->save($tmp);return $tmp;
    }

    public function importRab(int $contractId,string $filePath): array
    {
        if(!is_file($filePath))throw new InvalidArgumentException('File RAB tidak ditemukan');
        $sheet=IOFactory::load($filePath)->getActiveSheet();$rows=$sheet->toArray(null,true,true,true);$items=[];
        foreach($rows as $index=>$row){if($index===1)continue;if(strtoupper(trim((string)($row['A']??'')))==='MINGGU')break;$description=trim((string)($row['B']??''));$unit=trim((string)($row['C']??''));$volume=$row['D']??null;$price=$row['E']??null;if($description===''||preg_match('/^\d{4}-\d{2}-\d{2}$/',$description)||$unit===''||!is_numeric($volume)||!is_numeric($price)||(float)$volume<=0||(float)$price<=0)continue;$items[]=['nomor'=>$row['A']??count($items)+1,'uraian'=>$description,'satuan'=>$unit,'volume'=>(float)$volume,'harga_satuan'=>(float)$price,'keterangan'=>trim((string)($row['H']??''))];}
        if(!$items)throw new InvalidArgumentException('Tidak ada baris RAB yang dapat diimpor. Gunakan template resmi.');
        $result=$this->saveRab($contractId,$items);$result['imported']=count($items);return $result;
    }

    public function rabPdf(int $contractId): string
    {
        $data=$this->delivery($contractId);$d=$data['contract'];$pdf=new TCPDF('L','mm','A4',true,'UTF-8');$pdf->SetMargins(10,10,10);$pdf->AddPage();$pdf->SetFont('helvetica','B',13);$pdf->Cell(0,7,'RENCANA ANGGARAN BIAYA (RAB)',0,1,'C');$pdf->SetFont('helvetica','',9);$pdf->Cell(0,6,'Kontrak: '.($d['nomor_kontrak']??'-').' — '.($d['uraian_kontrak']??'-'),0,1,'C');
        $html='<br><table border="1" cellpadding="4"><tr style="font-weight:bold;background-color:#dcecff"><th width="6%">No</th><th width="38%">Uraian</th><th width="10%">Satuan</th><th width="10%">Volume</th><th width="14%">Harga Satuan</th><th width="14%">Jumlah</th><th width="8%">Bobot</th></tr>';$total=0;
        foreach($data['rab'] as $x){$total+=(float)$x['jumlah_negoisasi'];$html.='<tr><td>'.htmlspecialchars((string)$x['nomor']).'</td><td>'.htmlspecialchars((string)$x['uraian']).'</td><td>'.htmlspecialchars((string)$x['satuan']).'</td><td align="right">'.number_format((float)$x['vol_negoisasi'],2,',','.').'</td><td align="right">'.number_format((float)$x['harga_sat_negoisasi'],0,',','.').'</td><td align="right">'.number_format((float)$x['jumlah_negoisasi'],0,',','.').'</td><td align="right">'.number_format((float)$x['bobot'],2,',','.').'%</td></tr>';}
        $html.='<tr style="font-weight:bold;background-color:#eef6ff"><td colspan="5" align="right">TOTAL</td><td align="right">'.number_format($total,0,',','.').'</td><td align="right">100,00%</td></tr></table>';$pdf->writeHTML($html,true,false,true,false,'');
        if($data['schedule']){$pdf->Ln(4);$pdf->SetFont('helvetica','B',10);$pdf->Cell(0,6,'TIME SCHEDULE / KURVA S',0,1);$pdf->SetFont('helvetica','',7);$chart='<table border="1" cellpadding="3"><tr style="font-weight:bold;background-color:#dff5ec"><th>Minggu</th><th>Mulai</th><th>Selesai</th><th>Rencana</th><th>Realisasi</th><th>Rencana Kumulatif</th><th>Realisasi Kumulatif</th></tr>';foreach($data['schedule'] as $x)$chart.='<tr><td>'.$x['minggu_ke'].'</td><td>'.$x['tanggal_mulai'].'</td><td>'.$x['tanggal_selesai'].'</td><td>'.$x['bobot_rencana'].'%</td><td>'.$x['bobot_realisasi'].'%</td><td>'.$x['rencana_kumulatif'].'%</td><td>'.$x['realisasi_kumulatif'].'%</td></tr>';$pdf->writeHTML($chart.'</table>',true,false,true,false,'');}
        return $pdf->Output('','S');
    }

    public function termsPdf(int $contractId,string $type): string
    {
        $type=strtoupper($type);if(!in_array($type,['SSKK','SSUK'],true))throw new InvalidArgumentException('Jenis syarat kontrak tidak valid');$data=$this->delivery($contractId);$d=$data['contract'];$pdf=new TCPDF('P','mm','A4',true,'UTF-8');$pdf->SetMargins(18,15,18);$pdf->SetAutoPageBreak(true,18);$pdf->AddPage();$pdf->SetFont('helvetica','B',13);$title=$type==='SSKK'?'SYARAT-SYARAT KHUSUS KONTRAK (SSKK)':'SYARAT-SYARAT UMUM KONTRAK (SSUK)';$pdf->MultiCell(0,7,$title,0,'C');$pdf->SetFont('helvetica','',9);$pdf->MultiCell(0,6,'Nomor Kontrak: '.($d['nomor_kontrak']??'-').' | Pekerjaan: '.($d['uraian_kontrak']??'-'),0,'C');$pdf->Ln(4);
        $special=[['Para Pihak','PPK '.($d['nama_ppk']??'-').' dan Penyedia '.($d['nama_penyedia']??'-').'.'],['Ruang Lingkup','Seluruh uraian pekerjaan, spesifikasi, volume, RAB, gambar, dan keluaran yang menjadi bagian kontrak.'],['Nilai Kontrak','Rp '.number_format((float)($d['nilai_kontrak']??0),0,',','.').' termasuk pajak dan biaya yang sah.'],['Jangka Waktu',($d['tanggal_mulai']??'-').' sampai '.($d['tanggal_selesai']??'-').' ('.($d['waktu_pelaksanaan']??'-').' hari kalender).'],['Pembayaran','Pembayaran dilakukan berdasarkan prestasi pekerjaan yang telah diverifikasi, dokumen bukti lengkap, dan tidak melampaui nilai kontrak.'],['Pelaporan','Penyedia menyerahkan laporan kemajuan, realisasi fisik/keuangan, foto, Kurva S, dan dokumen mutu sesuai jadwal.'],['Serah Terima','PHO/BAST dilakukan setelah pekerjaan dinyatakan selesai; FHO dilakukan setelah masa pemeliharaan berakhir.'],['Jaminan dan Denda','Jenis, nilai, masa berlaku jaminan, denda keterlambatan, serta retensi mengikuti data kontrak dan peraturan pengadaan yang berlaku.'],['Perubahan Kontrak','Adendum hanya dapat dilakukan secara tertulis oleh para pihak dengan alasan, nilai, waktu, dan ruang lingkup yang dapat dipertanggungjawabkan.']];
        $general=[['Definisi dan Penafsiran','Istilah dalam kontrak dibaca bersama surat perjanjian, spesifikasi, gambar, daftar kuantitas/RAB, jadwal, dan dokumen pemilihan.'],['Urutan Dokumen','Apabila terdapat pertentangan, dokumen berlaku menurut urutan yang ditetapkan dalam kontrak dan adendum terakhir.'],['Hak dan Kewajiban','Para pihak wajib bertindak profesional, beritikad baik, menjaga integritas, dan memenuhi seluruh kewajiban hukum.'],['Pelaksanaan dan Pengendalian','Penyedia melaksanakan pekerjaan sesuai mutu, volume, keselamatan, jadwal, serta instruksi tertulis PPK.'],['Personel, Peralatan, dan Subkontrak','Personel/peralatan utama harus tersedia; penggantian dan subkontrak memerlukan persetujuan sesuai ketentuan.'],['Pemeriksaan dan Pengujian','PPK/pengawas dapat memeriksa bahan, hasil pekerjaan, dokumen mutu, dan meminta perbaikan atas ketidaksesuaian.'],['Pembayaran dan Pajak','Pembayaran dilakukan atas prestasi terverifikasi setelah pajak, denda, pengembalian, dan kewajiban lainnya diperhitungkan.'],['Keadaan Kahar','Pihak terdampak wajib memberi pemberitahuan dan bukti; akibat terhadap waktu/biaya ditetapkan melalui evaluasi dan adendum.'],['Penghentian dan Pemutusan','Kontrak dapat dihentikan atau diputus berdasarkan alasan dan prosedur dalam peraturan pengadaan serta kontrak.'],['Penyelesaian Perselisihan','Perselisihan diselesaikan bertahap melalui musyawarah, mediasi/konsiliasi, arbitrase, atau pengadilan sesuai pilihan dalam SSKK.'],['Larangan Korupsi dan Benturan Kepentingan','Para pihak dilarang melakukan korupsi, kolusi, nepotisme, gratifikasi, pemalsuan, dan benturan kepentingan.'],['Audit dan Penyimpanan Dokumen','Dokumen kontrak dan transaksi wajib tersedia untuk pemeriksaan APIP/BPK serta disimpan sesuai retensi arsip.']];
        $clauses=$type==='SSKK'?$special:$general;$html='<table border="1" cellpadding="5"><tr style="font-weight:bold;background-color:#dcecff"><th width="8%">No</th><th width="27%">Ketentuan</th><th width="65%">Isi</th></tr>';foreach($clauses as $i=>[$name,$body])$html.='<tr><td>'.($i+1).'</td><td><b>'.htmlspecialchars($name).'</b></td><td>'.htmlspecialchars($body).'</td></tr>';$pdf->writeHTML($html.'</table>',true,false,true,false,'');$pdf->Ln(8);$pdf->writeHTML('<table><tr><td width="50%" align="center">PPK<br><br><br><b>'.htmlspecialchars((string)($d['nama_ppk']??'-')).'</b></td><td width="50%" align="center">Penyedia<br><br><br><b>'.htmlspecialchars((string)($d['nama_penyedia']??'-')).'</b></td></tr></table>',true,false,true,false,'');return $pdf->Output('','S');
    }

    public function uploadDocument(int $contractId,array $meta,array $file): array
    {
        $header=$this->contractHeader($contractId);$types=['KONTRAK','SPK','SPMK','SSKK','SSUK','RAB','JADWAL','KURVA_S','GAMBAR','BAST','PHO','FHO','ADENDUM','JAMINAN','LAPORAN','LAINNYA'];$type=strtoupper((string)($meta['jenis_dokumen']??''));
        if(!in_array($type,$types,true))throw new InvalidArgumentException('Jenis dokumen kontrak tidak valid');if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new InvalidArgumentException('File dokumen belum dipilih atau gagal diunggah');if(($file['size']??0)>25*1024*1024)throw new InvalidArgumentException('Ukuran file maksimal 25 MB');
        $allowed=['application/pdf','image/jpeg','image/png','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);if(!in_array($mime,$allowed,true))throw new InvalidArgumentException('Format file harus PDF, JPG, PNG, XLSX, DOCX, atau ZIP');
        $safeScope=preg_replace('/[^A-Za-z0-9._-]/','_',($header['kd_wilayah']??'wilayah').'-'.($header['kd_opd']??'opd'));$relative='storage/uploads/'.$safeScope.'/'.$header['tahun'].'/kontrak/'.$contractId.'/'.strtolower($type);$directory=dirname(__DIR__,2).'/'.$relative;if(!is_dir($directory)&&!mkdir($directory,0775,true))throw new RuntimeException('Folder dokumen kontrak tidak dapat dibuat');
        $extension=strtolower(pathinfo((string)$file['name'],PATHINFO_EXTENSION));$stored=date('YmdHis').'-'.bin2hex(random_bytes(5)).($extension?'.'.$extension:'');if(!move_uploaded_file($file['tmp_name'],$directory.'/'.$stored))throw new RuntimeException('File gagal dipindahkan ke penyimpanan kontrak');
        $version=(int)($this->db->query('SELECT COALESCE(MAX(versi),0)+1 versi FROM kontrak_dokumen_neo WHERE kontrak_id=? AND jenis_dokumen=?',[$contractId,$type])->fetch()['versi']??1);$id=$this->db->insert('kontrak_dokumen_neo',['kontrak_id'=>$contractId,'jenis_dokumen'=>$type,'nomor_dokumen'=>$meta['nomor_dokumen']??null,'tanggal_dokumen'=>$meta['tanggal_dokumen']??null,'judul'=>trim((string)($meta['judul']??$type)),'nama_file_asli'=>basename((string)$file['name']),'path_file'=>$relative.'/'.$stored,'mime_type'=>$mime,'ukuran'=>(int)$file['size'],'versi'=>$version,'keterangan'=>$meta['keterangan']??null,'kd_wilayah'=>$header['kd_wilayah'],'kd_opd'=>$header['kd_opd'],'tahun'=>$header['tahun'],'username_insert'=>$this->user['username']??'system']);return ['id'=>$id,'versi'=>$version,'path'=>$relative.'/'.$stored];
    }

    public function document(int $id): array
    {
        [$w,$o,$y]=$this->scope();$sql='SELECT d.* FROM kontrak_dokumen_neo d JOIN kontrak_neo k ON k.id=d.kontrak_id WHERE d.id=? AND d.is_deleted=0 AND k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0';$p=[$id,$w,$y];if($o&&$o!=='0'){$sql.=' AND k.kd_opd=?';$p[]=$o;}$row=$this->db->query($sql.' LIMIT 1',$p)->fetch();if(!$row)throw new RuntimeException('Dokumen tidak ditemukan');return $row;
    }

    public function contractItems(int $contractId): array
    {
        $this->contractHeader($contractId);
        return $this->db->query('SELECT id,kontrak_id,tahap,anggaran_id,kd_sub_keg,kd_akun,uraian,pagu,nilai_kontrak FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY kd_sub_keg,kd_akun,uraian',[$contractId])->fetchAll();
    }

    public function saveContractItems(int $contractId,array $items): array
    {
        $header=$this->contractHeader($contractId);
        if(!$items) throw new InvalidArgumentException('Pilih minimal satu uraian DPA/DPPA untuk kontrak');
        $seen=[]; $validated=[]; $totalPagu=0.0; $totalContract=0.0;
        foreach($items as $index=>$item){
            $stage=($item['tahap']??'')==='dppa'?'dppa':'dpa'; $budgetId=(int)($item['anggaran_id']??0); $value=(float)($item['nilai_kontrak']??0);
            $key=$stage.':'.$budgetId;
            if(!$budgetId||isset($seen[$key])) throw new InvalidArgumentException('Uraian kontrak ke-'.($index+1).' tidak valid atau dipilih dua kali');
            if($value<=0) throw new InvalidArgumentException('Nilai kontrak uraian ke-'.($index+1).' harus lebih besar dari nol');
            $seen[$key]=true; $table=$stage==='dppa'?'dppa_neo':'dpa_neo';
            $params=[$budgetId,$header['kd_wilayah'],$header['tahun']]; $opdSql='';
            if(($header['kd_opd']??'')&&$header['kd_opd']!=='0'){ $opdSql=' AND kd_opd=?'; $params[]=$header['kd_opd']; }
            $budget=$this->db->query("SELECT id,kd_sub_keg,kd_akun,uraian,jumlah FROM $table WHERE id=? AND kd_wilayah=? AND tahun=?$opdSql AND setujui=1 AND is_deleted=0 LIMIT 1",$params)->fetch();
            if(!$budget) throw new InvalidArgumentException('Uraian kontrak ke-'.($index+1).' tidak ditemukan atau DPA/DPPA belum disetujui');
            $used=(float)($this->db->query('SELECT COALESCE(SUM(nilai_kontrak),0) total FROM kontrak_item_neo WHERE tahap=? AND anggaran_id=? AND kontrak_id<>? AND is_deleted=0',[$stage,$budgetId,$contractId])->fetch()['total']??0);
            $available=(float)$budget['jumlah']-$used;
            if($value>$available) throw new InvalidArgumentException('Nilai kontrak untuk '.$budget['kd_sub_keg'].' / '.$budget['uraian'].' melebihi pagu tersedia Rp '.number_format($available,0,',','.'));
            $validated[]=['tahap'=>$stage,'anggaran_id'=>$budgetId,'kd_sub_keg'=>$budget['kd_sub_keg'],'kd_akun'=>$budget['kd_akun'],'uraian'=>$budget['uraian'],'pagu'=>(float)$budget['jumlah'],'nilai_kontrak'=>$value];
            $totalPagu+=(float)$budget['jumlah']; $totalContract+=$value;
        }
        $this->db->begin();
        try{
            $this->db->query('DELETE FROM kontrak_item_neo WHERE kontrak_id=?',[$contractId]);
            foreach($validated as $row){$this->db->insert('kontrak_item_neo',array_merge($row,['kontrak_id'=>$contractId,'kd_wilayah'=>$header['kd_wilayah'],'kd_opd'=>$header['kd_opd'],'tahun'=>$header['tahun'],'username_insert'=>$this->user['username']??'system','is_deleted'=>0]));}
            $first=$validated[0];
            $this->db->update('kontrak_neo',['tahap'=>$first['tahap'],'anggaran_id'=>$first['anggaran_id'],'kd_sub_keg'=>$first['kd_sub_keg'],'total_anggaran'=>$totalPagu,'nilai_kontrak'=>$totalContract,'tgl_update'=>date('Y-m-d H:i:s'),'username_update'=>$this->user['username']??'system'],'WHERE id=?',[$contractId]);
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
        return ['items'=>$validated,'total_anggaran'=>$totalPagu,'nilai_kontrak'=>$totalContract];
    }

    private function contractHeader(int $id): array
    {
        [$w,$o,$y]=$this->scope(); $sql='SELECT * FROM kontrak_neo WHERE id=? AND kd_wilayah=? AND tahun=? AND is_deleted=0'; $params=[$id,$w,$y];
        if($o&&$o!=='0'){ $sql.=' AND kd_opd=?'; $params[]=$o; }
        $row=$this->db->query($sql.' LIMIT 1',$params)->fetch(); if(!$row)throw new RuntimeException('Kontrak tidak ditemukan atau bukan dalam lingkup pengguna'); return $row;
    }

    public function contractPdf(int $id): string
    {
        [$w,$o,$y]=$this->scope(); $sql="SELECT k.*,r.nama_perusahaan,r.alamat,r.npwp,r.direktur FROM kontrak_neo k LEFT JOIN rekanan_neo r ON r.id=k.rekanan_id WHERE k.id=? AND k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0"; $params=[$id,$w,$y];
        if($o&&$o!=='0'){ $sql.=' AND k.kd_opd=?'; $params[]=$o; }
        $d=$this->db->query($sql,$params)->fetch(); if(!$d)throw new RuntimeException('Kontrak tidak ditemukan');
        $pdf=new TCPDF('P','mm','A4',true,'UTF-8'); $pdf->SetMargins(18,15,18); $pdf->AddPage(); $pdf->SetFont('helvetica','B',14); $pdf->Cell(0,8,'SURAT PERJANJIAN / KONTRAK',0,1,'C'); $pdf->SetFont('helvetica','',10); $pdf->Cell(0,6,'Nomor: '.($d['nomor_kontrak']??'-'),0,1,'C'); $pdf->Ln(5);
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        $items=$this->contractItems($id); $itemHtml='<table border="1" cellpadding="4"><tr style="font-weight:bold;background-color:#e8f1fb"><th width="17%">Sub Kegiatan</th><th width="16%">Kode Belanja</th><th width="37%">Uraian</th><th width="15%">Pagu</th><th width="15%">Nilai Kontrak</th></tr>';
        foreach($items as $item)$itemHtml.='<tr><td>'.$e($item['kd_sub_keg']).'</td><td>'.$e($item['kd_akun']).'</td><td>'.$e($item['uraian']).'</td><td align="right">'.number_format((float)$item['pagu'],0,',','.').'</td><td align="right">'.number_format((float)$item['nilai_kontrak'],0,',','.').'</td></tr>';
        $itemHtml.='</table>';
        $html='<table cellpadding="5"><tr><td width="32%"><b>Pekerjaan</b></td><td width="68%">'.$e($d['uraian_kontrak']).'</td></tr><tr><td><b>Nilai Kontrak</b></td><td>Rp '.number_format((float)$d['nilai_kontrak'],2,',','.').'</td></tr><tr><td><b>SPK</b></td><td>'.$e($d['nomor_spk']).' tanggal '.$e($d['tanggal_spk']).'</td></tr><tr><td><b>SPMK</b></td><td>'.$e($d['nomor_spmk']).' tanggal '.$e($d['tanggal_spmk']).'</td></tr><tr><td><b>Pelaksanaan</b></td><td>'.$e($d['tanggal_mulai']).' s.d. '.$e($d['tanggal_selesai']).' ('.$e($d['waktu_pelaksanaan']).' hari)</td></tr><tr><td><b>PPK</b></td><td>'.$e($d['nama_ppk']).'</td></tr><tr><td><b>Penyedia</b></td><td>'.$e($d['nama_perusahaan']?:$d['nama_penyedia']).'<br>'.$e($d['alamat']).'<br>NPWP '.$e($d['npwp']).'<br>Direktur '.$e($d['direktur']).'</td></tr></table><h4>Rincian Uraian Kontrak</h4>'.$itemHtml.'<br><p>Para pihak sepakat melaksanakan pekerjaan tersebut sesuai ruang lingkup, nilai, jadwal, spesifikasi, serta ketentuan peraturan perundang-undangan yang berlaku.</p>';
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

    public function financialRows(): array
    {
        [$w,$o,$y]=$this->scope();$sql="SELECT dr.tanggal,dr.periode,dr.nomor_bukti,k.nomor_kontrak,k.kd_sub_keg,dr.kd_akun,COALESCE(dr.uraian_progress,dr.ket_uraian_paket) uraian,dr.vol,dr.jumlah,dr.progress_fisik,dr.progress_keuangan,COALESCE(r.nama_perusahaan,k.nama_penyedia) penyedia FROM daftar_realisasi_neo dr JOIN kontrak_neo k ON k.id=dr.kontrak_id LEFT JOIN rekanan_neo r ON r.id=k.rekanan_id WHERE dr.is_deleted=0 AND k.is_deleted=0 AND k.kd_wilayah=? AND k.tahun=?";$p=[$w,$y];if($o&&$o!=='0'){$sql.=' AND k.kd_opd=?';$p[]=$o;}return $this->db->query($sql.' ORDER BY dr.tanggal,dr.id',$p)->fetchAll();
    }

    public function financialExcel(string $format): string
    {
        $format=in_array($format,['spj','lra','bulanan_fisik_keuangan'],true)?$format:'lra';$rows=$this->financialRows();$book=new Spreadsheet();$s=$book->getActiveSheet();$titles=['spj'=>'SPJ Bendahara','lra'=>'Laporan Realisasi Anggaran','bulanan_fisik_keuangan'=>'Laporan Bulanan Fisik Keuangan'];$s->setTitle(substr($titles[$format],0,31));$headers=$format==='spj'?['NO','TANGGAL','NOMOR BUKTI','KONTRAK','PENYEDIA','URAIAN','KODE AKUN','JUMLAH']:['NO','BULAN','SUB KEGIATAN','KODE AKUN','KONTRAK','URAIAN','ANGGARAN/REALISASI','FISIK %','KEUANGAN %'];$s->fromArray($headers,null,'A1');$r=2;foreach($rows as $i=>$x){$data=$format==='spj'?[$i+1,$x['tanggal'],$x['nomor_bukti'],$x['nomor_kontrak'],$x['penyedia'],$x['uraian'],$x['kd_akun'],(float)$x['jumlah']]:[$i+1,$x['periode']?:date('n',strtotime($x['tanggal'])),$x['kd_sub_keg'],$x['kd_akun'],$x['nomor_kontrak'],$x['uraian'],(float)$x['jumlah'],(float)$x['progress_fisik'],(float)$x['progress_keuangan']];$s->fromArray($data,null,'A'.$r++);}$last=$format==='spj'?'H':'I';$s->getStyle("A1:{$last}1")->getFont()->setBold(true);foreach(range('A',$last) as $c)$s->getColumnDimension($c)->setAutoSize(true);$tmp=tempnam(sys_get_temp_dir(),'finance_').'.xlsx';(new Xlsx($book))->save($tmp);return $tmp;
    }

    public function financialPdf(string $format): string
    {
        $format=in_array($format,['spj','lra','bulanan_fisik_keuangan'],true)?$format:'lra';$rows=$this->financialRows();$title=['spj'=>'FORMAT SPJ BENDAHARA','lra'=>'LAPORAN REALISASI ANGGARAN','bulanan_fisik_keuangan'=>'LAPORAN BULANAN FISIK DAN KEUANGAN'][$format];$pdf=new TCPDF('L','mm','A4',true,'UTF-8');$pdf->SetMargins(8,10,8);$pdf->AddPage();$pdf->SetFont('helvetica','B',13);$pdf->Cell(0,8,$title,0,1,'C');$pdf->SetFont('helvetica','',7);$html='<table border="1" cellpadding="3"><tr style="font-weight:bold;background-color:#e8f1fb"><th>No</th><th>Tanggal</th><th>Bukti</th><th>Kontrak</th><th>Sub Kegiatan</th><th>Akun</th><th>Uraian</th><th>Penyedia</th><th>Jumlah</th><th>Fisik</th><th>Keuangan</th></tr>';foreach($rows as $i=>$x)$html.='<tr><td>'.($i+1).'</td><td>'.htmlspecialchars($x['tanggal']).'</td><td>'.htmlspecialchars((string)$x['nomor_bukti']).'</td><td>'.htmlspecialchars((string)$x['nomor_kontrak']).'</td><td>'.htmlspecialchars((string)$x['kd_sub_keg']).'</td><td>'.htmlspecialchars((string)$x['kd_akun']).'</td><td>'.htmlspecialchars((string)$x['uraian']).'</td><td>'.htmlspecialchars((string)$x['penyedia']).'</td><td align="right">'.number_format((float)$x['jumlah'],0,',','.').'</td><td>'.number_format((float)$x['progress_fisik'],2).'%</td><td>'.number_format((float)$x['progress_keuangan'],2).'%</td></tr>';$pdf->writeHTML($html.'</table>',true,false,true,false,'');return $pdf->Output('','S');
    }
}
