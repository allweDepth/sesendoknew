<?php
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/../Core/Auth.php';
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/OfficialLetterheadPdfService.php';

/** Master + snapshot dokumen pengadaan yang dapat diedit tanpa mengubah master. */
final class ProcurementDocumentService
{
    private DB $db;
    private array $user;
    private const EDIT_ROLES=['super_admin','admin_wilayah','admin_opd','kepala_opd','pa_kpa','ppk','pejabat_pengadaan','pokja_ulp'];

    public function __construct(array $user=[]){$this->db=DB::getInstance();$this->user=Auth::scopedUser()?:$user;}

    private function scope():array
    {
        $w=(string)($this->user['kd_wilayah']??'');$o=(string)($this->user['kd_opd']??'');$y=(int)($this->user['tahun']??0);
        if($w===''||$y<2000)throw new RuntimeException('Scope pengguna tidak lengkap');
        return[$w,$o,$y];
    }
    private function canEdit():void
    {
        if(!in_array($this->user['type_user']??'viewer',self::EDIT_ROLES,true))throw new RuntimeException('Dokumen pengadaan hanya dapat dibuat atau disunting PA/KPA, PPK, Pejabat Pengadaan/ULP, atau administrator OPD');
    }
    private function contract(int $id):array
    {
        [$w,$o,$y]=$this->scope();$sql="SELECT k.*,COALESCE(r.nama_perusahaan,k.nama_penyedia) nama_penyedia_resmi,r.alamat alamat_penyedia,r.npwp,r.direktur,COALESCE(ppk.nama_pegawai,k.nama_ppk) nama_ppk_resmi,ppk.nip nip_ppk_resmi FROM kontrak_neo k LEFT JOIN rekanan_neo r ON r.id=k.rekanan_id LEFT JOIN pejabat_tahunan_neo ppk ON ppk.id=k.ppk_id AND ppk.is_deleted=0 WHERE k.id=? AND k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0";$p=[$id,$w,$y];
        if($o!==''&&$o!=='0'){$sql.=' AND k.kd_opd=?';$p[]=$o;}$row=$this->db->query($sql.' LIMIT 1',$p)->fetch();if(!$row)throw new RuntimeException('Kontrak tidak ditemukan dalam lingkup pengguna');return$row;
    }

    public static function recommendForm(string $way,string $kind,string $method,float $value,?string $swakelolaType=null):string
    {
        $way=strtoupper($way);$kind=strtoupper($kind);$method=strtolower(trim($method));
        if($way==='SWAKELOLA')return'PERJANJIAN_SWAKELOLA';
        if(str_contains($method,'e-purchasing')||str_contains($method,'e purchasing')||str_contains($method,'katalog'))return'SURAT_PESANAN';
        if(in_array($kind,['KONSULTANSI_KONSTRUKSI','KONSULTANSI_NON_KONSTRUKSI'],true))return$value<=100000000?'SPK':'SURAT_PERJANJIAN';
        if($kind==='PEKERJAAN_KONSTRUKSI')return$value<=400000000?'SPK':'SURAT_PERJANJIAN';
        if($value<=10000000)return'BUKTI_PEMBELIAN';
        if($value<=50000000)return'KUITANSI';
        if($value<=200000000)return'SPK';
        return'SURAT_PERJANJIAN';
    }

    public function templates(int $contractId):array
    {
        $c=$this->contract($contractId);$way=strtoupper((string)($c['cara_pengadaan']??'PENYEDIA'));$kind=strtoupper((string)($c['jenis_pengadaan']??'BARANG'));$method=(string)($c['metode_pemilihan']??'');$sw=$c['tipe_swakelola']??null;$value=(float)($c['nilai_kontrak']??0);
        $recommended=self::recommendForm($way,$kind,$method,$value,$sw);
        $rows=$this->db->query("SELECT id,kode,nama,kelompok,cara_pengadaan,jenis_pengadaan,metode_pemilihan,tipe_swakelola,bentuk_kontrak,nilai_min,nilai_max,dasar_hukum,sumber_format,versi,orientasi FROM master_dokumen_pengadaan_neo WHERE aktif=1 AND is_deleted=0 AND berlaku_mulai<=CURDATE() AND (berlaku_sampai IS NULL OR berlaku_sampai>=CURDATE()) AND (cara_pengadaan='SEMUA' OR cara_pengadaan=?) AND (jenis_pengadaan='SEMUA' OR jenis_pengadaan=?) AND (tipe_swakelola IS NULL OR tipe_swakelola=?) ORDER BY FIELD(kelompok,'PERSIAPAN','PEMILIHAN','KONTRAK','PELAKSANAAN','SWAKELOLA'),nama",[$way,$kind,$sw])->fetchAll();
        foreach($rows as &$r){$r['direkomendasikan']=($r['bentuk_kontrak']===$recommended)||($way==='SWAKELOLA'&&$r['kode']==='SWAKELOLA_'.$sw);$r['sesuai_nilai']=($value>=(float)$r['nilai_min']&&($r['nilai_max']===null||$value<=(float)$r['nilai_max']));}unset($r);
        return['contract'=>$c,'recommended_form'=>$recommended,'templates'=>$rows];
    }

    public function list(int $contractId):array
    {
        $this->contract($contractId);return$this->db->query('SELECT id,master_id,kode_dokumen,nomor_dokumen,tanggal_dokumen,judul,status,versi_master,pembuat_role,tgl_update,tgl_insert FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY tanggal_dokumen DESC,id DESC',[$contractId])->fetchAll();
    }

    public function detail(int $id):array
    {
        [$w,$o,$y]=$this->scope();$sql='SELECT d.*,m.nama nama_master,m.sumber_format,m.orientasi FROM dokumen_pengadaan_neo d JOIN master_dokumen_pengadaan_neo m ON m.id=d.master_id JOIN kontrak_neo k ON k.id=d.kontrak_id WHERE d.id=? AND d.kd_wilayah=? AND d.tahun=? AND d.is_deleted=0 AND k.is_deleted=0';$p=[$id,$w,$y];if($o!==''&&$o!=='0'){$sql.=' AND d.kd_opd=?';$p[]=$o;}$doc=$this->db->query($sql.' LIMIT 1',$p)->fetch();if(!$doc)throw new RuntimeException('Dokumen pengadaan tidak ditemukan');$doc['sections']=$this->db->query('SELECT id,kode_bagian,judul,urutan,isi FROM dokumen_pengadaan_bagian_neo WHERE dokumen_id=? AND is_deleted=0 ORDER BY urutan,id',[$id])->fetchAll();$doc['attachments']=$this->db->query('SELECT id,jenis_lampiran,judul,urutan,isi,nama_file,mime_type,ukuran FROM dokumen_pengadaan_lampiran_neo WHERE dokumen_id=? AND is_deleted=0 ORDER BY urutan,id',[$id])->fetchAll();return$doc;
    }

    public function save(array $payload):array
    {
        $this->canEdit();$id=(int)($payload['id']??0);$contractId=(int)($payload['contract_id']??0);$masterId=(int)($payload['master_id']??0);$date=trim((string)($payload['tanggal_dokumen']??''));$title=trim((string)($payload['judul']??''));
        if(!$contractId||!$masterId||$date===''||$title==='')throw new InvalidArgumentException('Kontrak, master, tanggal, dan judul dokumen wajib diisi');$contract=$this->contract($contractId);
        $master=$this->db->query('SELECT * FROM master_dokumen_pengadaan_neo WHERE id=? AND aktif=1 AND is_deleted=0 LIMIT 1',[$masterId])->fetch();if(!$master)throw new RuntimeException('Master dokumen tidak aktif');
        $sections=$payload['sections']??[];if(is_string($sections))$sections=json_decode($sections,true)?:[];$attachments=$payload['attachments']??[];if(is_string($attachments))$attachments=json_decode($attachments,true)?:[];$data=$payload['data_isian']??[];if(is_string($data))$data=json_decode($data,true)?:[];
        [$w,$o,$y]=$this->scope();$values=['kontrak_id'=>$contractId,'master_id'=>$masterId,'kode_dokumen'=>$master['kode'],'nomor_dokumen'=>trim((string)($payload['nomor_dokumen']??''))?:null,'tanggal_dokumen'=>$date,'judul'=>$title,'status'=>in_array($payload['status']??'', ['DRAFT','DIAJUKAN','DISETUJUI','DITANDATANGANI'],true)?$payload['status']:'DRAFT','data_isian'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'dasar_hukum_snapshot'=>$master['dasar_hukum'],'versi_master'=>$master['versi'],'pembuat_pegawai_id'=>$this->user['pegawai_id']??null,'pembuat_role'=>$this->user['type_user']??'','kd_wilayah'=>$w,'kd_opd'=>$contract['kd_opd'],'tahun'=>$y];
        $this->db->begin();try{
            if($id){$old=$this->detail($id);if((int)$old['kontrak_id']!==$contractId)throw new RuntimeException('Kontrak dokumen tidak boleh dipindahkan');$values['tgl_update']=date('Y-m-d H:i:s');$values['username_update']=$this->user['username']??'system';$this->db->update('dokumen_pengadaan_neo',$values,'WHERE id=?',[$id]);}
            else{$values['username_insert']=$this->user['username']??'system';$id=(int)$this->db->insert('dokumen_pengadaan_neo',$values);}
            $masterSections=$this->db->query('SELECT * FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND is_deleted=0 ORDER BY urutan,id',[$masterId])->fetchAll();$given=[];foreach($sections as $s)if(!empty($s['kode_bagian']))$given[(string)$s['kode_bagian']]=$s;
            foreach($masterSections as $s){$custom=$given[$s['kode_bagian']]??[];$body=trim((string)($custom['isi']??$s['isi_template']));if(!empty($s['wajib'])&&$body==='')throw new InvalidArgumentException('Bagian '.$s['judul'].' wajib diisi');$this->db->query('INSERT INTO dokumen_pengadaan_bagian_neo(dokumen_id,master_bagian_id,kode_bagian,judul,urutan,isi,username_insert,is_deleted) VALUES(?,?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE judul=VALUES(judul),urutan=VALUES(urutan),isi=VALUES(isi),tgl_update=NOW(),username_update=VALUES(username_insert),is_deleted=0',[$id,$s['id'],$s['kode_bagian'],$custom['judul']??$s['judul'],$s['urutan'],$body,$this->user['username']??'system']);}
            $this->db->query('UPDATE dokumen_pengadaan_lampiran_neo SET is_deleted=1 WHERE dokumen_id=?',[$id]);
            foreach($attachments as $i=>$a){$attachmentTitle=trim((string)($a['judul']??''));$attachmentBody=trim((string)($a['isi']??''));if($attachmentTitle===''&&$attachmentBody==='')continue;$this->db->insert('dokumen_pengadaan_lampiran_neo',['dokumen_id'=>$id,'jenis_lampiran'=>trim((string)($a['jenis_lampiran']??'NARASI'))?:'NARASI','judul'=>$attachmentTitle?:('Lampiran '.($i+1)),'urutan'=>$i+1,'isi'=>$attachmentBody,'username_insert'=>$this->user['username']??'system','is_deleted'=>0]);}
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw$e;}
        return$this->detail($id);
    }

    public function draft(int $contractId,int $masterId):array
    {
        $c=$this->contract($contractId);$m=$this->db->query('SELECT * FROM master_dokumen_pengadaan_neo WHERE id=? AND aktif=1 AND is_deleted=0',[$masterId])->fetch();if(!$m)throw new RuntimeException('Master dokumen tidak ditemukan');$sections=$this->db->query('SELECT kode_bagian,judul,urutan,isi_template isi,petunjuk_edit,wajib FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND is_deleted=0 ORDER BY urutan,id',[$masterId])->fetchAll();return['contract'=>$c,'master'=>$m,'sections'=>$sections,'attachments'=>[],'recommended_form'=>self::recommendForm((string)($c['cara_pengadaan']??'PENYEDIA'),(string)($c['jenis_pengadaan']??'BARANG'),(string)($c['metode_pemilihan']??''),(float)($c['nilai_kontrak']??0),$c['tipe_swakelola']??null)];
    }

    public function pdf(int $id):string
    {
        $d=$this->detail($id);$c=$this->contract((int)$d['kontrak_id']);$setup=PageSetupService::current($this->user);$pdf=PageSetupService::createPdf($setup,($d['orientasi']??'P'));
        PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);$pdf->SetTitle($d['judul']);$vars=$this->variables($d,$c);
        if(str_starts_with((string)$d['kode_dokumen'],'SPK_')){$pdf->AddPage();$cover='<table border="1" cellpadding="8"><tr style="background-color:#d9ead3"><td align="center"><b style="font-size:15px">SURAT PERINTAH KERJA</b><br><b>{{nama_opd}}</b></td></tr><tr><td align="center"><br><b style="font-size:13px">{{nama_paket}}</b><br><br></td></tr><tr><td><table cellpadding="5"><tr><td width="32%">Nomor SPK</td><td width="68%">: {{nomor_dokumen}}</td></tr><tr><td>Tanggal</td><td>: {{tanggal_dokumen}}</td></tr><tr><td>Sumber Dana</td><td>: {{sumber_dana}}</td></tr><tr><td>Nilai</td><td>: Rp {{nilai_kontrak}}</td></tr><tr><td>Waktu Pelaksanaan</td><td>: '.htmlspecialchars((string)($c['waktu_pelaksanaan']??'-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').' hari kalender</td></tr><tr><td>Penyedia</td><td>: {{nama_penyedia}}</td></tr></table></td></tr><tr style="background-color:#fff2cc"><td align="center"><b>TAHUN ANGGARAN '.(int)$c['tahun'].'</b></td></tr></table>';$pdf->SetFont($setup['font']??'helvetica','',10);$pdf->writeHTML($this->sanitizeHtml($this->renderVariables($cover,$vars)),true,false,true,false,'');}
        $pdf->AddPage();OfficialLetterheadPdfService::draw($pdf,$this->user);$pdf->SetFont($setup['font']??'helvetica','B',13);$pdf->MultiCell(0,7,strtoupper((string)$d['judul']),0,'C');$pdf->SetFont($setup['font']??'helvetica','',9);$pdf->MultiCell(0,5,'Nomor: '.($d['nomor_dokumen']?:'-').' | Tanggal: '.$d['tanggal_dokumen'],0,'C');$pdf->Ln(4);
        foreach($d['sections'] as $section){$pdf->SetFont($setup['font']??'helvetica','B',10);$pdf->MultiCell(0,6,$section['judul'],0,'L');$pdf->SetFont($setup['font']??'helvetica','',9);$body=$this->renderVariables((string)$section['isi'],$vars);$pdf->writeHTML($this->sanitizeHtml($body),true,false,true,false,'');$pdf->Ln(2);}if($d['attachments']){foreach($d['attachments'] as $a){$pdf->AddPage();$pdf->SetFont($setup['font']??'helvetica','B',11);$pdf->MultiCell(0,7,'LAMPIRAN - '.$a['judul'],0,'C');$pdf->SetFont($setup['font']??'helvetica','',9);$pdf->writeHTML($this->sanitizeHtml($this->renderVariables((string)($a['isi']??''),$vars)),true,false,true,false,'');}}
        $pdf->SetFont($setup['font']??'helvetica','I',7);$pdf->MultiCell(0,4,'Dasar format: '.$d['dasar_hukum_snapshot'].' | Master versi '.$d['versi_master'],0,'L');return$pdf->Output('','S');
    }

    private function variables(array $d,array $c):array
    {
        return['nama_opd'=>$this->user['nama_opd']??$this->user['nama_org']??$c['kd_opd'],'nama_dokumen'=>$d['nama_master']??$d['judul'],'nomor_dokumen'=>$d['nomor_dokumen']?:'-','tanggal_dokumen'=>$d['tanggal_dokumen'],'nama_paket'=>$c['uraian_kontrak']??'-','kd_sub_keg'=>$c['kd_sub_keg']??'-','sumber_dana'=>$c['sumber_dana']??'APBD','nilai_kontrak'=>number_format((float)($c['nilai_kontrak']??0),2,',','.'),'nama_ppk'=>$c['nama_ppk_resmi']??$c['nama_ppk']??'-','nip_ppk'=>$c['nip_ppk_resmi']??$c['nip_ppk']??'-','nama_penyedia'=>$c['nama_penyedia_resmi']??$c['nama_penyedia']??'-'];
    }
    private function renderVariables(string $html,array $vars):string{foreach($vars as $k=>$v)$html=str_replace('{{'.$k.'}}',htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),$html);return$html;}
    private function sanitizeHtml(string $html):string{return strip_tags($html,'<p><br><b><strong><i><em><u><table><thead><tbody><tfoot><tr><td><th><ol><ul><li><h1><h2><h3><h4>');}
}
