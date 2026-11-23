<?php
require_once __DIR__.'/ProcurementContractFormService.php';

/** Paired SSUK/SSKK from the user's SE Dirjen Bina Marga 03/SE/Db/2025 reference. */
final class ProcurementMiniCompetitionTermsService
{
    public static function variant(array $c):string
    {
        $v=(string)($c['terms_variant']??$c['print_settings']['terms_variant']??'STANDARD');
        if(!in_array($v,['STANDARD','MINI_KOMPETISI'],true))throw new InvalidArgumentException('Pilihan syarat kontrak tidak valid');
        if($v==='MINI_KOMPETISI'&&!self::eligible($c))throw new InvalidArgumentException('Format mini kompetisi tersedia untuk surat perjanjian atau surat pesanan pekerjaan konstruksi');
        return $v;
    }
    public static function eligible(array $c):bool
    {
        return ($c['cara_pengadaan']??'PENYEDIA')==='PENYEDIA'&&($c['jenis_pengadaan']??'')==='PEKERJAAN_KONSTRUKSI'
            &&in_array(ProcurementContractFormService::recommend($c),['SURAT_PERJANJIAN','SURAT_PESANAN'],true);
    }
    public static function rows(string $code,array $c):array
    {
        if(!in_array($code,['SSUK','SSKK'],true))return [];
        self::variant(array_replace($c,['terms_variant'=>'MINI_KOMPETISI']));
        $file=__DIR__.'/../../resources/procurement/reference/'.strtolower($code).'-mini-kompetisi-bina-marga.json';
        $rows=json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
        return $code==='SSKK'?self::fill($rows,$c):$rows;
    }
    public static function fill(array $rows,array $c):array
    {
        $ppk=$c['nama_ppk_resmi']??$c['nama_ppk']??'{{nama_ppk}}';
        $provider=$c['nama_penyedia_resmi']??$c['nama_penyedia']??'{{nama_penyedia}}';
        $values=[
            '4.1 & 4.2'=>"Alamat Para Pihak:\nPejabat Penandatangan Kontrak: ".($c['nama_opd']??'{{nama_opd}}')."\nNama PPK: {$ppk}\nAlamat: ".($c['alamat_opd']??'................................')."\nE-mail: ................................\n\nPenyedia: {$provider}\nNama: ".($c['direktur']??'................................')."\nAlamat: ".($c['alamat_penyedia']??'................................')."\nE-mail: ".($c['email_pelaksana']??'................................'),
            '4.2 & 5.1'=>"Untuk Pejabat Penandatangan Kontrak: {$ppk}\nUntuk Penyedia: ".($c['direktur']??'................................')."\nKewenangan wakil lain mengikuti penetapan atau kuasa tertulis para pihak.",
            '6'=>($c['lokasi_pekerjaan']??$c['lokasi']??'................................'),
            '28.1'=>'Masa Pelaksanaan selama '.(($c['waktu_pelaksanaan']??'')?:'........').' hari kalender terhitung sejak Tanggal Mulai Kerja yang tercantum dalam SPMK.',
            '34.8'=>'Masa Pemeliharaan selama '.(!empty($c['masa_pemeliharaan_bulan'])?$c['masa_pemeliharaan_bulan'].' bulan':(!empty($c['masa_pemeliharaan'])?$c['masa_pemeliharaan'].' hari kalender':'........')).' terhitung sejak Tanggal Penyerahan Pertama Pekerjaan (PHO).',
        ];
        foreach($rows as &$r){$p=array_map('trim',explode('|',(string)($r['text']??''),3));$key=preg_replace('/\s+/u',' ',$p[0]??'');if(isset($values[$key]))$r['text']=$p[0].' | '.($p[1]??'').' | '.$values[$key];}unset($r);
        return $rows;
    }
}
