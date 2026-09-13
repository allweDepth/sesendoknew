<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$realizationPath = $argv[1] ?? $root . '/2024/Realisasi 2024 Fix.xls';
$rabPath = $argv[2] ?? $root . '/2024/RAb Kontrak 2024.xlsx';
$commit = in_array('--commit', $argv, true);
$replace = in_array('--replace', $argv, true);
$skipInvalidRealizations = in_array('--skip-invalid-realisasi', $argv, true);
foreach ([$realizationPath, $rabPath] as $path) if (!is_file($path)) throw new RuntimeException('File tidak ditemukan: ' . $path);

$config = include $root . '/config/database.php';
$dsn = !empty($config['socket']) ? 'mysql:unix_socket='.$config['socket'].';dbname='.$config['dbname'] : 'mysql:host='.$config['host'].';dbname='.$config['dbname'];
$db = new PDO($dsn, (string)$config['username'], (string)$config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
$wilayah='76.01'; $opd='1.03.0.00.0.00.01.0000'; $year=2024; $actor='IMPORT_KONTRAK_REALISASI_PUPR_2024';
$number = static fn(mixed $value): ?float => is_numeric($value) ? (float)$value : null;
$norm = static fn(string $value): string => mb_strtolower(trim((string)preg_replace('/\s+/u',' ',str_replace("\xc2\xa0",' ',$value))),'UTF-8');
$date = static function(mixed $value): ?string {
    if ($value===null || trim((string)$value)==='') return null;
    if (is_numeric($value) && (float)$value>20000) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
    $value=preg_replace('/\s+/u',' ',trim((string)$value)); $months=['januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12];
    if (preg_match('/(\d{1,2})[ -]+([A-Za-z]+)[ -]+(\d{4})/u',$value,$m) && isset($months[mb_strtolower($m[2],'UTF-8')])) return sprintf('%04d-%02d-%02d',(int)$m[3],$months[mb_strtolower($m[2],'UTF-8')],(int)$m[1]);
    if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/',$value,$m)) return sprintf('%04d-%02d-%02d',(int)$m[3],(int)$m[2],(int)$m[1]);
    return preg_match('/^\d{4}-\d{2}-\d{2}/',$value) ? substr($value,0,10) : null;
};
$insert = static function(PDO $db,string $table,array $row): int { $columns=array_keys($row); $marks=implode(',',array_fill(0,count($columns),'?')); $db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$columns).'`) VALUES ('.$marks.')')->execute(array_values($row)); return (int)$db->lastInsertId(); };
$load = static function(string $path): \PhpOffice\PhpSpreadsheet\Spreadsheet { $reader=\PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path); $reader->setReadDataOnly(true); return $reader->load($path); };

$codeFor = static function(string $sheet,string $package): ?string {
    $text=mb_strtolower($sheet.' '.$package,'UTF-8');
    if (str_contains($text,'penggantian jembatan')) return '1.03.10.2.01.0031';
    if (str_contains($text,'jembatan')) return '1.03.10.2.01.0040';
    if (str_contains($text,'jalan')) return '1.03.10.2.01.0032';
    if (str_contains($text,'drainase')) return '1.03.06.2.01.0012';
    if (str_contains($text,'tebing')) return '1.03.02.2.01.0109';
    if (str_contains($text,'ck fisik') && (str_contains($text,'gedung') || str_contains($text,'masjid') || str_contains($text,'taman'))) return '1.03.09.2.01.0008';
    if (str_contains($text,'limbah') || str_contains($text,'spald')) return '1.03.05.2.01.0029';
    if (str_contains($text,'spam') || str_contains($text,'air minum') || str_contains($text,'distribusi')) return '1.03.03.2.01.0028';
    if (str_contains($text,'rehabilitasi') || str_contains($text,'irigasi')) return '1.03.02.2.02.0014';
    if (str_contains($text,'normalisasi') || str_contains($text,'sungai')) return '1.03.02.2.01.0093';
    if (str_contains($text,'alat berat')) return '1.03.01.2.09.0003';
    if (str_contains($text,'sertifikasi')) return '1.03.11.2.01.0010';
    if (str_contains($text,'pelatihan') || str_contains($text,'kapasitas pegawai')) return '1.03.11.2.01.0012';
    if (str_contains($text,'jasa konstruksi') || str_contains($text,'jakon')) return '1.03.11.2.01.0012';
    if (str_contains($text,'cagar') || str_contains($text,'pariwisata')) return '1.03.09.2.01.0008';
    return null;
};

$contracts=[]; $realizations=[];
$realBook=$load($realizationPath);
foreach ($realBook->getWorksheetIterator() as $sheet) {
    $sheetName = mb_strtolower($sheet->getTitle(), 'UTF-8');
    if (str_contains($sheetName, 'pengawasan') || str_contains($sheetName, 'lembar') || str_contains($sheetName, 'sheet1') || str_contains($sheetName, 'rekap') || str_contains($sheetName, 'statisitk') || str_contains($sheetName, 'cover') || $sheetName === 'ck fisik (3)') continue;
    $current=null; $blocked=false; $sectionCode=null; $maxColumn=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
    for ($row=1;$row<=$sheet->getHighestRow();$row++) {
        for ($sectionColumn=1; $sectionColumn<=min(4,$maxColumn); $sectionColumn++) {
            $sectionValue = (string)$sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($sectionColumn).$row)->getValue();
            if (preg_match('~(1\.03(?:\.\d+){3,5})\s*-?~', $sectionValue, $sectionMatch)) $sectionCode=$sectionMatch[1];
        }
        $contractText=trim((string)$sheet->getCell("F$row")->getValue());
        $match=preg_match('~^\d{3}/(\d{1,15})/~',$contractText,$m)?$m[1]:null;
        if ($match) {$current=null; $blocked=true;}
        $contractValue=$number($sheet->getCell("H$row")->getValue());
        if ($match && $contractValue!==null && $contractValue>0) {
            $package=trim((string)$sheet->getCell("B$row")->getValue()); $code=$codeFor($sheet->getTitle(),$package);
            if (!$code) { $current=null; continue; }
            $candidate=['number'=>$match,'package'=>preg_replace('/^\[\s*-\s*\]\s*/','',$package),'activity'=>$code,'budget'=>$number($sheet->getCell("G$row")->getValue())??0,'contract_value'=>$contractValue,'vendor'=>trim((string)$sheet->getCell("I$row")->getValue()),'date'=>$date($contractText),'sheet'=>$sheet->getTitle(),'row'=>$row];
            if (isset($contracts[$match])) {
                if (abs($contracts[$match]['contract_value']-$contractValue)>0.01) { $current=null; continue; }
            } else $contracts[$match]=$candidate;
            $current=$match; $blocked=false;
        }
        if (!$current && !$match && !$blocked) {
            for ($lookback=$row-1; $lookback>=max(1,$row-30); $lookback--) {
                $previousContractText=trim((string)$sheet->getCell("F$lookback")->getValue());
                if (preg_match('~^\d{3}/(\d{1,15})/~',$previousContractText,$previousMatch) && isset($contracts[$previousMatch[1]])) {
                    $previousValue=$number($sheet->getCell("H$lookback")->getValue());
                    if ($previousValue!==null && abs($previousValue-$contracts[$previousMatch[1]]['contract_value'])<=0.01) {$current=$previousMatch[1];break;}
                }
            }
        }
        if (!$current) continue;
        $sp2d=null; $sp2dColumn=null;
        for($column=1;$column<=$maxColumn;$column++){ $cell=trim((string)$sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$row)->getValue()); if(preg_match('~^76\.01/.+/LS/~',$cell)){ $sp2d=$cell; $sp2dColumn=$column; break; } }
        if ($sp2d===null) continue;
        $amount=null; for($column=$sp2dColumn+1;$column<=$maxColumn;$column++){ $candidateAmount=$number($sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$row)->getValue()); if($candidateAmount!==null&&$candidateAmount>0){$amount=$candidateAmount;break;} }
        $sp2dDate=null; for($column=$sp2dColumn-1;$column>=1;$column--){$candidateDate=$sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$row)->getValue();if($candidateDate!==null&&trim((string)$candidateDate)!==''){ $parsedDate=$date($candidateDate);if($parsedDate){$sp2dDate=$parsedDate;break;} }}
        if($amount!==null&&$amount>0&&$sp2dDate){
            $known = array_column($realizations[$current] ?? [], 'number');
            $alreadyStored = in_array($sp2d, $known, true);
            if (!$alreadyStored) $realizations[$current][]=['number'=>$sp2d,'date'=>$sp2dDate,'amount'=>$amount,'sheet'=>$sheet->getTitle(),'row'=>$row,'description'=>trim((string)$sheet->getCell("B$row")->getValue())];
        }
    }
}

$rabs=[]; $rabBook=$load($rabPath);
foreach($rabBook->getWorksheetIterator() as $sheet){
    if(!preg_match('/^(\d{3,15})/',trim($sheet->getTitle()),$m))continue; $contractNumber=$m[1];
    for($row=1;$row<=$sheet->getHighestRow();$row++){
        $item=trim((string)$sheet->getCell("A$row")->getValue());$description=trim((string)$sheet->getCell("B$row")->getValue());$volume=$number($sheet->getCell("D$row")->getValue());$unit=trim((string)$sheet->getCell("G$row")->getValue());$price=$number($sheet->getCell("J$row")->getValue())??$number($sheet->getCell("I$row")->getValue());
        if($description===''||$volume===null||$volume<=0||$price===null||$unit===''||!preg_match('/^\d+(?:\.\d+)?$/',$item))continue;
        $rabs[$contractNumber][]=['number'=>$item,'description'=>$description,'unit'=>$unit,'volume'=>$volume,'price'=>$price,'amount'=>$volume*$price,'source_row'=>$row];
    }
}

$dpa=[];$dppa=[];
foreach(['dpa_neo'=>&$dpa,'dppa_neo'=>&$dppa] as $table=>&$target){$q=$db->prepare("SELECT id,kd_sub_keg,kd_akun,uraian,komponen,jumlah FROM $table WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 AND jumlah>0 ORDER BY id");$q->execute([$wilayah,$opd,$year]);foreach($q as $row)$target[$row['kd_sub_keg']][]=$row;}
$unmappedContracts=[];
foreach($contracts as $contractNumber=>$contract){
    if (empty($dpa[$contract['activity']]) && empty($dppa[$contract['activity']])) {
        $unmappedContracts[$contractNumber]=$contract;
        unset($contracts[$contractNumber],$realizations[$contractNumber]);
    }
}
$totals=[]; foreach($contracts as $contract)$totals[$contract['activity']]=($totals[$contract['activity']]??0)+$contract['contract_value'];
$budgets=[];
foreach($totals as $code=>$total){$dppaTotal=array_sum(array_column($dppa[$code]??[],'jumlah'));$dpaTotal=array_sum(array_column($dpa[$code]??[],'jumlah'));if($dppaTotal>=$total-.01)$budgets[$code]=['stage'=>'dppa','rows'=>$dppa[$code],'total'=>$dppaTotal];elseif($dpaTotal>=$total-.01)$budgets[$code]=['stage'=>'dpa','rows'=>$dpa[$code],'total'=>$dpaTotal];else throw new RuntimeException("Total kontrak $total melebihi DPA/DPPA untuk $code");}
$invalidRealizations=[];
foreach($realizations as $number=>$rows){$sum=array_sum(array_column($rows,'amount'));if($sum>$contracts[$number]['contract_value']+.01){$invalidRealizations[$number]=['sp2d_total'=>$sum,'contract_value'=>$contracts[$number]['contract_value'],'rows'=>count($rows)];if($skipInvalidRealizations)unset($realizations[$number]);else throw new RuntimeException('Total SP2D melebihi kontrak '.$number.' (SP2D '.$sum.' > kontrak '.$contracts[$number]['contract_value'].'; rows '.count($rows).')');}}
$existing=$db->prepare('SELECT COUNT(*) FROM kontrak_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0');$existing->execute([$wilayah,$opd,$year]);if((int)$existing->fetchColumn()>0&&!$replace)throw new RuntimeException('Kontrak 2024 aktif sudah ada; gunakan --replace untuk mengganti hasil import sebelumnya.');
$summary=['contracts'=>count($contracts),'unmapped_contracts'=>count($unmappedContracts),'unmapped_numbers'=>array_keys($unmappedContracts),'rab_contracts'=>count($rabs),'rab_missing'=>count(array_diff(array_keys($contracts),array_keys($rabs))),'sp2d_rows'=>array_sum(array_map('count',$realizations)),'sp2d_total'=>array_sum(array_map(static fn(array $rows):float=>array_sum(array_column($rows,'amount')),$realizations)),'invalid_realisasi'=>$invalidRealizations,'stages'=>array_count_values(array_column($budgets,'stage'))];
if(!$commit){echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;exit;}

$db->beginTransaction();
try{
    if ($replace) {
        $oldQuery = $db->prepare('SELECT id FROM kontrak_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0');
        $oldQuery->execute([$wilayah,$opd,$year]);
        $oldIds = array_column($oldQuery->fetchAll(), 'id');
        if ($oldIds) {
            $marks=implode(',',array_fill(0,count($oldIds),'?'));
            foreach(['daftar_realisasi_neo','rab_paket_neo','kontrak_item_neo'] as $table) $db->prepare("UPDATE `$table` SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE kontrak_id IN ($marks) AND is_deleted=0")->execute(array_merge([$actor],$oldIds));
            $db->prepare("UPDATE kontrak_neo SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE id IN ($marks)")->execute(array_merge([$actor],$oldIds));
        }
    }
    foreach($budgets as $budget) {$ids=array_column($budget['rows'],'id');if(!$ids)continue;$marks=implode(',',array_fill(0,count($ids),'?'));$table=$budget['stage']==='dppa'?'dppa_neo':'dpa_neo';$db->prepare("UPDATE `$table` SET setujui=1,kunci=1,tgl_update=NOW(),username_update=? WHERE id IN ($marks) AND is_deleted=0")->execute(array_merge([$actor],$ids));}
    $vendors=[];$contractIds=[];
    foreach($contracts as $contract){$vendorKey=$norm($contract['vendor']);if(!isset($vendors[$vendorKey])){$q=$db->prepare('SELECT id,nama_perusahaan FROM rekanan_neo WHERE kd_wilayah=? AND is_deleted=0');$q->execute([$wilayah]);$vendorId=null;foreach($q as $v)if($norm((string)$v['nama_perusahaan'])===$vendorKey){$vendorId=(int)$v['id'];break;}if(!$vendorId)$vendorId=$insert($db,'rekanan_neo',['kd_wilayah'=>$wilayah,'nama_perusahaan'=>$contract['vendor']?:'Tidak tercantum','alamat'=>'-','npwp'=>'-','direktur'=>'-','disable'=>0,'is_deleted'=>0,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s')]);$vendors[$vendorKey]=$vendorId;}
        $budget=$budgets[$contract['activity']];$contractId=$insert($db,'kontrak_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_sub_keg'=>$contract['activity'],'anggaran_id'=>(int)$budget['rows'][0]['id'],'rekanan_id'=>$vendors[$vendorKey],'nama_sub_keg'=>$contract['activity'],'tahap'=>$budget['stage'],'cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','bentuk_kontrak'=>'SURAT_PERJANJIAN','total_anggaran'=>$budget['total'],'nilai_hps'=>$contract['budget'],'nilai_kontrak'=>$contract['contract_value'],'nomor_kontrak'=>'600/'.$contract['number'],'tanggal_kontrak'=>$contract['date'],'uraian_kontrak'=>$contract['package'],'nama_penyedia'=>$contract['vendor'],'status_kontrak'=>'aktif','disable'=>0,'kunci'=>0,'setujui'=>0,'is_deleted'=>0,'keterangan'=>'Sumber Realisasi 2024 Fix.xls sheet '.$contract['sheet'].' baris '.$contract['row'],'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$actor]);$contractIds[$contract['number']]=$contractId;
        $left=$contract['contract_value'];$items=[];foreach($budget['rows'] as $index=>$row){$allocated=$index===array_key_last($budget['rows'])?$left:round($contract['contract_value']*(float)$row['jumlah']/$budget['total'],2);$left-=$allocated;$items[]=$insert($db,'kontrak_item_neo',['kontrak_id'=>$contractId,'tahap'=>$budget['stage'],'anggaran_id'=>(int)$row['id'],'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_sub_keg'=>$contract['activity'],'kd_akun'=>$row['kd_akun'],'uraian'=>$row['uraian'].' - '.($row['komponen']?:$contract['package']),'pagu'=>$row['jumlah'],'nilai_kontrak'=>$allocated,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);}
        foreach($rabs[$contract['number']]??[] as $rab)$insert($db,'rab_paket_neo',['kontrak_id'=>$contractId,'kontrak_item_id'=>$items[0]??null,'tahun'=>$year,'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'id_renja_p'=>0,'id_dpa'=>$budget['stage']==='dpa'?(int)$budget['rows'][0]['id']:0,'id_dppa'=>$budget['stage']==='dppa'?(int)$budget['rows'][0]['id']:0,'nomor'=>$rab['number'],'uraian'=>$rab['description'],'satuan'=>$rab['unit'],'type'=>'RAB Kontrak 2024','vol_hps'=>$rab['volume'],'vol_penawaran'=>$rab['volume'],'vol_negoisasi'=>$rab['volume'],'harga_sat_hps'=>$rab['price'],'harga_sat_penawaran'=>$rab['price'],'harga_sat_negoisasi'=>$rab['price'],'jumlah_hps'=>$rab['amount'],'jumlah_penawaran'=>$rab['amount'],'jumlah_negoisasi'=>$rab['amount'],'bobot'=>0,'keterangan'=>'Sheet '.$contract['number'].' baris '.$rab['source_row'],'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);
        foreach($realizations[$contract['number']]??[] as $index=>$realization)$insert($db,'daftar_realisasi_neo',['tahun'=>$year,'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'kd_sub_keg'=>$contract['activity'],'kd_akun'=>$budget['rows'][0]['kd_akun'],'id_paket'=>0,'kontrak_id'=>$contractId,'transaksi_uuid'=>sprintf('%08d-0000-4000-8000-%012d',$contractId,$index+1),'rab_id'=>null,'ket_paket'=>$contract['package'],'id_uraian_paket'=>0,'ket_uraian_paket'=>$realization['description']?:$contract['package'],'id_dok_anggaran'=>(int)$budget['rows'][0]['id'],'dok'=>$budget['stage'],'vol'=>1,'jumlah'=>$realization['amount'],'tanggal'=>$realization['date'],'periode'=>(int)date('n',strtotime($realization['date'])),'progress_fisik'=>0,'progress_keuangan'=>round($realization['amount']/$contract['contract_value']*100,2),'nomor_bukti'=>$realization['number'],'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'keterangan'=>'Sumber Realisasi 2024 Fix.xls sheet '.$realization['sheet'].' baris '.$realization['row'],'disable'=>0,'is_deleted'=>0,'setujui'=>0,'kunci'=>0]);
    }
    $db->commit();$summary['inserted_contracts']=count($contractIds);echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){$db->rollBack();throw $e;}