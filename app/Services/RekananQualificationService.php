<?php
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/../Core/Auth.php';
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/ContractAccessService.php';
require_once __DIR__.'/RekananQualificationData.php';

/** Printable supplier declarations and qualification schedules; no eligibility is asserted by the application. */
final class RekananQualificationService
{
    private DB $db;
    private array $user;
    public const TYPES=['konstruksi'=>'Pekerjaan Konstruksi','barang_jasa'=>'Barang / Jasa Lainnya','konsultansi'=>'Jasa Konsultansi'];
    public const METHODS=['langsung'=>'Pengadaan Langsung','tender'=>'Tender / Seleksi','mini'=>'Mini Kompetisi'];
    public function __construct(array $user=[]){$this->db=DB::getInstance();$this->user=$user?:Auth::scopedUser();}
    public function vendor(int $id):array
    {
        if(!in_array($this->user['type_user']??'',['super_admin','admin_wilayah','admin_opd'],true))throw new RuntimeException('Akses referensi rekanan tidak diizinkan.');
        $region=trim((string)($this->user['kd_wilayah']??''));if($region==='')throw new RuntimeException('Pilih wilayah terlebih dahulu.');
        $r=$this->db->query('SELECT * FROM rekanan_neo WHERE id=? AND kd_wilayah=? AND is_deleted=0',[$id,$region])->fetch();
        if(!$r)throw new RuntimeException('Rekanan tidak ditemukan dalam wilayah pengguna.');return $r;
    }
    public function options(int $id):array
    {
        $r=$this->vendor($id);$sql="SELECT k.id,k.uraian_kontrak,k.nomor_rup,k.jenis_pengadaan,k.metode_pemilihan FROM kontrak_neo k WHERE k.rekanan_id=? AND k.kd_wilayah=? AND k.tahun=? AND k.is_deleted=0 AND k.cara_pengadaan='PENYEDIA'";
        $p=[$id,$this->user['kd_wilayah'],(int)($this->user['tahun']??0)];
        if(!empty($this->user['kd_opd'])&&$this->user['kd_opd']!=='0'){$sql.=' AND k.kd_opd=?';$p[]=$this->user['kd_opd'];}
        [$scope,$params]=ContractAccessService::condition($this->user);$sql.=$scope;$p=array_merge($p,$params);
        return ['id'=>$id,'nama_perusahaan'=>$r['nama_perusahaan'],'penandatangan'=>['nama'=>$r['direktur'],'jabatan'=>$r['jabatan']?:'Direktur','identitas'=>$r['no_ktp']??'','dasar'=>($r['no_akta_pendirian']?'Akta '.$r['no_akta_pendirian'].($r['tgl_akta_pendirian']?' tanggal '.$r['tgl_akta_pendirian']:''):'')],'paket'=>$this->db->query($sql.' ORDER BY k.uraian_kontrak',$p)->fetchAll()];
    }
    public function qualification(int $id):array
    {
        $v=$this->vendor($id);$saved=json_decode($v['kualifikasi_json']??'null',true)?:[];
        if(($saved['schema_version']??1)!==1)throw new RuntimeException('Versi skema kualifikasi tidak didukung.');
        $saved+=['versi'=>0,'profil'=>[],'rincian'=>[]];
        foreach(['pekerjaan_berjalan','personel','peralatan','pengalaman'] as $kind){if(isset($v[$kind.'_json']))$saved['rincian'][$kind]=json_decode($v[$kind.'_json'],true,512,JSON_THROW_ON_ERROR)?:[];}
        
        if(empty($saved['rincian']['izin'])&&$saved['versi']===0&&preg_match('/\bNIB\s*:?\s*(\d{10,20})/i',(string)($v['keterangan']??''),$match))$saved['rincian']['izin']=[['jenis_nomor'=>'NIB '.$match[1]]];
        return ['id'=>$id,'nama_perusahaan'=>$v['nama_perusahaan'],'data'=>$saved,'profil_fields'=>array_diff_key(RekananQualificationData::profile(),['alamat_pusat'=>true]),'sections'=>RekananQualificationData::sections()];
    }
    public function saveQualification(int $id,array $input):array
    {
        $this->vendor($id);$clean=RekananQualificationData::clean($input);$expected=filter_var($input['versi']??null,FILTER_VALIDATE_INT);
        if($expected===false||$expected===null||$expected<0)throw new InvalidArgumentException('Versi data tidak valid. Muat ulang form.');
        $this->db->begin();try{
            $row=$this->db->query('SELECT kualifikasi_json FROM rekanan_neo WHERE id=? AND kd_wilayah=? AND is_deleted=0 FOR UPDATE',[$id,$this->user['kd_wilayah']])->fetch();if(!$row)throw new RuntimeException('Rekanan tidak ditemukan.');
            $old=json_decode($row['kualifikasi_json']??'null',true)?:[];
            if((int)($old['versi']??0)!==$expected)throw new RuntimeException('Data telah berubah oleh pengguna lain. Muat ulang sebelum menyimpan agar perubahan tidak tertimpa.');
            $clean+=['schema_version'=>1,'versi'=>$expected+1,'updated_at'=>date('c'),'updated_by'=>$this->user['username']??'system'];
            $changes=['username_update'=>$this->user['username']??'system'];
            foreach(['pekerjaan_berjalan','personel','peralatan','pengalaman'] as $kind){$changes[$kind.'_json']=json_encode($clean['rincian'][$kind],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);unset($clean['rincian'][$kind]);}
            $changes['kualifikasi_json']=json_encode($clean,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $this->db->update('rekanan_neo',$changes,'WHERE id=? AND kd_wilayah=?',[$id,$this->user['kd_wilayah']]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollback();throw $e;}
        return $this->qualification($id);
    }
    public function data(int $id,array $input):array
    {
        $v=$this->vendor($id);$type=(string)($input['jenis']??'konstruksi');$method=(string)($input['metode']??'langsung');
        if(!isset(self::TYPES[$type])||!isset(self::METHODS[$method]))throw new InvalidArgumentException('Jenis format atau metode pemilihan tidak valid.');
        $detail=(string)($input['subjenis']??($type==='konsultansi'?'konstruksi':'barang'));
        if($type==='barang_jasa'&&!in_array($detail,['barang','jasa_lainnya'],true))throw new InvalidArgumentException('Pilih barang atau jasa lainnya.');
        if($type==='konsultansi'&&!in_array($detail,['konstruksi','nonkonstruksi'],true))throw new InvalidArgumentException('Pilih konsultansi konstruksi atau nonkonstruksi.');
        $date=trim((string)($input['tanggal']??''));if($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))))throw new InvalidArgumentException('Tanggal penandatanganan tidak valid.');
        $package=trim((string)($input['paket_nama']??''));$place=trim((string)($input['tempat']??''));$contract=null;
        if(mb_strlen($package)>400||mb_strlen($place)>150)throw new InvalidArgumentException('Nama paket atau tempat terlalu panjang.');
        $cid=filter_var($input['kontrak_id']??0,FILTER_VALIDATE_INT);if($cid===false||$cid<0)throw new InvalidArgumentException('Paket tidak valid.');
        if($cid){$ids=array_column($this->options($id)['paket'],'id');if(!in_array($cid,array_map('intval',$ids),true))throw new RuntimeException('Paket tidak terkait rekanan atau di luar lingkup pengguna.');$contract=$this->db->query('SELECT * FROM kontrak_neo WHERE id=?',[$cid])->fetch();$package=$contract['uraian_kontrak'];}
        $mode=(string)($input['kualifikasi']??($type==='konsultansi'?'pra':'pasca'));if(!in_array($mode,['pra','pasca'],true))throw new InvalidArgumentException('Tahapan kualifikasi tidak valid.');
        $original=$v;$signer=[];foreach(['penandatangan_nama','penandatangan_jabatan','penandatangan_identitas','dasar_kewenangan'] as $key){$value=trim((string)($input[$key]??''));if(mb_strlen($value)>500)throw new InvalidArgumentException('Data penandatangan terlalu panjang.');$signer[$key]=$value;}
        if($signer['penandatangan_nama']!=='')$v['direktur']=$signer['penandatangan_nama'];
        if($signer['penandatangan_jabatan']!=='')$v['jabatan']=$signer['penandatangan_jabatan'];
        if(array_key_exists('penandatangan_identitas',$input))$v['no_ktp']=$signer['penandatangan_identitas'];
        return ['master_vendor'=>$original,'signer'=>$signer,'vendor'=>$v,'type'=>$type,'detail'=>$detail,'method'=>$method,'mode'=>$mode,'date'=>$date,'place'=>$place,'package'=>$package,'contract'=>$contract];
    }
    private function e($s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private function value($v):string{return trim((string)$v)!==''?$this->e($v):'................................................................';}
    private function page(TCPDF $p,string $title,string $orientation='P'):void
    {
        $p->AddPage($orientation);$p->SetFont('helvetica','B',12);$p->MultiCell(0,7,str_replace("\\n","\n",$title),0,'C');$p->Ln(3);$p->SetFont('helvetica','',10);
    }
    private function kv(TCPDF $p,array $rows):void
    {
        $h='<table cellpadding="4">';foreach($rows as $l=>$v)$h.='<tr nobr="true"><td width="34%">'.$this->e($l).'</td><td width="3%">:</td><td width="63%">'.$this->value($v).'</td></tr>';$p->writeHTML($h.'</table>');
    }
    private function heading(TCPDF $p,string $s):void{$p->Ln(3);$p->SetFont('helvetica','B',10);$p->MultiCell(0,6,$s,0,'L');$p->SetFont('helvetica','',10);}
    private function para(TCPDF $p,string $s):void{$p->MultiCell(0,0,$s,0,'L',false,1);$p->Ln(3);}
    private function numbered(TCPDF $p,int $number,string $text):void
    {
        $p->writeHTML('<table width="100%" cellpadding="0" cellspacing="0"><tr nobr="true"><td width="5%" valign="top">'.$number.'.</td><td width="95%" valign="top"><p align="justify">'.$this->e($text).'</p></td></tr></table>',true,false,true,false,'');
        $p->Ln(1);
    }
    private function table(TCPDF $p,array $heads,array $rows=[],int $blankRows=3):void
    {
        $p->Ln(2);$w=96/max(1,count($heads)-1);$h='<table border="1" cellpadding="5"><thead><tr style="font-weight:bold;background-color:#edf2f7">';foreach($heads as $j=>$s)$h.='<th width="'.($j===0?4:$w).'%">'.$this->e($s).'</th>';$h.='</tr></thead><tbody>';
        foreach($rows as $r){$h.='<tr nobr="true">';foreach($r as $j=>$s)$h.='<td width="'.($j===0?4:$w).'%">'.(trim((string)$s)===''?'&nbsp;':$this->e($s)).'</td>';$h.='</tr>';}
        for($i=0;$i<$blankRows;$i++){$h.='<tr nobr="true">';foreach($heads as $j=>$s)$h.='<td width="'.($j===0?4:$w).'%" height="28">'.($j===0?(string)(count($rows)+$i+1):'').'</td>';$h.='</tr>';}
        $p->writeHTML($h.'</tbody></table>',true,false,true,false,'');$p->Ln(3);
    }
    private function signature(TCPDF $p,array $d):void
    {
        $v=$d['vendor'];$date=$d['date']!==''?date('d-m-Y',strtotime($d['date'])):'........................';
        $p->writeHTML('<table nobr="true" cellpadding="3"><tr><td width="48%"></td><td width="52%" align="center">'.$this->value($d['place']).', '.$this->e($date).'<br>Untuk dan atas nama<br>'.$this->e($v['nama_perusahaan']).'<br><br>Meterai sesuai ketentuan<br><br><br><b><u>'.$this->value($v['direktur']).'</u></b><br>'.$this->value($v['jabatan']).'</td></tr></table>');
    }
    public function pdf(int $id,array $input=[]):string
    {
        $d=$this->data($id,$input);$saved=$this->qualification($id)['data'];$q=$saved['rincian'];$pr=$saved['profil'];$v=$d['vendor'];$type=$d['type'];$construction=$type==='konstruksi'||($type==='konsultansi'&&$d['detail']==='konstruksi');$consult=$type==='konsultansi';
        $label=self::TYPES[$type];if($type==='barang_jasa')$label=$d['detail']==='barang'?'Pengadaan Barang':'Jasa Lainnya';if($consult)$label.=$construction?' Konstruksi':' Nonkonstruksi';
        // Supplier forms never inherit an OPD government letterhead or government signatures.
        $setup=PageSetupService::current($this->user);$setup=array_replace($setup,['header_enabled'=>false,'footer_enabled'=>false,'orientation'=>'AUTO','margin_left'=>15,'margin_right'=>15,'margin_top'=>15,'margin_bottom'=>15]);
        $p=PageSetupService::createPdf($setup);PageSetupService::applyPdf($p,$setup);$p->SetTitle('Isian Kualifikasi - '.$v['nama_perusahaan']);$p->SetCreator('seSendok');
        $this->page($p,'PAKTA INTEGRITAS');
        $this->identity($p,$v);$this->para($p,'Dalam rangka '.self::METHODS[$d['method']].' untuk paket '.($d['package']?:'........................................................').', dengan ini menyatakan bahwa:');
        $integrity=$this->declarations($d,$pr)['integrity'];
        foreach($integrity as $i=>$n)$this->numbered($p,$i+1,$n);$this->signature($p,$d);
        $this->page($p,'SURAT PERNYATAAN PESERTA DAN KEBENARAN DATA KUALIFIKASI');$this->identity($p,$v);
        $this->para($p,'Dengan sesungguhnya menyatakan bahwa:');
        $statements=$this->declarations($d,$pr)['statements'];
        foreach($statements as $i=>$n)$this->numbered($p,$i+1,$n);$this->signature($p,$d);
        $this->page($p,'ISIAN KUALIFIKASI - IDENTITAS DAN LEGALITAS');
        $this->heading($p,'A. Data Administrasi');$this->kv($p,['Nama Badan Usaha'=>$v['nama_perusahaan'],'Status Badan Usaha'=>$pr['status']??'','Alamat Kantor'=>$v['alamat'],'Telepon / Faks'=>trim(($pr['telepon']??'').' / '.($pr['faks']??''),' /'),'E-mail'=>$v['email'],'Alamat Kantor Pusat'=>$v['alamat'],'Alamat Kantor Cabang'=>$pr['alamat_cabang']??'','Bukti penguasaan tempat usaha'=>$pr['bukti_kantor']??'']);
        $this->heading($p,'B. Landasan Hukum Pendirian Badan Usaha');$this->kv($p,['Nomor Akta Pendirian'=>$v['no_akta_pendirian'],'Tanggal Akta Pendirian'=>$v['tgl_akta_pendirian'],'Nama Notaris'=>$v['nama_notaris_pendirian'],'Nomor pengesahan / pendaftaran'=>$pr['pengesahan']??'','Nomor Akta Perubahan Terakhir'=>$v['no_akta_perubahan'],'Tanggal Akta Perubahan'=>$v['tgl_akta_perubahan'],'Nama Notaris Perubahan'=>$v['nama_notaris_perubahan'],'Bukti pengesahan / perubahan'=>$pr['bukti_perubahan']??'','Dasar kuasa / akta cabang / KSO'=>$pr['dasar_kuasa']??'']);
        $this->page($p,'ISIAN KUALIFIKASI - PENGURUS DAN PERIZINAN');
        $this->heading($p,'C. Pengurus Badan Usaha');$this->jsonTable($p,'pengurus',$q,$d['master_vendor']);
        $this->heading($p,'Komisaris (untuk Perseroan Terbatas)');$this->jsonTable($p,'komisaris',$q);
        $this->heading($p,'D. Perizinan Berusaha');preg_match('/\bNIB\s*:?\s*(\d{10,20})/i',(string)($v['keterangan']??''),$nib);
        $this->jsonTable($p,'izin',$q);
        if($construction){$this->heading($p,'E. Sertifikat Badan Usaha dan Sertifikat Standar');$this->jsonTable($p,'sbu',$q);}
        else{$this->heading($p,'E. Izin Lainnya (apabila dipersyaratkan)');$this->jsonTable($p,'izin_lain',$q);}
        $this->page($p,'ISIAN KUALIFIKASI - KEPEMILIKAN DAN PERPAJAKAN');
        $this->heading($p,'F. Susunan Pemegang Saham / Pesero');$this->jsonTable($p,'pemilik',$q);
        $this->heading($p,'G. Pemilik Manfaat (Beneficial Owner)');$this->jsonTable($p,'pemilik_manfaat',$q);
        
        $this->heading($p,'H. Kepatuhan Perpajakan');$this->kv($p,['NPWP'=>$v['npwp'],'Status kepatuhan / hasil KSWP'=>$pr['status_pajak']??'','Tanggal / sumber konfirmasi'=>trim(($pr['tanggal_pajak']??'').' / '.($pr['sumber_pajak']??''),' /')]);
        $this->page($p,$consult?'I. DATA TENAGA AHLI DAN PERSONEL':'I. DATA PERSONEL', 'L');
        $this->para($p,$consult?'Diisi tenaga ahli/manajerial sesuai persyaratan KAK dan dokumen pemilihan, termasuk status tenaga ahli tetap apabila disyaratkan.':'Bagian ini dilengkapi apabila personel dipersyaratkan; untuk konstruksi mengikuti dokumen teknis paket.');
        $this->jsonTable($p,'personel',$q);
        if($consult){$this->heading($p,'Riwayat Pengalaman Tenaga Ahli (tambahkan lembar sesuai kebutuhan)');$this->jsonTable($p,'pengalaman_personel',$q);}
        $this->page($p,'J. DATA FASILITAS / PERALATAN / PERLENGKAPAN','L');$this->para($p,'Diisi apabila diperlukan/dipersyaratkan. Bukti kepemilikan atau penguasaan sesuai dokumen paket (milik sendiri/sewa/dukungan sewa).');
        $this->jsonTable($p,'peralatan',$q);
        $this->page($p,'K. DATA PENGALAMAN BADAN USAHA','L');
        $this->para($p,$construction?($consult?'Pengalaman jasa konsultansi konstruksi: pekerjaan 4 tahun terakhir dan pengalaman sejenis 10 tahun terakhir sesuai dokumen pemilihan.':'Pengalaman konstruksi 4 tahun terakhir; untuk kemampuan dasar usaha menengah/besar, pengalaman sejenis 15 tahun terakhir sesuai dokumen pemilihan.'):'Pengalaman bidang/divisi 1 tahun terakhir, kelompok/pekerjaan sejenis 3 tahun terakhir, dan nilai pekerjaan sejenis tertinggi 10 tahun terakhir sesuai dokumen pemilihan. Perhatikan pengecualian bagi pelaku usaha baru sesuai ketentuan.');
        $this->jsonTable($p,'pengalaman',$q);
        $this->page($p,'L. PEKERJAAN YANG SEDANG DILAKSANAKAN','L');$this->jsonTable($p,'pekerjaan_berjalan',$q);
        if($type==='konstruksi'){$this->heading($p,'Data Pendukung Sisa Kemampuan Paket (SKP)');$this->kv($p,['Kualifikasi usaha'=>$pr['kualifikasi_usaha']??'','P (paket yang sedang dikerjakan)'=>count($q['pekerjaan_berjalan']??[])?:'','N (paket bersamaan terbanyak 5 tahun terakhir)'=>$pr['n_paket']??'','KP (kemampuan paket sesuai ketentuan)'=>$pr['kp']??'','SKP = KP - P'=>$pr['skp']??'']);}
        if($type==='konstruksi'&&$d['method']==='tender'){$this->heading($p,'Kemampuan Dasar (hanya untuk usaha menengah/besar apabila dipersyaratkan)');$this->kv($p,['NPt (nilai pengalaman tertinggi relevan)'=>$pr['npt']??'','KD = 3 x NPt'=>$pr['kd']??'','Paket / Subklasifikasi acuan'=>$pr['acuan_kd']??'']);}
        $this->page($p,'PENGESAHAN ISIAN KUALIFIKASI');$this->para($p,'Demikian formulir isian kualifikasi ini saya buat dengan sebenarnya dan penuh rasa tanggung jawab. Seluruh isian dan dokumen pendukung merupakan tanggung jawab badan usaha serta penandatangan yang berwenang.');$this->signature($p,$d);
        $pages=$p->getNumPages();$p->SetAutoPageBreak(false);
        for($n=1;$n<=$pages;$n++){$p->setPage($n);$p->SetAutoPageBreak(false);$p->SetFont('helvetica','',8);$p->SetXY(15,$p->getPageHeight()-11);$p->Cell($p->getPageWidth()-30,5,'Isian Kualifikasi - '.self::TYPES[$type].' | '.$n.' / '.$pages,0,0,'R');}
        return $p->Output('','S');
    }
    private function jsonTable(TCPDF $p,string $kind,array $data,array $master=[]):void
    {
        if($kind==='pengurus'&&!empty($master['direktur'])){$extra=array_filter($data[$kind]??[],fn($r)=>trim($r['nama']??'')!==trim($master['direktur']));$data[$kind]=array_merge([['nama'=>$master['direktur'],'identitas'=>$master['no_ktp']??'','jabatan'=>$master['jabatan']??'']],array_values($extra));}
        $schema=RekananQualificationData::sections()[$kind]['fields'];$heads=array_merge(['No'],array_column($schema,'label'));$rows=[];
        foreach($data[$kind]??[] as $i=>$row){$cells=[$i+1];foreach($schema as $key=>$field)$cells[]=$row[$key]??'';$rows[]=$cells;}
        $this->table($p,$heads,$rows,$rows?1:3);
    }
    public function declarations(array $d,array $pr):array
    {
        return ['integrity'=>['tidak akan melakukan praktik korupsi, kolusi, dan/atau nepotisme;','tidak akan menyampaikan dokumen atau informasi tidak benar untuk memenuhi persyaratan dalam dokumen pemilihan/kompetisi;','tidak meminjamkan atau menggunakan nama perusahaan pihak lain (pinjam bendera perusahaan), atau mengalihkan seluruh pekerjaan dan tanggung jawab kepada pihak lain;','akan mengikuti proses pengadaan secara bersih, transparan dan profesional untuk memberikan hasil kerja terbaik sesuai ketentuan peraturan perundang-undangan;','apabila melanggar pernyataan di atas, bersedia menerima sanksi sesuai peraturan perundang-undangan.'],'statements'=>['saya secara hukum berwenang bertindak untuk dan atas nama badan usaha berdasarkan '.($d['signer']['dasar_kewenangan']?:($pr['dasar_kuasa']??'........................................................ (nomor dan tanggal akta/anggaran dasar/surat kuasa/perjanjian KSO)')).';','saya dan manajemen badan usaha tidak pailit berdasarkan putusan berkekuatan hukum tetap (inkracht), dan kegiatan usaha tidak sedang dihentikan;','badan usaha dan/atau pengurus badan usaha tidak sedang dikenakan sanksi daftar hitam;','saya yang bertindak untuk dan atas nama badan usaha tidak sedang menjalani sanksi pidana (inkracht);','keikutsertaan badan usaha tidak menimbulkan pertentangan kepentingan;','pimpinan dan pengurus badan usaha bukan pegawai Aparatur Sipil Negara;','data kualifikasi dan dokumen penawaran yang disampaikan benar. Apabila di kemudian hari ditemukan ketidakbenaran atau pemalsuan, peserta bertanggung jawab penuh dan bersedia dikenai sanksi sesuai ketentuan pembinaan pelaku usaha pengadaan barang/jasa pemerintah.']];
    }
    private function identity(TCPDF $p,array $v):void{$this->kv($p,['Nama'=>$v['direktur'],'No. Identitas'=>$v['no_ktp'],'Jabatan'=>$v['jabatan'],'Bertindak untuk dan atas nama'=>$v['nama_perusahaan'],'Alamat'=>$v['alamat'],'E-mail'=>$v['email']]);}
}
