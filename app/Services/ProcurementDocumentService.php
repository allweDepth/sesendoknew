<?php
require_once __DIR__.'/ContractAccessService.php';
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/../Core/Auth.php';
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/OfficialLetterheadPdfService.php';
require_once __DIR__.'/ProcurementFixedPdfService.php';
require_once __DIR__.'/ProcurementReferenceTermsService.php';
require_once __DIR__.'/SwakelolaDocumentService.php';
require_once __DIR__.'/SwakelolaAutoFillService.php';
require_once __DIR__.'/ContractOfficialService.php';
require_once __DIR__.'/ContractPrintSettingsService.php';
require_once __DIR__.'/ProcurementProcessService.php';

/** Master + snapshot dokumen pengadaan yang dapat diedit tanpa mengubah master. */
final class ProcurementDocumentService
{
    private DB $db;
    private array $user;
    private const EDIT_ROLES=['super_admin','admin_wilayah','admin_opd','kepala_opd','pa_kpa','ppk','pejabat_pengadaan','pokja_ulp'];

    public function __construct(array $user=[]){$this->db=DB::getInstance();$this->user=Auth::scopedUser()?:$user;
        if(!empty($this->user['kd_opd'])){$kop=OfficialLetterheadPdfService::settings($this->user);foreach(['nama_opd'=>'nama_opd','nama_pemerintah'=>'nama_pemda','alamat'=>'alamat_opd'] as $from=>$to)if(!empty($kop[$from]))$this->user[$to]=$kop[$from];}
        foreach(['nama_opd','nama_org'] as $key)if(!empty($this->user[$key]))$this->user[$key]=ProcurementDocumentFormatService::bodyOpdName($this->user[$key]);
    }

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
        [$w,$o,$y]=$this->scope();$sql="SELECT k.*,COALESCE(r.nama_perusahaan,k.nama_penyedia) nama_penyedia_resmi,r.alamat alamat_penyedia,r.npwp,r.direktur,r.jabatan jabatan_pelaksana,r.no_ktp nik_pelaksana,r.email email_pelaksana,r.no_akta_pendirian,r.tgl_akta_pendirian,COALESCE(ppk.nama_pegawai,k.nama_ppk) nama_ppk_resmi,ppk.nip nip_ppk_resmi FROM kontrak_neo k LEFT JOIN rekanan_neo r ON r.id=k.rekanan_id AND r.is_deleted=0 LEFT JOIN pejabat_tahunan_neo ppk ON ppk.id=k.ppk_id AND ppk.is_deleted=0 WHERE k.id=? AND k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0";$p=[$id,$w,$y];
        if($o!==''&&$o!=='0'){$sql.=' AND k.kd_opd=?';$p[]=$o;}$row=$this->db->query($sql.' LIMIT 1',$p)->fetch();if(!$row)throw new RuntimeException('Kontrak tidak ditemukan dalam lingkup pengguna');
        ContractAccessService::assert($this->user,$row);$row=ContractOfficialService::resolve($row);$row['sumber_dana']='APBD';$row['print_settings']=ContractPrintSettingsService::get($id);
        $row['rab']=$this->db->query('SELECT * FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY COALESCE(NULLIF(urutan,0),id),id',[$id])->fetchAll();
        $row['dokumen_referensi']=[];foreach($this->db->query('SELECT kode_dokumen,nomor_dokumen,tanggal_dokumen FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY id',[$id])->fetchAll() as $ref)$row['dokumen_referensi'][$ref['kode_dokumen']]=$ref;
        $vendor=$row['rekanan_id']?$this->db->query('SELECT no_rekening,bank_rekening,atas_nama_rekening,nama_notaris_pendirian FROM rekanan_neo WHERE id=? AND is_deleted=0',[$row['rekanan_id']])->fetch():[];
        return $row+($vendor?:[]);
    }

    public function printSettings(int $id,?array $input=null):array
    {
        $contract=$this->contract($id);
        if($input!==null){$this->canEdit();ContractAccessService::assert($this->user,$contract,'dapat_input');return ContractPrintSettingsService::save($id,$input,(string)($this->user['username']??''));}
        return ContractPrintSettingsService::get($id);
    }
    public function processSchedule(int $id=0):array
    {
        $this->scope();$c=$id?$this->contract($id):[];
        $types=[];foreach(['BARANG','JASA_LAINNYA','PEKERJAAN_KONSTRUKSI','KONSULTANSI','KONSULTANSI_KONSTRUKSI','KONSULTANSI_NON_KONSTRUKSI'] as $kind)$types[$kind]=ProcurementContractTypeService::options(['jenis_pengadaan'=>$kind]);
        $types['SWAKELOLA']=ProcurementContractTypeService::options(['cara_pengadaan'=>'SWAKELOLA']);
        return ['catalog'=>ProcurementProcessService::catalog(),'values'=>$id?ProcurementProcessService::values($c):[],'contract_types'=>$types];
    }
    private function assertApplicable(array $master, array $contract): void
    {
        foreach (['cara_pengadaan','jenis_pengadaan'] as $key) {
            if (($master[$key]??'SEMUA')!=='SEMUA' && $master[$key]!==($contract[$key]??'PENYEDIA')) throw new InvalidArgumentException('Master dokumen tidak sesuai dengan pengadaan kontrak');
        }
        if (!empty($master['tipe_swakelola']) && $master['tipe_swakelola']!==($contract['tipe_swakelola']??null)) throw new InvalidArgumentException('Master tidak sesuai tipe swakelola kontrak');
        if(!empty($master['metode_pemilihan'])&&ProcurementProcessService::flow(['metode_pemilihan'=>$master['metode_pemilihan']])!==ProcurementProcessService::flow($contract))throw new InvalidArgumentException('Master tidak sesuai metode pemilihan kontrak');
        $today=date('Y-m-d');
        if (($master['berlaku_mulai']??'0000-00-00')>$today || (!empty($master['berlaku_sampai']) && $master['berlaku_sampai']<$today)) throw new InvalidArgumentException('Masa berlaku master dokumen telah berakhir atau belum dimulai');
    }

    private function letterhead():array
    {
        [$w,$o,$y]=$this->scope();
        $kop=OfficialLetterheadPdfService::settings($this->user);
        $out=['nama_pemerintah'=>$kop['nama_pemerintah']??$this->user['nama_pemda']??'PEMERINTAH DAERAH',
            'nama_opd'=>$kop['nama_opd']??$this->user['nama_opd']??$this->user['nama_org']??'PERANGKAT DAERAH',
            'alamat'=>$kop['alamat']??$this->user['alamat_opd']??'',
            'kontak'=>implode(' · ',array_filter([$kop['telepon']??'',$kop['email']??'',$kop['website']??'',$kop['kode_pos']??''])),
            'gunakan_gambar_kop'=>!empty($kop['gunakan_gambar_kop'])];
        $root=realpath(__DIR__.'/../..').DIRECTORY_SEPARATOR;
        foreach(['gambar_kop','logo_kiri','logo_kanan'] as $field){
            $path=OfficialLetterheadPdfService::imagePath($kop[$field]??'');
            if($path && str_starts_with($path,$root) && is_file($path) && is_readable($path) && filesize($path)<=3*1024*1024){
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
                if(in_array($mime,['image/png','image/jpeg','image/webp'],true))$out[$field]='data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
            }
        }
        return $out;
    }

    public static function recommendForm(string $way,string $kind,string $method,float $value,?string $swakelolaType=null):string
    {
        $way=strtoupper($way);$kind=strtoupper($kind);$method=strtolower(trim($method));
        if($way==='SWAKELOLA')return'PERJANJIAN_SWAKELOLA';
        if(str_contains($method,'e-purchasing')||str_contains($method,'e purchasing')||str_contains($method,'katalog'))return'SURAT_PESANAN';
        if(in_array($kind,['KONSULTANSI','KONSULTANSI_KONSTRUKSI','KONSULTANSI_NON_KONSTRUKSI'],true))return$value<=100000000?'SPK':'SURAT_PERJANJIAN';
        if($kind==='PEKERJAAN_KONSTRUKSI')return$value<=400000000?'SPK':'SURAT_PERJANJIAN';
        if($value<=10000000)return'BUKTI_PEMBELIAN';
        if($value<=50000000)return'KUITANSI';
        if($value<=200000000)return'SPK';
        return'SURAT_PERJANJIAN';
    }

    public function templates(int $contractId):array
    {
        $c=$this->contract($contractId);$way=strtoupper((string)($c['cara_pengadaan']??'PENYEDIA'));$kind=strtoupper((string)($c['jenis_pengadaan']??'BARANG'));$method=(string)($c['metode_pemilihan']??'');$sw=$c['tipe_swakelola']??null;$value=(float)($c['nilai_kontrak']??$c['total_anggaran']??0);
        $recommended=self::recommendForm($way,$kind,$method,$value,$sw);
        $rows=$this->db->query("SELECT id,kode,nama,kelompok,cara_pengadaan,jenis_pengadaan,metode_pemilihan,tipe_swakelola,bentuk_kontrak,nilai_min,nilai_max,dasar_hukum,sumber_format,versi,orientasi FROM master_dokumen_pengadaan_neo WHERE aktif=1 AND is_deleted=0 AND berlaku_mulai<=CURDATE() AND (berlaku_sampai IS NULL OR berlaku_sampai>=CURDATE()) AND (cara_pengadaan='SEMUA' OR cara_pengadaan=?) AND (jenis_pengadaan='SEMUA' OR jenis_pengadaan=?) AND (tipe_swakelola IS NULL OR tipe_swakelola=?) ORDER BY FIELD(kelompok,'PERSIAPAN','PEMILIHAN','KONTRAK','PELAKSANAAN','SWAKELOLA'),nama",[$way,$kind,$sw])->fetchAll();
        foreach($rows as &$r){$r['direkomendasikan']=($r['bentuk_kontrak']===$recommended)||($way==='SWAKELOLA'&&$r['kode']==='SWAKELOLA_'.$sw);$r['sesuai_nilai']=($value>=(float)$r['nilai_min']&&($r['nilai_max']===null||$value<=(float)$r['nilai_max']));}unset($r);
        return['contract'=>$c,'recommended_form'=>$recommended,'templates'=>$rows,'can_edit'=>in_array($this->user['type_user']??'viewer',self::EDIT_ROLES,true)&&ContractAccessService::canInput($this->user,$c)];
    }

    public function list(int $contractId):array
    {
        $this->contract($contractId);return$this->db->query('SELECT id,master_id,kode_dokumen,nomor_dokumen,tanggal_dokumen,judul,status,versi_master,pembuat_role,tgl_update,tgl_insert FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY tanggal_dokumen DESC,id DESC',[$contractId])->fetchAll();
    }

    public function detail(int $id):array
    {
        [$w,$o,$y]=$this->scope();$sql='SELECT d.*,m.nama nama_master,m.sumber_format,m.orientasi FROM dokumen_pengadaan_neo d JOIN master_dokumen_pengadaan_neo m ON m.id=d.master_id JOIN kontrak_neo k ON k.id=d.kontrak_id WHERE d.id=? AND d.kd_wilayah=? AND d.tahun=? AND d.is_deleted=0 AND k.is_deleted=0';$p=[$id,$w,$y];if($o!==''&&$o!=='0'){$sql.=' AND d.kd_opd=?';$p[]=$o;}$doc=$this->db->query($sql.' LIMIT 1',$p)->fetch();if(!$doc)throw new RuntimeException('Dokumen pengadaan tidak ditemukan');$this->contract((int)$doc['kontrak_id']);$doc['sections']=$this->db->query('SELECT id,kode_bagian,judul,urutan,isi FROM dokumen_pengadaan_bagian_neo WHERE dokumen_id=? AND is_deleted=0 ORDER BY urutan,id',[$id])->fetchAll();$doc['attachments']=$this->db->query('SELECT id,jenis_lampiran,judul,urutan,isi,nama_file,mime_type,ukuran FROM dokumen_pengadaan_lampiran_neo WHERE dokumen_id=? AND is_deleted=0 ORDER BY urutan,id',[$id])->fetchAll();if(SwakelolaDocumentService::supports($doc['kode_dokumen'])){$doc['kop_surat']=$this->letterhead();foreach($doc['sections'] as &$section){$blocks=SwakelolaDocumentService::loadBlocks($this->db,'dokumen_bagian_id',(int)$section['id']);if($blocks!==null)$section['isi']=json_encode($blocks,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}unset($section);}return$doc;
    }

    public function save(array $payload):array
    {
        $this->canEdit();$id=(int)($payload['id']??0);$contractId=(int)($payload['contract_id']??0);$masterId=(int)($payload['master_id']??0);$date=trim((string)($payload['tanggal_dokumen']??''));$title=trim((string)($payload['judul']??''));
        if(!$contractId||!$masterId||$date===''||$title==='')throw new InvalidArgumentException('Kontrak, master, tanggal, dan judul dokumen wajib diisi');$contract=$this->contract($contractId);ContractAccessService::assert($this->user,$contract,'dapat_input');
        $master=$this->db->query('SELECT * FROM master_dokumen_pengadaan_neo WHERE id=? AND aktif=1 AND is_deleted=0 LIMIT 1',[$masterId])->fetch();if(!$master)throw new RuntimeException('Master dokumen tidak aktif');$this->assertApplicable($master,$contract);
        $sections=$payload['sections']??[];if(is_string($sections))$sections=json_decode($sections,true)?:[];$attachments=$payload['attachments']??[];if(is_string($attachments))$attachments=json_decode($attachments,true)?:[];$data=$payload['data_isian']??[];if(is_string($data))$data=json_decode($data,true)?:[];
        if(SwakelolaDocumentService::supports($master['kode'])){$draft=$this->draft($contractId,$masterId);if(!$sections)$sections=$draft['sections'];if(!$data)$data=$draft['data_isian'];}
        [$w,$o,$y]=$this->scope();$values=['kontrak_id'=>$contractId,'master_id'=>$masterId,'kode_dokumen'=>$master['kode'],'nomor_dokumen'=>trim((string)($payload['nomor_dokumen']??''))?:null,'tanggal_dokumen'=>$date,'judul'=>$title,'status'=>in_array($payload['status']??'', ['DRAFT','DIAJUKAN','DISETUJUI','DITANDATANGANI'],true)?$payload['status']:'DRAFT','data_isian'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'dasar_hukum_snapshot'=>$master['dasar_hukum'],'versi_master'=>$master['versi'],'pembuat_pegawai_id'=>$this->user['pegawai_id']??null,'pembuat_role'=>$this->user['type_user']??'','kd_wilayah'=>$w,'kd_opd'=>$contract['kd_opd'],'tahun'=>$y];
        $this->db->begin();try{
            if($id){$old=$this->detail($id);if((int)$old['master_id']!==$masterId)throw new RuntimeException('Master dokumen hasil tidak boleh diganti');if((int)$old['kontrak_id']!==$contractId)throw new RuntimeException('Kontrak dokumen tidak boleh dipindahkan');$values['tgl_update']=date('Y-m-d H:i:s');$values['username_update']=$this->user['username']??'system';$this->db->update('dokumen_pengadaan_neo',$values,'WHERE id=?',[$id]);}
            else{$values['username_insert']=$this->user['username']??'system';$id=(int)$this->db->insert('dokumen_pengadaan_neo',$values);}
            $masterSections=$this->db->query('SELECT * FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND is_deleted=0 ORDER BY urutan,id',[$masterId])->fetchAll();$given=[];foreach($sections as $s)if(!empty($s['kode_bagian']))$given[(string)$s['kode_bagian']]=$s;
            foreach($masterSections as $s){$custom=$given[$s['kode_bagian']]??[];$body=trim((string)($custom['isi']??$s['isi_template']));if(SwakelolaDocumentService::supports($master['kode'])){$blocks=json_decode($body,true,512,JSON_THROW_ON_ERROR);if(!is_array($blocks))throw new InvalidArgumentException('Struktur dokumen tidak valid');SwakelolaDocumentService::validate($blocks);$body=json_encode(SwakelolaDocumentService::fill($blocks,SwakelolaDocumentService::identity($contract,$this->user)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}if(!empty($s['wajib'])&&$body==='')throw new InvalidArgumentException('Bagian '.$s['judul'].' wajib diisi');$this->db->query('INSERT INTO dokumen_pengadaan_bagian_neo(dokumen_id,master_bagian_id,kode_bagian,judul,urutan,isi,username_insert,is_deleted) VALUES(?,?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE judul=VALUES(judul),urutan=VALUES(urutan),isi=VALUES(isi),tgl_update=NOW(),username_update=VALUES(username_insert),is_deleted=0',[$id,$s['id'],$s['kode_bagian'],$custom['judul']??$s['judul'],$s['urutan'],$body,$this->user['username']??'system']);}
            if(SwakelolaDocumentService::supports($master['kode'])){foreach($this->db->query('SELECT id,isi FROM dokumen_pengadaan_bagian_neo WHERE dokumen_id=? AND is_deleted=0',[$id])->fetchAll() as $part)SwakelolaDocumentService::storeBlocks($this->db,'dokumen_bagian_id',(int)$part['id'],json_decode($part['isi'],true,512,JSON_THROW_ON_ERROR));}
            $this->db->query('UPDATE dokumen_pengadaan_lampiran_neo SET is_deleted=1 WHERE dokumen_id=?',[$id]);
            foreach($attachments as $i=>$a){$attachmentTitle=trim((string)($a['judul']??''));$attachmentBody=trim((string)($a['isi']??''));if($attachmentTitle===''&&$attachmentBody==='')continue;$this->db->insert('dokumen_pengadaan_lampiran_neo',['dokumen_id'=>$id,'jenis_lampiran'=>trim((string)($a['jenis_lampiran']??'NARASI'))?:'NARASI','judul'=>$attachmentTitle?:('Lampiran '.($i+1)),'urutan'=>$i+1,'isi'=>$attachmentBody,'username_insert'=>$this->user['username']??'system','is_deleted'=>0]);}
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw$e;}
        return$this->detail($id);
    }

    public function draft(int $contractId,int $masterId):array
    {
        $c=$this->contract($contractId);
        $m=$this->db->query('SELECT * FROM master_dokumen_pengadaan_neo WHERE id=? AND aktif=1 AND is_deleted=0',[$masterId])->fetch();
        if(!$m)throw new RuntimeException('Master dokumen tidak ditemukan');
        $this->assertApplicable($m,$c);
        $sections=$this->db->query('SELECT id,kode_bagian,judul,urutan,isi_template isi,petunjuk_edit,wajib FROM master_dokumen_pengadaan_bagian_neo WHERE master_id=? AND is_deleted=0 ORDER BY urutan,id',[$masterId])->fetchAll();
        $code=strtoupper((string)$m['kode']);$extra=[];$process=ProcurementProcessService::documentValue($c,$code);
        if(SwakelolaDocumentService::supports($code)){
            $auto=new SwakelolaAutoFillService($this->db,$c,$this->user,['nomor_dokumen'=>$process['nomor']??'','tanggal_dokumen'=>$process['tanggal']??'']);
            foreach($sections as &$section){
                $blocks=SwakelolaDocumentService::loadBlocks($this->db,'master_bagian_id',(int)$section['id'])??json_decode($section['isi'],true,512,JSON_THROW_ON_ERROR);
                $blocks=SwakelolaDocumentService::fill($blocks,SwakelolaDocumentService::identity($c,$this->user));
                $section['isi']=json_encode($auto->blocks($blocks),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }unset($section);
            $extra=['judul'=>mb_substr($m['nama'].' — '.$c['uraian_kontrak'],0,255),'nomor_dokumen'=>$c['nomor_kontrak']??'',
                'tanggal_dokumen'=>$c['tanggal_kontrak']?:date('Y-m-d'),
                'data_isian'=>['rekanan_id'=>$c['rekanan_id'],'kontrak_id'=>$contractId,'otomatis'=>true,'data'=>$auto->data(),'data_belum_tersedia'=>$auto->missing()]];
        }
        if(in_array($code,['SURAT_PERJANJIAN','SSKK','SSUK'],true)){
            $reference=ProcurementReferenceTermsService::rows($code,$c);
            if($reference){
                if(!$sections)$sections=[['kode_bagian'=>'ISI','judul'=>'Isi Dokumen','urutan'=>1,'wajib'=>1]];
                $sections[0]['isi']=json_encode($reference,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
        }
        if(SwakelolaDocumentService::supports($code))$extra['kop_surat']=$this->letterhead();
        $process=ProcurementProcessService::documentValue($c,$code);
        $extra['nomor_dokumen']=$process['nomor']??'';$extra['tanggal_dokumen']=$process['tanggal']??'';
        return $extra+['contract'=>$c,'master'=>$m,'sections'=>$sections,'attachments'=>[],
            'recommended_form'=>self::recommendForm($c['cara_pengadaan']??'PENYEDIA',$c['jenis_pengadaan']??'BARANG',$c['metode_pemilihan']??'',(float)($c['nilai_kontrak']??0),$c['tipe_swakelola']??null)];
    }

    public function generateSwakelola(int $contractId):array
    {
        $this->canEdit();$available=$this->templates($contractId);
        if(($available['contract']['cara_pengadaan']??'')!=='SWAKELOLA')throw new InvalidArgumentException('Paket bukan pengadaan swakelola');
        $created=0;$existing=0;$pending=[];$missingDates=[];
        foreach($available['templates'] as $master){
            // Complete reference packages remain available individually; avoid duplicate bundles.
            if(!SwakelolaDocumentService::supports($master['kode'])||str_ends_with($master['kode'],'_LENGKAP'))continue;
            $old=$this->db->query('SELECT id FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND master_id=? AND is_deleted=0 LIMIT 1',[$contractId,$master['id']])->fetch();
            if($old){$existing++;continue;}
            $draft=$this->draft($contractId,(int)$master['id']);
            if(empty($draft['tanggal_dokumen']))$missingDates[]=$master['nama'];
            $pending[]=[$master,$draft];
        }
        if($missingDates)throw new FieldValidationException('proses_pengadaan','Lengkapi tanggal proses Swakelola pada edit kontrak sebelum menyusun dokumen: '.implode('; ',$missingDates));
        foreach($pending as [$master,$draft]){
            $this->save(['contract_id'=>$contractId,'master_id'=>$master['id'],'judul'=>$draft['judul'],
                'nomor_dokumen'=>$draft['nomor_dokumen'],'tanggal_dokumen'=>$draft['tanggal_dokumen'],
                'sections'=>$draft['sections'],'data_isian'=>$draft['data_isian'],'status'=>'DRAFT']);
            $created++;
        }
        return ['created'=>$created,'existing'=>$existing,'contract_id'=>$contractId];
    }

    public function swakelolaContractPdf(int $contractId):string
    {
        $c=$this->contract($contractId);$type=$c['tipe_swakelola']??'';
        if(($c['cara_pengadaan']??'')!=='SWAKELOLA'||!in_array($type,['I','II','III','IV'],true))throw new InvalidArgumentException('Tipe swakelola kontrak belum dipilih');
        $method=mb_strtolower($c['metode_pemilihan']??'');
        $source=($type==='III'&&str_contains($method,'pemilihan'))||($type==='IV'&&str_contains($method,'usulan'))?'03':'01';
        $chapter=$type==='III'?'08':'07';$code='SW_'.$type.'_'.$source.'_'.$chapter;
        $master=$this->db->query('SELECT id FROM master_dokumen_pengadaan_neo WHERE kode=? AND aktif=1 AND is_deleted=0 ORDER BY id DESC LIMIT 1',[$code])->fetch();
        if(!$master)throw new RuntimeException('Master pokok perjanjian swakelola belum terpasang');
        $saved=$this->db->query('SELECT id FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND master_id=? AND is_deleted=0 ORDER BY tanggal_dokumen DESC,id DESC LIMIT 1',[$contractId,$master['id']])->fetch();
        if($saved)return $this->pdf((int)$saved['id']);
        $draft=$this->draft($contractId,(int)$master['id']);
        return SwakelolaDocumentService::render($draft,$draft['contract'],$this->user,PageSetupService::current($this->user));
    }

    public function delete(int $id):array
    {
        $this->canEdit();$document=$this->detail($id);ContractAccessService::assert($this->user,$this->contract((int)$document['kontrak_id']),'dapat_input');$username=$this->user['username']??'system';$now=date('Y-m-d H:i:s');
        $this->db->begin();try{
            $this->db->query('UPDATE dokumen_pengadaan_bagian_neo SET is_deleted=1,tgl_update=?,username_update=? WHERE dokumen_id=? AND is_deleted=0',[$now,$username,$id]);
            $this->db->query('UPDATE dokumen_pengadaan_lampiran_neo SET is_deleted=1 WHERE dokumen_id=? AND is_deleted=0',[$id]);
            $this->db->query('UPDATE dokumen_pengadaan_neo SET is_deleted=1,tgl_update=?,username_update=? WHERE id=? AND is_deleted=0',[$now,$username,$id]);
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw$e;}
        return['id'=>$id,'judul'=>$document['judul'],'deleted'=>true];
    }

    public function pdf(int $id):string
    {
        $d=$this->detail($id);$c=$this->contract((int)$d['kontrak_id']);$setup=PageSetupService::current($this->user);if(SwakelolaDocumentService::supports($d['kode_dokumen']))return SwakelolaDocumentService::render($d,$c,$this->user,$setup);$pdf=PageSetupService::createPdf($setup,($d['orientasi']??'P'));
        return(new ProcurementFixedPdfService($this->user))->render($d,$c,$setup);
        PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);$pdf->SetTitle($d['judul']);$vars=$this->variables($d,$c);
        if(str_starts_with((string)$d['kode_dokumen'],'SPK_')){$pdf->AddPage();$cover='<table border="1" cellpadding="8"><tr style="background-color:#d9ead3"><td align="center"><b style="font-size:15px">SURAT PERINTAH KERJA</b><br><b>{{nama_opd}}</b></td></tr><tr><td align="center"><br><b style="font-size:13px">{{nama_paket}}</b><br><br></td></tr><tr><td><table cellpadding="5"><tr><td width="32%">Nomor SPK</td><td width="68%">: {{nomor_dokumen}}</td></tr><tr><td>Tanggal</td><td>: {{tanggal_dokumen}}</td></tr><tr><td>Sumber Dana</td><td>: {{sumber_dana}}</td></tr><tr><td>Nilai</td><td>: Rp {{nilai_kontrak}}</td></tr><tr><td>Waktu Pelaksanaan</td><td>: '.htmlspecialchars((string)($c['waktu_pelaksanaan']??'-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').' hari kalender</td></tr><tr><td>Penyedia</td><td>: {{nama_penyedia}}</td></tr></table></td></tr><tr style="background-color:#fff2cc"><td align="center"><b>TAHUN ANGGARAN '.(int)$c['tahun'].'</b></td></tr></table>';$pdf->SetFont($setup['font']??'helvetica','',10);$pdf->writeHTML($this->sanitizeHtml($this->renderVariables($cover,$vars)),true,false,true,false,'');}
        $pdf->AddPage();OfficialLetterheadPdfService::draw($pdf,$this->user);$pdf->SetFont($setup['font']??'helvetica','B',13);$pdf->MultiCell(0,7,strtoupper((string)$d['judul']),0,'C');$pdf->SetFont($setup['font']??'helvetica','',9);$pdf->MultiCell(0,5,'Nomor: '.($d['nomor_dokumen']?:'-').' | Tanggal: '.$d['tanggal_dokumen'],0,'C');$pdf->Ln(4);
        foreach($d['sections'] as $section){$pdf->SetFont($setup['font']??'helvetica','B',10);$pdf->MultiCell(0,6,$section['judul'],0,'L');$pdf->SetFont($setup['font']??'helvetica','',9);$body=$this->renderVariables((string)$section['isi'],$vars);$pdf->writeHTML($this->sanitizeHtml($body),true,false,true,false,'');$pdf->Ln(2);}if($d['attachments']){foreach($d['attachments'] as $a){$pdf->AddPage();$pdf->SetFont($setup['font']??'helvetica','B',11);$pdf->MultiCell(0,7,'LAMPIRAN - '.$a['judul'],0,'C');$pdf->SetFont($setup['font']??'helvetica','',9);$pdf->writeHTML($this->sanitizeHtml($this->renderVariables((string)($a['isi']??''),$vars)),true,false,true,false,'');}}
        $pdf->SetFont($setup['font']??'helvetica','I',7);$pdf->MultiCell(0,4,'Dasar format: '.$d['dasar_hukum_snapshot'].' | Master versi '.$d['versi_master'],0,'L');return$pdf->Output('','S');
    }

    /** Preserve edited snapshots; only create missing applicable documents. */
    public function bundlePdf(int $contractId):string
    {
        $this->canEdit();$info=$this->templates($contractId);$c=$info['contract'];
        if(($c['cara_pengadaan']??'')!=='PENYEDIA')throw new InvalidArgumentException('Gunakan penyusunan dokumen swakelola untuk paket ini');
        $flow=ProcurementProcessService::flow($c);
        $order=array_column(ProcurementProcessService::steps($c),0);
        $form=$info['recommended_form'];$kind=$c['jenis_pengadaan'];$main=$form==='SPK'?($kind==='PEKERJAAN_KONSTRUKSI'?'SPK_KONSTRUKSI':'SPK_'.$kind):($form==='SURAT_PERJANJIAN'?'SURAT_PERJANJIAN':$form);
        $order=array_values(array_unique(array_map(static fn($code)=>$code==='KONTRAK'?$main:$code,$order)));
        $reference=$flow==='LANGSUNG'&&ProcurementDocumentFormatService::supports('SAMPUL',$c);
        if($flow==='LANGSUNG'){$sampleOrder=['UNDANGAN_PL','JADWAL_DOKUMEN','BA_SURVEY_HARGA','PAKTA_INTEGRITAS','BA_KLARIFIKASI_NEGOSIASI','LAMPIRAN_NEGOSIASI','DAFTAR_HADIR_KLARIFIKASI','PENETAPAN_PENYEDIA','PENGUMUMAN_PENYEDIA','BA_EVALUASI','BAHP','SPPBJ','SPMK','RAPAT_PRA_KONTRAK'];$order=array_merge([$main],array_reverse($sampleOrder));}
        $existing=[];foreach($this->list($contractId) as $d)if(!isset($existing[$d['kode_dokumen']]))$existing[$d['kode_dokumen']]=$d['id'];
        $masters=[];foreach($info['templates'] as $m)if($m['sesuai_nilai']&&(!$m['metode_pemilihan']||ProcurementProcessService::flow(['metode_pemilihan'=>$m['metode_pemilihan']])===$flow))$masters[$m['kode']]=$m;
        $missing=[];foreach($order as $code)if(!isset($existing[$code])&&isset($masters[$code])&&empty(ProcurementProcessService::documentValue($c,$code)['tanggal']))$missing[]=$masters[$code]['nama'];
        if($missing)throw new InvalidArgumentException('Lengkapi tanggal proses di form kontrak: '.implode(', ',$missing));
        foreach($order as $code){
            if(isset($existing[$code])||!isset($masters[$code]))continue;
            $m=$masters[$code];$draft=$this->draft($contractId,(int)$m['id']);$process=ProcurementProcessService::documentValue($c,$code);
            $saved=$this->save(['contract_id'=>$contractId,'master_id'=>$m['id'],'judul'=>$m['nama'],'nomor_dokumen'=>$process['nomor']??'','tanggal_dokumen'=>$process['tanggal'],'status'=>'DRAFT','sections'=>$draft['sections'],'data_isian'=>['dibuat_sebagai'=>'bundel','rup_sementara'=>str_contains($c['keterangan']??'','RUP acak sementara')]]);
            $existing[$code]=$saved['id'];
        }
        $c=$this->contract($contractId);$setup=PageSetupService::current($this->user);if($reference)$setup=ProcurementDocumentFormatService::setup($setup,$c['print_settings']??[]);$pdf=PageSetupService::createPdf($setup,'P');$pdf->SetTitle('Dokumen Pengadaan - '.$c['uraian_kontrak']);
        $renderer=new ProcurementFixedPdfService($this->user);
        if($form==='SPK'){$renderer->cover($pdf,$c,$setup);$pdf->Bookmark('Sampul SPK',0,0,$pdf->getPage());}
        foreach($order as $code){
            if(!isset($existing[$code]))continue;
            $d=$this->detail((int)$existing[$code]);$d['include_cover']=false;$start=$pdf->getNumPages()+1;
            $renderer->renderInto($pdf,$d,$c,$setup);$pdf->Bookmark($d['nama_master']??$d['judul'],0,0,$start);
        }
        if(!$pdf->getNumPages())throw new RuntimeException('Tidak ada dokumen yang sesuai untuk digabung');
        return $pdf->Output('','S');
    }

    private function variables(array $d,array $c):array
    {
        return['nama_opd'=>$this->user['nama_opd']??$this->user['nama_org']??$c['kd_opd'],'nama_dokumen'=>$d['nama_master']??$d['judul'],'nomor_dokumen'=>$d['nomor_dokumen']?:'-','tanggal_dokumen'=>$d['tanggal_dokumen'],'nama_paket'=>$c['uraian_kontrak']??'-','kd_sub_keg'=>$c['kd_sub_keg']??'-','sumber_dana'=>$c['sumber_dana']??'APBD','nilai_kontrak'=>number_format((float)($c['nilai_kontrak']??0),2,',','.'),'nama_ppk'=>$c['nama_ppk_resmi']??$c['nama_ppk']??'-','nip_ppk'=>$c['nip_ppk_resmi']??$c['nip_ppk']??'-','nama_penyedia'=>$c['nama_penyedia_resmi']??$c['nama_penyedia']??'-'];
    }
    private function renderVariables(string $html,array $vars):string{foreach($vars as $k=>$v)$html=str_replace('{{'.$k.'}}',htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),$html);return$html;}
    private function sanitizeHtml(string $html):string{return strip_tags($html,'<p><br><b><strong><i><em><u><table><thead><tbody><tfoot><tr><td><th><ol><ul><li><h1><h2><h3><h4>');}
}
