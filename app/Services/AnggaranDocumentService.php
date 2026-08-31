<?php
require_once __DIR__.'/../Core/DB.php';
require_once __DIR__.'/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;use PhpOffice\PhpSpreadsheet\Writer\Xlsx;use PhpOffice\PhpSpreadsheet\Style\Alignment;use PhpOffice\PhpSpreadsheet\Style\Border;

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
    public function exportExcel(string $logical):string
    {
        $groups=$this->groups($logical);$book=new Spreadsheet();$book->removeSheetByIndex(0);$used=[];
        foreach($groups as $index=>$group){$code=(string)$group['kd_sub_keg'];$name=str_replace(['\\','/','?','*','[',']',':'],'-',substr($code,0,25))?:'SubKegiatan-'.($index+1);while(isset($used[$name]))$name=substr($name,0,28).'-'.($index+1);$used[$name]=1;$sheet=$book->createSheet();$sheet->setTitle($name);$details=$this->details($logical,$code);$sheet->mergeCells('A1:J1')->setCellValue('A1',strtoupper(str_replace('_P',' PERUBAHAN',strtoupper($logical))).' — RINCIAN SUB KEGIATAN');$sheet->mergeCells('A2:J2')->setCellValue('A2','Kode: '.$code.' | '.($group['nama_sub_kegiatan']??''));$sheet->mergeCells('A3:J3')->setCellValue('A3','Program: '.($group['program']??'-').' | Kegiatan: '.($group['kegiatan']??'-'));$sheet->fromArray(['NO','KODE AKUN','KELOMPOK','KOMPONEN/URAIAN','SPESIFIKASI','VOLUME','HARGA SATUAN','JUMLAH','STATUS','KETERANGAN'],null,'A5');$row=6;foreach($details as $i=>$d)$sheet->fromArray([$i+1,$d['kd_akun']??'-',implode(' / ',array_filter([$d['kel_rek']??null,$d['jenis_kelompok']??null,$d['kelompok']??null])),$d['komponen']??$d['uraian']??'',$d['spesifikasi']??'',(float)($d['volume']??0),(float)($d['harga_satuan']??0),(float)($d['jumlah']??0),!empty($d['setujui'])?'DISETUJUI':'DRAFT',$d['keterangan']??''],null,'A'.$row++);$sheet->fromArray(['','','','','','','TOTAL',array_sum(array_map(fn($x)=>(float)($x['jumlah']??0),$details))],null,'A'.$row);$sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle('A1:J1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF174A68');$sheet->getStyle('A2:J3')->getFont()->setBold(true);$sheet->getStyle('A5:J5')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');$sheet->getStyle('A5:J5')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF2185D0');$sheet->getStyle("A5:J$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFB5C7D3');$sheet->getStyle("F6:H$row")->getNumberFormat()->setFormatCode('#,##0.00');$sheet->getStyle("A1:J$row")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);$sheet->getStyle("G$row:H$row")->getFont()->setBold(true);$sheet->freezePane('A6');$sheet->setAutoFilter('A5:J'.max(5,$row-1));foreach(['A'=>6,'B'=>18,'C'=>24,'D'=>38,'E'=>32,'F'=>12,'G'=>18,'H'=>18,'I'=>14,'J'=>24] as $col=>$width)$sheet->getColumnDimension($col)->setWidth($width);$sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);}
        if(!$groups){$sheet=$book->createSheet();$sheet->setTitle('Kosong');$sheet->setCellValue('A1','Tidak ada data dalam lingkup pengguna.');}$book->setActiveSheetIndex(0);$tmp=tempnam(sys_get_temp_dir(),'anggaran_').'.xlsx';(new Xlsx($book))->save($tmp);return $tmp;
    }
}
