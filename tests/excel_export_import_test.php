<?php
ob_start();
require_once __DIR__.'/../app/Services/DynamicTableService.php';
require_once __DIR__.'/../app/Services/KontrakRealisasiService.php';
require_once __DIR__.'/../app/Services/StandarHargaService.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
function excelCheck($condition,$message){if(!$condition)throw new RuntimeException($message);echo "PASS: $message\n";}
$db=DB::getInstance();excelCheck(str_starts_with($db->query('SELECT DATABASE() d')->fetch()['d'],'qa_'),'Database QA terpisah');
$_SESSION=['user'=>['id'=>1,'username'=>'EXCEL_TEST','type_user'=>'admin_opd','kd_wilayah'=>'76.01','kd_opd'=>'1.03.0.00.0.00.01.0000','tahun'=>2026],'scope_kd_opd'=>'1.03.0.00.0.00.01.0000'];
$token=bin2hex(random_bytes(5));$files=[];
function saveTestExcel($book){global $files;$p=tempnam(sys_get_temp_dir(),'xlsx_test_').'.xlsx';(new Xlsx($book))->save($p);$files[]=$p;return$p;}
try{
 $b=new Spreadsheet();$s=$b->getActiveSheet();$s->fromArray(['NO','URAIAN','SATUAN','HARGA SATUAN'],null,'A1');$s->fromArray(['001','Pekerjaan dengan uraian yang panjang untuk pengujian lebar kolom','LS',1234567890.25],null,'A2');ExcelPresentationService::apply($b,$_SESSION['user'],'RAB & Rekap');
 excelCheck($s->getColumnDimension('B')->getWidth()>$s->getColumnDimension('A')->getWidth(),'Uraian lebih lebar daripada nomor');
 excelCheck(str_contains($s->getHeaderFooter()->getOddHeader(),'RAB && Rekap')&&str_contains($s->getHeaderFooter()->getOddFooter(),'&P'),'Header dan footer cetak serta nomor halaman');
 $textBook=new Spreadsheet();$textSheet=$textBook->getActiveSheet();$textSheet->setCellValue('A1','NPWP')->setCellValueExplicit('A2','001234567890123456',\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);ExcelPresentationService::apply($textBook,$_SESSION['user']);
 $textFile=saveTestExcel($textBook);$textSaved=IOFactory::load($textFile)->getActiveSheet()->getCell('A2');excelCheck($textSaved->getDataType()==='s'&&$textSaved->getValue()==='001234567890123456'&&$textSaved->getStyle()->getNumberFormat()->getFormatCode()==='@','NPWP tetap teks utuh dengan nol awal dan lebih dari 15 digit');
 $db->begin();
 try{
  $b=new Spreadsheet();$s=$b->getActiveSheet();$s->setCellValue('A1','NAMA_PERUSAHAAN')->setCellValue('C1','NPWP')->setCellValue('D1','KD_WILAYAH')->setCellValue('E1','ALAMAT')->setCellValue('F1','DIREKTUR');
  $s->setCellValue('A2','Uji Excel '.$token)->setCellValue('C2','0099'.$token)->setCellValue('D2','WILAYAH-LAIN');
  $s->setCellValue('A4','Uji Excel Kedua '.$token)->setCellValue('C4','0088'.$token);
  foreach([2,4] as$r)$s->setCellValue('E'.$r,'Alamat pengujian')->setCellValue('F'.$r,'Direktur Uji');
  $service=new DynamicTableService();$response=json_decode($service->importStrict('rekanan',saveTestExcel($b)),true);
  excelCheck($response['meta']['berhasil']===2&&$response['meta']['total']===2,'Header kosong tidak menggeser kolom dan baris kosong dilewati');
  $rows=$db->query('SELECT nama_perusahaan,npwp,kd_wilayah FROM rekanan_neo WHERE nama_perusahaan LIKE ?',['%'.$token])->fetchAll();
  excelCheck(count($rows)===2&&$rows[0]['kd_wilayah']===$_SESSION['user']['kd_wilayah']&&str_starts_with($rows[0]['npwp'],'00'),'Identifier nol awal dan scope wilayah dipertahankan');
  $asn=$db->query('SELECT * FROM db_asn_pemda_neo WHERE kd_wilayah=? AND kd_opd=? AND is_deleted=0 LIMIT 1',['76.01',$_SESSION['user']['kd_opd']])->fetch();
  unset($asn['id']);$asn['nip']='99'.random_int(1000000000000000,9999999999999999);$asn['nama']='Tanggal Excel '.$token;$asn['npwp']='00'.$token;
  $dateField=array_key_exists('tanggal_lahir',$asn)?'tanggal_lahir':(array_key_exists('tgl_lahir',$asn)?'tgl_lahir':null);
  if($dateField){$asn[$dateField]=45000;$b=new Spreadsheet();$b->getActiveSheet()->fromArray(array_keys($asn),null,'A1')->fromArray(array_values($asn),null,'A2');$r=json_decode($service->importStrict('asn',saveTestExcel($b)),true);excelCheck($r['meta']['berhasil']===1,'Tanggal serial Excel diimpor sebagai tanggal ASN');}
  $b=new Spreadsheet();$b->getActiveSheet()->fromArray(['NAMA_PERUSAHAAN','NAMA_PERUSAHAAN'],null,'A1');try{$service->importStrict('rekanan',saveTestExcel($b));throw new Exception('Duplicate header accepted');}catch(InvalidArgumentException $e){excelCheck(str_contains($e->getMessage(),'lebih dari satu'),'Header ganda ditolak dengan pesan jelas');}
  $b=new Spreadsheet();$s=$b->getActiveSheet();$s->fromArray(['NAMA_PERUSAHAAN','ALAMAT','DIREKTUR','NPWP'],null,'A1');$s->fromArray(['Baris invalid '.$token,'Alamat','','98'.$token],null,'A2');$s->fromArray(['Baris valid '.$token,'Alamat','Direktur','97'.$token],null,'A3');
  $partial=json_decode($service->importStrict('rekanan',saveTestExcel($b)),true);excelCheck($partial['meta']['total']===2&&$partial['meta']['berhasil']===1&&$partial['meta']['gagal']===1&&$partial['data'][0]['rows']===['2'],'Impor parsial menunjukkan nomor baris gagal dan melanjutkan baris valid');
  $b=new Spreadsheet();$s=$b->getActiveSheet();$s->fromArray(['NAMA_PERUSAHAAN','ALAMAT','DIREKTUR','NPWP'],null,'A1');$s->fromArray(['NPWP angka '.$token,'Alamat','Direktur',1234567890123456],null,'A2');$s->setCellValueExplicit('D2',1234567890123456,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);$badNpwp=json_decode($service->importStrict('rekanan',saveTestExcel($b)),true);excelCheck(!$badNpwp['success']&&str_contains($badNpwp['data'][0]['message'],'NPWP wajib bertipe teks'),'NPWP numerik ditolak dengan petunjuk perbaikan');
  $realizationService=new KontrakRealisasiService($_SESSION['user']);$contractItem=$realizationService->contractItems(850)[0];
  $b=new Spreadsheet();$s=$b->getActiveSheet();$s->fromArray(['KONTRAK_ID','TANGGAL','URAIAN_TRANSAKSI','DOK','ID_DOK_ANGGARAN','JUMLAH','PROGRESS_FISIK'],null,'A1');$s->fromArray([850,'2026-10-26','Uji impor realisasi '.$token,$contractItem['tahap'],$contractItem['anggaran_id'],1,1],null,'A2',true);
  $realizationImport=json_decode($service->importStrict('realisasi',saveTestExcel($b)),true);excelCheck(($realizationImport['meta']['berhasil']??0)===1,'Impor realisasi memakai layanan transaksi kontrak');
  $s->setCellValue('F2',100000000000000);$excessImport=json_decode($service->importStrict('realisasi',saveTestExcel($b)),true);excelCheck(!$excessImport['success']&&str_contains($excessImport['data'][0]['message'],'melebihi nilai kontrak'),'Impor realisasi melebihi kontrak ditolak');
  $sipdUser=$_SESSION['user'];$sipdUser['type_user']='tapd';$sipd=new StandarHargaService($sipdUser);
  foreach(['ssh','hspk','asb','sbu']as$type){
    $b=new Spreadsheet();$s=$b->getActiveSheet();$s->fromArray(['Kode Kelompok Barang','Uraian Kelompok Barang','ID Standar Harga','Kode Barang','Uraian Barang','Spesifikasi','Satuan','Harga Satuan','Kode Rekening'],null,'A1');
    $s->fromArray(['1.3','Kelompok uji',$token,'SIPD-'.$type.'-'.$token,'Barang uji '.$token,'Spesifikasi','Buah','1.234,56','5.1.02.01.01.0001'],null,'A2');
    $r=$sipd->importSipd($type,saveTestExcel($b),2026);excelCheck($r['inserted']===1,'Impor SIPD '.$type.' dan angka desimal Indonesia');
    $s->setCellValue('H2',1250.5);$r=$sipd->importSipd($type,saveTestExcel($b),2026);excelCheck($r['updated']===1,'Impor ulang SIPD '.$type.' memperbarui data yang sama');
  }
  $_SESSION['user']['type_user']='viewer';try{(new DynamicTableService())->importStrict('rekanan',saveTestExcel($b));throw new Exception('Viewer accepted');}catch(Throwable $e){excelCheck(!str_contains($e->getMessage(),'Viewer accepted'),'Role viewer tidak dapat melakukan impor');}
 }finally{$db->rollback();$_SESSION['user']['type_user']='admin_opd';}
 $id=850;$service=new KontrakRealisasiService($_SESSION['user']);$before=$service->delivery($id);$file=$service->rabExcel($id);$files[]=$file;
 $book=IOFactory::load($file);excelCheck($book->getSheetCount()===3&&$book->getSheet(0)->getCell('F1')->getValue()==='PAJAK %','Ekspor RAB membawa pajak, jadwal dan kurva dalam sheet terpisah');
 $result=$service->importRab($id,$file);$after=$service->delivery($id);
 excelCheck(count($before['rab'])===count($after['rab'])&&array_column($before['rab'],'id')===array_column($after['rab'],'id'),'Ekspor-impor RAB mempertahankan item dan ID jadwal');
 excelCheck(abs(array_sum(array_column($before['rab'],'jumlah_negoisasi'))-array_sum(array_column($after['rab'],'jumlah_negoisasi')))<.011,'Ekspor-impor RAB mempertahankan total termasuk pajak');
 excelCheck($before['schedule']===$after['schedule'],'Jadwal tidak hilang saat impor ulang RAB');
 $legacy=new Spreadsheet();$legacySheet=$legacy->getActiveSheet();$legacySheet->fromArray(['NO','URAIAN','SATUAN','VOLUME','HARGA SATUAN','JUMLAH','BOBOT %'],null,'A1');$legacyRow=2;
 foreach($before['rab']as$item)$legacySheet->fromArray([$item['nomor'],$item['uraian'],$item['satuan'],$item['vol_negoisasi'],$item['harga_sat_negoisasi'],$item['jumlah_negoisasi'],$item['bobot']],null,'A'.$legacyRow++,true);
 $service->importRab($id,saveTestExcel($legacy));$legacyAfter=$service->delivery($id);excelCheck(array_column($before['rab'],'pajak')===array_column($legacyAfter['rab'],'pajak'),'Impor RAB lama tanpa kolom pajak mempertahankan pajak yang ada');
 $sheet=$book->getSheet(0);$originalPrice=(float)$sheet->getCell('E2')->getValue();$sheet->setCellValue('E2',$originalPrice+100);\PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($book)->clearCalculationCache();
 $expected=round((float)$sheet->getCell('D2')->getValue()*($originalPrice+100)*(1+(float)$sheet->getCell('F2')->getValue()/100),2);excelCheck(abs((float)$sheet->getCell('G2')->getCalculatedValue()-$expected)<.001,'Jumlah RAB Excel dihitung ulang ketika harga satuan diubah');
 $emptyUser=$_SESSION['user'];$emptyUser['tahun']=2099;$_SESSION['user']=$emptyUser;
 foreach(['spj','lra']as$format){$emptyFile=(new KontrakRealisasiService($emptyUser))->financialExcel($format);$files[]=$emptyFile;$emptySheet=IOFactory::load($emptyFile)->getActiveSheet();$col=$format==='spj'?'H':'D';excelCheck($emptySheet->getCell($col.'5')->getCalculatedValue()===0,'Ekspor '.$format.' kosong tidak membuat formula melingkar');}
 $_SESSION['user']['tahun']=2026;
 foreach($book->getAllSheets()as$s)excelCheck($s->getHeaderFooter()->getOddHeader()!==''&&$s->getHeaderFooter()->getOddFooter()!=='','Header/footer sheet '.$s->getTitle());
}finally{foreach($files as$file)if(is_file($file))unlink($file);}
