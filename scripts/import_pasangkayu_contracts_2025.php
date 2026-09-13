<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$contractPath = $argv[1] ?? $root . '/2025/Daftar Kontrak 2025.xlsx';
$rabPath = $argv[2] ?? $root . '/2025/RAB Kontrak 2025.xlsx';
$realizationPath = $argv[3] ?? $root . '/2025/Realisasi 2025.xls';
$commit = in_array('--commit', $argv, true);

foreach ([$contractPath, $rabPath, $realizationPath] as $path) {
    if (!is_file($path)) {
        throw new RuntimeException('File tidak ditemukan: ' . $path);
    }
}

$config = include $root . '/config/database.php';
$dsn = !empty($config['socket'])
    ? 'mysql:unix_socket=' . $config['socket'] . ';dbname=' . $config['dbname']
    : 'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'];
$db = new PDO($dsn, (string)$config['username'], (string)$config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");

$wilayah = '76.01';
$opd = '1.03.0.00.0.00.01.0000';
$year = 2025;
$actor = 'IMPORT_KONTRAK_REALISASI_PUPR_2025';
$normalise = static fn(string $value): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $value))), 'UTF-8');
$number = static fn(mixed $value): ?float => is_numeric($value) ? (float)$value : null;

$date = static function (mixed $value): ?string {
    if ($value === null || trim((string)$value) === '') {
        return null;
    }
    if (is_numeric($value) && (float)$value > 20000) {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
    }
    $value = trim((string)$value);
    if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/u', $value, $match)) {
        $months = ['januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12];
        $month = $months[mb_strtolower($match[2], 'UTF-8')] ?? null;
        if ($month) {
            return sprintf('%04d-%02d-%02d', (int)$match[3], $month, (int)$match[1]);
        }
    }
    return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) ? substr($value, 0, 10) : null;
};
$insert = static function (PDO $db, string $table, array $row): int {
    $columns = array_keys($row);
    $marks = implode(',', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES (' . $marks . ')';
    $db->prepare($sql)->execute(array_values($row));
    return (int)$db->lastInsertId();
};
$load = static function (string $path): \PhpOffice\PhpSpreadsheet\Spreadsheet {
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    return $reader->load($path);
};

$contractsSheet = $load($contractPath)->getActiveSheet();
$contracts = [];
for ($row = 1; $row <= $contractsSheet->getHighestRow(); $row++) {
    $rawNumber = trim((string)$contractsSheet->getCell("B$row")->getValue());
    $contractValue = $number($contractsSheet->getCell("M$row")->getValue());
    $rawSubActivity = preg_replace('/\s+/u', '', trim((string)$contractsSheet->getCell("AF$row")->getValue()));
    preg_match('/1\.03\.\d{2}\.\d\.\d{2}\.\d{4}/', $rawSubActivity, $subActivityMatch);
    $subActivity = $subActivityMatch[0] ?? $rawSubActivity;
    if (!preg_match('/^\d{3,9}$/', $rawNumber) || (int)$rawNumber === 0 || $contractValue === null || $contractValue <= 0 || $subActivity === '') {
        continue;
    }
    if (isset($contracts[$rawNumber])) {
        throw new RuntimeException('Nomor kontrak ganda pada daftar: ' . $rawNumber);
    }
    $contracts[$rawNumber] = ['number'=>$rawNumber,'package'=>trim((string)$contractsSheet->getCell("C$row")->getValue()),'activity'=>trim((string)$contractsSheet->getCell("D$row")->getValue()),'sub_activity'=>$subActivity,'budget'=>$number($contractsSheet->getCell("J$row")->getValue()) ?? 0,'contract_value'=>$contractValue,'vendor'=>trim((string)$contractsSheet->getCell("W$row")->getValue()) ?: trim((string)$contractsSheet->getCell("N$row")->getValue()),'address'=>trim((string)$contractsSheet->getCell("R$row")->getValue()),'npwp'=>trim((string)$contractsSheet->getCell("Q$row")->getValue()),'director'=>trim((string)$contractsSheet->getCell("O$row")->getValue()),'date'=>$date($contractsSheet->getCell("AG$row")->getValue()),'row'=>$row];
}

$rabBook = $load($rabPath);
$rabs = [];
foreach ($rabBook->getWorksheetIterator() as $sheet) {
    $contractNumber = trim($sheet->getTitle());
    if (!preg_match('/^\d{3,9}$/', $contractNumber)) {
        continue;
    }
    for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
        $itemNumber = trim((string)$sheet->getCell("A$row")->getValue());
        $description = trim((string)$sheet->getCell("B$row")->getValue());
        $volume = $number($sheet->getCell("D$row")->getValue());
        $unit = trim((string)$sheet->getCell("G$row")->getValue());
        $price = $number($sheet->getCell("J$row")->getValue()) ?? $number($sheet->getCell("I$row")->getValue());
        if ($description === '' || $volume === null || $volume <= 0 || $price === null || $price < 0 || $unit === '' || !preg_match('/^\d+(?:\.\d+)?$/', $itemNumber)) {
            continue;
        }
        $rabs[$contractNumber][] = ['number'=>$itemNumber,'description'=>$description,'unit'=>$unit,'volume'=>$volume,'price'=>$price,'amount'=>$volume*$price,'source_row'=>$row];
    }
}

$realizations = [];
foreach ($load($realizationPath)->getWorksheetIterator() as $sheet) {
    $current = null;
    $maxColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
    for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
        $contractText = trim((string)$sheet->getCell("F$row")->getValue());
        $contractMatch = preg_match('~^\d{3}/(\d{1,15})/~i', $contractText, $match) ? $match[1] : null;
        $contractValue = $number($sheet->getCell("H$row")->getValue());
        if ($contractMatch && $contractValue !== null && $contractValue > 0) {
            $current = isset($contracts[$contractMatch]) && abs($contractValue - $contracts[$contractMatch]['contract_value']) <= 0.01 ? $contractMatch : null;
        }
        if (!$current) {
            continue;
        }
        $sp2d = null;
        $sp2dColumn = null;
        for ($column = 1; $column <= $maxColumn; $column++) {
            $cell = trim((string)$sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row)->getValue());
            if (preg_match('~^76\.01/.+/LS/~', $cell)) {
                $sp2d = $cell;
                $sp2dColumn = $column;
                break;
            }
        }
        if ($sp2d === null) {
            continue;
        }
        $amount = null;
        for ($column = $sp2dColumn + 1; $column <= $maxColumn; $column++) {
            $candidate = $number($sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row)->getValue());
            if ($candidate !== null && $candidate > 0) {
                $amount = $candidate;
                break;
            }
        }
        $sp2dDate = null;
        for ($column = $sp2dColumn - 1; $column >= 1; $column--) {
            $candidate = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row)->getValue();
            if ($candidate !== null && trim((string)$candidate) !== '') {
                $sp2dDate = $date($candidate);
                if ($sp2dDate) {
                    break;
                }
            }
        }
        if ($amount !== null && $amount > 0 && $sp2dDate) {
            $realizations[$current][] = ['number'=>$sp2d,'date'=>$sp2dDate,'amount'=>$amount,'sheet'=>$sheet->getTitle(),'row'=>$row,'description'=>trim((string)$sheet->getCell("B$row")->getValue())];
        }
    }
}

$dpa = [];
$dppa = [];
foreach (['dpa_neo'=>&$dpa,'dppa_neo'=>&$dppa] as $table=>&$target) {
    $query = $db->prepare("SELECT id,kd_sub_keg,kd_akun,uraian,komponen,jumlah FROM $table WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0 ORDER BY id");
    $query->execute([$wilayah,$opd,$year]);
    foreach ($query as $row) {
        if ((float)$row['jumlah'] > 0) {
            $target[$row['kd_sub_keg']][] = $row;
        }
    }
}

$bySubActivity = [];
foreach ($contracts as $contract) {
    $bySubActivity[$contract['sub_activity']] = ($bySubActivity[$contract['sub_activity']] ?? 0) + $contract['contract_value'];
}
$budgetRows = [];
foreach ($bySubActivity as $subActivity=>$contractTotal) {
    $dppaTotal = array_sum(array_column($dppa[$subActivity] ?? [], 'jumlah'));
    $dpaTotal = array_sum(array_column($dpa[$subActivity] ?? [], 'jumlah'));
    if ($dppaTotal >= $contractTotal - 0.01) {
        $budgetRows[$subActivity] = ['stage'=>'dppa','rows'=>$dppa[$subActivity],'total'=>$dppaTotal];
    } elseif ($dpaTotal >= $contractTotal - 0.01) {
        $budgetRows[$subActivity] = ['stage'=>'dpa','rows'=>$dpa[$subActivity],'total'=>$dpaTotal];
    } else {
        throw new RuntimeException(sprintf('Total kontrak %.2f melebihi DPA/DPPA untuk %s (DPA %.2f, DPPA %.2f)', $contractTotal,$subActivity,$dpaTotal,$dppaTotal));
    }
}

$realizationTotal = [];
foreach ($realizations as $contractNumber=>$rows) {
    $realizationTotal[$contractNumber] = array_sum(array_column($rows,'amount'));
    if ($realizationTotal[$contractNumber] > $contracts[$contractNumber]['contract_value'] + 0.01) {
        throw new RuntimeException('Total SP2D melebihi nilai kontrak: ' . $contractNumber);
    }
}
$existing = $db->prepare('SELECT COUNT(*) FROM kontrak_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0');
$existing->execute([$wilayah,$opd,$year]);
if ((int)$existing->fetchColumn() > 0) {
    throw new RuntimeException('Kontrak aktif tahun 2025 sudah ada; import dibatalkan untuk mencegah duplikasi.');
}

$summary = ['contracts'=>count($contracts),'rab_contracts'=>count($rabs),'rab_missing'=>count(array_diff(array_keys($contracts),array_keys($rabs))),'sp2d_rows'=>array_sum(array_map('count',$realizations)),'sp2d_total'=>array_sum($realizationTotal),'stages'=>array_count_values(array_column($budgetRows,'stage'))];
if (!$commit) {
    echo json_encode($summary, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$db->beginTransaction();
try {
    $lockedBudgetIds = [];
    foreach ($budgetRows as $budget) {
        if ($budget['stage'] === 'dppa') {
            foreach ($budget['rows'] as $budgetRow) {
                $lockedBudgetIds[(int)$budgetRow['id']] = true;
            }
        }
    }
    if ($lockedBudgetIds) {
        $marks = implode(',', array_fill(0, count($lockedBudgetIds), '?'));
        $db->prepare("UPDATE dppa_neo SET setujui=1, kunci=1, tgl_update=NOW(), username_update=? WHERE id IN ($marks) AND is_deleted=0")->execute(array_merge([$actor], array_keys($lockedBudgetIds)));
    }
    $vendorIds = [];
    $contractIds = [];
    foreach ($contracts as $contract) {
        $vendorKey = $normalise($contract['vendor']);
        if (!isset($vendorIds[$vendorKey])) {
            $vendorQuery = $db->prepare('SELECT id,nama_perusahaan FROM rekanan_neo WHERE kd_wilayah=? AND is_deleted=0 ORDER BY id');
            $vendorQuery->execute([$wilayah]);
            $vendorId = null;
            foreach ($vendorQuery as $existingVendor) {
                if ($normalise((string)$existingVendor['nama_perusahaan']) === $vendorKey) {
                    $vendorId = $existingVendor['id'];
                    break;
                }
            }
            if (!$vendorId) {
                $vendorId = $insert($db,'rekanan_neo',['kd_wilayah'=>$wilayah,'nama_perusahaan'=>$contract['vendor'] ?: 'Tidak tercantum','alamat'=>$contract['address'] ?: '-','npwp'=>$contract['npwp'] ?: '-','direktur'=>$contract['director'] ?: '-','disable'=>0,'is_deleted'=>0,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s')]);
            }
            $vendorIds[$vendorKey] = (int)$vendorId;
        }
        $budget = $budgetRows[$contract['sub_activity']];
        $contractId = $insert($db,'kontrak_neo',['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_sub_keg'=>$contract['sub_activity'],'anggaran_id'=>(int)$budget['rows'][0]['id'],'rekanan_id'=>$vendorIds[$vendorKey],'nama_sub_keg'=>$contract['activity'],'tahap'=>$budget['stage'],'cara_pengadaan'=>'PENYEDIA','jenis_pengadaan'=>'PEKERJAAN_KONSTRUKSI','bentuk_kontrak'=>'SURAT_PERJANJIAN','total_anggaran'=>$budget['total'],'nilai_hps'=>$contract['budget'],'nilai_kontrak'=>$contract['contract_value'],'nomor_kontrak'=>'600/'.$contract['number'],'tanggal_kontrak'=>$contract['date'],'uraian_kontrak'=>$contract['package'],'nama_penyedia'=>$contract['vendor'],'status_kontrak'=>'aktif','disable'=>0,'kunci'=>0,'setujui'=>0,'is_deleted'=>0,'keterangan'=>'Sumber Daftar Kontrak 2025.xlsx baris '.$contract['row'],'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$actor]);
        $contractIds[$contract['number']] = $contractId;
        $left = $contract['contract_value'];
        $itemIds = [];
        foreach ($budget['rows'] as $index=>$budgetRow) {
            $allocated = $index===array_key_last($budget['rows']) ? $left : round($contract['contract_value']*(float)$budgetRow['jumlah']/$budget['total'],2);
            $left -= $allocated;
            $itemIds[] = $insert($db,'kontrak_item_neo',['kontrak_id'=>$contractId,'tahap'=>$budget['stage'],'anggaran_id'=>(int)$budgetRow['id'],'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'kd_sub_keg'=>$contract['sub_activity'],'kd_akun'=>$budgetRow['kd_akun'],'uraian'=>$budgetRow['uraian'].' - '.($budgetRow['komponen'] ?: $contract['package']),'pagu'=>$budgetRow['jumlah'],'nilai_kontrak'=>$allocated,'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);
        }
        foreach ($rabs[$contract['number']] ?? [] as $rab) {
            $insert($db,'rab_paket_neo',['kontrak_id'=>$contractId,'kontrak_item_id'=>$itemIds[0]??null,'tahun'=>$year,'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'id_renja_p'=>0,'id_dpa'=>$budget['stage']==='dpa'?(int)$budget['rows'][0]['id']:0,'id_dppa'=>$budget['stage']==='dppa'?(int)$budget['rows'][0]['id']:0,'nomor'=>$rab['number'],'uraian'=>$rab['description'],'satuan'=>$rab['unit'],'type'=>'RAB Kontrak 2025','vol_hps'=>$rab['volume'],'vol_penawaran'=>$rab['volume'],'vol_negoisasi'=>$rab['volume'],'harga_sat_hps'=>$rab['price'],'harga_sat_penawaran'=>$rab['price'],'harga_sat_negoisasi'=>$rab['price'],'jumlah_hps'=>$rab['amount'],'jumlah_penawaran'=>$rab['amount'],'jumlah_negoisasi'=>$rab['amount'],'bobot'=>0,'keterangan'=>'Sheet '.$contract['number'].' baris '.$rab['source_row'],'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'is_deleted'=>0]);
        }
        foreach ($realizations[$contract['number']] ?? [] as $realizationIndex=>$realization) {
            $insert($db,'daftar_realisasi_neo',['tahun'=>$year,'kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'kd_sub_keg'=>$contract['sub_activity'],'kd_akun'=>$budget['rows'][0]['kd_akun'],'id_paket'=>0,'kontrak_id'=>$contractId,'transaksi_uuid'=>sprintf('%08d-0000-4000-8000-%012d',$contractId,$realizationIndex+1),'rab_id'=>null,'ket_paket'=>$contract['package'],'id_uraian_paket'=>0,'ket_uraian_paket'=>$realization['description'] ?: $contract['package'],'id_dok_anggaran'=>(int)$budget['rows'][0]['id'],'dok'=>$budget['stage'],'vol'=>1,'jumlah'=>$realization['amount'],'tanggal'=>$realization['date'],'periode'=>(int)date('n',strtotime($realization['date'])),'progress_fisik'=>0,'progress_keuangan'=>$contract['contract_value']>0?round($realization['amount']/$contract['contract_value']*100,2):0,'nomor_bukti'=>$realization['number'],'username_insert'=>$actor,'tgl_insert'=>date('Y-m-d H:i:s'),'keterangan'=>'Sumber Realisasi 2025.xls sheet '.$realization['sheet'].' baris '.$realization['row'],'disable'=>0,'is_deleted'=>0,'setujui'=>0,'kunci'=>0]);
        }
    }
    $db->commit();
    $summary['inserted_contracts'] = count($contractIds);
    echo json_encode($summary, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $exception) {
    $db->rollBack();
    throw $exception;
}