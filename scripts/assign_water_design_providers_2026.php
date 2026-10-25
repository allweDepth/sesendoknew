<?php
/** User-directed 4+4 allocation; reuse verified companies and existing seven-row RABs. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../app/Core/DB.php';
$db = DB::getInstance();
$apply = in_array('--apply', $argv, true);
$actor = 'ASSIGN_WATER_PROVIDERS_2026';
$root = '/Users/nabiila_inayah/Library/Mobile Documents/com~apple~CloudDocs/2026/Pengadaan 2026/Perubahan 2026/Perusahaan Stok design Air bersih/';
$sources = [588 => $root.'Perusahaan 1/COMPANY PROFILE CV. PCE.pdf', 461 => $root.'Perusahaan 2/PROFIL PERUSAHAAN VISUAL ART DESIGN 2026.pdf'];
$names = [588 => 'CV. PATAMBERO CONSULTANT ENGINEERING', 461 => 'CV. VISUAL ART DESIGN'];
$report = ['assignment_basis' => 'User: four packages per company; folder order Perusahaan 1 then Perusahaan 2; contract order Air Minum I–V then Sanitasi I–III.', 'companies' => [], 'contracts' => []];
foreach ($sources as $id => $path) {
    if (!is_file($path)) throw new RuntimeException('Missing company profile');
    $v = $db->query('SELECT * FROM rekanan_neo WHERE id=? AND kd_wilayah=? AND is_deleted=0 AND disable=0', [$id, '76.01'])->fetch();
    if (!$v || $v['nama_perusahaan'] !== $names[$id]) throw new RuntimeException('Company identity mismatch');
    $report['companies'][] = ['id' => $id, 'name' => $names[$id], 'source' => $path, 'sha256' => hash_file('sha256', $path), 'account_status' => $v['no_rekening'] ? 'Existing database account retained' : 'Not found; left empty'];
}
$contracts = [];
foreach (range(810, 817) as $id) {
    $k = $db->query('SELECT * FROM kontrak_neo WHERE id=? AND kd_wilayah=? AND kd_opd=? AND tahun=2026 AND is_deleted=0', [$id, '76.01', '1.03.0.00.0.00.01.0000'])->fetch();
    $vendor = $id < 814 ? 588 : 461;
    if (!$k || $k['status_kontrak'] !== 'draft' || $k['setujui'] || $k['kunci'] || $k['username_insert'] !== 'IMPORT_PERUBAHAN_PUPR_2026' || ($k['rekanan_id'] !== null && (int)$k['rekanan_id'] !== $vendor) || (float)$k['total_anggaran'] !== 100000000.0) throw new RuntimeException('Contract guard failed: '.$id);
    $r = $db->query('SELECT COUNT(*) n,SUM(jumlah_hps) hps,SUM(jumlah_negoisasi) nego FROM rab_paket_neo WHERE kontrak_id=? AND is_deleted=0', [$id])->fetch();
    if ((int)$r['n'] !== 7 || abs((float)$r['hps'] - 99999642.96) > .01 || abs((float)$r['nego'] - 99838000) > .01) throw new RuntimeException('RAB mismatch');
    $items = $db->query('SELECT * FROM kontrak_item_neo WHERE kontrak_id=? AND is_deleted=0', [$id])->fetchAll();
    if (count($items) !== 1) throw new RuntimeException('Unexpected item count');
    $contracts[] = [$k, $vendor, $r, $items[0]];
    $report['contracts'][] = ['id' => $id, 'package' => $k['uraian_kontrak'], 'nomor_kontrak' => $k['nomor_kontrak'], 'vendor_id' => $vendor, 'vendor' => $names[$vendor], 'rab_rows' => 7, 'hps' => $r['hps'], 'negotiated_estimate' => $r['nego']];
}
if (!$apply) { echo json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL; exit; }
$db->begin();
try {
    // Existing bank evidence is preserved. No account is inferred from a company name.
    $db->update('rekanan_neo', ['email' => 'visualartdesign09@gmail.com', 'username_update' => $actor, 'tgl_update' => date('Y-m-d H:i:s')], 'WHERE id=? AND email IS NULL', [461]);
    foreach ($contracts as [$k, $vendor, $r, $item]) {
        $db->update('kontrak_neo', ['rekanan_id' => $vendor, 'nama_penyedia' => $names[$vendor], 'nilai_hps' => $r['hps'], 'nilai_kontrak' => $r['nego'], 'username_update' => $actor, 'tgl_update' => date('Y-m-d H:i:s')], 'WHERE id=?', [$k['id']]);
        $db->update('kontrak_item_neo', ['nilai_kontrak' => $r['nego']], 'WHERE id=?', [$item['id']]);
        $docs = $db->query('SELECT id,data_isian FROM dokumen_pengadaan_neo WHERE kontrak_id=? AND is_deleted=0 AND status=? AND username_insert=?', [$k['id'], 'DRAFT', 'IMPORT_PERUBAHAN_PUPR_2026'])->fetchAll();
        foreach ($docs as $doc) {
            $meta = json_decode($doc['data_isian'], true, 512, JSON_THROW_ON_ERROR);
            $meta['penyedia_status'] = 'PROFIL_PERUSAHAAN_USER';
            $db->update('dokumen_pengadaan_neo', ['data_isian' => json_encode($meta, JSON_UNESCAPED_UNICODE)], 'WHERE id=?', [$doc['id']]);
        }
    }
    $counts = $db->query('SELECT rekanan_id,COUNT(*) n FROM kontrak_neo WHERE id BETWEEN 810 AND 817 GROUP BY rekanan_id')->fetchAll();
    if (count($counts) !== 2 || array_sum(array_column($counts, 'n')) !== 8 || (int)$counts[0]['n'] !== 4 || (int)$counts[1]['n'] !== 4) throw new RuntimeException('Allocation verification failed');
    $db->commit();
} catch (Throwable $e) { $db->rollback(); throw $e; }
$dir = __DIR__.'/../storage/imports/kontrak_perubahan_2026/';
file_put_contents($dir.'water_provider_assignment.json', json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL);
echo json_encode(['updated_contracts' => 8, 'companies_reused' => 2, 'packages_per_company' => 4, 'rab_rows_preserved' => 56, 'missing_accounts_left_empty' => true]).PHP_EOL;
