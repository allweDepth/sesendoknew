<?php
require_once __DIR__.'/../Core/DB.php';

class AnggaranDocumentService
{
    private DB $db; private array $user;
    private const TABLES=['rkpd'=>'rkpd_neo','renja'=>'renja_neo','rka'=>'rka_neo','dpa'=>'dpa_neo','rkpd_p'=>'rkpd_p_neo','renja_p'=>'renja_p_neo','rka_p'=>'rka_p_neo','dppa'=>'dppa_neo'];
    public function __construct(array $user){$this->db=DB::getInstance();$this->user=$user;}
    private function table(string $logical):string{if(!isset(self::TABLES[$logical]))throw new InvalidArgumentException('Dokumen anggaran tidak valid');return self::TABLES[$logical];}
    private function scope(string $alias='a'):array{$w=$this->user['kd_wilayah']??'';$o=$this->user['kd_opd']??'';$y=(int)($this->user['tahun']??0);if(!$w||!$y)throw new RuntimeException('Scope pengguna tidak lengkap');$sql="$alias.kd_wilayah=? AND $alias.tahun=? AND $alias.is_deleted=0";$p=[$w,$y];if($o&&$o!=='0'){$sql.=" AND $alias.kd_opd=?";$p[]=$o;}return[$sql,$p];}
    public function groups(string $logical):array
    {
        $table=$this->table($logical);[$scope,$params]=$this->scope();$amount=str_starts_with($logical,'rkpd')?'a.pagu':'a.jumlah';$description=str_starts_with($logical,'rkpd')?'MAX(a.indikator)':'MAX(a.uraian)';
        $prefix="CONVERT(a.kd_sub_keg USING utf8mb4) COLLATE utf8mb4_general_ci";
        return $this->db->query("SELECT a.kd_sub_keg,COALESCE(r.uraian,$description) nama_sub_kegiatan,COUNT(*) jumlah_uraian,COALESCE(SUM($amount),0) total,MAX(a.setujui) setujui,MAX(a.kunci) kunci,
          (SELECT CONCAT(x.kode,' ',x.uraian) FROM rekening_kegiatan x WHERE x.level='program' AND $prefix LIKE CONCAT(x.kode,'.%') COLLATE utf8mb4_general_ci ORDER BY CHAR_LENGTH(x.kode) DESC LIMIT 1) program,
          (SELECT CONCAT(x.kode,' ',x.uraian) FROM rekening_kegiatan x WHERE x.level='kegiatan' AND $prefix LIKE CONCAT(x.kode,'.%') COLLATE utf8mb4_general_ci ORDER BY CHAR_LENGTH(x.kode) DESC LIMIT 1) kegiatan,
          (SELECT CONCAT(x.kode,' ',x.uraian) FROM rekening_kegiatan x WHERE x.level='bidang' AND $prefix LIKE CONCAT(x.kode,'.%') COLLATE utf8mb4_general_ci ORDER BY CHAR_LENGTH(x.kode) DESC LIMIT 1) bidang
          FROM `$table` a LEFT JOIN rekening_kegiatan r ON r.kode=$prefix AND r.level='sub_kegiatan' WHERE $scope GROUP BY a.kd_sub_keg,r.uraian ORDER BY a.kd_sub_keg",$params)->fetchAll();
    }
    public function details(string $logical,string $code):array
    {
        $table=$this->table($logical);[$scope,$params]=$this->scope();$params[]=$code;
        if(str_starts_with($logical,'rkpd'))$select='a.id,a.kd_sub_keg,a.indikator AS uraian,a.target AS volume,a.pagu AS jumlah,a.lokasi,a.kelompok_sasaran,a.kunci,a.setujui,a.keterangan';
        else {$columns=array_column($this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field');$awal=in_array('jumlah_awal',$columns,true)?'a.volume_awal,a.harga_satuan_awal,a.jumlah_awal,':'NULL volume_awal,NULL harga_satuan_awal,NULL jumlah_awal,';$select="a.id,a.kd_sub_keg,a.kd_akun,a.kel_rek,a.jenis_kelompok,a.kelompok,a.komponen,a.spesifikasi,a.uraian,a.volume,a.harga_satuan,a.jumlah,$awal a.sumber_dana_id,a.kunci,a.setujui,a.keterangan";}
        return $this->db->query("SELECT $select FROM `$table` a WHERE $scope AND a.kd_sub_keg=? ORDER BY a.id",$params)->fetchAll();
    }
}
