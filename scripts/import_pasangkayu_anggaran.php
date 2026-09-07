<?php
/**
 * Impor rincian SIPD awal dan perubahan ke Renja/RKA/DPA serta
 * Renja Perubahan/RKA Perubahan/DPPA untuk satu tahun dan OPD.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../app/Core/DB.php';

$initialPath = $argv[1] ?? '';
$changePath = $argv[2] ?? '';
foreach ([$initialPath, $changePath] as $path) if (!is_file($path)) {
    throw new RuntimeException('Pemakaian: php scripts/import_pasangkayu_anggaran.php JSON_AWAL JSON_PERUBAHAN');
}
$initial = json_decode(file_get_contents($initialPath), true, 512, JSON_THROW_ON_ERROR);
$change = json_decode(file_get_contents($changePath), true, 512, JSON_THROW_ON_ERROR);
foreach (['tahun','kd_wilayah','kd_opd'] as $field) if (($initial[$field] ?? null) !== ($change[$field] ?? null)) {
    throw new RuntimeException("Scope sumber awal dan perubahan berbeda pada $field");
}
$year = (int)($initial['tahun'] ?? 0);
$wilayah = (string)($initial['kd_wilayah'] ?? '');
$opd = (string)($initial['kd_opd'] ?? '');
if ($year < 2000 || $year > 2100 || $wilayah !== '76.01' || $opd === '') {
    throw new RuntimeException('Scope sumber tidak valid');
}

$verifyPayload = static function(array $payload, string $label): void {
    $items = 0; $amount = 0.0;
    foreach (($payload['documents'] ?? []) as $document) {
        $items += count($document['items'] ?? []);
        foreach (($document['items'] ?? []) as $item) $amount += (float)($item['jumlah'] ?? 0);
    }
    if (!$items || $items !== (int)($payload['total_items'] ?? -1) || abs($amount - (float)($payload['total_amount'] ?? -1)) > .01) {
        throw new RuntimeException("Rekonsiliasi JSON $label gagal");
    }
};
$verifyPayload($initial, 'awal');
$verifyPayload($change, 'perubahan');

$db = DB::getInstance();
$scope = [$wilayah, $opd, $year];
$username = 'IMPORT_DOKUMEN_SIPD_PUPR_PASANGKAYU_' . $year;
$initialTables = ['renja_neo','rka_neo','dpa_neo'];
$changeTables = ['renja_p_neo','rka_p_neo','dppa_neo'];
$tables = array_merge($initialTables, $changeTables);
$columns = [];
foreach ($tables as $table) $columns[$table] = array_flip(array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field'));
$filter = static fn(array $row, array $allowed): array => array_intersect_key($row, $allowed);
$normalize = static fn(string $value): string => mb_strtolower(trim((string)preg_replace('/\s+/u', ' ', $value)), 'UTF-8');
$matchKey = static function(string $subCode, array $item) use ($normalize): string {
    return implode('|', [
        $subCode, (string)($item['kd_akun'] ?? ''),
        $normalize((string)($item['kelompok'] ?? '')),
        $normalize((string)($item['uraian_kelompok'] ?? '')),
        $normalize((string)($item['komponen'] ?? '')),
        $normalize((string)($item['spesifikasi'] ?? '')),
        $normalize((string)($item['satuan'] ?? '')),
    ]);
};

$standards = [];
foreach ($db->query('SELECT id,tipe,uraian,harga,tkdn FROM master_biaya WHERE kd_wilayah=? AND tahun=? AND is_deleted=0', [$wilayah,$year])->fetchAll() as $row) {
    $standards[$normalize((string)$row['uraian']).'|'.number_format((float)$row['harga'],2,'.','')][] = $row;
}
$funds = $db->query('SELECT id,uraian FROM sumber_dana_neo WHERE is_deleted=0')->fetchAll();
$fundId = static function(string $name) use ($funds, $normalize): ?int {
    $needle = $normalize($name);
    foreach ($funds as $fund) {
        $hay = $normalize((string)$fund['uraian']);
        if ($needle !== '' && ($hay === $needle || str_contains($hay,$needle) || str_contains($needle,$hay))) return (int)$fund['id'];
    }
    return null;
};
$baseRow = static function(array $document, array $item) use ($scope,$standards,$normalize,$fundId,$username): array {
    $standardKey = $normalize((string)$item['komponen']).'|'.number_format((float)$item['harga_satuan'],2,'.','');
    $standard = $standards[$standardKey][0] ?? null;
    $account = (string)$item['kd_akun'];
    $row = [
        'source_table'=>'sipd_dpa_pdf','source_id'=>null,
        'kd_wilayah'=>$scope[0],'kd_opd'=>$scope[1],'tahun'=>$scope[2],
        'kd_sub_keg'=>(string)$document['sub_kegiatan'],'kd_akun'=>$account,'kel_rek'=>$account,
        'objek_belanja'=>str_starts_with($account,'5.2') ? 'belanja_modal' : 'belanja_operasi',
        'uraian'=>(string)($item['uraian_kelompok'] ?? ''),'jenis_kelompok'=>(string)($item['jenis_kelompok'] ?? 'pemaketan'),
        'kelompok'=>(string)($item['kelompok'] ?? ''),'jenis_standar_harga'=>$standard ? strtoupper((string)$standard['tipe']) : null,
        'id_standar_harga'=>$standard['id'] ?? null,'komponen'=>(string)($item['komponen'] ?? ''),
        'spesifikasi'=>(string)($item['spesifikasi'] ?? ''),'tkdn'=>$standard['tkdn'] ?? 0,
        'pajak'=>(float)($item['pajak'] ?? 0),'harga_satuan'=>(float)($item['harga_satuan'] ?? 0),
        'volume'=>(float)($item['volume'] ?? 0),'koefisien_keterangan'=>(string)($item['koefisien_keterangan'] ?? ''),
        'sat_5'=>(string)($item['satuan'] ?? ''),'jumlah'=>(float)($item['jumlah'] ?? 0),
        'sumber_dana_id'=>$fundId((string)($item['sumber_dana'] ?? '')),
        'sumber_dana_teks'=>(string)(($item['sumber_dana'] ?? '') ?: ($document['sumber_pendanaan'] ?? '')),
        'keterangan'=>'Sumber SIPD: '.(string)($document['source_file'] ?? ''),
        'disable'=>0,'kunci'=>0,'setujui'=>0,'is_deleted'=>0,
        'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$username,
    ];
    for ($i=1; $i<=5; $i++) {
        $row['vol_'.$i] = (float)($item['factors'][$i-1] ?? 0);
        $row['sat_'.$i] = (string)($item['factor_units'][$i-1] ?? '');
    }
    return $row;
};
$withInitial = static function(array $current, ?array $old, string $status): array {
    $current['status_perubahan'] = $status;
    foreach (['jenis_standar_harga','id_standar_harga','komponen','spesifikasi','tkdn','pajak','harga_satuan','volume','jumlah'] as $field) {
        $current[$field.'_awal'] = $old[$field] ?? (in_array($field,['tkdn','pajak','harga_satuan','volume','jumlah'],true) ? 0 : null);
    }
    return $current;
};
$sameFinancial = static fn(array $a, array $b): bool =>
    abs((float)$a['harga_satuan']-(float)$b['harga_satuan']) < .0001 &&
    abs((float)$a['volume']-(float)$b['volume']) < .0001 &&
    abs((float)$a['jumlah']-(float)$b['jumlah']) < .01 &&
    abs((float)$a['pajak']-(float)$b['pajak']) < .01;

$cashStats = ['dpa_documents'=>0,'dppa_documents'=>0,'skipped_documents'=>0,'rows'=>0];
$insertCash = static function(string $documentType, array $document) use ($db,$scope,$username,&$cashStats): void {
    $monthly = $document['monthly'] ?? [];
    $detailTotal = (float)($document['total_amount'] ?? 0);
    if (!$monthly || abs(array_sum($monthly)-$detailTotal) > .01) { $cashStats['skipped_documents']++; return; }
    $accountTotals = [];
    foreach (($document['items'] ?? []) as $item) $accountTotals[$item['kd_akun']] = ($accountTotals[$item['kd_akun']] ?? 0) + (float)$item['jumlah'];
    $accounts = array_keys($accountTotals);
    foreach ($monthly as $month=>$monthTotal) {
        $left = (float)$monthTotal;
        foreach ($accounts as $index=>$account) {
            $value = $index === array_key_last($accounts) ? $left : round($detailTotal ? (float)$monthTotal*$accountTotals[$account]/$detailTotal : 0, 2);
            $left -= $value;
            $db->query('INSERT INTO rencana_rekening_anggaran_neo(dokumen,kd_wilayah,kd_opd,tahun,kd_sub_keg,kd_akun,jenis,bulan,nilai,username_insert,is_deleted) VALUES(?,?,?,?,?,?,?,?,?,?,0) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai),username_update=VALUES(username_insert),tgl_update=NOW(),is_deleted=0', [$documentType,...$scope,(string)$document['sub_kegiatan'],$account,'belanja',(int)$month,$value,$username]);
            $cashStats['rows']++;
        }
    }
    $cashStats[$documentType.'_documents']++;
};

$stats = ['initial'=>0,'change_source'=>0,'unchanged'=>0,'modified'=>0,'added'=>0,'deleted'=>0,'standard_matched_initial'=>0,'standard_matched_change'=>0];
$db->begin();
try {
    foreach ($tables as $table) $db->query("UPDATE `$table` SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0", [$username,...$scope]);
    foreach (['dpa','dppa'] as $doc) $db->query('UPDATE rencana_rekening_anggaran_neo SET is_deleted=1,tgl_update=NOW(),username_update=? WHERE dokumen=? AND kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0', [$username,$doc,...$scope]);

    $initialIndex = [];
    foreach ($initial['documents'] as $document) {
        foreach ($document['items'] as $item) {
            $base = $baseRow($document,$item);
            if ($base['id_standar_harga']) $stats['standard_matched_initial']++;
            $renjaId = (int)$db->insert('renja_neo',$filter($base,$columns['renja_neo']));
            $rka = $base; $rka['source_table']='renja_neo'; $rka['source_id']=$renjaId;
            $rkaId = (int)$db->insert('rka_neo',$filter($rka,$columns['rka_neo']));
            $dpa = $base; $dpa['source_table']='rka_neo'; $dpa['source_id']=$rkaId;
            $dpaId = (int)$db->insert('dpa_neo',$filter($dpa,$columns['dpa_neo']));
            $initialIndex[$matchKey((string)$document['sub_kegiatan'],$item)][] = ['row'=>$base,'ids'=>['renja'=>$renjaId,'rka'=>$rkaId,'dpa'=>$dpaId],'used'=>false];
            $stats['initial']++;
        }
        $insertCash('dpa',$document);
    }

    foreach ($change['documents'] as $document) {
        foreach ($document['items'] as $item) {
            $current = $baseRow($document,$item);
            if ($current['id_standar_harga']) $stats['standard_matched_change']++;
            $key = $matchKey((string)$document['sub_kegiatan'],$item);
            $matched = null;
            if (!empty($initialIndex[$key])) foreach ($initialIndex[$key] as $index=>$candidate) if (!$candidate['used']) {
                $matched = $candidate; $initialIndex[$key][$index]['used'] = true; break;
            }
            $status = !$matched ? 'tambah' : ($sameFinancial($current,$matched['row']) ? 'awal' : 'ubah');
            $stats[$status==='awal'?'unchanged':($status==='ubah'?'modified':'added')]++;
            foreach ([['renja_p_neo','renja'],['rka_p_neo','rka'],['dppa_neo','dpa']] as [$table,$logical]) {
                $row = $withInitial($current,$matched['row'] ?? null,$status);
                $row['source_table'] = $logical.'_neo'; $row['source_id'] = $matched['ids'][$logical] ?? null;
                $db->insert($table,$filter($row,$columns[$table]));
            }
            $stats['change_source']++;
        }
        $insertCash('dppa',$document);
    }

    // Rincian awal yang tidak lagi muncul tetap disimpan sebagai jejak "hapus".
    foreach ($initialIndex as $candidates) foreach ($candidates as $candidate) if (!$candidate['used']) {
        $current = $candidate['row']; $current['volume']=0; $current['jumlah']=0;
        for ($i=1;$i<=5;$i++) $current['vol_'.$i]=0;
        foreach ([['renja_p_neo','renja'],['rka_p_neo','rka'],['dppa_neo','dpa']] as [$table,$logical]) {
            $row=$withInitial($current,$candidate['row'],'hapus');
            $row['source_table']=$logical.'_neo'; $row['source_id']=$candidate['ids'][$logical];
            $db->insert($table,$filter($row,$columns[$table]));
        }
        $stats['deleted']++;
    }

    $stageTotals = ['renja'=>(float)$initial['total_amount'],'rka'=>(float)$initial['total_amount'],'dpa'=>(float)$initial['total_amount'],'renja_p'=>(float)$change['total_amount'],'rka_p'=>(float)$change['total_amount'],'dppa'=>(float)$change['total_amount']];
    foreach ($stageTotals as $document=>$total) {
        $limit=$db->query('SELECT id FROM batas_pagu_opd_neo WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND dokumen=? LIMIT 1', [...$scope,$document])->fetch();
        $limitData=['pagu_maksimal'=>$total,'keterangan'=>'Total rincian dokumen SIPD PUPR Pasangkayu '.$year,'tgl_update'=>date('Y-m-d H:i:s'),'username_update'=>$username,'is_deleted'=>0];
        if ($limit) $db->update('batas_pagu_opd_neo',$limitData,'WHERE id=?',[(int)$limit['id']]);
        else $db->insert('batas_pagu_opd_neo',$limitData+['kd_wilayah'=>$wilayah,'kd_opd'=>$opd,'tahun'=>$year,'dokumen'=>$document,'tgl_insert'=>date('Y-m-d H:i:s'),'username_insert'=>$username]);
    }

    foreach (array_combine($tables,['renja','rka','dpa','renja_p','rka_p','dppa']) as $table=>$logical) {
        $audit=$db->query("SELECT COUNT(*) jumlah,COALESCE(SUM(jumlah),0) total FROM `$table` WHERE kd_wilayah=? AND kd_opd=? AND tahun=? AND is_deleted=0",$scope)->fetch();
        $expectedCount=in_array($logical,['renja','rka','dpa'],true)?$stats['initial']:($stats['change_source']+$stats['deleted']);
        if ((int)$audit['jumlah']!==$expectedCount || abs((float)$audit['total']-$stageTotals[$logical])>.01) throw new RuntimeException("Rekonsiliasi tabel $table gagal");
    }
    $db->commit();
    echo json_encode(['tahun'=>$year,'dokumen_awal'=>count($initial['documents']),'dokumen_perubahan'=>count($change['documents']),'total_awal'=>$initial['total_amount'],'total_perubahan'=>$change['total_amount'],'rincian'=>$stats,'rencana_kas'=>$cashStats],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
} catch (Throwable $exception) {
    $db->rollback(); throw $exception;
}
