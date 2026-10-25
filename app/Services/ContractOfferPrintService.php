<?php
require_once __DIR__.'/KontrakRealisasiService.php';
require_once __DIR__.'/FieldValidationException.php';
/** Native TCPDF supplier offer letter and offer RAB, scoped to one contract. */
final class ContractOfferPrintService
{
    private DB $db;private array $user;
    public function __construct(array $user){$this->db=DB::getInstance();$this->user=Auth::scopedUser()?:$user;}
    private function data(int $id):array
    {
        $d=(new KontrakRealisasiService($this->user))->delivery($id);
        $d['contract']['dokumen_referensi']=[];
        foreach($this->db->query('SELECT kode_dokumen,nomor_dokumen,tanggal_dokumen FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0 ORDER BY id',[$id])->fetchAll() as $ref)$d['contract']['dokumen_referensi'][$ref['kode_dokumen']]=$ref;
        // A newly entered RAB has no separate offer snapshot yet. Use its saved
        // amounts; existing offer snapshots remain the source for offer printing.
        foreach($d['rab'] as &$r)if($r['type']!=='KETERANGAN'&&$r['jumlah_penawaran']===null){$r['vol_penawaran']=$r['vol_negoisasi'];$r['harga_sat_penawaran']=$r['harga_sat_negoisasi'];$r['jumlah_penawaran']=$r['jumlah_negoisasi'];}
        unset($r);return $d;
    }
    private function raw(int $id):array{$r=$this->db->query('SELECT settings_json FROM kontrak_print_settings_neo WHERE kontrak_id=?',[$id])->fetch();return $r?(json_decode($r['settings_json'],true,512,JSON_THROW_ON_ERROR)?:[]):[];}
    private function settings(int $id,array $c):array
    {
        $p=$this->raw($id)['offer_print']??[];$process=ProcurementProcessService::values($c);
        return array_replace(['nomor_surat'=>'SPSE','tanggal_surat'=>($process['UPLOAD_PENAWARAN']['tanggal']??'')?:($c['tanggal_kontrak']??''),'masa_berlaku'=>20,'kop_aktif'=>false,'kop_path'=>'','kop_filename'=>''],is_array($p)?$p:[]);
    }
    public function get(int $id):array{$d=$this->data($id);$p=$this->settings($id,$d['contract']);unset($p['kop_path']);return $p;}
    public function save(int $id,array $input,array $file=[]):array
    {
        $d=$this->data($id);ContractAccessService::assert($this->user,$d['contract'],'dapat_input');if(in_array($this->user['type_user']??'viewer',['tapd','viewer','user'],true))throw new RuntimeException('Role ini hanya dapat membaca dan mencetak penawaran.');
        $p=$this->settings($id,$d['contract']);$number=trim((string)($input['nomor_surat']??''));$date=trim((string)($input['tanggal_surat']??''));$days=filter_var($input['masa_berlaku']??20,FILTER_VALIDATE_INT);
        if($number===''||mb_strlen($number)>255)throw new FieldValidationException('nomor_surat','Isi nomor surat penawaran, maksimal 255 karakter.');
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)))throw new FieldValidationException('tanggal_surat','Tanggal surat penawaran tidak valid.');
        if($days===false||$days<1||$days>365)throw new FieldValidationException('masa_berlaku','Masa berlaku penawaran harus 1–365 hari.');
        $p=array_replace($p,['nomor_surat'=>$number,'tanggal_surat'=>$date,'masa_berlaku'=>$days,'kop_aktif'=>!empty($input['kop_aktif'])]);$newFile=null;$oldPath=$p['kop_path'];
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            if(($file['error']??-1)!==UPLOAD_ERR_OK)throw new FieldValidationException('kop_file','File kop gagal diunggah.');
            if(($file['size']??0)>3*1024*1024)throw new FieldValidationException('kop_file','Ukuran kop maksimal 3 MB.');
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$types=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
            if(!isset($types[$mime])||!getimagesize($file['tmp_name']))throw new FieldValidationException('kop_file','Kop harus berupa gambar PNG, JPG, atau WebP.');
            $scope=preg_replace('/[^A-Za-z0-9._-]/','_',$d['contract']['kd_wilayah'].'-'.$d['contract']['kd_opd']);$dir='storage/uploads/'.$scope.'/'.$d['contract']['tahun'].'/kontrak/'.$id.'/kop-penawaran';$absolute=dirname(__DIR__,2).'/'.$dir;
            if(!is_dir($absolute)&&!mkdir($absolute,0775,true))throw new RuntimeException('Folder kop tidak dapat dibuat.');$newFile=$dir.'/'.bin2hex(random_bytes(16)).'.'.$types[$mime];
            if(!move_uploaded_file($file['tmp_name'],dirname(__DIR__,2).'/'.$newFile))throw new RuntimeException('File kop gagal disimpan.');$p['kop_path']=$newFile;$p['kop_filename']=basename($file['name']);
        }
        if($p['kop_aktif']&&empty($p['kop_path']))throw new FieldValidationException('kop_file','Pilih gambar kop rekanan atau nonaktifkan kop.');
        try{$raw=$this->raw($id);$raw['offer_print']=$p;$this->db->query('INSERT INTO kontrak_print_settings_neo(kontrak_id,settings_json,updated_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE settings_json=VALUES(settings_json),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP',[$id,json_encode($raw,JSON_UNESCAPED_UNICODE),$this->user['username']??'system']);}catch(Throwable $e){if($newFile)unlink(dirname(__DIR__,2).'/'.$newFile);throw $e;}
        if($newFile&&$oldPath&&$oldPath!==$newFile){$old=realpath(dirname(__DIR__,2).'/'.$oldPath);$dir=realpath(dirname(__DIR__,2).'/'.dirname($newFile));if($old&&$dir&&str_starts_with($old,$dir.DIRECTORY_SEPARATOR))unlink($old);}
        unset($p['kop_path']);return$p;
    }
    public function pdf(int $id,string $kind='bundle'):string
    {
        if(!in_array($kind,['bundle','letter','rab'],true))throw new InvalidArgumentException('Jenis cetak penawaran tidak valid.');
        $d=$this->data($id);$c=$d['contract'];$rows=$d['rab'];$total=round(array_sum(array_map(fn($r)=>$r['type']==='KETERANGAN'?0:(float)$r['jumlah_penawaran'],$rows)),2);
        if($total<=0)throw new RuntimeException('RAB penawaran belum terisi. Lengkapi RAB penawaran sebelum mencetak.');
        $vendor=$this->db->query('SELECT nama_perusahaan,direktur,jabatan FROM rekanan_neo WHERE id=? AND is_deleted=0',[(int)($c['rekanan_id']??0)])->fetch();if(!$vendor)throw new RuntimeException('Pilih perusahaan dan lengkapi identitas penyedia sebelum mencetak surat penawaran.');
        $p=$this->settings($id,$c);if(empty($p['tanggal_surat']))throw new RuntimeException('Lengkapi tanggal surat penawaran terlebih dahulu.');
        $setup=array_merge(PageSetupService::current($this->user),ContractPrintSettingsService::get($id));$pdf=PageSetupService::createPdf($setup,$kind==='rab'?'L':'P');PageSetupService::applyPdf($pdf,$setup);ProcurementDocumentFormatService::typography($pdf,$setup);$pdf->SetCreator('seSendok');$pdf->SetTitle('Penawaran - '.$c['uraian_kontrak']);
        if($kind!=='rab'){$this->page($pdf,$setup,$p,'P');$this->letter($pdf,$c,$vendor,$p,$total);}
        if($kind!=='letter'){$this->page($pdf,$setup,$p,'L');$this->rab($pdf,$c,$vendor,$rows,$total);}
        return$pdf->Output('','S');
    }
    private function page(TCPDF $pdf,array $setup,array $p,string $orientation):void
    {
        $pdf->AddPage(PageSetupService::orientation($setup,$orientation));
        if(!empty($p['kop_aktif'])){$root=realpath(dirname(__DIR__,2).'/storage/uploads');$file=realpath(dirname(__DIR__,2).'/'.$p['kop_path']);if(!$root||!$file||!str_starts_with($file,$root.DIRECTORY_SEPARATOR))throw new RuntimeException('File kop rekanan tidak ditemukan. Unggah ulang atau nonaktifkan kop.');$size=getimagesize($file);$m=$pdf->getMargins();$width=$pdf->getPageWidth()-$m['left']-$m['right'];$width=min($width,45*$size[0]/$size[1]);$height=$width*$size[1]/$size[0];$pdf->Image($file,$m['left'],$pdf->GetY(),$width,$height);$pdf->SetY($pdf->GetY()+$height+5);}
    }
    private function letter(TCPDF $pdf,array $c,array $v,array $p,float $total):void
    {
        $e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');$opd=ProcurementDocumentFormatService::bodyOpdName($this->user['nama_opd']??$this->user['nama_org']??'');if(!$opd){$o=$this->db->query('SELECT uraian FROM organisasi_neo WHERE kode=? AND kd_wilayah=? AND is_deleted=0 LIMIT 1',[$c['kd_opd'],$c['kd_wilayah']])->fetch();$opd=ProcurementDocumentFormatService::bodyOpdName($o['uraian']??'');}
        $place=$this->place($c);$flow=ProcurementProcessService::flow($c);$recipient=$flow==='LANGSUNG'?'Pejabat Pengadaan':($flow==='TENDER'?'Pokja Pemilihan':'Pejabat Pembuat Komitmen');
        $html='<table cellpadding="2"><tr><td width="53%"><table><tr><td width="24%">Nomor</td><td width="4%">:</td><td width="72%">'.$e($p['nomor_surat']).'</td></tr><tr><td width="24%">Lampiran</td><td width="4%">:</td><td width="72%">1 (satu) rangkap penawaran</td></tr><tr><td width="24%">Perihal</td><td width="4%">:</td><td width="72%">Penawaran Pekerjaan: '.$e($c['uraian_kontrak']).'</td></tr></table></td><td width="47%">'.$e($place).', '.$e($this->date($p['tanggal_surat'])).'<br>Kepada Yth.<br>'.$e($recipient).' pada '.$e($opd).'<br>TA. '.$e($c['tahun']).'<br><br>di- '.$e($place).'</td></tr></table><br>';
        $process=ProcurementProcessService::values($c);$inv=$process['UNDANGAN_PL']??[];$reference=(!empty($inv['nomor'])&&!empty($inv['tanggal']))?'Sehubungan dengan Undangan Pengadaan Langsung Nomor '.$e($inv['nomor']).' tanggal '.$e($this->date($inv['tanggal'])).', dan setelah kami pelajari dengan saksama Dokumen Pengadaan, ':'Setelah kami pelajari dengan saksama Dokumen Pengadaan, ';
        $days=(int)($c['waktu_pelaksanaan']??0);if(!$days&&!empty($c['tanggal_mulai'])&&!empty($c['tanggal_selesai']))$days=(int)(new DateTimeImmutable($c['tanggal_mulai']))->diff(new DateTimeImmutable($c['tanggal_selesai']))->days+1;
        $texts=[$reference.'dengan ini kami mengajukan penawaran untuk pekerjaan '.$e($c['uraian_kontrak']).' sebesar Rp'.number_format($total,2,',','.').' ('.$e(ucwords($this->words((int)round($total)))).' Rupiah).','Penawaran ini sudah memperhatikan ketentuan dan persyaratan yang tercantum dalam Dokumen Pengadaan untuk melaksanakan pekerjaan tersebut di atas.','Kami akan melaksanakan pekerjaan tersebut dengan jangka waktu pelaksanaan pekerjaan selama '.$days.' ('.$e(ucwords($this->words($days))).') hari kalender.','Penawaran ini berlaku selama '.$p['masa_berlaku'].' ('.$e($this->words((int)$p['masa_berlaku'])).') hari kalender sejak tanggal surat penawaran ini.','Surat penawaran beserta lampirannya kami sampaikan sebanyak 1 (satu) rangkap dokumen asli.','Dengan disampaikannya surat penawaran ini, kami menyatakan sanggup dan akan tunduk pada semua ketentuan yang tercantum dalam Dokumen Pengadaan.'];foreach($texts as $text)$html.='<p align="justify" style="text-indent:10mm;">'.$text.'</p>';
        $html.='<table cellpadding="2"><tr nobr="true"><td width="50%"></td><td width="50%" align="left"><b>'.$e($v['nama_perusahaan']).'</b><br><br><br><br><b><u>'.$e($v['direktur']).'</u></b><br>'.$e($v['jabatan']?:'Direktur').'</td></tr></table>';$pdf->writeHTML($html,true,false,true,false,'');
    }
    private function rab(TCPDF $pdf,array $c,array $v,array $rows,float $total):void
    {
        $e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');$money=fn($n)=>number_format((float)$n,2,',','.');
        $html='<h3 align="center">Rincian Penawaran</h3><table border="1" cellpadding="5"><tr><td width="30%"><b>Kode PL / RUP</b></td><td width="70%">'.$e($c['nomor_rup']).'</td></tr><tr><td width="30%"><b>Nama Paket</b></td><td width="70%">'.$e($c['uraian_kontrak']).'</td></tr><tr><td width="30%"><b>Nama Peserta</b></td><td width="70%">'.$e($v['nama_perusahaan']).'</td></tr></table><br><br><table border="1" cellpadding="4"><thead><tr style="font-weight:bold"><th width="27%">Jenis Barang/Jasa</th><th width="7%">Satuan Unit</th><th width="8%">Volume</th><th width="13%">Harga Satuan</th><th width="13%">Total sebelum Pajak</th><th width="7%">Pajak (%)</th><th width="15%">Total setelah Pajak</th><th width="10%">Keterangan</th></tr></thead><tbody>';
        foreach($rows as $r){if($r['type']==='KETERANGAN'){$html.='<tr nobr="true"><td width="100%" colspan="8"><b>'.$e($r['uraian']).'</b></td></tr>';continue;}$net=(float)$r['vol_penawaran']*(float)$r['harga_sat_penawaran'];$html.='<tr nobr="true"><td width="27%">'.$e($r['uraian']).'</td><td width="7%" align="center">'.$e($r['satuan']).'</td><td width="8%" align="right">'.$money($r['vol_penawaran']).'</td><td width="13%" align="right">'.$money($r['harga_sat_penawaran']).'</td><td width="13%" align="right">'.$money($net).'</td><td width="7%" align="center">'.$money($r['pajak']).'</td><td width="15%" align="right">'.$money($r['jumlah_penawaran']).'</td><td width="10%"></td></tr>';}
        $html.='<tr nobr="true" style="font-weight:bold"><td width="75%" colspan="6">Total Penawaran</td><td width="15%" align="right">'.$money($total).'</td><td width="10%"></td></tr></tbody></table>';$pdf->writeHTML($html,true,false,true,false,'');
    }
    private function place(array $c):string{$r=$this->db->query('SELECT uraian FROM wilayah_neo WHERE kode=? AND is_deleted=0 LIMIT 1',[$c['kd_wilayah']])->fetch();return mb_convert_case(trim(preg_replace('/^(Kabupaten|Kota|Kab\.)\s+/iu','',$r['uraian']??'')),MB_CASE_TITLE,'UTF-8')?:'—';}
    private function date(string $d):string{$months=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];$t=strtotime($d);return $t?date('j',$t).' '.$months[(int)date('n',$t)].' '.date('Y',$t):'';}
    private function words(int $n):string{$a=['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];if($n<12)return $a[max(0,$n)];if($n<20)return$this->words($n-10).' belas';if($n<100)return trim($this->words(intdiv($n,10)).' puluh '.($n%10?$this->words($n%10):''));if($n<200)return trim('seratus '.($n%100?$this->words($n%100):''));if($n<1000)return trim($this->words(intdiv($n,100)).' ratus '.($n%100?$this->words($n%100):''));if($n<2000)return trim('seribu '.($n%1000?$this->words($n%1000):''));foreach([1000000000000=>'triliun',1000000000=>'miliar',1000000=>'juta',1000=>'ribu'] as $u=>$label)if($n>=$u)return trim($this->words(intdiv($n,$u)).' '.$label.' '.($n%$u?$this->words($n%$u):''));return'';}
}
